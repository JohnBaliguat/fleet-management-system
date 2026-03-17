<?php
include "../../config/config.php";

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $sql = "UPDATE rescue_unit SET r_status = 'dispatched' WHERE r_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Rescue dispatched successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update status."]);
    }

    $stmt->close();
    $conn->close();
}
?>
