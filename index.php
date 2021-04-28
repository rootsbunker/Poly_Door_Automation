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

include('functions.php');

$mysqli_con = dbConnect();

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='average_temp' LIMIT 1");

$average_temp = mysqli_fetch_array($sql);

// get the east door status
$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='east_door_status' LIMIT 1");

$east_door_status = mysqli_fetch_array($sql);

// get the west door status
$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='west_door_status' LIMIT 1");

$west_door_status = mysqli_fetch_array($sql);

// set the value for whether to run door_control.py
$run_door_control = False;

// emergency stop!
if(isset($_POST['stop_button'])) {
	if ($_POST['stop_button'] == "Stop") {

		if ($_POST['stop_door'] == "east") {
		
			stopEastDoor();
		
		}
		
		if ($_POST['stop_door'] == "west") {
		
			stopWestDoor();
			
		}
		
		
	}
}

if(isset($_POST['east_door'])){
	if ($_POST['east_door'] == "open" && $east_door_status['value'] != "opened") {
		
		if($east_door_status['value'] != "opening"){
			
		// open the east door
		openEastDoor();
		
		// enable the python script
		$run_door_control = True;
		}
		
	}
	elseif ($_POST['east_door'] == "close" && $east_door_status['value'] != "closed"){
		
		if($east_door_status['value'] != "closing") {
			
		// close the east door
		closeEastDoor();

		// enable the python script
		$run_door_control = True;
		}
	}
}

if(isset($_POST['west_door'])){
	if ($_POST['west_door'] == "open" && $west_door_status['value'] != "opened"){
		
		if($west_door_status['value'] != "opening"){
		// open the west door
		openWestDoor();
		
		// enable the python script
		$run_door_control = True;
		}
	}
	elseif ($_POST['west_door'] == "close" && $west_door_status['value'] != "closed"){
		
		if($west_door_status['value'] != "closing"){
		// close the west door
		closeWestDoor();

		// enable the python script
		$run_door_control = True;
		}
	}
}

if ($run_door_control == True) {
	
	// to make this work added user www-data to sudo group: usermod -a -G sudo www-data
	// and updated /etc/sudoers file with NOPASSWD: ALL for %sudo group:
	// %sudo   ALL=(ALL:ALL) NOPASSWD: ALL

	// execute door_control.py (has to run elevated for relay modules to work)
	exec('sudo /usr/bin/python3 /home/pi/poly_auto/door_control.py > /dev/null 2>&1 &');
	
}

// get the east door status
$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='east_door_status' LIMIT 1");

$east_door_status = mysqli_fetch_array($sql);

// get the west door status
$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='west_door_status' LIMIT 1");

$west_door_status = mysqli_fetch_array($sql);	

##### SETTINGS PAGE ####

// process the settings form

if (isset($_POST['form_name']) && $_POST['form_name'] == "settings") {
	
	// set the page to land on
	$open_page = "document.getElementById('settings').click();";
	
	// get the POST vars
	extract($_POST);
	
	if($max_temp_east < $min_temp_east || $max_temp_west < $min_temp_west) {
		// set an error
		$slider_error = "<tr><td id='slider_error' colspan='2'>Close temp cannot exceed Open!</td></tr>";
	}
	else
	{
	
	// put in the temp values for all sliders
	@mysqli_query($mysqli_con,"UPDATE string_vars SET value='$max_temp_east' WHERE name='max_temp_east'");
	@mysqli_query($mysqli_con,"UPDATE string_vars SET value='$min_temp_east' WHERE name='min_temp_east'");
	@mysqli_query($mysqli_con,"UPDATE string_vars SET value='$max_temp_west' WHERE name='max_temp_west'");
	@mysqli_query($mysqli_con,"UPDATE string_vars SET value='$min_temp_west' WHERE name='min_temp_west'");
	
	// put in the override values for the checkboxes
	if (isset($east_man_override)) {
		@mysqli_query($mysqli_con,"UPDATE string_vars SET value='off' WHERE name='east_man_override'");
	}else{
		@mysqli_query($mysqli_con,"UPDATE string_vars SET value='on' WHERE name='east_man_override'");
	}
	if (isset($west_man_override)) {
		@mysqli_query($mysqli_con,"UPDATE string_vars SET value='off' WHERE name='west_man_override'");
	}else{
		@mysqli_query($mysqli_con,"UPDATE string_vars SET value='on' WHERE name='west_man_override'");
	}
	
	// settings changes, restart the service
	exec('sudo systemctl restart poly');
	
	// give that a chance to restart
	sleep(2);
	// get door status again in case it's triggered an event
	// get the east door status
	$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='east_door_status' LIMIT 1");

	$east_door_status = mysqli_fetch_array($sql);

	// get the west door status
	$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='west_door_status' LIMIT 1");

	$west_door_status = mysqli_fetch_array($sql);	
	
	}
}


