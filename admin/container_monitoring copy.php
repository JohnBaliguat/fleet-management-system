<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Admin") {


?>
  <!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reefer Container Monitoring</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="datatable/datatables.min.css">
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="assets/css/monitoring.css" />
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
            <div class="container-card" data-id="1" data-status="in-transit" data-line="COSCO">
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
                  <div class="timeline-node completed" data-stage="cy-arrival" title="CY Arrival">
                    <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node completed" data-stage="cy-departure" title="CY Departure">
                    <iconify-icon icon="solar:truck-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node active" data-stage="ph-arrival" title="PH Arrival">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="ptsi" title="PTSI Departure">
                    <iconify-icon icon="solar:ship-bold"></iconify-icon>
                  </div>
                </div>
                <div class="timeline-labels">
                  <span>CY Arrival<br><small class="cy-arrival-date">3/8 14:51</small></span>
                  <span>CY Departure<br><small class="cy-departure-date">3/8 17:20</small></span>
                  <span style="color: var(--status-info); font-weight: 600;">PH Arrival<br><small class="ph-arrival-date">3/8 --:--</small></span>
                  <span>PTSI<br><small class="ptsi-date">Pending</small></span>
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
                <div class="action-buttons">
                  <button class="action-btn edit" onclick="openEditModal(1)" title="Edit Details">
                    <iconify-icon icon="solar:pen-new-square-bold"></iconify-icon>
                  </button>
                  <button class="action-btn contact" onclick="contactDriver('PM620')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn details" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>
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
                      <strong class="ph-arrival-detail">3/8/26</strong>
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
                  <div class="remarks-box mt-3 p-2 bg-white rounded border" style="display: none;">
                    <small class="text-muted">Remarks:</small>
                    <p class="mb-0 remarks-text"></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2: Critical Delayed -->
            <div class="container-card critical" data-id="2" data-status="delayed" data-line="DOLE">
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
                  <div class="timeline-node completed" data-stage="cy-arrival" style="background: var(--status-danger); border-color: var(--status-danger);">
                    <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node active" data-stage="cy-departure" style="background: var(--status-danger); border-color: var(--status-danger); box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.2);">
                    <iconify-icon icon="solar:danger-circle-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="ph-arrival">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="ptsi">
                    <iconify-icon icon="solar:ship-bold"></iconify-icon>
                  </div>
                </div>
                <div class="timeline-labels">
                  <span>CY Arrival<br><small class="cy-arrival-date">3/8 14:51</small></span>
                  <span style="color: var(--status-danger); font-weight: 600;">Issue at CY<br><small class="cy-departure-date">3/8 17:20</small></span>
                  <span>PH Arrival<br><small class="ph-arrival-date">Pending</small></span>
                  <span>PTSI<br><small class="ptsi-date">--</small></span>
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
                <div class="action-buttons">
                  <button class="action-btn edit" onclick="openEditModal(2)" title="Edit Details">
                    <iconify-icon icon="solar:pen-new-square-bold"></iconify-icon>
                  </button>
                  <button class="action-btn contact" onclick="contactDriver('PM842')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn details" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>
              </div>

              <div class="card-details">
                <div class="details-content">
                  <div class="alert alert-danger" role="alert" style="font-size: 0.875rem;">
                    <strong>Issue:</strong> <span class="remarks-display">Customs documentation delay at CY departure</span>
                  </div>
                  <div class="details-grid">
                    <div class="detail-row">
                      <span>ETD</span>
                      <strong>3/8/26 14:51</strong>
                    </div>
                    <div class="detail-row">
                      <span>CY Departure</span>
                      <strong class="cy-departure-detail">3/8/26 17:20</strong>
                    </div>
                    <div class="detail-row">
                      <span>Expected PH Arrival</span>
                      <strong class="ph-arrival-detail">3/8/26 19:30</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 3: At CY -->
            <div class="container-card" data-id="3" data-status="at-cy" data-line="COSCO">
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
                  <div class="timeline-node active" data-stage="cy-arrival" style="background: var(--status-warning); border-color: var(--status-warning); box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.2);">
                    <iconify-icon icon="solar:warehouse-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="cy-departure">
                    <iconify-icon icon="solar:truck-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="ph-arrival">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node pending" data-stage="ptsi">
                    <iconify-icon icon="solar:ship-bold"></iconify-icon>
                  </div>
                </div>
                <div class="timeline-labels">
                  <span style="color: var(--status-warning); font-weight: 600;">CY Arrival<br><small class="cy-arrival-date">3/8 16:40</small></span>
                  <span>CY Departure<br><small class="cy-departure-date">3/8 17:30</small></span>
                  <span>PH Arrival<br><small class="ph-arrival-date">Pending</small></span>
                  <span>PTSI<br><small class="ptsi-date">--</small></span>
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
                <div class="action-buttons">
                  <button class="action-btn edit" onclick="openEditModal(3)" title="Edit Details">
                    <iconify-icon icon="solar:pen-new-square-bold"></iconify-icon>
                  </button>
                  <button class="action-btn contact" onclick="contactDriver('PM651')" title="Contact Driver">
                    <iconify-icon icon="solar:phone-bold"></iconify-icon>
                  </button>
                  <button class="action-btn details" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>
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
                      <strong class="cy-departure-detail">3/8/26 17:30</strong>
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
            <div class="container-card" data-id="4" data-status="completed" data-line="SITC">
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
                  <div class="timeline-node completed" data-stage="cy-arrival">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node completed" data-stage="cy-departure">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node completed" data-stage="ph-arrival">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                  </div>
                  <div class="timeline-node completed" data-stage="ptsi">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                  </div>
                </div>
                <div class="timeline-labels">
                  <span>CY Arrival<br><small class="cy-arrival-date">3/9 16:20</small></span>
                  <span>CY Departure<br><small class="cy-departure-date">3/9 22:35</small></span>
                  <span>PH Arrival<br><small class="ph-arrival-date">3/10 07:19</small></span>
                  <span style="color: var(--status-active); font-weight: 600;">PTSI<br><small class="ptsi-date">3/11 09:00</small></span>
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
                <div class="action-buttons">
                  <button class="action-btn edit" onclick="openEditModal(4)" title="Edit Details">
                    <iconify-icon icon="solar:pen-new-square-bold"></iconify-icon>
                  </button>
                  <button class="action-btn contact" onclick="viewReport('PM651')" title="View Report">
                    <iconify-icon icon="solar:document-text-bold"></iconify-icon>
                  </button>
                  <button class="action-btn details" onclick="toggleDetails(this)" title="View Details">
                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                  </button>
                </div>
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
                      <strong class="ptsi-detail">3/11/26 09:00</strong>
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

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Update Container Status</h5>
            <small class="text-white-50" id="modalVanInfo">Van: TTNU 8642584 • PM: PM620</small>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="updateForm">
            <input type="hidden" id="editContainerId">
            
            <!-- Status Selection -->
            <div class="mb-3">
              <label class="form-label fw-bold">Current Status</label>
              <div class="status-selector">
                <div class="status-option in-transit" onclick="selectStatus('in-transit')">
                  <div class="status-icon">🚛</div>
                  <div class="status-label">In Transit</div>
                </div>
                <div class="status-option at-cy" onclick="selectStatus('at-cy')">
                  <div class="status-icon">🏭</div>
                  <div class="status-label">At CY</div>
                </div>
                <div class="status-option completed" onclick="selectStatus('completed')">
                  <div class="status-icon">✅</div>
                  <div class="status-label">Completed</div>
                </div>
                <div class="status-option delayed" onclick="selectStatus('delayed')">
                  <div class="status-icon">⚠️</div>
                  <div class="status-label">Delayed</div>
                </div>
                <div class="status-option special" onclick="selectStatus('special')">
                  <div class="status-icon">⭐</div>
                  <div class="status-label">Special</div>
                </div>
              </div>
              <input type="hidden" id="selectedStatus" required>
            </div>

            <!-- Timeline Editor -->
            <div class="timeline-editor">
              <h6><iconify-icon icon="solar:timeline-bold" class="me-2"></iconify-icon>Update Timeline Stages</h6>
              
              <div class="stage-item" data-stage="cy-arrival">
                <div class="stage-checkbox" onclick="toggleStage('cy-arrival')">
                  <iconify-icon icon="solar:check-bold" style="display: none;"></iconify-icon>
                </div>
                <div class="stage-info">
                  <div class="stage-name">CY Arrival</div>
                  <div class="stage-status">Container arrived at Container Yard</div>
                </div>
                <div class="stage-datetime">
                  <input type="date" id="cy-arrival-date" class="form-control form-control-sm">
                  <input type="time" id="cy-arrival-time" class="form-control form-control-sm">
                </div>
              </div>

              <div class="stage-item" data-stage="cy-departure">
                <div class="stage-checkbox" onclick="toggleStage('cy-departure')">
                  <iconify-icon icon="solar:check-bold" style="display: none;"></iconify-icon>
                </div>
                <div class="stage-info">
                  <div class="stage-name">CY Departure</div>
                  <div class="stage-status">Container departed from CY</div>
                </div>
                <div class="stage-datetime">
                  <input type="date" id="cy-departure-date" class="form-control form-control-sm">
                  <input type="time" id="cy-departure-time" class="form-control form-control-sm">
                </div>
              </div>

              <div class="stage-item" data-stage="ph-arrival">
                <div class="stage-checkbox" onclick="toggleStage('ph-arrival')">
                  <iconify-icon icon="solar:check-bold" style="display: none;"></iconify-icon>
                </div>
                <div class="stage-info">
                  <div class="stage-name">PH Arrival</div>
                  <div class="stage-status">Arrived at Port Handler</div>
                </div>
                <div class="stage-datetime">
                  <input type="date" id="ph-arrival-date" class="form-control form-control-sm">
                  <input type="time" id="ph-arrival-time" class="form-control form-control-sm">
                </div>
              </div>

              <div class="stage-item" data-stage="ptsi">
                <div class="stage-checkbox" onclick="toggleStage('ptsi')">
                  <iconify-icon icon="solar:check-bold" style="display: none;"></iconify-icon>
                </div>
                <div class="stage-info">
                  <div class="stage-name">PTSI Departure</div>
                  <div class="stage-status">Departed from PTSI</div>
                </div>
                <div class="stage-datetime">
                  <input type="date" id="ptsi-date" class="form-control form-control-sm">
                  <input type="time" id="ptsi-time" class="form-control form-control-sm">
                </div>
              </div>
            </div>

            <!-- Remarks -->
            <div class="remarks-section">
              <label class="form-label fw-bold">
                <iconify-icon icon="solar:chat-square-text-bold" class="me-2"></iconify-icon>
                Remarks / Special Instructions
              </label>
              <textarea id="remarks" placeholder="Enter any delays, issues, or special handling instructions..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-save" onclick="saveChanges()">
            <iconify-icon icon="solar:disk-bold" class="me-2"></iconify-icon>
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container"></div>

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
    // Store container data
    const containerData = {
      1: {
        van: 'TTNU 8642584',
        pm: 'PM620',
        line: 'COSCO-GF',
        ph: 'PS01',
        status: 'in-transit',
        stages: {
          'cy-arrival': { completed: true, date: '2026-03-08', time: '14:51' },
          'cy-departure': { completed: true, date: '2026-03-08', time: '17:20' },
          'ph-arrival': { completed: false, date: '', time: '' },
          'ptsi': { completed: false, date: '', time: '' }
        },
        remarks: ''
      },
      2: {
        van: 'GMOU 8870744',
        pm: 'PM842',
        line: 'DOLE',
        ph: 'PS01',
        status: 'delayed',
        stages: {
          'cy-arrival': { completed: true, date: '2026-03-08', time: '14:51' },
          'cy-departure': { completed: true, date: '2026-03-08', time: '17:20' },
          'ph-arrival': { completed: false, date: '', time: '' },
          'ptsi': { completed: false, date: '', time: '' }
        },
        remarks: 'Customs documentation delay at CY departure'
      },
      3: {
        van: 'SZLU 9680663',
        pm: 'PM651',
        line: 'COSCO-GF',
        ph: 'PS01',
        status: 'at-cy',
        stages: {
          'cy-arrival': { completed: true, date: '2026-03-08', time: '16:40' },
          'cy-departure': { completed: false, date: '2026-03-08', time: '17:30' },
          'ph-arrival': { completed: false, date: '', time: '' },
          'ptsi': { completed: false, date: '', time: '' }
        },
        remarks: ''
      },
      4: {
        van: 'TLLU 1135943',
        pm: 'PM651',
        line: 'SITC',
        ph: 'PS01',
        status: 'completed',
        stages: {
          'cy-arrival': { completed: true, date: '2026-03-09', time: '16:20' },
          'cy-departure': { completed: true, date: '2026-03-09', time: '22:35' },
          'ph-arrival': { completed: true, date: '2026-03-10', time: '07:19' },
          'ptsi': { completed: true, date: '2026-03-11', time: '09:00' }
        },
        remarks: ''
      }
    };

    let currentEditId = null;

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

    // Open Edit Modal
    function openEditModal(containerId) {
      currentEditId = containerId;
      const data = containerData[containerId];
      
      // Set header info
      document.getElementById('modalVanInfo').textContent = `Van: ${data.van} • PM: ${data.pm}`;
      document.getElementById('editContainerId').value = containerId;
      
      // Set status
      selectStatus(data.status);
      
      // Set stages
      Object.keys(data.stages).forEach(stage => {
        const stageData = data.stages[stage];
        const stageEl = document.querySelector(`.stage-item[data-stage="${stage}"]`);
        
        // Reset
        stageEl.classList.remove('completed', 'active');
        stageEl.querySelector('.stage-checkbox iconify-icon').style.display = 'none';
        
        if (stageData.completed) {
          stageEl.classList.add('completed');
          stageEl.querySelector('.stage-checkbox iconify-icon').style.display = 'block';
          document.getElementById(`${stage}-date`).value = stageData.date;
          document.getElementById(`${stage}-time`).value = stageData.time;
        } else {
          // Check if it's the next active stage
          const prevStage = getPreviousStage(stage);
          if (!prevStage || data.stages[prevStage].completed) {
            stageEl.classList.add('active');
          }
          document.getElementById(`${stage}-date`).value = stageData.date || '';
          document.getElementById(`${stage}-time`).value = stageData.time || '';
        }
      });
      
      // Set remarks
      document.getElementById('remarks').value = data.remarks || '';
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
    }

    function getPreviousStage(stage) {
      const stages = ['cy-arrival', 'cy-departure', 'ph-arrival', 'ptsi'];
      const idx = stages.indexOf(stage);
      return idx > 0 ? stages[idx - 1] : null;
    }

    // Select Status
    function selectStatus(status) {
      document.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('selected'));
      document.querySelector(`.status-option.${status}`).classList.add('selected');
      document.getElementById('selectedStatus').value = status;
    }

    // Toggle Stage Completion
    function toggleStage(stage) {
      const stageEl = document.querySelector(`.stage-item[data-stage="${stage}"]`);
      const isCompleted = stageEl.classList.contains('completed');
      const checkIcon = stageEl.querySelector('.stage-checkbox iconify-icon');
      
      if (isCompleted) {
        stageEl.classList.remove('completed');
        stageEl.classList.add('active');
        checkIcon.style.display = 'none';
        
        // Clear subsequent stages
        const stages = ['cy-arrival', 'cy-departure', 'ph-arrival', 'ptsi'];
        const currentIdx = stages.indexOf(stage);
        for (let i = currentIdx + 1; i < stages.length; i++) {
          const nextEl = document.querySelector(`.stage-item[data-stage="${stages[i]}"]`);
          nextEl.classList.remove('completed', 'active');
          nextEl.querySelector('.stage-checkbox iconify-icon').style.display = 'none';
          document.getElementById(`${stages[i]}-date`).value = '';
          document.getElementById(`${stages[i]}-time`).value = '';
        }
      } else {
        // Check if previous stage is completed
        const prevStage = getPreviousStage(stage);
        if (prevStage && !document.querySelector(`.stage-item[data-stage="${prevStage}"]`).classList.contains('completed')) {
          showToast('Please complete the previous stage first', 'warning');
          return;
        }
        
        stageEl.classList.remove('active');
        stageEl.classList.add('completed');
        checkIcon.style.display = 'block';
        
        // Set current time if empty
        const dateInput = document.getElementById(`${stage}-date`);
        const timeInput = document.getElementById(`${stage}-time`);
        if (!dateInput.value) {
          const now = new Date();
          dateInput.value = now.toISOString().split('T')[0];
          timeInput.value = now.toTimeString().slice(0, 5);
        }
        
        // Auto-activate next stage
        const stages = ['cy-arrival', 'cy-departure', 'ph-arrival', 'ptsi'];
        const currentIdx = stages.indexOf(stage);
        if (currentIdx < stages.length - 1) {
          const nextEl = document.querySelector(`.stage-item[data-stage="${stages[currentIdx + 1]}"]`);
          if (!nextEl.classList.contains('completed')) {
            nextEl.classList.add('active');
          }
        }
      }
    }

    // Save Changes
    function saveChanges() {
      const containerId = document.getElementById('editContainerId').value;
      const newStatus = document.getElementById('selectedStatus').value;
      const remarks = document.getElementById('remarks').value;
      
      // Update data object
      const data = containerData[containerId];
      data.status = newStatus;
      data.remarks = remarks;
      
      // Update stages
      document.querySelectorAll('.stage-item').forEach(stageEl => {
        const stage = stageEl.dataset.stage;
        const isCompleted = stageEl.classList.contains('completed');
        data.stages[stage] = {
          completed: isCompleted,
          date: document.getElementById(`${stage}-date`).value,
          time: document.getElementById(`${stage}-time`).value
        };
      });
      
      // Update UI
      updateCardUI(containerId);
      
      // Close modal
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
      
      // Show success message
      showToast('Container updated successfully!', 'success');
      
      // Log for backend integration
      console.log('Update payload:', {
        container_id: containerId,
        status: newStatus,
        stages: data.stages,
        remarks: remarks,
        updated_at: new Date().toISOString()
      });
    }

    // Update Card UI
    function updateCardUI(containerId) {
      const card = document.querySelector(`.container-card[data-id="${containerId}"]`);
      const data = containerData[containerId];
      
      // Update status badge and styling
      card.dataset.status = data.status;
      const header = card.querySelector('.card-header-custom');
      const badge = card.querySelector('.status-badge');
      badge.textContent = data.status.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
      
      // Update header color based on status
      const gradients = {
        'in-transit': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'at-cy': 'linear-gradient(135deg, #f39c12 0%, #e67e22 100%)',
        'completed': 'linear-gradient(135deg, #27ae60 0%, #229954 100%)',
        'delayed': 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)',
        'special': 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%)'
      };
      header.style.background = gradients[data.status];
      
      // Update driver avatar
      card.querySelector('.driver-avatar').style.background = gradients[data.status];
      
      // Update priority flag
      const flag = card.querySelector('.priority-flag');
      flag.className = 'priority-flag';
      if (data.status === 'delayed') flag.classList.add('priority-high');
      else if (data.status === 'special') flag.classList.add('priority-high');
      else if (data.status === 'at-cy') flag.classList.add('priority-medium');
      else flag.classList.add('priority-low');
      
      // Update timeline
      updateTimeline(card, data);
      
      // Update remarks
      if (data.remarks) {
        let remarksBox = card.querySelector('.remarks-box');
        if (!remarksBox) {
          remarksBox = document.createElement('div');
          remarksBox.className = 'remarks-box mt-3 p-2 bg-white rounded border';
          remarksBox.innerHTML = '<small class="text-muted">Remarks:</small><p class="mb-0 remarks-text"></p>';
          card.querySelector('.details-content').appendChild(remarksBox);
        }
        remarksBox.style.display = 'block';
        remarksBox.querySelector('.remarks-text').textContent = data.remarks;
        
        // Update alert if exists
        const alert = card.querySelector('.alert');
        if (alert && data.status === 'delayed') {
          alert.querySelector('.remarks-display').textContent = data.remarks;
        }
      }
      
      // Add update animation
      card.classList.add('updated');
      setTimeout(() => card.classList.remove('updated'), 1000);
    }

    function updateTimeline(card, data) {
      const stages = ['cy-arrival', 'cy-departure', 'ph-arrival', 'ptsi'];
      const stageNames = ['CY Arrival', 'CY Departure', 'PH Arrival', 'PTSI Departure'];
      let completedCount = 0;
      let activeFound = false;
      
      stages.forEach((stage, idx) => {
        const node = card.querySelector(`.timeline-node[data-stage="${stage}"]`);
        const stageData = data.stages[stage];
        const label = card.querySelector(`.timeline-labels span:nth-child(${idx + 1}) small`);
        
        // Reset classes
        node.className = 'timeline-node';
        
        if (stageData.completed) {
          node.classList.add('completed');
          completedCount++;
          if (label) {
            const timeStr = stageData.time ? `${parseInt(stageData.time.split(':')[0])}/${stageData.time}` : 'Done';
            label.textContent = timeStr;
            label.className = stage + '-date';
          }
        } else if (!activeFound) {
          node.classList.add('active');
          activeFound = true;
          if (label) {
            label.textContent = 'Pending';
            label.style.color = 'var(--status-info)';
            label.style.fontWeight = '600';
          }
        } else {
          node.classList.add('pending');
          if (label) {
            label.textContent = '--';
            label.style.color = '';
            label.style.fontWeight = '';
          }
        }
      });
      
      // Update progress bar
      const progress = card.querySelector('.timeline-progress');
      const percent = (completedCount / stages.length) * 100;
      progress.style.width = percent + '%';
      
      // Update progress color based on status
      if (data.status === 'delayed') progress.style.background = 'var(--status-danger)';
      else if (data.status === 'at-cy') progress.style.background = 'var(--status-warning)';
      else progress.style.background = 'var(--status-active)';
    }

    // Show Toast Notification
    function showToast(message, type = 'info') {
      const toastContainer = document.querySelector('.toast-container');
      const toastId = 'toast-' + Date.now();
      
      const colors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning text-dark',
        info: 'bg-info'
      };
      
      const toast = document.createElement('div');
      toast.className = `toast align-items-center text-white ${colors[type]} border-0`;
      toast.id = toastId;
      toast.setAttribute('role', 'alert');
      toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <iconify-icon icon="solar:${type === 'success' ? 'check-circle' : type === 'warning' ? 'danger-circle' : 'info-circle'}-bold" class="me-2"></iconify-icon>
            ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      `;
      
      toastContainer.appendChild(toast);
      const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
      bsToast.show();
      
      toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // Filter containers by status
    function filterContainers(status) {
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

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.card-details.expanded').forEach(details => {
          details.classList.remove('expanded');
          const btn = details.closest('.container-card').querySelector('.action-btn[title="Hide Details"]');
          if (btn) {
            btn.querySelector('iconify-icon').setAttribute('icon', 'solar:alt-arrow-down-bold');
            btn.title = "View Details";
          }
        });
      }
      
      if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.querySelector('input[type="search"]').focus();
      }
    });

    // Initialize
    $(document).ready(function() {
      console.log('Reefer Monitoring Dashboard Loaded with Edit Functionality');
    });
  </script>
</body>
</html>
<?php
} else {
  header("Location: index.php?route=login");
  exit();
}
