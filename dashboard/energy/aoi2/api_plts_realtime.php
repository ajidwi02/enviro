<?php
/**
 * api_plts_realtime.php
 * ---------------------------------------------------------------------------
 * Endpoint JSON untuk auto-refresh "PLTS Dashboard -AOI2-".
 * Dipanggil oleh plts.php setiap 60 detik via fetch().
 *
 * Contoh akses:
 *   http://localhost/enviro/dashboard/energy/aoi2/api_plts_realtime.php
 *
 * Response (contoh):
 * {
 *   "success": true,
 *   "data": { "voltage_r": 231.93, ..., "power": 722.86, "kwh_total": 16798837 },
 *   "meta": { "last_update_display": "21-10-2026 10:57:17 WIB", ... }
 * }
 * ---------------------------------------------------------------------------
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/plts_data.php';

if (!plts_db_is_connected($mysqli)) {
    http_response_code(503);
    echo json_encode(array(
        'success' => false,
        'message' => isset($GLOBALS['PLTS_DB_ERROR'])
                     ? $GLOBALS['PLTS_DB_ERROR']
                     : 'Koneksi database tidak tersedia.',
    ));
    exit;
}

try {
    $payload = plts_build_payload($mysqli);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Gagal membaca data: ' . $e->getMessage(),
    ));
}
