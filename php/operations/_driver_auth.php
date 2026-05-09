<?php
// Phase 5 — small helper used by every driver-app endpoint.
// Usage:
//   require __DIR__ . '/_driver_auth.php';
//   $driverId = require_driver_session();   // exits with JSON error otherwise
function require_driver_session(): int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (($_SESSION['user_type'] ?? '') !== 'Driver') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Driver session required']);
        exit;
    }
    return (int)($_SESSION['user_id'] ?? 0);
}

function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'POST required']);
        exit;
    }
}

function json_out(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
