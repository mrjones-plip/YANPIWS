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
$cacheMoontime= getCacheAge(false,'moon');
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
<link rel="stylesheet" type="text/css" href="css/styles.css.php?<?= $YANPIWS['cache_bust'] ?>" />
<div class="col">
    <div class="row">
        <p>
            <a href="/" class="homeLink"><-  Weather</a>
        </p>
        <a href="https://github.com/mrjones-plip/YANPIWS">YANPIWS <?php print $YANPIWS['release'] ?></a> - Released Jan 21, 2026<br />
        <a href="http://pirateweather.net/en/latest/#introduction/">Powered by Pirate Weather</a><br />
        <a href="https://aa.usno.navy.mil/about/mission">Powered by Astronomical Applications Department, U.S. Naval Observatory </a><br />
        <?php echo $currentTempHtml ?>
        Forecast Cache Age: <?php echo $cachetime ?><br/>
        Mooncast Cache Age: <?php echo $cacheMoontime ?><br/>
        IP: <?php echo $address ?>
    </div>
</div>

</body>
</html>
