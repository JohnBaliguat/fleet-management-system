<?php
include "php/config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_id = trim($_POST['driver_id']);

    // Check if driver exists
    $query = "SELECT driver_id, driver_IdNumber, driver_uname, driver_pass FROM drivers WHERE driver_IdNumber = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // ID not found
        echo json_encode(["status" => "not_found"]);
    } else {
        $row = $result->fetch_assoc();
        if (!empty($row['driver_uname']) && !empty($row['driver_pass'])) {
            // Account already exists
            echo json_encode(["status" => "has_account"]);
        } else {
            // Valid ID, no account yet
            echo json_encode(["status" => "ok", "driver_id" => $row['driver_id']]);
        }
    }
    exit;
}
?>
