<?php
include '../php/config/config.php';

if(isset($_POST['trailer_id'])) {

    $trailer_id = $_POST['trailer_id'];
    $fromDate   = $_POST['fromDate'] ?? '';
    $toDate     = $_POST['toDate'] ?? '';

    $query = "
        SELECT * 
        FROM trailer_movement
        WHERE tm_trailerName = ?
    ";

    // Add date filter if provided
    if(!empty($fromDate) && !empty($toDate)) {
        $query .= " AND DATE(tm_date) BETWEEN ? AND ? ";
    }

    $query .= " ORDER BY tm_date DESC";

    $stmt = $conn->prepare($query);

    if(!empty($fromDate) && !empty($toDate)) {
        $stmt->bind_param("sss", $trailer_id, $fromDate, $toDate);
    } else {
        $stmt->bind_param("s", $trailer_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>".date("F d, Y h:i A", strtotime($row['tm_date']))."</td>
                    <td>{$row['tm_location']}</td>
                    <td>{$row['tm_recordedType']}</td>
                    <td>{$row['tm_driverAssign']}</td>
                    <td>{$row['tm_container']}</td>
                    <td>{$row['tm_remarks']}</td>
                    <td>{$row['tm_recordedBy']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No movement record found</td></tr>";
    }

    $stmt->close();
}
?>
