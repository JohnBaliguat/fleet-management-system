<?php
include "../config/config.php";

$s_id      = intval($_POST['rescue_id']); // shop_unit ID
$manpower  = $_POST['manpower'];          // array of smp_id

$conn->begin_transaction();

try {

    // 1️⃣ Update shop_unit status
    $stmt = $conn->prepare(
        "UPDATE shop_unit 
         SET s_status = 'assigned' 
         WHERE s_id = ?"
    );
    $stmt->bind_param("i", $s_id);
    $stmt->execute();

    // 2️⃣ Insert shop_record
    $stmt = $conn->prepare(
        "INSERT INTO shop_record (s_id, sr_description, sr_date)
         VALUES (?, 'Unit Assigned', NOW())"
    );
    $stmt->bind_param("i", $s_id);
    $stmt->execute();

    // Get inserted shop_record ID
    $sr_id = $conn->insert_id;

    // 3️⃣ Insert assign_manpower (multiple)
    $stmt = $conn->prepare(
        "INSERT INTO assign_manpower (sr_id, smp_id)
         VALUES (?, ?)"
    );

    foreach ($manpower as $smp_id) {
        $smp_id = intval($smp_id);
        $stmt->bind_param("ii", $sr_id, $smp_id);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        "status"  => "success",
        "message" => "Unit successfully assigned."
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => "Assignment failed."
    ]);
}
