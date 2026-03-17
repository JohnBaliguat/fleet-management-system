<?php
include '../config/config.php'; // adjust path if needed
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $driver_id = $_POST['driver_id'];

    date_default_timezone_set("Asia/Manila");
    $done_date = date("Y-m-d H:i:s");

    // Start transaction for safety
    $conn->begin_transaction();

    try {

        // 1️⃣ Update driver status to GOOD
        $updateDriver = $conn->prepare("
            UPDATE drivers 
            SET driver_status = 'Good'
            WHERE driver_id = ?
        ");
        $updateDriver->bind_param("i", $driver_id);
        $updateDriver->execute();

        // 2️⃣ Update violation status to DONE (only active violations)
        $updateViolation = $conn->prepare("
            UPDATE violation_record
            SET 
                vr_status = 'Done',
                vr_done_date = ?
            WHERE driver_id = ?
              AND (vr_status IS NULL OR vr_status != 'Done')
        ");
        $updateViolation->bind_param("si", $done_date, $driver_id);
        $updateViolation->execute();

        // Commit if all successful
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Driver marked as GOOD and violations set to DONE.'
        ]);

    } catch (Exception $e) {

        // Rollback on error
        $conn->rollback();

        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update driver and violations.'
        ]);
    }
}
