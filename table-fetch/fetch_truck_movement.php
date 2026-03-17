<?php
include '../php/config/config.php';

if(isset($_POST['truck_id'])) {

    $unitName = $_POST['truck_id'];
    $fromDate = $_POST['fromDate'] ?? '';
    $toDate   = $_POST['toDate'] ?? '';

    $query = "
        SELECT 
            t.trip_id, 
            t.trip_type, 
            t.costumer, 
            t.trip_from, 
            t.trip_to, 
            t.trip_departureDateTime, 
            t.trip_arrivalDateTime, 
            t.trip_status, 
            d.d_driverName, 
            d.d_datetime
        FROM dispatch d
        INNER JOIN trips t ON t.d_id = d.d_id
        WHERE d.d_truck = ?
    ";

    // ✅ Add Date Filter if provided
    if(!empty($fromDate) && !empty($toDate)) {
        $query .= " AND DATE(d.d_datetime) BETWEEN ? AND ? ";
    }

    $query .= " ORDER BY d.d_datetime DESC";

    $stmt = $conn->prepare($query);

    if(!empty($fromDate) && !empty($toDate)) {
        $stmt->bind_param("sss", $unitName, $fromDate, $toDate);
    } else {
        $stmt->bind_param("s", $unitName);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>".date("M d, Y h:i A", strtotime($row['d_datetime']))."</td>
                    <td>{$row['d_driverName']}</td>
                    <td>{$row['costumer']}</td>
                    <td>{$row['trip_type']}</td>
                    <td>{$row['trip_from']}</td>
                    <td>{$row['trip_to']}</td>
                    <td>{$row['trip_status']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No trip record found</td></tr>";
    }

    $stmt->close();
}
?>
