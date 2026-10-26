<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

date_default_timezone_set("Asia/Jakarta");
include __DIR__ . '/../config_temp_humid.php';

// Hapus file json lama jika masih ada (agar tidak membebani server dengan file cache)
$old_cache_file = __DIR__ . '/cache_weather_aoi5.json';
if (file_exists($old_cache_file)) {
    @unlink($old_cache_file);
}

// Koordinat Pabrik AOI 5 (Kawasan Industri Wijayakusuma Semarang: 6°58'09.2"S 110°19'48.2"E)
$latitude      = -6.9692;
$longitude     = 110.3301;
$factory_name  = "AOI 5";
$location_desc = "Kawasan Industri Wijayakusuma, Semarang";

// Fungsi Klasifikasi 3 Status Cuaca (Standar Pabrik: Cerah, Mendung, Hujan - Tanpa Icon)
function classify_weather($weather_code, $precipitation, $rain, $cloud_cover) {
    $rain_codes = [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 85, 86, 95, 96, 99];
    
    // Prioritas 1: Hujan (jika curah hujan > 0 atau kode WMO hujan/gerimis/badai)
    if (floatval($rain) > 0 || floatval($precipitation) > 0 || in_array((int)$weather_code, $rain_codes)) {
        return 'Hujan';
    }
    
    // Prioritas 2: Mendung (jika kode awan tebal / kabut atau tutupan awan >= 50%)
    $cloudy_codes = [2, 3, 45, 48];
    if (in_array((int)$weather_code, $cloudy_codes) || intval($cloud_cover) >= 50) {
        return 'Mendung';
    }
    
    // Prioritas 3: Cerah
    return 'Cerah';
}

$force_refresh = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');
$weather_data = null;
$latest_db_row = null;

// 3. Cek data terakhir langsung dari tabel database weather_aoi5 (Pengganti cache JSON)
if ($conn) {
    try {
        $check_last_sql = "SELECT * FROM weather_aoi5 ORDER BY record_time DESC LIMIT 1";
        $check_last_res = @mysqli_query($conn, $check_last_sql);
        if ($check_last_res && mysqli_num_rows($check_last_res) > 0) {
            $latest_db_row = mysqli_fetch_assoc($check_last_res);
        }
    } catch (Throwable $e) {
        $latest_db_row = null;
    }
}

// Jika tidak dipaksa refresh dan data di tabel weather_aoi5 masih dalam 1 jam terakhir, gunakan langsung data dari database
if (!$force_refresh && $latest_db_row) {
    $last_time_ts = strtotime($latest_db_row['record_time']);
    $diff_seconds = time() - $last_time_ts;
    
    // Jika data disimpan kurang dari 1 jam (3600 detik), kembalikan data dari database (tanpa panggil API luar & tanpa file cache JSON)
    if ($diff_seconds < 3600) {
        $weather_data = [
            'factory'          => $factory_name,
            'location_desc'    => $location_desc,
            'coordinates'      => "{$latitude}, {$longitude}",
            'weather_status'   => $latest_db_row['weather_status'],
            'temp_outdoor'     => floatval($latest_db_row['temp_outdoor']),
            'humidity_outdoor' => floatval($latest_db_row['humidity_outdoor']),
            'rainfall'         => floatval($latest_db_row['rainfall']),
            'wind_speed'       => floatval($latest_db_row['wind_speed']),
            'cloud_cover'      => intval($latest_db_row['cloud_cover']),
            'weather_code'     => intval($latest_db_row['weather_code']),
            'record_time'      => $latest_db_row['record_time'],
            'last_updated'     => $latest_db_row['record_time'],
            'source'           => 'database_weather_aoi5',
            'db_save_status'   => 'already_saved_in_db'
        ];
    }
}

