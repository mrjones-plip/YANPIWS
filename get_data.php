<?php
/**
 * get all the valid config options required to bootstrap YANPIWS
 *
 * @return array of key names
 */
function getValidConfigs(){
    return array(
        'lat',
        'lon',
        'darksky',
        'labels',
        'animate',
        'dataPath',
    );
}
/**
 * include the config file or die trying. prints error and exits if fails
 */
function getConfigOrDie()
{
    if(is_file('config.csv')) {
        global $YANPIWS ;
        $options = getValidConfigs();
        $YANPIWStmp = array_map('str_getcsv', file('config.csv'));
        foreach ($YANPIWStmp as $config){
            if (substr($config[0],0,6) == 'labels'){
                $label = explode('_',$config[0]);
                $YANPIWS['labels'][$label[1]] = $config[1];
            } elseif (in_array($config[0],$options)) {
                $YANPIWS[$config[0]] = $config[1];
            }
        }
    } else {
        die(
            '<h3>Error</h3><p>No config.csv!  Copy config.dist.csv to config.csv</p>'.
            getDailyForecastHtml()
        );
    }
}

/**
 * Assuming a CSV of this structure:
 *  2017-03-22 23:11:43,211,72.5,34
 * Return a multi-dimensional array like this:
 *
 *Array(
 *    [211] => Array(
 *        [2017-03-22 23:08:31] => Array(
 *                    [0] => 2017-03-22 23:08:31
 *                    [1] => 211
 *                    [2] => 72.5
 *                    [3] => 34
 *               )
 *
 * @param  string $file where CSV data is
 * @return array of formatted CSV data
 */
function getData($file)
{
    if (is_file($file) && is_readable($file)) {
        $data = file($file);
        $goodData = array();
        foreach ($data as $line){
            $lineArray = explode(",", $line);
            $goodData[$lineArray[1]][$lineArray[0]] = $lineArray;
        }
        foreach (array_keys($goodData) as $id){
            asort($goodData[$id]);
        }

        return $goodData;
    } else {
        return array();
    }
}

/**
 * assuming theere's many temps for a day for a given sensor, get an array of the most current
 *
 * @param $id int of ID of the sensor
 * @param null $date string in YEAR-MO-DAY format, defaults to today if none passed
 * @return array of results - if no data found, array of "No Data Found" returned
 */
function getMostRecentTemp($id, $date = null)
{
    global $YANPIWS;
    if ($date == null) {
        $date = date('Y-m-d', time());
    }
    $allData = getData($YANPIWS['dataPath'] . $date);
    if (isset($allData[$id])) {
        return array_pop($allData[$id]);
    } else {
        return array('NA','No Data Found',null ,'NA','NA');
    }
}

/**
 * given an array from getMostRecentTemp(), format it into html
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getTempHtml($tempLine)
{
    global $YANPIWS;
    $key = $tempLine[1];
    if (isset($YANPIWS['labels'][$key])) {
        $label = $YANPIWS['labels'][$key];
    } else {
        $label = "#$key";
    }
    if (isset($tempLine[2]) && $tempLine != null) {
        $temp = number_format($tempLine[2], 0);
        return "<span class='degrees'>{$temp}°</span>" .
            "<span class='label'>$label</span>\n";
    } else {
        return "No Data\n";
    }
}

/**
 * given an array from getMostRecentTemp(), format it into debug html showing age of temp
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getTempLastHtml($tempLine)
{
    global $YANPIWS;
    if ($tempLine[0] == "NA") {
        return "<li>$label: $age ". implode(" - ", $tempLine) . "</li>";
    } else {
        $lineEpoch = strtotime($tempLine[0]);
        $age = getHumanTime(time() - $lineEpoch);
        $temp = "{$tempLine[2]}°";
        $id = $tempLine[1];
        if (isset($YANPIWS['labels'][$id])) {
            $label = $YANPIWS['labels'][$id];
        } else {
            $label = "<no label>";
        }
        return "<li>$label: $temp $age ago</li>";
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
    return '<img src="moon.svg" class="moon" /> '. $time;
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
    return '<img src="sun.svg" class="sun" /> '. $time;
}

/**
 * get data from dark sky.  will cache data and refresh it every 15 minutes
 *
 * @return stdClass of either resutls or very lightly populated error object
 */
function getDarkSkyData()
{
    global $YANPIWS;
    $path = $YANPIWS['dataPath'];
    $cache = $path . 'darksky.cache';
    $hourAgo = time() - (60*15); // 15 minutes
    $data = false;
    if(isset($YANPIWS['darksky']) && $YANPIWS['darksky'] != null && isset($YANPIWS['lat']) && isset($YANPIWS['lon']) ) {
        $url = 'https://api.darksky.net/forecast/' . $YANPIWS['darksky'] . '/' . $YANPIWS['lat'] . ',' . $YANPIWS['lon'];
        if ((!is_file($cache) || filectime($cache) < $hourAgo) && is_writable($path)) {
            $data = json_decode(file_get_contents($url));
            file_put_contents($cache, serialize($data));
        } elseif (is_file($cache)) {
            $data = unserialize(file_get_contents($cache));
        }
    }
    if ($data === false || $data === null) {
        $data = new stdClass();
        $data->daily = null;
        $data->currently = null;
    }
    return $data;
}

/**
 * expects the $data->daily object from getDarkSkyData(), returns 5 days of forecast HTML
 *
 * @param null $daily $data->daily object from getDarkSkyData()
 * @return string of HTML
 */
function getDailyForecastHtml($daily = null)
{
    global $YANPIWS;
    $html = '';
    $js = '';
    $animate = $YANPIWS['animate'];
    if ($daily == null) {
        // show rain for error
        $html .= "<div class='forecastday'>";
        $html .= "<img src='./skycons/rain.png' width='70' height='70' /> ";
        $html .= "No Dark Sky Data for forecast.";
        $html .= "</div>";
    } else {
        $count = 1;
        foreach ($daily->data as $day) {
            if ($count == 1) {
                $today = "Today";
            } elseif($count > 5) {
                break;
            } else {
                $today = substr(date('D', $day->time), 0, 3);
            }
            $html .= "<div class='forecastday'>";
            $html .= "<div class='forcastDay'>$today</div>";
            if ($animate) {
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
 * expects the $data->currently object from getDarkSkyData(), returns windspeed HTML
 *
 * @param null $daily $data->currently object from getDarkSkyData()
 * @return string of HTML
 */
function getCurrentWindHtml($currentlyObject)
{
    return number_format($currentlyObject->windSpeed, 0) . " mph";
}