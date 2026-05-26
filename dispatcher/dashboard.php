<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Dispatcher") {


?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    <link rel="stylesheet" href="assets/css/enhancements.css" />
    <link rel="stylesheet" href="datatable/datatables.min.css">
    <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
    <style>
      /* Common style for all status cells */
      #table-data td {
        font-weight: 600;       /* bold text */
      }

      /* Specific background + darker font */
      .status-restday {
        background-color: lightcoral !important;
        color: #222 !important; /* darker text */
        border-color: lightcoral !important;
      }

      .status-leave {
        background-color: lightyellow !important;
        color: #333 !important;
        border-color: lightyellow !important;
      }

      .status-dispatch {
        background-color: lightgreen !important;
        color: #111 !important;
        border-color: lightgreen !important;
      }

      .mismatch-unit {
        background-color: lightcoral !important;
        color: #222 !important;
      }

      /* Dropdown */
      .dropdown1 {
        position: relative;
        display: inline-block;
        width: 250px;
        font-family: Arial, sans-serif;
        margin: 10px;
      }
      .dropdown1-btn {
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #ccc;
        width: 100%;
        cursor: pointer;
        text-align: left;
        border-radius: 5px;
      }
      .dropdown1-content {
        display: none;
        position: absolute;
        background: #fff;
        border: 1px solid #ddd;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      .dropdown1-content label {
        display: block;
        padding: 8px;
        cursor: pointer;
      }
      .dropdown1-content input[type="checkbox"] {
        margin-right: 8px;
      }
      .dropdown1.show .dropdown1-content {
        display: block;
      }
      .selected-values {
        margin: 10px 0;
        font-size: 14px;
      }
    </style>
  </head>

  <body>
    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
      data-sidebar-position="fixed" data-header-position="fixed">

      <!--  App Topstrip -->
      <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center justify-content-center gap-5 mb-2 mb-lg-0">
          <a class="d-flex justify-content-center" href="#">
            <img src="assets/images/logos/pantrucks.png" alt="" width="122">
          </a>
        </div>

        <div class="d-lg-flex align-items-center gap-2">
          <h3 class="text-white mb-2 mb-lg-0 fs-5 text-center">Pantrucks Fleet Management System</h3>
          <div class="d-flex align-items-center justify-content-center gap-2">
          </div>
        </div>

      </div>
      <!-- Sidebar Start -->
      <?php include 'sidebar.php'; ?>
      <!--  Sidebar End -->
      <!--  Main wrapper -->
      <div class="body-wrapper">
        <!--  Header Start -->
        <?php include 'navbar.php'; ?>
        <!--  Header End -->
        <div class="body-wrapper-inner">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-clock fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Today's Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="dailyCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-chart-line fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Average Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="avgCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-database fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Total Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Phase 4 + 7: Gate Queue + Incidents + Pending Verification + Dispatchable -->
            <div class="row mt-2">
              <div class="col-md-3">
                <a href="dispatch-gate" class="text-decoration-none">
                  <div class="card overflow-hidden border-warning">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-warning rounded-circle round-48 hstack justify-content-center text-dark"><i class="ti ti-door fs-6"></i></span>
                        <div class="ms-3"><h5 class="mb-0 fw-bolder fs-4 text-dark">Gate Queue</h5><p class="mb-0 text-muted small" id="gateQueueLatest">&mdash;</p></div>
                        <div class="ms-auto"><span id="gateQueueCount" class="badge bg-warning text-dark" style="font-size:24px;font-weight:700;">0</span></div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
              <div class="col-md-3">
                <a href="dispatch-incidents" class="text-decoration-none">
                  <div class="card overflow-hidden border-danger">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-danger rounded-circle round-48 hstack justify-content-center"><i class="ti ti-alert-triangle fs-6"></i></span>
                        <div class="ms-3"><h5 class="mb-0 fw-bolder fs-4 text-dark">Open Incidents</h5><p class="mb-0 text-muted small" id="incidentLatest">&mdash;</p></div>
                        <div class="ms-auto"><span id="openIncidentCount" class="badge bg-danger" style="font-size:24px;font-weight:700;">0</span></div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
              <div class="col-md-3">
                <a href="dispatch-verifications" class="text-decoration-none">
                  <div class="card overflow-hidden border-info">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-info rounded-circle round-48 hstack justify-content-center"><i class="ti ti-clipboard-check fs-6"></i></span>
                        <div class="ms-3"><h5 class="mb-0 fw-bolder fs-4 text-dark">Pending Verification</h5><p class="mb-0 text-muted small">POD review queue</p></div>
                        <div class="ms-auto"><span id="pendingVerifyCount" class="badge bg-info" style="font-size:24px;font-weight:700;">0</span></div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
              <div class="col-md-3">
                <a href="dispatch-equipment" class="text-decoration-none">
                  <div class="card overflow-hidden border-success">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-success rounded-circle round-48 hstack justify-content-center"><i class="ti ti-users fs-6"></i></span>
                        <div class="ms-3"><h5 class="mb-0 fw-bolder fs-4 text-dark">Dispatchable Drivers</h5><p class="mb-0 text-muted small">On shift, with truck</p></div>
                        <div class="ms-auto"><span id="dispatchableCount" class="badge bg-success" style="font-size:24px;font-weight:700;">0</span></div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            </div>

            <!-- Phase 10: blocked-units row -->
            <div class="row mt-2">
              <div class="col-md-6">
                <a href="dispatch-blocked-units" class="text-decoration-none">
                  <div class="card overflow-hidden border-secondary">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-dark rounded-circle round-48 hstack justify-content-center"><i class="ti ti-tools fs-6"></i></span>
                        <div class="ms-3"><h5 class="mb-0 fw-bolder fs-4 text-dark">Blocked Units (maintenance)</h5><p class="mb-0 text-muted small" id="blockedUnitsLatest">&mdash;</p></div>
                        <div class="ms-auto"><span id="blockedUnitsCount" class="badge bg-dark" style="font-size:24px;font-weight:700;">0</span></div>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            </div>
            <script>
            (function(){
              function pollPhase4(){
                $.getJSON('php/fetch/gate_queue.php', { status: 'pending' }, function(res){
                  if (res.status !== 'success') return;
                  $('#gateQueueCount').text(res.rows.length);
                  if (res.rows.length) {
                    var latest = res.rows[0];
                    $('#gateQueueLatest').text((latest.truck_plate || '-') + ' — ' + (latest.d_driverName || latest.booking_no || ''));
                  } else {
                    $('#gateQueueLatest').text('No vehicles waiting.');
                  }
                });
                $.getJSON('php/fetch/incident_list.php', { source: 'all', limit: 1 }, function(res){
                  if (res.status !== 'success') return;
                  // Use the open count endpoint for accuracy.
                  $.getJSON('php/fetch/incident_open.php', function(c){
                    if (c.status === 'success') $('#openIncidentCount').text(c.count);
                  });
                  if (res.rows.length) {
                    var i = res.rows[0];
                    $('#incidentLatest').text(i.incident_type + ' — ' + (i.truck_plate || '-') + ' (' + i.severity + ')');
                  } else {
                    $('#incidentLatest').text('No incidents.');
                  }
                });
                $.getJSON('php/fetch/pending_verifications.php', function(res){
                  if (res.status !== 'success') return;
                  $('#pendingVerifyCount').text(res.rows.length);
                });
                $.getJSON('php/fetch/dispatchable_drivers.php', function(res){
                  if (res.status !== 'success') return;
                  $('#dispatchableCount').text(res.count);
                });
                $.getJSON('php/fetch/maintenance_active.php', function(res){
                  if (res.status !== 'success') return;
                  $('#blockedUnitsCount').text(res.summary.blocked || 0);
                  if (res.rows && res.rows.length) {
                    var r = res.rows[0];
                    $('#blockedUnitsLatest').text(r.unit_code + ' (' + r.unit_kind + ') — ' + r.category);
                  } else {
                    $('#blockedUnitsLatest').text('All units available.');
                  }
                });
              }
              $(pollPhase4);
              setInterval(pollPhase4, 15000);
            })();
            </script>

            <!--  -->
            <div class="row">
              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-info rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-users fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Driver</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="dailyCount1" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-user fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Present Driver</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount_others" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-success rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-route fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Dispatch</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="avgCount1" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-secondary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-route-off fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">N-Dispatch</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount_ndispatch" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-warning rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-user fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">VL/SL</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount_vlsl" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-danger rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-user fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Absent</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount_restday" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--  Row 1 -->
            <div class="row">
              <div class="col-lg-9">
                <div class="card w-100">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-3">
                        <div class="d-md-flex align-items-center">
                        <div>
                          <h4 class="card-title">Dispatch Overview</h4>
                          <p class="card-subtitle">
                            Dispatch by Driver
                          </p>
                        </div>
                    </div>
                      </div>
                      <div class="col-md-9 text-end">
                        <div class="btn-group" role="group" aria-label="Basic example">
                          <a class="btn btn-outline-primary" href="dispatch-dashboard-attendance">Driver Attendance</a>
                          <a class="btn btn-outline-primary" href="dispatch-dashboard-truck">Truck Movement</a>
                          <a href="dispatch-dashboard-trailer" class="btn btn-outline-primary">Trailer Movement</a>
                        </div>
                      </div>
                    </div>
                    <div class="row mt-3">
                      <div class="col-md-4">
                        <label for="">Filter Segment</label>
                        <div class="dropdown1">
                          <div class="dropdown1-btn">Select Hauling Segments</div>
                          <div class="dropdown1-content" id="hauling-list"></div>
                        </div>
                        <div class="selected-values" id="selectedHauling" hidden>Selected: None</div>
                      </div>

                      <div class="col-md-3">
                        <label for="">Status</label>
                        <div class="dropdown1">
                          <div class="dropdown1-btn">Select Dispatch Status</div>
                          <div class="dropdown1-content" id="dispatch-list"></div>
                        </div>
                        <div class="selected-values" id="selectedDispatch" hidden>Selected: None</div>
                      </div>

                      <div class="col-md-3">
                        <label for="">Unit</label>
                        <div class="dropdown1">
                          <div class="dropdown1-btn">Select Units</div>
                          <div class="dropdown1-content" id="unit-list"></div>
                        </div>
                        <div class="selected-values" id="selectedUnits" hidden>Selected: None</div>
                      </div>
                    </div>
                     <div class="table-responsive mt-4" style="overflow: hidden;">
                      <table class="table table-bordered table-striped mb-0 text-nowrap varient-table align-middle fs-3" id="table-data">
                        <thead>
                          <tr>
                            <th>No</th>
                            <th>Driver's Name</th>
                            <th>Assign Unit</th>
                            <th>Dispatched Unit</th>
                            <th>Unit Status</th>
                            <th>Driver Status</th>
                            <th>Dispatch Date</th>
                            <th>Dispatch By</th>
                            <th>Segment</th>
                          </tr>
                        </thead>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <?php
                include "php/config/config.php";

                // Count trailers based on status
                $query = "SELECT unit_status, COUNT(*) as count 
                          FROM units 
                          WHERE unit_name NOT LIKE 'GS%' 
                          GROUP BY unit_status";
                $result = mysqli_query($conn, $query);

                // Initialize counts
                $counts = [
                  'good' => 0,
                  'dispatch' => 0,
                  'shop unit' => 0,
                  'rescue' => 0
                ];

                $totalCount = 0; // ✅ total for all

                while ($row = mysqli_fetch_assoc($result)) {
                    $status = strtolower($row['unit_status']);
                    $count = (int)$row['count'];

                    $totalCount += $count; // ✅ add to total

                    if (isset($counts[$status])) {
                        $counts[$status] = $count;
                    }
                }
                ?>

              <div class="col-lg-3">
                <div class="col-md-12">
                  <div class="card overflow-hidden shadow-sm rounded-3">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center mb-3">
                        <div class="me-2">
                          <i class="ti ti-truck text-primary" style="font-size: 28px;"></i>
                        </div>
                        <div>
                          <h4 class="card-title mb-0">Truck Utilization</h4>
                          <small class="text-muted">Current usage overview</small>
                        </div>
                      </div>

                       <?php
                        // --- PHP LOGIC TO GET TRUCK UTILIZATION DATA BASED ON TRIPS ---

                        // Get total number of trucks
                        $totalTrucksQuery = $conn->query("SELECT COUNT(*) AS total FROM units WHERE unit_name NOT LIKE 'GS%' AND unit_status != 'Disposed'");
                        $totalTrucks = $totalTrucksQuery->fetch_assoc()['total'];

                        // Get count of trucks that are utilized (have at least one trip)
                        $utilizedQuery1 = $conn->query("
                            SELECT COUNT(DISTINCT d.d_truck) AS utilized
                            FROM dispatch d
                            INNER JOIN trips t ON d.d_id = t.d_id
                            WHERE d.d_truck IS NOT NULL 
                              AND d.d_truck != ''
                              AND t.trip_status IS NOT NULL
                        ");
                        $utilized1 = $utilizedQuery1->fetch_assoc()['utilized'] ?? 0;

                        // Compute unutilized
                        $unutilized1 = $totalTrucks - $utilized1;

                        // Compute percentages
                        $utilizedPercent1 = ($totalTrucks > 0) ? round(($utilized1 / $totalTrucks) * 100, 1) : 0;
                        $unutilizedPercent1 = ($totalTrucks > 0) ? round(($unutilized1 / $totalTrucks) * 100, 1) : 0;
                        ?>

                      <div class="row text-center">
                        <!-- Utilized -->
                        <div class="col-md-6 border-end">
                          <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="ti ti-check text-success" style="font-size: 22px;"></i>
                            <span class="ms-2 fw-bold text-success"><?= $utilizedPercent1 ?>%</span>
                          </div>
                          <p class="mb-1 fw-semibold">Utilized Trucks</p>
                          <small class="text-muted"><?= $utilized1 ?> of <?= $totalTrucks ?> units</small>
                          <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?= $utilizedPercent1 ?>%;"></div>
                          </div>
                        </div>

                        <!-- Unutilized -->
                        <div class="col-md-6">
                          <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="ti ti-truck-off text-danger" style="font-size: 22px;"></i>
                            <span class="ms-2 fw-bold text-danger"><?= $unutilizedPercent1 ?>%</span>
                          </div>
                          <p class="mb-1 fw-semibold">Unutilized Trucks</p>
                          <small class="text-muted"><?= $unutilized1 ?> of <?= $totalTrucks ?> units</small>
                          <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: <?= $unutilizedPercent1 ?>%;"></div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="card overflow-hidden shadow-sm rounded-3">
                    <div class="card-body pb-3">
                      <div class="d-flex align-items-center mb-3">
                        <div class="me-2">
                          <i class="ti ti-truck text-primary" style="font-size: 28px;"></i>
                        </div>
                        <div>
                          <h4 class="card-title mb-0">Trailer Utilization</h4>
                          <small class="text-muted">Current usage overview</small>
                        </div>
                      </div>

                      <?php
                        // --- PHP LOGIC TO GET UTILIZATION DATA ---
                        $totalTrailersQuery = $conn->query("SELECT COUNT(*) AS total FROM trailer");
                        $totalTrailers = $totalTrailersQuery->fetch_assoc()['total'];

                        $utilizedQuery = $conn->query("
                            SELECT COUNT(DISTINCT tm_trailerName) AS utilized
                            FROM trailer_movement
                        ");
                        $utilized = $utilizedQuery->fetch_assoc()['utilized'];

                        $unutilized = $totalTrailers - $utilized;

                        $utilizedPercent = ($totalTrailers > 0) ? round(($utilized / $totalTrailers) * 100, 1) : 0;
                        $unutilizedPercent = ($totalTrailers > 0) ? round(($unutilized / $totalTrailers) * 100, 1) : 0;
                      ?>

                      <div class="row text-center">
                        <!-- Utilized -->
                        <div class="col-md-6 border-end">
                          <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="ti ti-check text-success" style="font-size: 22px;"></i>
                            <span class="ms-2 fw-bold text-success"><?= $utilizedPercent ?>%</span>
                          </div>
                          <p class="mb-1 fw-semibold">Utilized Trailers</p>
                          <small class="text-muted"><?= $utilized ?> of <?= $totalTrailers ?> units</small>
                          <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?= $utilizedPercent ?>%;"></div>
                          </div>
                        </div>

                        <!-- Unutilized -->
                        <div class="col-md-6">
                          <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="ti ti-truck-off text-danger" style="font-size: 22px;"></i>
                            <span class="ms-2 fw-bold text-danger"><?= $unutilizedPercent ?>%</span>
                          </div>
                          <p class="mb-1 fw-semibold">Unutilized Trailers</p>
                          <small class="text-muted"><?= $unutilized ?> of <?= $totalTrailers ?> units</small>
                          <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: <?= $unutilizedPercent ?>%;"></div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="card overflow-hidden">
                    <div class="card-body pb-0">
                      <div class="d-flex align-items-start">
                        <div>
                          <h4 class="card-title">Truck Stats</h4>
                          <p class="card-subtitle">Number of truck by Status</p>
                        </div>
                      </div>
                      <div class="mt-4 pb-3 d-flex align-items-center">
                        <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                          <i class="ti ti-truck fs-6"></i>
                        </span>
                        <div class="ms-3">
                          <h5 class="mb-0 fw-bolder fs-4">Total Units</h5>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-secondary-subtle text-black"><?= $totalCount; ?></span>
                        </div>
                      </div>
                      <div class="mt-4 pb-3 d-flex align-items-center">
                        <span class="btn btn-info rounded-circle round-48 hstack justify-content-center">
                          <i class="ti ti-truck fs-6"></i>
                        </span>
                        <div class="ms-3">
                          <h5 class="mb-0 fw-bolder fs-4">Not Dispatch Unit</h5>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-secondary-subtle text-black"><?= $counts['good'] ?></span>
                        </div>
                      </div>
                      <div class="py-3 d-flex align-items-center">
                        <span class="btn btn-success rounded-circle round-48 hstack justify-content-center">
                          <i class="ti ti-truck-delivery fs-6"></i>
                        </span>
                        <div class="ms-3">
                          <h5 class="mb-0 fw-bolder fs-4">Dispatch Unit</h5>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-secondary-subtle text-black"><?= $counts['dispatch'] ?></span>
                        </div>
                      </div>
                      <div class="py-3 d-flex align-items-center">
                        <span class="btn btn-warning rounded-circle round-48 hstack justify-content-center">
                          <i class="ti ti-car-crane fs-6"></i>
                        </span>
                        <div class="ms-3">
                          <h5 class="mb-0 fw-bolder fs-4">Rescue Units</h5>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-secondary-subtle text-black"><?= $counts['rescue'] ?></span>
                        </div>
                      </div>
                      <div class="pt-3 mb-7 d-flex align-items-center">
                        <span class="btn btn-danger rounded-circle round-48 hstack justify-content-center">
                          <i class="ti ti-truck-off fs-6"></i>
                        </span>
                        <div class="ms-3">
                          <h5 class="mb-0 fw-bolder fs-4">Shop Units</h5>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-secondary-subtle text-black"><?= $counts['shop unit'] ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>
              
            </div>
            <div class="py-6 px-6 text-center">
              <p class="mb-0 fs-4">Design and Developed by JA Baliguat | 2025</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebarmenu.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <script src="datatable/datatables.min.js"></script>
    <script src="js/dashboard.js"></script>
    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script>
      
      
      function loadDriverCounts() {
        fetch('table-fetch/getDriverCount.php')
          .then(response => response.json())
          .then(data => {
            document.getElementById('dailyCount1').innerText = data.totalDrivers;
            document.getElementById('avgCount1').innerText = data.dispatch;
            document.getElementById('totalCount_ndispatch').innerText = data.ndispatch;
            document.getElementById('totalCount_vlsl').innerText = data.vlsl;
            document.getElementById('totalCount_others').innerText = data.others;
            document.getElementById('totalCount_restday').innerText = data.restDay;
          });
      }

      // Load first time
      loadDriverCounts();

      // Refresh every 5 seconds
      setInterval(loadDriverCounts, 5000);

      
      


       // --- Dropdown setup ---
      function setupDropdown(btnClass, listId, selectedId, data, key="value") {
      let container = document.getElementById(listId);
      container.innerHTML = '';

      // --- Search input ---
      let searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.placeholder = 'Search...';
      searchInput.style.width = '90%';
      searchInput.style.margin = '5px';
      searchInput.style.padding = '5px';
      container.appendChild(searchInput);

      // --- Select All ---
      let selectAll = document.createElement('label');
      selectAll.innerHTML = '<input type="checkbox" id="selectAll-' + listId + '"> Select All';
      container.appendChild(selectAll);

      // --- Options ---
      data.forEach(item => {
        let value = key === "value" ? item : item[key];
        let label = document.createElement('label');
        label.innerHTML = `<input type="checkbox" class="option-${listId}" value="${value}"> ${value}`;
        container.appendChild(label);
      });

      // --- Toggle dropdown ---
      document.querySelectorAll("." + btnClass).forEach(btn => {
        btn.addEventListener('click', function() {
          this.parentElement.classList.toggle('show');
        });
      });

      // --- Select All handler ---
      document.getElementById("selectAll-" + listId).addEventListener('change', function() {
        document.querySelectorAll('.option-' + listId).forEach(opt => opt.checked = this.checked);
        updateSelected(listId, selectedId);
        $('#table-data').DataTable().ajax.reload();
      });

      // --- Option handler ---
      document.querySelectorAll('.option-' + listId).forEach(opt => {
        opt.addEventListener('change', function() {
          updateSelected(listId, selectedId);
          $('#table-data').DataTable().ajax.reload();
        });
      });

      // --- Search filter ---
      searchInput.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        container.querySelectorAll('label').forEach(label => {
          // skip the search input itself
          if (label === searchInput.parentNode) return;
          let text = label.textContent.toLowerCase();
          label.style.display = text.includes(filter) ? '' : 'none';
        });
      });
    }

      function updateSelected(listId, selectedId) {
        let selected = [];
        document.querySelectorAll('.option-' + listId + ':checked').forEach(opt => {
          selected.push(opt.value);
        });
        document.getElementById(selectedId).innerText = selected.length ? "Selected: " + selected.join(', ') : "Selected: None";

        // Update Select All checkbox
        let allOpts = document.querySelectorAll('.option-' + listId).length;
        let checkedOpts = document.querySelectorAll('.option-' + listId + ':checked').length;
        document.getElementById("selectAll-" + listId).checked = allOpts && (allOpts === checkedOpts);
      }

      function getSelected(listId) {
        let selected = [];
        document.querySelectorAll('.option-' + listId + ':checked').forEach(opt => {
          selected.push(opt.value);
        });
        return selected;
      }

      // --- DataTable ---
      $(document).ready(function () {
        let table = $('#table-data').DataTable({
          "ajax": {
            "url": "table-fetch/drivers.php",
            "type": "POST",
            "data": function (d) {
              d.segmentFilter = getSelected('hauling-list');
              d.statusFilter  = getSelected('dispatch-list');
              d.unitFilter    = getSelected('unit-list');
            },
            "dataSrc": "data"
          },
          "pageLength": 10,
          "createdRow": function (row, data) {
            let driverStatus   = data[5]; // driver_status col
            let assignUnit     = data[2]; // assigned unit col
            let dispatchedUnit = data[3]; // dispatched unit col

            let $cells = $('td', row);

            // ✅ Clear previous styling
            $cells.removeClass("status-restday status-leave status-dispatch mismatch-unit");

            // ✅ Apply status-based color
            if (driverStatus) {
              let status = driverStatus.toLowerCase();
              if (status === "rest day") $cells.addClass("status-restday");
              else if (status === "vl" || status === "sl") $cells.addClass("status-leave");
              else if (status === "dispatch") $cells.addClass("status-dispatch");
            }

            // ✅ Highlight assigned unit cell if mismatch
            if (assignUnit !== '-' && dispatchedUnit !== '-' && assignUnit !== dispatchedUnit) {
              $('td:eq(2)', row).addClass('mismatch-unit');
            }
          }
        });

        // Auto refresh every 5 sec
        setInterval(function () {
          table.ajax.reload(null, false);
        }, 5000);
      });

      // --- Load dropdown data ---
      fetch('php/fetch/get_hauling.php')
        .then(res => res.json())
        .then(data => {
          setupDropdown("dropdown1-btn", "hauling-list", "selectedHauling", data, "hauling_segment");
        });

      setupDropdown("dropdown1-btn", "dispatch-list", "selectedDispatch", ["Dispatch", "Not Dispatch"], "value");

      fetch('php/fetch/get_units.php')
        .then(res => res.json())
        .then(data => {
          setupDropdown("dropdown1-btn", "unit-list", "selectedUnits", data, "unit_name");
        });
    </script>
    <?php include __DIR__ . '/../php/assets/realtime_alerts.php'; ?>
  </body>

  </html>
<?php
} else {
  header("Location: dispatcher-index.php?route=login");
  exit();
} ?>
