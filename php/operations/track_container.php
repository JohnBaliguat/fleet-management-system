<?php
include '../config/config.php';

if (isset($_GET['container'])) {
    $container = $_GET['container'];

    $sql = "
        SELECT 
            id,
            container_name,
            container_location,
            truck_no,
            trailer_no,
            genset_no,
            driver_name,
            container_status,
            trip_status,
            Date
        FROM container_activity
        WHERE container_name = '$container'
        ORDER BY Date DESC
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $records = [];
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'records' => $records
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Container not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing container parameter']);
}
?>
