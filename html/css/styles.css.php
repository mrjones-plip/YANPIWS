<?php
global $YANPIWS;
$path = realpath(dirname(__FILE__)) . "/../";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'get_data.php';
getConfig('../../');
configIsValid();
$tempWidth = round(100/$YANPIWS['temp_count']);
header("Content-type: text/css");
if ($YANPIWS['theme'] === 'light'){
    $background_color = 'white';
    $font_color = 'black';
    $sun_moon_forecast = 'filter: invert(100%);';
} else {
    $background_color = 'black';
    $font_color = 'white';
    $sun_moon_forecast = '';
}
?>

body {
    margin: 10px;
    padding: 10px;
    background-color: <?= $background_color?>;
    color: <?= $font_color?>;
    font-size: 22pt;
    font-family: sans-serif;
}
a {
    color: <?= $font_color?>;
}
.error {
    color: red;
    font-size: 30pt;
}
.errorImg {
    width:60px;
}
.homeLink {
    font-size:29pt;
}
.YANPIWS a {
    color: darkgreen;
    text-decoration: none;
    font-size:12pt;
}
a.yellow {
    color: yellow;
}
.YANPIWS {
    position: fixed;
    z-index: 10000;
    left: 42%;
    top: -10px;
}
.suntimes img, #forecast canvas, #forecast img {
    <?= $sun_moon_forecast ?>
}
.forecastday {
    width:20%;
    float:left;
    text-align: center;
}
.temp {
    padding-bottom:10px;
    font-size: <?= $YANPIWS['font_temp']?>pt;
    float: left;
    text-align: center;
    width: <?= $tempWidth ?>%;
}

.temp:last-child  {
    text-align: right;
}
.temp:first-child  {
    text-align: left;
}

.degrees {
    font-weight: bold;
}
.col {
    float:left;
    width: 100%;
}
.rightCol{
    padding-top:20px;
}
.row {
    padding-bottom: 5px;
    clear:both;
    width: 100%;
}
.sun {
    width:53px;
}

.label, .wind_now {
    font-size:<?= $YANPIWS['font_time_date_wind']?>pt;
    font-weight: bold;
}

.big_time {
    font-size: 127pt;
    text-align: center;
    width: 100%;
}

.small_time {
    font-size: <?= $YANPIWS['font_time_date_wind']?>pt;
    text-align: left;
    width: fit-content;
    padding: 5px;
    display: inline-block;
}

.big_clock_hide.big_time {
    display: none;
}

.label {
    text-transform: uppercase;
}
#date, #time, #wind_now{
    font-weight: bold;
}

#time:hover, #date:hover, .moontimes:hover, .bigmoon:hover {
    cursor: pointer;
}
.wind_now {
    width: fit-content;
    display: inline-block;
    float: right;
    padding-left: 30px;
}

#datetime {
    display: block;
}

#date {
    padding-left: 30px;
}
.lowt {
    color: #476b6b;
}
.spreadtemp {
    font-size:18pt;
}
.wind {
    font-size:12pt;
}
.suntimes {
    padding-top: 20px;
}
label {
    clear: both;
    float: left;
    width: 25%;
    text-align: left;
}
input {
    width:70%;
}
#last_ajax {
    display: none;
}

/* moon phase */
.moonphase {
    border-radius: 100%;
    width: 50px;
    height: 50px;
    display: flex;
    overflow: hidden;
    align-items: center;
    position: relative;
    margin: 0 20px 0 20px;
}

.bigmoon .moonphase {
    width: 200px;
    height: 200px;
    float: left;
    margin-right: 40px;
}

.moontimes>span {
    float: right;
}
.moontimes #moonphase {
    margin-right: 20px;
    margin-left: 20px;
}
.moontimes .time {
    margin-top: 24px;
}
.bigmoon {
    display: none;
}
.hemisphere {
    width: 50%;
    height: 100%;
}
.light {
    background-color: #F4F6F0;
}
.dark {
    background-color: #575851;
}
.divider,
.divider:after {
    top: 0;
    left: 0;
    width: 50px;
    height: 50px;
    position: absolute;
    border-radius: 100%;
    transform-style: preserve-3d;
    backface-visibility: hidden;
}

.bigmoon  .divider,
.bigmoon .divider:after {
    width: 200px;
    height: 200px;
}

.divider {
    background-color: #575851;
}
.divider:after {
    content: '';
    background-color: #F4F6F0;
    transform: rotateY(180deg);
}


@media only screen and (max-width : 480px) {
    body {
        font-size: 15pt;
    }
    .error {
        color: red;
        font-size: 20pt;
    }
    .errorImg {
        width:40px;
    }
    .temp {
        font-size: 25pt;
    }
    .rightCol{
        padding-top:10px;
    }
    .date, .time, .label, .wind_now {
        font-size:15pt;
    }
    .spreadtemp {
        font-size:11pt;
    }
    .YANPIWS a{
        font-size:15pt;
    }
    .suntimes {
        padding-top: 10px;
    }
    canvas {
        width:50px;
        height:50px;
    }
}
