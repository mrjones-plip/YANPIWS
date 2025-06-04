#!/usr/bin/python3
from json.decoder import JSONArray

from luma.core.interface.serial import i2c
from luma.core.render import canvas
from luma.oled.device import ssd1306
from PIL import ImageFont
import argparse
import logging.handlers
import smbus2
import bme280
import pathlib
import time
from dateutil import tz
import requests
from datetime import datetime
import traceback, sys

WAIT = 120
ADDRESS = 0x76  # can also be 0x77 - check  i2cdetect -y 1 or i2cdetect -y 0

def post_to_yanpiws(yanpiws_ip, calibration_params, bus, local_id):
    data = bme280.sample(bus, ADDRESS, calibration_params)
    temperature_celsius = data.temperature
    temperature_fahrenheit = round((temperature_celsius * 9 / 5) + 32, 2)
    url = f'http://{str(yanpiws_ip)}/parse_and_save.php'
    data = {
        "model" : "BMP280",
        "time" : datetime.now(tz=tz.tzlocal()).strftime("%Y-%m-%d %H:%M:%S"),
        "id" : local_id,
        "temperature_F" : temperature_fahrenheit,
        "password" : "boxcar-spinning-problem-rockslide-scored"  # default pass, todo - not hardcode
    }
    global last_seen_temp
    last_seen_temp = temperature_fahrenheit
    requests.post(url, data=data)


def get_remote_humid_and_temp(yanpiws_ajax_url, temp_id):
    forecast_url = f'{yanpiws_ajax_url}humidity&id={str(temp_id)}'
    return requests.get(forecast_url).json()


def get_remote_forecast(yanpiws_ajax_url):
    forecast_url = f'{yanpiws_ajax_url}forecast_full_json'
    return requests.get(forecast_url).json()


def strip_am_pm(string):
    return str(string).replace(" AM","A").replace(" PM","P")


def get_remote_sun(yanpiws_ajax_url):
    data = requests.get(f'{yanpiws_ajax_url}sunrise').json()
    data.update(requests.get(f'{yanpiws_ajax_url}sunset').json())
    return f'☀ ↑{strip_am_pm(data["sunrise"])} ↓{strip_am_pm(data["sunset"])}'


def get_remote_moon(yanpiws_ajax_url):
    data = requests.get(f'{yanpiws_ajax_url}moonset').json()
    data.update(requests.get(f'{yanpiws_ajax_url}moonrise').json())
    result = '○'
    if data['moonrise']:
        result += f' ↑{strip_am_pm(data["moonrise"])}'
    if data['moonset']:
        result += f' ↓{strip_am_pm(data["moonset"])}'
    return result


def show_info(yanpiws_ajax_url, yanpiws_temp_1, device, local_id):

    # fetch the cooked up json -> strings
    forecast = {}
    humid_and_temp1 = {}
    moon_all = ''
    second_line = ''
    first_line2 = ''
    third_line = ''
    try:
        no_error = True
        humid_and_temp1 = get_remote_humid_and_temp(yanpiws_ajax_url, yanpiws_temp_1)
        moon_all = get_remote_moon(yanpiws_ajax_url)
        forecast = get_remote_forecast(yanpiws_ajax_url)
        first_line = get_remote_sun(yanpiws_ajax_url)
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

    font_file = f'{pathlib.Path(__file__).parent.resolve()}/Lato-Heavy.ttf'
    font2 = ImageFont.truetype(font_file, 13)
    font1 = ImageFont.truetype(font_file, 19)

    with canvas(device) as draw:
        draw.text((0, 4), first_line, font=font2, fill="white")
        draw.text((0, 16), first_line2, font=font2, fill="white")
        draw.text((0, 30), second_line, font=font1, fill="white")
        draw.text((0, 46), third_line, font=font1, fill="white")

    global last_seen_temp
    my_logger.debug(f"Weathercaster: simple Updated screen, posted {last_seen_temp} temp under ID {local_id}. Waiting {WAIT} seconds to update again.")


def full_stack():
    exc = sys.exc_info()[0]
    stack = traceback.extract_stack()[:-1]  # last one would be full_stack()
    if not exc:  # i.e. an exception is present
        del stack[-1]  # remove call of full_stack, the printed exception
        # will contain the caught exception caller instead
    trc = 'Traceback (most recent call last):\n'
    stackstr = trc + ''.join(traceback.format_list(stack))
    if exc is not None:
        stackstr += '  ' + traceback.format_exc().lstrip(trc)
    return stackstr


def main():
    while True:
        parser = argparse.ArgumentParser()

        # Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
        # Rev 1 Pi uses bus 0
        # Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
        parser.add_argument('--bus', '-b', default=0, type=int, help='Bus Number, defaults to 0')

        # IP address of your YANPIWS device you want to show data from
        parser.add_argument('--remote_ip', '-ip', default='192.168.68.105', type=str,
                            help=f'Temp sensor ID, defaults to {ADDRESS}')

        # ID from your YANPIWS config.csv of temp 1, will fetch this ID and show on screen
        parser.add_argument('--temp_id1', '-id1', default='143', type=int, help='ID from your YANPIWS config.csv of temp 1, will fetch this ID and show on screen, defaults to 143')

        # ID to upload your local temp with to YANPIWS server
        parser.add_argument('--temp_id2', '-id2', default='71', type=int, help='ID to upload your local temp with to YANPIWS server, defaults to 71')

        args = parser.parse_args()

        # Build URL and set some vars
        yanpiws_ip = args.remote_ip
        yanpiws_temp_1 = args.temp_id1
        yanpiws_temp_2 = args.temp_id2
        yanpiws_ajax_url = 'http://' + str(yanpiws_ip) + '/ajax.php?content='
        global last_seen_temp
        last_seen_temp = 0.0

        # BME280 sensor address (default address), Initialize I2C bus and calibration
        bus = smbus2.SMBus(1)  # can also be 0, depends on where you found device on per i2cdetect
        calibration_params = bme280.load_calibration_params(bus, ADDRESS)

        serial = i2c(port=args.bus, address=0x3C)
        device = ssd1306(serial)
        post_to_yanpiws(yanpiws_ip, calibration_params, bus, yanpiws_temp_2)
        show_info(yanpiws_ajax_url, yanpiws_temp_1, device, yanpiws_temp_2)
        time.sleep(WAIT)


if __name__ == "__main__":
    try:
        # set up syslog logging
        my_logger = logging.getLogger('MyLogger')
        my_logger.setLevel(logging.DEBUG)
        handler = logging.handlers.SysLogHandler(address='/dev/log')
        my_logger.addHandler(handler)
        my_logger.debug('Weathercaster: simple Starting ')

        main()

    except KeyboardInterrupt:
        my_logger.debug("Weathercaster: simple Stopping(Ctrl + C) ")

    except:
        my_logger.debug("Weathercaster simple exit trace: " + full_stack())
