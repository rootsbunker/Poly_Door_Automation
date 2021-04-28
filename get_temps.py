#!/usr/bin/env python3


# temp sensor:
# https://thepihut.com/blogs/raspberry-pi-tutorials/18095732-sensors-temperature-with-the-1-wire-interface-and-the-ds18b20

# temp is written to a fixed position in this file in thousandths of C
# grab the 5 digit number, convert to float and divide by 1000 rounded to 1 place
# gives us our working value a current_temp



# Poly Door Automation - Temperature controlled door activation using linear actuators
# Copyright (C) 2021  Nick Franklin
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.



import mysql.connector
import time
import itertools
import logging
import os

db_connection = mysql.connector.connect(
    host = "localhost",
    user = "[user here]",
    passwd = "[password here]",
    database = "poly"
    )

# make the connection to MySQL
db_cursor = db_connection.cursor()

# setup logging
get_logging = "SELECT value FROM string_vars WHERE name='logging'"
db_cursor.execute(get_logging)
result = db_cursor.fetchone()

if result[0] == 'on':
    logging.basicConfig(filename='/home/pi/poly_auto/get_temps.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.DEBUG)
else:
    logging.basicConfig(filename='/home/pi/poly_auto/get_temps.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.INFO)

logging.info('get_temps.py has started')
logging.info('Logging: %s', result[0])


# runtime vars:
sleep_time = 300 # time to pause between temp readings - 5 minutes give 30 minute averages

get_max_temp_override = "SELECT value FROM string_vars WHERE name='max_temp_override'"
get_max_temp_east = "SELECT value FROM string_vars WHERE name='max_temp_east'"
get_max_temp_west = "SELECT value FROM string_vars WHERE name='max_temp_west'"
get_min_temp_east = "SELECT value FROM string_vars WHERE name='min_temp_east'"
get_min_temp_west = "SELECT value FROM string_vars WHERE name='min_temp_west'"
east_man_override = "SELECT value FROM string_vars WHERE name='east_man_override'"
west_man_override = "SELECT value FROM string_vars WHERE name='west_man_override'"

db_cursor.execute(get_max_temp_override)
result = db_cursor.fetchone();
max_temp_override = int(result[0]) # above this temp average is not calculated doors are just opened

db_cursor.execute(get_max_temp_east)
result = db_cursor.fetchone();
max_temp_east = int(result[0]) # open east doors
logging.debug('Max temp East: %s',max_temp_east)

db_cursor.execute(get_max_temp_west)
result = db_cursor.fetchone();
max_temp_west = int(result[0]) # open west doors
logging.debug('Max temp West: %s',max_temp_west)

db_cursor.execute(get_min_temp_east)
result = db_cursor.fetchone();
min_temp_east = int(result[0]) # close east doors
logging.debug('Min temp East: %s',min_temp_east)

db_cursor.execute(get_min_temp_west)
result = db_cursor.fetchone();
min_temp_west = int(result[0]) # close west doors
logging.debug('Min temp West: %s',min_temp_west)

db_cursor.execute(east_man_override)
east_man_override = db_cursor.fetchone()

db_cursor.execute(west_man_override)
west_man_override = db_cursor.fetchone() 

# enter the loop to get readings
while True: 
    
    # get the tyempt reading from the output file and convert to a float to one decimal place
    with open('/sys/bus/w1/devices/28-000007d3a568/w1_slave') as f:
        lines = f.readlines()
        temp = lines[1]
        current_temp = float(temp[29:34])
        current_temp = round(current_temp/1000, 1)
        logging.debug('Current temp: %s', current_temp)
        
    # write temp reading to MySQL
    # insert_temp = '''INSERT INTO temp_readings (temp) VALUES (%s)'''
    db_cursor.execute("INSERT INTO temp_readings (temp) VALUES (%s)"%(current_temp))    
    db_connection.commit()
    logging.debug('%s record inserted', db_cursor.rowcount)
    
    # check if current temp has exceeded the max override value
    if current_temp > max_temp_override:
        # open both doors it's TOO HOT!!
        if (east_man_override[0] == "off"):
            db_cursor.execute("UPDATE string_vars SET value='open' WHERE name='east_door'")
            db_connection.commit()
            logging.debug('Max override triggered, Esst doors set to open - temp: %s', current_temp)
        if (west_man_override[0] == "off"):
            db_cursor.execute("UPDATE string_vars SET value='open' WHERE name='west_door'")
            db_connection.commit()
            logging.debug('Max override triggered, West doors set to open - temp: %s', current_temp)

        # call the door control script
        os.system('python3 /home/pi/poly_auto/door_control.py')
        
    # get readings from last half hour
    db_cursor.execute("SELECT temp FROM temp_readings WHERE updated > (NOW() - INTERVAL 30 MINUTE)")
    temps = db_cursor.fetchall()
    # print("temps: ", temps)
    temp_list = list(itertools.chain(*temps))
    logging.debug('%s',temp_list)
    
    # only go on to calculate average if there are more than 2 readings  
    if len(temps) > 2:
        # calc the average value
        average_temp = round(sum(temp_list)/len(temp_list),1)
        db_cursor.execute("UPDATE string_vars SET value=%s WHERE name='average_temp'"%(average_temp))
        db_connection.commit()
        logging.info('Av temp: %s', average_temp)
        if average_temp > max_temp_east:
            # open east doors
            logging.debug('Opening East doors - temp: %s', average_temp)
            if (east_man_override[0] == "off"):
                db_cursor.execute("UPDATE string_vars SET value='open' WHERE name='east_door'")
                db_connection.commit()
                logging.debug('Opening East doors - temp: %s', average_temp)
                # call the door control script
                os.system('python3 /home/pi/poly_auto/door_control.py')
        elif average_temp > max_temp_west:
            # open west doors
            if (west_man_override[0] == "off"):
                db_cursor.execute("UPDATE string_vars SET value='open' WHERE name='west_door'")
                db_connection.commit()
                logging.debug('Opening West doors - temp: %s', average_temp)
                # call the door control script
                os.system('python3 /home/pi/poly_auto/door_control.py')
        elif average_temp < min_temp_east:
            # close East doors
            if (east_man_override[0] == "off"):
                db_cursor.execute("UPDATE string_vars SET value='close' WHERE name='east_door'")
                db_connection.commit()
                logging.debug('Closing East doors - temp: %s', average_temp)
                # call the door control script
                os.system('python3 /home/pi/poly_auto/door_control.py')
        elif average_temp < min_temp_west:
            # close west doors
            if (west_man_override[0] == "off"):
                db_cursor.execute("UPDATE string_vars SET value='close' WHERE name='west_door'")
                db_connection.commit()
                logging.debug('Closing West doors - temp: %s', average_temp)
                # call the door control script
                os.system('python3 /home/pi/poly_auto/door_control.py')

        
        
    # delete any records older than 1 day
    db_cursor.execute("DELETE FROM temp_readings WHERE updated < (NOW() - INTERVAL 1 DAY)")
    db_connection.commit()

    logging.debug('Pausing loop for: %s', sleep_time)
    time.sleep(sleep_time)
    continue
    
        
