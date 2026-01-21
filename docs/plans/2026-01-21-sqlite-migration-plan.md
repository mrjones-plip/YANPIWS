# SQLite Migration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace CSV-based temperature storage with SQLite for simpler code and historical queries.

**Architecture:** New `database.php` provides all SQLite operations. Existing files call these functions instead of CSV logic. Schema auto-creates on first use.

**Tech Stack:** PHP 8+ with SQLite3 extension (built-in), WAL mode for concurrent access.

---

## Task 1: Create database.php with Schema

**Files:**
- Create: `html/database.php`

**Step 1: Create the database.php file with getDb() and initDb()**

```php
<?php
/**
 * SQLite database functions for YANPIWS temperature storage
 */

/**
 * Get a SQLite database connection
 * Creates the database file if it doesn't exist
 *
 * @return SQLite3
 */
function getDb() {
    global $YANPIWS;

    // Ensure config is loaded
    if (!isset($YANPIWS['dataPath'])) {
        getConfig();
    }

    $dataPath = $YANPIWS['dataPath'] ?? '../data/';
    // Remove trailing slash if present, then add it back consistently
    $dataPath = rtrim($dataPath, '/') . '/';
    $dbPath = $dataPath . 'yanpiws.db';

    $db = new SQLite3($dbPath);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA busy_timeout=5000');

    // Initialize schema if needed
    initDb($db);

    return $db;
}

/**
 * Initialize the database schema if tables don't exist
 *
 * @param SQLite3 $db
 */
function initDb($db) {
    $db->exec('
        CREATE TABLE IF NOT EXISTS readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recorded_at DATETIME NOT NULL,
            sensor_id TEXT NOT NULL,
            temperature_f REAL NOT NULL,
            humidity REAL
        )
    ');

    // Create indexes if they don't exist
    $db->exec('CREATE INDEX IF NOT EXISTS idx_readings_sensor_time ON readings(sensor_id, recorded_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_readings_time ON readings(recorded_at)');
}
```

**Step 2: Verify the file was created correctly**

Run: `php -l html/database.php`
Expected: `No syntax errors detected`

**Step 3: Test database creation**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); \$db = getDb(); echo 'DB created' . PHP_EOL;"`
Expected: `DB created`

**Step 4: Verify schema**

Run: `sqlite3 data/yanpiws.db ".schema"`
Expected: Shows CREATE TABLE and CREATE INDEX statements

**Step 5: Commit**

```bash
git add html/database.php
git commit -m "feat: add database.php with SQLite schema initialization"
```

---

## Task 2: Add saveReading() Function

**Files:**
- Modify: `html/database.php`

**Step 1: Add saveReading() function to database.php**

Append to `html/database.php`:

```php

/**
 * Save a temperature reading to the database
 *
 * @param string $sensorId The sensor identifier
 * @param float $temperatureF Temperature in Fahrenheit
 * @param float|null $humidity Humidity percentage (optional)
 * @param string|null $timestamp ISO format datetime (defaults to now)
 * @return bool True on success
 */
function saveReading($sensorId, $temperatureF, $humidity = null, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = date('Y-m-d H:i:s');
    }

    $db = getDb();

    $stmt = $db->prepare('
        INSERT INTO readings (recorded_at, sensor_id, temperature_f, humidity)
        VALUES (:recorded_at, :sensor_id, :temperature_f, :humidity)
    ');

    $stmt->bindValue(':recorded_at', $timestamp, SQLITE3_TEXT);
    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);
    $stmt->bindValue(':temperature_f', $temperatureF, SQLITE3_FLOAT);

    if ($humidity !== null && $humidity !== '') {
        $stmt->bindValue(':humidity', (float)$humidity, SQLITE3_FLOAT);
    } else {
        $stmt->bindValue(':humidity', null, SQLITE3_NULL);
    }

    $result = $stmt->execute();
    $db->close();

    return $result !== false;
}
```

**Step 2: Verify syntax**

Run: `php -l html/database.php`
Expected: `No syntax errors detected`

**Step 3: Test saveReading()**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); \$result = saveReading('211', 72.5, 45, '2026-01-21 12:00:00'); echo \$result ? 'Saved!' : 'Failed'; echo PHP_EOL;"`
Expected: `Saved!`

**Step 4: Verify data was inserted**

Run: `sqlite3 data/yanpiws.db "SELECT * FROM readings;"`
Expected: Shows the inserted row with id=1

**Step 5: Commit**

```bash
git add html/database.php
git commit -m "feat: add saveReading() for inserting temperature data"
```

