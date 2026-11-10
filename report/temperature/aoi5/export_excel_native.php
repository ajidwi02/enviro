<?php
/**
 * ==============================================================================
 * Native Dynamic Excel Export Engine (PhpSpreadsheet)
 * Project: Enviro Monitoring System - AOI 5
 * Target: report/temperature/aoi5/export_excel_native.php
 * Author: PT Apparel One Indonesia
 * Description: Menghasilkan file Excel multi-sheet dengan 4 Grafik Native Dinamis
 *              (bukan gambar) yang terhubung langsung ke formula sel data.
 * ==============================================================================
 */

// Aktifkan error reporting untuk logging tapi jangan corrupt output binary
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../../vendor/autoload.php';

try {
    include __DIR__ . '/../../../config_temp_humid.php';
} catch (Throwable $e) {
    $conn = false;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

date_default_timezone_set("Asia/Jakarta");

$TEMP_SAFE_LIMIT = 30.0;
$HUM_SAFE_LIMIT  = 60.0;

// 1. Tangkap Rentang Tanggal Filter
$range_param = isset($_GET['range']) ? trim($_GET['range']) : '';
$where_date = "t.record_time >= NOW() - INTERVAL 1 WEEK";
$range_title = "Last 1 Week";

if (!empty($range_param)) {
    $aRanges = explode(' to ', $range_param);
    $range1 = trim($aRanges[0]);
    $range2 = isset($aRanges[1]) ? trim($aRanges[1]) : $range1;
    
    if (!empty($range1)) {
        $safe_r1 = $conn ? mysqli_real_escape_string($conn, $range1) : preg_replace('/[^0-9\-]/', '', $range1);
        $safe_r2 = $conn ? mysqli_real_escape_string($conn, $range2) : preg_replace('/[^0-9\-]/', '', $range2);
        $where_date = "DATE(t.record_time) BETWEEN '$safe_r1' AND '$safe_r2'";
        $range_title = date('d M Y', strtotime($range1)) . ' s/d ' . date('d M Y', strtotime($range2));
    }
}

// 2. Query Data dari Database
$query = "SELECT t.id_transaction, t.id_location, t.temp, t.humidity, t.record_time, 
                 DATE_FORMAT(t.record_time, '%W') AS day_name,
                 DATE(t.record_time) AS date_only,
                 w.weather_status,
                 w.temp_outdoor
          FROM temp_humid t
          LEFT JOIN weather_aoi5 w 
              ON w.id_transaction = CONCAT('AOI5_WTR_', DATE_FORMAT(t.record_time, '%Y%m%d_%H'), '0000')
          WHERE (t.id_location IN ('501', '502', 'area501', 'area502', 'Area_1', 'Area_2', 'temp1', 'temp2')) 
            AND $where_date
          ORDER BY t.record_time ASC";

$result = false;
if ($conn) {
    try {
        $result = mysqli_query($conn, $query);
    } catch (Throwable $e) {
        $result = false;
    }
}

$records = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($r = mysqli_fetch_assoc($result)) {
        $id_loc = trim($r['id_location']);
        $loc_lower = strtolower($id_loc);
        $is501 = ($loc_lower == '501' || $loc_lower == 'area501' || $loc_lower == 'area_1' || $loc_lower == 'temp1');
        $is502 = ($loc_lower == '502' || $loc_lower == 'area502' || $loc_lower == 'area_2' || $loc_lower == 'temp2');

        if (!$is501 && !$is502) {
            if (strpos($loc_lower, '501') !== false) $is501 = true;
            else if (strpos($loc_lower, '502') !== false) $is502 = true;
            else continue;
        }

        $records[] = [
            'id_location'   => $is501 ? '501' : '502',
            'location_name' => $is501 ? 'Folding Area' : 'Dry Room',
            'is501'         => $is501,
            'is502'         => $is502,
            'temp'          => is_numeric($r['temp']) ? round(floatval($r['temp']), 1) : null,
            'humidity'      => is_numeric($r['humidity']) ? round(floatval($r['humidity']), 1) : null,
            'weather'       => !empty($r['weather_status']) ? $r['weather_status'] : '-',
            'record_time'   => $r['record_time'],
            'day_name'      => $r['day_name']
        ];
    }
}

