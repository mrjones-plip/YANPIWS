<?php
global $YANPIWS;
$YANPIWS = array();
$YANPIWS['lat'] = 31.775554;
$YANPIWS['lon'] = -81.822436;
$YANPIWS['dataPath'] = '/var/www/html/data'; // no trailing slash please ;)
$YANPIWS['gmt_offset'] = '-8';
$YANPIWS['darksky'] = false;
$YANPIWS['labels'] = array(
    '211' => 'In',
    '109' => 'Out',
);
$YANPIWS['api_password'] = 'boxcar-spinning-problem-rockslide-scored'; // should match password below
$YANPIWS['servers'][] = array(
    'url' => 'http://127.0.0.1',
    'password' => 'boxcar-spinning-problem-rockslide-scored', // should match password above
);