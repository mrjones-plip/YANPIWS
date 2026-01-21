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
