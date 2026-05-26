<?php
header('Content-Type: application/json');
include '../php/config/config.php';

$columns = [
    'booking_no', 'booking_date', 'booking_dateRequired', 'age', 'costumer', 'container_seal',
    'container', 'container_status', 'hauling_segment', 'hauling_type',
    'trip_from', 'trip_to', 'quantity', 'quantity_use', 'status'
];

$query = "SELECT booking_id, booking_no, booking_date, booking_dateRequired, costumer, container_seal, container, booking_activity, container_status, 
                 hauling_segment, hauling_type, trip_from, trip_to, quantity, quantity_use, status 
          FROM booking WHERE status != 'Disable'";
$filter_query = $query;

// Search
if (!empty($_POST['search']['value'])) {
    $search_value = mysqli_real_escape_string($conn, $_POST['search']['value']);
    $filter_query .= " WHERE booking_no LIKE '%$search_value%'
        OR costumer LIKE '%$search_value%'
        OR container_seal LIKE '%$search_value%'
        OR container LIKE '%$search_value%'
        OR container_status LIKE '%$search_value%'
        OR hauling_segment LIKE '%$search_value%'
        OR hauling_type LIKE '%$search_value%'
        OR trip_from LIKE '%$search_value%'
        OR trip_to LIKE '%$search_value%'
        OR status LIKE '%$search_value%'";
}

// Total filtered
$filtered_result = mysqli_query($conn, $filter_query);
$totalFiltered = mysqli_num_rows($filtered_result);

// Ordering
if (isset($_POST["order"])) {
    $order_column_index = intval($_POST['order'][0]['column']);
    $order_dir = $_POST['order'][0]['dir'] === 'desc' ? 'ASC' : 'DESC';
    if (isset($columns[$order_column_index])) {
        $order_column_name = $columns[$order_column_index];
        $filter_query .= " ORDER BY $order_column_name $order_dir";
    }
} else {
    $filter_query .= " ORDER BY booking_id DESC";
}

// Pagination
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$filter_query .= " LIMIT $start, $length";

// Fetch data
$result = mysqli_query($conn, $filter_query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // ✅ Calculate Age in days
    $requiredDate = new DateTime($row['booking_dateRequired']);
    $today = new DateTime();
    $age = $today->diff($requiredDate)->days . ' Days';


    // ✅ Combined columns
    $containerCol = $row['container'] . " - " . '<span class="badge bg-info">'.$row['container_status'].'</span>';
    $segmentCol   = $row['hauling_segment'] . " - " . '<span class="badge bg-info">'.$row['hauling_type'].'</span>';
    $tripCol      = $row['trip_from'] . " ➝ " . $row['trip_to'];

    if ($row['container'] == '') {
         $containerCol = $row['container'] . "" . '<span class="badge bg-info">'.$row['container_status'].'</span>';
    }

    // ✅ Status badge color
    $statusBadge = '';
    switch (strtolower($row['status'])) {
        case 'approved':
            $statusBadge = '<span class="badge bg-success">'.$row['status'].'</span>';
            break;
        case 'pending':
            $statusBadge = '<span class="badge bg-warning text-dark">'.$row['status'].'</span>';
            break;
        case 'cancelled':
            $statusBadge = '<span class="badge bg-danger">'.$row['status'].'</span>';
            break;
        default:
            $statusBadge = '<span class="badge bg-secondary">'.$row['status'].'</span>';
    }

    // ✅ Action buttons based on quantity / quantity_use
    $actionBtns = '<div class="d-flex align-items-center list-user-action">';

    if ($row['quantity_use'] < $row['quantity']) {
        $actionBtns .= '<a class="btn btn-primary btn-sm" data-toggle="tooltip" title="Edit" href="#" '
            . 'onclick="editBooking(' . (int)$row['booking_id'] . '); return false;"><i class="ti ti-edit"></i></a>';
    }

    if ($row['quantity_use'] == 0) {
        $actionBtns .= '<a class="btn btn-danger btn-sm" data-toggle="tooltip" title="Delete" href="#" 
            onclick="deleteBooking(' . $row['booking_id'] . ')" style="margin-left: 5px;">
            <i class="ti ti-trash"></i></a>';
    }

    $actionBtns .= '</div>';

    $data[] = [
        $row['booking_no'],
        $row['booking_date'],
        $row['booking_dateRequired'],
        $age, // ✅ Age column
        $row['costumer'],
        $containerCol,
        $segmentCol,
        $tripCol,
        $row['quantity'],
        $row['quantity_use'],
        $statusBadge,
        $actionBtns
    ];
}

// Output to DataTables
echo json_encode([
    "draw" => intval($_POST["draw"]),
    "recordsTotal" => $totalFiltered,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
]);
?>
