<?php
include 'db.php';
$start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end   = $_GET['end_date'] ?? date('Y-m-d');

$sql = "SELECT tanggal,total_tarif FROM electricity_aoi1_harian
        WHERE id_location='101' AND tanggal BETWEEN '$start' AND '$end' ORDER BY tanggal ASC";
$result = $conn->query($sql);
$labels=[]; $values=[];
while($row=$result->fetch_assoc()){
    $labels[] = $row['tanggal'];
    $values[] = (float)$row['total_tarif'];
}
echo json_encode(['labels'=>$labels,'values'=>$values]);
