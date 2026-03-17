<?php
// getData.php
header('Content-Type: application/json; charset=utf-8');

include "../config/config.php";

// optional filters
$segment = isset($_GET['segment']) ? $mysqli->real_escape_string($_GET['segment']) : null;
$detail  = isset($_GET['detail']) ? $mysqli->real_escape_string($_GET['detail']) : null;

// If a detail is requested, return single trip detail
if ($detail) {
    $sql = "
    SELECT d.*, t.*
    FROM dispatch d
    LEFT JOIN trips t ON d.d_id = t.d_id
    WHERE t.trip_id = ?
    LIMIT 1
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $detail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        echo json_encode(['detail' => $row]);
    } else {
        echo json_encode(['detail' => null]);
    }
    exit;
}

// Main query
// Logic:
// - include trips whose required_date is today
// - OR include trips whose trip_container has any trip where trip_status is NOT 'Completed' (so container remains if activities not done)
// Note: adjust column names/status strings to match your real values if they differ
$todayCondition = "DATE(t.required_date) = CURDATE()";

$nonCompletedContainersSubquery = "
  SELECT DISTINCT tt.trip_container
  FROM trips tt
  WHERE tt.trip_status IS NULL OR LOWER(tt.trip_status) NOT LIKE '%done%'
";

$whereParts = [];
$whereParts[] = "(" . $todayCondition . " OR t.trip_container IN (" . $nonCompletedContainersSubquery . "))";

if ($segment) {
    // match either dispatch costumer or trip costumer
    $segEsc = $mysqli->real_escape_string($segment);
    $whereParts[] = "(LOWER(d.costumer) = LOWER('".$segEsc."') OR LOWER(t.costumer) = LOWER('".$segEsc."'))";
}

$where = implode(" AND ", $whereParts);

$sql = "
SELECT 
    d.d_id, d.booking_no, d.booking_sn, d.booking_do, d.cth_broker, d.cth_EIROut,
    d.d_datetime, d.d_dispatcher, d.d_dispatchHub, d.d_driverName, d.driver_id,
    d.d_truck, d.d_trailer, d.d_genset, d.d_tripReceipt, d.d_ecs, d.costumer AS dispatch_customer,

    t.trip_id, t.d_id AS t_d_id, t.trip_type, t.costumer AS trip_customer, t.trip_containerType,
    t.trip_container, t.container_activity, t.trip_containerStat,
    t.trip_haulingSegment, t.trip_haulingType, t.trip_from, t.trip_to,
    t.return_location, t.km_run,
    t.trip_departureDateTime, t.trip_arrivalDateTime, t.trip_pharrivalDateTime,
    t.deliver_location, t.deliver_dateTime, t.withdraw_location, t.withdraw_dateTime,
    t.required_date, t.trip_status,
    t.deliver_location, t.withdraw_location
FROM dispatch d
LEFT JOIN trips t ON d.d_id = t.d_id
WHERE {$where}
ORDER BY t.trip_departureDateTime DESC, t.trip_id DESC
LIMIT 1000
";

$res = $mysqli->query($sql);
if (!$res) {
    http_response_code(500);
    echo json_encode(["error" => "Query error: " . $mysqli->error]);
    exit;
}

// Group into empty/loaded/returned arrays based on container_activity
$empty = [];
$loaded = [];
$returned = [];

while ($row = $res->fetch_assoc()) {
    // standardize customer field for frontend filter convenience
    if (empty($row['trip_customer']) && !empty($row['dispatch_customer'])) {
        $row['trip_customer'] = $row['dispatch_customer'];
    }

    // normalize activity label to decide tab
    $activity = strtolower(trim($row['container_activity'] ?? ''));
    if ($activity === 'empty' || $activity === 'empties' || strpos($activity, 'empty') !== false) {
        $empty[] = $row;
    } elseif ($activity === 'loaded' || $activity === 'load' || strpos($activity, 'load') !== false) {
        $loaded[] = $row;
    } elseif ($activity === 'return' || $activity === 'returned' || strpos($activity, 'return') !== false) {
        $returned[] = $row;
    } else {
        // if the activity is ambiguous, attempt to classify by trip_containerStat or trip_type:
        $stat = strtolower($row['trip_containerStat'] ?? '');
        if (strpos($stat, 'empty') !== false) $empty[] = $row;
        else if (strpos($stat, 'full') !== false || strpos($stat, 'loaded') !== false) $loaded[] = $row;
        else $loaded[] = $row; // fallback to loaded
    }
}

echo json_encode([
    'empty' => $empty,
    'loaded' => $loaded,
    'returned' => $returned
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$mysqli->close();
