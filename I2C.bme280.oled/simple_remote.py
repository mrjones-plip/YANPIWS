#!/usr/bin/python3
from json.decoder import JSONArray

from luma.core.interface.serial import i2c
from luma.core.render import canvas
from luma.oled.device import ssd1306
from PIL import ImageFont
import argparse
import json
import logging.handlers
import smbus2
import bme280
import os
import time
from dateutil import tz
import requests
from datetime import datetime


def post_to_yanpiws():
    data = bme280.sample(bus, address, calibration_params)
    temperature_celsius = data.temperature
    temperature_fahrenheit = round((temperature_celsius * 9 / 5) + 32, 2)
    url = f'http://{str(yanpiws_ip)}/parse_and_save.php'
    data = {
        "model" : "BMP280",
        "time" : datetime.now(tz=tz.tzlocal()).strftime("%Y-%m-%d %H:%M:%S"),
        "id" : 71, # E bedroom, todo - not hardcode
        "temperature_F" : temperature_fahrenheit,
        "password" : "boxcar-spinning-problem-rockslide-scored"  # default pass, todo - not hardcode
    }
    global last_seen_temp
    last_seen_temp = temperature_fahrenheit
    requests.post(url, data=data)


def get_string_from_url(url):
    raw_html = requests.get(url).content
    return raw_html


def get_remote_humid_and_temp(id):
    forecast_url = f'{yanpiws_ajax_url}humidity&id={str(id)}'
    return json.loads(get_string_from_url(forecast_url))


def get_remote_forecast():
    forecast_url = f'{yanpiws_ajax_url}forecast_full_json'
    return json.loads(get_string_from_url(forecast_url))


def get_remote_sun():
    data = json.loads(get_string_from_url(f'{yanpiws_ajax_url}sunrise'))
    data.update(json.loads(get_string_from_url(f'{yanpiws_ajax_url}sunset')))
    return f'☀ ↑{str(data["sunrise"])} ↓{str(data["sunset"])}'


def get_remote_moon():
    data = json.loads(get_string_from_url(f'{yanpiws_ajax_url}moonset'))
    data.update(json.loads(get_string_from_url(f'{yanpiws_ajax_url}moonrise')))
    result = '○'
    if data['moonrise']:
        result += f' ↑{data["moonrise"]}'
    if data['moonset']:
        result += f' ↓{data["moonset"]}'
    return result


def show_info(wait):

    # fetch the cooked up json -> strings
    forecast = {}
    humid_and_temp1 = {}
    moon_all = ''
    second_line = ''
    first_line2 = ''
    third_line = ''
    try:
        no_error = True
        humid_and_temp1 = get_remote_humid_and_temp(yanpiws_temp_1)
        moon_all = get_remote_moon()
        forecast = get_remote_forecast()
        first_line = get_remote_sun()
    except Exception as e:
        first_line = 'Error - check logs :( '
        no_error = False
        my_logger.debug(f'Weathercaster error on boot: {str(e)}')

    if no_error and humid_and_temp1[0]['temp'] != 'NA':
        second_line = str(int(float(humid_and_temp1[0]['temp']))) + '°' + humid_and_temp1[0]['label']

    if no_error and moon_all:
        first_line2 = moon_all

    if no_error and forecast[0]:
        third_line = str(int(float(forecast[0]['temperatureMax']))) + '°' + forecast[0]['icon']

    full_path = os.path.dirname(os.path.abspath(__file__)) + "/"
    font2 = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 13)
    font1 = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 19)

    with canvas(device) as draw:
        draw.text((0, 4), first_line, font=font2, fill="white")
        draw.text((0, 16), first_line2, font=font2, fill="white")
        draw.text((0, 30), second_line, font=font1, fill="white")
        draw.text((0, 46), third_line, font=font1, fill="white")

    my_logger.debug(f"Weathercaster: simple Updated screen, posted {last_seen_temp} temp. Waiting {wait} seconds to update again.")


def full_stack():
    import traceback, sys
    exc = sys.exc_info()[0]
    stack = traceback.extract_stack()[:-1]  # last one would be full_stack()
    if exc is not None:  # i.e. an exception is present
        del stack[-1]  # remove call of full_stack, the printed exception
        # will contain the caught exception caller instead
    trc = 'Traceback (most recent call last):\n'
    stackstr = trc + ''.join(traceback.format_list(stack))
    if exc is not None:
        stackstr += '  ' + traceback.format_exc().lstrip(trc)
    return stackstr


def main(device):
    while True:
        wait = 120
        post_to_yanpiws()
        show_info(wait)
        time.sleep(wait)


if __name__ == "__main__":
    try:
        parser = argparse.ArgumentParser()

        # Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
        # Rev 1 Pi uses bus 0
        # Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
        parser.add_argument('--bus', '-b', default=0, type=int, help='Bus Number, defaults to 0')

        # IP address of your YANPIWS device you want to show data from
        parser.add_argument('--remote_ip', '-ip', default='192.168.68.105', type=str,
                            help='Temp sensor ID, defaults to 0x76')

        # ID from your YANPIWS config.csv of temp 1
        parser.add_argument('--temp_id1', '-id1', default='143', type=int, help='remote temp ID #1, defaults to 143')

        args = parser.parse_args()

        # Build URL and set some vars
        yanpiws_ip = args.remote_ip
        yanpiws_temp_1 = args.temp_id1
        yanpiws_ajax_url = 'http://' + str(yanpiws_ip) + '/ajax.php?content='
        last_seen_temp = 0.0

        # set up syslog logging
        my_logger = logging.getLogger('MyLogger')
        my_logger.setLevel(logging.DEBUG)
        handler = logging.handlers.SysLogHandler(address='/dev/log')
        my_logger.addHandler(handler)

        # BME280 sensor address (default address), Initialize I2C bus and calibration
        address = 0x76  # can also be 0x77 - check  i2cdetect -y 1 or i2cdetect -y 0
        bus = smbus2.SMBus(1)  # can also be 0, depends on where you found device on per i2cdetect
        calibration_params = bme280.load_calibration_params(bus, address)

        my_logger.debug('Weathercaster: simple Starting ')
        serial = i2c(port=args.bus, address=0x3C)
        device = ssd1306(serial)
        main(device)


    except KeyboardInterrupt:
        my_logger.debug("Weathercaster: simple Stopping(Ctrl + C) ")
        pass


    finally:
        my_logger.debug("Weathercaster simple exit trace: " + full_stack())
