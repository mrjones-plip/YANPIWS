<?php


require_once ("get_data.php");
getConfig();
if (isset($_GET['content'])){

    $today = date('Y-m-d', time());
    $time = date('g:i A', time());
    $date = date('D M j', time());
    $forecast = getDarkSkyData();

    if ($_GET['raw'] === '1'){
        $raw = true;
    } else {
        $raw = false;
    }

    switch ($_GET['content']){
        case "forecast":
            print getDailyForecastHtml($forecast->daily);
            break;

        case "wind_now":;
            if (isset($forecast->currently));
            break;

        case "sunset":
            if (isset($forecast->daily->data[0]->sunsetTime)){
                print getSunsetHtml($forecast->daily->data[0]->sunsetTime);
            }
            break;

        case "sunrise":
            if (isset($forecast->daily->data[0]->sunriseTime)){
                print getSunriseHtml($forecast->daily->data[0]->sunriseTime);
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
                print '<a class="yellow" href="/stats.php">YANPIWS</a>';
            } else {
                print '<a  href="/stats.php">YANPIWS</a>';
            }
            break;

        case "datetime":
            print "<div class='time'>$time</div><div class='date'> $date</div>";
            break;

        case "temp":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                print getTempHtml($tempLine);
            }
            break;

        case "humidity":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                print getHumidityHtml($tempLine);
            }
            break;

        case "last_ajax":
            // update this ajax file per #61 https://github.com/Ths2-9Y-LqJt6/YANPIWS/issues/61
            touch($YANPIWS['dataPath'] . '/' . 'last_ajax');
            print "<!-- " .  time() . "-->";
            break;

        // todo do catch all somehow?
        // print print "No AJAX here";

    }
}
exit;