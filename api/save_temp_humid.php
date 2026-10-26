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

function log_webhook($status, $details, $raw_input = '') {
    $log_file = __DIR__ . '/webhook_debug.log';
    if (file_exists($log_file) && filesize($log_file) > 1000000) {
        @unlink($log_file);
    }
    $time = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI/UNKNOWN';
    $raw_snippet = substr(str_replace(["\r", "\n", "\t"], ' ', (string)$raw_input), 0, 200);
    $entry = "[$time] [IP: $ip] [$status] $details | RAW: $raw_snippet\n";
    @file_put_contents($log_file, $entry, FILE_APPEND);
}

// 1. Tangkap Payload (JSON, EMQX Webhook Data Bridge, Form POST, atau GET)
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// Dukungan jika raw_input berupa string JSON yang di-encode base64 secara utuh
if (!$data && is_string($raw_input) && strlen(trim($raw_input)) > 0) {
    $b64_raw = base64_decode(trim($raw_input), true);
    if ($b64_raw !== false) {
        $data = json_decode($b64_raw, true);
    }
}

if (!$data) {
    $data = !empty($_POST) ? $_POST : $_GET;
}

if (empty($data)) {
    log_webhook('EMPTY', 'No data payload received', $raw_input);
    echo json_encode([
        'status' => 'error',
        'message' => 'No data payload received'
    ]);
    exit;
}

// 1.1 Unwrapping jika payload dibungkus oleh EMQX Data Bridge / Rule Engine
// Format EMQX seringkali: {"payload": {...}, "topic": "..."} atau {"payload": "{...}"} atau {"payload": "Base64..."}
if (isset($data['payload'])) {
    if (is_string($data['payload'])) {
        $unwrapped = json_decode($data['payload'], true);
        if (!is_array($unwrapped)) {
            // Cek apakah payload merupakan string Base64 dari EMQX v5
            $b64 = base64_decode($data['payload'], true);
            if ($b64 !== false) {
                $unwrapped = json_decode($b64, true);
            }
        }
        if (is_array($unwrapped)) {
            $data = array_merge($data, $unwrapped);
        }
    } elseif (is_array($data['payload'])) {
        $data = array_merge($data, $data['payload']);
    }
}

// 2. Ekstrak dan Normalisasi Field Lokasi / Sensor
$raw_location = '';
if (!empty($data['id_location'])) {
    $raw_location = trim($data['id_location']);
} elseif (!empty($data['sensor'])) {
    $raw_location = trim($data['sensor']);
} elseif (!empty($data['_groupName'])) {
    $raw_location = trim($data['_groupName']);
} elseif (!empty($data['topic'])) {
    $raw_location = trim($data['topic']);
} elseif (!empty($_GET['topic'])) {
    $raw_location = trim($_GET['topic']);
} elseif (!empty($_GET['sensor'])) {
    $raw_location = trim($_GET['sensor']);
}

// Mapping nama sensor area501/area502/Area_1/Area_2 ke ID numeric (501 / 502)
$loc_lower = strtolower($raw_location);
if (strpos($loc_lower, '502') !== false || strpos($loc_lower, 'area_2') !== false || strpos($loc_lower, 'area2') !== false) {
    $id_location = '502';
} elseif (strpos($loc_lower, '501') !== false || strpos($loc_lower, 'area_1') !== false || strpos($loc_lower, 'area1') !== false) {
    $id_location = '501';
} else {
    // Tolak jika bukan sensor milik AOI 5
    log_webhook('IGNORED', "Location '$raw_location' not recognized for AOI 5", $raw_input);
    echo json_encode([
        'status' => 'ignored',
        'message' => 'Sensor location not recognized for AOI 5 (expected 501/Area_1 or 502/Area_2)',
        'received_location' => $raw_location
    ]);
    exit;
}

// 2.1 Ekstrak Nilai Suhu (Temperature)
$temp = null;
if (isset($data['temper']) && is_numeric($data['temper'])) {
    $temp = floatval($data['temper']);
} elseif (isset($data['temp']) && is_numeric($data['temp'])) {
    $temp = floatval($data['temp']);
} elseif (isset($data['temperature']) && is_numeric($data['temperature'])) {
    $temp = floatval($data['temperature']);
}

// 2.2 Ekstrak Nilai Kelembapan (Humidity)
$humidity = null;
if (isset($data['humidi']) && is_numeric($data['humidi'])) {
    $humidity = floatval($data['humidi']);
} elseif (isset($data['humidity']) && is_numeric($data['humidity'])) {
    $humidity = floatval($data['humidity']);
} elseif (isset($data['hum']) && is_numeric($data['hum'])) {
    $humidity = floatval($data['hum']);
} elseif (isset($data['rh']) && is_numeric($data['rh'])) {
    $humidity = floatval($data['rh']);
} elseif (isset($data['kelembapan']) && is_numeric($data['kelembapan'])) {
    $humidity = floatval($data['kelembapan']);
}

