<?php
include '../config_electricity_aoi1.php';

$message = $_GET["message"] ?? null;

if (!$message) {
    die("Message parameter required");
}

echo "<br>Message = " . htmlspecialchars($message) . "<br>";

date_default_timezone_set("Asia/Jakarta");
$datenow  = date('Y-m-d H:i:s');
$datenow2 = date('Ymd_His');

$records = explode(",", $message);

// ==============================
// 1. Cek apakah semua record = 0
// ==============================
$allZero = true;

foreach ($records as $text) {
    $text = trim($text);
    if ($text === '') continue;

    $exp = explode("_", $text);
    if (count($exp) != 13) continue;

    for ($i=1; $i<13; $i++) {
        if (floatval($exp[$i]) != 0) {
            $allZero = false;
            break 2; // keluar dari kedua loop
        }
    }
}

if ($allZero) {
    echo "Semua record = 0 -> tidak di-insert<br>";
    exit;
}

// ==============================
// 2. Insert semua record
// ==============================
foreach ($records as $text) {
    $text = trim($text);
    if ($text === '') continue;

    $exp = explode("_", $text);
    if (count($exp) != 13) {
        echo "Format salah: " . htmlspecialchars($text) . "<br>";
        continue;
    }

    // mapping
    $id_location_raw = $exp[0];
    $voltage_r    = floatval($exp[1]);
    $voltage_s    = floatval($exp[2]);
    $voltage_t    = floatval($exp[3]);
    $voltage_rs   = floatval($exp[4]);
    $voltage_st   = floatval($exp[5]);
    $voltage_rt   = floatval($exp[6]);
    $current_r    = floatval($exp[7]);
    $current_s    = floatval($exp[8]);
    $current_t    = floatval($exp[9]);
    $power_factor = floatval($exp[10]);
    $power        = floatval($exp[11]);
    $kwh_total    = floatval($exp[12]);

    $id_location    = mysqli_real_escape_string($mysqli, $id_location_raw);
    $id_transaction = mysqli_real_escape_string($mysqli, $id_location . "_" . $datenow2);

    echo "Processing id_location=$id_location - Vr=$voltage_r - Vs=$voltage_s - Vt=$voltage_t - P=$power - KWh=$kwh_total - Date=$datenow<br>";

    // ==============================
    // cek record terakhir per lokasi
    // ==============================
    $sql_select = "SELECT id_transaction, datenow, TIMESTAMPDIFF(SECOND, datenow, NOW()) AS gap
                   FROM electricity_aoi1_reporting
                   WHERE id_lokasi = '$id_location'
                   ORDER BY datenow DESC LIMIT 1";

    $result_select = mysqli_query($mysqli, $sql_select);

    if ($result_select && mysqli_num_rows($result_select) > 0) {
        $row_last = mysqli_fetch_assoc($result_select);
        $gap = $row_last['gap'];

        if ($gap < 50) {
            echo "Last record is $gap seconds ago.. skipped insert<br>";
            continue;
        }
    }

    // ==============================
    // INSERT record
    // ==============================
    $sql_insert = "INSERT INTO electricity_aoi1_reporting 
        (id_transaction, id_lokasi, voltage_r, voltage_s, voltage_t, voltage_rs, voltage_st, voltage_rt,
         current_r, current_s, current_t, power_factor, power, kwh_total, datenow)
        VALUES (
         '$id_transaction','$id_location',$voltage_r,$voltage_s,$voltage_t,$voltage_rs,$voltage_st,$voltage_rt,
         $current_r,$current_s,$current_t,$power_factor,$power,$kwh_total,'$datenow'
        )";

    $result = mysqli_query($mysqli, $sql_insert);

    if ($result) {
        echo "Record id_location=$id_location inserted successfully<br>";
    } else {
        echo "Insert failed for id_location=$id_location: " . mysqli_error($mysqli) . "<br>";
    }
}
?>
