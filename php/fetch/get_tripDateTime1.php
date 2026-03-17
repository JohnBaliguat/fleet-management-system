<?php
include "../config/config.php";
header('Content-Type: application/json');

$trip_id = $_GET['trip_id'] ?? null;

if (!$trip_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing trip ID']);
    exit;
}

$sql = "SELECT trip_container, trip_arrivalDateTime, trip_departureDateTime, trip_pharrivalDateTime 
        FROM trips WHERE trip_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'status' => 'success',
        'trip' => $row
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Trip not found'
    ]);
}

$stmt->close();
$conn->close();
?>