// get all the data for the form fields

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='max_temp_east' LIMIT 1");

$max_temp_east = mysqli_fetch_array($sql);

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='max_temp_west' LIMIT 1");

$max_temp_west = mysqli_fetch_array($sql);

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='min_temp_east' LIMIT 1");

$min_temp_east = mysqli_fetch_array($sql);

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='min_temp_west' LIMIT 1");

$min_temp_west = mysqli_fetch_array($sql);

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='east_man_override' LIMIT 1");

$east_man_override = mysqli_fetch_array($sql);

if ($east_man_override['value'] == "off") {
	$east_check = "checked";
}else{
	$east_check = "";
}

$sql = mysqli_query($mysqli_con,"SELECT value FROM string_vars WHERE name='west_man_override' LIMIT 1");

$west_man_override = mysqli_fetch_array($sql);

if ($west_man_override['value'] == "off") {
	$west_check = "checked";
}else{
	$west_check = "";
}



##### STATUS PAGE #####

$gpu_temp = exec('sudo vcgencmd measure_temp');
$gpu_temps = explode("=", $gpu_temp);
$cpu_temp = exec('cat /sys/class/thermal/thermal_zone0/temp');
$cpu_temp = round($cpu_temp/1000, 1);

$last_temps = getLastTemps();

$string_vars = getStringVars();

if(isset($_GET)){
	
	// get the GET vars
	extract($_GET);
	
	//check for open_page
	if(isset($open_page) && $open_page == "info"){
		
		// set the javascript for info tab
		$open_page = "document.getElementById('info').click();";
	}
}	
		
?>



