# SQLite Migration Design

## Overview

Migrate YANPIWS from CSV-based daily files to SQLite for simpler code and historical trend queries.

## Goals

- **Historical trends**: Query data from weeks/months/years ago, compare periods
- **Simpler code**: Replace custom CSV parsing with SQL queries

## Non-Goals

- Migrating existing CSV data (fresh start, CSVs kept as archive)
- Pre-computed rollups (aggregate on-demand via SQL)

## Database Schema

Location: `data/yanpiws.db` (respects `dataPath` config)

```sql
CREATE TABLE readings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recorded_at DATETIME NOT NULL,
    sensor_id TEXT NOT NULL,
    temperature_f REAL NOT NULL,
    humidity REAL
);

CREATE INDEX idx_readings_sensor_time ON readings(sensor_id, recorded_at);
CREATE INDEX idx_readings_time ON readings(recorded_at);
```

## PHP Data Layer

New file `html/database.php`:

```php
function getDb() {
    global $YANPIWS;
    getConfig();
    $dbPath = ($YANPIWS['dataPath'] ?? 'data') . '/yanpiws.db';

    $db = new SQLite3($dbPath);
    $db->exec('PRAGMA journal_mode=WAL');
    return $db;
}

function initDb() {
    // Creates tables and indexes if not exist
}

function saveReading($sensorId, $temperatureF, $humidity = null, $timestamp = null) {
    // Single insert, replaces CSV append
}

function getReadings($sensorId, $startDate, $endDate) {
    // Returns array of readings for a sensor in date range
}

function getLatestReading($sensorId) {
    // Replaces getMostRecentTemp()
}

function getHourlyAverages($sensorId, $startDate, $endDate) {
    // SQL GROUP BY replaces convertDataToHourly()
}
```

WAL mode enables concurrent reads while writing.

## File Changes

### Modified

| File | Changes |
|------|---------|
| `parse_and_save.php` | Replace CSV append with `saveReading()` call |
| `read_and_post.php` | Replace CSV append with `saveReading()` call |
| `get_data.php` | Replace `getData()`, `getMostRecentTemp()`, `convertDataToHourly()` to use DB functions |
| `temps.php` | Update to use `getReadings()` instead of `mergeDayData()` |

### New

| File | Purpose |
|------|---------|
| `database.php` | All SQLite functions (schema, read, write) |

### Removed Code

- `cleanseData()` - SQLite prepared statements handle escaping
- `mergeDayData()` - SQL date ranges replace manual day merging
- CSV file writing logic

### Unchanged

- Config loading (`config.csv` format)
- API caching (`forecast.cache`, `moondata.cache`)
- Multi-node `sendData()` - still POSTs to remote servers

## Example Queries

**Compare this week vs same week last year:**
```sql
SELECT sensor_id, AVG(temperature_f) as avg_temp,
       strftime('%w', recorded_at) as day_of_week
FROM readings
WHERE recorded_at BETWEEN date('now', '-7 days') AND date('now')
GROUP BY sensor_id, day_of_week
```

**Monthly highs/lows:**
```sql
SELECT strftime('%Y-%m', recorded_at) as month,
       MIN(temperature_f) as low, MAX(temperature_f) as high
FROM readings
WHERE sensor_id = '211'
GROUP BY month
ORDER BY month DESC
```

**Hourly pattern for date range:**
```sql
SELECT strftime('%H', recorded_at) as hour, AVG(temperature_f)
FROM readings
WHERE sensor_id = '211' AND recorded_at > date('now', '-30 days')
GROUP BY hour
```
