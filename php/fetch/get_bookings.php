<?php
include "../config/config.php";

$sql = "SELECT 
            booking_id, 
            booking_no, 
            booking_date, 
            booking_dateRequired,
            costumer, 
            container, 
            booking_activity,
            container_status,   
            hauling_segment, 
            hauling_type, 
            trip_from, 
            trip_to, 
            quantity, 
            quantity_use, 
            status,
            (quantity - quantity_use) AS available_qty
        FROM booking
        WHERE (quantity - quantity_use) > 0 AND costumer != 'CTH'
        ORDER BY booking_dateRequired ASC";  // ✅ sort by required date

$result = mysqli_query($conn, $sql);

$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = [
        "booking_id"      => $row['booking_id'],
        "img"             => "assets/images/logos/booking-icon.png",
        "bookingNo"       => $row['booking_no'],
        "costumerName"    => $row['costumer'],
        "location"        => $row['trip_from'] . " to " . $row['trip_to'],
        "others"          => $row['container'] . " (" . $row['container_status'] .")",
        "quantity"        => (int)$row['available_qty'],
        "bookingDate"     => $row['booking_date'],
        "dateRequired"    => $row['booking_dateRequired'] // ✅ pass required date
    ];
}

header('Content-Type: application/json');
echo json_encode($bookings);
