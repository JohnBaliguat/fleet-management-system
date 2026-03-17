<?php
include "../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_no   = trim($_POST['booking_no']);
    $trip_receipt = trim($_POST['trip_receipt']);
    $ecs          = trim($_POST['ecs']);
    $driver       = trim($_POST['driver']);
    $driver_id    = trim($_POST['driver_id']);
    $genset       = trim($_POST['genset']);
    $trailer      = trim($_POST['trailer']);
    $dispatcher   = trim($_POST['userName']);
    $dispatchHub  = trim($_POST['assignLocation']);
    $unitName     = trim($_POST['unitName']);

    mysqli_begin_transaction($conn);

    try {
        // 0️⃣ Check if booking still has available quantity
        $checkQty = "SELECT quantity, quantity_use, costumer FROM booking WHERE booking_no = ?";
        $stmt = $conn->prepare($checkQty);
        $stmt->bind_param("s", $booking_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookingQty = $result->fetch_assoc();
        $stmt->close();

        if (!$bookingQty) {
            echo json_encode(["status" => "error", "message" => "Booking not found!"]);
            exit;
        }

        $costumer = $bookingQty['costumer'];

        if ((int)$bookingQty['quantity_use'] >= (int)$bookingQty['quantity']) {
            echo json_encode(["status" => "error", "message" => "Booking is already fully assigned!"]);
            exit;
        }

        // 0.5️⃣ Validate Unit (Truck) registration + status + assignment
        if (!empty($unitName)) {
            $checkUnit = "SELECT unit_status, unit_assign, driver_id FROM units WHERE unit_name = ?";
            $stmt = $conn->prepare($checkUnit);
            $stmt->bind_param("s", $unitName);
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
                echo json_encode(["status" => "error", "message" => "Truck is unavailable! (".$unitRow['unit_status'].")"]);
                exit;
            }

            // 🚫 Assignment check
            if (!empty($unitRow['unit_assign']) && (int)$unitRow['driver_id'] !== 0) {
                if ($unitRow['unit_assign'] !== $driver || (int)$unitRow['driver_id'] !== (int)$driver_id) {
                    echo json_encode(["status" => "error", "message" => "Truck is already assigned to another driver!"]);
                    exit;
                }
            }
        }

        // 0.5️⃣ Validate Unit (Genset) registration + status + assignment
        if (!empty($genset)) {
            $checkGenset = "SELECT unit_status, unit_assign, driver_id FROM units WHERE unit_name = ?";
            $stmt = $conn->prepare($checkGenset);
            $stmt->bind_param("s", $genset);
            $stmt->execute();
            $result = $stmt->get_result();
            $gensetRow = $result->fetch_assoc();
            $stmt->close();

            if (!$gensetRow) {
                echo json_encode(["status" => "error", "message" => "Genset is not registered!"]);
                exit;
            }

            if (in_array($gensetRow['unit_status'], ['Rescue', 'Shop Unit'])) {
                echo json_encode(["status" => "error", "message" => "Genset is unavailable! (".$gensetRow['unit_status'].")"]);
                exit;
            }

            if (!empty($gensetRow['unit_assign']) && (int)$gensetRow['driver_id'] !== 0) {
                if ($gensetRow['unit_assign'] !== $driver || (int)$gensetRow['driver_id'] !== (int)$driver_id) {
                    echo json_encode(["status" => "error", "message" => "Genset is already assigned to another driver!"]);
                    exit;
                }
            }
        }

        // 0.6️⃣ Validate Trailer registration + status + assignment
        if (!empty($trailer)) {
            $checkTrailer = "SELECT trailer_status, trailer_assignTo, driver_id FROM trailer WHERE trailer_name = ?";
            $stmt = $conn->prepare($checkTrailer);
            $stmt->bind_param("s", $trailer);
            $stmt->execute();
            $result = $stmt->get_result();
            $trailerRow = $result->fetch_assoc();
            $stmt->close();

            if (!$trailerRow) {
                echo json_encode(["status" => "error", "message" => "Trailer is not registered!"]);
                exit;
            }

            if (in_array($trailerRow['trailer_status'], ['Rescue', 'Shop Unit'])) {
                echo json_encode(["status" => "error", "message" => "Trailer is unavailable! (".$trailerRow['trailer_status'].")"]);
                exit;
            }

            if (!empty($trailerRow['trailer_assignTo']) && (int)$trailerRow['driver_id'] !== 0) {
                if ($trailerRow['trailer_assignTo'] !== $driver || (int)$trailerRow['driver_id'] !== (int)$driver_id) {
                    echo json_encode(["status" => "error", "message" => "Trailer is already assigned to another driver!"]);
                    exit;
                }
            }
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

        $errors = [];

        // Only check if both receipt and ECS are given
        if (!empty($trip_receipt) && !empty($ecs)) {

            // === Check Trip Receipt for LOADED trips ===
            $checkReceiptLoaded = "
                SELECT 1 
                FROM dispatch d
                INNER JOIN trips t ON d.d_id = t.d_id
                WHERE d.d_tripReceipt = ?
                AND t.trip_containerStat = 'LOADED'
                LIMIT 1
            ";
            $stmt = $conn->prepare($checkReceiptLoaded);
            $stmt->bind_param("s", $trip_receipt);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Trip Receipt already exists in a LOADED trip!";
            }
            $stmt->close();

            // === Check Trip Receipt for EMPTY trips ===
            $checkReceiptEmpty = "
                SELECT 1 
                FROM dispatch d
                INNER JOIN trips t ON d.d_id = t.d_id
                WHERE d.d_tripReceipt = ?
                AND t.trip_containerStat = 'EMPTY'
                LIMIT 1
            ";
            $stmt = $conn->prepare($checkReceiptEmpty);
            $stmt->bind_param("s", $trip_receipt);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Trip Receipt already exists in an EMPTY trip!";
            }
            $stmt->close();


            // === Check ECS for LOADED trips ===
            $checkEcsLoaded = "
                SELECT 1 
                FROM dispatch d
                INNER JOIN trips t ON d.d_id = t.d_id
                WHERE d.d_ecs = ?
                AND t.trip_containerStat = 'LOADED'
                LIMIT 1
            ";
            $stmt = $conn->prepare($checkEcsLoaded);
            $stmt->bind_param("s", $ecs);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "ECS already exists in a LOADED trip!";
            }
            $stmt->close();

            // === Check ECS for EMPTY trips ===
            $checkEcsEmpty = "
                SELECT 1 
                FROM dispatch d
                INNER JOIN trips t ON d.d_id = t.d_id
                WHERE d.d_ecs = ?
                AND t.trip_containerStat = 'EMPTY'
                LIMIT 1
            ";
            $stmt = $conn->prepare($checkEcsEmpty);
            $stmt->bind_param("s", $ecs);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "ECS already exists in an EMPTY trip!";
            }
            $stmt->close();

            // If any error found, return JSON
            if (!empty($errors)) {
                echo json_encode([
                    "status" => "error",
                    "message" => implode(" ", $errors)
                ]);
                exit;
            }
        }

        date_default_timezone_set("Asia/Manila");
        $now = date("Y-m-d H:i:s");

        // 2️⃣ Insert into dispatch
        $insertDispatch = "INSERT INTO dispatch 
            (booking_no, d_datetime, d_dispatcher, d_dispatchHub, d_driverName, driver_id, d_truck, d_trailer, d_genset, d_tripReceipt, d_ecs, costumer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertDispatch);
        $stmt->bind_param(
            "ssssssssssss", 
            $booking_no,
            $now, 
            $dispatcher, 
            $dispatchHub, 
            $driver, 
            $driver_id, 
            $unitName, 
            $trailer, 
            $genset, 
            $trip_receipt, 
            $ecs, 
            $costumer
        );
        if (!$stmt->execute()) {
            throw new Exception("Error inserting dispatch: " . $stmt->error);
        }
        $dispatch_id = $stmt->insert_id;
        $stmt->close();

        // 3️⃣ Get booking data for trips
        $getBooking = "SELECT container, container_status, hauling_segment, hauling_type, trip_from, trip_to, booking_dateRequired, booking_activity
               FROM booking WHERE booking_no = ?";
        $stmt = $conn->prepare($getBooking);
        $stmt->bind_param("s", $booking_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookingData = $result->fetch_assoc();
        $stmt->close();

        if (!$bookingData) {
            throw new Exception("Booking details not found for booking_no: " . $booking_no);
        }
        
        function getOSRMDistanceWithGeometry($lat1, $lon1, $lat2, $lon2)
        {
            $url = "http://router.project-osrm.org/route/v1/driving/"
                . "$lon1,$lat1;$lon2,$lat2"
                . "?overview=full&geometries=geojson";

            $response = @file_get_contents($url);
            if ($response === false) return 0;

            $data = json_decode($response, true);

            if (
                !isset($data['routes'][0]['distance']) ||
                !isset($data['routes'][0]['geometry']['coordinates'])
            ) {
                return 0;
            }

            $route = $data['routes'][0];
            $distanceKm = $route['distance'] / 1000;
            $coords = $route['geometry']['coordinates'];

            // 📍 SPECIAL POINT
            $specialLat = 7.333325;
            $specialLon = 125.626795;

            // 🔍 Check if route passes near this point
            if (pointNearRoute($coords, $specialLat, $specialLon, 100)) {
                $distanceKm += 2.4;
            }

            return round($distanceKm, 2);
        }

            function pointNearRoute($routeCoords, $checkLat, $checkLon, $toleranceMeters = 100)
            {
                foreach ($routeCoords as $coord) {
                    // OSRM uses [lon, lat]
                    $lon = $coord[0];
                    $lat = $coord[1];

                    $distance = haversineMeters($lat, $lon, $checkLat, $checkLon);

                    if ($distance <= $toleranceMeters) {
                        return true;
                    }
                }
                return false;
            }

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
        

        $from = $bookingData['trip_from'];
        $to   = $bookingData['trip_to'];
        $sqlLoc = "SELECT location_name, latitude, longitude FROM location WHERE location_name IN (?, ?)";
        $stmt = $conn->prepare($sqlLoc);
        $stmt->bind_param("ss", $from, $to);
        $stmt->execute();
        $resLoc = $stmt->get_result();
        $locations = [];
        while ($row = $resLoc->fetch_assoc()) {
            $locations[$row['location_name']] = $row;
        }
        $stmt->close();

        $km_run = 0; // default if no lat/lng
        if (
            isset(
                $locations[$from]['latitude'], 
                $locations[$from]['longitude'],
                $locations[$to]['latitude'], 
                $locations[$to]['longitude']
            )
            && is_numeric($locations[$from]['latitude'])
            && is_numeric($locations[$from]['longitude'])
            && is_numeric($locations[$to]['latitude'])
            && is_numeric($locations[$to]['longitude'])
        ) {
            $km_run = getOSRMDistanceWithGeometry(
                $locations[$from]['latitude'],
                $locations[$from]['longitude'],
                $locations[$to]['latitude'],
                $locations[$to]['longitude']
            );
        }

        // 4️⃣ Insert into trips
        $insertTrip = "INSERT INTO trips 
            (d_id, trip_type, costumer, trip_container, container_activity, trip_containerStat, trip_haulingSegment, trip_haulingType, 
            trip_from, trip_to, km_run, required_date, trip_status)
            VALUES (?, 'Trip 1', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";

        $stmt = $conn->prepare($insertTrip);
        $stmt->bind_param(
            "issssssssds",   // ✅ Correct: 10 params
            $dispatch_id,
            $costumer,
            $bookingData['container'],
            $bookingData['booking_activity'],
            $bookingData['container_status'],
            $bookingData['hauling_segment'],
            $bookingData['hauling_type'],
            $bookingData['trip_from'],
            $bookingData['trip_to'],
            $km_run,
            $bookingData['booking_dateRequired']
        );
        if (!$stmt->execute()) {
            throw new Exception("Error inserting trip: " . $stmt->error);
        }
        $stmt->close();

        // ✅ Insert into container_activity
        if (!empty($bookingData['container'])) {
            $insertContainer = "INSERT INTO container_activity 
                (container_name, container_location, truck_no, trailer_no, genset_no, driver_name, container_status, trip_status, Date)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?)";
            $stmt = $conn->prepare($insertContainer);
            $stmt->bind_param(
                "ssssssss",
                $bookingData['container'], 
                $bookingData['trip_to'], 
                $unitName, 
                $trailer, 
                $genset, 
                $driver, 
                $bookingData['container_status'], 
                $now
            );
            if (!$stmt->execute()) {
                throw new Exception("Error inserting container activity: " . $stmt->error);
            }
            $stmt->close();
        }

        // 5️⃣ Update booking quantity_use
        $updateBooking = "UPDATE booking SET quantity_use = quantity_use + 1 WHERE booking_no = ?";
        $stmt = $conn->prepare($updateBooking);
        $stmt->bind_param("s", $booking_no);
        if (!$stmt->execute()) {
            throw new Exception("Error updating booking quantity_use: " . $stmt->error);
        }
        $stmt->close();

        // 🔟 After updating quantity_use, check if complete
        $checkComplete = "SELECT quantity, quantity_use FROM booking WHERE booking_no = ?";
        $stmt = $conn->prepare($checkComplete);
        $stmt->bind_param("s", $booking_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && (int)$row['quantity_use'] >= (int)$row['quantity']) {
            $updateStatus = "UPDATE booking SET status = 'Complete' WHERE booking_no = ?";
            $stmt = $conn->prepare($updateStatus);
            $stmt->bind_param("s", $booking_no);
            if (!$stmt->execute()) {
                throw new Exception("Error updating booking status: " . $stmt->error);
            }
            $stmt->close();
        }

        // 6️⃣ Update driver status to Dispatch
        $updateDriver = "UPDATE drivers SET driver_status = 'Dispatch' WHERE driver_id = ?";
        $stmt = $conn->prepare($updateDriver);
        $stmt->bind_param("s", $driver_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating driver status: " . $stmt->error);
        }
        $stmt->close();

        
        // 7️⃣ Assign driver to Unit (Truck)
        if (!empty($unitName)) {
            $updateUnit = "UPDATE units SET unit_assign = ?, driver_id = ?, unit_assignGenset = ?,  unit_assignTrailer = ?, unit_status = 'Dispatch' WHERE unit_name = ?";
            $stmt = $conn->prepare($updateUnit);
            $stmt->bind_param("sisss", $driver,$driver_id, $genset, $trailer, $unitName);
            if (!$stmt->execute()) {
                throw new Exception("Error assigning driver to unit: " . $stmt->error);
            }
            $stmt->close();
        }

        // 8️⃣ Assign driver to Trailer
        if (!empty($trailer)) {
            $updateTrailer = "UPDATE trailer SET trailer_assignTo = ?, driver_id = ? WHERE trailer_name = ?";
            $stmt = $conn->prepare($updateTrailer);
            $stmt->bind_param("sis", $driver, $driver_id, $trailer);
            if (!$stmt->execute()) {
                throw new Exception("Error assigning driver to trailer: " . $stmt->error);
            }
            $stmt->close();
        }
        // 8.1️⃣ Insert into trailer_movement
        if (!empty($trailer)) {
            $insertTrailerMovement = "INSERT INTO trailer_movement 
                (tm_trailerName, tm_driverAssign, tm_location, tm_recordedType, tm_recordedBy, tm_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertTrailerMovement);
            $recordedType = "Dispatch";
            $stmt->bind_param("ssssss", $trailer, $driver, $to, $recordedType, $dispatcher, $now);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting into trailer_movement: " . $stmt->error);
            }
            $stmt->close();
        }

        // 9️⃣ Assign driver to Genset
        if (!empty($genset)) {
            $updateGenset = "UPDATE units SET unit_assign = ?, driver_id = ?, unit_status = 'Dispatch' WHERE unit_name = ?";
            $stmt = $conn->prepare($updateGenset);
            $stmt->bind_param("sis", $driver, $driver_id, $genset);
            if (!$stmt->execute()) {
                throw new Exception("Error assigning driver to genset: " . $stmt->error);
            }
            $stmt->close();
        }

        // ✅ Commit transaction
        mysqli_commit($conn);
        echo json_encode(["status" => "success", "message" => "Booking assigned successfully!", "insert_id" => $dispatch_id]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