// 3. Agregasi Data 4 Granularitas Waktu
// A. Per Jam (Hourly)
$hourly_map = [];
foreach ($records as $rec) {
    $hKey = substr($rec['record_time'], 0, 13) . ':00';
    if (!isset($hourly_map[$hKey])) {
        $dParts = explode('-', substr($rec['record_time'], 0, 10));
        $lbl = (count($dParts) === 3 ? ($dParts[2] . '/' . $dParts[1]) : $dParts[0]) . ' ' . substr($rec['record_time'], 11, 5);
        $hourly_map[$hKey] = [
            'label'   => $lbl,
            'temp501' => null,
            'hum501'  => null,
            'temp502' => null,
            'hum502'  => null,
        ];
    }
    if ($rec['is501']) {
        $hourly_map[$hKey]['temp501'] = $rec['temp'];
        $hourly_map[$hKey]['hum501']  = $rec['humidity'];
    } elseif ($rec['is502']) {
        $hourly_map[$hKey]['temp502'] = $rec['temp'];
        $hourly_map[$hKey]['hum502']  = $rec['humidity'];
    }
}
ksort($hourly_map);

// B. Per Hari (Daily)
$daily_map = [];
foreach ($records as $rec) {
    $dKey = substr($rec['record_time'], 0, 10);
    if (!isset($daily_map[$dKey])) {
        $daily_map[$dKey] = [
            'date'    => $dKey,
            'dayName' => $rec['day_name'],
            't501' => [], 'h501' => [],
            't502' => [], 'h502' => [],
            'weathers' => []
        ];
    }
    if ($rec['is501']) {
        if ($rec['temp'] !== null) $daily_map[$dKey]['t501'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $daily_map[$dKey]['h501'][] = $rec['humidity'];
    } elseif ($rec['is502']) {
        if ($rec['temp'] !== null) $daily_map[$dKey]['t502'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $daily_map[$dKey]['h502'][] = $rec['humidity'];
    }
    if ($rec['weather'] !== '-') $daily_map[$dKey]['weathers'][] = $rec['weather'];
}
ksort($daily_map);

function array_stats($arr) {
    if (empty($arr)) return ['avg' => null, 'min' => null, 'max' => null];
    $sum = array_sum($arr);
    return [
        'avg' => round($sum / count($arr), 1),
        'min' => min($arr),
        'max' => max($arr)
    ];
}

// C. Per Minggu (Weekly)
$weekly_map = [];
foreach ($records as $rec) {
    $ts = strtotime($rec['record_time']);
    $wKey = date('Y-\WW', $ts);
    if (!isset($weekly_map[$wKey])) {
        $weekly_map[$wKey] = [
            'label' => 'Minggu ' . date('W', $ts),
            't501' => [], 'h501' => [],
            't502' => [], 'h502' => []
        ];
    }
    if ($rec['is501']) {
        if ($rec['temp'] !== null) $weekly_map[$wKey]['t501'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $weekly_map[$wKey]['h501'][] = $rec['humidity'];
    } elseif ($rec['is502']) {
        if ($rec['temp'] !== null) $weekly_map[$wKey]['t502'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $weekly_map[$wKey]['h502'][] = $rec['humidity'];
    }
}
ksort($weekly_map);

// D. Per Bulan (Monthly)
$monthly_map = [];
$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
foreach ($records as $rec) {
    $mKey = substr($rec['record_time'], 0, 7);
    if (!isset($monthly_map[$mKey])) {
        $mParts = explode('-', $mKey);
        $mIdx = intval($mParts[1]) - 1;
        $mLbl = ($mIdx >= 0 && $mIdx < 12 ? $month_names[$mIdx] : $mParts[1]) . ' ' . $mParts[0];
        $monthly_map[$mKey] = [
            'label' => $mLbl,
            't501' => [], 'h501' => [],
            't502' => [], 'h502' => []
        ];
    }
    if ($rec['is501']) {
        if ($rec['temp'] !== null) $monthly_map[$mKey]['t501'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $monthly_map[$mKey]['h501'][] = $rec['humidity'];
    } elseif ($rec['is502']) {
        if ($rec['temp'] !== null) $monthly_map[$mKey]['t502'][] = $rec['temp'];
        if ($rec['humidity'] !== null) $monthly_map[$mKey]['h502'][] = $rec['humidity'];
    }
}
ksort($monthly_map);

// Hitung KPI
$all_501_t = []; $all_501_h = [];
$all_502_t = []; $all_502_h = [];
$safe_records_cnt = 0;
$total_cnt = count($records);

foreach ($records as $rec) {
    if ($rec['is501']) {
        if ($rec['temp'] !== null) $all_501_t[] = $rec['temp'];
        if ($rec['humidity'] !== null) $all_501_h[] = $rec['humidity'];
    } elseif ($rec['is502']) {
        if ($rec['temp'] !== null) $all_502_t[] = $rec['temp'];
        if ($rec['humidity'] !== null) $all_502_h[] = $rec['humidity'];
    }
    $t_exceed = ($rec['temp'] !== null && $rec['temp'] > $TEMP_SAFE_LIMIT);
    $h_exceed = ($rec['humidity'] !== null && $rec['humidity'] > $HUM_SAFE_LIMIT);
    if (!$t_exceed && !$h_exceed) $safe_records_cnt++;
}

$kpi501T = array_stats($all_501_t);
$kpi501H = array_stats($all_501_h);
$kpi502T = array_stats($all_502_t);
$kpi502H = array_stats($all_502_h);
$compliance_rate = ($total_cnt > 0) ? round(($safe_records_cnt / $total_cnt) * 100, 1) : 100.0;

// 4. Inisialisasi Spreadsheet
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('PT Apparel One Indonesia - Enviro System')
    ->setTitle('Laporan Temperature & Humidity AOI 5');

// ==============================================================================
// SHEET 3: DATA SOURCE CHART ENGINE (Tersembunyi atau Tabel Rujukan Dinamis)
// ==============================================================================
$sheetData = $spreadsheet->getActiveSheet();
$sheetData->setTitle('Chart Data Source');

// Tulis Tabel Hourly
$sheetData->setCellValue('A1', 'Waktu');
$sheetData->setCellValue('B1', 'Suhu Folding (501)');
$sheetData->setCellValue('C1', 'Humid Folding (501)');
$sheetData->setCellValue('D1', 'Suhu Dry Room (502)');
$sheetData->setCellValue('E1', 'Humid Dry Room (502)');
$sheetData->setCellValue('F1', 'Batas Suhu (30°C)');
$sheetData->setCellValue('G1', 'Batas Humid (60%)');

$hRow = 2;
foreach ($hourly_map as $hItem) {
    $sheetData->setCellValue('A' . $hRow, $hItem['label']);
    $sheetData->setCellValue('B' . $hRow, $hItem['temp501']);
    $sheetData->setCellValue('C' . $hRow, $hItem['hum501']);
    $sheetData->setCellValue('D' . $hRow, $hItem['temp502']);
    $sheetData->setCellValue('E' . $hRow, $hItem['hum502']);
    $sheetData->setCellValue('F' . $hRow, $TEMP_SAFE_LIMIT);
    $sheetData->setCellValue('G' . $hRow, $HUM_SAFE_LIMIT);
    $hRow++;
}
$hourly_end_row = max(2, $hRow - 1);

// Tulis Tabel Daily
$sheetData->setCellValue('I1', 'Tanggal');
$sheetData->setCellValue('J1', 'Avg Suhu 501');
$sheetData->setCellValue('K1', 'Avg Humid 501');
$sheetData->setCellValue('L1', 'Avg Suhu 502');
$sheetData->setCellValue('M1', 'Avg Humid 502');
$sheetData->setCellValue('N1', 'Batas Suhu (30°C)');
$sheetData->setCellValue('O1', 'Batas Humid (60%)');

$dRow = 2;
foreach ($daily_map as $dItem) {
    $s1T = array_stats($dItem['t501']);
    $s1H = array_stats($dItem['h501']);
    $s2T = array_stats($dItem['t502']);
    $s2H = array_stats($dItem['h502']);

    $sheetData->setCellValue('I' . $dRow, date('d/m/Y', strtotime($dItem['date'])));
    $sheetData->setCellValue('J' . $dRow, $s1T['avg']);
    $sheetData->setCellValue('K' . $dRow, $s1H['avg']);
    $sheetData->setCellValue('L' . $dRow, $s2T['avg']);
    $sheetData->setCellValue('M' . $dRow, $s2H['avg']);
    $sheetData->setCellValue('N' . $dRow, $TEMP_SAFE_LIMIT);
    $sheetData->setCellValue('O' . $dRow, $HUM_SAFE_LIMIT);
    $dRow++;
}
$daily_end_row = max(2, $dRow - 1);

// Tulis Tabel Weekly
$sheetData->setCellValue('Q1', 'Minggu');
$sheetData->setCellValue('R1', 'Avg Suhu 501');
$sheetData->setCellValue('S1', 'Avg Humid 501');
$sheetData->setCellValue('T1', 'Avg Suhu 502');
$sheetData->setCellValue('U1', 'Avg Humid 502');
$sheetData->setCellValue('V1', 'Batas Suhu (30°C)');
$sheetData->setCellValue('W1', 'Batas Humid (60%)');

$wRow = 2;
foreach ($weekly_map as $wItem) {
    $s1T = array_stats($wItem['t501']);
    $s1H = array_stats($wItem['h501']);
    $s2T = array_stats($wItem['t502']);
    $s2H = array_stats($wItem['h502']);

    $sheetData->setCellValue('Q' . $wRow, $wItem['label']);
    $sheetData->setCellValue('R' . $wRow, $s1T['avg']);
    $sheetData->setCellValue('S' . $wRow, $s1H['avg']);
    $sheetData->setCellValue('T' . $wRow, $s2T['avg']);
    $sheetData->setCellValue('U' . $wRow, $s2H['avg']);
    $sheetData->setCellValue('V' . $wRow, $TEMP_SAFE_LIMIT);
    $sheetData->setCellValue('W' . $wRow, $HUM_SAFE_LIMIT);
    $wRow++;
}
$weekly_end_row = max(2, $wRow - 1);

// Tulis Tabel Monthly
$sheetData->setCellValue('Y1', 'Bulan');
$sheetData->setCellValue('Z1', 'Avg Suhu 501');
$sheetData->setCellValue('AA1', 'Avg Humid 501');
$sheetData->setCellValue('AB1', 'Avg Suhu 502');
$sheetData->setCellValue('AC1', 'Avg Humid 502');
$sheetData->setCellValue('AD1', 'Batas Suhu (30°C)');
$sheetData->setCellValue('AE1', 'Batas Humid (60%)');

$mRow = 2;
foreach ($monthly_map as $mItem) {
    $s1T = array_stats($mItem['t501']);
    $s1H = array_stats($mItem['h501']);
    $s2T = array_stats($mItem['t502']);
    $s2H = array_stats($mItem['h502']);

    $sheetData->setCellValue('Y' . $mRow, $mItem['label']);
    $sheetData->setCellValue('Z' . $mRow, $s1T['avg']);
    $sheetData->setCellValue('AA' . $mRow, $s1H['avg']);
    $sheetData->setCellValue('AB' . $mRow, $s2T['avg']);
    $sheetData->setCellValue('AC' . $mRow, $s2H['avg']);
    $sheetData->setCellValue('AD' . $mRow, $TEMP_SAFE_LIMIT);
    $sheetData->setCellValue('AE' . $mRow, $HUM_SAFE_LIMIT);
    $mRow++;
}
$monthly_end_row = max(2, $mRow - 1);

// ==============================================================================
// SHEET 1: DASHBOARD & GRAFIK NATIVE EXCEL DINAMIS
// ==============================================================================
$sheetDash = $spreadsheet->createSheet(0);
$sheetDash->setTitle('Dashboard & Grafik');
$sheetDash->setShowGridLines(true);

// Atur Lebar Kolom
$cols_width = ['A' => 4, 'B' => 20, 'C' => 18, 'D' => 20, 'E' => 18, 'F' => 20, 'G' => 18, 'H' => 20, 'I' => 18, 'J' => 18, 'K' => 18];
foreach ($cols_width as $colKey => $cW) {
    $sheetDash->getColumnDimension($colKey)->setWidth($cW);
}

// Header Perusahaan
$sheetDash->setCellValue('B2', 'PT APPAREL ONE INDONESIA (FACTORY 5)');
$sheetDash->getStyle('B2')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF1E3A8A');

$sheetDash->setCellValue('B3', 'EXECUTIVE SUMMARY & GRAFIK TREN TEMPERATURE & HUMIDITY (DYNAMIC NATIVE CHARTS)');
$sheetDash->getStyle('B3')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF334155');

$sheetDash->setCellValue('B4', 'Periode Laporan: ' . $range_title . ' | Dicetak: ' . date('d M Y H:i') . ' WIB');
$sheetDash->getStyle('B4')->getFont()->setItalic(true)->setSize(9.5)->getColor()->setARGB('FF64748B');

$sheetDash->setCellValue('B5', 'STANDAR BATAS ACUAN AMAN: SUHU <= ' . number_format($TEMP_SAFE_LIMIT, 1) . ' °C  |  HUMIDITY <= ' . number_format($HUM_SAFE_LIMIT, 1) . ' % (Grafik Dinamis Terhubung Langsung ke Sel Data)');
$sheetDash->getStyle('B5')->getFont()->setBold(true)->setSize(9.5)->getColor()->setARGB('FFDC2626');

// KPI CARDS
function setKpiCard($sheet, $col1, $col2, $rowStart, $title, $l1, $l2, $l3, $bgColor) {
    $sheet->mergeCells("{$col1}{$rowStart}:{$col2}{$rowStart}");
    $sheet->setCellValue("{$col1}{$rowStart}", $title);
    $sheet->getStyle("{$col1}{$rowStart}")->getFont()->setBold(true)->setSize(10.5);
    $sheet->getStyle("{$col1}{$rowStart}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $r1 = $rowStart + 1; $r2 = $rowStart + 2; $r3 = $rowStart + 3;
    $sheet->mergeCells("{$col1}{$r1}:{$col2}{$r1}"); $sheet->setCellValue("{$col1}{$r1}", $l1);
    $sheet->mergeCells("{$col1}{$r2}:{$col2}{$r2}"); $sheet->setCellValue("{$col1}{$r2}", $l2);
    $sheet->mergeCells("{$col1}{$r3}:{$col2}{$r3}"); $sheet->setCellValue("{$col1}{$r3}", $l3);
    $sheet->getStyle("{$col1}{$r3}")->getFont()->setBold(true);

    for ($r = $rowStart; $r <= $r3; $r++) {
        $sheet->getStyle("{$col1}{$r}:{$col2}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
        $sheet->getStyle("{$col1}{$r}:{$col2}{$r}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
    }
}

setKpiCard($sheetDash, 'B', 'C', 7, '1. FOLDING AREA (501)',
    "Rata-rata Suhu : {$kpi501T['avg']} °C (Min {$kpi501T['min']} / Max {$kpi501T['max']})",
    "Rata-rata Humid: {$kpi501H['avg']} % (Min {$kpi501H['min']} / Max {$kpi501H['max']})",
    "Batas Acuan : " . ($kpi501T['max'] <= $TEMP_SAFE_LIMIT ? 'AMAN (Optimal)' : 'PERINGATAN (>30°C)'),
    'FFFFF7ED'
);

setKpiCard($sheetDash, 'D', 'E', 7, '2. DRY ROOM (502)',
    "Rata-rata Suhu : {$kpi502T['avg']} °C (Min {$kpi502T['min']} / Max {$kpi502T['max']})",
    "Rata-rata Humid: {$kpi502H['avg']} % (Min {$kpi502H['min']} / Max {$kpi502H['max']})",
    "Batas Acuan : " . ($kpi502T['max'] <= $TEMP_SAFE_LIMIT ? 'AMAN (Optimal)' : 'PERINGATAN (>30°C)'),
    'FFF0FDFA'
);

setKpiCard($sheetDash, 'F', 'G', 7, '3. KEPATUHAN BATAS ACUAN',
    "Batas Acuan : Suhu <= {$TEMP_SAFE_LIMIT}°C | Hum <= {$HUM_SAFE_LIMIT}%",
    "Tingkat Kepatuhan: {$compliance_rate} %",
    "Status Audit : " . ($compliance_rate >= 95.0 ? 'MEMENUHI STANDAR' : 'TERDAPAT PELANGGARAN'),
    $compliance_rate >= 95.0 ? 'FFF0FDF4' : 'FFFEF2F2'
);

setKpiCard($sheetDash, 'H', 'I', 7, '4. TOTAL DATA RECORD',
    "Total Snapshot : {$total_cnt} Jam Record",
    "Sesuai Batas : {$safe_records_cnt} Jam",
    "Melampaui Batas : " . ($total_cnt - $safe_records_cnt) . " Jam",
    'FFEFF6FF'
);

// FUNGSI MEMBUAT CHART NATIVE EXCEL DENGAN FORMULA RANGES
function buildNativeLineChart($chartName, $titleText, $catCol, $catStart, $catEnd, $colB, $colC, $colD, $colE, $colF, $colG, $tlPos, $brPos) {
    $count = ($catEnd - $catStart) + 1;
    $sheetName = "'Chart Data Source'";

    // Sumbu X Categories
    $categories = [
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$catCol}\${$catStart}:\${$catCol}\${$catEnd}", null, $count)
    ];

    // Label Seri
    $labels = [
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colB}\$1", null, 1),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colC}\$1", null, 1),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colD}\$1", null, 1),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colE}\$1", null, 1),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colF}\$1", null, 1),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheetName}!\${$colG}\$1", null, 1),
    ];

    // Nilai Seri
    $values = [
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colB}\${$catStart}:\${$colB}\${$catEnd}", null, $count),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colC}\${$catStart}:\${$colC}\${$catEnd}", null, $count),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colD}\${$catStart}:\${$colD}\${$catEnd}", null, $count),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colE}\${$catStart}:\${$colE}\${$catEnd}", null, $count),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colF}\${$catStart}:\${$colF}\${$catEnd}", null, $count),
        new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheetName}!\${$colG}\${$catStart}:\${$colG}\${$catEnd}", null, $count),
    ];

    $series = new DataSeries(
        DataSeries::TYPE_LINECHART,
        DataSeries::GROUPING_STANDARD,
        range(0, count($values) - 1),
        $labels,
        $categories,
        $values
    );

    $plotArea = new PlotArea(null, [$series]);
    $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
    $chartTitle = new Title($titleText);

    $chart = new Chart(
        $chartName,
        $chartTitle,
        $legend,
        $plotArea,
        true,
        DataSeries::EMPTY_AS_GAP,
        null,
        null
    );

    $chart->setTopLeftPosition($tlPos);
    $chart->setBottomRightPosition($brPos);

    return $chart;
}

