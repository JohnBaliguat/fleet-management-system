<?php
include "../config/config.php";

if (isset($_POST['r_id'])) {
    $r_id = intval($_POST['r_id']);

    // 1. Update rescue_unit (set status to Good, close end time)
    $updateRescue = "UPDATE rescue_unit SET r_endDateTime = NOW(), r_status = 'Good' WHERE r_id = ?";
    $stmt = $conn->prepare($updateRescue);
    $stmt->bind_param("i", $r_id);
    $stmt->execute();

    // 2. Also update units table (set unit_status = Good)
    $sqlGetUnit = "SELECT r_unit FROM rescue_unit WHERE r_id = ?";
    $stmt2 = $conn->prepare($sqlGetUnit);
    $stmt2->bind_param("i", $r_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $rescue = $result->fetch_assoc();

    if ($rescue) {
        $unit_name = $rescue['r_unit'];

        $updateUnit = "UPDATE units SET unit_status = 'Good' WHERE unit_name = ?";
        $stmt3 = $conn->prepare($updateUnit);
        $stmt3->bind_param("s", $unit_name);
        $stmt3->execute();
    }

    echo "Unit has been marked as Good.";
} else {
    echo "Invalid request.";
}
?>
