#!/usr/bin/python3

from luma.core.interface.serial import i2c
from luma.core.render import canvas
from luma.oled.device import ssd1306
import argparse
import atexit


parser = argparse.ArgumentParser()

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
parser.add_argument('--bus', '-b', default=0, type=int, help='Bus Number, defaults to 0')

args = parser.parse_args()
bus_number = args.bus


if __name__ == "__main__":
    serial = i2c(port=bus_number, address=0x3C)

    device = ssd1306(serial)

    with canvas(device) as draw:
        draw.rectangle(device.bounding_box, outline="black", fill="black")
