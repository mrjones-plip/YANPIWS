<?php
error_log('YANPIWS Start read_and_post');
require_once 'get_data.php';
getConfig('../../');

error_log('config loaded');
if(defined('STDIN')){
    while ($f = fgets(STDIN)) {
	    $dataArray = json_decode($f, true);
	    sendData($YANPIWS, $dataArray);
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
