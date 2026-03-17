<?php
include "../config/config.php";

$response = [
    "total_booking" => 0,
    "today_booking" => 0,
    "today_completed" => 0
];

// TOTAL BOOKINGS
$sqlTotal = "SELECT COUNT(*) AS total FROM booking";
$resTotal = mysqli_query($conn, $sqlTotal);
$response['total_booking'] = mysqli_fetch_assoc($resTotal)['total'];

// TODAY'S BOOKINGS
$sqlToday = "
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE DATE(booking_date) = CURDATE()
";
$resToday = mysqli_query($conn, $sqlToday);
$response['today_booking'] = mysqli_fetch_assoc($resToday)['total'];

// TODAY'S COMPLETED TRIPS
$sqlCompleted = "
    SELECT COUNT(*) AS total
    FROM trips
    WHERE trip_status = 'Done'
      AND (
            deliver_dateTime >= CURDATE()
            AND deliver_dateTime < CURDATE() + INTERVAL 1 DAY
         OR withdraw_dateTime >= CURDATE()
            AND withdraw_dateTime < CURDATE() + INTERVAL 1 DAY
      )
";
$resCompleted = mysqli_query($conn, $sqlCompleted);
$response['today_completed'] = mysqli_fetch_assoc($resCompleted)['total'];

echo json_encode($response);
