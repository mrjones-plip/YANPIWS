import glob
import time
import argparse
from datetime import datetime

base_dir = '/sys/bus/w1/devices/'
device_folder = glob.glob(base_dir + '28*')[0]
device_file = device_folder + '/w1_slave'

parser = argparse.ArgumentParser()
parser.add_argument('--id', '-i', default='96', type=int, help='ID to output, defaults to 96')

def read_temp_raw():
    f = open(device_file, 'r')
    lines = f.readlines()
    f.close()
    return lines

def read_temp():
    lines = read_temp_raw()
    while lines[0].strip()[-3:] != 'YES':
        time.sleep(0.2)
        lines = read_temp_raw()
    equals_pos = lines[1].find('t=')
    if equals_pos != -1:
        temp_string = lines[1][equals_pos+2:]
        temp_c = float(temp_string) / 1000.0
        temp_f = round((temp_c * 9.0 / 5.0 + 32.0),3)
        return  temp_f

def main():
  temperature_F = read_temp()
  rightnow = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
  json = '{"time" : "' + str(rightnow) + '", "model" : "DS18B20", "id" : ' + str(args.id) + ', "temperature_F" : ' + str(temperature_F) + '}'
  print(json)

if __name__=="__main__":
   main()