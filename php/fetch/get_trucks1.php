<?php
include "../config/config.php";

$sql = "SELECT unit_name 
        FROM units
        WHERE  unit_name NOT LIKE 'GS%'
        ORDER BY unit_name ASC";
$result = mysqli_query($conn, $sql);

$trailers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $trailers[] = $row['unit_name'];
}

header('Content-Type: application/json');
echo json_encode($trailers);
