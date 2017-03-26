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
the IDs of the $13 sensors change every time the sensor batteries die/are changed. As well, the Pi WiFi 
seems to interfere with the USB SDR.  I'm inspiring confidence, yeah?! 

## Install steps

1. Gather all the hardware above and get your Pi 
[installed and booting](https://www.raspberrypi.org/documentation/installation/installing-images/README.md),
 [online](https://www.raspberrypi.org/documentation/configuration/wireless/README.md) 
 and [accessible via SSH](https://www.raspberrypi.org/documentation/remote-access/ssh/README.md). 
 I recommend using a normal monitor for the install instead of the 5". It's easier this way. After it's 
 installed, update your Pi to be current;
    ```
    sudo apt-get update&& sudo apt-get upgrade
    ```
1. In order to get your 5" screen working, if you opted to go that route, edit ``sudo vim /boot/config.txt`` 
(feel free to use ``pico`` or what not instead of ``vim``) and add these lines at the end:
   ```
   hdmi_group=2
   hdmi_mode=1
   hdmi_mode=87
   hdmi_cvt 800 480 60 6 0 0 0
   dtparam=spi=on
   dtparam=i2c_arm=on
   
   dtoverlay=ads7846,cs=1,penirq=25,penirq_pull=2,speed=50000,keep_vref_on=0,swapxy=0,pmax=255,xohms=150,xmin=200,xmax=3900,ymin=200,ymax=3900
   
   dtoverlay=w1-gpio-pullup,gpiopin=4,extpullup=1
   ```
   After a reboot, the screen should work. 
   Thanks [random Amazon comment](https://www.amazon.com/gp/customer-reviews/R3QVPHGJAQIYGW/ref=cm_cr_dp_d_rvw_ttl?ie=UTF8&ASIN=B013JECYF2)!
   I never got touch calibrated correctly, but feel free follow their instructions with ``xinput-calibrator``.
1. Install git, apache, php, compile utils for rtl, chrome and chrome utils for doing 
full screen(some of which may be installed already):

   ```
   sudo apt-get install -y curl git mercurial make binutils bison gcc build-essential chromium-browser ttf-mscorefonts-installer unclutter x11-xserver-utils apache2 php5
   ```
1. Download, compile and install [rtl_433](https://github.com/merbanan/rtl_433)
1. With your wireless temp sensor(s) powered up and the USB Dongle attached, make sure your 
sensors are read with rtl_433. Let it run for a while an note the IDs returned for the later step
of creating your own config file.  Here we see ID 153:
   ```
   pi@raspberrypi:~ $ rtl_433 -q
   
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
1. Edit ``/home/pi/.config/lxsession/LXDE-pi/autostart`` to auto start Chromium in incognito and kiosk mode
on the Pi's web server.  Thanks
to [blog.gordonturner.com](https://blog.gordonturner.com/2016/12/29/raspberry-pi-full-screen-browser-raspbian-november-2016/)
and [superuser.com](https://superuser.com/questions/461035/disable-google-chrome-session-restore-functionality#618972)
 pages for the howto:
    ```
    @lxpanel --profile LXDE-pi
    @pcmanfm --desktop --profile LXDE-pi
    #@xscreensaver -no-splash
    @point-rpi
    @/usr/bin/chromium-browser --kiosk --incognito --start-maximized http://127.0.0.1
    @unclutter
    @xset s off
    @xset s noblank
    @xset -dpms
     ```
     Upon reboot you should see the default apache page, full screen, with no menu bar at the top.
1. As root, remove the default ``index.html``, clone this repo into ``/var/www/html`` and create your own 
``config.php``:
   ```
   sudo su - 
   cd /var/html/
   rm www/index.html
   git clone https://github.com/Ths2-9Y-LqJt6/YANPIWS.git html
   cd html
   cp config.dist.php config.php
   ```
1. Edit your newly created ``config.php`` to have the correct values. 
Specifically, your latitude (``lat``),
longitude (``lon``), time zone (``gmt_offset``) and labels which you 
got in the step above running ``rtl_433 -q``. As well, you'll need to sign up for an API key
on [Dark Sky](https://darksky.net/dev/register). Be sure to keep the other lines, specifically
the line that declares the ``$YANPIWS`` variable a global, untouched.  Here's a sample:
    ```
    $YANPIWS['lat'] = 31.775554;
    $YANPIWS['lon'] = -81.822436;
    $YANPIWS['dataPath'] = '/var/www/html/data/';
    $YANPIWS['gmt_offset'] = '-8';
    $YANPIWS['darksky'] = '3824vcu89v89f7das878f7a8sd';
    $YANPIWS['labels'] = array(
        '211' => 'In',
        '109' => 'Out',
    );
    ```
1. Reboot your Pi so the browser starts loading the configured YANPIWS app.
1. start your data collection in the background and ensure you start seeing 'It's 74.3 at ID ...":
    ```
    sudo su -
    cd /var/www/html
    rtl_433 -f 433820000 -C customary -F json -q | php -f parse_and_save.php&
    It's 74.3 at ID 153 - data written to ./data/2017-03-25
    ```
    This step will need some improvement as the ``rtl_433`` process can die and your temps will stop
    being updated :( Stay tuned!

Whew that's it!  Enjoy your new weather station. Let me know which awesome case you
 build for it and report any bugs here!
 
 
![](./product.jpg)


## Version History
* 0.7 - Mar 25, 2017 - Add Install Steps, tweak sun icon, full path in config, 
better handle empty config file
* 0.6 - Mar 25, 2017 - horizontal layout, moon and sun icons instead of text, bigger forecast icons
* 0.5 - Mar 24, 2017 - simplified layout, improve readme, better error handling of missing config.php
* 0.4 - Mar 24, 2017 - cache darksky API calls, implement layout for 800x480 screen
* 0.3 - Mar 23, 2017 - forecast if you have a darksky API
* 0.2 - Mar 23, 2017 - reading CSV, super basic HTML output
* 0.1 - Mar 22, 2017 - parsing data, writing to CSV, crude readme, no html output