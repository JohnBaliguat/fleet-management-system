<?php
include '../config/config.php';
header('Content-Type: application/json');

$driver_id = $_POST['driver_id'] ?? '';

if (empty($driver_id)) {
    echo json_encode([
        "status" => "error",
        "message" => "Driver ID is missing."
    ]);
    exit;
}

$sql = "
    SELECT vr_type, vr_description, vr_date
    FROM violation_record
    WHERE driver_id = ?
    AND vr_status = 'Active'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $violations = [];
    while ($row = $result->fetch_assoc()) {
        $violations[] = $row;
    }

    echo json_encode([
        "status" => "violation",
        "violations" => $violations
    ]);
    exit;
}

echo json_encode([
    "status" => "clear"
]);