// 4. Jika data di database belum ada, sudah lewat 1 jam, atau user klik Refresh manual: Ambil dari Open-Meteo API
if (!$weather_data) {
    $api_url = "https://api.open-meteo.com/v1/forecast?latitude={$latitude}&longitude={$longitude}&current=temperature_2m,relative_humidity_2m,precipitation,rain,weather_code,cloud_cover,wind_speed_10m&timezone=Asia%2FJakarta";
    $api_response = false;
    
    // Coba via cURL jika tersedia
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $api_response = curl_exec($ch);
        curl_close($ch);
    }
    
    // Fallback file_get_contents jika cURL gagal
    if (!$api_response && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 5],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        $api_response = @file_get_contents($api_url, false, $ctx);
    }
    
    if ($api_response) {
        $parsed = json_decode($api_response, true);
        if (isset($parsed['current'])) {
            $curr = $parsed['current'];
            
            $w_code   = isset($curr['weather_code']) ? intval($curr['weather_code']) : 0;
            $precip   = isset($curr['precipitation']) ? floatval($curr['precipitation']) : 0.0;
            $rain_val = isset($curr['rain']) ? floatval($curr['rain']) : 0.0;
            $cloud    = isset($curr['cloud_cover']) ? intval($curr['cloud_cover']) : 0;
            $temp_out = isset($curr['temperature_2m']) ? round(floatval($curr['temperature_2m']), 1) : null;
            $hum_out  = isset($curr['relative_humidity_2m']) ? round(floatval($curr['relative_humidity_2m']), 1) : null;
            $wind_spd = isset($curr['wind_speed_10m']) ? round(floatval($curr['wind_speed_10m']), 1) : 0.0;
            
            $status_cuaca = classify_weather($w_code, $precip, $rain_val, $cloud);
            
            $rec_time = !empty($curr['time']) ? str_replace('T', ' ', $curr['time']) : date('Y-m-d H:i:s');
            if (strlen($rec_time) === 16) {
                $rec_time .= ':00'; // Format Y-m-d H:i:s
            }
            
            $weather_data = [
                'factory'          => $factory_name,
                'location_desc'    => $location_desc,
                'coordinates'      => "{$latitude}, {$longitude}",
                'weather_status'   => $status_cuaca, // Cerah / Mendung / Hujan
                'temp_outdoor'     => $temp_out,
                'humidity_outdoor' => $hum_out,
                'rainfall'         => $precip > 0 ? $precip : $rain_val,
                'wind_speed'       => $wind_spd,
                'cloud_cover'      => $cloud,
                'weather_code'     => $w_code,
                'record_time'      => $rec_time,
                'last_updated'     => date('Y-m-d H:i:s'),
                'source'           => 'open_meteo_live'
            ];
            
            // 5. Simpan langsung ke tabel weather_aoi5 di database (Tanpa File JSON)
            $db_save_status = 'no_connection';
            if ($conn) {
                $safe_status   = mysqli_real_escape_string($conn, $status_cuaca);
                $s_temp_out    = $temp_out !== null ? floatval($temp_out) : 0.0;
                $s_hum_out     = $hum_out !== null ? floatval($hum_out) : 0.0;
                $s_rain_val    = $precip > 0 ? floatval($precip) : floatval($rain_val);
                $s_wind_val    = floatval($wind_spd);
                $s_cloud_val   = intval($cloud);
                $s_wmo_code    = intval($w_code);
                $safe_rec_time = mysqli_real_escape_string($conn, $rec_time);
                
                // ID Transaksi per jam: AOI5_WTR_YYYYMMDD_HH0000
                $hourly_slot   = date('Ymd_H', strtotime($rec_time)) . '0000';
                $id_trans      = "AOI5_WTR_" . $hourly_slot;
                $safe_id_trans = mysqli_real_escape_string($conn, $id_trans);
                
                try {
                    $insert_query = "INSERT INTO weather_aoi5 
                        (id_transaction, factory, weather_status, temp_outdoor, humidity_outdoor, rainfall, wind_speed, cloud_cover, weather_code, record_time)
                        VALUES 
                        ('$safe_id_trans', '$factory_name', '$safe_status', $s_temp_out, $s_hum_out, $s_rain_val, $s_wind_val, $s_cloud_val, $s_wmo_code, '$safe_rec_time')
                        ON DUPLICATE KEY UPDATE
                            weather_status   = VALUES(weather_status),
                            temp_outdoor     = VALUES(temp_outdoor),
                            humidity_outdoor = VALUES(humidity_outdoor),
                            rainfall         = VALUES(rainfall),
                            wind_speed       = VALUES(wind_speed),
                            cloud_cover      = VALUES(cloud_cover),
                            weather_code     = VALUES(weather_code),
                            record_time      = VALUES(record_time)";
                            
                    $ins_res = @mysqli_query($conn, $insert_query);
                    if ($ins_res) {
                        $db_save_status = 'saved_success';
                    } else {
                        $db_save_status = 'error: ' . mysqli_error($conn);
                    }
                } catch (Throwable $e) {
                    $db_save_status = 'error: ' . $e->getMessage();
                }
            }
            $weather_data['db_save_status'] = $db_save_status;
        }
    }
}

// 6. Fallback jika Open-Meteo gagal dan belum dapat data: gunakan rekaman database terakhir
if (!$weather_data && $latest_db_row) {
    $weather_data = [
        'factory'          => $factory_name,
        'location_desc'    => $location_desc,
        'coordinates'      => "{$latitude}, {$longitude}",
        'weather_status'   => $latest_db_row['weather_status'],
        'temp_outdoor'     => floatval($latest_db_row['temp_outdoor']),
        'humidity_outdoor' => floatval($latest_db_row['humidity_outdoor']),
        'rainfall'         => floatval($latest_db_row['rainfall']),
        'wind_speed'       => floatval($latest_db_row['wind_speed']),
        'cloud_cover'      => intval($latest_db_row['cloud_cover']),
        'weather_code'     => intval($latest_db_row['weather_code']),
        'record_time'      => $latest_db_row['record_time'],
        'last_updated'     => $latest_db_row['record_time'],
        'source'           => 'fallback_database_weather_aoi5',
        'db_save_status'   => 'loaded_from_db'
    ];
}

// Default jika database masih kosong dan internet tidak tersambung
if (!$weather_data) {
    $weather_data = [
        'factory'          => $factory_name,
        'location_desc'    => $location_desc,
        'coordinates'      => "{$latitude}, {$longitude}",
        'weather_status'   => 'Cerah',
        'temp_outdoor'     => 30.0,
        'humidity_outdoor' => 60.0,
        'rainfall'         => 0.0,
        'wind_speed'       => 5.0,
        'cloud_cover'      => 20,
        'weather_code'     => 1,
        'record_time'      => date('Y-m-d H:i:s'),
        'last_updated'     => date('Y-m-d H:i:s'),
        'source'           => 'default_initial',
        'db_save_status'   => 'default'
    ];
}

echo json_encode([
    'status' => 'success',
    'data'   => $weather_data
], JSON_PRETTY_PRINT);
