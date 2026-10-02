<?php
include 'db.php';

$type = $_GET['type'] ?? 'month'; // month, year, lifetime
$param = $_GET['param'] ?? '';    // contoh: "2025-07" untuk month, "2025" untuk year, kosong untuk lifetime

$data = [];
$labels = [];
$values = [];

if ($type === 'month') {
    // Ambil data harian dalam bulan tertentu dari tabel harian
    // $param format YYYY-MM
    $sql = "SELECT tanggal, (lwbp_consumpt + wbp_consumpt) AS total_consumpt, total_tarif 
            FROM harian 
            WHERE location = 'LVMDP'
            AND DATE_FORMAT(tanggal, '%Y-%m') = '$param'
            ORDER BY tanggal ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = date('d', strtotime($row['tanggal']));
        $values[] = $row['total_tarif'];
    }

} elseif ($type === 'year') {
    // Ambil data bulanan dalam tahun tertentu dari tabel bulanan
    // $param format YYYY
    $sql = "SELECT bulan, total_tarif 
            FROM bulanan 
            WHERE location = 'LVMDP'
            AND YEAR(bulan,4) = '$param'
            ORDER BY bulan ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = date('M', strtotime($row['bulan']));
        $values[] = $row['total_tarif'];
    }

} elseif ($type === 'lifetime') {
    // Ambil data tahunan dari tabel bulanan (aggregate)
    $sql = "SELECT YEAR(bulan) AS tahun, SUM(total_tarif) AS total_tarif
            FROM bulanan
            WHERE location = 'LVMDP'
            GROUP BY YEAR(bulan)
            ORDER BY tahun ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = $row['tahun'];
        $values[] = $row['total_tarif'];
    }
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
