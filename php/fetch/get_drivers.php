<?php
include "../config/config.php";

$today = date('Y-m-d');

$sql = "
    SELECT DISTINCT 
        d.driver_id,
        d.driver_fname,
        d.driver_mname,
        d.driver_lname,
        d.driver_status
    FROM drivers d
    INNER JOIN drivers_attendance da 
        ON da.driver_id = d.driver_id
    WHERE 
        da.da_date = '$today'
        AND da.da_status = 'Present'
        AND d.driver_status != 'Dispatch'
        AND da.da_timeIn IS NOT NULL
        AND da.da_timeOut IS NULL
    ORDER BY d.driver_lname ASC
";

$result = mysqli_query($conn, $sql);

$drivers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $formattedName = strtoupper($row['driver_lname']) . ", " . strtoupper($row['driver_fname']) . ".";

    $drivers[] = [
        'id'   => $row['driver_id'],
        'name' => $formattedName
    ];
}

header('Content-Type: application/json');
echo json_encode($drivers);
