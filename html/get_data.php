<?php
require_once 'database.php';

/**
 * get all the valid config options required to bootstrap YANPIWS
 *
 * @return array of key names
 */
function getValidConfigs(){
    return array(
        'lat',
        'lon',
        'forecast_api_token',
        'forecast_api_url',
        'labels',
        'animate',
        'dataPath',
        'api_password',
        'temp_count',
        'font_time_date_wind',
        'font_temp',
        'font_temp_label',
        'theme',
        'moondata_api_URL',
        'timezone',
        // we accept these two. listing it here commented out for completeness. see getConfig() below
        // servers_*
        // labels_*
    );
}

/**
 * Generate status HTML with error if config is not valid
 *
 * @param $status array from configIsValid
 * @return string of html to show upon error, returns empty if no error
 */
function getStatusHTML(array $status): string
{
    if ($status['valid'] === false && isset($status['reason'])) {
        $statusHtml = "<div class='error'>ERROR: {$status['reason']}</div>";
        $statusHtml .= "<style>.temp,.suntimes{display:none;}</style>";
        return $statusHtml;
    } else {
        return '';
    }
}

/**
 * include the config file
 * @param boolean $die prints error and exits if fails
 */
function getConfig($baseDir = '../', $die = true)
{
    $fullPath = $baseDir . 'config.csv';
    if(is_file($fullPath)) {
        global $YANPIWS ;
        $options = getValidConfigs();
        $YANPIWStmp = array_map('str_getcsv', file($fullPath));
        foreach ($YANPIWStmp as $config){
            if(!isset($config[0])){
                continue;
            }

            if (substr($config[0],0,6) === 'labels'){
                $label = explode('_',$config[0]);
                $YANPIWS['labels'][$label[1]] = $config[1];
            } elseif(substr($config[0],0,7) === 'servers'){
                $serversAry = explode('_',$config[0]);
                $YANPIWS['servers'][$serversAry[1]][$serversAry[2]] = $config[1];
            } elseif (in_array($config[0],$options)) {
                $YANPIWS[$config[0]] = $config[1];
            }

        }
        $YANPIWS['release'] = '0.13.1';
        $YANPIWS['cache_bust'] = $YANPIWS['release'];
        date_default_timezone_set($YANPIWS['timezone']);
    } elseif ($die) {
        die(
            '<h3>Error</h3><p>No config.csv!  Copy config.dist.csv to config.csv</p>'.
            getDailyForecastHtml()
        );
    }
}

/**
 * based on the values retrieved in getConfigOrDie(), validate all the config options
 *
 * @return array returns an array with key of valid as boolean and reason a string of why it's not valid
 */
