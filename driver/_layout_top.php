<?php
// Shared driver-app page shell. Caller already auth'd as Driver.
$pageTitle = $pageTitle ?? 'Pantrucks Driver';
$activeNav = $activeNav ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="manifest" href="manifest.webmanifest">
  <meta name="theme-color" content="#0d6efd">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="apple-touch-icon" href="assets/images/logos/LogoFleet.png">
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="assets/css/driver-modern.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>
<body>
  <div class="page-wrapper" id="main-wrapper">
    <div class="app-topstrip"></div>

    <div class="body-wrapper">
      <header class="app-header" style="padding:10px 15px;display:flex;align-items:center;gap:10px;background:#fff;border-bottom:1px solid #eee;position:sticky;top:0;z-index:5;">
        <a href="driver-dashboard" class="text-decoration-none text-dark"><i class="ti ti-arrow-left fs-5"></i></a>
        <strong><?php echo htmlspecialchars($pageTitle); ?></strong>
      </header>

      <div class="container-fluid pb-5 mb-5">
