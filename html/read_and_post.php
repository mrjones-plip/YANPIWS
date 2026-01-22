<?php
error_log('YANPIWS Start read_and_post');
require_once 'get_data.php';
require_once 'database.php';
getConfig();

error_log('config loaded');
if(defined('STDIN')){
    while ($f = fgets(STDIN)) {
	    $dataArray = json_decode($f, true);
	    // Save locally to SQLite
	    saveDataLocally($dataArray);
	    // Forward to remote servers if configured
	    sendData($YANPIWS, $dataArray);
    }
}

/**
 * Save sensor data locally to SQLite database
 * @param array $dataArray with keys: time, id, temperature_F, humidity (optional)
 */
function saveDataLocally($dataArray) {
    $time = isset($dataArray['time']) ? trim($dataArray['time']) : date('Y-m-d H:i:s');
    $id = isset($dataArray['id']) ? trim($dataArray['id']) : null;
    $temp = isset($dataArray['temperature_F']) ? trim($dataArray['temperature_F']) : null;
    $humidity = isset($dataArray['humidity']) ? trim($dataArray['humidity']) : null;

    if ($id !== null && $temp !== null) {
        $result = saveReading($id, $temp, $humidity, $time);
        if ($result) {
            error_log("read_and_post saved locally: sensor=$id temp=$temp");
        } else {
            error_log("read_and_post FAILED to save locally: sensor=$id temp=$temp");
        }
    } else {
        error_log("read_and_post: missing required data (id or temperature_F)");
    }
}

function sendData($YANPIWS, $dataArray){
	if(isset($YANPIWS['servers']) && is_array($YANPIWS['servers'])){
	    foreach ($YANPIWS['servers'] as $server){
	        if (isset($server['url']) && isset($server['password'])) {
	            $url = $server['url'] . '/parse_and_save.php';
	            $dataArray['password'] = $server['password'];
	
	            $ch = curl_init();
	            curl_setopt($ch, CURLOPT_URL,$url);
	            curl_setopt($ch, CURLOPT_POST, 1);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dataArray));
	            curl_setopt($ch, CURLOPT_HEADER, true);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	            // for debug or logging
	            $server_output = curl_exec($ch);
	            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	            $bytes = curl_getinfo($ch,   CURLINFO_REQUEST_SIZE);

	            curl_close ($ch);
	            error_log("YANPIWS made curl call to $url, sent $bytes bytes, got response code $httpcode ");
		}
	    }
	}
}
