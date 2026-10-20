<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config_temp_humid.php';

date_default_timezone_set("Asia/Jakarta");

// 1. Tangkap Payload (JSON, Form POST, atau GET)
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    $data = !empty($_POST) ? $_POST : $_GET;
}

if (empty($data)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No data payload received'
    ]);
    exit;
}

// 2. Ekstrak dan Normalisasi Field
$raw_location = isset($data['id_location']) ? trim($data['id_location']) : (isset($data['sensor']) ? trim($data['sensor']) : '');

if (empty($raw_location)) {
    if (!empty($data['_groupName'])) {
        $raw_location = trim($data['_groupName']);
    } elseif (!empty($data['topic'])) {
        $raw_location = trim($data['topic']);
    } else {
        $raw_location = '501';
    }
}

// Mapping nama sensor area501/area502/temp1/temp2/Area_1/Area_2 ke ID numeric
$loc_lower = strtolower($raw_location);
if (strpos($loc_lower, '501') !== false || strpos($loc_lower, 'temp1') !== false || strpos($loc_lower, 'area_1') !== false || strpos($loc_lower, 'area1') !== false) {
    $id_location = '501';
} elseif (strpos($loc_lower, '502') !== false || strpos($loc_lower, 'temp2') !== false || strpos($loc_lower, 'area_2') !== false || strpos($loc_lower, 'area2') !== false) {
    $id_location = '502';
} else {
    $id_location = $raw_location;
}

$temp = isset($data['temp']) ? floatval($data['temp']) : (isset($data['temper']) ? floatval($data['temper']) : 0.0);
$humidity = isset($data['humidity']) ? floatval($data['humidity']) : (isset($data['humidi']) ? floatval($data['humidi']) : 0.0);

// Normalisasi Record Time
if (!empty($data['_terminalTime'])) {
    $record_time = date('Y-m-d H:i:s', strtotime($data['_terminalTime']));
} elseif (!empty($data['record_time'])) {
    $record_time = date('Y-m-d H:i:s', strtotime($data['record_time']));
} else {
    $record_time = date('Y-m-d H:i:s');
}

// Format ID Transaction per jam: [id_location]_[YYYYMMDD_HH]0000 (Contoh: 501_20261020_090000)
$time_hourly = date('Ymd_H', strtotime($record_time)) . '0000';
$id_transaction = isset($data['id_transaction']) && !empty($data['id_transaction']) ? $data['id_transaction'] : ($id_location . '_' . $time_hourly);

// 3. Validasi Koneksi Database
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit;
}

$safe_id_trans = mysqli_real_escape_string($conn, $id_transaction);
$safe_id_loc = mysqli_real_escape_string($conn, $id_location);
$safe_record_time = mysqli_real_escape_string($conn, $record_time);

// 4. Throttling Penyimpanan 1 Jam (Hourly Guard)
// Cek data terakhir dari sensor ini. Jika selisihnya kurang dari 1 jam (3600 detik), abaikan insert baru.
$is_forced = isset($data['force']) && ($data['force'] === true || $data['force'] === 'true' || $data['force'] === 1);
if (!$is_forced) {
    $check_query = "SELECT record_time FROM temp_humid 
                    WHERE id_location = '$safe_id_loc' 
                    ORDER BY record_time DESC 
                    LIMIT 1";
    $check_res = mysqli_query($conn, $check_query);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        $last_row = mysqli_fetch_assoc($check_res);
        $last_timestamp = strtotime($last_row['record_time']);
        $curr_timestamp = strtotime($record_time);
        $diff_seconds = abs($curr_timestamp - $last_timestamp);

        // Jika kurang dari 1 jam (3600 detik), skip penyimpanan
        if ($diff_seconds < 3600) {
            echo json_encode([
                'status' => 'skipped',
                'message' => 'Hourly throttle: Data already saved within the last 1 hour (' . round($diff_seconds / 60) . ' mins ago). Next save in ' . round((3600 - $diff_seconds) / 60) . ' mins.',
                'data' => [
                    'id_location'      => $safe_id_loc,
                    'last_record_time' => $last_row['record_time'],
                    'current_time'     => $safe_record_time
                ]
            ]);
            exit;
        }
    }
}

// 5. Simpan ke Tabel temp_humid dengan ON DUPLICATE KEY UPDATE
$query = "INSERT INTO temp_humid (id_transaction, id_location, temp, humidity, record_time) 
          VALUES ('$safe_id_trans', '$safe_id_loc', $temp, $humidity, '$safe_record_time')
          ON DUPLICATE KEY UPDATE 
            temp = VALUES(temp), 
            humidity = VALUES(humidity), 
            record_time = VALUES(record_time)";

$result = mysqli_query($conn, $query);

if ($result) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Data successfully saved into temp_humid (Hourly Snapshot)',
        'data' => [
            'id_transaction' => $safe_id_trans,
            'id_location'    => $safe_id_loc,
            'temp'           => $temp,
            'humidity'       => $humidity,
            'record_time'    => $safe_record_time
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database insert error: ' . mysqli_error($conn),
        'query'   => $query
    ]);
}
?>
