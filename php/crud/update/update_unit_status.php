<?php
include "../../config/config.php";

if (!isset($_POST['unit_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$unit_id = intval($_POST['unit_id']);

$sql = "UPDATE units SET unit_status = 'Available' WHERE unit_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $unit_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
