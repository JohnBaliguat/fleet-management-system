<?php
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';
$res = $conn->query("SELECT COUNT(*) c FROM incident WHERE status = 'open'");
$row = $res->fetch_assoc();
echo json_encode(['status' => 'success', 'count' => (int)$row['c']]);
$conn->close();
