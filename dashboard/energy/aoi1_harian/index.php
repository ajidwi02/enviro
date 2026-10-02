<?php
$host = "192.168.51.40";
$user = "remote";
$password = "Automation@321.";
$database = "enviro";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("❌ Koneksi gagal: " . mysqli_connect_error());
}

$tanggal_kemarin = date('Y-m-d', strtotime('-1 day'));

$sqlLocations = "SELECT id_location, location FROM loc_electricity_aoi1";
$resultLocations = mysqli_query($conn, $sqlLocations);

if (!$resultLocations) {
    die("❌ Query lokasi gagal: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($resultLocations)) {
    $id_location = $row['id_location'];
    $location = $row['location'];

    $cekQuery = "SELECT COUNT(*) as jumlah FROM harian WHERE id_location = '$id_location' AND tanggal = '$tanggal_kemarin'";
    $cekResult = mysqli_query($conn, $cekQuery);
    $cekData = mysqli_fetch_assoc($cekResult);

    if ($cekData['jumlah'] > 0) {
        echo "⏩ Data sudah ada untuk lokasi: $id_location (tanggal: $tanggal_kemarin)<br>";
        continue;
    }

    $queryLwbp = "SELECT kwh_awal, kwh_akhir, consumpt, tarif 
                  FROM electricity_aoi1_lwbp 
                  WHERE id_location = '$id_location' 
                  AND DATE(datenow) = '$tanggal_kemarin'
                  ORDER BY datenow DESC LIMIT 1";
    $resultLwbp = mysqli_query($conn, $queryLwbp);
    $dataLwbp = mysqli_fetch_assoc($resultLwbp);

    $queryWbp = "SELECT kwh_awal, kwh_akhir, consumpt, tarif 
                 FROM electricity_aoi1_wbp 
                 WHERE id_location = '$id_location' 
                 AND DATE(datenow) = '$tanggal_kemarin'
                 ORDER BY datenow DESC LIMIT 1";
    $resultWbp = mysqli_query($conn, $queryWbp);
    $dataWbp = mysqli_fetch_assoc($resultWbp);

    if (!$dataLwbp || !$dataWbp) {
        echo "⚠️ Data tidak lengkap untuk lokasi: $id_location<br>";
        continue;
    }

    $lwbp_awal = $dataLwbp['kwh_awal'];
    $lwbp_akhir = $dataLwbp['kwh_akhir'];
    $lwbp_consumpt = $dataLwbp['consumpt'];
    $tarif_lwbp = $dataLwbp['tarif'];

    $wbp_awal = $dataWbp['kwh_awal'];
    $wbp_akhir = $dataWbp['kwh_akhir'];
    $wbp_consumpt = $dataWbp['consumpt'];
    $tarif_wbp = $dataWbp['tarif'];

    // ✅ Validasi nilai negatif jadi 0
    if ($lwbp_consumpt < 0) $lwbp_consumpt = 0;
    if ($wbp_consumpt < 0) $wbp_consumpt = 0;
    if ($tarif_lwbp < 0) $tarif_lwbp = 0;
    if ($tarif_wbp < 0) $tarif_wbp = 0;

    $total_tarif = $tarif_lwbp + $tarif_wbp;

    $sqlInsert = "INSERT INTO harian (
        id_location, location, tanggal,
        lwbp_awal, lwbp_akhir, lwbp_consumpt, tarif_lwbp,
        wbp_awal, wbp_akhir, wbp_consumpt, tarif_wbp, total_tarif
    ) VALUES (
        '$id_location', '$location', '$tanggal_kemarin',
        '$lwbp_awal', '$lwbp_akhir', '$lwbp_consumpt', '$tarif_lwbp',
        '$wbp_awal', '$wbp_akhir', '$wbp_consumpt', '$tarif_wbp', '$total_tarif'
    )";

    if (mysqli_query($conn, $sqlInsert)) {
        echo "✅ Data berhasil diinsert untuk lokasi: $id_location<br>";
    } else {
        echo "❌ Gagal insert data untuk lokasi: $id_location. Error: " . mysqli_error($conn) . "<br>";
    }
}

mysqli_close($conn);
?>
