#!/usr/bin/env python3

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

# this script is triggered from php when door is stopped

import lib4relay
import lib8relay
import mysql.connector
import time
import itertools
import logging
import os
import sys



db_connection = mysql.connector.connect(
  	host = "localhost",
  	user = "[user here]",
  	passwd = "[password here]",
    database = "poly"
    )

# make the connection to MySQL
db_cursor = db_connection.cursor()

# setup logging
get_logging = "SELECT value FROM string_vars WHERE name = 'logging'"
db_cursor.execute(get_logging)
result = db_cursor.fetchone()

if result[0] == 'on':
    logging.basicConfig(filename='/home/pi/poly_auto/stop_east.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.DEBUG)
else:
    logging.basicConfig(filename='/home/pi/poly_auto/stop_east.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.INFO)

logging.info('stop_east.py has started')
# logging.info('Logging: %s', result[0])

# function to log relay status
def log_relay_status():
    get_relay = lib4relay.get_all(0)
    logging.debug('4relay status %s', get_relay)
    get_relay = lib8relay.get_all(0)
    logging.debug('8relay status %s', get_relay)
    return


# stop the east door
lib8relay.set(0,1,0)
lib8relay.set(0,2,0)

# write to log file
log_relay_status()

# update status to part_open
db_cursor.execute("UPDATE string_vars SET value='part_open' WHERE name='east_door_status'")    
db_connection.commit()

# finish up
db_connection.close()
logging.info('stop_east.py has completed')
exit()

