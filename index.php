
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
$time = date('g:i A', time());
$date = date('D M j', time());
$allData = getData("./data/" . $today);

$currentTempHtml = '';
$count = 1;
foreach ($YANPIWS['labels'] as $id => $label){
    $tempLine = getMostRecentTemp($id);
    $currentTempHtml .= getTempHtml($tempLine, $count++);
}
$sunrise = '<img src="sun.svg" class="sun" /> ' . getSunriseTime();
$sunset = '<img src="moon.svg" class="moon" />  ' . getSunsetTime();

$forecast = getDarkSkyData();
$forecastHtml = getDailyForecastHtml($forecast->daily);
?>

<div class="col">
    <div class="row"><?php echo $currentTempHtml ?></div>
    <div class="row ">
        <div class=" time"><?php echo $time ?></div>
        <div class="date"><?php echo $date ?></div>
    </div>
    <div class="row suntimes"><?php echo $sunrise ?><?php echo $sunset ?></div>
</div>
<div class="col rigthtCol">
    <?php echo $forecastHtml ?>
</div>

</body>
</html>