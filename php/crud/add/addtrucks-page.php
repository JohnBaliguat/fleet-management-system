<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Text fields
    $unit_name      = trim($_POST['unit_name']);
    $unit_std       = trim($_POST['std']);
    $unit_plateNo   = trim($_POST['plate_number']);
    $unit_or        = trim($_POST['OR']);
    $unit_cr        = trim($_POST['CR']);
    $unit_brand     = trim($_POST['brand']);
    $unit_modal     = trim($_POST['model']);
    $unit_year      = trim($_POST['year']);
    $unit_engineNo  = trim($_POST['engine_number']);
    $unit_chassisNo = trim($_POST['chassis_number']);
    $unit_fuelType  = trim($_POST['fuel_type']);
    $unit_capacity  = trim($_POST['capacity']);
    $unit_remarks   = trim($_POST['remarks']);

    // ✅ Duplicate check
    $check_sql = "SELECT unit_id FROM units 
                  WHERE unit_name = '$unit_name' 
                     OR unit_plate = '$unit_plateNo' 
                     OR unit_or = '$unit_or' 
                     OR unit_cr = '$unit_cr' 
                  LIMIT 1";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        echo "Error: Duplicate record found (Unit Name, Plate Number, OR, or CR already exists).";
        exit;
    }

    // Upload directory
    $uploadDir = "truckphoto/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Sanitize unit name for filenames
    $safeUnitName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $unit_name);

    // Handle image uploads (optional)
    $frontView = uploadImage('front_view', $uploadDir, $safeUnitName . "_front");
    $backView  = uploadImage('back_view', $uploadDir, $safeUnitName . "_back");
    $leftView  = uploadImage('left_view', $uploadDir, $safeUnitName . "_left");
    $rightView = uploadImage('right_view', $uploadDir, $safeUnitName . "_right");

    $defaultImg = 'noimage.png'; // must exist in truckphoto/
    $frontView = $frontView ?: $defaultImg;
    $backView  = $backView ?: $defaultImg;
    $leftView  = $leftView ?: $defaultImg;
    $rightView = $rightView ?: $defaultImg;
    // Insert into DB (all dbValue calls guaranteed to return something)
    $sql = "INSERT INTO units (
                unit_name, unit_std, unit_plate, unit_or, unit_cr, 
                unit_brand, unit_modal, unit_year, unit_engineNo, unit_chassisNo, 
                unit_fuelType, unit_capacity, unit_remarks, 
                unit_frontView, unit_leftView, unit_rightView, unit_backview
            ) VALUES (
                '$unit_name', '$unit_std', '$unit_plateNo', '$unit_or', '$unit_cr', 
                '$unit_brand', '$unit_modal', '$unit_year', '$unit_engineNo', '$unit_chassisNo', 
                '$unit_fuelType', '$unit_capacity', '$unit_remarks', 
                " . dbValue($frontView) . ", " . dbValue($leftView) . ", " . dbValue($rightView) . ", " . dbValue($backView) . "
            )";

    if (mysqli_query($conn, $sql)) {
        echo "Truck unit added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Image upload helper with custom filename (optional)
function uploadImage($fieldName, $uploadDir, $newBaseName) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['size'] > 0) {
        $fileName = basename($_FILES[$fieldName]['name']);
        $fileTmp  = $_FILES[$fieldName]['tmp_name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allow only certain extensions
        if (!in_array($fileExt, ['png', 'jpg', 'jpeg'])) {
            return null;
        }

        $newName = $newBaseName . "." . $fileExt;
        if (move_uploaded_file($fileTmp, $uploadDir . $newName)) {
            return $newName;
        }
        return null;
    }
    return null; // No file uploaded
}

// Helper to insert NULL or quoted string into SQL
function dbValue($val) {
    return ($val === null || $val === "") ? "NULL" : "'" . addslashes($val) . "'";
}
?>
