<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['location_id']);
    $location = mysqli_real_escape_string($conn, $_POST['location_name']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    if (empty($id) || empty($location)) {
        echo "All fields are required.";
        exit;
    }

    $sql = "UPDATE location SET location_name = '$location', latitude = '$latitude', longitude = '$longitude' WHERE location_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "Location record updated successfully.";
    } else {
        echo "Update failed: " . mysqli_error($conn);
    }
}
?>
