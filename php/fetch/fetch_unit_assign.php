<?php
include "../config/config.php";

if (isset($_GET['unit_id'])) {
    $unit_id = intval($_GET['unit_id']);

    $sql = "SELECT unit_assign, driver_id, unit_assignGenset, unit_assignTrailer 
            FROM units WHERE unit_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = $result->fetch_assoc();
    echo json_encode($data);
}
?>