function configIsValid($validateApi = false)
{
    global $YANPIWS;
    $valid = array('valid' => true, 'reason' => '');
    $options = getValidConfigs();
    if (sizeof($YANPIWS) < sizeof($options)){
        $valid['valid'] = false;
        $valid['reason'] .= 'Missing required option. ';
    }
    if (!isset($YANPIWS['forecast_api_url']) || strlen($YANPIWS['forecast_api_url']) < 10){
        $valid['valid'] = false;
        $valid['reason'] .= 'Forecast API URL is wrong length or missing.  (<pre>forecast_api_url</pre>)';
    }
    if (!isset($YANPIWS['forecast_api_token']) || strlen($YANPIWS['forecast_api_token']) < 16 || strlen($YANPIWS['forecast_api_token']) > 41){
        $valid['valid'] = false;
        $valid['reason'] .= 'Forecast API Key is wrong length or missing. (<pre>forecast_api_token</pre>)';
    }
    if(!isset($YANPIWS['lat'])) {
        $valid['valid'] = false;
        $valid['reason'] .= 'Latitude is missing. ';
    } elseif (!validateLatitude($YANPIWS['lat'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Latitude is invalid. ';
    }
    if (!isset($YANPIWS['lon'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Longitude is missing. ';
    } elseif (!validateLongitude($YANPIWS['lon'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Longitude is invalid. ';
    }
    if (!isset($YANPIWS['temp_count'])){
        error_log('temp_count is unset! Defaulting to "1"');
        $YANPIWS['temp_count'] = 1;
    }

    // for these font sizes ones, lets default to a sane size
    // and then write and error to the error log. Will make
    // a much safer upgrade path for Manny ;)
    if (!isset($YANPIWS['theme']) || !in_array($YANPIWS['theme'], array('dark','light'))){
        error_log('theme is unset or not recognized! Defaulting to "dark"');
        $YANPIWS['theme'] = 'dark';
    }
    if (isset($_GET['toggle_theme'])){
        if($YANPIWS['theme'] == 'dark') {
            $YANPIWS['theme'] = 'light';
        } else {
            $YANPIWS['theme'] = 'dark';
        }
    }
    if (!isset($YANPIWS['font_time_date_wind'])){
        error_log('font_time_date_wind is unset! Defaulting to "35"');
        $YANPIWS['font_time_date_wind'] = 35;
    } elseif (!validateFontSize($YANPIWS['font_time_date_wind'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for time/date/wind is invalid. ';
    }
    if (!isset($YANPIWS['font_temp'])){
        error_log('font_temp is unset! Defaulting to "50"');
        $YANPIWS['font_temp'] = 50;
    } elseif (!validateFontSize($YANPIWS['font_temp'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for temp is invalid. ';
    }
    if (!isset($YANPIWS['font_temp_label'])){
        error_log('font_temp_label is unset! Defaulting to "25"');
        $YANPIWS['font_temp_label'] = 25;
    } elseif (!validateFontSize($YANPIWS['font_temp_label'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for temp label is invalid. ';
    }

    if (!isset($YANPIWS['dataPath']) || !is_writable($YANPIWS['dataPath'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'DataPath does not exist or is not writable. ';
    }
    // Note: Removed check for today's data - SQLite will work even with empty database
    if ($validateApi){
        $http = curl_init(getForecastUrl(true));
        curl_setopt($http, CURLOPT_NOBODY  , true);
        curl_exec($http);
        $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
        if ($http_status != 200){
            $valid['valid'] = false;
            $valid['reason'] .= 'Forecast API call failed: Either invalid API key or invalid Lat/Long ' .
                "(status: $http_status). ";
        }
    }
    return $valid;
}

/**
 * Get sensor readings for a specific date
 *
 * @param string|null $date Date in Y-m-d format, defaults to today
 * @return array Multi-dimensional array keyed by sensor_id => timestamp => reading
 */
function getDataForDate($date = null){
    global $YANPIWS;

    if ($date === null) {
        $date = date('Y-m-d');
    }

    $startDate = $date . ' 00:00:00';
    $endDate = $date . ' 23:59:59';

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

function getTodaysData(){
    return getDataForDate(date('Y-m-d'));
}

function getYesterdaysData(){
    return getDataForDate(date('Y-m-d', strtotime('yesterday')));
}

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

/**
 * given an array from getMostRecentTemp(), format it into html
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getTempHtml($tempLine)
{
    if (isset($tempLine['temp']) && is_numeric($tempLine['temp'])) {
        $temp = number_format($tempLine['temp'], 0);
        return "<span class='degrees'>{$temp}°</span>" .
            "<span class='label'>{$tempLine['label']}</span>\n";
    } else {
        return "NA";
    }
}
/**
 * given an array from getMostRecentTemp(), format it into html
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getHumidityHtml($tempLine, $useLabel = false)
{
    global $YANPIWS;
    $key = $tempLine['id'];
    if (isset($YANPIWS['labels'][$key])) {
        $label = $YANPIWS['labels'][$key];
    } else {
        $label = "#$key";
    }
    if (isset($tempLine['humidity']) && $tempLine != null) {
        $humidity = number_format(trim($tempLine['humidity']), 0);
        $result = "<span class='percent'>{$humidity}%</span>";
        if($useLabel) {
            $result .= "<span class='label'>$label</span>\n";
        }
        return $result;
    } else {
        return "NA";
    }
}

/**
 * Get the age in human time (sec, min, hour etc) of the Forecast cache
 * @param $returnSeconds boolean to return int of seconds if true, otherwise string of human time
 */
function getCacheAge(bool $returnSeconds = false, string $type = 'weather'): int|string
{
    global $YANPIWS;
    if ($type === 'weather') {
        $file = 'forecast.cache';
    } else {
        $file = 'moondata.cache';
    }
    $forecast_cache_time =  filemtime($YANPIWS['dataPath'] . 'forecast.cache');
    if (!$returnSeconds) {
        return getHumanTime(time() - $forecast_cache_time);
    } else {
        return (time() - $forecast_cache_time);
    }
}

/**
 * given an array from getMostRecentTemp(), format it into debug html showing age of temp
 *
 * @param $tempLine array from getMostRecentTemp()
 * @param $returnOnlySeconds boolean to return int of seconds if true, otherwise string of human time
 * @return string of HTML
 */
function getTempLastHtml($tempLine, $returnOnlySeconds = false)
{
    global $YANPIWS;
    if ($tempLine['date'] == "NA") {
        $age = '';
        $label = '';
        return "<li>$label: $age ". implode(" - ", $tempLine) . "</li>";
    } else {
        $lineEpoch = strtotime($tempLine['date']);
        $age = getHumanTime(time() - $lineEpoch);
        $temp = "{$tempLine['temp']}°";
        $id = $tempLine['id'];
        $label = $tempLine['label'];
        if (!$returnOnlySeconds) {
            return "<li>$label: $temp $age ago</li>";
        } else {
            return (time() - $lineEpoch);
        }
    }
}

/**
 * given an epoch timestampe, return html with icon
 *
 * @param $time int of epoch
 * @return string of html
 */
function getSunsetHtml($time)
{
    $time = date('g:i A', $time);
    return '<img src="images/moon.svg" class="moon" /> ' . $time;
}

/**
 * given an epoch timestampe, return html with icon
 *
 * @param $time int of epoch
 * @return string of html
 */
function getSunriseHtml($time)
{
    $time = date('g:i A', $time);
    return '<img src="images/sun.svg" class="sun" /> ' . $time;
}

/**
 * get data from Forecast API.  will cache data and refresh it every 10 minutes
 * @param $type string of either `moon` or `weather`
 * @return stdClass of either results or very lightly populated error object
 */
function fetchRemoteApiDataAndSave($type){
    global $YANPIWS;
    $noDataFound = new stdClass();
    $noDataFound->daily = null;
    $noDataFound->currently = null;

    if ($type === 'moon') {
        $cache = $YANPIWS['dataPath'] . 'moondata.cache';
        $url = getMoondataUrl();
        $timeAgo = time() - (60*60); // 60 minutes
    } elseif ($type === 'weather') {
        $cache = $YANPIWS['dataPath'] . 'forecast.cache';
        $timeAgo = time() - (60*60); // 60 minutes, ~144 API calls/month
        $url = getForecastUrl();
    } else {
        return $noDataFound;
    }

    $data = false;
    $configStatus = configIsValid();
    if($configStatus['valid'] === true) {
        if ((!is_file($cache) || filectime($cache) < $timeAgo)) {
            $http = curl_init($url);
            curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
            $dataFromRemote = curl_exec($http);
            $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
            if ($http_status == 200){
                file_put_contents($cache, serialize(json_decode($dataFromRemote)));
            } else {
                error_log('[ERROR] Tried to get info from ' . $url . ', did not get 200 HTTP Code, instead got: ' . $http_status);
            }
        }

        // always fetch from file if present
        if (is_file($cache)) {
            $data = unserialize(file_get_contents($cache));
        }
    }
    if ($data === false || $data === null) {
        $data = $noDataFound;
    }
    return $data;
}

/**
 * @param $useTestLatLong boolean to use test data or not
 * @return array
 */
function getLatLon($useTestLatLong){
    global $YANPIWS;
    if ($useTestLatLong){
        $lat = "31.775554";
        $lon = "81.822436";
    } else {
        $lat = $YANPIWS['lat'];
        $lon = $YANPIWS['lon'];
    }
    return array('lat' => $lat, 'lon' => $lon);
}

/**
 * Simple wrapper to concat the string for the Forecast API endpoint
 *
 * @return string of URL
 */
function getForecastUrl($useTestLatLong = false){
    global $YANPIWS;
    extract(getLatLon($useTestLatLong));
    return $YANPIWS['forecast_api_url'] . '/forecast/' . $YANPIWS['forecast_api_token'] . '/' . $lat . ',' . $lon;
}

/**
 * Simple wrapper to concat the string for the Forecast API endpoint
 *
 * @return string of URL
 */
function getMoondataUrl($useTestLatLong = false){
    global $YANPIWS;
    $date = date('Y-m-d', time());
    extract(getLatLon($useTestLatLong));
    $hoursOffset = ((float) date('Z')/60/60);
    return $YANPIWS['moondata_api_URL'] . '?tz=' . $hoursOffset . '&date=' . $date . '&coords=' . $lat . ',' . $lon;
}

/**
 * /**
 * expects the $data->daily object from fetchRemoteApiDataAndSave('weather'), returns $days (default 5) of forecast HTML
 *
 * @param null $daily $data->daily object from fetchRemoteApiDataAndSave('weather')
 * @param int $days how many days of forecast to return
 * @param string $animate show animation or not: 'true' or 'false' literal string
 * @return string of HTML
 */
function getDailyForecastHtml($daily = null, $days = 5, $animate = null)
{
    global $YANPIWS;
    $html = '';
    if ($animate === null) {
        $animate = $YANPIWS['animate'];
    }
    if ($daily == null) {
        // show rain for error
        $html .= "<img src='skycons/rain.png' class='errorImg'  /> ";
        $html .= "No Data for forecast.";
    } else {
        $count = 1;
        foreach ($daily->data as $day) {
            if($count > $days) {
                break;
            }

            if ($count == 1) {
                $today = "Today";
            } else {
                $today = date('D',strtotime("+".($count-1)." day"));
            }
            $html .= "<div class='forecastday'>";
            $html .= "<div class='forcastDay'>$today</div>";
            if ($animate === 'true') {
                $html .= "<canvas id='$today.$day->icon' class='forecasticon' width='70' height='70'></canvas>";
            } else {
                $html .= "<img src='./skycons/{$day->icon}.png' width='70' height='70' />";
            }
            $html .= '<div class="hight spreadtemp">' . number_format($day->temperatureMax, 0) . '°</div>';
            $html .= '<div class="lowt spreadtemp">' . number_format($day->temperatureMin, 0) . '°</div>';
            $html .= '<div class="wind"> ' . number_format($day->windSpeed, 0) .  ' mph</div>';
            $html .= '</div>'. "\n";
            $count++;
        }
    }
    return $html;
}

/**
 * expects the $data->daily object from fetchRemoteApiDataAndSave('weather'), returns $days (default 5) of forecast HTML
 *
 * @param null $daily $data->daily object from fetchRemoteApiDataAndSave('weather')
 * @param int $days how many days of forecast to return
 * @return string of HTML
 */
function getDailyForecast($daily = null, $days = 5)
{
    $result = array();
    if ($daily == null) {
        $result['result'] = "No Data for forecast.";
    } else {

        $count = 1;
        foreach ($daily->data as $day) {
            if($count > $days) {
                break;
            }
            $dayAry = array();

            // figure which day it is
            if ($count == 1) {
                $today = "Today";
            } else {
                $today = substr(date('D', $day->time), 0, 3);
            }

            // assemble result array
            $dayAry['day'] = $today;
            $dayAry['High'] =  number_format($day->temperatureMax, 0);
            $dayAry['Low'] =  number_format($day->temperatureMin, 0);
            $dayAry['Wind'] = number_format($day->windSpeed, 0) .  ' mph';
            $dayAry['Icon'] = $day->icon;

            $result[] = $dayAry;
            $count++;
        }

    }

    return json_encode($result);
}


/**
 * given an int of seconds, return sec, min, hours or days, rounded
 * thanks http://www.kavoir.com/2010/09/php-get-human-readable-time-from-seconds.html
 *
 * @param $s int of seconds
 * @return string of human time
 */
function getHumanTime($s)
{
    $m = $s / 60;
    $h = $s / 3600;
    $d = $s / 86400;
    if ($m > 1) {
        if ($h > 1) {
            if ($d > 1) {
                return (int)$d.' days';
            } else {
                return (int)$h.' hours';
            }
        } else {
            return (int)$m.' minutes';
        }
    } else {
        return (int)$s.' seconds';
    }
}


/**
 * expects the $data->currently object from fetchRemoteApiDataAndSave('weather'), returns windspeed HTML
 *
 * @param null $daily $data->currently object from fetchRemoteApiDataAndSave('weather')
 * @return string of HTML
 */
function getCurrentWind($currentlyObject)
{
    return number_format($currentlyObject->windSpeed, 0) . " mph";
}

/**
 * @param $key
 * @return int|string
 */
function getConfigValue($key){
    global $YANPIWS;
    if (in_array($key,getValidConfigs())){
        return print htmlspecialchars($YANPIWS[$key], ENT_QUOTES, 'UTF-8');
    } else {
        return 'Invalid Config Requested';
    }
}

/**
 * @param $content
 * @param $tempID
 * @return mixed
 */
function get_json_inline($content, $tempID = null){
    $result = fetch_json($content, 'false', $tempID);
    if ( $content === null || $result === null || json_decode($result) === null || json_decode($result) === false ) {
        return null;
    }
    $fetchResults = json_decode($result);
    return $fetchResults->$content;
}

function get_formatted_time($raw){
    global $YANPIWS;

    $dt = DateTimeImmutable::createFromFormat(
        'G:i',
        $raw,
        new DateTimeZone($YANPIWS['timezone'])
    );
    return $dt->format('g:i A'); # cooked ;)
}
function get_formatted_date($raw){
    global $YANPIWS;

    $dt = DateTimeImmutable::createFromFormat(
        'j n X',
        $raw,
        new DateTimeZone($YANPIWS['timezone'])
    );
    return $dt->format('F jS'); # cooked ;)
}
/**
 * fetch JSON contentfor use on the main DOM to render content
 *
 * @param $content which piece of content you want
 * @param $YANPIWS global from getConfig()
 * @return false|string|void
 */
function fetch_json($content, $animate = null, $tempID = null){
    global $YANPIWS;
    $time = date('g:i A', time());
    $forecast = fetchRemoteApiDataAndSave('weather');
    $moondata = fetchRemoteApiDataAndSave('moon');
    if(isset($_GET['id'])){
        $tempID = $_GET['id'];
    }

    switch ($content){
        case "forecast":
            if (isset($forecast->daily)) {
                return json_encode(array('forecast' => getDailyForecastHtml($forecast->daily,5 , $animate)));
            }
            break;
        case "forecast_full_json":
            if (isset($forecast->daily)) {
                return json_encode($forecast->daily->data);
            }
            break;

        case "wind_now":;
            if (isset($forecast->currently)) {
                return json_encode(array('wind_now' => getCurrentWind($forecast->currently)));
            }
            break;

        case "sunset":
            if (isset($forecast->daily->data[0]->sunsetTime)){
                $time = date('g:i A', $forecast->daily->data[0]->sunsetTime);
                return json_encode(array('sunset' => $time));
            }
            break;

        case "sunrise":
            if (isset($forecast->daily->data[0]->sunriseTime)){
                $time = date('g:i A', $forecast->daily->data[0]->sunriseTime);
                return json_encode(array('sunrise' => $time));
            }
            break;

        case "moonrise":
        case "moonset":
            if (isset($moondata->properties->data->moondata)){
                if($content === 'moonset'){
                    $search = 'Set';
                } else {
                    $search = 'Rise';
                }
                foreach ($moondata->properties->data->moondata as $moonItem){
                    if ($moonItem->phen && $moonItem->phen === $search){
                        $time = get_formatted_time($moonItem->time);
                        $content = (string) $content;
                        return json_encode(array($content => $time));
                    }
                }

            }
            break;

        case "moonall":
            if (isset($moondata->properties->data->moondata)){
                $data = $moondata->properties->data;
                foreach ($data->moondata as $moonItem){
                    if ($moonItem->time){
                        $moonItem->time = get_formatted_time($moonItem->time);
                    }
                }

                if (isset($data->closestphase->time)){
                    $data->closestphase->time = get_formatted_time(
                        $data->closestphase->time
                    );
                    $data->closestphase->description =
                        $data->closestphase->phase  . " " .
                        get_formatted_date(
                            $data->closestphase->day  . " " .
                            $data->closestphase->month . " " .
                            $data->closestphase->year
                        );
                } else {
                    $data->closestphase->description = "";
                }
                unset($data->sundata);
                return json_encode(array('moonall' => $data));
            }
            break;

        case "moonphase":
            if (isset($forecast->daily->data[0]->moonPhase)){
                $moonPhase = 360 - floor($forecast->daily->data[0]->moonPhase * 360);
                return json_encode(array('moonphase' => $moonPhase));
            }
            break;

        case "age":
            $maxTempAge = 0;
            foreach ($YANPIWS['labels'] as $id => $label){
                $tempLine = getMostRecentTemp($id);
                $currentTempAge = getTempLastHtml($tempLine, true);
                if ($maxTempAge < $currentTempAge){
                    $maxTempAge = $currentTempAge;
                }
            }
            if ($currentTempAge > 600 || $maxTempAge > 600){
                // todo - refactor calls to not expect cooked HTML in respone, just raw JSON
                $result['age'] = '<span style="color: yellow">YANPIWS</span>';
            } else {
                $result['age'] = 'YANPIWS';
            }
            return json_encode($result);

        case "date":
            return json_encode(array('date' => date('D M j', time())));

        case "time":
            return json_encode(array('time' => $time));

        case "datetime":
            return json_encode(array('date' => date('D M j', time()), 'time' => $time));

        case "temp":
            if (isset($YANPIWS['labels'][$tempID])){
                $tempLine = getMostRecentTemp($tempID);
                if(isset($_GET['cooked'])){
                    return getTempHtml($tempLine);
                } elseif (isset($_GET['raw'])) {
                    return json_encode($tempLine);
                } else {
                    // todo - refactor calls to not expect cooked HTML in respone, just raw JSON
                    // per 'raw' above
                    return json_encode(array('temp' => getTempHtml($tempLine)));
                }
            }
            break;

        case "humidity":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                return json_encode(array($tempLine));
            }
            break;

        case "last_ajax":
            // update this ajax file per #61 https://github.com/Ths2-9Y-LqJt6/YANPIWS/issues/61
            touch($YANPIWS['dataPath'] . '/' . 'last_ajax');
            return json_encode(array('last_ajax' => time()));
    }

}

/**
 * Thanks to https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1 for these
 * next three lat long funcions
 */
/**
 * Validates a given latitude $lat
 *
 * @param float|int|string $lat Latitude
 * @return bool `true` if $lat is valid, `false` if not
 */
function validateLatitude($lat) {
    return preg_match('/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $lat);
}
/**
 * Validates a given longitude $long
 *
 * @param float|int|string $long Longitude
 * @return bool `true` if $long is valid, `false` if not
 */
function validateLongitude($long) {
    return preg_match('/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/', $long);
}
/**
 * Validates a given fontsize $font_time_date_wind
 *
 * @param int fontSize Font Size (pt)
 * @return bool `true` if $fontSize is valid, `false` if not
 */
function validateFontSize($fontSize) {
    // thanks https://www.php.net/manual/en/function.is-int.php#82857
    return(ctype_digit(strval($fontSize)));
}
/**
 * Validates a given coordinate
 *
 * @param float|int|string $lat Latitude
 * @param float|int|string $long Longitude
 * @return bool `true` if the coordinate is valid, `false` if not
 */
function validateLatLong($lat, $long) {
    return preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $lat.','.$long);
}
