<?php
include '../php/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/error_log.txt");

$columns = [
    'd_driverName',
    'd_dispatchHub',
    'trip_from',
    'trip_haulingSegment',
    'd_datetime',
    'd_dispatcher'
];

// -------------------------------
// Base Query (ONE ROW PER TRIP)
// -------------------------------
$baseQuery = "
SELECT 
    d.*, 
    t.trip_id,
    t.trip_type,
    t.trip_container,
    t.trip_containerStat,
    t.trip_haulingSegment,
    t.trip_haulingType,
    t.trip_from,
    t.trip_to,
    t.trip_status
FROM dispatch d
INNER JOIN trips t ON d.d_id = t.d_id
";

// -------------------------------
// WHERE conditions
// -------------------------------
$whereClauses = ["t.trip_status != 'Done'"];

// Customer filter
if (!empty($_POST['customer'])) {
    $customer = mysqli_real_escape_string($conn, $_POST['customer']);
    $whereClauses[] = "t.costumer = '$customer'";
}

// Search filter
if (!empty($_POST["search"]["value"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
    $whereClauses[] = "(d.d_driverName LIKE '%$search%' 
                       OR d.d_dispatchHub LIKE '%$search%' 
                       OR d.d_truck LIKE '%$search%' 
                       OR t.trip_container LIKE '%$search%'
                       OR t.trip_haulingSegment LIKE '%$search%')";
}

$query = $baseQuery;

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

// -------------------------------
// Total records
// -------------------------------
$totalQuery = "SELECT COUNT(*) AS total FROM trips";
$totalRes = mysqli_query($conn, $totalQuery);
$totalData = ($totalRes) ? intval(mysqli_fetch_assoc($totalRes)['total']) : 0;

// -------------------------------
// Filtered count
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
// Fetch Data
// -------------------------------
$data = [];
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {

        $sub_array = [];
        $sub_array[] = '';

        // Driver Info
        $sub_array[] = '
        <div class="d-flex align-items-center">
          <img src="../assets/images/profile/user-3.jpg" class="rounded-circle" width="40">
          <div class="ms-3">
            <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['d_driverName']) . '</h6>
            <span class="fw-bolder">' . htmlspecialchars($row['d_truck']) . '</span>
            <span class="fw-bolder">' . htmlspecialchars($row['d_trailer']) . '</span>
            <span class="fw-bolder">' . htmlspecialchars($row['d_genset']) . '</span>
          </div>
        </div>';

        // Date
        $sub_array[] = date('M d, Y h:i A', strtotime($row['d_datetime']));

        // Trip Info (Single Trip Only)
        $sub_array[] = '
        <div class="d-flex flex-column">
            <span class="fw-bold">' . htmlspecialchars($row['trip_type']) . '</span>
            <span>Route: ' . htmlspecialchars($row['trip_from']) . ' - ' . htmlspecialchars($row['trip_to']) . '</span>
            <span>Container: ' . htmlspecialchars($row['trip_container']) . '</span>
            <span>Status: ' . htmlspecialchars($row['trip_status']) . '</span>
        </div>';

        // Segment & Hauling
        $sub_array[] = '
        <div class="d-flex flex-column">
            <span class="fw-bold">Segment: ' . htmlspecialchars($row['trip_haulingSegment']) . '</span>
            <span class="fw-bold">Hauling: ' . htmlspecialchars($row['trip_haulingType']) . '</span>
        </div>';

        // Dispatcher
        $sub_array[] = htmlspecialchars($row['d_dispatcher'].'/'.$row['d_dispatchHub']);

        // Actions (IMPORTANT: use trip_id now)
        $sub_array[] = '
        <div class="d-flex gap-2 justify-content-end">
          <button class="btn btn-sm btn-success" onclick="markDone(' . $row['trip_id'] . ')">Done</button>
          <button class="btn btn-sm btn-primary" onclick="editTrip(' . $row['trip_id'] . ')" hidden>Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteTrip(' . $row['trip_id'] . ')">Delete</button>
        </div>';

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
