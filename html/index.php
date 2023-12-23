<?php
global $YANPIWS;
require_once 'get_data.php';
getConfig();

$count = 1;
$refreshTempJS = '';
$tempsHtml = '';

$forecast = getForecastData();
$status = configIsValid();
$statusHtml = getStatusHTML($status['valid']);
if(isset($_GET['toggle_theme'])){
    $cssToggleQuery = '&toggle_theme=1';
} else {
    $cssToggleQuery = '';
}

function get_json_inline($content){
    $tmp = json_decode(fetch_json($content, $YANPIWS));
    return $tmp->$content;
}

foreach ($YANPIWS['labels'] as $id => $label) {
    $tempsHtml .= "\t\t\t<div class='temp temp{$count}' id='temp{$count}'></div>\n";
    $refreshTempJS .= "\t\t refreshData('temp&id={$id}',\t'temp',\t'#temp{$count}');\n";
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
    <script src="YANPIWS.js?<?php echo $YANPIWS['cache_bust'] ?>"></script>
    <script>const skycons = new Skycons({'color': 'white'});</script>
    <title>YANPIWS</title>
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css.php?<?=  $YANPIWS['cache_bust'] . $cssToggleQuery ?>" />

<?= $statusHtml ?>

<div id="YANPIWS" class="YANPIWS"><a href="stats.php">YANPIWS</a></div>

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
                <div id='date' class='small_time'<?= get_json_inline('date') ?>></div>
            </div>
        </div>
    </div>
    <div class="row suntimes big_clock_hide">
        <span><img src="sun.svg" class="sun" alt="Sunrise Time"/>
            <span id="sunrise" ><?= get_json_inline('sunrise') ?></span>
        </span>
        <span><img src="moon.svg" class="moon" alt="Sunset Time"/>
            <span id="sunset" ><?= get_json_inline('sunset') ?></span>
        </span>
    </div>
</div>
<div class="col rightCol big_clock_hide" id="forecast">
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
        //          Endpoint    data        DOM Location    callback
        refreshData('sunrise',   'sunrise',  '#sunrise');
        refreshData('sunset',    'sunset',   '#sunset');
        refreshData('wind_now',  'wind_now', '#wind_now');
        refreshData('date',      'date',     '#date');
        refreshData('time',      'time',     '#time');
        refreshData('forecast',  'forecast', '#forecast', animateForecast);
        refreshData('age',       'age',      '#YANPIWS a');
        refreshData('last_ajax', 'last_ajax','#last_ajax');
        setClockSize(clockState, <?= $YANPIWS['font_time_date_wind']?>);
<?= $refreshTempJS ?>
    }
    // refreshAll();
    setInterval ( refreshAll, 60000 );
</script>
</body>
</html>
