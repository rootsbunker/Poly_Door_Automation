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

# this script relies on get_temps.py to update the db with commands for the doors based on temperature readings

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
get_logging = '''SELECT value FROM string_vars WHERE name = "logging"'''
db_cursor.execute(get_logging)
result = db_cursor.fetchone()

if result[0] == 'on':
    logging.basicConfig(filename='/home/pi/poly_auto/door_control.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.DEBUG)
else:
    logging.basicConfig(filename='/home/pi/poly_auto/door_control.log',
                        filemode='a',
                        format='%(asctime)s %(levelname)s %(message)s',
                        datefmt='%d-%m-%Y %H:%M:%S',
                        level=logging.INFO)

logging.info('door_control.py has started')
logging.info('Logging: %s', result[0])

# function to turn all relays off
def reset_relays():
    get_relay = lib4relay.get_all(0)
    get_relay = int(get_relay)
    if (get_relay != 0):
        # kill the AC first
        lib4relay.set(0,1,0) # neutral AC
        lib4relay.set(0,2,0) # live AC
        time.sleep(20) # give the transformer time to discharge
        # then reset DC (crossed over)
        lib4relay.set(0,3,0) # live DC
        lib4relay.set(0,4,0) # ground DC
    # then reset the 8relay board
    lib8relay.set_all(0,0)
    return

# function to log relay status
def log_relay_status():
    get_relay = lib4relay.get_all(0)
    logging.debug('4relay status %s', get_relay)
    get_relay = lib8relay.get_all(0)
    logging.debug('8relay status %s', get_relay)
    return


# check db to see if this is already running
door_control_running_sql = "SELECT value FROM string_vars WHERE name='door_control_running'"
db_cursor.execute(door_control_running_sql)
door_control_running = db_cursor.fetchone()


while(door_control_running[0] == "yes"):

    logging.debug('Already running checking how recent')
    
    # check db to see if this is already running, if it's been running for more than 5 minutes assume it's died
    door_control_running_sql = "SELECT value FROM string_vars WHERE name='door_control_running' AND updated > (NOW() - INTERVAL 5 MINUTE)"
    db_cursor.execute(door_control_running_sql)
    door_control_running_5 = db_cursor.fetchone()

    if (door_control_running_5 is None):

        logging.debug('Its older than 5 mins, killing it')

        # it's running but older than 5 mins - kill it
        # get my pid so i don't kill this process
        mypid = os.getpid()
        # kill all processes except this one
        os.system("kill $(ps aux |  grep [d]oor_control | grep python3 | grep -v %d | awk '{print $2}')" %(mypid))
    
        # update running to no
        db_cursor.execute("UPDATE string_vars SET value='no' WHERE name='door_control_running'")    
        db_connection.commit()
    
    else:
    
        # it's a recent start time and check again
        logging.debug('Its newer than 5 mins, sleep for a minute')
        
        time.sleep(60)
        
        # check db to see if this is already running
        door_control_running_sql = "SELECT value FROM string_vars WHERE name='door_control_running'"
        db_cursor.execute(door_control_running_sql)
        door_control_running = db_cursor.fetchone()
        

# it isn't running, carry on

# update door_control_running to yes
db_cursor.execute("UPDATE string_vars SET value='yes' WHERE name='door_control_running'")    
db_connection.commit()

door_sleep = 80 # delay for motor to run, takes ~70 seconds

# look for door commands in database
east_door = "SELECT value FROM string_vars WHERE name='east_door'"
east_door_status = "SELECT value FROM string_vars WHERE name='east_door_status'"
west_door = "SELECT value FROM string_vars WHERE name='west_door'"
west_door_status = "SELECT value FROM string_vars WHERE name='west_door_status'"

db_cursor.execute(east_door)
east_door = db_cursor.fetchone()

db_cursor.execute(east_door_status)
east_door_status = db_cursor.fetchone()

db_cursor.execute(west_door)
west_door = db_cursor.fetchone()

db_cursor.execute(west_door_status)
west_door_status = db_cursor.fetchone()


### EAST DOOR ###
if east_door[0] == 'close' and east_door_status[0] != 'closed':
    #close the east door
    logging.debug('Closing East door...')
    
    # turn all relays off
    reset_relays()
    
    # update status to closing
    db_cursor.execute("UPDATE string_vars SET value='closing' WHERE name='east_door_status'")    
    db_connection.commit()
    
    # leave the DC on 'off' to connect crossed over
    # lib4relay 3 & 4 unchanged
    
    # turn on transformer
    lib4relay.set(0,1,1) # neutral AC
    lib4relay.set(0,2,1) # live AC
    
    # run the east door
    lib8relay.set(0,1,1)
    lib8relay.set(0,2,1)
    # print relay status to log
    log_relay_status()
    
    #sleep while the motor runs (takes ~70 seconds)
    time.sleep(door_sleep)
    
    #reset the relays
    logging.debug('Finished closing East door, resetting relays')
    
    # update the status to closed
    db_cursor.execute("UPDATE string_vars SET value='closed' WHERE name='east_door_status'")       
    db_connection.commit()
    
    # turn all relays off
    reset_relays()
    # print relay status to log
    log_relay_status()
   
if east_door[0] == 'open' and east_door_status[0] != 'opened':
    #open the east door
    logging.debug('Opening East door...')
    
    # turn all relays off
    reset_relays()
    
    # update status to opening
    db_cursor.execute("UPDATE string_vars SET value='opening' WHERE name='east_door_status'")    
    db_connection.commit()
    
    # connect the DC straight thru ('on' for both relays)
    lib4relay.set(0,3,1) # live DC
    lib4relay.set(0,4,1) # ground DC
    
    # turn on transformer
    lib4relay.set(0,1,1) # neutral AC
    lib4relay.set(0,2,1) # live AC
    
    # run the east door
    lib8relay.set(0,1,1)
    lib8relay.set(0,2,1)
    
    # print relay status to log
    log_relay_status()
    
    #sleep while the motor runs (takes ~70 seconds)
    time.sleep(door_sleep)
    
    #reset the relays
    logging.debug('Finished opening East door, resetting relays')
        
    # update status to opened
    db_cursor.execute("UPDATE string_vars SET value='opened' WHERE name='east_door_status'")    
    db_connection.commit()
    
    # turn all relays off
    reset_relays()
    # print relay status to log
    log_relay_status()
    
    ### EAST DOOR END ###
    
### WEST DOOR ###
if west_door[0] == 'close' and west_door_status[0] != 'closed':
    #close the west door
    logging.debug('Closing West door...')
    
    # turn all relays off
    reset_relays()
    
    # update status to closing
    db_cursor.execute("UPDATE string_vars SET value='closing' WHERE name='west_door_status'")    
    db_connection.commit()

    # leave the DC on 'off' to connect crossed over
    # lib4relay 3 & 4 unchanged 

    # turn on transformer
    lib4relay.set(0,1,1) # neutral AC
    lib4relay.set(0,2,1) # live AC

    # run the west door
    lib8relay.set(0,3,1)
    lib8relay.set(0,4,1)
    # print relay status to log
    log_relay_status()
    
    #sleep while the motor runs (takes ~70 seconds)
    time.sleep(door_sleep)
    
    #reset the relays
    logging.debug('Finished closing West door, resetting relays')
    
    # update the status to closed
    db_cursor.execute("UPDATE string_vars SET value='closed' WHERE name='west_door_status'")    
    db_connection.commit()
    
    # turn all relays off
    reset_relays()
    # print relay status to log
    log_relay_status()
   
if west_door[0] == 'open' and west_door_status[0] != 'opened':
    #open the west door
    logging.debug('Opening West door...')
    
    # turn all relays off
    reset_relays()
    
    # update status to opening
    db_cursor.execute("UPDATE string_vars SET value='opening' WHERE name='west_door_status'")    
    db_connection.commit()
    
    # connect the DC straight thru ('on' for both relays)
    lib4relay.set(0,3,1) # live DC
    lib4relay.set(0,4,1) # ground DC
    
    # turn on transformer
    lib4relay.set(0,1,1) # neutral AC
    lib4relay.set(0,2,1) # live AC

    # run the west door
    lib8relay.set(0,3,1)
    lib8relay.set(0,4,1)
    # print relay status to log
    log_relay_status()
    
    #sleep while the motor runs (takes ~70 seconds)
    time.sleep(door_sleep)
    
    #reset the relays
    logging.debug('Finished opening West door, resetting relays')
    
    # update status to opened
    db_cursor.execute("UPDATE string_vars SET value='opened' WHERE name='west_door_status'")    
    db_connection.commit()

    # turn all relays off
    reset_relays()
    # print relay status to log
    log_relay_status()

    ### WEST DOOR END ##
            
# all done
# update door_control_running to no
db_cursor.execute("UPDATE string_vars SET value='no' WHERE name='door_control_running'")    
db_connection.commit()
db_connection.close()
logging.info('door_control.py has completed')
exit()

