<?php
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
 * @param string $file where CSV data is
 * @return array of formatted CSV data
 */
function getConfigOrDie(){
    if(is_file('config.php')){
        require_once ("config.php");
    } else {
        die (
            '<h3>Error</h3><p>no config.php!  Copy config.dist.php to config.php</p>'.
            getDailyForecastHtml()
        );
    }
}

function getData($file){
    if (is_file($file) && is_readable($file)){
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

function getMostRecentTemp($id, $date = null){
    global $YANPIWS;
    if ($date == null){
        $date = date('Y-m-d', time());
    }
    $allData = getData($YANPIWS['dataPath'] . $date);
    if (isset($allData[$id])) {
        return array_pop($allData[$id]);
    } else {
        return array('NA','No Data Found',null ,'NA','NA');
    }
}

function getTempHtml($tempLine, $id=1){
    global $YANPIWS;
    $key = $tempLine[1];
    if (isset($YANPIWS['labels'][$key])){
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

function getTempLastHtml($tempLine){
    if ($tempLine[0] == "NA"){
        return "<li>$label: $age ". implode(" - ",$tempLine) . "</li>";
    } else {
        $lineEpoch = strtotime($tempLine[0]);
        $age = getHumanTime(time() - $lineEpoch);
        $temp = "{$tempLine[2]}°";
        return "<li>$label: $temp $age ago</li>";
    }
}
function getSunriseTime(){
    global $YANPIWS;
    return date(
        'g:i A',
        date_sunrise(
            time(),
            SUNFUNCS_RET_TIMESTAMP,
            $YANPIWS['lat'],
            $YANPIWS['lon'],
            90,
            $YANPIWS['gmt_offset']
        )
    );
}
function getSunsetTime(){
    global $YANPIWS;
    return date(
        'g:i A',
        date_sunset(
            time(),
            SUNFUNCS_RET_TIMESTAMP,
            $YANPIWS['lat'],
            $YANPIWS['lon'],
            90,
            $YANPIWS['gmt_offset']
        )
    );
}
function getSunsetHtml($time){
    return '<img src="moon.svg" class="moon" /> '. $time;
}
function getSunriseHtml($time){
    return '<img src="sun.svg" class="sun" /> '. $time;
}

function getDarkSkyData(){
    global $YANPIWS;
    $path = $YANPIWS['dataPath'];
    $cache = $path.'darksky.cache';
    $hourAgo = time() - (60*60);
    $data = null;
    $url = 'https://api.darksky.net/forecast/' .$YANPIWS['darksky'] . '/' . $YANPIWS['lat'] . ',' . $YANPIWS['lon'];
    if($YANPIWS['darksky'] != null ) {
        if ((!is_file($cache) || filectime($cache) < $hourAgo) && is_writable($path)) {
            $data = json_decode(file_get_contents($url));
            file_put_contents($cache, serialize($data));
        } elseif (is_file($cache)) {
            $data = unserialize(file_get_contents($cache));
        } else {
            $data = json_decode(file_get_contents($url));
        }
    }
    return $data;
}

function getDailyForecastHtml($daily = null){
    $html = '';
    $js = '';
    if ($daily == null){
        // show rain for error

        $html .= "<div class='forecastday'>";
        $html .= "<img src=''./skycons/rain.png' width='100' height='100'></img> No Dark Sky Data for forcast";
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
            $html .= "<img src='./skycons/{$day->icon}.png' width='70' height='70'></img>";
            $html .= '<div class="hight spreadtemp">' . number_format($day->temperatureMax, 0) . '°</div>';
            $html .= '<div class="lowt spreadtemp">' . number_format($day->temperatureMin, 0) . '°</div>';
            $html .= '<div class="wind"> ' . number_format($day->windSpeed, 0) .  ' mph</div>';
            $html .= '</div>';

            $count++;
        }
    }
    return $html;
}

// thanks http://www.kavoir.com/2010/09/php-get-human-readable-time-from-seconds.html
function getHumanTime($s) {
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