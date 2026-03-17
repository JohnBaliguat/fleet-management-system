<?php
include '../config/config.php';
date_default_timezone_set("Asia/Manila");

header('Content-Type: application/json');

// Force MySQL error reporting (optional but recommended)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$raw = $_POST['attendance'] ?? '';
$attendanceData = json_decode($raw, true);

if (!is_array($attendanceData)) {
    echo json_encode(["status" => "error", "message" => "Invalid attendance data."]);
    exit;
}

$date = date('Y-m-d');
$allowedStatuses = ['Present', 'VL', 'SL', 'Absent'];
$saved = 0;
$skipped = 0;

try {
    foreach ($attendanceData as $item) {
        $driver_id = isset($item['driver_id']) ? intval($item['driver_id']) : 0;
        $status = $item['status'] ?? '';
        $remarks = $item['remarks'] ?? '';
        $datePrepared = $item['datePrepared'] ?? null;
        $dateStart = $item['dateStart'] ?? null;
        $vlslDate = $item['vlslDate'] ?? null;
        $timeIn = $item['timeIn'] ?? null;

        if (empty($driver_id) || !in_array($status, $allowedStatuses, true)) {
            $skipped++;
            continue;
        }

        // Prevent duplicate attendance for same driver on same date (prepared)
        $checkStmt = $conn->prepare("SELECT da_id FROM drivers_attendance 
            WHERE da_status IN ('Present', 'VL', 'SL', 'Absent') AND driver_id = ? AND da_date = ? LIMIT 1");
        $checkStmt->bind_param("is", $driver_id, $date);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            $skipped++;
            continue;
        }
        $checkStmt->close();

        // Insert using prepared statements
        if (!empty($datePrepared) && !empty($dateStart) && !empty($vlslDate)) {
            $stmt = $conn->prepare("INSERT INTO drivers_attendance 
                (driver_id, da_status, da_remarks, da_date, vl_sl_datePrepared, vl_sl_dateStart, vl_sl_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $driver_id, $status, $remarks, $date, $datePrepared, $dateStart, $vlslDate);
        } elseif (!empty($timeIn)) {
            $stmt = $conn->prepare("INSERT INTO drivers_attendance 
                (driver_id, da_status, da_remarks, da_date, da_timeIn) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $driver_id, $status, $remarks, $date, $timeIn);
        } else {
            $stmt = $conn->prepare("INSERT INTO drivers_attendance 
                (driver_id, da_status, da_remarks, da_date) 
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $driver_id, $status, $remarks, $date);
        }
        $stmt->execute();
        $stmt->close();
        $saved++;

        // Update driver status (prepared)
        $newStatus = null;
        switch ($status) {
            case "Present":
                $cs = $conn->prepare("SELECT driver_status FROM drivers WHERE driver_id = ?");
                $cs->bind_param("i", $driver_id);
                $cs->execute();
                $cs->bind_result($currentStatus);
                if ($cs->fetch() && $currentStatus !== "Dispatch") {
                    $newStatus = "Good";
                }
                $cs->close();
                break;
            case "Absent": $newStatus = "Absent"; break;
            case "VL":     $newStatus = "VL"; break;
            case "SL":     $newStatus = "SL"; break;
        }
        if ($newStatus !== null) {
            $up = $conn->prepare("UPDATE drivers SET driver_status = ? WHERE driver_id = ?");
            $up->bind_param("si", $newStatus, $driver_id);
            $up->execute();
            $up->close();
        }
    }

    echo json_encode([
        "status"  => "success",
        "message" => "Attendance saved.",
        "saved"   => $saved,
        "skipped" => $skipped
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
exit;
