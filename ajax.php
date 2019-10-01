<?php


require_once ("get_data.php");
getConfig();
if (isset($_GET['content'])){

    $today = date('Y-m-d', time());
    $time = date('g:i A', time());
    $date = date('D M j', time());
    $count=1;
    if ($_GET['content'] == 'forecast'){
        $forecast = getDarkSkyData();
        print getDailyForecastHtml($forecast->daily);
    } elseif ($_GET['content'] == 'wind_now'){
        $forecast = getDarkSkyData();
        if (isset($forecast->currently))
        print getCurrentWindHtml($forecast->currently);
    } elseif ($_GET['content'] == 'sunset'){
        $forecast = getDarkSkyData();
        if (isset($forecast->daily->data[0]->sunsetTime))
        print getSunsetHtml($forecast->daily->data[0]->sunsetTime);
    } elseif ($_GET['content'] == 'sunrise'){
        $forecast = getDarkSkyData();
        if (isset($forecast->daily->data[0]->sunriseTime))
        print getSunriseHtml($forecast->daily->data[0]->sunriseTime);
    } elseif ($_GET['content'] == 'age'){
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
    } elseif ($_GET['content'] == 'datetime'){
        print "<div class='time'>$time</div><div class='date'>$date</div>";
    } elseif ($_GET['content'] == 'temp' && isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
        $tempLine = getMostRecentTemp($_GET['id']);
        print getTempHtml($tempLine, $count++);
    } else {
        //ahhhh!  wtf!?!
        print "No AJAX here";
    }

}
exit;