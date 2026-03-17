<?php
include "../config/config.php";

$query = "SELECT unit_id, unit_name FROM units WHERE unit_name IS NOT NULL AND unit_name <> ''";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode($data);
?>
