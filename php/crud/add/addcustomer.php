<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_code = mysqli_real_escape_string($conn, $_POST['customer_code']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);

    if (empty($customer_code) || empty($customer_name)) {
        echo "Please fill all fields.";
        exit;
    }

    // Prevent duplicate customer_code
    $check = mysqli_query($conn, "SELECT * FROM customer WHERE customer_code = '$customer_code'");
    if (mysqli_num_rows($check) > 0) {
        echo "Customer Code already exists.";
        exit;
    }

    $sql = "INSERT INTO customer (customer_code, customer_name) 
            VALUES ('$customer_code', '$customer_name')";

    if (mysqli_query($conn, $sql)) {
        echo "Customer added successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
