<?php

// routes.php - Define available routes
$routes = [
    '' => 'admin/dashboard.php',
    'dashboard' => 'admin/dashboard.php',
    'dispatchDashboard' => 'admin/dispatch-dashboard1.php',
    'dashboard-trailer' => 'admin/dashboard-trailer.php',
    'dashboard-truck' => 'admin/dashboard-truck.php',
    'dispatch' => 'admin/dispatch.php',
    'driversReport' => 'admin/drivers-report.php',
    'drivers' => 'admin/drivers.php',
    'print' => 'admin/print_dispatch.php',
    'printcth' => 'admin/print_dispatchCTH.php',
    'gate' => 'admin/gate.php',
    'mybook' => 'admin/mybooking.php',
    'allbook' => 'admin/allbooking.php',
    'addbook' => 'admin/addbooking.php',
    'monitoring' => 'admin/monitoring.php',
    'abcmonitoring' => 'admin/abcmonitoring.php',
    'dolemonitoring' => 'admin/dolemonitoring.php',
    'dmmonitoring' => 'admin/dmmonitoring.php',
    'farmmonitoring' => 'admin/farmmonitoring.php',
    'sumimonitoring' => 'admin/sumimonitoring.php',
    'cthmonitoring' => 'admin/cthmonitoring.php',
    'trailer' => 'admin/trailer.php',
    'tripReport' => 'admin/trip-report.php',
    'attendReport' => 'admin/attend-report.php',
    'trailerReport' => 'admin/trailer-report.php',
    'truckReport' => 'admin/truck-report.php',
    'truck' => 'admin/truck.php',
    'unitProfile' => 'admin/addunit-page.php',
    'unitEditProfile' => 'admin/updateunit-page.php',
    'genset' => 'admin/genset.php',
    'user' => 'admin/user.php',
    'segment' => 'admin/segment.php',
    'profile' => 'admin/profile.php',
    'logout' => 'logout.php',
    'login' => 'login.php',
];

// Get the route from URL
$route = $_GET['route'] ?? 'dashboard';

// Check if the route exists, otherwise show a 404 page
if (array_key_exists($route, $routes)) {
    require $routes[$route];
} else {
    require 'admin/404.php';
}
?>
