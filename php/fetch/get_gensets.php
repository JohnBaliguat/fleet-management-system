<?php
include "../config/config.php";

$sql = "SELECT unit_name 
        FROM units 
        WHERE unit_name LIKE 'GS%' 
          AND unit_status = 'Good'
        ORDER BY unit_name ASC";
$result = mysqli_query($conn, $sql);

$gensets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $gensets[] = $row['unit_name'];
}

header('Content-Type: application/json');
echo json_encode($gensets);