---

## Task 3: Add getLatestReading() Function

**Files:**
- Modify: `html/database.php`

**Step 1: Add getLatestReading() function to database.php**

Append to `html/database.php`:

```php

/**
 * Get the most recent temperature reading for a sensor
 *
 * @param string $sensorId The sensor identifier
 * @return array Reading data with keys: date, id, temp, label, humidity
 */
function getLatestReading($sensorId) {
    global $YANPIWS;

    $db = getDb();

    $stmt = $db->prepare('
        SELECT recorded_at, sensor_id, temperature_f, humidity
        FROM readings
        WHERE sensor_id = :sensor_id
        ORDER BY recorded_at DESC
        LIMIT 1
    ');
    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    if ($row) {
        $label = '';
        if (isset($YANPIWS['labels'][$sensorId])) {
            $label = $YANPIWS['labels'][$sensorId];
        }

        return [
            'date' => $row['recorded_at'],
            'id' => $row['sensor_id'],
            'temp' => $row['temperature_f'],
            'label' => $label,
            'humidity' => $row['humidity'] ?? ''
        ];
    }

    return [
        'date' => 'NA',
        'id' => 'No Data Found',
        'temp' => 'NA',
        'label' => 'NA',
        'humidity' => 'NA'
    ];
}
```

**Step 2: Verify syntax**

Run: `php -l html/database.php`
Expected: `No syntax errors detected`

**Step 3: Test getLatestReading()**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); print_r(getLatestReading('211'));"`
Expected: Shows array with the reading from Task 2

**Step 4: Test non-existent sensor**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); print_r(getLatestReading('999'));"`
Expected: Shows array with 'NA' values

**Step 5: Commit**

```bash
git add html/database.php
git commit -m "feat: add getLatestReading() for fetching latest sensor data"
```

---

## Task 4: Add getReadings() and getHourlyAverages() Functions

**Files:**
- Modify: `html/database.php`

**Step 1: Add getReadings() function to database.php**

Append to `html/database.php`:

```php

/**
 * Get all readings for a sensor within a date range
 *
 * @param string $sensorId The sensor identifier
 * @param string $startDate Start datetime (Y-m-d H:i:s format)
 * @param string $endDate End datetime (Y-m-d H:i:s format)
 * @return array Array of readings
 */
function getReadings($sensorId, $startDate, $endDate) {
    $db = getDb();

    $stmt = $db->prepare('
        SELECT recorded_at, sensor_id, temperature_f, humidity
        FROM readings
        WHERE sensor_id = :sensor_id
          AND recorded_at >= :start_date
          AND recorded_at <= :end_date
        ORDER BY recorded_at ASC
    ');
    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);
    $stmt->bindValue(':start_date', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':end_date', $endDate, SQLITE3_TEXT);

    $result = $stmt->execute();
    $readings = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $readings[] = [
            $row['recorded_at'],
            $row['sensor_id'],
            $row['temperature_f'],
            $row['humidity'] ?? ''
        ];
    }

    $db->close();
    return $readings;
}

/**
 * Get hourly temperature averages for a sensor within a date range
 *
 * @param string $sensorId The sensor identifier
 * @param string $startDate Start datetime
 * @param string $endDate End datetime
 * @return array Keyed by hour (0-23) => average temperature
 */
function getHourlyAverages($sensorId, $startDate, $endDate) {
    $db = getDb();

    $stmt = $db->prepare("
        SELECT CAST(strftime('%H', recorded_at) AS INTEGER) as hour,
               AVG(temperature_f) as avg_temp
        FROM readings
        WHERE sensor_id = :sensor_id
          AND recorded_at >= :start_date
          AND recorded_at <= :end_date
        GROUP BY hour
        ORDER BY hour ASC
    ");
    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);
    $stmt->bindValue(':start_date', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':end_date', $endDate, SQLITE3_TEXT);

    $result = $stmt->execute();
    $hourly = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hourly[$row['hour']] = $row['avg_temp'];
    }

    $db->close();
    return $hourly;
}
```

**Step 2: Verify syntax**

Run: `php -l html/database.php`
Expected: `No syntax errors detected`

**Step 3: Add more test data for hourly averages**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); saveReading('211', 70.0, 40, '2026-01-21 12:30:00'); saveReading('211', 68.0, 42, '2026-01-21 13:00:00'); saveReading('211', 75.0, 38, '2026-01-21 13:30:00'); echo 'Added test data' . PHP_EOL;"`
Expected: `Added test data`

