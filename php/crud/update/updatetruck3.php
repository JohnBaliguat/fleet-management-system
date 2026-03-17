<?php
include "../../config/config.php";  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['unit_id1']; 
    $unit_name = $_POST['unit_name_update'];
    $unit_std = $_POST['unit_std_update'];
    $unit_plate = $_POST['unit_plate_update'];
    $unit_address = $_POST['unit_location_update'];
    $unit_status = "Good"; 

    // Update query
    $sql = "UPDATE units 
            SET unit_name='$unit_name', unit_std='$unit_std', unit_plate='$unit_plate', unit_address='$unit_address', unit_status='$unit_status' 
            WHERE unit_id='$id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        date_default_timezone_set("Asia/Manila");
        $now = date("Y-m-d H:i:s");

        $update_rescue = "UPDATE rescue_unit 
                          SET r_status='Good', r_endDateTime= '$now' 
                          WHERE r_unit='$unit_name' 
                          AND r_status='Active' 
                          ORDER BY r_id DESC 
                          LIMIT 1"; 

        $rescue_result = mysqli_query($conn, $update_rescue);

        if ($rescue_result) {
            echo "Truck unit and rescue unit updated successfully!";
        } else {
            $error_message = "Error updating rescue unit: " . mysqli_error($conn);
            http_response_code(500);
            echo $error_message;
            error_log($error_message, 0);
        }
    } else {
        $error_message = "Error: " . mysqli_error($conn);
        http_response_code(500);
        echo $error_message;
        error_log($error_message, 0);
    }
}
?>
