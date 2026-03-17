<?php
include '../config/config.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {

    $file = $_FILES['import_file'];

    if ($file['error'] != UPLOAD_ERR_OK) {
        echo "File upload error.";
        exit;
    }

    $allowed_ext = ['xls', 'xlsx', 'csv'];
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array($file_ext, $allowed_ext)) {
        echo "Invalid file type.";
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($file['tmp_name']);

        // ✅ ONLY TEMPLATE SHEET
        $sheet = $spreadsheet->getSheetByName('TEMPLATE');
        if (!$sheet) {
            echo "Sheet 'TEMPLATE' not found.";
            exit;
        }

        $highestRow = $sheet->getHighestRow();

        // 🔹 GET LAST booking_no
        $prefix = 'PTSIBN-';

        $result = mysqli_query($conn, "
            SELECT booking_no 
            FROM booking 
            WHERE booking_no LIKE '$prefix%' 
            ORDER BY booking_id DESC 
            LIMIT 1
        ");

        $row = mysqli_fetch_assoc($result);

        if ($row) {
            // Extract numeric part: PTSIBN-0013 → 13
            $lastNumber = intval(substr($row['booking_no'], strlen($prefix)));
        } else {
            $lastNumber = 0;
        }

        $nextNumber = $lastNumber + 1;

        $inserted = 0;

        for ($r = 2; $r <= $highestRow; $r++) {

            $customer = trim($sheet->getCell("B$r")->getValue());
            if ($customer == '') continue;

            // Dates
            $booking_date = $sheet->getCell("A$r")->getValue();
            if (Date::isDateTime($sheet->getCell("A$r"))) {
                $booking_date = Date::excelToDateTimeObject($booking_date)->format('Y-m-d');
            }

            $dateRequired = $sheet->getCell("D$r")->getValue();
            if (Date::isDateTime($sheet->getCell("D$r"))) {
                $dateRequired = Date::excelToDateTimeObject($dateRequired)->format('Y-m-d');
            }

            $currentBookingNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Other fields
            $container        = mysqli_real_escape_string($conn, $sheet->getCell("E$r")->getValue());
            $seal             = mysqli_real_escape_string($conn, $sheet->getCell("F$r")->getValue());
            $activity         = mysqli_real_escape_string($conn, $sheet->getCell("G$r")->getValue());
            $status           = mysqli_real_escape_string($conn, $sheet->getCell("H$r")->getValue());
            $hauling_segment  = mysqli_real_escape_string($conn, $sheet->getCell("I$r")->getValue());
            $trip_from        = mysqli_real_escape_string($conn, $sheet->getCell("J$r")->getValue());
            $trip_to          = mysqli_real_escape_string($conn, $sheet->getCell("K$r")->getValue());
            $quantity         = intval($sheet->getCell("L$r")->getValue());

            $sql = "
                INSERT INTO booking (
                    booking_no,
                    booking_date,
                    booking_dateRequired,
                    costumer,
                    container,
                    container_seal,
                    booking_activity,
                    container_status,
                    hauling_segment,
                    trip_from,
                    trip_to,
                    quantity,
                    status
                ) VALUES (
                    '$currentBookingNo',
                    '$booking_date',
                    '$dateRequired',
                    '$customer',
                    '$container',
                    '$seal',
                    '$activity',
                    '$status',
                    '$hauling_segment',
                    '$trip_from',
                    '$trip_to',
                    '$quantity',
                    'Active'
                )
            ";

            if (!mysqli_query($conn, $sql)) {
                echo "Insert error on row $r: " . mysqli_error($conn);
                exit;
            }

            $inserted++;
            $nextNumber++;
        }

        echo "$inserted records inserted successfully.";
        

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No file uploaded.";
}
?>
