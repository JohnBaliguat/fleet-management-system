<?php
include "../../config/config.php";

/* =======================
   HELPER FUNCTIONS
======================= */

function haversineMeters($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) ** 2 +
        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *
        sin($dLon / 2) ** 2;

    return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
}

function pointNearRoute($routeCoords, $checkLat, $checkLon, $toleranceMeters = 100)
{
    foreach ($routeCoords as $coord) {
        $lon = $coord[0];
        $lat = $coord[1];

        if (haversineMeters($lat, $lon, $checkLat, $checkLon) <= $toleranceMeters) {
            return true;
        }
    }
    return false;
}

function getOSRMDistanceWithGeometry($lat1, $lon1, $lat2, $lon2)
{
    $url = "http://router.project-osrm.org/route/v1/driving/"
        . "$lon1,$lat1;$lon2,$lat2"
        . "?overview=full&geometries=geojson";

    $response = @file_get_contents($url);
    if ($response === false) return 0;

    $data = json_decode($response, true);

    if (!isset($data['routes'][0])) return 0;

    $route = $data['routes'][0];
    $distanceKm = $route['distance'] / 1000;
    $coords = $route['geometry']['coordinates'];

    // SPECIAL POINT
    $specialLat = 7.333325;
    $specialLon = 125.626795;

    if (pointNearRoute($coords, $specialLat, $specialLon, 100)) {
        $distanceKm += 2.4;
    }

    return round($distanceKm, 2);
}