// Jika keduanya null, berikan respon error
if ($temp === null && $humidity === null) {
    log_webhook('INVALID_METRIC', "No valid temperature or humidity found in payload", $raw_input);
    echo json_encode([
        'status' => 'error',
        'message' => 'No valid temperature or humidity values found in payload',
        'received' => $data
    ]);
    exit;
}

$temp_val = ($temp !== null) ? round($temp, 1) : 0.0;
$humidity_val = ($humidity !== null) ? round($humidity, 1) : 0.0;

// 2.3 Normalisasi Record Time
if (!empty($data['_terminalTime'])) {
    $ts = strtotime($data['_terminalTime']);
    $record_time = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
} elseif (!empty($data['record_time'])) {
    $ts = strtotime($data['record_time']);
    $record_time = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
} elseif (!empty($data['timestamp'])) {
    $raw_ts = $data['timestamp'];
    $ts = is_numeric($raw_ts) ? (strlen((string)$raw_ts) > 10 ? floor($raw_ts / 1000) : $raw_ts) : strtotime($raw_ts);
    $record_time = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
} else {
    $record_time = date('Y-m-d H:i:s');
}

// Format ID Transaction per jam: [id_location]_[YYYYMMDD_HH]0000 (Contoh: 501_20261020_090000)
$time_hourly = date('Ymd_H', strtotime($record_time)) . '0000';
$id_transaction = isset($data['id_transaction']) && !empty($data['id_transaction']) ? $data['id_transaction'] : ($id_location . '_' . $time_hourly);

// 3. Validasi Koneksi Database
if (!$conn) {
    log_webhook('DB_ERROR', 'Database connection failed: ' . mysqli_connect_error(), $raw_input);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit;
}

$safe_id_trans = mysqli_real_escape_string($conn, $id_transaction);
$safe_id_loc = mysqli_real_escape_string($conn, $id_location);
$safe_record_time = mysqli_real_escape_string($conn, $record_time);

// 3.1 Background 24/7 Server-Side Weather Ingestion (Solusi 1)
// Sinkronkan data cuaca luar per jam secara mandiri di server tanpa butuh browser
if ($conn) {
    require_once __DIR__ . '/weather_helper.php';
    try {
        sync_weather_aoi5($conn, false);
    } catch (Throwable $e) {
        // Jangan biarkan error cuaca mengganggu proses utama sensor suhu
    }
}

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

        // Abaikan jika timestamp payload lebih lampau atau sama dengan data terakhir di database
        if ($curr_timestamp <= $last_timestamp) {
            log_webhook('SKIPPED_STALE', "Stale data for Loc $safe_id_loc (current: $safe_record_time <= last: " . $last_row['record_time'] . ")", $raw_input);
            echo json_encode([
                'status' => 'skipped',
                'message' => 'Stale data: Payload timestamp is older than or equal to last record in database',
                'data' => [
                    'id_location'      => $safe_id_loc,
                    'last_record_time' => $last_row['record_time'],
                    'current_time'     => $safe_record_time
                ]
            ]);
            exit;
        }

        $diff_seconds = $curr_timestamp - $last_timestamp;

        // Jika kurang dari 1 jam (3600 detik), skip penyimpanan
        if ($diff_seconds < 3600) {
            log_webhook('SKIPPED_THROTTLE', "Hourly throttle for Loc $safe_id_loc (" . round($diff_seconds / 60) . "m ago < 60m)", $raw_input);
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
          VALUES ('$safe_id_trans', '$safe_id_loc', $temp_val, $humidity_val, '$safe_record_time')
          ON DUPLICATE KEY UPDATE 
            temp = IF($temp_val > 0, VALUES(temp), temp), 
            humidity = IF($humidity_val > 0, VALUES(humidity), humidity), 
            record_time = VALUES(record_time)";

$result = mysqli_query($conn, $query);

if ($result) {
    log_webhook('SUCCESS', "Saved Loc $safe_id_loc | Temp: $temp_val | Hum: $humidity_val | Time: $safe_record_time", $raw_input);
    echo json_encode([
        'status' => 'success',
        'source' => isset($data['topic']) ? 'emqx_data_bridge' : 'client_api',
        'message' => 'Data successfully saved into temp_humid (Hourly Snapshot)',
        'data' => [
            'id_transaction' => $safe_id_trans,
            'id_location'    => $safe_id_loc,
            'temp'           => $temp_val,
            'humidity'       => $humidity_val,
            'record_time'    => $safe_record_time
        ]
    ]);
} else {
    log_webhook('INSERT_ERROR', "MySQL Error: " . mysqli_error($conn) . " | SQL: $query", $raw_input);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database insert error: ' . mysqli_error($conn),
        'query'   => $query
    ]);
}
?>
