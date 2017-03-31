
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css" />
<?php
require_once ("get_data.php");
getConfigOrDie();

$today = date('M j, Y, g:i a', time());
$time = date('g:i A', time());
$date = date('D M j', time());
$allData = getData($YANPIWS['dataPath'] . $today);

$currentTempHtml = '<ul>';
$count = 1;

foreach ($YANPIWS['labels'] as $id => $label){
    $tempLine = getMostRecentTemp($id);
    $lineEpoch = strtotime($tempLine[0]);
    $age = date("F j, Y, g:i a", $lineEpoch);
    $currentTempHtml .= "<li>$label: $age (". implode(" - ",$tempLine) . ")</li>";
}
$currentTempHtml .= '</ul>';

if (isset($_SERVER['SERVER_ADDR'])){
    $address = $_SERVER['SERVER_ADDR'];
} else {
    $address = 'NA';
}

?>
<div class="col">
    <div class="row">
        <a href="/" class="homeLink"><-  Weather</a> - Current time: <?php echo $today?><br />
        <a href="https://github.com/Ths2-9Y-LqJt6/YANPIWS">YANPIS 0.9</a> - Released Mar 26, 2017<br />
        <a href="https://darksky.net/poweredby/">Powered by Dark Sky</a><br />
        <p>
        Last Sensor Info:<br />
        <?php echo $currentTempHtml ?>
        </p>
        IP: <?php echo $address ?>
    </div>
</div>

</body>
</html>