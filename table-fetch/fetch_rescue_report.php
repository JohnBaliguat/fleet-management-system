<?php
include '../php/config/config.php';

$sql = "
SELECT 
    r.r_id,
    r.r_unit,
    r.r_startDateTime,
    r.r_endDateTime,
    r.r_status,
    d.driver_fname,
    d.driver_lname,
    GROUP_CONCAT(
        CONCAT(sm.smp_fname, ' ', sm.smp_lname)
        SEPARATOR ', '
    ) AS manpower
FROM rescue_unit r
LEFT JOIN drivers d ON r.driver_id = d.driver_id
LEFT JOIN rescue_record rr ON r.r_id = rr.r_id
LEFT JOIN assign_manpower am ON rr.rr_id = am.rr_id
LEFT JOIN shop_manpower sm ON am.smp_id = sm.smp_id
WHERE r.r_status = 'Good'
GROUP BY r.r_id
";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {

    $start = $row['r_startDateTime'];
    $end   = $row['r_endDateTime'];

    $start_display = date("F j, Y h:i A", strtotime($start));

    if ($end == "0000-00-00 00:00:00" || empty($end)) {
        $end_display = "<span class='live-time' data-start='{$start}'></span>";
        $age_display = "<span class='live-age' data-start='{$start}'></span>";
    } else {
        $end_display = date("F j, Y h:i A", strtotime($end));

        $diff = strtotime($end) - strtotime($start);
        $days = floor($diff / 86400);
        $hms  = gmdate("H:i:s", $diff % 86400);
        $age_display = $days . " Days " . $hms;
    }

    $data[] = [
        "driver"   => $row['driver_fname'] . " " . $row['driver_lname'],
        "unit"     => $row['r_unit'],
        "start"    => $start_display,
        "end"      => $end_display,
        "age"      => $age_display,
        "manpower" => $row['manpower'] ?? '—',
        "status"   => "<span class='badge bg-info'>Good</span>"
    ];
}

echo json_encode(["data" => $data]);
?>
