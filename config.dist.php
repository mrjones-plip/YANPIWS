<?php
global $YANPIWS;
$YANPIWS = array();

// edit these two to be your location.  Use one of these if you need help! https://duckduckgo.com/?q=what's+my+lat+long+finder
$YANPIWS['lat'] = 31.775554;
$YANPIWS['lon'] = -81.822436;

// set your time zone
$YANPIWS['gmt_offset'] = '-8';

// set your darksky API token
$YANPIWS['darksky'] = false;

// label ID map to human name
$YANPIWS['labels'] = array(
    '211' => 'In',
    '109' => 'Out',
);

// likely this won't need to change if you're following default install instructions
$YANPIWS['dataPath'] = '/var/www/html/data'; // no trailing slash please ;)

// unless you're deploying a lot of nodes reporting back to a central server, don't touch these ;)
$YANPIWS['api_password'] = 'boxcar-spinning-problem-rockslide-scored'; // should match password below
$YANPIWS['servers'][] = array(
    'url' => 'http://127.0.0.1',  // no trailing slash please ;)
    'password' => 'boxcar-spinning-problem-rockslide-scored', // should match password above
);