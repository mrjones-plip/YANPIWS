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
