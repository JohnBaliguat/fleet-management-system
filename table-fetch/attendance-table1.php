<?php 
include '../php/config/config.php';
header('Content-Type: application/json');
error_reporting(0);

$columns = ['', 'driver_name', 'da_date', 'da_timeIn', 'da_timeOut', 'da_status', 'da_remarks'];

// -------------------------------
// Base Query
// -------------------------------
$baseQuery = "
  SELECT 
    d.driver_id,
    CONCAT(d.driver_fname, ' ', d.driver_mname, ' ', d.driver_lname) AS driver_name,
    d.driver_image,
    da.da_id,
    da.da_controlNo,
    da.da_status,
    da.da_remarks,
    da.da_timeIn,
    da.da_timeOut,
    da.vl_sl_datePrepared,
    da.vl_sl_dateStart,
    da.vl_sl_date,
    da.da_date
  FROM drivers d
  INNER JOIN drivers_attendance da ON d.driver_id = da.driver_id
";

// -------------------------------
// WHERE Clause (Optional Filters)
// -------------------------------
$where = [];

if (!empty($_POST['fromDate']) && !empty($_POST['toDate'])) {
  $from = mysqli_real_escape_string($conn, $_POST['fromDate']);
  $to = mysqli_real_escape_string($conn, $_POST['toDate']);
  $where[] = "da.da_date BETWEEN '$from' AND '$to'";
}

if (!empty($_POST["search"]["value"])) {
  $search = mysqli_real_escape_string($conn, $_POST["search"]["value"]);
  $where[] = "(d.driver_fname LIKE '%$search%' 
            OR d.driver_lname LIKE '%$search%' 
            OR da.da_status LIKE '%$search%'
            OR da.da_remarks LIKE '%$search%')";
}

if (!empty($where)) {
  $baseQuery .= " WHERE " . implode(" AND ", $where);
}

// -------------------------------
// Order & Pagination
// -------------------------------
$orderColumnIndex = $_POST['order'][0]['column'] ?? 2;
$orderColumn = $columns[$orderColumnIndex] ?: 'da_date';
$orderDir = $_POST['order'][0]['dir'] ?? 'DESC';
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);

$query = "$baseQuery ORDER BY $orderColumn $orderDir LIMIT $start, $length";

// -------------------------------
// Fetch data
// -------------------------------
$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
  $sub = [];
  $sub[] = '';
  $sub[] = '
    <div class="d-flex align-items-center">
      <img src="assets/images/profile/user-3.jpg" 
           class="rounded-circle" width="40" height="40" alt="Driver Image">
      <div class="ms-3">
        <h6 class="mb-0 fw-bolder">' . htmlspecialchars($row['driver_name']) . '</h6>
      </div>
    </div>';
  $sub[] = htmlspecialchars($row['da_date'] ?? '');
  $sub[] = htmlspecialchars(date('H:i:s', strtotime($row['da_timeIn'])) ?? '');
  $sub[] = htmlspecialchars(date('H:i:s', strtotime($row['da_timeOut'])) ?? '');
  $sub[] = htmlspecialchars($row['vl_sl_datePrepared'] ?? '');
  $sub[] = htmlspecialchars($row['vl_sl_dateStart'] ?? '');
  $sub[] = htmlspecialchars($row['vl_sl_date'] ?? '');
  $sub[] = htmlspecialchars($row['da_status'] ?? '');
  $sub[] = htmlspecialchars($row['da_remarks'] ?? '');
  $sub[] = '
    <button class="btn btn-sm btn-danger printBtn"
            data-id="' . $row['da_id'] . '">
        <i class="ti ti-printer"></i> Print
    </button>
  ';
  $data[] = $sub;
}

// -------------------------------
// Get counts
// -------------------------------
$totalQuery = "SELECT COUNT(*) AS total FROM drivers";
$totalRes = mysqli_query($conn, $totalQuery);
$totalData = mysqli_fetch_assoc($totalRes)['total'] ?? 0;

$countQuery = "
  SELECT COUNT(*) AS total 
  FROM drivers d
  LEFT JOIN drivers_attendance da ON d.driver_id = da.driver_id
";
if (!empty($where)) {
  $countQuery .= " WHERE " . implode(" AND ", $where);
}
$filteredRes = mysqli_query($conn, $countQuery);
$totalFiltered = mysqli_fetch_assoc($filteredRes)['total'] ?? 0;

// -------------------------------
// Output JSON
// -------------------------------
echo json_encode([
  "draw" => intval($_POST["draw"] ?? 0),
  "recordsTotal" => intval($totalData),
  "recordsFiltered" => intval($totalFiltered),
  "data" => $data
]);
