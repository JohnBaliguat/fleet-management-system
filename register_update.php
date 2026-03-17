<?php
include "php/config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_POST['driver_id'];
    $uname = $_POST['uname'];
    $pass = $_POST['pass'];

    if (empty($driver_id) || empty($uname) || empty($pass)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    // ✅ Strong password check
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters, include uppercase, lowercase, number, and special character."]);
        exit;
    }

    // Check if username already exists
    $check = $conn->prepare("SELECT driver_id FROM drivers WHERE driver_uname = ?");
    $check->bind_param("s", $uname);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username already taken!"]);
        exit;
    }
    $check->close();

    // Update account
    $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE drivers SET driver_uname=?, driver_pass=?, driver_account_status='Pending' WHERE driver_id=?");
    $stmt->bind_param("ssi", $uname, $hashedPass, $driver_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Your account has been created successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Account already activated or invalid ID."]);
    }

    $stmt->close();
    $conn->close();
}
?>
