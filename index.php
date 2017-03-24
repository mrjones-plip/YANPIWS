
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="50; URL=index.php">
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css" />
<?php
require_once ("get_data.php");
if(is_file('config.php')){
    require_once ("config.php");
} else {
    die (
        '<h3>Error</h3><p>no config.php!  Copy config.dist.php to config.php</p>'.
        getDailyForecastHtml()
    );
}

$today = date('Y-m-d', time());
$time = date('h:i A', time());
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
    <div class="row"><?php echo $currentTempHtml ?></div>
    <div class="row"><?php echo $date ?></div>
    <div class="row"><?php echo $time ?></div>
    <div class="row"><?php echo $sunrise ?></div>
    <div class="row"><?php echo $sunset ?></div>
</div>
<div class="col rigthtCol">
    <?php echo $forecastHtml ?>
</div>

</body>
</html>