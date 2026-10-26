<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include __DIR__ . '/../config_temp_humid.php';
require_once __DIR__ . '/weather_helper.php';

$force_refresh = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');
$weather_data = sync_weather_aoi5($conn, $force_refresh);

echo json_encode([
    'status' => 'success',
    'data'   => $weather_data
], JSON_PRETTY_PRINT);
