<?php
include "../config/config.php";

$sql = "SELECT trailer_name 
        FROM trailer 
        WHERE trailer_status = 'Good'
        ORDER BY trailer_name ASC";
$result = mysqli_query($conn, $sql);

$trailers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $trailers[] = $row['trailer_name'];
}

header('Content-Type: application/json');
echo json_encode($trailers);
