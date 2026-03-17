<?php
include '../php/config/config.php';

// Total Drivers
$totalDriversQuery = "SELECT COUNT(*) as total FROM drivers";
$totalDriversResult = mysqli_query($conn, $totalDriversQuery);
$totalDrivers = mysqli_fetch_assoc($totalDriversResult)['total'];

// Dispatch
$dispatchQuery = "SELECT COUNT(*) as total FROM drivers WHERE driver_status='Dispatch'";
$dispatchResult = mysqli_query($conn, $dispatchQuery);
$dispatch = mysqli_fetch_assoc($dispatchResult)['total'];

// N-Dispatch
$ndispatchQuery = "SELECT COUNT(*) as total FROM drivers WHERE driver_status !='Dispatch'";
$ndispatchResult = mysqli_query($conn, $ndispatchQuery);
$ndispatch = mysqli_fetch_assoc($ndispatchResult)['total'];

// VL/SL
$today = date('Y-m-d');
$vlslQuery = "
    SELECT COUNT(*) AS total 
    FROM drivers_attendance 
    WHERE (da_status='VL' OR da_status='SL')
      AND vl_sl_dateStart IS NOT NULL 
      AND vl_sl_date IS NOT NULL
      AND '$today' BETWEEN vl_sl_dateStart AND vl_sl_date
";
$vlslResult = mysqli_query($conn, $vlslQuery);
$vlsl = mysqli_fetch_assoc($vlslResult)['total'];

// Others
$othersQuery = "SELECT COUNT(*) AS total 
    FROM drivers_attendance 
    WHERE da_status='Others'
      AND da_date = CURDATE()";
$othersResult = mysqli_query($conn, $othersQuery);
$others = mysqli_fetch_assoc($othersResult)['total'];

// Rest Day
$restDayQuery = "SELECT COUNT(*) as total FROM drivers_attendance WHERE da_status='Absent' AND da_date = CURDATE()";
$restDayResult = mysqli_query($conn, $restDayQuery);
$restDay = mysqli_fetch_assoc($restDayResult)['total'];

echo json_encode([
    "totalDrivers" => $totalDrivers,
    "dispatch" => $dispatch,
    "ndispatch" => $ndispatch,
    "vlsl" => $vlsl,
    "others" => $others,
    "restDay" => $restDay
]);
?>
