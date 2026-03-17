<?php
include '../php/config/config.php';

$driverId = intval($_POST['driverId'] ?? 0);
$fromDate = $_POST['fromDate'] ?? '';
$toDate   = $_POST['toDate'] ?? '';

if ($driverId <= 0) {
    echo json_encode([]);
    exit;
}

$dateCondition = '';
if (!empty($fromDate) && !empty($toDate)) {
    $from = mysqli_real_escape_string($conn, $fromDate);
    $to = mysqli_real_escape_string($conn, $toDate);
    $dateCondition = " AND DATE(d.d_datetime) BETWEEN '$from' AND '$to'";
}

$sql = "
SELECT
    t.trip_id,
    u.unit_name AS unit,
    t.costumer AS customer,
    t.trip_haulingSegment AS segment,
    t.trip_status AS status,
    DATE_FORMAT(d.d_datetime, '%M %d, %Y %h:%i %p') AS date
FROM trips t
INNER JOIN dispatch d ON t.d_id = d.d_id
LEFT JOIN units u ON d.d_truck = u.unit_name
WHERE d.driver_id = $driverId
$dateCondition
ORDER BY d.d_datetime DESC
";

$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'trip_id' => $row['trip_id'],
        'unit' => $row['unit'] ?: '-',
        'customer' => $row['customer'] ?: '-',
        'segment' => $row['segment'] ?: '-',
        'status' => $row['status'],
        'date' => $row['date']
    ];
}

// Get trips by segment summary
$segmentSql = "
SELECT
    t.trip_haulingSegment AS segment,
    COUNT(t.trip_id) AS trip_count
FROM trips t
INNER JOIN dispatch d ON t.d_id = d.d_id
WHERE d.driver_id = $driverId
$dateCondition
GROUP BY t.trip_haulingSegment
ORDER BY trip_count DESC
";

$segmentResult = mysqli_query($conn, $segmentSql);

$segmentSummary = [];
while ($row = mysqli_fetch_assoc($segmentResult)) {
    $segmentSummary[] = [
        'segment' => $row['segment'] ?: 'Unknown',
        'count' => (int) $row['trip_count']
    ];
}

echo json_encode([
    'trips' => $data,
    'segment_summary' => $segmentSummary
]);
?>