**Step 4: Test getHourlyAverages()**

Run: `cd html && php -r "require_once 'get_data.php'; require_once 'database.php'; getConfig(); print_r(getHourlyAverages('211', '2026-01-21 00:00:00', '2026-01-21 23:59:59'));"`
Expected: Array with hour 12 => 71.25 (avg of 72.5 and 70.0), hour 13 => 71.5 (avg of 68 and 75)

**Step 5: Commit**

```bash
git add html/database.php
git commit -m "feat: add getReadings() and getHourlyAverages() for data queries"
```

---

## Task 5: Update parse_and_save.php to Use SQLite

**Files:**
- Modify: `html/parse_and_save.php`

**Step 1: Add require for database.php at the top**

After line 2 (`require_once 'get_data.php';`), add:

```php
require_once 'database.php';
```

**Step 2: Replace writeToDisk() function**

Replace the entire `writeToDisk()` function (lines 34-66) with:

```php
/**
 * Save the data to the database
 * @param array $dataArray
 * @param array $YANPIWS
 * @return bool
 */
function writeToDisk($dataArray, $YANPIWS){
    $time = isset($dataArray['time']) ? trim($dataArray['time']) : null;
    $id = isset($dataArray['id']) ? trim($dataArray['id']) : null;
    $temp = isset($dataArray['temperature_F']) ? trim($dataArray['temperature_F']) : null;
    $humidity = isset($dataArray['humidity']) ? trim($dataArray['humidity']) : null;

    if ($time !== null && $id !== null && $temp !== null) {
        $saveResult = saveReading($id, $temp, $humidity, $time);
        if ($saveResult) {
            error_log("parse_and_save wrote to database: sensor=$id temp=$temp");
        } else {
            error_log("parse_and_save FAILED to write to database: sensor=$id temp=$temp");
        }
        return $saveResult;
    } else {
        error_log("parse_and_save called but missing required data (time, id, or temperature_F)");
        return false;
    }
}
```

**Step 3: Remove cleanseData() and saveArrayToCsv() functions**

Delete lines 69-102 (the `cleanseData()` and `saveArrayToCsv()` functions). They are no longer needed.

**Step 4: Verify syntax**

Run: `php -l html/parse_and_save.php`
Expected: `No syntax errors detected`

**Step 5: Commit**

```bash
git add html/parse_and_save.php
git commit -m "refactor: update parse_and_save.php to use SQLite instead of CSV"
```

---

## Task 6: Update get_data.php to Use SQLite

**Files:**
- Modify: `html/get_data.php`

**Step 1: Add require for database.php after line 1**

At the top of the file, after `<?php`, add:

```php
require_once 'database.php';
```

**Step 2: Replace getMostRecentTemp() function (lines 284-309)**

Replace the entire function with:

```php
/**
 * assuming there's many temps for a day for a given sensor, get an array of the most current
 *
 * @param $id int of ID of the sensor
 * @return array of results - if no data found, array of "No Data Found" returned
 */
function getMostRecentTemp($id)
{
    return getLatestReading($id);
}
```

**Step 3: Replace getTodaysData() function (lines 183-187)**

Replace with:

```php
function getTodaysData(){
    global $YANPIWS;
    $startDate = date('Y-m-d') . ' 00:00:00';
    $endDate = date('Y-m-d') . ' 23:59:59';

    $result = [];
    if (isset($YANPIWS['labels'])) {
        foreach (array_keys($YANPIWS['labels']) as $sensorId) {
            $readings = getReadings($sensorId, $startDate, $endDate);
            foreach ($readings as $reading) {
                $result[$sensorId][$reading[0]] = $reading;
            }
        }
    }
    return $result;
}
```

**Step 4: Replace getYesterdaysData() function (lines 188-192)**

Replace with:

```php
function getYesterdaysData(){
    global $YANPIWS;
    $startDate = date('Y-m-d', strtotime('yesterday')) . ' 00:00:00';
    $endDate = date('Y-m-d', strtotime('yesterday')) . ' 23:59:59';

    $result = [];
    if (isset($YANPIWS['labels'])) {
        foreach (array_keys($YANPIWS['labels']) as $sensorId) {
            $readings = getReadings($sensorId, $startDate, $endDate);
            foreach ($readings as $reading) {
                $result[$sensorId][$reading[0]] = $reading;
            }
        }
    }
    return $result;
}
```

**Step 5: Remove getData() function (lines 210-229)**

Delete the entire `getData()` function - it's no longer needed.

**Step 6: Verify syntax**

