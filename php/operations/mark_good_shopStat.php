<?php
include "../config/config.php";

if (isset($_POST['s_id'])) {
    $s_id = intval($_POST['s_id']);


    // 1. Update rescue_unit (set status to Good, close end time)
    $updateRescue = "UPDATE shop_unit SET s_endDateTime = NOW(), s_status = 'Good' WHERE s_id = ?";
    $stmt = $conn->prepare($updateRescue);
    $stmt->bind_param("i", $s_id);
    $stmt->execute();

    // 2. Also update units table (set unit_status = Good)
    $sqlGetUnit = "SELECT s_unit FROM shop_unit WHERE s_id = ?";
    $stmt2 = $conn->prepare($sqlGetUnit);
    $stmt2->bind_param("i", $s_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $rescue = $result->fetch_assoc();

    if ($rescue) {
        $unit_name = $rescue['s_unit'];

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