function getKmRun($conn, $from, $to)
{
    if (empty($from) || empty($to)) return 0;

    $sql = "SELECT location_name, latitude, longitude
            FROM location
            WHERE location_name IN (?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();

    $locations = [];
    while ($row = $res->fetch_assoc()) {
        $locations[$row['location_name']] = $row;
    }
    $stmt->close();

    if (
        isset(
            $locations[$from]['latitude'],
            $locations[$from]['longitude'],
            $locations[$to]['latitude'],
            $locations[$to]['longitude']
        )
    ) {
        return getOSRMDistanceWithGeometry(
            $locations[$from]['latitude'],
            $locations[$from]['longitude'],
            $locations[$to]['latitude'],
            $locations[$to]['longitude']
        );
    }

    return 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_no = $_POST["booking_no"];
    date_default_timezone_set("Asia/Manila");
    $d_datetime = date("Y-m-d H:i:s");
    $d_dispatcher = $_POST['userName'];
    $d_dispatchHub = $_POST['assinglocation'];

    // Main dispatch data
    $d_driverName = $_POST['driver'];  // changed
    $driver_id    = $_POST['driver_id'];
    $d_truck = $_POST['unit_name'];    // changed
    $d_trailer = $_POST['trailer'];
    $d_genset = $_POST['genset'];
    $d_tripReceipt = $_POST['tr'];
    $d_ecs = $_POST['ecs'];

    // Trip 1
    $trip1_costumer = $_POST['costumer'] ?? '';
    $trip1_containerSeal = $_POST['container_seal'];
    $trip1_container = $_POST['container_no'];
    $trip1_status = $_POST['container_status'];
    $trip1_activity = $_POST['booking_activity'];
    $trip1_segment = $_POST['hauling_segment'];
    $trip1_type = "Trip 1";
    $trip1_from = $_POST['destination_from'];
    $trip1_to = $_POST['destination_to'];

    // Trip 2
    $trip2_costumer = $_POST['costumer2'] ?? '';
    $trip2_containerSeal = $_POST['container_seal2'];
    $trip2_container = $_POST['container_no2'];
    $trip2_status = $_POST['container_status2'];
    $trip2_activity = $_POST['booking_activity2'];
    $trip2_segment = $_POST['hauling_segment2'];
    $trip2_type = "Trip 2";
    $trip2_from = $_POST['destination_from1'];
    $trip2_to = $_POST['destination_to1'];

    // Trip 3

    $trip3_costumer = $_POST['costumer3'] ?? '';
    $trip3_containerSeal = $_POST['container_seal3'];
    $trip3_container = $_POST['container_no3'];
    $trip3_status = $_POST['container_status3'];
    $trip3_activity = $_POST['booking_activity3'];
    $trip3_segment = $_POST['hauling_segment3'];
    $trip3_type = "Trip 3";
    $trip3_from = $_POST['destination_from3'];
    $trip3_to = $_POST['destination_to3'];

    // Trip 4

    $trip4_costumer = $_POST['costumer4'] ?? '';
    $trip4_containerSeal = $_POST['container_seal4'];
    $trip4_container = $_POST['container_no4'];
    $trip4_status = $_POST['container_status4'];
    $trip4_activity = $_POST['booking_activity4'];
    $trip4_segment = $_POST['hauling_segment4'];
    $trip4_type = "Trip 4";
    $trip4_from = $_POST['destination_from4'];
    $trip4_to = $_POST['destination_to4'];

    // Fetch hauling types
    $trip1_hauling_type = '';
    $trip2_hauling_type = '';
    $trip3_hauling_type = '';
    $trip4_hauling_type = '';

    $trip1_km_run = getKmRun($conn, $trip1_from, $trip1_to);
    $trip2_km_run = 0;

    if (!empty($trip2_from) && !empty($trip2_to)) {
        $trip2_km_run = getKmRun($conn, $trip2_from, $trip2_to);
    }

    if (!empty($trip3_from) && !empty($trip3_to)) {
        $trip3_km_run = getKmRun($conn, $trip3_from, $trip3_to);
    }

    if (!empty($trip4_from) && !empty($trip4_to)) {
        $trip4_km_run = getKmRun($conn, $trip4_from, $trip4_to);
    }

    

    // 0.5️⃣ Validate Unit (Truck) registration + status + assignment
    if (!empty($d_truck)) {
        $checkUnit = "SELECT unit_status, unit_assign, driver_id FROM units WHERE unit_name = ?";
        $stmt = $conn->prepare($checkUnit);
        $stmt->bind_param("s", $d_truck);
        $stmt->execute();
        $result = $stmt->get_result();
        $unitRow = $result->fetch_assoc();
        $stmt->close();

        if (!$unitRow) {
            echo json_encode(["status" => "error", "message" => "Truck is not registered!"]);
            exit;
        }

        // 🚫 Status check
        if (in_array($unitRow['unit_status'], ['Rescue', 'Shop Unit'])) {
            echo json_encode(["status" => "error", "message" => "Truck is unavailable! (" . $unitRow['unit_status'] . ")"]);
            exit;
        }

        // 🚫 Assignment check
        if (!empty($unitRow['unit_assign']) && (int)$unitRow['driver_id'] !== 0) {
            if ($unitRow['unit_assign'] !== $d_driverName || (int)$unitRow['driver_id'] !== (int)$driver_id) {
                echo json_encode(["status" => "error", "message" => "Truck is already assigned to another driver!"]);
                exit;
            }
        }
    }

    // 0.5️⃣ Validate Unit (Genset) registration + status + assignment
    if (!empty($d_genset)) {
        $checkGenset = "SELECT unit_status, unit_assign, driver_id FROM units WHERE unit_name = ?";
        $stmt = $conn->prepare($checkGenset);
        $stmt->bind_param("s", $d_genset);
        $stmt->execute();
        $result = $stmt->get_result();
        $gensetRow = $result->fetch_assoc();
        $stmt->close();

        if (!$gensetRow) {
            echo json_encode(["status" => "error", "message" => "Genset is not registered!"]);
            exit;
        }

        if (in_array($gensetRow['unit_status'], ['Rescue', 'Shop Unit'])) {
            echo json_encode(["status" => "error", "message" => "Genset is unavailable! (" . $gensetRow['unit_status'] . ")"]);
            exit;
        }

        if (!empty($gensetRow['unit_assign']) && (int)$gensetRow['driver_id'] !== 0) {
            if ($gensetRow['unit_assign'] !== $d_driverName || (int)$gensetRow['driver_id'] !== (int)$driver_id) {
                echo json_encode(["status" => "error", "message" => "Genset is already assigned to another driver!"]);
                exit;
            }
        }
    }

    // 0.6️⃣ Validate Trailer registration + status + assignment
    if (!empty($d_trailer)) {
        $checkTrailer = "SELECT trailer_status, trailer_assignTo, driver_id FROM trailer WHERE trailer_name = ?";
        $stmt = $conn->prepare($checkTrailer);
        $stmt->bind_param("s", $d_trailer);
        $stmt->execute();
        $result = $stmt->get_result();
        $trailerRow = $result->fetch_assoc();
        $stmt->close();

        if (!$trailerRow) {
            echo json_encode(["status" => "error", "message" => "Trailer is not registered!"]);
            exit;
        }

        if (in_array($trailerRow['trailer_status'], ['Rescue', 'Shop Unit'])) {
            echo json_encode(["status" => "error", "message" => "Trailer is unavailable! (" . $trailerRow['trailer_status'] . ")"]);
            exit;
        }

        // if (!empty($trailerRow['trailer_assignTo']) && (int)$trailerRow['driver_id'] !== 0) {
        //     if ($trailerRow['trailer_assignTo'] !== $d_driverName || (int)$trailerRow['driver_id'] !== (int)$driver_id) {
        //         echo json_encode(["status" => "error", "message" => "Trailer is already assigned to another driver!"]);
        //         exit;
        //     }
        // }
    }

    // 0.7️⃣ Check if driver already has 2 active trips
    $checkActiveTrips = "SELECT COUNT(*) AS active_count
            FROM trips t
            INNER JOIN dispatch d ON t.d_id = d.d_id
            WHERE d.driver_id = ? AND t.trip_status != 'Done'
        ";
    $stmt = $conn->prepare($checkActiveTrips);
    $stmt->bind_param("s", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $activeData = $result->fetch_assoc();
    $stmt->close();

    if ((int)$activeData['active_count'] == 2) {
        echo json_encode(["status" => "error", "message" => "Driver already has 2 active trips!"]);
        exit;
    }

    $stmt1 = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
    $stmt1->bind_param("s", $trip1_segment);
    $stmt1->execute();
    $stmt1->bind_result($trip1_hauling_type);
    $stmt1->fetch();
    $stmt1->close();

    // TRIP 2
    if (!empty($trip2_segment)) {
        $stmt2 = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
        $stmt2->bind_param("s", $trip2_segment);
        $stmt2->execute();
        $stmt2->bind_result($trip2_hauling_type);
        $stmt2->fetch();
        $stmt2->close();
    }
    // TRIP 3
    if (!empty($trip3_segment)) {
        $stmt3 = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
        $stmt3->bind_param("s", $trip3_segment);
        $stmt3->execute();
        $stmt3->bind_result($trip3_hauling_type);
        $stmt3->fetch();
        $stmt3->close();
    }
    // TRIP 4
    if (!empty($trip4_segment)) {
        $stmt4 = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
        $stmt4->bind_param("s", $trip4_segment);
        $stmt4->execute();
        $stmt4->bind_result($trip4_hauling_type);
        $stmt4->fetch();
        $stmt4->close();
    }

    // Get driver_id from DB
    $driver_id = null;
    $stmt = $conn->prepare("SELECT driver_id FROM drivers WHERE driver_lname = ?");
    $lastname = strtok($d_driverName, ","); // get last name before comma
    $stmt->bind_param("s", $lastname);
    $stmt->execute();
    $stmt->bind_result($driver_id);
    $stmt->fetch();
    $stmt->close();

    // Insert into dispatch
    $stmt = $conn->prepare("INSERT INTO dispatch 
        (booking_no, d_datetime, d_dispatcher, d_dispatchHub, d_driverName, driver_id, d_truck, d_trailer, d_genset, d_tripReceipt, d_ecs)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $booking_no, $d_datetime, $d_dispatcher, $d_dispatchHub, $d_driverName, $driver_id, $d_truck, $d_trailer, $d_genset, $d_tripReceipt, $d_ecs);

    if ($stmt->execute()) {
        $dispatch_id = $stmt->insert_id;

        // Trip 1 insert
        $trip1_stmt = $conn->prepare("INSERT INTO trips 
            (d_id, trip_type, costumer, trip_container, trip_containerStat, container_activity, trip_haulingSegment, trip_haulingType, trip_from, trip_to, km_run, trip_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        $trip1_stmt->bind_param("isssssssssd", $dispatch_id, $trip1_type, $trip1_costumer, $trip1_container, $trip1_status, $trip1_activity, $trip1_segment, $trip1_hauling_type, $trip1_from, $trip1_to, $trip1_km_run);
        $trip1_stmt->execute();
        $trip1_stmt->close();

        // ✅ Insert container_activity for Trip 1
        if (!empty($trip1_container)) {
            $insertContainer1 = $conn->prepare("INSERT INTO container_activity 
                (container_name, container_location, truck_no, trailer_no, genset_no, driver_name, container_status, trip_status, Date)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
            $insertContainer1->bind_param("ssssssss", 
                $trip1_container, 
                $trip1_to, 
                $d_truck, 
                $d_trailer, 
                $d_genset, 
                $d_driverName, 
                $trip1_status, 
                $d_datetime
            );
            $insertContainer1->execute();
            $insertContainer1->close();
        }

        // Trip 2 insert (optional)
        if (!empty($trip2_costumer) || !empty($trip2_container) || !empty($trip2_status) || !empty($trip2_segment) || !empty($trip2_from) || !empty($trip2_to)) {
            $trip2_stmt = $conn->prepare("INSERT INTO trips 
                (d_id, trip_type, costumer, trip_container, trip_containerStat, container_activity, trip_haulingSegment, trip_haulingType, trip_from, trip_to, km_run, trip_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            $trip2_stmt->bind_param("isssssssssd", $dispatch_id, $trip2_type, $trip2_costumer, $trip2_container, $trip2_status, $trip2_activity, $trip2_segment, $trip2_hauling_type, $trip2_from, $trip2_to, $trip2_km_run);
            $trip2_stmt->execute();
            $trip2_stmt->close();

            // ✅ Insert container_activity for Trip 2
            if (!empty($trip2_container)) {
                $insertContainer2 = $conn->prepare("INSERT INTO container_activity 
                    (container_name, container_location, truck_no, trailer_no, genset_no, driver_name, container_status, trip_status, Date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                $insertContainer2->bind_param("ssssssss", 
                    $trip2_container, 
                    $trip2_to, 
                    $d_truck, 
                    $d_trailer, 
                    $d_genset, 
                    $d_driverName, 
                    $trip2_status, 
                    $d_datetime
                );
                $insertContainer2->execute();
                $insertContainer2->close();
            }
        }

        // Trip 3 insert (optional)
        if (!empty($trip3_costumer) || !empty($trip3_container) || !empty($trip3_status) || !empty($trip3_segment) || !empty($trip3_from) || !empty($trip3_to)) {
            $trip3_stmt = $conn->prepare("INSERT INTO trips 
                (d_id, trip_type, costumer, trip_container, trip_containerStat, container_activity, trip_haulingSegment, trip_haulingType, trip_from, trip_to, km_run, trip_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            $trip3_stmt->bind_param("isssssssssd", $dispatch_id, $trip3_type, $trip3_costumer, $trip3_container, $trip3_status, $trip3_activity, $trip3_segment, $trip3_hauling_type, $trip3_from, $trip3_to, $trip3_km_run);
            $trip3_stmt->execute();
            $trip3_stmt->close();

            // ✅ Insert container_activity for Trip 3
            if (!empty($trip3_container)) {
                $insertContainer3 = $conn->prepare("INSERT INTO container_activity 
                    (container_name, container_location, truck_no, trailer_no, genset_no, driver_name, container_status, trip_status, Date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                $insertContainer3->bind_param("ssssssss", 
                    $trip3_container, 
                    $trip3_to, 
                    $d_truck, 
                    $d_trailer, 
                    $d_genset, 
                    $d_driverName, 
                    $trip3_status, 
                    $d_datetime
                );
                $insertContainer3->execute();
                $insertContainer3->close();
            }
        }

        if (!empty($trip4_costumer) || !empty($trip4_container) || !empty($trip4_status) || !empty($trip4_segment) || !empty($trip4_from) || !empty($trip4_to)) {
            $trip4_stmt = $conn->prepare("INSERT INTO trips 
                (d_id, trip_type, costumer, trip_container, trip_containerStat, container_activity, trip_haulingSegment, trip_haulingType, trip_from, trip_to, km_run, trip_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            $trip4_stmt->bind_param("isssssssssd", $dispatch_id, $trip4_type, $trip4_costumer, $trip4_container, $trip4_status, $trip4_activity, $trip4_segment, $trip4_hauling_type, $trip4_from, $trip4_to, $trip4_km_run);
            $trip4_stmt->execute();
            $trip4_stmt->close();

            // ✅ Insert container_activity for Trip 4
            if (!empty($trip4_container)) {
                $insertContainer4 = $conn->prepare("INSERT INTO container_activity 
                    (container_name, container_location, truck_no, trailer_no, genset_no, driver_name, container_status, trip_status, Date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                $insertContainer4->bind_param("ssssssss", 
                    $trip4_container, 
                    $trip4_to, 
                    $d_truck, 
                    $d_trailer, 
                    $d_genset, 
                    $d_driverName, 
                    $trip4_status, 
                    $d_datetime
                );
                $insertContainer4->execute();
                $insertContainer4->close();
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Dispatch and trip(s) added!',
            'insert_id' => $dispatch_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $stmt->error
        ]);
    }

    $stmt->close();


    // 6️⃣ Update driver status to Dispatch
    $updateDriver = "UPDATE drivers SET driver_status = 'Dispatch' WHERE driver_id = ?";
    $stmt = $conn->prepare($updateDriver);
    $stmt->bind_param("s", $driver_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating driver status: " . $stmt->error);
    }
    $stmt->close();

    // 7️⃣ Assign driver to Unit (Truck)
    if (!empty($d_truck)) {
        $updateUnit = "UPDATE units SET unit_assign = ?, driver_id = ?, unit_assignGenset = ?,  unit_assignTrailer = ?, unit_status = 'Dispatch' WHERE unit_name = ?";
        $stmt = $conn->prepare($updateUnit);
        $stmt->bind_param("sisss", $d_driverName, $driver_id, $d_genset, $d_trailer, $d_truck);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to unit: " . $stmt->error);
        }
        $stmt->close();
    }

    // 8️⃣ Assign driver to Trailer
    if (!empty($d_trailer)) {
        $updateTrailer = "UPDATE trailer SET trailer_assignTo = ?, driver_id = ? WHERE trailer_name = ?";
        $stmt = $conn->prepare($updateTrailer);
        $stmt->bind_param("sis", $d_driverName, $driver_id, $d_trailer);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to trailer: " . $stmt->error);
        }
        $stmt->close();
    }

    // 8.1️⃣ Insert into trailer_movement
    if (!empty($d_trailer)) {
        $insertTrailerMovement = "INSERT INTO trailer_movement 
            (tm_trailerName, tm_driverAssign, tm_location, tm_recordedType, tm_recordedBy, tm_date) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertTrailerMovement);
        $recordedType = "Dispatch";
        $location = !empty($trip2_to) ? $trip2_to : $trip1_to;
        $stmt->bind_param("ssssss", $d_trailer, $d_driverName, $location, $recordedType, $d_dispatcher, $d_datetime);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting into trailer_movement: " . $stmt->error);
        }
        $stmt->close();
    }

    // 9️⃣ Assign driver to Genset
    if (!empty($d_genset)) {
        $updateGenset = "UPDATE units SET unit_assign = ?, driver_id = ? WHERE unit_name = ?";
        $stmt = $conn->prepare($updateGenset);
        $stmt->bind_param("sis", $d_driverName, $driver_id, $d_genset);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to genset: " . $stmt->error);
        }
        $stmt->close();
    }
}
