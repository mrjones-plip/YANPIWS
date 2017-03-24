
<link rel="stylesheet" type="text/css" href="styles.css" />
<?php
if(is_file('config.php')){
    require_once ("config.php");
} else {
    die ('config.php not found :(');
}

require_once ("get_data.php");
$today = date('Y-m-d', time());
$time = date('h:i:s A', time());
$date = date('D j, F Y', time());
$allData = getData("./data/" . $today);

$currentTempHtml = '';
foreach ($YANPIWS['labels'] as $id => $label){
    $tempLine = getMostRecentTemp($id);
    $currentTempHtml .= getTempHtml($tempLine);
}
$sunrise = 'Sunrise ' . getSunriseTime();
$sunset = 'Sunset ' . getSunsetTime();

$forecast = getDarkSkyData();
$forecastHtml = getDailyForecastHtml($forecast->daily);
?>


<p><?php echo $date ?> - <?php echo $time ?></p>
<p style="clear:both;"><?php echo $sunrise ?> - <?php echo $sunset ?></p>
<p><?php echo $currentTempHtml ?></p>
<p  style="clear:both;"><?php echo $forecastHtml ?></p>

