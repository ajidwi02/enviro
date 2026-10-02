<?php
include 'db.php';
$type = $_GET['type'] ?? 'month';
$result = [];

if ($type === 'month') {
    $query = "SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') AS val 
              FROM harian 
              WHERE location='LVMDP' 
              ORDER BY val DESC";
    $res = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $label = date("F Y", strtotime($row['val']."-01"));
        $result[] = ["value" => $row['val'], "label" => $label];
    }
} elseif ($type === 'year') {
    $query = "SELECT DISTINCT LEFT(bulan, 4) AS val 
              FROM bulan 
              WHERE location='LVMDP' 
              ORDER BY val DESC";
    $res = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $result[] = ["value" => $row['val'], "label" => $row['val']];
    }
} else { // lifetime
    $result[] = ["value" => "all", "label" => "Semua Data"];
}

header('Content-Type: application/json');
echo json_encode($result);
mysqli_close($conn);
