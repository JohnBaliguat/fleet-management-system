<?php
include '../php/config/config.php';

$sql = "SELECT r.r_id, r.r_unit, r.driver_id, r.r_startDateTime, r.r_endDateTime, r.r_status, 
               d.driver_fname, d.driver_lname
        FROM rescue_unit r
        LEFT JOIN drivers d ON r.driver_id = d.driver_id";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $start  = $row['r_startDateTime'];
    $end    = $row['r_endDateTime'];
    $status = strtolower($row['r_status']); // normalize status (lowercase)

    // Handle End Time + Age
    if ($end == "0000-00-00 00:00:00") {
        $end_display = "<span class='live-time' data-start='{$start}'></span>";
        $age_display = "<span class='live-age' data-start='{$start}'></span>";
    } else {
        $end_display = date("F j, Y h:i A", strtotime($end));
        
        $diff = strtotime($end) - strtotime($start);
        $days = floor($diff / 86400);
        $hms  = gmdate("H:i:s", $diff % 86400);
        $age_display = $days . " Days " . $hms;

        $start_display = date("F j, Y h:i A", strtotime($start));
    }

    // Action buttons based on status
    $buttons = "<div class='d-grid gap-2 d-md-block text-start'>";
    if ($status === "active") {
    $buttons .= "<button class='btn btn-primary btn-sm dispatch-btn' data-id='{$row['r_id']}'>Dispatch Rescue</button>";
    } elseif ($status === "dispatched") {
        $buttons .= "<button class='btn btn-danger btn-sm forward-btn' data-id='{$row['r_id']}' style='margin-right:5px;'>Forward to Workshop</button>";
        $buttons .= "<button class='btn btn-success btn-sm good-btn' data-id='{$row['r_id']}'>Rescue Complete</button>";
    } else if($status === "good"){
     $buttons .= "<button class='btn btn-success btn-sm'>Confirm</button>";
    } else if($status === "to be confirmed by dispatch"){
     $buttons .= "<button class='btn btn-info btn-sm'>To Be Confirmed by Dispatch</button>";
    }
    $buttons .= "</div>";

    $data[] = [
        "driver" => $row['driver_fname'] . " " . $row['driver_lname'],
        "unit"   => $row['r_unit'],
        "start"  => $start_display,
        "end"    => $end_display,
        "age"    => $age_display,
        "action" => $buttons
    ];
}

echo json_encode(["data" => $data]);
?>
