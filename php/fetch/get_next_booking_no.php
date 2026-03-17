<?php
include "../config/config.php";

$result = mysqli_query($conn, "SELECT booking_no FROM booking ORDER BY booking_id DESC LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $last = (int)substr($row['booking_no'], -5);
    $next = $last + 1;
} else {
    $next = 1;
}
echo 'PTSIBN-' . str_pad($next, 5, '0', STR_PAD_LEFT);
?>
