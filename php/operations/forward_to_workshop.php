<?php
include "../config/config.php";

if (isset($_POST['r_id'])) {
    $r_id = intval($_POST['r_id']);

    // Get rescue_unit record
    $sql = "SELECT * FROM rescue_unit WHERE r_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $r_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rescue = $result->fetch_assoc();

    if ($rescue) {
        $unit      = $rescue['r_unit'];
        $driver_id = !empty($rescue['driver_id']) ? $rescue['driver_id'] : 0;
        $start     = $rescue['r_startDateTime'];

        // 1. Update rescue_unit
        $update = "UPDATE rescue_unit 
                   SET r_endDateTime = NOW(), r_status = 'Good' 
                   WHERE r_id = ?";
        $stmt2 = $conn->prepare($update);
        $stmt2->bind_param("i", $r_id);
        $stmt2->execute();

        // 2. Insert into shop_unit
        $insert = "INSERT INTO shop_unit (s_unit, driver_id, s_startDateTime, s_status, s_remarks) 
                   VALUES (?, ?, NOW(), 'Active', 'Forwarded from Rescue Unit')";
        $stmt3 = $conn->prepare($insert);
        $stmt3->bind_param("si", $unit, $driver_id);
        $stmt3->execute();

        echo "Unit forwarded to workshop successfully.";
    } else {
        echo "Rescue record not found.";
    }
} else {
    echo "Invalid request.";
}
?>
