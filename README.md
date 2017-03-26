# YANPIWS
Yet Another Pi Weather Station (YANPIWS) - My explorations in getting a Rasberry Pi 
showing local time and weather:

![](./YANPIWS.gif)

## Overview

For a while I've had a [wireless weather station](http://amzn.to/2nAxo3L).  And for as long as I've 
had it, it's never quite worked right.  Certainly the part about 
"Equipped with an atomic clock, time is set automatically via radio" has *never* worked.  As 
well, maybe the sunset/sunrise times were accurate once or twice.  The only thing 
that worked pretty well was the indoor and outdoor temperatures.  I stumbled upon the
[rtl_433](https://github.com/merbanan/rtl_433) project and I was super stoked
to DIY a weather station.  

Goals for this project are:

* Use cheap hardware (e.g. Rasberry Pi)
* Show live weather from local, wireless sensors
* Show time
* Show today's sunset/sunrise times
* Show weather forecast from [Dark Sky API](https://darksky.net/dev/)

## Hardware

Here's the parts I used and prices at time of publishing:

* $20 - [433 MHz SDR USB dongle](http://amzn.to/2nc5MhX)
* $13 - [Wireless Temperature sensor](http://amzn.to/2lVdhJ6)
* $40 - [5" 800x480 screen](http://amzn.to/2mRjWYT)
* $43 - [Rasberry Pi 3 Model B and Power Adapter](http://amzn.to/2nklto3)
* $11 - [8GB Micro SD card](http://amzn.to/2nRE9Pt)

Total for this is $127, so, erm, not that cheap.  Ideally you'd have a lot of 
parts around you could re-use for this project. As well, you could reduce the price by going with a 
3.5" screen ([only $17](http://amzn.to/2mCIxlg)) and Pi B+ ([only $30](http://amzn.to/2n5nioJ))
. For the B+ you'd have to use ethernet or bring your own USB WiFi 
adapter.  I knew I'd have use for a 5" HDMI monitor, so I was happy to pay the premium.

Caveat Emptor - You'd probably be better off spending even more (ok, not so cheap here any more ;)
on a less janky wireless setup like [z-wave](http://amzn.to/2n3RFLn). I found out the hard way that
the IDs of the $13 sensors change every time your batteries die/are changed. As well, the Pi WiFi 
seems to interfere with the USB SDR.  I'm inspiring confidence, yeah?! 

## Install steps

These steps assume some competency in building computers and using the command line and such. As
much as possible I'll try and link to detailed install guides:

1. Gather all the hardware above 
1. Get your Pi 
[installed and booting](https://www.raspberrypi.org/documentation/installation/installing-images/README.md). 
1. Get your [Pi online](https://www.raspberrypi.org/documentation/configuration/wireless/README.md)
1. Ensure you can [SSH to your Pi](https://www.raspberrypi.org/documentation/remote-access/ssh/README.md)
1. Update your Pi to be current;
   ```
   sudo apt-get update&& sudo apt-get upgrade
   ```
1. Install git, apache, php, compile utils for rtl, chrome and chrome utils for doing 
full screen(some of which may be installed already):

   ```
   sudo apt-get install -y curl git mercurial make binutils bison gcc build-essential chromium-browser ttf-mscorefonts-installer unclutter x11-xserver-utils apache2 php5
   ```
1. Download, compile and install [rtl_433](https://github.com/merbanan/rtl_433)
1. With your wireless sensor(s) powered up and the USB Dongle attached, make sure your 
sensors are read with rtl_433:
   ```
   root@raspberrypi:/var/www/html# rtl_433 -q
   
   Found Rafael Micro R820T tuner
   Exact sample rate is: 250000.000414 Hz
   Sample rate set to 250000.
   Bit detection level set to 0 (Auto).
   Tuner gain set to Auto.
   Tuned to 433920000 Hz.
   2017-03-25 17:07:21 :   Fine Offset Electronics, WH2 Temperature/Humidity sensor
           ID:      153
           Temperature:     25.5 C
           Humidity:        36 %
   ```
1. As root, clone this repo into /var/www/html:
   ```
   sudo su - 
   cd /var/html/www
   git clone https://github.com/merbanan/rtl_433.git
   ```

## Version History
* 0.6 - Mar 25, 2017 - horizontal layout, moon and sun icons instead of text, bigger forecast icons
* 0.5 - Mar 24, 2017 - simplified layout, improve readme, better error handling of missing config.php
* 0.4 - Mar 24, 2017 - cache darksky API calls, implement layout for 800x480 screen
* 0.3 - Mar 23, 2017 - forecast if you have a darksky API
* 0.2 - Mar 23, 2017 - reading CSV, super basic HTML output
* 0.1 - Mar 22, 2017 - parsing data, writing to CSV, crude readme, no html output