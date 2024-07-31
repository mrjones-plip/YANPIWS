<?php
global $YANPIWS;
$path = realpath(dirname(__FILE__));
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'get_data.php';
getConfig();

$count = 1;
$refreshTempJS = '';
$tempsHtml = '';
$animateJS = '';

$status = configIsValid();
$statusHtml = getStatusHTML($status['valid']);
if(isset($_GET['toggle_theme'])){
    $cssToggleQuery = '&toggle_theme=1';
} else {
    $cssToggleQuery = '';
}

foreach ($YANPIWS['labels'] as $id => $label) {
    $tempsHtml .= "\t\t\t<div class='temp temp{$count}' id='temp{$count}'>" . get_json_inline('temp', $id) . "</div>\n";
    $refreshTempJS .= "\t\trefreshData('temp&id={$id}',\t'#temp{$count}',\tfalse,\t'temp');\n";
    $count++;
    if ($count > $YANPIWS['temp_count']) {
        break;
    }
}


if($YANPIWS['animate'] === 'true'){
    $animateJS .= "\trefreshData('forecast', '#forecast', animateForecast);\n";
}
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <script src="skycons/skycons.js?<?= $YANPIWS['cache_bust'] ?>"></script>
    <script src="js/jquery-3.5.1.min.js?<?= $YANPIWS['cache_bust']?>"></script>
    <script src="js/YANPIWS.js?<?= $YANPIWS['cache_bust'] ?>"></script>
    <script>const skycons = new Skycons({'color': 'white'});</script>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <title>YANPIWS</title>
</head>
<body>
<link rel="stylesheet" type="text/css" href="css/styles.css.php?<?=  $YANPIWS['cache_bust'] . $cssToggleQuery ?>" />

<?= $statusHtml ?>

<div id="YANPIWS" class="YANPIWS"><a href="stats.php" id="age">YANPIWS</a></div>

<div class="col">
    <div class="row temp-row">
        <a href="temps.php">
          <?= $tempsHtml ?>
        </a>
    </div>
</div>
<div class="col">
    <div class="row"></div>
    <div class="row ">
        <div id='datetimewind'>
            <div id="wind_now" class="wind_now small_time big_clock_hide"><?= get_json_inline('wind_now') ?></div>
            <div id='datetime'>
                <div id='time' class='small_time'><?= get_json_inline('time') ?></div>
                <div id='date' class='small_time'><?= get_json_inline('date') ?></div>
            </div>
        </div>
    </div>
    <div class="row suntimes big_clock_hide">
        <span><img src="images/sun.svg" class="sun" alt="Sunrise Time"/>
            <span id="sunrise" ><?= get_json_inline('sunrise') ?></span>
        </span>
        <span><img src="images/moon.svg" class="moon" alt="Sunset Time"/>
            <span id="sunset" ><?= get_json_inline('sunset') ?></span>
        </span>
        <span class="moonphase">
            <span class="light hemisphere"></span>
            <span class="dark hemisphere"></span>
            <span class="divider"></span>
        </span>
    </div>
</div>
<div class="col rightCol big_clock_hide" id="forecast">
    <?= get_json_inline('forecast') ?>
</div>
<span id="last_ajax"></span>
<script>
    let clockState = 'small';
    $( "#datetime" ).click(function() {
        if (clockState === 'small'){
            clockState = 'big';
        } else {
            clockState = 'small';
        }
        setClockSize(clockState, <?= $YANPIWS['font_time_date_wind']?>);
    });
    function refreshAll() {
        //          Endpoint        DOM Location    callback
        refreshData('sunrise',      '#sunrise');
        refreshData('sunset',       '#sunset');
        refreshData('wind_now',     '#wind_now');
        refreshData('date',         '#date');
        refreshData('time',         '#time');
        refreshData('forecast',     '#forecast',   animateForecast);
        refreshData('age',          '#age');
        refreshData('last_ajax',    '#last_ajax');
<?=     $refreshTempJS ?>
        setClockSize(clockState, <?= $YANPIWS['font_time_date_wind']?>);
        setMoonRotation('<?= get_json_inline('moonphase') ?>'); // todo - make this dynamic
    }
<?= $animateJS ?>
    setInterval ( refreshAll, 60000 );
    setMoonRotation('<?= get_json_inline('moonphase') ?>');
</script>
</body>
</html>
