<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trailer_name = $_POST['trailer_name'];
    $trailer_plateNo = $_POST['trailer_plateNo'];
    $trailer_location = $_POST['trailer_location'];
    $trailer_status = "Good";

    $sql = "INSERT INTO trailer (trailer_name, trailer_plateNo, trailer_location, trailer_status) 
            VALUES ('$trailer_name', '$trailer_plateNo', '$trailer_location', '$trailer_status')";

    if (mysqli_query($conn, $sql)) {
        echo "Trailer added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
