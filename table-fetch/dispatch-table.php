<?php
include '../php/config/config.php';

$columns = array(
    '',                     // 0 - Action column / blank
    'd.d_driverName',       // 1 - Driver Name
    'd.d_trailer',          // 2 - Trailer
    'd.d_genset',           // 3 - Genset
    'd.d_dispatchHub',      // 4 - Dispatch Hub
    'd.d_datetime',         // 5 - Date & Time (DESC FIXED)
    '',                     // 6 - Trips (combined) -> Not sortable
    '',                     // 7 - Hauling (combined) -> Not sortable
    'd.d_dispatcher',       // 8 - Dispatcher
    ''                      // 9 - Actions
);

$query = "
SELECT 
    d.*, 
    t1.trip_container AS trip1_container,
    t1.trip_containerStat AS trip1_status,
    t1.trip_haulingSegment AS trip1_segment,
    t1.trip_haulingType AS trip1_haulingType,
    t1.trip_from AS trip1_from,
    t1.trip_to AS trip1_to,
    t2.trip_container AS trip2_container,
    t2.trip_containerStat AS trip2_status,
    t2.trip_haulingSegment AS trip2_segment,
    t2.trip_haulingType AS trip2_haulingType,
    t2.trip_from AS trip2_from,
    t2.trip_to AS trip2_to
FROM dispatch d
LEFT JOIN trips t1 ON d.d_id = t1.d_id AND t1.trip_type = 'Trip 1'
LEFT JOIN trips t2 ON d.d_id = t2.d_id AND t2.trip_type = 'Trip 2'
";

$filter_query = $query;

// SEARCHING
if (!empty($_POST["search"]["value"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
    $filter_query .= "
    WHERE d.d_driverName LIKE '%$search%'
       OR d.d_dispatchHub LIKE '%$search%'
       OR d.d_truck LIKE '%$search%'
       OR d.d_trailer LIKE '%$search%'
       OR d.d_genset LIKE '%$search%' 
       OR t1.trip_container LIKE '%$search%'
       OR t2.trip_container LIKE '%$search%'
       OR t1.trip_haulingSegment LIKE '%$search%'
       OR t2.trip_haulingSegment LIKE '%$search%'";
}

$filtered_result = mysqli_query($conn, $filter_query);
$totalFiltered = mysqli_num_rows($filtered_result);

// ORDERING
if (isset($_POST["order"])) {
    $colIndex = intval($_POST['order'][0]['column']);
    $colDir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';

    if (!empty($columns[$colIndex])) {
        $filter_query .= " ORDER BY {$columns[$colIndex]} $colDir";
    } else {
        // If selected column is non-sortable → fallback to date DESC
        $filter_query .= " ORDER BY d.d_datetime DESC";
    }
} else {
    $filter_query .= " ORDER BY d.d_datetime DESC";
}

// PAGINATION
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
      <img src="../assets/images/profile/user-3.jpg" class="rounded-circle" width="40" alt="driver">
      <div class="ms-3">
        <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['d_driverName']) . '</h6>
        <span class="fw-bolder">' . htmlspecialchars($row['d_truck']) . '</span>
        <span class="fw-bolder">' . htmlspecialchars($row['d_trailer']) . '</span>
        <span class="fw-bolder">' . htmlspecialchars($row['d_genset']) . '</span>
      </div>
    </div>';
    $sub_array[] = htmlspecialchars($row['d_dispatchHub']);

    $sub_array[] = date('M d, Y h:i A', strtotime($row['d_datetime']));
    // Combined Trip Column
    $trip_html = '';

    // Trip 1
    $trip_html .= '
      <div class="d-flex align-items-center mb-2">
        <div class="ms-3">
          <h6 class="mb-0 fw-bolder">TRIP 1: ' . htmlspecialchars($row['trip1_from']) . ' - ' . htmlspecialchars($row['trip1_to']) . '</h6>
          <span class="fw-bolder">Container: ' . htmlspecialchars($row['trip1_container']) . ', Status: ' . htmlspecialchars($row['trip1_status']) . ', Segment: ' . htmlspecialchars($row['trip1_segment']) . '</span>
        </div>
      </div>';

    // Trip 2 (optional)
    if (!empty($row['trip2_from']) && !empty($row['trip2_to'])) {

        $trip_html .= '
        <div class="d-flex align-items-center">
          <div class="ms-3">
            <h6 class="mb-0 fw-bolder">TRIP 2: ' . htmlspecialchars($row['trip2_from']) . ' - ' . htmlspecialchars($row['trip2_to']) . '</h6>
            <span class="fw-bolder">Container: ' . htmlspecialchars($row['trip2_container']) . ', Status: ' . htmlspecialchars($row['trip2_status']) . ', Segment: ' . htmlspecialchars($row['trip2_segment']) . '</span>
          </div>
        </div>';

    } else {
        $trip_html .= '<span class="fw-bolder"></span>';
    }
    $sub_array[] = $trip_html;

    // Hauling
    // Prepare Trip 2 values (avoid outputting "-" when empty)
    $trip2_segment = !empty($row['trip2_segment']) ? $row['trip2_segment'] : '';
    $trip2_hauling = !empty($row['trip2_haulingType']) ? $row['trip2_haulingType'] : '';

    // Build segment string
    $segment_combined = $row['trip1_segment'];
    if (!empty($trip2_segment)) {
        $segment_combined .= ' - ' . $trip2_segment;
    }

    // Build hauling string
    $hauling_combined = $row['trip1_haulingType'];
    if (!empty($trip2_hauling)) {
        $hauling_combined .= ' - ' . $trip2_hauling;
    }

    // Add combined column as one cell
    $sub_array[] = '
        <div class="d-flex flex-column">
            <span class="fw-bold">Segment: ' . htmlspecialchars($segment_combined) . '</span>
            <span class="fw-bold">Hauling: ' . htmlspecialchars($hauling_combined) . '</span>
        </div>
    ';
    
    $sub_array[] = htmlspecialchars($row['d_dispatcher']);

    // ACTIONS
    $sub_array[] = '
        <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-sm btn-primary" onclick="editDispatch(' . $row['d_id'] . ')">Edit</button>
        </div>';

    $data[] = $sub_array;
}

// TOTAL RECORDS
$totalRecordsQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM dispatch");
$totalRecords = mysqli_fetch_assoc($totalRecordsQuery)['total'];

$output = [
    "draw" => intval($_POST["draw"]),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
];

echo json_encode($output);
?>
