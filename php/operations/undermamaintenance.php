<?php
include "../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $id = mysqli_real_escape_string($conn, $_POST['trailer_id1']);
    $trailer_name = mysqli_real_escape_string($conn, $_POST['trailer_name_update'] ?? '');
    $trailer_plateNo = mysqli_real_escape_string($conn, $_POST['trailer_plateNo_update'] ?? '');
    $trailer_assignTo = mysqli_real_escape_string($conn, $_POST['trailer_assignTo_update'] ?? '');
    $trailer_location = mysqli_real_escape_string($conn, $_POST['trailer_location_update'] ?? '');
    $trailer_status = "Under Maintenance";
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? ''); // ✅ fixed name

    // Update trailer table
    $sql = "UPDATE trailer 
            SET trailer_name='$trailer_name', 
                trailer_plateNo='$trailer_plateNo', 
                trailer_assignTo='$trailer_assignTo', 
                trailer_location='$trailer_location',
                trailer_status='$trailer_status'
            WHERE trailer_id='$id'";

    if (mysqli_query($conn, $sql)) {
        // Insert new record into trailer_movement table
        $tm_recordedType = 'Inventory';
        $tm_recordedBy = 'System'; // ✅ fixed typo
        $tm_approvedBy = '';
        $tm_date = date('Y-m-d H:i:s');
        $tm_container = '';

        $insert_sql = "INSERT INTO trailer_movement 
            (tm_trailerName, tm_trailer_status, tm_driverAssign, tm_location, tm_recordedType, tm_recordedBy, tm_approvedBy, tm_date, tm_container, tm_remarks)
            VALUES 
            ('$trailer_name', '$trailer_status', '$trailer_assignTo', '$trailer_location', '$tm_recordedType', '$tm_recordedBy', '$tm_approvedBy', '$tm_date', '$tm_container', '$remarks')";

        if (mysqli_query($conn, $insert_sql)) {
            echo "Trailer updated and movement recorded successfully!";
        } else {
            $error_message = "Error inserting trailer movement: " . mysqli_error($conn);
            http_response_code(500);
            echo $error_message;
            error_log($error_message);
        }
    } else {
        $error_message = "Error updating trailer: " . mysqli_error($conn);
        http_response_code(500);
        echo $error_message;
        error_log($error_message);
    }
}
?>
