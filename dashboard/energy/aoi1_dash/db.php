<?php
// Koneksi database
$host = "192.168.51.40";
$user = "remote";
$password = "Automation@321.";
$database = "enviro";

$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
