<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $code = mysqli_real_escape_string($conn, $_POST['customer_code']);
    $name = mysqli_real_escape_string($conn, $_POST['customer_name']);

    if (empty($id) || empty($code) || empty($name)) {
        echo "Please fill all fields.";
        exit;
    }

    // Prevent duplicate customer_code except for current record
    $check = mysqli_query($conn, "SELECT * FROM customer WHERE customer_code = '$code' AND customer_id != '$id'");
    if (mysqli_num_rows($check) > 0) {
        echo "Customer Code already exists.";
        exit;
    }

    $sql = "UPDATE customer 
            SET customer_code = '$code', customer_name = '$name' 
            WHERE customer_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "Customer updated successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
