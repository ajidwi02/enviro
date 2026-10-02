<?php
/**
 * using mysqli_connect for database connection
 */

$databaseHost = '192.168.51.40';
$databaseName = 'enviro';
$databaseUsername = 'remote';
$databasePassword = 'Automation@321.';

$mysqli = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);

?>