// 1. Chart Native 1: Per Jam (Hourly)
$sheetDash->mergeCells('B12:I12');
$sheetDash->setCellValue('B12', '1. GRAFIK TREN FLUKTUASI PER JAM (HOURLY NATIVE CHART)');
$sheetDash->getStyle('B12')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
$sheetDash->getStyle('B12')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
$sheetDash->getRowDimension(12)->setRowHeight(24);

$chartHourly = buildNativeLineChart('chartHourly', 'Grafik Tren Per Jam (Suhu & Humid vs Batas Acuan 30°C / 60%)', 'A', 2, $hourly_end_row, 'B', 'C', 'D', 'E', 'F', 'G', 'B13', 'I29');
$sheetDash->addChart($chartHourly);

// 2. Chart Native 2: Per Hari (Daily)
$sheetDash->mergeCells('B31:I31');
$sheetDash->setCellValue('B31', '2. GRAFIK TREN RATA-RATA PER HARI (DAILY NATIVE CHART)');
$sheetDash->getStyle('B31')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
$sheetDash->getStyle('B31')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
$sheetDash->getRowDimension(31)->setRowHeight(24);

$chartDaily = buildNativeLineChart('chartDaily', 'Grafik Tren Harian (Rata-rata Suhu & Humid vs Batas Acuan 30°C / 60%)', 'I', 2, $daily_end_row, 'J', 'K', 'L', 'M', 'N', 'O', 'B32', 'I48');
$sheetDash->addChart($chartDaily);

