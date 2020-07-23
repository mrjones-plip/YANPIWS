<?php
global $YANPIWS;
require_once 'get_data.php';
getConfig();
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <script src='skycons/skycons.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script src='jquery.3.2.1.slim.min.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script>var skycons = new Skycons({'color': 'white'});</script>
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css.php?<?=  $YANPIWS['cache_bust'] ?>" />

<?php

$forecast = getDarkSkyData();
$status =configIsValid();
if($status['valid'] != true){
    print "<div class='error'>ERROR: {$status['reason']}</div>";
    print "<style>.temp,.suntimes{display:none;}</style>";
} else {
    print '<div id="YANPIWS" class="YANPIWS"></div>';
}
?>
<div class="col">
    <div class="row temp-row">
        <a href="/temps.php">
<?php
$count = 1;
foreach ($YANPIWS['labels'] as $id => $label){
        echo "\t\t\t<div class='temp temp{$count}' id='temp{$count}'></div>\n";
        $count++;
        if ($count > $YANPIWS['temp_count']){
            break;
        }
}
?>
        </a>
    </div>
</div>
<div class="col">
    <div class="row"></div>
    <div class="row ">
        <div id="wind_now" class="wind_now"></div>
        <div  id="datetime"></div>
    </div>
    <div class="row suntimes">
        <span id="sunrise" ></span>
        <span id="sunset" ></span>
    </div>
</div>
<div class="col rigthtCol" id="forecast">
</div>
<script src="./YANPIWS.js?<?php echo $YANPIWS['cache_bust'] ?>"></script>
<script>
    function refreshAll() {
        refeshDateTime();
        refreshForecast();
        refreshSunrise();
        refreshSunset();
        refreshCurrentWind();
        checkTempAges();
        refreshLastAjax();
<?php
        $count = 1;
foreach ($YANPIWS['labels'] as $id => $label){
    echo "\t\trefreshTemp($id,$count);\n";
    $count++;
}
    ?>
    }
    refreshAll();
    setInterval ( refreshAll, 60000 );
</script>
<span id="dev_null"></span>
</body>
</html>
