<?php
include '../php/config/config.php';

// -------------------------------
// Error handling
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/error_log.txt");

// -------------------------------
// Column mapping for DataTables
// -------------------------------
$columns = [
    'd.d_truck',
    'd.d_driverName',
    'd.d_datetime',
    't1.trip_from',
    't_last.trip_to',
    't1.trip_haulingSegment',
    't1.trip_haulingType',
    'd.d_datetime',
    'd.d_dispatcher',
    'd.d_dispatchHub'
];

// -------------------------------
// Base Query
// -------------------------------
$baseQuery = "
SELECT 
    d.d_id,
    d.d_driverName,
    d.d_truck,
    d.d_datetime,
    d.d_dispatcher,
    d.d_dispatchHub,
    -- First trip for Start Location
    t1.trip_from AS start_location,
    t1.trip_haulingSegment,
    t1.trip_haulingType,
    -- Last trip for Last Location
    t_last.trip_to AS last_location
FROM dispatch d
LEFT JOIN trips t1 
    ON d.d_id = t1.d_id AND t1.trip_type = 'Trip 1'
LEFT JOIN trips t_last 
    ON d.d_id = t_last.d_id 
    AND t_last.trip_id = (
        SELECT MAX(t3.trip_id) 
        FROM trips t3 
        WHERE t3.d_id = d.d_id
    )
";

// -------------------------------
// WHERE conditions
// -------------------------------
$whereClauses = ["1=1"];

$whereClauses[] = "d.d_truck IS NOT NULL AND d.d_truck <> ''";

// Customer filter
if (!empty($_POST['customer'])) {
    $customer = mysqli_real_escape_string($conn, $_POST['customer']);
    $whereClauses[] = "d.costumer = '$customer'";
}

// truck filter
if (!empty($_POST['truck'])) {
    $truck = mysqli_real_escape_string($conn, $_POST['truck']);
    $whereClauses[] = "d.d_truck = '$truck'";
}

// Date range filter
if (!empty($_POST['fromDate']) && !empty($_POST['toDate'])) {
    $fromDate = mysqli_real_escape_string($conn, $_POST['fromDate']);
    $toDate   = mysqli_real_escape_string($conn, $_POST['toDate']);
    $whereClauses[] = "DATE(d.d_datetime) BETWEEN '$fromDate' AND '$toDate'";
}

// Search filter
if (!empty($_POST["search"]["value"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
    $whereClauses[] = "(
        d.d_driverName LIKE '%$search%' 
        OR d.d_truck LIKE '%$search%'
        OR d.d_dispatchHub LIKE '%$search%' 
        OR t1.trip_haulingSegment LIKE '%$search%'
        OR t1.trip_from LIKE '%$search%'
        OR t_last.trip_to LIKE '%$search%'
    )";
}

$query = $baseQuery . " WHERE " . implode(" AND ", $whereClauses);

// -------------------------------
// Count total records
// -------------------------------
$totalQuery = "SELECT COUNT(*) AS total FROM dispatch";
$totalRes = mysqli_query($conn, $totalQuery);
$totalData = ($totalRes) ? intval(mysqli_fetch_assoc($totalRes)['total']) : 0;

// -------------------------------
// Count after filter
// -------------------------------
$filteredRes = mysqli_query($conn, $query);
$totalFiltered = ($filteredRes) ? mysqli_num_rows($filteredRes) : 0;

// -------------------------------
// Ordering
// -------------------------------
if (isset($_POST["order"])) {
    $colIndex = intval($_POST['order'][0]['column']);
    $colDir = ($_POST['order'][0]['dir'] === 'desc') ? 'DESC' : 'ASC';
    $query .= " ORDER BY {$columns[$colIndex]} $colDir";
} else {
    $query .= " ORDER BY d.d_id DESC";
}

// -------------------------------
// Pagination
// -------------------------------
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$query .= " LIMIT $start, $length";

// -------------------------------
// Fetch data
// -------------------------------
$data = [];
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sub_array = [];

        // truck ONLY (no unit)
        $sub_array[] = '
          <div class="d-flex align-items-center">
            <i class="bi bi-truck me-2"></i>
            <div>
              <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['d_truck']) . '</h6>
            </div>
          </div>';

        // Driver
        $sub_array[] = htmlspecialchars($row['d_driverName']);

        // Date
        $sub_array[] = date('Y-m-d', strtotime($row['d_datetime']));

        // Start Location
        $sub_array[] = !empty($row['start_location']) ? htmlspecialchars($row['start_location']) : '<span class="text-muted">N/A</span>';

        // Last Location
        $sub_array[] = !empty($row['last_location']) ? htmlspecialchars($row['last_location']) : '<span class="text-muted">N/A</span>';

        // Hauling Segment
        $sub_array[] = htmlspecialchars($row['trip_haulingSegment']);

        // Hauling Type
        $sub_array[] = htmlspecialchars($row['trip_haulingType']);

        // Time
        $sub_array[] = date('H:i', strtotime($row['d_datetime']));

        // Dispatcher
        $sub_array[] = htmlspecialchars($row['d_dispatcher']);

        // Dispatch Hub
        $sub_array[] = htmlspecialchars($row['d_dispatchHub']);

        $data[] = $sub_array;
    }
}

// -------------------------------
// JSON Response
// -------------------------------
$output = [
    "draw" => intval($_POST["draw"]),
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
];

echo json_encode($output);
