<?php
include 'db.php';
$type = $_GET['type'] ?? 'month';
$value = $_GET['value'] ?? '';

$labels = [];
$tarifs = [];

if ($type === 'month') {
    // Ambil data harian untuk bulan tersebut
    $sql = "SELECT tanggal, total_tarif 
            FROM harian 
            WHERE location='LVMDP' 
            AND DATE_FORMAT(tanggal, '%Y-%m')='$value'
            ORDER BY tanggal ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = date("d M", strtotime($row['tanggal']));
        $tarifs[] = (float)$row['total_tarif'];
    }
}
elseif ($type === 'year') {
    // Ambil data bulanan untuk tahun tersebut
    $sql = "SELECT tanggal, total_tarif 
            FROM bulanan 
            WHERE location='LVMDP' 
            AND YEAR(tanggal)='$value'
            ORDER BY tanggal ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = date("M", strtotime($row['tanggal']));
        $tarifs[] = (float)$row['total_tarif'];
    }
}
elseif ($type === 'lifetime') {
    // Ambil data tahunan
    $sql = "SELECT YEAR(tanggal) as th, SUM(total_tarif) as tarif
            FROM bulanan
            WHERE location='LVMDP'
            GROUP BY th
            ORDER BY th ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = $row['th'];
        $tarifs[] = (float)$row['tarif'];
    }
}

echo json_encode(['labels' => $labels, 'tarifs' => $tarifs]);
mysqli_close($conn);
