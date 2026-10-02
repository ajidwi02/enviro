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

// Format ID Transaction: [id_location]_[YYYYMMDD]_[HHmmss] (Contoh: 501_20261001_075100)
$time_code = date('Ymd_His', strtotime($record_time));
$id_transaction = isset($data['id_transaction']) && !empty($data['id_transaction']) ? $data['id_transaction'] : ($id_location . '_' . $time_code);

// 3. Validasi Koneksi Database
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit;
}

// 4. Simpan ke Tabel temp_humid
$safe_id_trans = mysqli_real_escape_string($conn, $id_transaction);
$safe_id_loc = mysqli_real_escape_string($conn, $id_location);
$safe_record_time = mysqli_real_escape_string($conn, $record_time);

$query = "INSERT INTO temp_humid (id_transaction, id_location, temp, humidity, record_time) 
          VALUES ('$safe_id_trans', '$safe_id_loc', $temp, $humidity, '$safe_record_time')";

$result = mysqli_query($conn, $query);

if ($result) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Data successfully saved into temp_humid',
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
