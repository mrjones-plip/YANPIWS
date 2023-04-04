<?php
require_once "get_data.php";
getConfig();

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

$cachetime= getCacheAge();
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css.php?<?= $YANPIWS['cache_bust'] ?>" />
<div class="col">
    <div class="row">
        <p>
            <a href="/" class="homeLink"><-  Weather</a>
        </p>
        <a href="https://github.com/mrjones-plip/YANPIWS">YANPIS 0.10.0</a> - Released Apr 3, 2023<br />
        <a href="http://pirateweather.net/en/latest/#introduction/">Powered by Pirate Weather</a><br />
        <?php echo $currentTempHtml ?>
        Forecast Cache Age: <?php echo $cachetime ?><br/>
        IP: <?php echo $address ?>
    </div>
</div>

</body>
</html>
