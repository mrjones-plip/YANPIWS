<?php
require_once 'get_data.php';
require_once 'database.php';
getConfig();

if (is_array($_POST) && sizeof($_POST) > 0) {
    if (isset($_POST['password']) && $_POST['password'] == $YANPIWS['api_password']){
        $dataArray = $_POST;
        if (!isset($dataArray['time'])){
            $dataArray['time'] =  date('Y-m-d G:i:s', time());
        }
        writeToDisk($dataArray, $YANPIWS);
    } else {
        if (!isset($_POST['password'])) {
            $_POST['password'] = NULL;
        }
        error_log("Bad password sent to parse_and_save. got '"
            . $_POST['password'] . "' expected '" . $YANPIWS['api_password'] ."'");
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized', true, 401);
    }

} elseif(defined('STDIN')){
    while ($f = fgets(STDIN)) {
        $dataArray = json_decode($f, true);
        writeToDisk($dataArray, $YANPIWS);
    }
}


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

