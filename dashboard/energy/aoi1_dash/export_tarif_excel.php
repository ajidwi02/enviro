<?php
include 'db.php';
require 'vendor/autoload.php'; // composer require phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$query = "SELECT tanggal, lwbp_tarif, wbp_tarif, total_tarif 
          FROM electricity_aoi1_harian 
          WHERE id_location='101' 
          AND tanggal BETWEEN '$start_date' AND '$end_date'
          ORDER BY tanggal ASC";
$result = $conn->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Tarif Harian');

$sheet->setCellValue('A1', 'Tanggal');
$sheet->setCellValue('B1', 'LWBP Tarif');
$sheet->setCellValue('C1', 'WBP Tarif');
$sheet->setCellValue('D1', 'Total Tarif');

$rowNum = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A'.$rowNum, $row['tanggal']);
        $sheet->setCellValue('B'.$rowNum, $row['lwbp_tarif']);
        $sheet->setCellValue('C'.$rowNum, $row['wbp_tarif']);
        $sheet->setCellValue('D'.$rowNum, $row['total_tarif']);
        $rowNum++;
    }
}

foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = "Tarif_Harian_{$start_date}_sd_{$end_date}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
