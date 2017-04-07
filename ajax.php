<?php


require_once ("get_data.php");
getConfigOrDie();
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
        print getCurrentWindHtml($forecast->currently);
    } elseif ($_GET['content'] == 'sunset'){
        print getSunsetHtml(getSunsetTime());
    } elseif ($_GET['content'] == 'sunrise'){
        print getSunriseHtml(getSunriseTime());
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