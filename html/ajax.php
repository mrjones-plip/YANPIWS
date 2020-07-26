<?php


require_once ("get_data.php");
getConfig();
if (isset($_GET['content'])){

    $today = date('Y-m-d', time());
    $time = date('g:i A', time());
    $date = date('D M j', time());
    $forecast = getDarkSkyData();

    if (isset($_GET['raw']) && $_GET['raw'] === '1'){
        $raw = true;
    } else {
        $raw = false;
    }

    switch ($_GET['content']){
        case "forecast":
            if ($raw){
                print json_encode(array('forecast' => getDailyForecastHtml($forecast->daily)));
            } else {
                print getDailyForecastHtml($forecast->daily);
            }
            break;

        case "wind_now":;
            if (isset($forecast->currently)) {
                if ($raw){
                    print json_encode(array('wind' => getCurrentWind($forecast->currently)));
                } else {
                    print getCurrentWind($forecast->currently);
                }

            }
            break;

        case "sunset":
            if (isset($forecast->daily->data[0]->sunsetTime)){
                if ($raw){
                    $time = date('g:i A', $forecast->daily->data[0]->sunsetTime);
                    print json_encode(array('sunset' => $time));
                } else {
                    print getSunsetHtml($forecast->daily->data[0]->sunsetTime);
                }
            }
            break;

        case "sunrise":
            if (isset($forecast->daily->data[0]->sunriseTime)){
                if ($raw){
                    $time = date('g:i A', $forecast->daily->data[0]->sunriseTime);
                    print json_encode(array('sunrise' => $time));
                } else {
                    print getSunriseHtml($forecast->daily->data[0]->sunriseTime);
                }
            }
            break;

        case "age":
            $cacheAge = getCacheAge(true);
            $maxTempAge = 0;
            foreach ($YANPIWS['labels'] as $id => $label){
                $tempLine = getMostRecentTemp($id);
                $currentTempAge = getTempLastHtml($tempLine, true);
                if ($maxTempAge < $currentTempAge){
                    $maxTempAge = $currentTempAge;
                }
            }
            if ($currentTempAge > 600 || $maxTempAge > 600){
                $result['age'] = '<span style="color: yellow">YANPIWS</span>';
            } else {
                $result['age'] = 'YANPIWS';
            }
            print json_encode($result);
            break;

        case "datetime":
            if($raw){
                print json_encode(array('date' => $date, 'time' => $time));
            } else {
                print "<div class='time'>$time</div><div class='date'> $date</div>";
            }
            break;

        case "temp":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                if($raw){
                    print json_encode(array('temp' => getTempHtml($tempLine)));
                } else {
                    print getTempHtml($tempLine);
                }
            }
            break;

        case "humidity":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                if($raw){
                    print json_encode(array($tempLine));
                } else {
                    print getHumidityHtml($tempLine);
                }
            }
            break;

        case "last_ajax":
            // update this ajax file per #61 https://github.com/Ths2-9Y-LqJt6/YANPIWS/issues/61
            touch($YANPIWS['dataPath'] . '/' . 'last_ajax');
            print json_encode(array('last_ajax' => time()));
            break;
    }
}
exit;
