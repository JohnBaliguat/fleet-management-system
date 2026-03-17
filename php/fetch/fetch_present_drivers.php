<?php
include "../config/config.php";

/* MAIN QUERY: present drivers */
$sql = "
SELECT 
    d.driver_id,
    dp.d_id,
    CONCAT(d.driver_fname, ' ', d.driver_lname) AS driver_name,
    da.da_timeIn,
    MIN(dp.d_datetime) AS first_dispatch,
    CASE 
        WHEN COUNT(dp.d_id) > 0 THEN 1
        ELSE 0
    END AS has_dispatch
FROM drivers d
INNER JOIN drivers_attendance da 
    ON da.driver_id = d.driver_id
    AND da.da_date = CURDATE()
    AND da.da_status = 'Present'
LEFT JOIN dispatch dp
    ON dp.driver_id = d.driver_id
    AND DATE(dp.d_datetime) = CURDATE()
GROUP BY d.driver_id
ORDER BY da.da_timeIn ASC
";

$result = mysqli_query($conn, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    /* SECOND QUERY: count ACTIVE bookings/trips per driver */
    $d_id = $row['d_id'];

    $countSql = "
        SELECT COUNT(*) AS active_count
        FROM trips
        WHERE d_id = '$d_id'
          AND trip_status = 'Active'
    ";

    $countRes = mysqli_query($conn, $countSql);
    $countRow = mysqli_fetch_assoc($countRes);

    $row['active_booking_count'] = $countRow['active_count'] ?? 0;

    $data[] = $row;
}

echo json_encode($data);
