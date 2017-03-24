
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

<div class="col">
    <div class="row"><?php echo $date ?></div>
    <div class="row"><?php echo $time ?></div>
    <div class="row"><?php echo $sunrise ?></div>
    <div class="row"><?php echo $sunset ?></div>
    <div class="row"><?php echo $currentTempHtml ?></div>
</div>
<div class="col rigthtCol">
    <?php echo $forecastHtml ?>
</div>
