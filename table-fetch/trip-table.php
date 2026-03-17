<?php
include '../php/config/config.php';

// -------------------------------
// Prevent notices from breaking JSON
// -------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Log errors to file
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/error_log.txt");

$columns = [
    'd_driverName', 
    'd_dispatchHub', 
    'trip1_from', 
    'trip2_from', 
    'trip1_segment', 
    'trip2_segment', 
    'd_datetime', 
    'd_dispatcher'
];

// -------------------------------
// Base query
// -------------------------------
$baseQuery = "
SELECT 
    d.*, 
    t1.trip_container AS trip1_container,
    t1.trip_containerStat AS trip1_containerStat,
    t1.trip_haulingSegment AS trip1_segment,
    t1.trip_haulingType AS trip1_haulingType,
    t1.trip_from AS trip1_from,
    t1.trip_to AS trip1_to,
    t1.trip_status AS trip1_status,
    t2.trip_container AS trip2_container,
    t2.trip_containerStat AS trip2_containerStat,
    t2.trip_haulingSegment AS trip2_segment,
    t2.trip_haulingType AS trip2_haulingType,
    t2.trip_from AS trip2_from,
    t2.trip_to AS trip2_to,
    t2.trip_status AS trip2_status
FROM dispatch d
LEFT JOIN trips t1 ON d.d_id = t1.d_id AND t1.trip_type = 'Trip 1'
LEFT JOIN trips t2 ON d.d_id = t2.d_id AND t2.trip_type = 'Trip 2'
";

// -------------------------------
// Default WHERE: only Active trips
// -------------------------------
$whereClauses = ["(t1.trip_status IN ('Done') OR t2.trip_status IN ('Done'))"];

// -------------------------------
// Customer filter
// -------------------------------
if (!empty($_POST['customer'])) {
    $customer = mysqli_real_escape_string($conn, $_POST['customer']);
    $whereClauses[] = "d.costumer = '$customer'";
}

if (!empty($_POST['fromDate']) && !empty($_POST['toDate'])) {
    $fromDate = mysqli_real_escape_string($conn, $_POST['fromDate']);
    $toDate   = mysqli_real_escape_string($conn, $_POST['toDate']);
    
    $whereClauses[] = "d.d_datetime BETWEEN '$fromDate' AND '$toDate'";
}

// -------------------------------
// Search filter
// -------------------------------
if (!empty($_POST["search"]["value"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
    $whereClauses[] = "(d.d_driverName LIKE '%$search%' 
                       OR d.d_dispatchHub LIKE '%$search%'
                       OR d.d_truck LIKE '%$search%' 
                       OR t1.trip_container LIKE '%$search%'
                       OR t2.trip_container LIKE '%$search%'
                       OR t1.trip_haulingSegment LIKE '%$search%' 
                       OR t2.trip_haulingSegment LIKE '%$search%')";
}

// Apply WHERE
$query = $baseQuery;
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

// -------------------------------
// Count total records (no filter)
// -------------------------------
$totalQuery = "SELECT COUNT(*) AS total FROM dispatch";
$totalRes = mysqli_query($conn, $totalQuery);
$totalData = ($totalRes) ? intval(mysqli_fetch_assoc($totalRes)['total']) : 0;

// -------------------------------
// Count after filter
// -------------------------------
$countQuery = "SELECT COUNT(*) AS total FROM dispatch d
LEFT JOIN trips t1 ON d.d_id = t1.d_id AND t1.trip_type = 'Trip 1'
LEFT JOIN trips t2 ON d.d_id = t2.d_id AND t2.trip_type = 'Trip 2'";

if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(" AND ", $whereClauses);
}

$countRes = mysqli_query($conn, $countQuery);
$totalFiltered = ($countRes) ? intval(mysqli_fetch_assoc($countRes)['total']) : 0;

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
        $sub_array[] = '';
        // Driver & Truck
        $sub_array[] = '
        <div class="d-flex align-items-center">
          <img src="../assets/images/profile/user-3.jpg" class="rounded-circle" width="40" alt="driver" />
          <div class="ms-3">
            <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['d_driverName']) . '</h6>
            <span class="text-muted">' . htmlspecialchars($row['d_truck']) . '</span>
          </div>
        </div>';
      $sub_array[] = date('Y-m-d', strtotime($row['d_datetime']));
        // Dispatch Hub
        $sub_array[] = htmlspecialchars($row['d_dispatchHub']);

        // Trip 1
        if (!empty($row['trip1_from'])) {
            $sub_array[] = '
            <div class="d-flex align-items-center">
              <div class="ms-3">
                <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['trip1_from']) . ' - ' . htmlspecialchars($row['trip1_to']) . '</h6>
                <span class="text-muted">Container: ' . htmlspecialchars($row['trip1_container']) . 
                ', Status: ' . htmlspecialchars($row['trip1_containerStat']) . 
                ', Segment: ' . htmlspecialchars($row['trip1_segment']) . 
                ', Trip Status: ' . htmlspecialchars($row['trip1_status']) . '</span>
              </div>
            </div>';
        } else {
            $sub_array[] = '<span class="text-muted">No Trip 1</span>';
        }

        // Trip 2
        if (!empty($row['trip2_from'])) {
            $sub_array[] = '
            <div class="d-flex align-items-center">
              <div class="ms-3">
                <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['trip2_from']) . ' - ' . htmlspecialchars($row['trip2_to']) . '</h6>
                <span class="text-muted">Container: ' . htmlspecialchars($row['trip2_container']) . 
                ', Status: ' . htmlspecialchars($row['trip2_containerStat']) . 
                ', Segment: ' . htmlspecialchars($row['trip2_segment']) . 
                ', Trip Status: ' . htmlspecialchars($row['trip2_status']) . '</span>
              </div>
            </div>';
        } else {
            $sub_array[] = '<span class="text-muted">No Trip 2</span>';
        }

        // Hauling + Type
        $sub_array[] = htmlspecialchars($row['trip1_segment'] . '-' . $row['trip2_segment']);
        $sub_array[] = htmlspecialchars($row['trip1_haulingType'] . '-' . $row['trip2_haulingType']);

        // Time
        $sub_array[] = date('H:i', strtotime($row['d_datetime']));

        // Dispatcher
        $sub_array[] = htmlspecialchars($row['d_dispatcher']);

        

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
