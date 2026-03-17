<?php
include "../config/config.php";

if (isset($_GET['driver_id'])) {
    $driver_id = $_GET['driver_id'];

    // Optional: validate driver exists
    $driverQuery = $conn->prepare("
        SELECT driver_id
        FROM drivers 
        WHERE driver_id = ?
    ");
    $driverQuery->bind_param("i", $driver_id);
    $driverQuery->execute();
    $driverResult = $driverQuery->get_result();

    if ($driverResult->num_rows > 0) {

        $response = ["truck" => [], "trailer" => []];

        // 🚚 Get truck + genset using driver_id
        $unitQuery = $conn->prepare("
            SELECT * 
            FROM units 
            WHERE driver_id = ?
        ");
        $unitQuery->bind_param("i", $driver_id);
        $unitQuery->execute();
        $unitsResult = $unitQuery->get_result();

        while ($row = $unitsResult->fetch_assoc()) {

            // Truck starts with PM
            if (strpos($row['unit_name'], 'PM') === 0) {

                $truck = $row;

                // Total KM per truck
                $kmQuery = $conn->prepare("
                    SELECT SUM(t.km_run) AS total_km
                    FROM trips t
                    INNER JOIN dispatch d ON t.d_id = d.d_id
                    WHERE d.d_truck = ?
                ");
                $kmQuery->bind_param("s", $row['unit_name']);
                $kmQuery->execute();
                $kmResult = $kmQuery->get_result();
                $kmRow = $kmResult->fetch_assoc();

                $truck['total_km'] = $kmRow['total_km'] ?? 0;

                // Attach genset (GS%)
                $gensetQuery = $conn->prepare("
                    SELECT unit_name, unit_status
                    FROM units
                    WHERE unit_assign = ?
                    AND unit_name LIKE 'GS%'
                    LIMIT 1
                ");
                $gensetQuery->bind_param("i", $driver_id);
                $gensetQuery->execute();
                $gensetResult = $gensetQuery->get_result();

                if ($gensetRow = $gensetResult->fetch_assoc()) {
                    $truck['genset_name']   = $gensetRow['unit_name'];
                    $truck['genset_status'] = $gensetRow['unit_status'];
                } else {
                    $truck['genset_name']   = null;
                    $truck['genset_status'] = null;
                }

                $response['truck'][] = $truck;
            }
        }

        // 🚛 Get trailer using driver_id
        $trailerQuery = $conn->prepare("
            SELECT trailer_id, trailer_name, trailer_plateNo, driver_id, trailer_location, trailer_status
            FROM trailer
            WHERE driver_id = ?
        ");
        $trailerQuery->bind_param("i", $driver_id);
        $trailerQuery->execute();
        $trailerResult = $trailerQuery->get_result();

        while ($tr = $trailerResult->fetch_assoc()) {
            $response['trailer'][] = $tr;
        }

        echo json_encode($response);

    } else {
        echo json_encode(["error" => "Driver not found"]);
    }
}
?>
