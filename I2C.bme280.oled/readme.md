# I2C Bus: BME280 sensor & 0.96" OLED Display

For the folks who want to support super tiny displays, this directory has
info how to show up to two temperatures and humidity from your YANPIWS install like this using 
`remote_temps_humid.py`:

![](./remote.temps.jpg)

As well, you can show real time temperature if you have both a 
display and a sensor hooked up. This will up date every 100ms. No YANPIWS 
web server install needed. See `live_temp_hum_bme280.py`:

![](./realtime.temps.jpg)

Thanks to [raspberrypi-spy](https://www.raspberrypi-spy.co.uk/) for much of this script. 
Thanks to [code electron](http://codelectron.com/how-to-setup-oled-display-with-orange-pi-zero-python-ssd1306/)
 for images and tecno info!

Finally, if you want to show a more complete set of data, you can use the `remote_all.py` script
to show something like this:

![](./remote.all.jpeg)


# Requirements

## Hardware 
These python scripts assume you're using I2C compatible hardware.  For my development, I used 
(prices as of Oct 2019):

* $13 - [BME280 Digital 5V Temperature Humidity Sensor](https://amzn.to/2ZL42yZ)
* $5.80 - [Breadboard Jumper Wires](https://amzn.to/2Lesc15)
* $8.99 - [0.96" I2C 128X64 OLED with SSD1306 driver](https://amzn.to/2ImlX9t)

## Software

* Python3
* I2C bus enabled (eg `raspi-config` or `armbian-config`)
* Adafruit-GPIO Drivers for OLED installed

# Quick start

1. You should already have you screen set up and working.  You should know the I2C bus, 
either `1` or `0`, after running `i2cdetect -l`. As well, you should know the two 
device IDs after running `i2cdetect 0` or `i2cdetect 1`. These are likley `76` for temp sensor & `3c` for display.

1. The `remote_temps_humid.py` script takes the following arguments:
    ```angular2
    usage: remote_temps_humid.py [--bus] [--remote_ip]
                                 [--temp_id1] [--temp_id2]

    ```
   It defaults to `--bus/-b` of `0` and the `--remote_ip/-ip` should be where your main
   YANPIWS install is.  The `--temp_id1/-id1` and `--temp_id1/-id2` are from 
   IDs of the temps in your `config.csv`. A complete call might look like this:

    ```python
    python3 remote_temps_humid.py -b 0 -ip 10.0.40.219 -id1 96 -id2 97
    ```
1. `live_temp_hum_bme280.py` takes the following arguments:

    ```
    usage: live_temp_hum_bme280.py [-h] [--temp_bus TEMP_BUS]
                                   [--display_bus DISPLAY_BUS] [--device DEVICE]
    ```
   
   It defaults to `--bus/-b` of `1` and `--device/-d` of `0x76`.  Likely if you're on a Pi, you 
   won't need to change anything, so you can just call it with out any arguments.
   
 1. `remote_all.py` takes teh following arguments:
    ```$xslt
    usage: remote_all.py [-h] [--bus BUS] [--remote_ip REMOTE_IP]
                         [--temp_id1 TEMP_ID1] [--temp_id2 TEMP_ID2]
     ```
    
    It defaults to `--bus/-b` of `0` and you can pass in one or two remote IDs to use. Note that
    if you have long labels, you'll likely push the sunrise and sunset times off the screen.
 
 1. For ensure this runs at boot runs every minute, add **one** of these to your crontab, but be
 sure to update the variables and paths accordingly!
 
    ```cron
    * * * * * cd /var/www/html/I2C.bme280.oled; /usr/bin/python3 remote_temps_humid.py -b 0 -ip 10.0.40.219 -id1 96 -id2 97 
    * * * * * cd /var/www/html/I2C.bme280.oled; /usr/bin/python3 live_temp_hum_bme280.py 
    * * * * * /usr/bin/python3 /var/www/html/I2C.bme280.oled/remote_all.py -ip  192.168.68.105 -id1 73 -id2 231
    ``` 
 
If you need more help - read up on the "Long Start" below.
 
# Long Start
 
These are my notes for doing an install on an Orange Pi Zero. 
 
1. You'll need to wire your devices in parallel. I did it with a harness I made like this
with spliced, soldered and heat-shrink tubed [Breadboard Jumper Wires](https://amzn.to/2Lesc15): ![](./harness.jpeg)
1. Connect the harness to the correct I2C pins like this 
(Thanks to [code electron](http://codelectron.com/how-to-setup-oled-display-with-orange-pi-zero-python-ssd1306/)
 for image): ![](./schematics.png)
1. download "bionic" from [armbian](https://www.armbian.com/orange-pi-zero/)
1. write image to microsd card, put in device 
1. plugin in ethernet and boot up device
1. check your DHCP server for "orangepizero" IP
1. login first time with root/1234: `ssh root@192.168.68.104`
1. change root password to something good
1. create your user, use good password 
1. add your ssh key via `ssh-copy-id -i PATH_TO_KEY USER@IP` 
1. edit `/etc/ssh/sshd_conf` to `PasswordAuthentication no`. extra bonus `PermitRootLogin no` 
1. `systemctl restart sshd`
1. apt update;apt dist-upgrade -y
1. run `armbian-config` and do:
   * personal -> timezone -> change time zone to be correct
   * system -> hardware -> check 3 i2c boxes, save
1. reboot
1. ssh back in and run:
`apt install build-essential python3-pip python3-setuptools python3-smbus autoconf libtool pkg-config  python3-pil python3-dev  i2c-tools`
1. run `pip3 install wheel`
1. run `sudo pip3 install --upgrade setuptools`
1. install adafruit drivers 
   1. `git clone https://github.com/adafruit/Adafruit_Blinka.git`
   1. `cd Adafruit_Blinka`
   1. `python3 setup.py install`
   1. `pip3 install Adafruit-GPIO`
   1. `pip3 install Adafruit_SSD1306`
   1. `pip3 install Adafruit_BBIO`
1. go home `cd`
1. make apache dir (even though we don't have apache): `mkdir -p /var/www`
1. check out YANPIWS there `git clone https://github.com/Ths2-9Y-LqJt6/YANPIWS.git /var/www/html`
1. make sure we can see the three I2C buses:
    ```bash
    i2cdetect -l
    i2c-1   i2c             mv64xxx_i2c adapter                     I2C adapter
    i2c-2   i2c             mv64xxx_i2c adapter                     I2C adapter
    i2c-0   i2c             mv64xxx_i2c adapter                     I2C adapter
    ```
1. After wiring up your devices, make sure they show up:
    ```bash
    i2cdetect -y 0
         0  1  2  3  4  5  6  7  8  9  a  b  c  d  e  f
    00:          -- -- -- -- -- -- -- -- -- -- -- -- -- 
    10: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
    20: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
    30: -- -- -- -- -- -- -- -- -- -- -- -- 3c -- -- -- 
    40: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
    50: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
    60: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
    70: -- -- -- -- -- -- 76 -- 
    ```
1. add crontab entries per above with the correct flags/paths
1. enjoy!
