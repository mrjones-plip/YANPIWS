<?php
require_once 'get_data.php';
getConfig('../../');

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
 * Save the data to the disk
 * @param array dataArray
 * @param array YANPIWS
 */
function writeToDisk($dataArray,$YANPIWS){
    $knownKeys = array(
        'time',
        'id',
        'temperature_F',
        'humidity',
    );
    $saveMeArray = array();

    foreach ($knownKeys as $key) {
        if(isset($dataArray[$key])){
            $saveMeArray[] = cleanseData($dataArray[$key]);
        } else {
            $saveMeArray[] = null;
        }
    }

    if($saveMeArray[0] !== null &&
        $saveMeArray[1] !== null &&
        $saveMeArray[2] !== null
    ) {
        $today = date('Y-m-d', time());
        $saveResult = saveArrayToCsv($YANPIWS['dataPath'], $today, $saveMeArray);
        if ($saveResult){
            error_log("parse_and_save wrote to {$YANPIWS['dataPath']} this many items:" . sizeof($saveMeArray));
        }  else {
            error_log("parse_and_save FAILED  to write to  {$YANPIWS['dataPath']} this many items:" . print_r($saveMeArray,1));
        }
    } else {
        error_log("parse_and_save called but no data in 'saveMeArray' array");
    }
    return true;
}


/**
 * we likely shouldn't trust data sent over unencrypted
 * un-authenticated channels.  Let's make sure it's on the
 * up and up. uses regex to replace all but "^0-9\. :-"
 *
 * @param  string $data input to cleanse
 * @return string
 */
function cleanseData($data)
{
    $data = trim($data);
    return preg_replace("/[^0-9\. :-]+/", "", $data);
}

/**
 * @param string $path
 * @param array  $array of k/v pairs to save
 */
function saveArrayToCsv($path, $file, $array)
{
    if (is_dir($path) && is_writable($path) && is_array($array) && sizeof($array) > 0) {
        $time = date('g:i A');
        $filePutResult = file_put_contents($path .'/'. $file, implode(',' , $array) . "\n",FILE_APPEND);
        if ($filePutResult){
            error_log("{$array[2]} at  {$array[1]} - {$time} - wrote to {$path}/{$file}");
        }
        return $filePutResult;
    } else {
        error_log( "failed to write to {$path}/{$file} " .
            "It's not a dir, not writable or invalid/empty array passed. Array is: ".
            print_r($array, 1));
        return false;
    }
}

