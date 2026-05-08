<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Dispatcher') {
    header("Location: dispatcher-index.php?route=login");
    exit();
}
$role = 'dispatcher';
$baseRoute = 'dispatch-bookingSegments';
include __DIR__ . '/../php/assets/booking_segments_body.php';
