<?php
include '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = $_POST['user_id'];
    $driver_id   = $_POST['driver_id'];
    $violation   = $_POST['violation'];
    $description = $_POST['description'];

    date_default_timezone_set("Asia/Manila");
    $date = date("Y-m-d H:i:s");

    // 1. Insert violation record
    $insert = $conn->prepare("
        INSERT INTO violation_record 
        (driver_id, vr_type, vr_description, vr_status, vr_recordedBy, vr_date)
        VALUES (?, ?, ?, 'Active', ?, ?)
    ");
    $insert->bind_param("issis", $driver_id, $violation, $description, $user_id, $date);

    if ($insert->execute()) {

        // 2. Update driver status based on violation
        // You can customize this logic
        $new_status = 'With Violation';

        $update = $conn->prepare("
            UPDATE drivers 
            SET driver_status = ?
            WHERE driver_id = ?
        ");
        $update->bind_param("si", $new_status, $driver_id);
        $update->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Violation successfully recorded.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to insert violation.'
        ]);
    }
}
