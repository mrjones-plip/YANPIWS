<?php
/**
 * SQLite database functions for YANPIWS temperature storage
 *
 * Schema: readings table with columns:
 *   - id: INTEGER PRIMARY KEY
 *   - recorded_at: DATETIME in 'Y-m-d H:i:s' format
 *   - sensor_id: TEXT
 *   - temperature_f: REAL
 *   - humidity: REAL (nullable)
 */

/**
 * Get a SQLite database connection (singleton pattern)
 * Creates the database file if it doesn't exist
 *
 * @return SQLite3|null Returns null on error
 */
function getDb() {
    static $db = null;
    static $initialized = false;

    if ($db !== null) {
        return $db;
    }

    global $YANPIWS;

    // Ensure config is loaded
    if (!isset($YANPIWS['dataPath'])) {
        getConfig();
    }

    $dataPath = $YANPIWS['dataPath'] ?? '../data/';
    // Remove trailing slash if present, then add it back consistently
    $dataPath = rtrim($dataPath, '/') . '/';
    $dbPath = $dataPath . 'yanpiws.db';

    try {
        $db = new SQLite3($dbPath);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA busy_timeout=5000');

        // Initialize schema only once per request
        if (!$initialized) {
            initDb($db);
            $initialized = true;
        }
    } catch (Exception $e) {
        error_log("YANPIWS: Failed to open database at $dbPath: " . $e->getMessage());
        return null;
    }

    return $db;
}

/**
 * Initialize the database schema if tables don't exist
 *
 * @param SQLite3 $db
 */
function initDb($db) {
    $result = $db->exec('
        CREATE TABLE IF NOT EXISTS readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recorded_at DATETIME NOT NULL,
            sensor_id TEXT NOT NULL,
            temperature_f REAL NOT NULL,
            humidity REAL
        )
    ');

    if ($result === false) {
        error_log("YANPIWS: Failed to create readings table: " . $db->lastErrorMsg());
    }

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
    if ($db === null) {
        return false;
    }

    $stmt = $db->prepare('
        INSERT INTO readings (recorded_at, sensor_id, temperature_f, humidity)
        VALUES (:recorded_at, :sensor_id, :temperature_f, :humidity)
    ');

    if ($stmt === false) {
        error_log("YANPIWS: Failed to prepare saveReading statement: " . $db->lastErrorMsg());
        return false;
    }

    $stmt->bindValue(':recorded_at', $timestamp, SQLITE3_TEXT);
    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);
    $stmt->bindValue(':temperature_f', $temperatureF, SQLITE3_FLOAT);

    if ($humidity !== null && $humidity !== '') {
        $stmt->bindValue(':humidity', (float)$humidity, SQLITE3_FLOAT);
    } else {
        $stmt->bindValue(':humidity', null, SQLITE3_NULL);
    }

    $result = $stmt->execute();

    return $result !== false;
}

/**
 * Get the most recent temperature reading for a sensor
 *
 * @param string $sensorId The sensor identifier
 * @return array Reading data with keys: date, id, temp, label, humidity
 *               Returns 'NA' values when no data found (for display compatibility)
 */
function getLatestReading($sensorId) {
    global $YANPIWS;

    $db = getDb();
    if ($db === null) {
        return [
            'date' => 'NA',
            'id' => 'No Data Found',
            'temp' => 'NA',
            'label' => 'NA',
            'humidity' => 'NA'
        ];
    }

    $stmt = $db->prepare('
        SELECT recorded_at, sensor_id, temperature_f, humidity
        FROM readings
        WHERE sensor_id = :sensor_id
        ORDER BY recorded_at DESC
        LIMIT 1
    ');

    if ($stmt === false) {
        error_log("YANPIWS: Failed to prepare getLatestReading statement: " . $db->lastErrorMsg());
        return [
            'date' => 'NA',
            'id' => 'No Data Found',
            'temp' => 'NA',
            'label' => 'NA',
            'humidity' => 'NA'
        ];
    }

    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

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
            'humidity' => $row['humidity']  // null if not set
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
 * @return array Array of readings, each as [recorded_at, sensor_id, temperature_f, humidity]
 */
function getReadings($sensorId, $startDate, $endDate) {
    $db = getDb();
    if ($db === null) {
        return [];
    }

    $stmt = $db->prepare('
        SELECT recorded_at, sensor_id, temperature_f, humidity
        FROM readings
        WHERE sensor_id = :sensor_id
          AND recorded_at >= :start_date
          AND recorded_at <= :end_date
        ORDER BY recorded_at ASC
    ');

    if ($stmt === false) {
        error_log("YANPIWS: Failed to prepare getReadings statement: " . $db->lastErrorMsg());
        return [];
    }

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
            $row['humidity']  // null if not set
        ];
    }

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
    if ($db === null) {
        return [];
    }

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

    if ($stmt === false) {
        error_log("YANPIWS: Failed to prepare getHourlyAverages statement: " . $db->lastErrorMsg());
        return [];
    }

    $stmt->bindValue(':sensor_id', $sensorId, SQLITE3_TEXT);
    $stmt->bindValue(':start_date', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':end_date', $endDate, SQLITE3_TEXT);

    $result = $stmt->execute();
    $hourly = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hourly[$row['hour']] = $row['avg_temp'];
    }

    return $hourly;
}
