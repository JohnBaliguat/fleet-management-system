<?php
include '../php/config/config.php';

$query = "SELECT driver_id, driver_fname, driver_mname, driver_lname FROM drivers ORDER BY driver_lname ASC";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $fullname = $row['driver_fname'] . ' ' . $row['driver_mname'] . ' ' . $row['driver_lname'];
    $data[] = [
        'driver_id' => $row['driver_id'],
        'driver_name' => $fullname
    ];
}

echo json_encode($data);
?>
