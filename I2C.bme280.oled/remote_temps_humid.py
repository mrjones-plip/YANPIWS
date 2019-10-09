#!/usr/bin/python

######################################################
# Change these for your environment
######################################################

# IP address of your YANPIWS device you want to show data from
yanpiws_ip = '192.168.68.105'
# ID from your YANPIWS config.csv of temp 1
yanpiws_temp_1 = '231'
# ID from your YANPIWS config.csv of temp 2
yanpiws_temp_2 = '63'

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
bus_number = 0;

######################################################
# don't change anything below here!
######################################################




import smbus
import time
from ctypes import c_short
from ctypes import c_byte
from ctypes import c_ubyte
import json

import time
from random import *
import Adafruit_GPIO.SPI as SPI
import Adafruit_SSD1306
from PIL import Image
from PIL import ImageDraw
from PIL import ImageFont
import random
import subprocess

def remove_html_tags(text):
    """Remove html tags from a string"""
    import re
    clean = re.compile('<.*?>')
    return re.sub(clean, '', text)

def get_cleaned_string_from_url(url):
    import urllib.request
    raw_html = urllib.request.urlopen(url).read().decode('utf-8').rstrip()
    return remove_html_tags(raw_html)

# Raspberry Pi pin configuration:
RST = None     # on the PiOLED this pin isnt used
# Note the following are only used with SPI:
DC = 23
SPI_PORT = 0
SPI_DEVICE = 0

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
disp = Adafruit_SSD1306.SSD1306_128_64(rst=RST, i2c_bus=bus_number)

# Initialize library.
disp.begin()

# Clear display.
disp.clear()
disp.display()

# Create blank image for drawing.
# Make sure to create image with mode '1' for 1-bit color.
width = disp.width
height = disp.height
image = Image.new('1', (width, height))

# Get drawing object to draw on image.
draw = ImageDraw.Draw(image)

# Draw a black filled box to clear the image.
draw.rectangle((-20,-20,width,height), outline=0, fill=0)

# Draw some shapes.
# First define some constants to allow easy resizing of shapes.
padding = -2
top = padding
bottom = height-padding

temp1url = 'http://' + yanpiws_ip + '/ajax.php?content=temp&id=' + yanpiws_temp_1
temp2url = 'http://' + yanpiws_ip + '/ajax.php?content=temp&id=' + yanpiws_temp_2
humid1url = 'http://' + yanpiws_ip + '/ajax.php?content=humidity&id=' + yanpiws_temp_1
humid2url = 'http://' + yanpiws_ip + '/ajax.php?content=humidity&id=' + yanpiws_temp_2
datetime = 'http://' + yanpiws_ip + '/ajax.php?content=datetime'

# Load default font.
font = ImageFont.truetype("Lato-Heavy.ttf", 20)
font_small = ImageFont.truetype("Lato-Heavy.ttf", 12)
# Alternatively load a TTF font.  Make sure the .ttf font file is in the same directory as the python script!
# Some other nice fonts to try: http://www.dafont.com/bitmap.php
# font = ImageFont.truetype('Minecraftia.ttf', 8)
while True:

    # Draw a black filled box to clear the image.
    draw.rectangle((0,0,width,height), outline=0, fill=0)

    # fetch the cooked up html -> strings
    temp1 = get_cleaned_string_from_url(temp1url);
    temp2 = get_cleaned_string_from_url(temp2url);
    humid1 = get_cleaned_string_from_url(humid1url);
    humid2 = get_cleaned_string_from_url(humid2url);
    date_time = get_cleaned_string_from_url(datetime);

    # render the data
    draw.text((0, top ), date_time , font=font_small, fill=255)
    draw.text((0, top + 18), humid1 + ' ' +  temp1 , font=font, fill=255)
    draw.text((0, top + 46), humid2 + ' ' + temp2 , font=font, fill=255)

    # Display image.
    disp.image(image)
    disp.display()
    time.sleep(.5)

    time.sleep(5)