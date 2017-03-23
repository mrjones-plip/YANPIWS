#!/usr/bin/php
<?php

$knownKeys = array(
    'time',
    'id',
    'temperature_F',
    'humidity',
);
$path = "./data";

while($f = fgets(STDIN)){
    $dataObject = json_decode($f);
    $saveMeArray = array();

    foreach ($knownKeys as $key) {
        $saveMeArray[] = cleanseData($dataObject->$key);
    }

    $today = date('Y-m-d', time());
    saveArrayToCsv($path, $today, $saveMeArray);
}

/**
 * we likely shouldn't trust data sent over unencrypted
 * un-authenticated channels.  Let's make sure it's on the
 * up and up. uses regex to replace all but "^0-9\. :-"
 * @param string $data input to cleanse
 * @return string
 */
function cleanseData($data)
{
    $data = trim($data);
    return preg_replace("/[^0-9\. :-]+/", "", $data);
}

/**
 * @param string $path
 * @param array $array of k/v pairs to save
 */
function saveArrayToCsv($path, $file, $array)
{
    if (is_dir($path) && is_array($array) && sizeof($array) > 0) {
        echo 'It\'s ' . $array[2] . ' at ID ' . $array[1] . " - data written to " . $path . '/' . $file ."\n";
        file_put_contents($path . '/' . $file, implode(',' , $array) . "\n",FILE_APPEND);
    } else {
        echo "failed to write to {$path}/{$file} - not a dir, not writable or invalid/empty array passed :(";
    }
}

