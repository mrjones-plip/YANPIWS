<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
<meta charset="utf-8">
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css" />
<?php
require_once "get_data.php";
getConfig();

$today = date('M j, Y, g:i a', time());
$allData = getData($YANPIWS['dataPath'] . $today);

$currentTempHtml = '<ul>';
foreach ($YANPIWS['labels'] as $id => $label){
    $tempLine = getMostRecentTemp($id);
    $currentTempHtml .= getTempLastHtml($tempLine);
}
$currentTempHtml .= '</ul>';

if (isset($_SERVER['SERVER_ADDR'])) {
    $address = $_SERVER['SERVER_ADDR'];
} else {
    $address = 'NA';
}

$darkskytime= getCacheAge();

?>
<div class="col">
    <div class="row">
        <p>
            <a href="/" class="homeLink"><-  Weather</a><br />
            <a href="./config.php" class="homeLink">Config</a>
        </p>
        <a href="https://github.com/Ths2-9Y-LqJt6/YANPIWS">YANPIS 0.9</a> - Released Mar 26, 2017<br />
        <a href="https://darksky.net/poweredby/">Powered by Dark Sky</a><br />
        <?php echo $currentTempHtml ?>
        Dark Sky Cache Age: <?php echo $darkskytime ?><br/>
        IP: <?php echo $address ?>
    </div>
</div>

</body>
</html>