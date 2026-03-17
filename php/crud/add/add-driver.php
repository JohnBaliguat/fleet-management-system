<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $driver_IdNumber      = $_POST['driver_IdNumber'];
    $driver_rfid          = $_POST['driver_rfid'] ?? null;
    $driver_fname         = $_POST['driver_fname'];
    $driver_mname         = $_POST['driver_mname'];
    $driver_lname         = $_POST['driver_lname'];
    $driver_assignUnit    = $_POST['driver_assignUnit'];
    $driver_assignSegment = $_POST['driver_assignSegment'];
    $driver_assignBase    = $_POST['driver_assignBase'];

    $driver_image = "";

    /* IMAGE UPLOAD */
    if (!empty($_FILES['driver_image']['name'])) {

        $image_name = $_FILES['driver_image']['name'];
        $tmp_name   = $_FILES['driver_image']['tmp_name'];
        $ext        = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            echo "Only JPG, JPEG, and PNG files are allowed.";
            exit;
        }

        $new_name = uniqid('driver_', true) . '.' . $ext;
        $upload_path = '../../assets/uploads/' . $new_name;

        if (!move_uploaded_file($tmp_name, $upload_path)) {
            echo "Failed to upload image.";
            exit;
        }

        $driver_image = $new_name;
    }

    /* INSERT QUERY */
    $sql = "INSERT INTO drivers (
                driver_IdNumber,
                driver_rfid,
                driver_fname,
                driver_mname,
                driver_lname,
                driver_image,
                driver_assignUnit,
                driver_assignSegment,
                driver_assignBase,
                driver_status
            ) VALUES (
                '$driver_IdNumber',
                '$driver_rfid',
                '$driver_fname',
                '$driver_mname',
                '$driver_lname',
                '$driver_image',
                '$driver_assignUnit',
                '$driver_assignSegment',
                '$driver_assignBase',
                'Good'
            )";

    if (mysqli_query($conn, $sql)) {
        echo "Driver added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
