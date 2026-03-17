<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trailer_ids = explode(",", $_POST['trailer_ids']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $recordedBy = mysqli_real_escape_string($conn, $_POST['recordedBy']);
    $approvedBy = mysqli_real_escape_string($conn, $_POST['approvedBy']);
    $dateTime = mysqli_real_escape_string($conn, $_POST['dateTime']);
    $container = !empty($_POST['container']) ? mysqli_real_escape_string($conn, $_POST['container']) : "";
    $remarks   = !empty($_POST['remarks']) ? mysqli_real_escape_string($conn, $_POST['remarks']) : "";

    foreach ($trailer_ids as $id) {
        // Update trailer table
        $update = "UPDATE trailer 
                   SET trailer_location='$location', 
                       t_recordedBy='$recordedBy', 
                       t_approvedBy='$approvedBy', 
                       t_date='$dateTime',
                       trailer_container='$container',
                       trailer_remarks='$remarks'
                   WHERE trailer_id='$id'";
        mysqli_query($conn, $update);

        // Get trailer name
        $res = mysqli_query($conn, "SELECT trailer_name FROM trailer WHERE trailer_id='$id'");
        $row = mysqli_fetch_assoc($res);
        $trailerName = $row['trailer_name'];

        // Insert into trailer_movement
        $insert = "INSERT INTO trailer_movement (tm_trailerName, tm_location, tm_recordedType, tm_recordedBy, tm_approvedBy, tm_date, tm_container, tm_remarks)
                   VALUES ('$trailerName', '$location', 'Inventory', '$recordedBy', '$approvedBy', '$dateTime', '$container', '$remarks')";
        mysqli_query($conn, $insert);
    }

    echo "Trailer information updated successfully.";
}
?>
