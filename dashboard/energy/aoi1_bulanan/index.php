<?php
$host     = "192.168.51.40";
$user     = "remote";
$password = "Automation@321.";
$database = "enviro";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Bulan kemarin
$bulan_kemarin = date('Y-m', strtotime('first day of last month'));
//$bulan_kemarin = date('Y-m', strtotime('first day of -2 month'));

// Ambil lokasi
$sqlLocations = "SELECT DISTINCT id_location, location FROM electricity_aoi1_harian";
$resultLocations = $conn->query($sqlLocations);

if (!$resultLocations) {
    die("❌ Query lokasi gagal: " . $conn->error);
}

while ($row = $resultLocations->fetch_assoc()) {

    $id_location = $row['id_location'];
    $location    = $row['location'];

    // ===== CEK DATA SUDAH ADA =====
    $cek = $conn->prepare("
        SELECT 1 FROM bulan 
        WHERE id_location = ? AND bulan = ? 
        LIMIT 1
    ");
    $cek->bind_param("ss", $id_location, $bulan_kemarin);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        echo "⏩ Data SUDAH ADA untuk lokasi: $id_location ($bulan_kemarin)<br>";
        continue;
    }
    $cek->close();

    // ===== REKAP HARIAN =====
    $rekap = $conn->prepare("
        SELECT 
            COALESCE(SUM(lwbp1_consumpt + lwbp2_consumpt),0) AS total_lwbp,
            COALESCE(SUM(lwbp1_tarif + lwbp2_tarif),0) AS tarif_lwbp,
            COALESCE(SUM(wbp_consumpt),0) AS total_wbp,
            COALESCE(SUM(wbp_tarif),0) AS tarif_wbp,
            COALESCE(SUM(total_tarif),0) AS total_tarif
        FROM electricity_aoi1_harian
        WHERE id_location = ?
          AND DATE_FORMAT(tanggal, '%Y-%m') = ?
    ");

    $rekap->bind_param("ss", $id_location, $bulan_kemarin);
    $rekap->execute();
    $rekap->bind_result(
        $total_lwbp,
        $tarif_lwbp,
        $total_wbp,
        $tarif_wbp,
        $total_tarif
    );
    $rekap->fetch();
    $rekap->close();

    if ($total_tarif > 0) {

        $insert = $conn->prepare("
            INSERT INTO bulan (
                id_location, location, bulan,
                total_lwbp_consumpt, total_tarif_lwbp,
                total_wbp_consumpt, total_tarif_wbp,
                total_tarif
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->bind_param(
            "sssddddd",
            $id_location,
            $location,
            $bulan_kemarin,
            $total_lwbp,
            $tarif_lwbp,
            $total_wbp,
            $tarif_wbp,
            $total_tarif
        );

        if ($insert->execute()) {
            echo "✅ Berhasil untuk lokasi: $id_location ($location)<br>";
        } else {
            echo "❌ Gagal insert: $id_location - " . $insert->error . "<br>";
        }

        $insert->close();

    } else {
        echo "⚠️ Tidak ada data untuk $id_location di $bulan_kemarin<br>";
    }
}

$conn->close();
?>
