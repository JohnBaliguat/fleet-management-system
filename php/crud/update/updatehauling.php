<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['hauling_id']);
    $segment = mysqli_real_escape_string($conn, $_POST['hauling_segment']);
    $type = mysqli_real_escape_string($conn, $_POST['hauling_type']);

    if (empty($id) || empty($segment) || empty($type)) {
        echo "All fields are required.";
        exit;
    }

    $sql = "UPDATE hauling 
            SET hauling_segment = '$segment', hauling_type = '$type' 
            WHERE hauling_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "Hauling record updated successfully.";
    } else {
        echo "Update failed: " . mysqli_error($conn);
    }
}
?>
