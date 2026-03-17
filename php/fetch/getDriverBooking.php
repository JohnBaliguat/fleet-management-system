<?php
include "../config/config.php";
session_start();

$driverID = $_SESSION['user_id'];

// Count active bookings (status = Active)
$activeSql = "SELECT COUNT(*) as activeCount 
              FROM trips t
              INNER JOIN dispatch d ON t.d_id = d.d_id
              WHERE d.driver_id = ? AND t.trip_status = 'Active'";
$stmt = $conn->prepare($activeSql);
$stmt->bind_param("i", $driverID);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$activeCount = $result['activeCount'] ?? 0;

// Count done bookings (status = Done)
$doneSql = "SELECT COUNT(*) as doneCount 
            FROM trips t
            INNER JOIN dispatch d ON t.d_id = d.d_id
            WHERE d.driver_id = ? AND t.trip_status = 'Done'";
$stmt = $conn->prepare($doneSql);
$stmt->bind_param("i", $driverID);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$doneCount = $result['doneCount'] ?? 0;

echo json_encode([
    "active" => $activeCount,
    "done"   => $doneCount
]);
