<?php
$servername = "192.168.51.40";
$username = "remote";
$password = "Automation@321.";
$dbname = "sensor_test";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error){
  die("Koneksi gagal: " . $conn->connect_error);
}
?>