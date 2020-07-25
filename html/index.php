<?php
global $YANPIWS;
require_once 'get_data.php';
getConfig();

$forecast = getDarkSkyData();
$status = configIsValid();
$statusHtml = '';
if ($status['valid'] != true) {
    $statusHtml .= "<div class='error'>ERROR: {$status['reason']}</div>";
    $statusHtml .= "<style>.temp,.suntimes{display:none;}</style>";
} else {
    $statusHtml .= '<div id="YANPIWS" class="YANPIWS"></div>';
}

$count = 1;
$refreshTempJS = '';
$tempsHtml = '';
foreach ($YANPIWS['labels'] as $id => $label) {
    $tempsHtml .= "\t\t\t<div class='temp temp{$count}' id='temp{$count}'></div>\n";
    $refreshTempJS .= "\t\trefreshTemp($id,$count);\n";
    $count++;
    if ($count > $YANPIWS['temp_count']) {
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <script src='skycons/skycons.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script src='jquery-3.5.1.min.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script src="./YANPIWS.js?<?php echo $YANPIWS['cache_bust'] ?>"></script>
    <script>var skycons = new Skycons({'color': 'white'});</script>
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css.php?<?=  $YANPIWS['cache_bust'] ?>" />

<?= $statusHtml ?>

<div class="col">
    <div class="row temp-row">
        <a href="/temps.php">
            <?= $tempsHtml ?>
        </a>
    </div>
</div>
<div class="col">
    <div class="row"></div>
    <div class="row ">
        <div id="wind_now" class="wind_now"></div>
        <div  id="datetime">
            <div id='time'></div>
            <div id='date'></div>
        </div>
    </div>
    <div class="row suntimes">
        <span><img src="sun.svg" class="sun" /> <span id="sunrise" ></span></span>
        <span><img src="moon.svg" class="sun" /> <span id="sunset" ></span></span>
    </div>
</div>
<div class="col rigthtCol" id="forecast">
</div>
<script>
    function refreshAll() {
        //          Endpoint    data    DOM
        refeshData('sunrise', 'sunrise', '#sunrise');
        refeshData('sunset', 'sunset', '#sunset');
        refeshData('wind_now', 'wind', '#wind_now');
        // todo - date and time aren't being formatted correctly
        refeshData('datetime', 'date', '#date');
        refeshData('datetime', 'time', '#time');

        // todo - migrate this to use refreshData()
        refreshForecast();

        // todo - migrate this to use refreshData() instead of refreshTemp() above
        <?= $refreshTempJS ?>

        checkTempAges();
        refreshLastAjax();
    }
    refreshAll();
    setInterval ( refreshAll, 60000 );
</script>
<span id="dev_null"></span>
</body>
</html>