// 3. Chart Native 3: Per Minggu (Weekly)
$sheetDash->mergeCells('B50:I50');
$sheetDash->setCellValue('B50', '3. GRAFIK TREN RATA-RATA PER MINGGU (WEEKLY NATIVE CHART)');
$sheetDash->getStyle('B50')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
$sheetDash->getStyle('B50')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
$sheetDash->getRowDimension(50)->setRowHeight(24);

$chartWeekly = buildNativeLineChart('chartWeekly', 'Grafik Tren Mingguan (Rata-rata Suhu & Humid vs Batas Acuan 30°C / 60%)', 'Q', 2, $weekly_end_row, 'R', 'S', 'T', 'U', 'V', 'W', 'B51', 'I67');
$sheetDash->addChart($chartWeekly);

// 4. Chart Native 4: Per Bulan (Monthly)
$sheetDash->mergeCells('B69:I69');
$sheetDash->setCellValue('B69', '4. GRAFIK TREN RATA-RATA PER BULAN (MONTHLY NATIVE CHART)');
$sheetDash->getStyle('B69')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
$sheetDash->getStyle('B69')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
$sheetDash->getRowDimension(69)->setRowHeight(24);

$chartMonthly = buildNativeLineChart('chartMonthly', 'Grafik Tren Bulanan (Rata-rata Suhu & Humid vs Batas Acuan 30°C / 60%)', 'Y', 2, $monthly_end_row, 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'B70', 'I86');
$sheetDash->addChart($chartMonthly);

