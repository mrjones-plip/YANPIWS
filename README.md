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

Here's the parts I used and prices at time of publishing (March 2017):

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
the IDs of the $13 sensors change every time the sensor batteries die/are changed. ~~As well, the Pi WiFi 
seems to interfere with the USB SDR (I'm inspiring confidence, yeah?!)~~ Turns out I just needed to move
the antenna further away from the Pi! Finally, I coded the HTML
and CSS to work in an 800x480 screen or greater. If you use the cheaper, 
lower resolution screen (480x320), YANPIWS just works thanks to ``@media`` sensing.  
As well, it works on mobile devices and desktop devices as well.  All be it, mobile 
works best in landscape.

If you want to use the BME/BMP 280 I2C chip instead, this is now supported! So instead of 
getting the SDR USB dongle and Wireless Temperature Sensora above, instead get 
(prices as of Sep 2019):

* $13 - [BME280 Digital 5V Temperature Humidity Sensor](https://amzn.to/2ZL42yZ)
* $5.80 - [Breadboard Jumper Wires](https://amzn.to/2Lesc15)

While this reduces costs, it also changes how the set up works.  You'll only be able to run one 
sensor on the I2C bus and you'll have to know how to solder.  Finally, check out the alternate
install steps below.

## Install steps

These steps assume you already have your Pi 
[installed and booting](https://www.raspberrypi.org/documentation/installation/installing-images/README.md),
 [online](https://www.raspberrypi.org/documentation/configuration/wireless/README.md) 
 and [accessible via SSH](https://www.raspberrypi.org/documentation/remote-access/ssh/README.md). 
 I recommend using a normal monitor for the install instead of the 5". It's easier this way. 
 
Speaking of monitors - this install also assumes you have your 5" display (or 3.5" if you 
went that way (or what ever display you want!)) already working. 
 
These steps also assume you're using the SDR dongle and wireless temp sensors.  See below for
BME280 sensors.
 
All steps are done as the *Pi User* - be sure you've changed this user's password
from "raspberry" ;)

1. Ensure your Pi is current;
    ```
    sudo apt-get update&& sudo apt-get upgrade
    ```
1. Install git, apache, php, compile utils for rtl, chrome and chrome utils for doing 
full screen(some of which may be installed already):

   ```
   sudo apt-get install -y curl git mercurial make binutils bison gcc build-essential chromium-browser ttf-mscorefonts-installer unclutter x11-xserver-utils apache2 php php-curl
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
1. Remove the default ``index.html``, clone this repo into ``/var/www/html`` and create your own 
``config.php``:
   ```
   cd /var/html/
   sudo rm www/index.html
   sudo git clone https://github.com/Ths2-9Y-LqJt6/YANPIWS.git html
   cd html
   chown -R pi .
   chgrp -R www-data .
   cp config.dist.php config.php
   ```
1. Edit your newly created ``config.php`` to have the correct values. 
Specifically, your latitude (``lat``),
longitude (``lon``) and labels which you 
got in the step above running ``rtl_433 -q``. As well, you'll need to sign up for an API key
on [Dark Sky](https://darksky.net/dev/register). Be sure to keep the other lines, specifically
the line that declares the ``$YANPIWS`` variable a global, untouched.  If you want static icons instead of
animated ones, set 'animate' to ``false`` instead of ``true`` like below. Here's a sample:
    ```
    $YANPIWS['lat'] = 31.775554;
    $YANPIWS['lon'] = -81.822436;
    $YANPIWS['animate'] = true;
    $YANPIWS['dataPath'] = '/var/www/html/data/';
    $YANPIWS['darksky'] = '3824vcu89v89f7das878f7a8sd';
    $YANPIWS['labels'] = array(
        '211' => 'In',
        '109' => 'Out',
    );
    ```
1. Reboot your Pi so the browser starts loading the configured YANPIWS app.
1. [Add a cronjob](https://www.raspberrypi.org/documentation/linux/usage/cron.md) 
for the Pi user to run every 5 minutes to ensure temperature collection is happening:
    ```
    */5 * * * * /var/www/html/start.sh >> /var/www/html/data/cron.log

    ```
    This step will need some improvement as the ``rtl_433`` process can die and your temps will stop
    being updated :( Stay tuned!

Whew that's it!  Enjoy your new weather station. Let me know which awesome case you
 build for it and report any bugs here!
 
 
![](./product.jpg)

### Alternate install steps for attached I2C sensor

Follow the exact same steps as above, but don't do the last step with the cron job.  Instead,
you'll need to:

1. Make sure I2C is enabled by running `sudo raspi-config` -> "Interfacing Options" -> I2C -> "Yes" -> Reboot
1. Ensure that your BME280 sensor is attached correctly. 
 [Raspberry Pi Spy](https://www.raspberrypi-spy.co.uk/2016/07/using-bme280-i2c-temperature-pressure-sensor-in-python/) 
provided [this great schematic](./BME280-Module-Setup.png).
1. Assuming you installed in `/var/www/html`, run `python /var/www/html/bme280.py` and 
ensure you see good data.  This looks like this for me:
    ```bash
    {"time" : "2019-09-05 14:05:00", "model" : "BMP280", "id" : 96, "temperature_F" : 80.456, "humidity" : 40.54}
    ```
1. If that all looks good, as the `pi` user, set up a cron job to run once a minute and generate the stats:
    ```bash
    */1 * * * * /usr/bin/python /var/www/html/bme280.py| /usr/bin/php -f /var/www/html/read_and_post.php
    ``` 
   
### Multiple Sensor Nodes

If you have a lot of places you want to run temperature sensors, as of version 0.9.2, YANPIWS now
supports a server/client deployment. This means you have the ability to run an install that does 
nothing but send it's data to another YANPIWS instance.
To set that up, go ahead and deploy the 2nd (Nth!!) instance based on the steps above.  Make sure everything
is working.  Then, follow these steps:

1. Get the IP address of where you want to send the data to.  See `ifconfig` if you need help (this
command may work, but is likely fragile, `ifconfig|grep -i inet|grep broadc|cut -d ' ' -f10`). We'll
pretend you got the ip `192.168.4.199` back. But use the real IP!
1. One the remote node, edit the `config.php` file and in the bottom section after the `$YANPIWS['servers'][]`
line, do one of the following:
   * change the URL line `url` to be: `'url' => 'http://192.168.4.199',`. Remember, this should be the IP you 
   got in the 
   prior step. This will cause the data to not be stored locally on the node at all.  It will only
   be sent to the remote server. The final result should look like this:
   
       ```php
        $YANPIWS['servers'][] = array(
           'url' => 'http://192.168.4.199',  // no trailing slash please ;)
           'password' => 'boxcar-spinning-problem-rockslide-scored', // should match password above
        );
     
   OR
   * add 4 new lines with your new IP. The final result  will look like this:
   
       ```php
        $YANPIWS['servers'][] = array(
            'url' => 'http://127.0.0.1',  // no trailing slash please ;)
            'password' => 'boxcar-spinning-problem-rockslide-scored', // should match password above
        );
        $YANPIWS['servers'][] = array(
           'url' => 'http://192.168.4.199',  // no trailing slash please ;)
           'password' => 'boxcar-spinning-problem-rockslide-scored', // should match password above
        );
        ``` 
     
     This will cause the client to write to BOTH the local and remote YANPIWS instances
1. If your using the BME280 chip on both your server and your client(s), you'll need to edit the `bme280.py`.
This is because all of the BME280 chips have the same ID and your server won't know which sensor is which.
To fix this, change line 165 from this:

    ```python
    json = '{"time" : "' + str(rightnow) + '", "model" : "BMP280", "id" : ' + str(chip_id) + ', "temperature_F" 
    ```   
        
     To this:

    ```python
    json = '{"time" : "' + str(rightnow) + '", "model" : "BMP280", "id" : "44", "temperature_F" 
    ```  
        
     What this does is hard code this sensor to ID `44`.  For each new node you deploy, you'll need to change this 
     to it's own unique value.
1. On your server update the `$YANPIWS['labels']` to have an entry for your new sensor.  In the case of our
`44` example above, along with default `96` that the BME280 uses, that would look like this:

    ```php
    $YANPIWS['labels'] = array(
        '96' => 'In',
        '44' => 'Out',
    );
    ```

### API calls

YANPIWS, as of version 0.9.2, now has an HTTP API that you can use to send data.  This means
that anything that can do a `POST` to the IP of your YANPIWS server instance, can send it temp data!
The following fields are required:
* `id` - (int) the ID you want to write to the DB
* `time` - (string) the time of of the data, must be in Y-M-D H:M:S format like `2019-09-18 23:59:02`
* `temperature_F` - (float) of the temperature in ferinheight 
* `password` - (string) must match what you have in your `config.php` file under `api_password`. 
The default value is `boxcar-spinning-problem-rockslide-scored`.

Optionally you may pass:
* `humidity` - (float) of the humidity

Assuming you installed in the default path of `/var/www/html`, you should use the following URL 
for your `POST`, replace `IP_ADDRESS` with your real IP address of your server:

* `http://IP_ADDRESS/parse_and_save.php`

## Development

Check out this repo, ``cd`` into and start a web server:

```
sudo php -S  localhost:8000
```

The rtl_433 works great on Ubuntu for desktop/laptop development.  Manually kick off the input 
script and leave it running while you code to gather live temps:

```
rtl_433 -f 433820000 -C customary -F json -q | php -f read_and_post.php
```

If you don't want to deal with running the rtl-433 script, copy the sample data 
``example.data`` to today's date (YEAR-MONTH-DAY) into the ``data`` directory.  It has IDs 211 
and 109 which are the ones already in config.dist.php.

As well, if you want to simulate individual inputs via the HTTP POSTs, you can use this `curl` command.
Note that we're using the default password, you may need to change this if you've changed it in your 
deployment:

```bash
curl --data "password=boxcar-spinning-problem-rockslide-scored&temperature_F=44.08&id=2&time=2019-09-18 23:59:02" http://localhost:8000/parse_and_save.php
```

As the `bme280.py` script outputs JSON, if you want to more closely similate the cron job that's run, incluidng using
the config vars and a real `POST`, you can use this:

```bash
echo '{"temperature_F":"44.08","id":"2","time":"2019-09-18 23:59:02"}' | php read_and_post.php
```

Conversely, if you want to use the now deprecated `STDIN` method, you can use `echo` to pipe in JSON: 

```bash
echo '{"temperature_F":"44.08","id":"2","time":"2019-09-18 23:59:02"}' | php parse_and_save.php
```

Use your IDE of choice to edit and point your browser at ``localhost:8000`` 
(or the IP of your Pi) and away you go.

PRs and Issues welcome!

## Version History
* 0.9.3 - Oct 1, 2019 - merge old PR #20 with:
  * add stats dashboard per #17
  * adds AJAX reloads per #19
  * add windspeed per #16
  * remove GMT from config per #23
  * offer animated or static icons per #25
  * Fix labels per #24
  * natively support both 5" and 3.5" screens per #13
  * all functions documented and commented per #32
  * make title text yellow if caches are older than 10 minutes per #37
  * don't cache invalid dark sky data #36
* 0.9.2 - Sep 19, 2019 - add support for, and default, to http POST for 
data gathering. Fix typo & fix minor bug with use of `rand()`. Update docs for same. 
* 0.9.1 - Sep 9, 2019 - implement support for BME280 I2C sensors
* 0.9 - Mar 26, 2017 - get feedback from [@jamcole](https://github.com/jamcole) (thanks!), 
add developer section, add getConfigOrDie(), 
simplify index.php, add better logging for debugging
* 0.8 - Mar 26, 2017 - Use cron to ensure temperature collection happens, omg - pgrep where have you been
all my life?!
* 0.7 - Mar 25, 2017 - Add Install Steps, tweak sun icon, full path in config, 
better handle empty config file
* 0.6 - Mar 25, 2017 - horizontal layout, moon and sun icons instead of text, bigger forecast icons
* 0.5 - Mar 24, 2017 - simplified layout, improve readme, better error handling of missing config.php
* 0.4 - Mar 24, 2017 - cache darksky API calls, implement layout for 800x480 screen
* 0.3 - Mar 23, 2017 - forecast if you have a darksky API
* 0.2 - Mar 23, 2017 - reading CSV, super basic HTML output
* 0.1 - Mar 22, 2017 - parsing data, writing to CSV, crude readme, no html output
