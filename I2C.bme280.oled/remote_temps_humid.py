#!/usr/bin/python


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

# 128x64 display with hardware I2C:
disp = Adafruit_SSD1306.SSD1306_128_64(rst=RST)

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

temp1url = 'http://192.168.68.105/ajax.php?content=temp&id=231'
temp2url = 'http://192.168.68.105/ajax.php?content=temp&id=63'
datetime = 'http://192.168.68.105/ajax.php?content=datetime'

# Load default font.
font = ImageFont.truetype("/usr/share/fonts/truetype/lato/Lato-Heavy.ttf", 26)
font_small = ImageFont.truetype("/usr/share/fonts/truetype/lato/Lato-Heavy.ttf", 13)
# Alternatively load a TTF font.  Make sure the .ttf font file is in the same directory as the python script!
# Some other nice fonts to try: http://www.dafont.com/bitmap.php
# font = ImageFont.truetype('Minecraftia.ttf', 8)
while True:

    # Draw a black filled box to clear the image.
    draw.rectangle((0,0,width,height), outline=0, fill=0)

    # fetch the cooked up html -> strings
    temp1 = get_cleaned_string_from_url(temp1url);
    temp2 = get_cleaned_string_from_url(temp2url);
    date_time = get_cleaned_string_from_url(datetime);

    # render the data
    draw.text((0, top ),     str(date_time) , font=font_small, fill=255)
    draw.text((0, top + 12),     str(temp1) , font=font, fill=255)
    draw.text((0, top + 40),     str(temp2) , font=font, fill=255)

    # Display image.
    disp.image(image)
    disp.display()
    time.sleep(.5)

    time.sleep(5)
