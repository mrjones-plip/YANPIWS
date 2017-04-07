<?php
require_once ('get_data.php');
require_once ('config.php');
getConfigOrDie();
$forecast = getDarkSkyData();
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css" />
<!--<link rel="stylesheet" type="text/css" href="styles-mini.css" />-->

<div class="YANPIWS"><a href="/stats.php">YANPIWS</a></div>
<div class="col">
    <div class="row">
        <div class="temp temp1" id="temp1"></div>
<?php
$count = 1;
foreach ($YANPIWS['labels'] as $id => $label){
        echo "\t<div class='temp temp{$count}' id='temp{$count}'></div>\n";
        $count++;
}
?>
<div class="YANPIWS"><a href="/stats.php">YANPIWS</a></div>
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
<script src="./YANPIWS.js"></script>
<script>
    function refreshAll() {
        refeshDateTime();
        refreshForecast();
        refreshSunrise();
        refreshSunset();
        refreshCurrentWind();
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
</body>
</html>