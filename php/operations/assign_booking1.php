<?php
include "../config/config.php";

header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

/* ===============================
   GET MAIN DATA
=================================*/
$dispatcher   = $_POST['userName'] ?? '';
$dispatchHub  = $_POST['assignLocation'] ?? '';
$driver       = $_POST['driver'] ?? '';
$driver_id    = $_POST['driver_id'] ?? '';
$genset       = $_POST['genset'] ?? '';
$unitName     = $_POST['unitName'] ?? '';

$booking1 = json_decode($_POST['booking1'] ?? '{}', true);
$booking2 = json_decode($_POST['booking2'] ?? '{}', true);

$now = date("Y-m-d H:i:s");


/* =====================================================
   KM COMPUTATION FUNCTION
=====================================================*/
function computeKM($conn, $from, $to)
{
    if (empty($from) || empty($to)) return 0;

    $stmt = $conn->prepare("
        SELECT location_name, latitude, longitude
        FROM location
        WHERE location_name IN (?,?)
    ");

    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();

    $loc = [];
    while ($r = $res->fetch_assoc()) {
        $loc[$r['location_name']] = $r;
    }

    $stmt->close();

    if (!isset($loc[$from], $loc[$to])) return 0;

    $lat1 = (float)$loc[$from]['latitude'];
    $lon1 = (float)$loc[$from]['longitude'];
    $lat2 = (float)$loc[$to]['latitude'];
    $lon2 = (float)$loc[$to]['longitude'];

    return round(sqrt(pow($lat2 - $lat1, 2) + pow($lon2 - $lon1, 2)) * 111, 2);
}


/* =====================================================
   PROCESS BOOKING
=====================================================*/
function processBooking($conn, $data, $dispatcher, $dispatchHub, $driver, $driver_id, $unitName, $genset, $now)
{
    if (empty($data['booking_no'])) return null;

    $booking_no = $data['booking_no'];
    $trailer    = $data['trailer'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_no=?");
    $stmt->bind_param("s", $booking_no);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception("Booking not found: " . $booking_no);
    }

    /* INSERT DISPATCH */
    $stmt = $conn->prepare("
        INSERT INTO dispatch
        (booking_no,d_datetime,d_dispatcher,d_dispatchHub,
         d_driverName,driver_id,d_truck,d_trailer,d_genset,
         d_tripReceipt,d_ecs,costumer)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");

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
        $data['receipt'],
        $data['ecs'],
        $booking['costumer']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting dispatch: " . $stmt->error);
    }

    $dispatch_id = $stmt->insert_id;
    $stmt->close();

    /* ================= TRIPS ================= */

    $to = null; // we will store last destination

    // TRIP 1
    if (!empty($data['trip1_from']) && !empty($data['trip1_to'])) {

        $km = computeKM($conn, $data['trip1_from'], $data['trip1_to']);
        $tripType = "Trip 1";
        $to = $data['trip1_to'];

        $stmt = $conn->prepare("
            INSERT INTO trips
            (d_id,trip_type,costumer,trip_container,
             container_activity,trip_containerStat, trip_haulingSegment, trip_haulingType,
             trip_from,trip_to,km_run,required_date,trip_status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'Active')
        ");

        $stmt->bind_param(
            "isssssssssds",
            $dispatch_id,
            $tripType,
            $booking['costumer'],
            $booking['container'],
            $booking['booking_activity'],
            $booking['container_status'],
            $booking['hauling_segment'],
            $booking['hauling_type'],
            $data['trip1_from'],
            $data['trip1_to'],
            $km,
            $booking['booking_dateRequired']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting Trip 1: " . $stmt->error);
        }

        $stmt->close();
    }

    // TRIP 2
    if (!empty($data['trip2_from']) && !empty($data['trip2_to'])) {

        $km = computeKM($conn, $data['trip2_from'], $data['trip2_to']);
        $tripType = "Trip 2";
        $to = $data['trip2_to'];

        $stmt = $conn->prepare("
            INSERT INTO trips
            (d_id,trip_type,costumer,trip_container,
             container_activity,trip_containerStat,
             trip_from,trip_to,km_run,required_date,trip_status)
            VALUES (?,?,?,?,?,?,?,?,?,?, 'Active')
        ");

        $stmt->bind_param(
            "isssssssds",
            $dispatch_id,
            $tripType,
            $booking['costumer'],
            $booking['container'],
            $booking['booking_activity'],
            $booking['container_status'],
            $data['trip2_from'],
            $data['trip2_to'],
            $km,
            $booking['booking_dateRequired']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting Trip 2: " . $stmt->error);
        }

        $stmt->close();
    }

    /* ================= UPDATE BOOKING ================= */

    // 5️⃣ Update quantity_use
    $stmt = $conn->prepare("UPDATE booking SET quantity_use = quantity_use + 1 WHERE booking_no = ?");
    $stmt->bind_param("s", $booking_no);
    if (!$stmt->execute()) {
        throw new Exception("Error updating booking quantity_use: " . $stmt->error);
    }
    $stmt->close();

    // 🔟 Check if Complete
    $stmt = $conn->prepare("SELECT quantity, quantity_use FROM booking WHERE booking_no = ?");
    $stmt->bind_param("s", $booking_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && (int)$row['quantity_use'] >= (int)$row['quantity']) {
        $stmt = $conn->prepare("UPDATE booking SET status = 'Complete' WHERE booking_no = ?");
        $stmt->bind_param("s", $booking_no);
        if (!$stmt->execute()) {
            throw new Exception("Error updating booking status: " . $stmt->error);
        }
        $stmt->close();
    }

    /* ================= DRIVER & UNIT UPDATES ================= */

    // 6️⃣ Update driver status
    $stmt = $conn->prepare("UPDATE drivers SET driver_status = 'Dispatch' WHERE driver_id = ?");
    $stmt->bind_param("s", $driver_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating driver status: " . $stmt->error);
    }
    $stmt->close();

    // 7️⃣ Assign driver to unit
    if (!empty($unitName)) {
        $stmt = $conn->prepare("
            UPDATE units 
            SET unit_assign = ?, driver_id = ?, unit_assignGenset = ?, 
                unit_assignTrailer = ?, unit_status = 'Dispatch'
            WHERE unit_name = ?
        ");
        $stmt->bind_param("sisss", $driver, $driver_id, $genset, $trailer, $unitName);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to unit: " . $stmt->error);
        }
        $stmt->close();
    }

    // 8️⃣ Assign driver to trailer
    if (!empty($trailer)) {

        $stmt = $conn->prepare("UPDATE trailer SET trailer_assignTo = ?, driver_id = ? WHERE trailer_name = ?");
        $stmt->bind_param("sis", $driver, $driver_id, $trailer);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to trailer: " . $stmt->error);
        }
        $stmt->close();

        // 8.1️⃣ Insert trailer movement
        if (!empty($to)) {
            $recordedType = "Dispatch";

            $stmt = $conn->prepare("
                INSERT INTO trailer_movement 
                (tm_trailerName, tm_driverAssign, tm_location, tm_recordedType, tm_recordedBy, tm_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("ssssss", $trailer, $driver, $to, $recordedType, $dispatcher, $now);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting trailer movement: " . $stmt->error);
            }

            $stmt->close();
        }
    }

    // 9️⃣ Assign driver to genset
    if (!empty($genset)) {
        $stmt = $conn->prepare("UPDATE units SET unit_assign = ?, driver_id = ?, unit_status = 'Dispatch' WHERE unit_name = ?");
        $stmt->bind_param("sis", $driver, $driver_id, $genset);
        if (!$stmt->execute()) {
            throw new Exception("Error assigning driver to genset: " . $stmt->error);
        }
        $stmt->close();
    }
    return $dispatch_id;
}

/* =====================================================
   TRANSACTION
=====================================================*/
mysqli_begin_transaction($conn);

try {

    $dispatch_id1 = processBooking($conn, $booking1, $dispatcher, $dispatchHub, $driver, $driver_id, $unitName, $genset, $now);
    $dispatch_id2 = processBooking($conn, $booking2, $dispatcher, $dispatchHub, $driver, $driver_id, $unitName, $genset, $now);

    mysqli_commit($conn);

    echo json_encode([
        "status" => "success",
        "message" => "Dispatch Assigned Successfully",
        "dispatch_ids" => array_filter([$dispatch_id1, $dispatch_id2])
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
