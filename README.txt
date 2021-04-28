This is a simple set of scripts to run Poly tunnel doors based on average temp.

Use pip to install python modules as required, follow documentation for the relay hats to install them.

The main script loops every five minutes and calculates the temp based on an average of reading from last half hour, this prevents flapping as temp changes rapidly in tunnel.

SQL example provided for MySQL database structure.

Wiring diagram provided for my setup - note I am primarily using the 4relay hat so I can switch 240v AC.

If running from 12v battery / solar panel you could do all of this with just the 8relay hat.

PLEASE NOTE: I am not a developer and these and just some scripts I put together, error handling is limited! Logging is on and logs written to install directory.

Install directory on Pi:

/home/pi/poly_auto

Systemd file:

[Unit]
Description=Poly Automation
After=multi-user.target
[Service]
Type=simple
Restart=always
ExecStart=/usr/bin/python3 /home/pi/poly_auto/get_temps.py
User=pi
Group=pi
StartLimitBurst=2
StartLimitIntervalSec=180s

[Install]
WantedBy=multi-user.target

location: /etc/systemd/system/poly.service

use "sudo systemctl enable poly" to make this run at startup and restart on error.

##### WEB APP ####

index.php
functions.php
poly.css
poly.js

should be placed in /home/pi/poly_auto/www

Apache2 config:

<VirtualHost *:80>

        ServerName poly.shantysound.system
        ServerAlias rpi-poly.shantysound.system
        ServerAdmin webmaster@localhost
        DocumentRoot /home/pi/poly_auto/www
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost>

<Directory  /home/pi/poly_auto/www>
    Options Indexes FollowSymLinks MultiViews
    AllowOverride all
    Require all granted
</Directory>

Ensure you use chmod to permit access to www for www-data user.

##### SECURITY NOTICE ######

This is not intended to be a secure application. Web server is http and apache2 is allowed to sudo to run the relays.

Do not store (or grant access to) any sensitive data on the Pi that you install this on!