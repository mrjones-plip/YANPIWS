<?php
// important - don't change these two lines!
global $YANPIWS;
$YANPIWS = array();

// update to your location
$YANPIWS['lat'] = 31.775554;
$YANPIWS['lon'] = -81.822436;

// go get a dark sky API key and then enter it like this (but yours, not this test one):
// $YANPIWS['darksky'] = 'e7c6af681jed4sdd3d0eddd79av61883';
// sign up here: https://darksky.net/dev/
$YANPIWS['darksky'] = false;

// IDs from your temp sensors to give them labels, first two will be shown
// on main screen.  first in upper left, second in upper right
$YANPIWS['labels'] = array(
    '211' => 'In',
    '109' => 'Out',
);

// do you wnat your forecast to use animated icons or not?
$YANPIWS['animate'] = true;

// don't change this unless you know what you're doing ;)
$YANPIWS['dataPath'] = '/var/www/html/data/';
