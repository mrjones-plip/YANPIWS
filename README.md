# YANPIWS
Yet Another Pi Weather Station (YANPIWS)- My explorations in getting a Rasberry Pi showing local time and weather

## Background

With a daily workflow that invoves checking out a repo, making
commits, and the stopping work, it only made sense that I'd
do the same for my efforts to write a little weather app for my
Pi. This habbit means my work is always backed up and ready
for others to review.

Goals for this project are:

* Show current time
* Show sunset/sunrise times
* Show moonrise/moonset times
* Show weather forecast from some provider
* Show show current weather from some provider
* Show live weather from local, wireless sensors
* Show past weather from local, wireless sensors

## Hardware

* SDR USB dongle: http://amzn.to/2nc5MhX
* temp sensor: http://amzn.to/2lVdhJ6
* 5" screen: http://amzn.to/2mRjWYT
* pi and power; http://amzn.to/2nklto3

## Software

TBD - but using rtl_433 (https://github.com/merbanan/rtl_433) 
we'll use the USB SDR dongle to read the temps from the sensors
with a a call like this:

```
rtl_433 -C customary -F json -q | parse.php
```

And then, since I'm a PHP guy, we'll have that parse.php, be, 
you know php, that parses it, but my proof of concept looks
like this (thanks 
http://stackoverflow.com/a/11968298):

```php
#!/usr/bin/php
<?php

while($f = fgets(STDIN)){
        parseJson($f);
}

function parseJson($jsonLine){
        print_r(json_decode($jsonLine));
} 
```

And while I thought it'd be hot to use influx db and grafana 
like all the cools kids (see http://giatro.me/2015/09/30/install-influxdb-and-grafana-on-raspberry-pi.html) 
that's really hard.  I'll KISS and either
use an existing LAMP set up or do LAMP on the pi like so:

```
sudo apt-get install apache2 php5 php5-mysql mysql-server
```

And then some nice charts via something like http://canvasjs.com/html5-javascript-dynamic-chart/

## Conclusion

Not a lot of stuff here now - but sit tight - i'll try and 
impress you!