<?php
include '../php/config/config.php';

$columns = array(
    '',                     // 0 - Action column / blank
    'd.d_driverName',       // 1 - Driver Name
    'd.d_trailer',          // 2 - Trailer
    'd.d_genset',           // 3 - Genset
    'd.d_dispatchHub',      // 4 - Dispatch Hub
    'd.d_datetime',         // 5 - Date & Time
    '',                     // 6 - Trip Info (Not sortable)
    '',                     // 7 - Hauling (Not sortable)
    'd.d_dispatcher',       // 8 - Dispatcher
    ''                      // 9 - Actions
);

// -------------------------------
// BASE QUERY (ONE ROW PER TRIP)
// -------------------------------
$query = "
SELECT 
    d.*, 
    t.trip_id,
    t.trip_type,
    t.trip_container,
    t.trip_containerStat,
    t.trip_haulingSegment,
    t.trip_haulingType,
    t.trip_from,
    t.trip_to
FROM dispatch d
INNER JOIN trips t ON d.d_id = t.d_id
";

$filter_query = $query;

// -------------------------------
// SEARCH
// -------------------------------
if (!empty($_POST["search"]["value"])) {

    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);

    $filter_query .= "
    WHERE d.d_driverName LIKE '%$search%'
       OR d.d_dispatchHub LIKE '%$search%'
       OR d.d_truck LIKE '%$search%'
       OR d.d_trailer LIKE '%$search%'
       OR d.d_genset LIKE '%$search%'
       OR t.trip_container LIKE '%$search%'
       OR t.trip_haulingSegment LIKE '%$search%'
       OR t.trip_from LIKE '%$search%'
       OR t.trip_to LIKE '%$search%'";
}

// -------------------------------
// FILTERED COUNT
// -------------------------------
$filtered_result = mysqli_query($conn, $filter_query);
$totalFiltered = mysqli_num_rows($filtered_result);

// -------------------------------
// ORDERING
// -------------------------------
if (isset($_POST["order"])) {

    $colIndex = intval($_POST['order'][0]['column']);
    $colDir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';

    if (!empty($columns[$colIndex])) {
        $filter_query .= " ORDER BY {$columns[$colIndex]} $colDir";
    } else {
        $filter_query .= " ORDER BY d.d_datetime DESC";
    }

} else {
    $filter_query .= " ORDER BY d.d_datetime DESC";
}

// -------------------------------
// PAGINATION
// -------------------------------
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$filter_query .= " LIMIT $start, $length";

$result = mysqli_query($conn, $filter_query);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    $sub_array = [];
    $sub_array[] = '';

    // Assigned
    $sub_array[] = '
    <div class="d-flex align-items-center">
        <img src="assets/images/profile/user-3.jpg" class="rounded-circle" width="40">
        <div class="ms-3">
            <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['d_driverName']) . '</h6>
            <span class="fw-bolder">' . htmlspecialchars($row['d_truck']) . '</span>
            <span class="fw-bolder">' . htmlspecialchars($row['d_trailer']) . '</span>
            <span class="fw-bolder">' . htmlspecialchars($row['d_genset']) . '</span>
        </div>
    </div>';

    $sub_array[] = htmlspecialchars($row['d_dispatchHub']);
    $sub_array[] = date('M d, Y h:i A', strtotime($row['d_datetime']));

    // -------------------------------
    // SINGLE TRIP COLUMN
    // -------------------------------
    $sub_array[] = '
    <div class="d-flex flex-column">
        <span class="badge mb-1">' . htmlspecialchars($row['trip_type']) . '</span>
        <span><strong>Route:</strong> ' . htmlspecialchars($row['trip_from']) . ' - ' . htmlspecialchars($row['trip_to']) . '</span>
        <span><strong>Container:</strong> ' . htmlspecialchars($row['trip_container']) . '</span>
        <span><strong>Status:</strong> ' . htmlspecialchars($row['trip_containerStat']) . '</span>
    </div>';

    // Hauling Column
    $sub_array[] = '
    <div class="d-flex flex-column">
        <span class="fw-bold">Segment: ' . htmlspecialchars($row['trip_haulingSegment']) . '</span>
        <span class="fw-bold">Hauling: ' . htmlspecialchars($row['trip_haulingType']) . '</span>
    </div>';

    $sub_array[] = htmlspecialchars($row['d_dispatcher']);

    // ACTIONS (use trip_id)
    $sub_array[] = '
    <div class="d-flex gap-2 justify-content-end">
        <button class="btn btn-sm btn-primary" onclick="editTrip(' . $row['trip_id'] . ')">Edit</button>
    </div>';

    $data[] = $sub_array;
}

// -------------------------------
// TOTAL RECORDS (Trips count now)
// -------------------------------
$totalRecordsQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips");
$totalRecords = mysqli_fetch_assoc($totalRecordsQuery)['total'];

$output = [
    "draw" => intval($_POST["draw"]),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
];

echo json_encode($output);
?>
