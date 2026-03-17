<?php
include '../php/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$columns = [
  'driver_fname',
  'vr_type',
  'vr_description',
  'vr_status',
  'vr_date'
];

/* ===============================
   BASE QUERY
================================ */
$baseQuery = "
SELECT
  d.driver_id,
  CONCAT(d.driver_fname, ' ', d.driver_lname) AS driver_name,
  d.driver_assignUnit,
  v.vr_type,
  v.vr_description,
  v.vr_status,
  v.vr_date,
  v.vr_done_date
FROM drivers d
INNER JOIN violation_record v ON d.driver_id = v.driver_id
";

/* ===============================
   WHERE CONDITIONS
================================ */
$where = [];

if (!empty($_POST['violation'])) {
  $violation = mysqli_real_escape_string($conn, $_POST['violation']);
  $where[] = "v.vr_type = '$violation'";
}

if (!empty($_POST['fromDate']) && !empty($_POST['toDate'])) {
  $from = mysqli_real_escape_string($conn, $_POST['fromDate']);
  $to   = mysqli_real_escape_string($conn, $_POST['toDate']);
  $where[] = "v.vr_date BETWEEN '$from' AND '$to'";
}

if (!empty($_POST['search']['value'])) {
  $search = mysqli_real_escape_string($conn, $_POST['search']['value']);
  $where[] = "(d.driver_fname LIKE '%$search%'
               OR d.driver_lname LIKE '%$search%'
               OR v.vr_type LIKE '%$search%')";
}

$query = $baseQuery;
if ($where) {
  $query .= " WHERE " . implode(" AND ", $where);
}

/* ===============================
   COUNT
================================ */
$totalQuery = "SELECT COUNT(*) total FROM violation_record";
$totalRes = mysqli_query($conn, $totalQuery);
$totalData = mysqli_fetch_assoc($totalRes)['total'];

$filteredRes = mysqli_query($conn, $query);
$totalFiltered = mysqli_num_rows($filteredRes);

/* ===============================
   ORDER
================================ */
if (isset($_POST['order'])) {
  $col = $columns[$_POST['order'][0]['column']];
  $dir = $_POST['order'][0]['dir'];
  $query .= " ORDER BY $col $dir";
} else {
  $query .= " ORDER BY v.vr_date DESC";
}

/* ===============================
   PAGINATION
================================ */
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$query .= " LIMIT $start, $length";

/* ===============================
   DATA
================================ */
$data = [];
$res = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($res)) {
  $data[] = [
    '',
    '<strong>' . htmlspecialchars($row['driver_name']) . '</strong>',
    htmlspecialchars($row['vr_type']),
    htmlspecialchars($row['vr_description']),
    '<span class="badge bg-' . ($row['vr_status'] == 'Done' ? 'success' : 'warning') . '">' .
      htmlspecialchars($row['vr_status']) .
    '</span>',
    date('Y-m-d', strtotime($row['vr_date']))
  ];
}

/* ===============================
   RESPONSE
================================ */
echo json_encode([
  "draw" => intval($_POST['draw']),
  "recordsTotal" => $totalData,
  "recordsFiltered" => $totalFiltered,
  "data" => $data
]);
