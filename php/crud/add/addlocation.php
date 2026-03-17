<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $location = mysqli_real_escape_string($conn, $_POST['location_name']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);

    if (empty($location)) {
        echo "Please fill all fields.";
        exit;
    } 

    $sql = "INSERT INTO location (location_name, latitude, longitude) VALUES ('$location', '$latitude', '$longitude')";

    if (mysqli_query($conn, $sql)) {
        echo "location data inserted successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
