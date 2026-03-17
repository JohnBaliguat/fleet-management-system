<?php
include "../config/config.php";

$query = "SELECT hauling_id, hauling_segment, hauling_type FROM hauling";
$result = mysqli_query($conn, $query);

$hauling = [];
while ($row = mysqli_fetch_assoc($result)) {
    $hauling[] = $row;
}

echo json_encode($hauling);
?>
