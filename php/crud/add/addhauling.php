<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $segment = mysqli_real_escape_string($conn, $_POST['hauling_segment']);
    $type = mysqli_real_escape_string($conn, $_POST['hauling_type']);

    if (empty($segment) || empty($type)) {
        echo "Please fill all fields.";
        exit;
    }

    $sql = "INSERT INTO hauling (hauling_segment, hauling_type) VALUES ('$segment', '$type')";

    if (mysqli_query($conn, $sql)) {
        echo "Hauling data inserted successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
