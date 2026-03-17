<?php
include '../php/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

/* ========================================
   GET DATE RANGE
======================================== */
$fromDate = $_POST['fromDate'] ?? '';
$toDate   = $_POST['toDate'] ?? '';

$totalDays = 1;

if (!empty($fromDate) && !empty($toDate)) {
    $start = new DateTime($fromDate);
    $end   = new DateTime($toDate);
    $totalDays = max(1, $start->diff($end)->days + 1);
}

/* ========================================
   COLUMN MAP
======================================== */
$columns = [
    'tr.trailer_name',
    'd.d_driverName',
    'd.d_datetime',
    't_last.trip_to',
    't1.trip_haulingSegment',
    'd.d_dispatcher'
];

/* ========================================
   BASE QUERY
======================================== */
$baseQuery = "
SELECT 
    tr.trailer_name,

    d.d_driverName,
    d.d_datetime,
    d.d_dispatcher,
    d.d_dispatchHub,

    t1.trip_haulingSegment,
    t1.trip_haulingType,

    t_last.trip_to AS last_location,

    COUNT(DISTINCT DATE(d.d_datetime)) AS days_used,
    COUNT(d.d_id) AS total_dispatch

FROM trailer tr

LEFT JOIN dispatch d 
    ON tr.trailer_name = d.d_trailer
";

if (!empty($fromDate) && !empty($toDate)) {
    $baseQuery .= " 
    AND DATE(d.d_datetime) BETWEEN '$fromDate' AND '$toDate'
    ";
}

$baseQuery .= "
LEFT JOIN trips t1 
    ON d.d_id = t1.d_id AND t1.trip_type='Trip 1'

LEFT JOIN trips t_last 
    ON d.d_id = t_last.d_id
    AND t_last.trip_id = (
        SELECT MAX(t3.trip_id)
        FROM trips t3
        WHERE t3.d_id = d.d_id
    )
";

/* ========================================
   WHERE
======================================== */
$whereClauses = ["1=1"];

if (!empty($_POST['trailer'])) {
    $trailer = mysqli_real_escape_string($conn, $_POST['trailer']);
    $whereClauses[] = "tr.trailer_name='$trailer'";
}

if (!empty($_POST['customer'])) {
    $customer = mysqli_real_escape_string($conn, $_POST['customer']);
    $whereClauses[] = "d.costumer='$customer'";
}

if (!empty($_POST["search"]["value"])) {
    $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
    $whereClauses[] = "(
        tr.trailer_name LIKE '%$search%' OR
        d.d_driverName LIKE '%$search%' OR
        t1.trip_haulingSegment LIKE '%$search%'
    )";
}

$query = $baseQuery . " WHERE " . implode(" AND ", $whereClauses);
$query .= " GROUP BY tr.trailer_name ";

/* ========================================
   COUNT
======================================== */
$totalRes = mysqli_query($conn,"SELECT COUNT(*) as total FROM trailer");
$totalData = mysqli_fetch_assoc($totalRes)['total'];

$filteredRes = mysqli_query($conn,$query);
$totalFiltered = mysqli_num_rows($filteredRes);

/* ========================================
   ORDER
======================================== */
if (isset($_POST["order"])) {
    $colIndex = intval($_POST['order'][0]['column']);
    $colDir = $_POST['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
    $query .= " ORDER BY {$columns[$colIndex]} $colDir";
} else {
    $query .= " ORDER BY tr.trailer_name ASC";
}

/* ========================================
   PAGINATION
======================================== */
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$query .= " LIMIT $start,$length";

/* ========================================
   FETCH
======================================== */
$data = [];
$result = mysqli_query($conn,$query);

while($row=mysqli_fetch_assoc($result)){

    $sub_array=[];

    $daysUsed = intval($row['days_used']);
    $dispatchCount = intval($row['total_dispatch']);

    $utilization = ($daysUsed / $totalDays) * 100;
    $utilization = min(100, round($utilization));

    /* COLOR LOGIC */
    if($utilization >= 70){
        $color='bg-success';
    }elseif($utilization >=40){
        $color='bg-warning';
    }else{
        $color='bg-danger';
    }

    /* TRAILER */
    $sub_array[]='
    <div class="d-flex align-items-center">
        <i class="bi bi-truck me-2"></i>
        <h6 class="mb-0 fw-bold">'.htmlspecialchars($row['trailer_name']).'</h6>
    </div>';

    /* DRIVER */
    $sub_array[]=$row['d_driverName'] ?: '<span class="text-muted">Idle</span>';

    /* DATE */
    $sub_array[]= $row['d_datetime']
        ? date('Y-m-d H:i A',strtotime($row['d_datetime']))
        : '<span class="text-muted">N/A</span>';

    /* LOCATION */
    $sub_array[]= $row['last_location'] ?: '<span class="text-muted">N/A</span>';

    /* SEGMENT + PROGRESS */
    $segment=$row['trip_haulingSegment'] ?: 'No Activity';
    $type=$row['trip_haulingType'] ?: '';

    $sub_array[]='

        <div class="progress" style="height:10px;" 
             title="'.$dispatchCount.' dispatches / '.$daysUsed.' days used">
            <div class="progress-bar '.$color.'" 
                 style="width:'.$utilization.'%"></div>
        </div>

        <small class="text-muted">'.$utilization.'% utilized</small>
    ';
    $sub_array[]='
        <div class="fw-semibold mb-1">'.$segment.' '.$type.'</div>
    ';

    /* DISPATCHER */
    $sub_array[]=$row['d_dispatcher']
        ? $row['d_dispatcher'].' - '.$row['d_dispatchHub']
        : '<span class="text-muted">N/A</span>';

    $data[]=$sub_array;
}

/* ========================================
   OUTPUT
======================================== */
echo json_encode([
    "draw"=>intval($_POST["draw"]),
    "recordsTotal"=>$totalData,
    "recordsFiltered"=>$totalFiltered,
    "data"=>$data
]);
