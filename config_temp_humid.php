<?php
/**
 * using mysqli_connect for database connection
 */

// $databaseHost = '192.168.51.40';
// $databaseName = 'enviro';
// $databaseUsername = 'remote';
// $databasePassword = 'Automation@321.';

$databaseHost = 'localhost';
$databaseName = 'enviro';
$databaseUsername = 'root';
$databasePassword = ''; // sesuaikan password MySQL lokal Anda


$conn = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);

?>
