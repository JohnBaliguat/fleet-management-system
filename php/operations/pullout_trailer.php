<?php
include '../config/config.php';

if (isset($_POST['trailer_id'])) {
    $trailer_id = intval(value: $_POST['trailer_id']);

    $sql = "UPDATE trailer SET trailer_assignTo = '', trailer_status = 'Good' WHERE trailer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trailer_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "invalid";
}
?>
