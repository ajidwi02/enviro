<?php
// Konfigurasi koneksi langsung di sini:
$host = "192.168.51.40";
$user = "remote";
$password = "Automation@321.";
$database = "enviro";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Ambil tanggal dari input filter (jika ada)
$tanggal_terpilih = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Monitoring Konsumsi Listrik</title>
    <style>
        body {
            font-family: Arial;
            text-align: center;
        }
        table {
            margin: auto;
            border-collapse: collapse;
            width: 90%;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
        }
        th {
            background-color: #0074cc;
            color: white;
        }
        form {
            margin: 20px;
        }
        .export-buttons {
            margin: 10px;
        }
    </style>
</head>
<body>
    <h2>Monitoring Konsumsi Listrik Harian</h2>

    <!-- Filter tanggal -->
    <form method="get">
        <label for="tanggal">Pilih Tanggal: </label>
        <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal_terpilih) ?>">
        <button type="submit">Tampilkan</button>
    </form>

    <table>
        <tr>
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
            <th>Total Tarif</th>
            <th>Tanggal</th>
        </tr>

        <?php
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
                    ($tanggal_terpilih ? "AND DATE(datenow) = '$tanggal_terpilih'" : "") . "
                )
            LEFT JOIN electricity_aoi1_wbp wbp 
                ON wbp.id_location = loc.id_location 
                AND wbp.datenow = (
                    SELECT MAX(datenow) FROM electricity_aoi1_wbp 
                    WHERE id_location = loc.id_location " . 
                    ($tanggal_terpilih ? "AND DATE(datenow) = '$tanggal_terpilih'" : "") . "
                )
        ";

        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $lwbp_awal = max(0, $row['lwbp_awal']);
                $lwbp_akhir = max(0, $row['lwbp_akhir']);
                $lwbp_consumpt = max(0, $row['lwbp_consumpt']);
                $lwbp_tarif = max(0, $row['lwbp_tarif']);

                $wbp_awal = max(0, $row['wbp_awal']);
                $wbp_akhir = max(0, $row['wbp_akhir']);
                $wbp_consumpt = max(0, $row['wbp_consumpt']);
                $wbp_tarif = max(0, $row['wbp_tarif']);

                $total_tarif = $lwbp_tarif + $wbp_tarif;
        ?>
            <tr>
                <td><?= $row['id_location'] ?></td>
                <td><?= $row['location'] ?></td>
                <td><?= number_format($lwbp_awal, 0) ?></td>
                <td><?= number_format($lwbp_akhir, 0) ?></td>
                <td><?= number_format($lwbp_consumpt, 0) ?></td>
                <td><?= number_format($lwbp_tarif, 0) ?></td>
                <td><?= number_format($wbp_awal, 0) ?></td>
                <td><?= number_format($wbp_akhir, 0) ?></td>
                <td><?= number_format($wbp_consumpt, 0) ?></td>
                <td><?= number_format($wbp_tarif, 0) ?></td>
                <td><?= number_format($total_tarif, 0) ?></td>
                <td><?= date('Y-m-d', strtotime($row['tanggal_lwbp'] ?? $row['tanggal_wbp'])) ?></td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr><td colspan="12">Tidak ada data ditemukan</td></tr>
        <?php endif; ?>
    </table>

    <div class="export-buttons">
        <p><a href="export_excel.php?tanggal=<?= $tanggal_terpilih ?>">Export ke Excel</a> |
           <a href="export_pdf.php?tanggal=<?= $tanggal_terpilih ?>">Export ke PDF</a></p>
    </div>
</body>
</html>