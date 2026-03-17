<?php
include "../../config/config.php";

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assign form values to variables
    $username = $_POST['username'];
    $firstname = $_POST['user_fname'];
    $lastname = $_POST['user_lname'];
    $middlename = $_POST['user_mname'];
    $useremail = $_POST['user_email'];
    $userpass = $_POST['user_pass'];
    $hashedPassword = password_hash($userpass, PASSWORD_DEFAULT); // Hash the password
    $user_type = $_POST['user_type'];
    $user_assignLocation = $_POST['user_assignLocation'];


    // Check if an image was uploaded
    if(isset($_FILES['user_image']) && $_FILES['user_image']['size'] > 0) {
        $image = $_FILES['user_image']['name'];
        $tempname = $_FILES['user_image']['tmp_name'];
        $folder = 'uploads/' . $image;

        $file_ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));

        // Check if the file extension is allowed
        if (!in_array($file_ext, array('png', 'jpg', 'jpeg'))) {
            echo "Error: Only PNG, JPG, and JPEG images are allowed.";
            exit;
        }

        // Move the uploaded image to the uploads folder
        if (move_uploaded_file($tempname, $folder)) {
            // Insert the data into the database
            $sql = "INSERT INTO user (user_name, user_fname, user_lname, user_mname, user_assignLocation, user_email, user_pass, user_type, user_image, user_accountStat) VALUES ('$username', '$firstname', '$lastname', '$middlename', '$user_assignLocation', '$useremail', '$hashedPassword', '$user_type', '$image', 'Pending')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                echo "Data inserted successfully!";
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            echo "Error: Failed to move uploaded file.";
        }
    } else {
        // Insert the data into the database without the image
        $sql = "INSERT INTO user (user_name, user_fname, user_lname, user_mname, user_assignLocation, user_email, user_pass, user_type, user_accountStat) VALUES ('$username', '$firstname', '$lastname', '$middlename', '$user_assignLocation', '$useremail', '$hashedPassword', '$user_type', 'Pending')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            echo "Data inserted successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>
