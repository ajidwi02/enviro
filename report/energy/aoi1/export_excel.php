<?php
$host = "192.168.51.40";
$user = "remote";
$password = "Automation@321.";
$database = "enviro";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_listrik_$tanggal.xls");

echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>Lokasi</th>
        <th>LWBP Awal</th>
        <th>LWBP Akhir</th>
        <th>LWBP Konsumsi</th>
        <th>Tarif LWBP</th>
        <th>WBP Awal</th>
        <th>WBP Akhir</th>
        <th>WBP Konsumsi</th>
        <th>Tarif WBP</th>
        <th>Tanggal</th>
      </tr>";

$query = "
    SELECT 
        loc.id_location,
        loc.location,
        lwbp.kwh_awal AS lwbp_awal,
        lwbp.kwh_akhir AS lwbp_akhir,
        lwbp.consumpt AS lwbp_consumpt,
        lwbp.tarif AS lwbp_tarif,
        lwbp.datenow AS tanggal_lwbp,
        wbp.kwh_awal AS wbp_awal,
        wbp.kwh_akhir AS wbp_akhir,
        wbp.consumpt AS wbp_consumpt,
        wbp.tarif AS wbp_tarif,
        wbp.datenow AS tanggal_wbp
    FROM loc_electricity_aoi1 loc
    LEFT JOIN electricity_aoi1_lwbp lwbp 
        ON lwbp.id_location = loc.id_location 
        AND lwbp.datenow = (
            SELECT MAX(datenow) FROM electricity_aoi1_lwbp 
            WHERE id_location = loc.id_location " . 
            ($tanggal ? "AND DATE(datenow) = '$tanggal'" : "") . "
        )
    LEFT JOIN electricity_aoi1_wbp wbp 
        ON wbp.id_location = loc.id_location 
        AND wbp.datenow = (
            SELECT MAX(datenow) FROM electricity_aoi1_wbp 
            WHERE id_location = loc.id_location " . 
            ($tanggal ? "AND DATE(datenow) = '$tanggal'" : "") . "
        )
";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $tanggal_final = $row['tanggal_lwbp'] ?? $row['tanggal_wbp'];
    echo "<tr>
        <td>{$row['id_location']}</td>
        <td>{$row['location']}</td>
        <td>{$row['lwbp_awal']}</td>
        <td>{$row['lwbp_akhir']}</td>
        <td>{$row['lwbp_consumpt']}</td>
        <td>{$row['lwbp_tarif']}</td>
        <td>{$row['wbp_awal']}</td>
        <td>{$row['wbp_akhir']}</td>
        <td>{$row['wbp_consumpt']}</td>
        <td>{$row['wbp_tarif']}</td>
        <td>" . date('Y-m-d', strtotime($tanggal_final)) . "</td>
    </tr>";
}
echo "</table>";
?>
