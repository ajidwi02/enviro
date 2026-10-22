<?php
/**
 * plts_data.php
 * ---------------------------------------------------------------------------
 * Service layer (data access) untuk "PLTS Dashboard -AOI2-".
 *
 * Sumber data : MySQL database `enviro` -> tabel `electricity`
 *               filter id_location = '20105' (PLTS / RTS AOI2)
 *
 * Dipakai oleh:
 *   1. dashboard/energy/aoi2/plts.php               (render pertama)
 *   2. dashboard/energy/aoi2/api_plts_realtime.php  (refresh AJAX 60 detik)
 *
 * ATURAN:
 *   - File ini TIDAK boleh berisi data dummy.
 *   - Semua query WAJIB memfilter id_location (gunakan PLTS_ID_LOCATION).
 *   - Jangan mengubah isi config_electricity.php.
 * ---------------------------------------------------------------------------
 */

if (!defined('PLTS_DATA_LOADED')) {
    define('PLTS_DATA_LOADED', true);

    // Zona waktu: kolom `datenow` di database menggunakan WIB (Asia/Jakarta).
    date_default_timezone_set('Asia/Jakarta');

    /* ===================================================================
     * KONFIGURASI (ubah HANYA di blok ini)
     * =================================================================== */

    // id_location PLTS di tabel `electricity`
    define('PLTS_ID_LOCATION', '20105');

    // Satuan label "Power Output" pada kotak Informasi Listrik.
    //   'kW' = kilowatt (sesuai pemakaian di Energy Dashboard AOI2 & standard_power)
    //   'W'  = watt
    define('PLTS_POWER_UNIT', 'kW');

    // Satuan label "Consumption" pada kotak Informasi Listrik.
    // Kolom `consumpt` adalah selisih kWh meter -> satuannya kWh.
    define('PLTS_CONSUMPTION_UNIT', 'kWh');

    /* -------------------------------------------------------------------
     * MODE "Total KWH" (Rev 1.2 - berubah dari SUM per hari ke REALTIME)
     * -------------------------------------------------------------------
     *   'last_row' = 1 data terbaru saja (realtime, sama seperti gauge chart)
     *                -> nilai = akumulasi meter (kWh kumulatif)
     *   'sum_kwh'  = SUM(kwh) hari ini (00:00 -> data terakhir)
     *                -> mode lama, menghasilkan angka sangat besar
     *                -> HANYA bila data historis dibutuhkan
     * ------------------------------------------------------------------- */
    define('PLTS_TOTAL_KWH_MODE', 'last_row');

    /* -------------------------------------------------------------------
     * MODE "Consumption" (Rev 1.2, disempurnakan Rev 1.3)
     * -------------------------------------------------------------------
     *   'sanitized'    = DEFAULT. Selisih meter kWh pada baris VALID saja;
     *                    anomali meter reset / gangguan komunikasi dibuang.
     *                    Lihat plts_get_consumption_sanitized().
     *   'sum_consumpt' = SUM(consumpt) mentah (tanpa sanitasi)
     *   'kwh_delta'    = MAX(kwh) - MIN(kwh) hari ini
     * ------------------------------------------------------------------- */
    define('PLTS_CONSUMPTION_MODE', 'sanitized');

    /* ===================================================================
     * KONEKSI DATABASE
     * =================================================================== */
    try {
        require_once __DIR__ . '/../../../config_electricity.php';
    } catch (Throwable $e) {
        // PHP 8.1+ melempar exception saat koneksi gagal.
        $GLOBALS['PLTS_DB_ERROR'] = 'Koneksi database gagal: ' . $e->getMessage();
    }
}

/**
 * Cek apakah koneksi database tersedia.
 * @return bool
 */
function plts_db_is_connected($mysqli = null)
{
    if ($mysqli === null) {
        $mysqli = isset($GLOBALS['mysqli']) ? $GLOBALS['mysqli'] : null;
    }
    if (!($mysqli instanceof mysqli)) {
        return false;
    }
    return mysqli_connect_errno() === 0;
}

