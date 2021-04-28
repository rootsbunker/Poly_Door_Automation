<?php

$dbhost               = 'localhost';
$dbuser               = '[username here]';
$dbpasswd             = '[password here]';
$dbname               = 'poly';


// select database to use USING MySQLi

$mysqli_con = @mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);


?>
