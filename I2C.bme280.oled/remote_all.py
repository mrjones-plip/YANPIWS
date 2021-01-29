#!/usr/bin/python3

from luma.core.interface.serial import i2c, spi, pcf8574
from luma.core.render import canvas
from luma.oled.device import ssd1306
import argparse
import json
import time

parser = argparse.ArgumentParser()

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
parser.add_argument('--bus', '-b', default=0, type=int, help='Bus Number, defaults to 0')

# IP address of your YANPIWS device you want to show data from
parser.add_argument('--remote_ip', '-ip', default='192.168.68.105', type=str, help='Temp sensor ID, defaults to 0x76')

# ID from your YANPIWS config.csv of temp 1
parser.add_argument('--temp_id1', '-id1', default='231', type=int, help='remote temp ID #1, defaults to 231')

# ID from your YANPIWS config.csv of temp 2
parser.add_argument('--temp_id2', '-id2', type=int, help='remote temp ID #2, defaults to 63')

args = parser.parse_args()
yanpiws_ip = args.remote_ip
yanpiws_temp_1 = args.temp_id1
yanpiws_temp_2 = args.temp_id2
bus_number = args.bus


def get_string_from_url(url):
    import urllib.request
    raw_html = urllib.request.urlopen(url).read().decode('utf-8').rstrip()
    return raw_html


def get_forecast():
    forecastUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=forecast_full_json'
    return json.loads(get_string_from_url(forecastUrl))


def get_sunrise_sunset(string):
    url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=' + string
    data = json.loads(get_string_from_url(url))
    string = data[string].split(' ')[0]
    return string


def get_temp_string(temp_id):
    if temp_id != None:
        url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=humidity&id=' + str(temp_id)
        temp = json.loads(get_string_from_url(url))
        if temp[0]['temp'] != 'NA':
            the_string = ' ' + str(int(float(temp[0]['temp']))) + temp[0]['label']
        else:
            the_string = ''
    else:
        the_string = ''

    return the_string


def get_date_time():
    url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=datetime'
    return json.loads(get_string_from_url(url))


def showInfo(device):
    date_time = get_date_time(datetimeUrl)
    temp1final = get_temp_string(yanpiws_temp_1)
    temp2final = get_temp_string(yanpiws_temp_2)
    sunriseFinal = get_sunrise_sunset('sunrise')
    sunsetFinal = get_sunrise_sunset('sunset')
    forecast = get_forecast()

    with canvas(device) as draw:
        draw.rectangle(device.bounding_box, outline="white", fill="black")
        draw.text((0, 0), date_time['date'] + ' ' + date_time['time'], fill="white")
        draw.text((0, 17), temp1final + temp2final + ' ' + sunriseFinal + ' ' + sunsetFinal, fill="white")
        draw.text((0, 35), 'H: ' + str(int(forecast[0]['temperatureHigh'])) + ' L: ' + str(int(forecast[0]['temperatureLow'])) + ' ' + forecast[0]['icon'], fill="white")
        draw.text((0, 52), 'H: ' + str(int(forecast[1]['temperatureHigh'])) + ' L: ' + str(int(forecast[1]['temperatureLow'])) + ' ' + forecast[1]['icon'], fill="white")


def main():
    # rev.1 users set port=0
    # substitute spi(device=0, port=0) below if using that interface
    # substitute bitbang_6800(RS=7, E=8, PINS=[25,24,23,27]) below if using that interface
    serial = i2c(bus_number, address=0x3C)

    # substitute ssd1331(...) or sh1106(...) below if using that device
    device = ssd1306(serial)

    while True:
        showInfo(device)
        time.sleep(30)