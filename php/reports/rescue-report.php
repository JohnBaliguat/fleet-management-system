<?php
include "../config/config.php";
require "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fromDate = $_POST['fromDate'] ?? '';
    $toDate   = $_POST['toDate'] ?? '';

    $sql = "
        SELECT 
            r.r_id,
            r.r_unit,
            r.r_startDateTime,
            r.r_endDateTime,
            r.r_status,
            d.driver_fname,
            d.driver_lname,
            GROUP_CONCAT(
                CONCAT(sm.smp_fname, ' ', sm.smp_lname)
                SEPARATOR ', '
            ) AS manpower
        FROM rescue_unit r
        LEFT JOIN drivers d ON r.driver_id = d.driver_id
        LEFT JOIN rescue_record rr ON r.r_id = rr.r_id
        LEFT JOIN assign_manpower am ON rr.rr_id = am.rr_id
        LEFT JOIN shop_manpower sm ON am.smp_id = sm.smp_id
        WHERE r.r_status = 'Good' AND 1=1
    ";

    if (!empty($fromDate) && !empty($toDate)) {
        $sql .= " AND r.r_startDateTime BETWEEN '$fromDate' AND '$toDate'";
    }

    $sql .= " GROUP BY r.r_id ORDER BY r.r_startDateTime ASC";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 0) {
        echo "<script>alert('No records found!');window.close();</script>";
        exit;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    /* ===== TITLE ===== */
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'Rescue Unit Report');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', 'Date Range: ' . ($fromDate && $toDate ? "$fromDate to $toDate" : "All"));

    $sheet->mergeCells('A3:G3');
    $sheet->setCellValue('A3', 'Date Prepared: ' . date('Y-m-d'));

    /* ===== HEADERS ===== */
    $headers = [
        'A6' => 'Driver',
        'B6' => 'Rescue Unit',
        'C6' => 'Start Date & Time',
        'D6' => 'End Date & Time',
        'E6' => 'Duration',
        'F6' => 'Manpower',
        'G6' => 'Status'
    ];

    foreach ($headers as $cell => $text) {
        $sheet->setCellValue($cell, $text);
    }

    $sheet->getStyle('A6:G6')->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    /* ===== DATA ===== */
    $row = 7;

    while ($r = mysqli_fetch_assoc($result)) {

        $start = $r['r_startDateTime'];
        $end   = $r['r_endDateTime'];

        $sheet->setCellValue("A$row", $r['driver_fname'] . ' ' . $r['driver_lname']);
        $sheet->setCellValue("B$row", $r['r_unit']);

        $sheet->setCellValue("C$row", ExcelDate::PHPToExcel(strtotime($start)));
        $sheet->getStyle("C$row")->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');

        if (!empty($end) && $end !== '0000-00-00 00:00:00') {
            $sheet->setCellValue("D$row", ExcelDate::PHPToExcel(strtotime($end)));
            $sheet->getStyle("D$row")->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');

            $diff = strtotime($end) - strtotime($start);
            $days = floor($diff / 86400);
            $time = gmdate("H:i:s", $diff % 86400);
            $sheet->setCellValue("E$row", "$days Days $time");
        } else {
            $sheet->setCellValue("D$row", "Ongoing");
            $sheet->setCellValue("E$row", "—");
        }

        $sheet->setCellValue("F$row", $r['manpower'] ?: '—');
        $sheet->setCellValue("G$row", $r['r_status']);

        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $row++;
    }

    foreach ($sheet->getColumnIterator() as $col) {
        $sheet->getColumnDimension($col->getColumnIndex())->setAutoSize(true);
    }

    /* ===== OUTPUT ===== */
    $filename = "Rescue_Report_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
