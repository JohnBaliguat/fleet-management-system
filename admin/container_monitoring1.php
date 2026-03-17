<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Admin") {


?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Container Monitoring</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    <link rel="stylesheet" href="assets/css/monitoring.css" />
    <link rel="stylesheet" href="assets/css/enhancements.css" />
    <link rel="stylesheet" href="datatable/datatables.min.css">
    <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
    
  </head>

  <body>
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

      <!-- App Topstrip -->
      <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center justify-content-center gap-5 mb-2 mb-lg-0">
          <a class="d-flex justify-content-center" href="#">
            <img src="assets/images/logos/pantrucks.png" alt="" width="122">
          </a>
        </div>
        <div class="d-lg-flex align-items-center gap-2">
          <h3 class="text-white mb-2 mb-lg-0 fs-5 text-center">Reefer Container Monitoring</h3>
          <div class="d-flex align-items-center justify-content-center gap-2">
            <span class="badge bg-success">Live</span>
          </div>
        </div>
      </div>

      <!-- Sidebar Start -->
      <?php include 'sidebar.php'; ?>
      <!-- Sidebar End -->

      <!-- Main wrapper -->
      <div class="body-wrapper">
        <!-- Header Start -->
        <?php include 'navbar.php';
        $id = $_SESSION['user_id'];
        $query = "SELECT * FROM user WHERE user_id = '$id'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        $fname = $data['user_fname'];
        $firstLetter = substr($data['user_lname'], 0, 1);
        ?>
        <!-- Header End -->

        <div class="body-wrapper-inner monitoring-dashboard">
          <div class="container-fluid">

            <!-- Stats Overview -->
            <div class="stats-grid">
              <div class="stat-card active">
                <div class="stat-value" id="active-count">8</div>
                <div class="stat-label">Active Hauling</div>
              </div>
              <div class="stat-card warning">
                <div class="stat-value" id="pending-count">3</div>
                <div class="stat-label">Pending Arrival</div>
              </div>
              <div class="stat-card danger">
                <div class="stat-value" id="delayed-count">1</div>
                <div class="stat-label">Delayed / Issues</div>
              </div>
              <div class="stat-card">
                <div class="stat-value" id="completed-count">12</div>
                <div class="stat-label">Completed Today</div>
              </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
              <div class="filter-group">
                <label>Status:</label>
                <button class="filter-btn active" onclick="filterContainers('all')">All</button>
                <button class="filter-btn" onclick="filterContainers('in-transit')">In Transit</button>
                <button class="filter-btn" onclick="filterContainers('at-cy')">At CY</button>
                <button class="filter-btn" onclick="filterContainers('completed')">Completed</button>
              </div>
              <div class="filter-group">
                <label>Shipping Line:</label>
                <select class="form-select form-select-sm" style="width: auto;" onchange="filterByLine(this.value)">
                  <option value="">All Lines</option>
                  <option value="COSCO">COSCO</option>
                  <option value="DOLE">DOLE</option>
                  <option value="MAERSK">MAERSK</option>
                  <option value="SITC">SITC</option>
                </select>
              </div>
              <div class="filter-group ms-auto">
                <input type="search" class="form-control form-control-sm" placeholder="Search van number..." style="width: 200px;">
              </div>
            </div>

            <!-- Containers Grid -->
            <div class="containers-grid" id="containers-grid">

              <!-- Card 1: Active In Transit -->
              <div class="container-card" data-status="in-transit" data-line="COSCO">
                <div class="priority-flag priority-medium"></div>
                <div class="card-header-custom">
                  <div class="van-info">
                    <h5>TTNU 8642584</h5>
                    <div class="van-meta">PS01 • COSCO-GF</div>
                  </div>
                  <span class="status-badge">In Transit</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 66%;"></div>
                    <div class="timeline-node completed" title="CY Arrival">
                      <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed" title="CY Departure">
                      <iconify-icon icon="solar:truck-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node active" title="PH Arrival">
                      <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending" title="PTSI Departure">
                      <iconify-icon icon="solar:ship-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span>CY Arrival<br><small>3/8 14:51</small></span>
                    <span>CY Departure<br><small>3/8 17:20</small></span>
                    <span style="color: var(--status-info); font-weight: 600;">PH Arrival<br><small>3/8 --:--</small></span>
                    <span>PTSI<br><small>Pending</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value highlight">PM620</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221479</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">D30 / --</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">D. Granaderos</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar">DG</div>
                  <div class="driver-info">
                    <div class="driver-name">Diana, Norbert Granaderos</div>
                    <div class="driver-contact">TR#6 • Last update: 5 min ago</div>
                  </div>
                  <button class="action-btn" onclick="contactDriver('PM620')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>ETD</span>
                        <strong>3/8/26 03:02</strong>
                      </div>
                      <div class="detail-row">
                        <span>PH Arrival Date</span>
                        <strong>3/8/26</strong>
                      </div>
                      <div class="detail-row">
                        <span>Seal Number</span>
                        <strong>--</strong>
                      </div>
                      <div class="detail-row">
                        <span>Vessel ETDate</span>
                        <strong>--</strong>
                      </div>
                      <div class="detail-row">
                        <span>Week</span>
                        <strong>--</strong>
                      </div>
                      <div class="detail-row">
                        <span>Customer</span>
                        <strong>--</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 2: Critical Delayed -->
              <div class="container-card critical" data-status="delayed" data-line="DOLE">
                <div class="priority-flag priority-high"></div>
                <div class="card-header-custom" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                  <div class="van-info">
                    <h5>GMOU 8870744</h5>
                    <div class="van-meta">PS01 • DOLE</div>
                  </div>
                  <span class="status-badge">Delayed</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 25%; background: var(--status-danger);"></div>
                    <div class="timeline-node completed" style="background: var(--status-danger); border-color: var(--status-danger);">
                      <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node active" style="background: var(--status-danger); border-color: var(--status-danger); box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.2);">
                      <iconify-icon icon="solar:danger-circle-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:ship-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span>CY Arrival<br><small>3/8 14:51</small></span>
                    <span style="color: var(--status-danger); font-weight: 600;">Issue at CY<br><small>3/8 17:20</small></span>
                    <span>PH Arrival<br><small>Pending</small></span>
                    <span>PTSI<br><small>--</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value highlight" style="color: var(--status-danger);">PM842</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221601</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">TR670 / --</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">A. Marces</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">AM</div>
                  <div class="driver-info">
                    <div class="driver-name">Alcala, Alan Marces</div>
                    <div class="driver-contact" style="color: var(--status-danger); font-weight: 600;">⚠️ Delayed +2hrs</div>
                  </div>
                  <button class="action-btn" onclick="contactDriver('PM842')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="alert alert-danger" role="alert" style="font-size: 0.875rem;">
                      <strong>Issue:</strong> Customs documentation delay at CY departure
                    </div>
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>ETD</span>
                        <strong>3/8/26 14:51</strong>
                      </div>
                      <div class="detail-row">
                        <span>CY Departure</span>
                        <strong>3/8/26 17:20</strong>
                      </div>
                      <div class="detail-row">
                        <span>Expected PH Arrival</span>
                        <strong>3/8/26 19:30</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 3: At CY -->
              <div class="container-card" data-status="at-cy" data-line="COSCO">
                <div class="priority-flag priority-low"></div>
                <div class="card-header-custom" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                  <div class="van-info">
                    <h5>SZLU 9680663</h5>
                    <div class="van-meta">PS01 • COSCO-GF</div>
                  </div>
                  <span class="status-badge">At CY</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 25%; background: var(--status-warning);"></div>
                    <div class="timeline-node active" style="background: var(--status-warning); border-color: var(--status-warning); box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.2);">
                      <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:truck-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:ship-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span style="color: var(--status-warning); font-weight: 600;">CY Arrival<br><small>3/8 16:40</small></span>
                    <span>CY Departure<br><small>3/8 17:30</small></span>
                    <span>PH Arrival<br><small>Pending</small></span>
                    <span>PTSI<br><small>--</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value highlight" style="color: var(--status-warning);">PM651</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221499</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">D31 / GS720</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">N. Songahid</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">NS</div>
                  <div class="driver-info">
                    <div class="driver-name">Boya, Noel Songahid</div>
                    <div class="driver-contact">Waiting for loading</div>
                  </div>
                  <button class="action-btn" onclick="contactDriver('PM651')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>ETD</span>
                        <strong>3/8/26 16:40</strong>
                      </div>
                      <div class="detail-row">
                        <span>CY Departure Scheduled</span>
                        <strong>3/8/26 17:30</strong>
                      </div>
                      <div class="detail-row">
                        <span>Seal Number</span>
                        <strong>GS720</strong>
                      </div>
                      <div class="detail-row">
                        <span>Cargo Status</span>
                        <strong>Loading in progress</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 4: Completed -->
              <div class="container-card" data-status="completed" data-line="SITC">
                <div class="priority-flag priority-low"></div>
                <div class="card-header-custom" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                  <div class="van-info">
                    <h5>TLLU 1135943</h5>
                    <div class="van-meta">PS01 • SITC</div>
                  </div>
                  <span class="status-badge">Completed</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 100%;"></div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span>CY Arrival<br><small>3/9 16:20</small></span>
                    <span>CY Departure<br><small>3/9 22:35</small></span>
                    <span>PH Arrival<br><small>3/10 07:19</small></span>
                    <span style="color: var(--status-active); font-weight: 600;">PTSI<br><small>3/11 09:00</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value">PM651</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221611</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">TR136 / GS655</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">N. Songahid</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">NS</div>
                  <div class="driver-info">
                    <div class="driver-name">Boya, Noel Songahid</div>
                    <div class="driver-contact" style="color: var(--status-active);">✓ Job completed</div>
                  </div>
                  <button class="action-btn" onclick="viewReport('PM651')" title="View Report">
                    <iconify-icon icon="solar:document-text-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="alert alert-success" role="alert" style="font-size: 0.875rem;">
                      <strong>Completed:</strong> Container delivered to PTSI on time
                    </div>
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>Total Transit Time</span>
                        <strong>16h 40m</strong>
                      </div>
                      <div class="detail-row">
                        <span>Departure at PTSI</span>
                        <strong>3/11/26 09:00</strong>
                      </div>
                      <div class="detail-row">
                        <span>Seal Status</span>
                        <strong>Intact (GS655)</strong>
                      </div>
                      <div class="detail-row">
                        <span>Consol Date</span>
                        <strong>3/11/26</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 5: Special Handling -->
              <div class="container-card warning" data-status="in-transit" data-line="SPECIAL">
                <div class="priority-flag priority-high"></div>
                <div class="card-header-custom" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                  <div class="van-info">
                    <h5>SEKU 9208935</h5>
                    <div class="van-meta">PS01 • SPECIAL</div>
                  </div>
                  <span class="status-badge">Special</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 50%;"></div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node active" style="background: #9b59b6; border-color: #9b59b6; box-shadow: 0 0 0 4px rgba(155, 89, 182, 0.2);">
                      <iconify-icon icon="solar:truck-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node pending">
                      <iconify-icon icon="solar:ship-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span>CY Arrival<br><small>3/9 03:10</small></span>
                    <span style="color: #9b59b6; font-weight: 600;">In Transit<br><small>En route</small></span>
                    <span>PH Arrival<br><small>Expected 06:00</small></span>
                    <span>PTSI<br><small>--</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value highlight" style="color: #9b59b6;">PM620</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221608</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">D20 / --</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">D. Granaderos</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">DG</div>
                  <div class="driver-info">
                    <div class="driver-name">Diana, Norbert Granaderos</div>
                    <div class="driver-contact">Special handling required</div>
                  </div>
                  <button class="action-btn" onclick="contactDriver('PM620')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="alert alert-info" role="alert" style="font-size: 0.875rem;">
                      <strong>Special Instructions:</strong> Temperature-sensitive cargo, maintain -18°C
                    </div>
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>Temperature</span>
                        <strong>-18.2°C ✓</strong>
                      </div>
                      <div class="detail-row">
                        <span>Humidity</span>
                        <strong>65% ✓</strong>
                      </div>
                      <div class="detail-row">
                        <span>Last Check</span>
                        <strong>10 min ago</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 6: Maersk In Transit -->
              <div class="container-card" data-status="in-transit" data-line="MAERSK">
                <div class="priority-flag priority-medium"></div>
                <div class="card-header-custom" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);">
                  <div class="van-info">
                    <h5>MNBU 3747668</h5>
                    <div class="van-meta">PS05 • MAERSK</div>
                  </div>
                  <span class="status-badge">In Transit</span>
                </div>

                <div class="timeline-section">
                  <div class="timeline">
                    <div class="timeline-progress" style="width: 75%;"></div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:truck-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node completed">
                      <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    </div>
                    <div class="timeline-node active" style="background: #1e40af; border-color: #1e40af;">
                      <iconify-icon icon="solar:ship-bold"></iconify-icon>
                    </div>
                  </div>
                  <div class="timeline-labels">
                    <span>CY Arrival<br><small>3/9 23:45</small></span>
                    <span>CY Departure<br><small>3/10 05:10</small></span>
                    <span>PH Arrival<br><small>3/10 06:00</small></span>
                    <span style="color: #1e40af; font-weight: 600;">PTSI<br><small>Arriving</small></span>
                  </div>
                </div>

                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">PM Number</span>
                    <span class="info-value highlight" style="color: #1e40af;">PM870</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Reference</span>
                    <span class="info-value">ECS: 221685</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">TR/GS</span>
                    <span class="info-value">-- / --</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Driver</span>
                    <span class="info-value">E. Purisima</span>
                  </div>
                </div>

                <div class="driver-section">
                  <div class="driver-avatar" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);">EP</div>
                  <div class="driver-info">
                    <div class="driver-name">Cabante, Edison Purisima</div>
                    <div class="driver-contact">Arriving at PTSI in 15 min</div>
                  </div>
                  <button class="action-btn" onclick="contactDriver('PM870')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>

                <div class="card-details">
                  <div class="details-content">
                    <div class="details-grid">
                      <div class="detail-row">
                        <span>Vessel ETDate</span>
                        <strong>3/11/26</strong>
                      </div>
                      <div class="detail-row">
                        <span>Week</span>
                        <strong>W10</strong>
                      </div>
                      <div class="detail-row">
                        <span>Customer</span>
                        <strong>MAERSK Line</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Empty State (Hidden by default) -->
            <div id="empty-state" class="text-center py-5" style="display: none;">
              <div class="mb-3">
                <iconify-icon icon="solar:box-minimalistic-bold" style="font-size: 4rem; color: #dee2e6;"></iconify-icon>
              </div>
              <h5 class="text-muted">No containers found</h5>
              <p class="text-muted">Try adjusting your filters</p>
            </div>

          </div>

          <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Design and Developed by JA Baliguat | 2025</p>
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
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

    <script>
      // Toggle card details
      function toggleDetails(btn) {
        const card = btn.closest('.container-card');
        const details = card.querySelector('.card-details');
        const icon = btn.querySelector('iconify-icon');

        details.classList.toggle('expanded');

        if (details.classList.contains('expanded')) {
          icon.setAttribute('icon', 'solar:alt-arrow-up-bold');
          btn.title = "Hide Details";
        } else {
          icon.setAttribute('icon', 'solar:alt-arrow-down-bold');
          btn.title = "View Details";
        }
      }

      // Filter containers by status
      function filterContainers(status) {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => {
          btn.classList.remove('active');
          if (btn.textContent.toLowerCase().includes(status) || (status === 'all' && btn.textContent === 'All')) {
            btn.classList.add('active');
          }
        });

        const cards = document.querySelectorAll('.container-card');
        let visibleCount = 0;

        cards.forEach(card => {
          if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        // Show empty state if no cards visible
        document.getElementById('empty-state').style.display = visibleCount === 0 ? 'block' : 'none';
      }

      // Filter by shipping line
      function filterByLine(line) {
        const cards = document.querySelectorAll('.container-card');
        let visibleCount = 0;

        cards.forEach(card => {
          if (!line || card.dataset.line === line) {
            card.style.display = 'block';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        document.getElementById('empty-state').style.display = visibleCount === 0 ? 'block' : 'none';
      }

      // Contact driver action
      function contactDriver(pmNumber) {
        Swal.fire({
          title: 'Contact Driver',
          text: `Initiating contact for PM ${pmNumber}`,
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Call',
          cancelButtonText: 'Message',
          showDenyButton: true,
          denyButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            Swal.fire('Calling...', `Connecting to driver for ${pmNumber}`, 'success');
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Message', `Opening messaging interface for ${pmNumber}`, 'info');
          }
        });
      }

      // View report for completed jobs
      function viewReport(pmNumber) {
        Swal.fire({
          title: 'Trip Report',
          html: `
          <div class="text-left">
            <p><strong>PM Number:</strong> ${pmNumber}</p>
            <p><strong>Status:</strong> Completed ✓</p>
            <p><strong>Delivery Time:</strong> On time</p>
            <p><strong>Condition:</strong> Good</p>
          </div>
        `,
          icon: 'success',
          confirmButtonText: 'Download PDF'
        });
      }

      // Search functionality
      document.querySelector('input[type="search"]').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.container-card');

        cards.forEach(card => {
          const vanNumber = card.querySelector('h5').textContent.toLowerCase();
          const driver = card.querySelector('.driver-name').textContent.toLowerCase();
          const pmNumber = card.querySelector('.info-value.highlight').textContent.toLowerCase();

          if (vanNumber.includes(searchTerm) || driver.includes(searchTerm) || pmNumber.includes(searchTerm)) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });

      // Auto-refresh simulation (every 30 seconds)
      setInterval(() => {
        // Simulate real-time updates
        document.querySelectorAll('.driver-contact').forEach(contact => {
          if (contact.textContent.includes('min ago')) {
            const mins = parseInt(contact.textContent.match(/\d+/)[0]);
            if (mins < 59) {
              contact.textContent = contact.textContent.replace(/\d+/, mins + 1);
            }
          }
        });
      }, 30000);

      // Keyboard shortcuts
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          // Close all expanded details
          document.querySelectorAll('.card-details.expanded').forEach(details => {
            details.classList.remove('expanded');
            const btn = details.closest('.container-card').querySelector('.action-btn[title="Hide Details"]');
            if (btn) {
              btn.querySelector('iconify-icon').setAttribute('icon', 'solar:alt-arrow-down-bold');
              btn.title = "View Details";
            }
          });
        }

        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
          e.preventDefault();
          document.querySelector('input[type="search"]').focus();
        }
      });

      // Initialize tooltips and popovers
      $(document).ready(function() {
        // Any initialization code
        console.log('Reefer Monitoring Dashboard Loaded');
      });
    </script>
  </body>

  </html>
<?php
} else {
  header("Location: index.php?route=login");
  exit();
}
