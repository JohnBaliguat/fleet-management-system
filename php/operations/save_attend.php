<?php
include '../config/config.php';
date_default_timezone_set("Asia/Manila");

header('Content-Type: application/json');

// ==============================
// FORCE MYSQL ERROR REPORTING
// ==============================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // ==============================
    // GET INPUTS SAFELY
    // ==============================
    $driver_id     = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $controlNo     = $_POST['controlNo'] ?? '';
    $status        = $_POST['status'] ?? '';
    $remarks       = $_POST['remarks'] ?? '';
    $dateStart     = $_POST['dateStart'] ?? null;
    $dateEnd       = $_POST['dateEnd'] ?? null;
    $datePrepared  = $_POST['datePrepared'] ?? null;

    $date        = date('Y-m-d');
    $currentTime = date('Y-m-d H:i:s');

    if (empty($driver_id) || empty($status)) {
        throw new Exception("Driver ID and Status are required.");
    }

    // ==============================
    // START TRANSACTION (IMPORTANT)
    // ==============================
    $conn->begin_transaction();

    // ==============================
    // CHECK DUPLICATE
    // ==============================
    $checkStmt = $conn->prepare("SELECT da_id FROM drivers_attendance WHERE driver_id=? AND da_date=? LIMIT 1");
    $checkStmt->bind_param("is", $driver_id, $date);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        throw new Exception("Attendance already recorded for today.");
    }
    $checkStmt->close();

    // ==============================
    // INSERT LOGIC
    // ==============================
    if ($status === "Present") {

        $stmt = $conn->prepare("INSERT INTO drivers_attendance 
            (driver_id, da_status, da_remarks, da_date, da_timeIn, da_controlNo) 
            VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssss", $driver_id, $status, $remarks, $date, $currentTime, $controlNo);

    } elseif ($status === "VL" || $status === "SL") {

        if (empty($dateStart) || empty($dateEnd) || empty($datePrepared)) {
            throw new Exception("Date Prepared, Start Date, and End Date are required for Leave.");
        }

        if ($dateStart > $dateEnd) {
            throw new Exception("End date must be after Start date.");
        }

        $stmt = $conn->prepare("INSERT INTO drivers_attendance 
            (driver_id, da_status, da_remarks, da_date, vl_sl_datePrepared, vl_sl_dateStart, vl_sl_dateEnd) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("issssss", $driver_id, $status, $remarks, $date, $datePrepared, $dateStart, $dateEnd);

    } else {

        $stmt = $conn->prepare("INSERT INTO drivers_attendance 
            (driver_id, da_status, da_remarks, da_date) 
            VALUES (?, ?, ?, ?)");

        $stmt->bind_param("isss", $driver_id, $status, $remarks, $date);
    }

    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement.");
    }

    $stmt->execute();
    $insert_id = $stmt->insert_id;
    $stmt->close();

    // ==============================
    // GET DRIVER CURRENT STATUS
    // (NO get_result - Hostinger safe)
    // ==============================
    $checkStatusStmt = $conn->prepare("SELECT driver_status FROM drivers WHERE driver_id=?");
    $checkStatusStmt->bind_param("i", $driver_id);
    $checkStatusStmt->execute();
    $checkStatusStmt->bind_result($currentDriverStatus);
    $checkStatusStmt->fetch();
    $checkStatusStmt->close();

    $newStatus = null;

    switch ($status) {
        case "Present":
            if ($currentDriverStatus !== "Dispatch") {
                $newStatus = "Good";
            }
            break;

        case "Absent":
            $newStatus = "Absent";
            break;

        case "VL":
            $newStatus = "VL";
            break;

        case "SL":
            $newStatus = "SL";
            break;
    }

    if (!empty($newStatus)) {
        $updateStmt = $conn->prepare("UPDATE drivers SET driver_status=? WHERE driver_id=?");
        $updateStmt->bind_param("si", $newStatus, $driver_id);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // ==============================
    // COMMIT TRANSACTION
    // ==============================
    $conn->commit();

    echo json_encode([
        "status"    => "success",
        "message"   => "Attendance saved successfully.",
        "insert_id" => $insert_id
    ]);

} catch (Exception $e) {

    if ($conn->errno) {
        $conn->rollback();
    }

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
exit;
?>