Run: `php -l html/get_data.php`
Expected: `No syntax errors detected`

**Step 7: Commit**

```bash
git add html/get_data.php
git commit -m "refactor: update get_data.php to use SQLite functions"
```

---

## Task 7: Update temps.php for SQLite

**Files:**
- Modify: `html/temps.php`

**Step 1: Replace the data loading section (lines 25-27)**

Replace:
```php
$data1 = getTodaysData();
$data2 = getYesterdaysData();
$data = mergeDayData($data1 ,$data2, $YANPIWS['labels']);
```

With:
```php
// Get 24 hours of data using SQLite
$endDate = date('Y-m-d H:i:s');
$startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));

$data = [];
foreach ($YANPIWS['labels'] as $id => $label) {
    $readings = getReadings($id, $startDate, $endDate);
    foreach ($readings as $reading) {
        $data[$id][$reading[0]] = $reading;
    }
}
```

**Step 2: Verify syntax**

Run: `php -l html/temps.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add html/temps.php
git commit -m "refactor: update temps.php to use SQLite for 24-hour data"
```

---

## Task 8: Remove Unused CSV Functions from get_data.php

**Files:**
- Modify: `html/get_data.php`
- Modify: `html/temps.php`

**Step 1: Update temps.php to use getHourlyAverages()**

In temps.php, replace line 56:
```php
$hourlyTemps = convertDataToHourly($data[$id]);
```

With:
```php
$hourlyTemps = getHourlyAverages($id, $startDate, $endDate);
```

**Step 2: Remove mergeDayData() function from get_data.php**

Delete the `mergeDayData()` function (approximately lines 237-252).

**Step 3: Remove convertDataToHourly() function from get_data.php**

Delete the `convertDataToHourly()` function (approximately lines 253-275).

**Step 4: Verify syntax**

Run: `php -l html/get_data.php && php -l html/temps.php`
Expected: `No syntax errors detected` for both

**Step 5: Commit**

```bash
git add html/get_data.php html/temps.php
git commit -m "refactor: remove unused CSV functions, use getHourlyAverages()"
```

---

## Task 9: Update configIsValid() for SQLite

**Files:**
- Modify: `html/get_data.php`

**Step 1: Update the data validation in configIsValid()**

Find the section around lines 162-168 that checks for today's data:

```php
    if (!isset($YANPIWS['dataPath']) || !is_writable($YANPIWS['dataPath'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'DataPath does not exist or is not writable. ';
    } elseif (sizeof(getTodaysData()) === 0) {
        $valid['valid'] = false;
        $valid['reason'] .= 'Failed to get data for today. Check DataPath for valid data.';
    }
```

Replace with:

```php
    if (!isset($YANPIWS['dataPath']) || !is_writable($YANPIWS['dataPath'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'DataPath does not exist or is not writable. ';
    }
    // Note: Removed check for today's data - SQLite will work even with empty database
```

**Step 2: Verify syntax**

Run: `php -l html/get_data.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add html/get_data.php
git commit -m "refactor: update configIsValid() for SQLite (remove daily data check)"
```

---

## Task 10: End-to-End Test

**Step 1: Clear test database**

Run: `rm -f data/yanpiws.db`

**Step 2: Start development server**

Run: `cd html && php -S localhost:8000 router.php`
(Keep this running in the terminal)

**Step 3: Submit test reading (in another terminal)**

Run: `curl --data "password=YOUR_API_PASSWORD&temperature_F=72.5&id=211&time=$(date '+%Y-%m-%d %H:%M:%S')" http://localhost:8000/parse_and_save.php`

(Replace YOUR_API_PASSWORD with the actual password from config.csv)

**Step 4: Verify in database**

Run: `sqlite3 data/yanpiws.db "SELECT * FROM readings;"`
Expected: Shows the reading

**Step 5: Test web interface**

Open: `http://localhost:8000/` in browser
Expected: Should show the temperature (or "NA" if sensor ID doesn't match config labels)

**Step 6: Stop server**

Press Ctrl+C in the terminal running the server

**Step 7: Final commit**

```bash
git add -A
git commit -m "feat: complete SQLite migration - all CSV operations replaced"
```

---

## Summary

After completing all tasks:

1. **database.php** - New file with all SQLite operations
2. **parse_and_save.php** - Uses `saveReading()` instead of CSV
3. **get_data.php** - Uses SQLite query functions
4. **temps.php** - Uses `getReadings()` and `getHourlyAverages()`

CSV files in `data/` are preserved as archive but no longer written to.
