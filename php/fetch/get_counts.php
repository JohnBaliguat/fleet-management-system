<?php
include "../config/config.php";

// Customers to display
$customers = ['ABC','CTH','DPC','DM','DICT','DOLE','FARM','GF','SUMI','TDC'];

$query = "
    SELECT d.costumer, COUNT(*) as count
    FROM dispatch d
    INNER JOIN trips t ON d.d_id = t.d_id
    WHERE t.trip_status != 'Done'
    GROUP BY d.costumer
";

$result = mysqli_query($conn, $query);

// Initialize all customers to 0
$counts = array_fill_keys($customers, 0);

while ($row = mysqli_fetch_assoc($result)) {
    $cust = $row['costumer'];
    if (array_key_exists($cust, $counts)) {
        $counts[$cust] = $row['count'];
    }
}

echo json_encode($counts);
?>