// 5. Tabel Ringkasan Agregasi Harian (Row 88+)
$curR = 88;
$sheetDash->mergeCells("B{$curR}:I{$curR}");
$sheetDash->setCellValue("B{$curR}", '5. TABEL RINGKASAN AGREGASI HARIAN');
$sheetDash->getStyle("B{$curR}")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
$sheetDash->getStyle("B{$curR}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
$sheetDash->getRowDimension($curR)->setRowHeight(24);

$curR++;
$dHeaders = ['Tanggal', 'Hari', 'Avg Suhu 501', 'Min-Max 501', 'Avg Hum 501', 'Avg Suhu 502', 'Min-Max 502', 'Avg Hum 502'];
$dCols = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
foreach ($dCols as $idx => $cLetter) {
    $sheetDash->setCellValue($cLetter . $curR, $dHeaders[$idx]);
    $sheetDash->getStyle($cLetter . $curR)->getFont()->setBold(true)->setSize(9.5)->getColor()->setARGB('FFFFFFFF');
    $sheetDash->getStyle($cLetter . $curR)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF134E4A');
    $sheetDash->getStyle($cLetter . $curR)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}
$sheetDash->getRowDimension($curR)->setRowHeight(20);

foreach ($daily_map as $dKey => $dItem) {
    $curR++;
    $s1T = array_stats($dItem['t501']);
    $s1H = array_stats($dItem['h501']);
    $s2T = array_stats($dItem['t502']);
    $s2H = array_stats($dItem['h502']);

    $sheetDash->setCellValue('B' . $curR, date('d/m/Y', strtotime($dItem['date'])));
    $sheetDash->setCellValue('C' . $curR, $dItem['dayName']);
    $sheetDash->setCellValue('D' . $curR, $s1T['avg'] !== null ? ($s1T['avg'] . ' °C') : '-');
    $sheetDash->setCellValue('E' . $curR, $s1T['min'] !== null ? ($s1T['min'] . ' - ' . $s1T['max']) : '-');
    $sheetDash->setCellValue('F' . $curR, $s1H['avg'] !== null ? ($s1H['avg'] . ' %') : '-');
    $sheetDash->setCellValue('G' . $curR, $s2T['avg'] !== null ? ($s2T['avg'] . ' °C') : '-');
    $sheetDash->setCellValue('H' . $curR, $s2T['min'] !== null ? ($s2T['min'] . ' - ' . $s2T['max']) : '-');
    $sheetDash->setCellValue('I' . $curR, $s2H['avg'] !== null ? ($s2H['avg'] . ' %') : '-');

    $isExceed = ($s1T['max'] > $TEMP_SAFE_LIMIT || $s2T['max'] > $TEMP_SAFE_LIMIT || $s1H['max'] > $HUM_SAFE_LIMIT || $s2H['max'] > $HUM_SAFE_LIMIT);
    $rowBg = ($curR % 2 === 0) ? ($isExceed ? 'FFFEF2F2' : 'FFF8FAFC') : ($isExceed ? 'FFFEE2E2' : 'FFFFFFFF');

    foreach ($dCols as $idx => $cLetter) {
        $sheetDash->getStyle($cLetter . $curR)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
        $sheetDash->getStyle($cLetter . $curR)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        $sheetDash->getStyle($cLetter . $curR)->getAlignment()->setHorizontal($idx <= 1 ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_RIGHT);
    }
}

// ==============================================================================
// SHEET 2: DATA DETAIL HISTORIS (9 Kolom Standar - Tanpa Kolom Status Acuan)
// ==============================================================================
$sheetHist = $spreadsheet->createSheet(1);
$sheetHist->setTitle('Data Detail Historis');
$sheetHist->setShowGridLines(true);

$histHeaders = [
    'No', 'Factory', 'Id Location', 'Location', 'Temperature (C)', 'Humidity (%)', 'Weather', 'Record Time', 'Day Name'
];
$histCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
$histWidths = [6, 12, 14, 18, 18, 16, 16, 22, 14];

foreach ($histCols as $idx => $cLetter) {
    $sheetHist->getColumnDimension($cLetter)->setWidth($histWidths[$idx]);
    $sheetHist->setCellValue($cLetter . '1', $histHeaders[$idx]);
    $sheetHist->getStyle($cLetter . '1')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
    $sheetHist->getStyle($cLetter . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
    $sheetHist->getStyle($cLetter . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}
$sheetHist->getRowDimension(1)->setRowHeight(26);

$rowNum = 2;
foreach ($records as $rIdx => $rec) {
    $sheetHist->setCellValue('A' . $rowNum, $rIdx + 1);
    $sheetHist->setCellValue('B' . $rowNum, 'AOI 5');
    $sheetHist->setCellValue('C' . $rowNum, $rec['id_location']);
    $sheetHist->setCellValue('D' . $rowNum, $rec['location_name']);
    $sheetHist->setCellValue('E' . $rowNum, $rec['temp']);
    $sheetHist->setCellValue('F' . $rowNum, $rec['humidity']);
    $sheetHist->setCellValue('G' . $rowNum, $rec['weather']);
    $sheetHist->setCellValue('H' . $rowNum, $rec['record_time']);
    $sheetHist->setCellValue('I' . $rowNum, $rec['day_name']);

    $rowBg = ($rowNum % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';
    foreach ($histCols as $idx => $cLetter) {
        $sheetHist->getStyle($cLetter . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
        $sheetHist->getStyle($cLetter . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        $sheetHist->getStyle($cLetter . $rowNum)->getAlignment()->setHorizontal(($cLetter == 'D') ? Alignment::HORIZONTAL_LEFT : (($cLetter == 'E' || $cLetter == 'F') ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_CENTER));
    }

    // Penandaan khusus jika Suhu > 30°C
    if ($rec['temp'] !== null && $rec['temp'] > $TEMP_SAFE_LIMIT) {
        $sheetHist->getStyle('E' . $rowNum)->getFont()->setBold(true)->getColor()->setARGB('FF991B1B');
        $sheetHist->getStyle('E' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
    }

    // Penandaan khusus jika Kelembapan > 60%
    if ($rec['humidity'] !== null && $rec['humidity'] > $HUM_SAFE_LIMIT) {
        $sheetHist->getStyle('F' . $rowNum)->getFont()->setBold(true)->getColor()->setARGB('FF991B1B');
        $sheetHist->getStyle('F' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
    }

    $rowNum++;
}

// Format Angka Desimal
$lastDataRow = max(2, $rowNum - 1);
$sheetHist->getStyle("E2:E{$lastDataRow}")->getNumberFormat()->setFormatCode('0.0');
$sheetHist->getStyle("F2:F{$lastDataRow}")->getNumberFormat()->setFormatCode('0.0');
$sheetHist->setAutoFilter("A1:I{$lastDataRow}");

// Jadikan Sheet 1 sebagai sheet yang aktif saat file dibuka
$spreadsheet->setActiveSheetIndex(0);

// 5. Output ke Browser untuk Download
$fileDateSuffix = !empty($range_param) ? preg_replace('/[^a-zA-Z0-9]/', '_', $range_param) : date('Ymd_His');
$filename = "Laporan_Temp_Humid_AOI5_{$fileDateSuffix}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;