<!DOCTYPE html>
<html>
	<head>
		
		<meta charset="UTF-8">
		<!-- <meta http-equiv="refresh" content="30"> -->
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<script src="poly.js"></script>
		<link rel="stylesheet" href="poly.css">
		
		<title>Poly Automation</title>
	</head>
	
	<body>
	
	<!-- Tab links -->
	<div class="tab">	
	  <button class="tablinks" onclick="openTab(event, 'Doors')" id="doors">Doors</button>
	  <button class="tablinks" onclick="openTab(event, 'Settings')" id="settings">Settings</button>
	  <button class="tablinks" onclick="openTab(event, 'Info')" id="info">Info</button>
	</div>

	<!-- Tab content -->
	<div id="Doors" class="tabcontent">
	<table class="main-layout">  
		<form action="index.php" method="post">
		  <tr>
			<td><input type="reset" class="reset-button" value="Reset"></td>
			<td><input type="submit" class="submit-button" value="Do it!"></td>
		  </tr>
	  
			<tr>
			<th>Average Temp:</th>
			<th><a href="http://poly.shantysound.system/" title="refresh"><?php echo $average_temp['value']; ?>'C</a></th>
			</tr>
		
			<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			</tr>
		
		<!-- EAST DOOR -->
		
			<tr>
			<th>East Door:</th>
			<th>&nbsp;</th>
			</tr>
	
	  
	  <?php
		if ($east_door_status['value'] == "opening") {
			
			// display opening & stop button
			?>
			<tr>
				<td><span class="progress">Opening pls wait:</span>
				<span class="progress" id="east_countdown"></span></td>
			
				<td>
			<form action="index.php" method="post">
				<input type="hidden" name="stop_door" value="east">
				<input type="submit" name="stop_button" class="submit-button" value="Stop">
			</form>
			<script type='text/javascript'>timedRefreshEast(90);</script>
			</td>
		</tr>
		<?php	
		}
		elseif ($east_door_status['value'] == "closing") {
			
			// display closing & stop button
			?>
			<tr>
				<td><span class="progress">Closing pls wait:</span>
				<span class="progress" id="east_countdown"></span></td>
			
				<td>
			<form action="index.php" method="post">
				<input type="hidden" name="stop_door" value="east">
				<input type="submit" name="stop_button" class="submit-button" value="Stop">
			</form>
			<script type='text/javascript'>timedRefreshEast(90);</script>
			</td>
		</tr>
		<?php	
		}
		elseif ($east_door_status['value'] == "part_open") {
	
		// display two buttons to open or close
		?>
		<tr>
				
				<td>Part open</td>
				<td>&nbsp;</td>
		</tr>
		<tr>
				
				<td colspan="2">
				<div class="switch-field">
					<input type="radio" id="east_close" name="east_door" value="close">
					<label for="east_close">Close</label>
					<input type="radio" id="east_open" name="east_door" value="open">
					<label for="east_open">Open</label>
				</div>
				</td>
			</tr>
	  <?php
		}
		elseif ($east_door_status['value'] == "opened"){
		  
			// display radio buttons with open selected (checked)
			?>
			<tr>
				
				<td colspan="2" class="right-column">
					<div class="switch-field">
						<input type="radio" id="east_close" name="east_door" value="close" />
						<label for="east_close">Close</label>
						<input type="radio" id="east_open" name="east_door" value="open" checked />
						<label for="east_open">Open</label>
					</div>
					</td>
			</tr>
			
			
		<?php
		}
		elseif ($east_door_status['value'] == "closed"){
		  
			// display radio buttons with closed selected (checked)
			?>
			<tr>
				
				<td colspan="2" class="right-column">
				<div class="switch-field">
					<input type="radio" id="east_close" name="east_door" value="close"  checked />
					<label for="east_close">Close</label>
					<input type="radio" id="east_open" name="east_door" value="open"/>
					<label for="east_open">Open</label>
				</div>
				</td>
			</tr>
			
		<?php
		}
		
		?>
		

		<!-- WEST DOOR -->

			<tr>
			<th>West Door:</th>
			<th>&nbsp;</th>
			</tr>
	
	  
	  <?php
		if ($west_door_status['value'] == "opening") {
			
			// display opening & stop button
			?>
			<tr>
				<td><span class="progress">Opening pls wait:</span>
				<span class="progress" id="west_countdown"></span></td>
			
				<td>
			<form action="index.php" method="post">
				<input type="hidden" name="stop_door" value="west">
				<input type="submit" name="stop_button" class="submit-button" value="Stop">
			</form>
			<script type='text/javascript'>timedRefreshWest(90);</script>
			</td>
		</tr>
		<?php	
		}
		elseif ($west_door_status['value'] == "closing") {
			
			// display closing & stop button
			?>
			<tr>
				<td><span class="progress">Closing pls wait:</span>
				<span class="progress" id="west_countdown"></span></td>
			
				<td>
			<form action="index.php" method="post">
				<input type="hidden" name="stop_door" value="west">
				<input type="submit" name="stop_button" class="submit-button" value="Stop">
			</form>
			<script type='text/javascript'>timedRefreshWest(90);</script>
			</td>
		</tr>
		<?php	
		}
		elseif ($west_door_status['value'] == "part_open") {
	
		// display two buttons to open or close
		?>
		<tr>
				
				<td>Part open</td>
				<td>&nbsp;</td>
		</tr>
		<tr>
				
				<td colspan="2">
				<div class="switch-field">
					<input type="radio" id="west_close" name="west_door" value="close">
					<label for="west_close">Close</label>
					<input type="radio" id="west_open" name="west_door" value="open">
					<label for="west_open">Open</label>
				</div>
				</td>
			</tr>
	  <?php
		}
		elseif ($west_door_status['value'] == "opened"){
		  
			// display radio buttons with open selected (checked)
			?>
			<tr>
				
				<td colspan="2" class="right-column">
					<div class="switch-field">
						<input type="radio" id="west_close" name="west_door" value="close" />
						<label for="west_close">Close</label>
						<input type="radio" id="west_open" name="west_door" value="open" checked />
						<label for="west_open">Open</label>
					</div>
					</td>
			</tr>
			
			
		<?php
		}
		elseif ($west_door_status['value'] == "closed"){
		  
			// display radio buttons with closed selected (checked)
			?>
			<tr>
				
				<td colspan="2" class="right-column">
				<div class="switch-field">
					<input type="radio" id="west_close" name="west_door" value="close"  checked />
					<label for="west_close">Close</label>
					<input type="radio" id="west_open" name="west_door" value="open"/>
					<label for="west_open">Open</label>
				</div>
				</td>
			</tr>
			
		<?php
		}
		
		?>
	                

	</form>  
	</table>  
	</div>

	<div id="Settings" class="tabcontent">
	 
	<form action="index.php" method="post">
	<table class="main-layout">
		<input type="hidden" name="form_name" value="settings"> 
		
		  <tr>
			<td><input type="reset" class="reset-button" value="Reset"></td>
			<td><input type="submit" class="submit-button" value="Do it!"></td>
		  </tr>
	  
		<?php if(isset($slider_error)){ echo $slider_error; } ?>
		
		<tr>
			<td>Open East doors at:</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
		<td colspan="2">
		<div class="range-wrap">
		  <input type="range" class="range" min="10" max="25" value="<?php echo $max_temp_east['value']; ?>" name="max_temp_east">
		  <output class="bubble"></output>
		</div>
		</td>
		</tr>
		<tr>
		<td>Close East doors at:</td>
		<td>&nbsp;</td>
		</tr>
		<tr>
		<td colspan="2">
		<div class="range-wrap">
		  <input type="range" class="range" min="10" max="25" value="<?php echo $min_temp_east['value']; ?>" name="min_temp_east">
		  <output class="bubble"></output>
		</div>
		</td>
		</tr>
		
		<tr>
		<td>Open West doors at:</td>
		<td>&nbsp;</td>
		</tr>
		<tr>
		<td colspan="2">
		<div class="range-wrap">
		  <input type="range" class="range" min="10" max="25" value="<?php echo $max_temp_west['value']; ?>" name="max_temp_west">
		  <output class="bubble"></output>
		</div>
		</td>
		</tr>
		<tr>
		<td>Close West doors at:</td>
		<td>&nbsp;</td>
		</tr>
		<tr>
		<td colspan="2">
		<div class="range-wrap">
		  <input type="range" class="range" min="10" max="25" value="<?php echo $min_temp_west['value']; ?>" name="min_temp_west">
		  <output class="bubble"></output>
		</div>
		</td>
		</tr>
		<tr>
			<td>Auto East doors:</td>	
			<td>
			<label class="switch">
			  <input type="checkbox" name="east_man_override" value="on" <?php echo $east_check; ?> />
			  <span class="slider"></span>
			</label>
		</td>
		</tr>
		<tr>
			<td>Auto West doors:</td>	
			<td>
			<label class="switch">
			  <input type="checkbox" name="west_man_override" value="on" <?php echo $west_check; ?> />
			  <span class="slider"></span>
			</label>
			</td>
		</tr>
		
		
		</table>
	 </form>
	 
	 </div>
	 
	<!--  slider bar bubbles - placed here to execute after the php above -->
	
		<script type='text/javascript'>
		const allRanges = document.querySelectorAll(".range-wrap");
		allRanges.forEach(wrap => {
		  const range = wrap.querySelector(".range");
		  const bubble = wrap.querySelector(".bubble");

		  range.addEventListener("input", () => {
			setBubble(range, bubble);
		  });
		  setBubble(range, bubble);
		});

		function setBubble(range, bubble) {
		  const val = range.value;
		  const min = range.min ? range.min : 0;
		  const max = range.max ? range.max : 100;
		  const newVal = Number(((val - min) * 100) / (max - min));
		  bubble.innerHTML = val;

		  // Sorta magic numbers based on size of the native UI thumb
		  bubble.style.left = `calc(${newVal}% + (${8 - newVal * 0.15}px))`;
		}
		</script>
	
	<!-- ### INFO PAGE ### -->

	<div id="Info" class="tabcontent">
	  <p>CPU temp: <a href="http://poly.shantysound.system/index.php?open_page=info" title="refresh"><?php echo $cpu_temp; ?>'C</a></p>
	  <p>GPU temp: <a href="http://poly.shantysound.system/index.php?open_page=info" title="refresh"><?php echo $gpu_temps[1]; ?></a></p>
		<p>Temps in last hour:</p>
		<table class="info">
			<tr>
			<th>Temp:</th>
			<th>Updated:</th>
			</tr>
			
		<?php
		foreach ($last_temps as $temp_reading){
			?>
			<tr>
			<td><?php echo  $temp_reading['temp']; ?></td>
			<td><?php echo $temp_reading['updated']; ?></td>
			</tr>
			<?php
		}
		?>
		</table>
		<p>Current db values:</p>
		<table class="info">
			<tr>
			<th>Name:</th>
			<th>Value:</th>
			</tr>
		<?php
		foreach ($string_vars as $string_var) {
			?>
			<tr>
			<td><?php echo $string_var['name']; ?></td>
			<td><?php echo $string_var['value']; ?></td>
			</tr>
			<?php
		}
		?>
		</table>
		
		<p>poly systemd process status:</p>
		<?php
		$poly_service = shell_exec('sudo systemctl status poly');
		echo $poly_service;
		?>
		
	</div>
	
	
	<?php
	if(isset($open_page)){
		?>
		<script>
		// open the settings tab
		<?php echo $open_page; ?>
		</script>
	<?php
	}
	else
	{
	?>
		<script>
		// open the default tab
		document.getElementById("doors").click();
		</script>
	<?php
	}
	?>

	
	</body>
</html>