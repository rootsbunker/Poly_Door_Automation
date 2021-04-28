<?php

/*

Poly Door Automation - Temperature controlled door activation using linear actuators
Copyright (C) 2021  Nick Franklin

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.


*/

function dbConnect(){

include('../db_connect.php');


if(!$mysqli_con){
	echo "<script type='text/javascript'>alert('oops! that fucked up - database connect is wrong mofo!!')</script>";
   exit;
   }
	

return $mysqli_con;

}

function openEastDoor() {
	
	// set the door to open in the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='open' WHERE name='east_door'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='opening' WHERE name='east_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;
	
}

function closeEastDoor() {
	
	// set the door to close in the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='close' WHERE name='east_door'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='closing' WHERE name='east_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;
	
}

function openWestDoor() {
	
	// set the door to open in the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='open' WHERE name='west_door'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='opening' WHERE name='west_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;
	
}

function closeWestDoor() {
	
	// set the door to close in the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='close' WHERE name='west_door'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='closing' WHERE name='west_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;
	
}

function stopEastDoor() {
	
	// switch off the relays
	//exec('sudo 8relay 0 write 1 off');
	//exec('sudo 8relay 0 write 2 off');
	exec('sudo /usr/bin/python3 /home/pi/poly_auto/stop_east.py > /dev/null 2>&1 &');
	
	// update the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='part_open' WHERE name='east_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;
}

function stopWestDoor() {
	
	// switch off the relays
	//exec('sudo 8relay 0 write 3 off');
	//exec('sudo 8relay 0 write 4 off');
	exec('sudo /usr/bin/python3 /home/pi/poly_auto/stop_west.py > /dev/null 2>&1 &');
	
	// update the database
	$mysqli_con = dbConnect();
	
	
	$sql = @mysqli_query($mysqli_con,"UPDATE string_vars SET value='part_open' WHERE name='west_door_status'");
		   
		   if(!$sql){
			  echo "<script type='text/javascript'>alert('oops! DB update failed?')</script>";
			  exit;
		   }

return;	
}

function getLastTemps() {
	
	$mysqli_con = dbConnect();
	
	$last_temps = @mysqli_query($mysqli_con,"SELECT temp,updated FROM temp_readings WHERE updated > (NOW() - INTERVAL 60 MINUTE) ORDER BY id DESC");
	
	
return $last_temps;	
}

function getStringVars() {
	
	$mysqli_con = dbConnect();
	
	$string_vars = @mysqli_query($mysqli_con,"SELECT name,value FROM string_vars");
	
	
return $string_vars;	
}
?>