<?php
include "../config/config.php";

$sql = "SELECT driver_id, driver_fname, driver_mname, driver_lname
        FROM drivers ORDER BY driver_lname ASC";
$result = mysqli_query($conn, $sql);

$drivers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $formattedName = strtoupper($row['driver_lname']) . ", " . strtoupper($row['driver_fname']) . ".";

    

    $drivers[] = [
        'id' => $row['driver_id'],
        'name' => $formattedName
    ];
}

header('Content-Type: application/json');
echo json_encode($drivers);
