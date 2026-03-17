<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['driver_id1'];
    $driver_rfid = $_POST['driver_rfid1'];
    $driver_IdNumber = $_POST['driver_IdNumber1'];
    $firstname = $_POST['driver_fname1'];
    $middlename = $_POST['driver_mname1'];
    $lastname = $_POST['driver_lname1'];
    $driver_username = $_POST['driver_username1'];
    $driver_pass = $_POST['driver_pass1'];
    $driver_assignUnit = $_POST['driver_assignUnit1'];
    $driver_assignSegment = $_POST['driver_assignSegment1'];
    $driver_assignBase = $_POST['driver_assignBase1'];

    $status = $_POST['status'];


    $image = '';

    // Check if a new image was uploaded
    if (isset($_FILES['driver_image1']['name']) && !empty($_FILES['driver_image1']['name'])) {
        $image = $_FILES['driver_image1']['name'];
        $tempname = $_FILES['driver_image1']['tmp_name'];
        $folder = '../../assets/uploads/' . $image;

        // Move uploaded image
        move_uploaded_file($tempname, $folder);
    }

    // Update SQL depending on image upload
    if (!empty($driver_pass)) {
        $hashedPassword = password_hash($driver_pass, PASSWORD_DEFAULT);
        if (!empty($image)) {
            $sql = "UPDATE drivers 
                SET driver_IdNumber='$driver_IdNumber', 
                    driver_rfid='$driver_rfid',
                    driver_fname='$firstname', 
                    driver_mname='$middlename', 
                    driver_lname='$lastname', 
                    driver_assignUnit='$driver_assignUnit',
                    driver_assignSegment='$driver_assignSegment',
                    driver_assignBase='$driver_assignBase',
                    driver_image='$image',
                    driver_uname='$driver_username',
                    driver_pass='$hashedPassword',
                    driver_account_status='$status'
                WHERE driver_id='$id'";
        } else {
            $sql = "UPDATE drivers 
                SET driver_IdNumber='$driver_IdNumber', 
                    driver_rfid='$driver_rfid',
                    driver_fname='$firstname', 
                    driver_mname='$middlename', 
                    driver_lname='$lastname',
                    driver_assignUnit='$driver_assignUnit',
                    driver_assignSegment='$driver_assignSegment',
                    driver_assignBase='$driver_assignBase',
                    driver_uname='$driver_username',
                    driver_pass='$hashedPassword',
                    driver_account_status='$status' 
                WHERE driver_id='$id'";
        }
    } else {
        if (!empty($image)) {
            $sql = "UPDATE drivers 
                SET driver_IdNumber='$driver_IdNumber', 
                    driver_rfid='$driver_rfid',
                    driver_fname='$firstname', 
                    driver_mname='$middlename', 
                    driver_lname='$lastname', 
                    driver_assignUnit='$driver_assignUnit',
                    driver_assignSegment='$driver_assignSegment',
                    driver_assignBase='$driver_assignBase',
                    driver_image='$image',
                    driver_uname='$driver_username',
                    driver_account_status='$status'
                WHERE driver_id='$id'";
        } else {
            $sql = "UPDATE drivers 
                SET driver_IdNumber='$driver_IdNumber', 
                    driver_rfid='$driver_rfid',
                    driver_fname='$firstname', 
                    driver_mname='$middlename', 
                    driver_lname='$lastname',
                    driver_assignUnit='$driver_assignUnit',
                    driver_assignSegment='$driver_assignSegment',
                    driver_assignBase='$driver_assignBase',
                    driver_uname='$driver_username',
                    driver_account_status='$status' 
                WHERE driver_id='$id'";
        }
    }

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "Driver data updated successfully!";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
        http_response_code(500);
        echo $error_message;
        error_log($error_message, 0);
    }
}
