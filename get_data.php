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
        return array('NA','NA','NA','NA','NA');
    }
}

function getTempHtml($tempLine, $id=1){
    global $YANPIWS;
    $key = $tempLine[1];
    if (isset($YANPIWS['labels'][$key])){
        $label = $YANPIWS['labels'][$key];
    } else {
        $label = "ID $key";
    }
    if (isset($tempLine[2])) {
        $temp = number_format($tempLine[2], 0);
    } else {
        $temp = 'NA';
    }
    return "<div class='temp temp{$id}'><span class='degrees'>{$temp}°</span>" .
        "<span class='label'>$label</span></div>\n";
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

function getDarkSkyData(){
    global $YANPIWS;
    $path = $YANPIWS['dataPath'];
    $cache = $path.'/darksky.cahce';
    $hourAgo = time() - (60*60);
    $url = 'https://api.darksky.net/forecast/' .$YANPIWS['darksky'] . '/' . $YANPIWS['lat'] . ',' . $YANPIWS['lon'];
    if ((!is_file($cache) || filectime($cache) < $hourAgo) && is_writable($path)){
        $data = json_decode(file_get_contents($url));
        file_put_contents($cache,serialize($data));
    } elseif (is_file($cache)){
        $data = unserialize(file_get_contents($cache));
    } else {
        $data = json_decode(file_get_contents($url));
    }
    return $data;
}

function getDailyForecastHtml($daily = null){
    $html = '';
    $js = '';
    if ($daily == null){
        // show rain for error
        $html .= "<canvas id='W.112035303696' width='100' height='100'></canvas>";
        $js .= "skycons.add('W.112035303696', 'sleet');\n";
    } else {
        $count = 1;
        foreach ($daily->data as $day) {
            $rand = rand(99999, 999999999999);
            if ($count == 1) {
                $today = "Today";
            } elseif($count > 5) {
                break;
            } else {
                $today = substr(date('D', $day->time), 0, 3);
            }
            $html .= "<div class='forecastday'>";
            $html .= "<div class='forcastDay'>$today</div>";
            $html .= "<canvas id='$today.$rand' width='70' height='70'></canvas>";
            $html .= '<div class="hight spreadtemp">' . number_format($day->temperatureMax, 0) . '°</div>';
            $html .= '<div class="lowt spreadtemp">' . number_format($day->temperatureMin, 0) . '°</div>';
            $html .= '<div class="wind"> ' . number_format($day->windSpeed, 0) .  ' mph</div>';
            $html .= '</div>';

            $js .= "skycons.add('$today.$rand', '$day->icon');\n";
            $count++;
        }
    }
    $html .= "
        <script src='skycons/skycons.js'></script>
        <script>
          var skycons = new Skycons({'color': 'white'});
          $js
          skycons.play();
        </script>
    ";
    return $html;
}