/**
 * Ubah nilai DB menjadi float, atau null bila kosong / bukan angka.
 * Penting: kolom decimal yang NULL harus tetap null (bukan 0),
 * agar UI bisa menampilkan "—" dan tidak menampilkan angka palsu.
 * @param mixed $value
 * @return float|null
 */
function plts_num_or_null($value)
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

/**
 * Format angka untuk tampilan (pemisah ribuan ",", desimal ".").
 * Mengembalikan "-" bila nilai null / bukan angka.
 * @param mixed $value
 * @param int   $decimals
 * @return string
 */
function plts_fmt_num($value, $decimals = 2)
{
    if (!is_numeric($value)) {
        return '-';
    }
    return number_format((float) $value, $decimals);
}

/**
 * Ambil 1 baris data realtime terakhir untuk id_location PLTS.
 * @param mysqli $mysqli
 * @return array|null
 */
function plts_get_latest_row($mysqli)
{
    $id = mysqli_real_escape_string($mysqli, PLTS_ID_LOCATION);

    $sql = "SELECT voltage_r, voltage_s, voltage_t,
                   current_r, current_s, current_t,
                   power, kwh, consumpt, datenow
            FROM electricity
            WHERE id_location = '$id'
            ORDER BY datenow DESC
            LIMIT 1";

    $result = mysqli_query($mysqli, $sql);
    if ($result === false) {
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    return $row ? $row : null;
}

/**
 * Ambil agregat data hari ini (00:00 -> data terakhir hari ini).
 * @param mysqli $mysqli
 * @return array|null
 */
function plts_get_today_aggregate($mysqli)
{
    $id = mysqli_real_escape_string($mysqli, PLTS_ID_LOCATION);

    $sql = "SELECT COUNT(*)        AS jml_record,
                   SUM(power)      AS sum_power,
                   SUM(kwh)        AS sum_kwh,
                   SUM(consumpt)   AS sum_consumpt,
                   MAX(kwh)        AS max_kwh,
                   MIN(kwh)        AS min_kwh,
                   MAX(datenow)    AS last_update
            FROM electricity
            WHERE id_location = '$id'
              AND CAST(datenow AS DATE) = CURDATE()";

    $result = mysqli_query($mysqli, $sql);
    if ($result === false) {
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    return $row ? $row : null;
}

/**
 * Hitung Consumption hari ini dengan SANITASI anomali meter.
 *
 * Latar belakang (terverifikasi pada data `electricity` id_location 20105):
 * kolom `consumpt` dihitung saat ingest sebagai `kwh_sekarang - kwh_sebelumnya`.
 * Karena itu 2 jenis gangguan membuat kolom ini tidak reliable:
 *
 *   1. GLITCH : satu baris reading berisi `kwh = 0` (komunikasi terputus /
 *               meter reset). Efeknya sepasang nilai: satu -1.4 juta dan satu
 *               +1.4 juta. Kalau keduanya jatuh di hari yang sama -> saling
 *               membatalkan (tidak terlihat). Kalau melintasi batas hari ->
 *               total hari tersebut RUSAK parah (terbukti -1.398.472 kWh).
 *
 *   2. STALE  : meter melaporkan nilai `kwh` yang MUNDUR (nilai basi), lalu
 *               kembali ke nilai benar pada reading berikutnya.
 *
 * Algoritma (max-tracking):
 *   - Baris `kwh <= 0`       -> dilewati, acuan (prev) TIDAK digeser.
 *   - Baris `kwh < prev`     -> dianggap STALE, delta diabaikan, prev TIDAK
 *                               digeser (agar kompensasi saat meter kembali
 *                               ke nilai benar tidak terhitung 2 kali).
 *   - Selain itu              -> delta = kwh - prev, dijumlahkan; prev diupdate.
 *
 * Data 1 hari sebelum hari ini ikut diambil sebagai konteks, agar delta
 * "carry-over" (kWh sejak reading terakhir kemarin) ikut terhitung sehingga
 * hasilnya tetap bermakna sebagai "kWh sejak 00:00 hari ini".
 *
 * @param mysqli $mysqli
 * @return array{value:float, anomali:int, jml_valid:int}
 */
function plts_get_consumption_sanitized($mysqli)
{
    $fallback = array('value' => 0.0, 'anomali' => 0, 'jml_valid' => 0);

    $id = mysqli_real_escape_string($mysqli, PLTS_ID_LOCATION);

    $sql = "SELECT datenow, kwh, consumpt, (DATE(datenow) = CURDATE()) AS is_today
            FROM electricity
            WHERE id_location = '$id'
              AND datenow >= (CURDATE() - INTERVAL 1 DAY)
              AND datenow <= NOW()
            ORDER BY datenow ASC";

    $result = mysqli_query($mysqli, $sql);
    if ($result === false) {
        return $fallback;
    }

    $prev     = null;   // kWh tepercaya terakhir (acuan)
    $total    = 0.0;    // akumulasi delta hari ini
    $anomali  = 0;      // jumlah baris bermasalah hari ini
    $jmlValid = 0;      // jumlah baris valid hari ini

    while ($row = mysqli_fetch_assoc($result)) {
        $isToday = ((int)$row['is_today'] === 1);
        $kwh     = plts_num_or_null($row['kwh']);

        if ($kwh === null || $kwh <= 0) {
            // GLITCH: reading 0 / tidak terbaca -> abaikan, jangan geser acuan
            if ($isToday) {
                $anomali++;
            }
            continue;
        }

        if ($prev === null) {
            $prev = $kwh;                 // baris pertama = acuan awal
            if ($isToday) {
                $jmlValid++;
            }
            continue;
        }

        $delta = $kwh - $prev;

        if ($delta < 0) {
            // STALE: meter mundur -> abaikan, acuan tidak digeser
            if ($isToday) {
                $anomali++;
            }
            continue;
        }

        if ($isToday) {
            $total    += $delta;
            $jmlValid++;
        }
        $prev = $kwh;
    }
    mysqli_free_result($result);

    return array(
        'value'     => $total,
        'anomali'   => $anomali,
        'jml_valid' => $jmlValid,
    );
}

/**
 * Susun payload lengkap untuk ditampilkan oleh halaman & endpoint JSON.
 * @param mysqli $mysqli
 * @return array
 */
function plts_build_payload($mysqli)
{
    $latest = plts_get_latest_row($mysqli);
    $agg    = plts_get_today_aggregate($mysqli);

    $sumPower    = $agg ? (float)($agg['sum_power']    ?? 0) : 0.0;
    $sumKwh      = $agg ? (float)($agg['sum_kwh']      ?? 0) : 0.0;
    $sumConsumpt = $agg ? (float)($agg['sum_consumpt'] ?? 0) : 0.0;
    $maxKwh      = $agg ? (float)($agg['max_kwh']      ?? 0) : 0.0;
    $minKwh      = $agg ? (float)($agg['min_kwh']      ?? 0) : 0.0;
    $jmlRecord   = $agg ? (int)($agg['jml_record']     ?? 0) : 0;

    // --- Total KWH (Rev 1.2) ------------------------------------------------
    // Mode default 'last_row': 1 data terbaru (realtime) -> akumulasi meter.
    switch (PLTS_TOTAL_KWH_MODE) {
        case 'sum_kwh':
            $kwhTotal = $sumKwh;
            break;
        case 'last_row':
        default:
            $kwhTotal = plts_num_or_null($latest['kwh'] ?? null);
            if ($kwhTotal === null) {
                $kwhTotal = 0.0;
            }
            break;
    }

    // --- Consumption (Rev 1.2 / 1.3) ------------------------------------------
    // Default 'sanitized': selisih meter pada baris valid, anomali dibuang.
    $sanitized   = plts_get_consumption_sanitized($mysqli);
    $jmlAnomali  = 0;

    switch (PLTS_CONSUMPTION_MODE) {
        case 'sum_consumpt':
            // SUM mentah - tetap tersedia untuk perbandingan
            $consumption = $sumConsumpt;
            $jmlAnomali  = 0;
            break;
        case 'kwh_delta':
            // Selisih meter mentah - TIDAK tahan anomali (baris kwh=0 bikin
            // MIN=0 sehingga hasilnya meledak, lihat dokumen Bab 3.4)
            $consumption = $maxKwh - $minKwh;
            if ($consumption < 0) {
                $consumption = 0.0;
            }
            $jmlAnomali  = 0;
            break;
        case 'sanitized':
        default:
            $consumption = $sanitized['value'];
            $jmlAnomali  = (int)$sanitized['anomali'];
            break;
    }

    $lastUpdate = $latest['datenow'] ?? ($agg['last_update'] ?? null);

    $data = array(
        // --- Tegangan 1 Phasa (REALTIME: baris terakhir) ---
        'voltage_r' => plts_num_or_null($latest['voltage_r'] ?? null),
        'voltage_s' => plts_num_or_null($latest['voltage_s'] ?? null),
        'voltage_t' => plts_num_or_null($latest['voltage_t'] ?? null),

        // --- Tegangan 3 Phasa: TIDAK ADA di tabel `electricity` -> selalu null ---
        // (Section card "3 Phasa (Antar Fasa)" sudah dihapus dari tampilan,
        //  key tetap dikirim agar kontrak API tidak berubah.)
        'voltage_rs' => null,
        'voltage_st' => null,
        'voltage_rt' => null,

        // --- Arus R/S/T (REALTIME: baris terakhir) ---
        'current_r' => plts_num_or_null($latest['current_r'] ?? null),
        'current_s' => plts_num_or_null($latest['current_s'] ?? null),
        'current_t' => plts_num_or_null($latest['current_t'] ?? null),

        // --- Informasi Listrik ---
        // Power Factor DIHAPUS (kolom power_factor selalu NULL utk 20105)
        // -> diganti "Consumption" (kWh) dari SUM(consumpt) hari ini
        'consumption'  => $consumption,
        'power'        => $sumPower,  // SUM(power) hari ini
        'kwh_total'    => $kwhTotal,  // realtime: kWh baris terakhir (akumulasi meter)
    );

    $meta = array(
        'id_location'         => PLTS_ID_LOCATION,
        'jml_record_hari_ini' => $jmlRecord,
        'sum_power'           => $sumPower,
        'sum_kwh'             => $sumKwh,
        'sum_consumpt'        => $sumConsumpt,
        'max_kwh'             => $maxKwh,
        'min_kwh'             => $minKwh,
        'kwh_latest'          => plts_num_or_null($latest['kwh'] ?? null),
        'kwh_delta_hari_ini'  => $maxKwh - $minKwh,
        'consumption'         => $consumption,
        'consumpt_mode'       => PLTS_CONSUMPTION_MODE,
        'jml_anomali'         => $jmlAnomali,
        'jml_valid_hari_ini'  => (int)$sanitized['jml_valid'],
        'total_kwh_mode'      => PLTS_TOTAL_KWH_MODE,
        'power_unit'          => PLTS_POWER_UNIT,
        'consumpt_unit'       => PLTS_CONSUMPTION_UNIT,
        'last_update_db'      => $lastUpdate,
        'last_update_display' => $lastUpdate
                                 ? date('d-m-Y H:i:s', strtotime($lastUpdate)) . ' WIB'
                                 : '-',
        'server_time'         => date('d-m-Y H:i:s'),
    );

    return array(
        'success' => true,
        'data'    => $data,
        'meta'    => $meta,
    );
}
