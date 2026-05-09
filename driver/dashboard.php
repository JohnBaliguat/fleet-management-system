<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Driver") {
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Dashboard - Driver</title>
  <link rel="manifest" href="manifest.webmanifest">
  <meta name="theme-color" content="#0d6efd">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="PT Driver">
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="apple-touch-icon" href="assets/images/logos/LogoFleet.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="assets/css/driver-modern.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>
<body>
  <div class="page-wrapper" id="main-wrapper">
    
    <!-- Mobile Header -->
    <div class="app-topstrip">
      <div class="d-flex align-items-center justify-content-between w-100">
        <!-- <img src="assets/images/logos/pantrucks.png" alt="Logo">
        <h3>Pantrucks Fleet</h3> -->
      </div>
    </div>

    <!-- Sidebar (Desktop) -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="body-wrapper">
      <!--  Header Start -->
        <?php include 'navbar.php'; ?>
      <div class="container-fluid">
        
        <!-- Stats Overview -->
        <div class="stats-grid mb-4 mt-5">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="ti ti-clock"></i>
            </div>
            <div class="stat-info">
              <h6>Available</h6>
              <span class="stat-value" id="booking">0</span>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon success">
              <i class="ti ti-check-circle"></i>
            </div>
            <div class="stat-info">
              <h6>Completed</h6>
              <span class="stat-value" id="totalCount">0</span>
            </div>
          </div>
        </div>

        <!-- Bookings Section -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>My Bookings</span>
            <span class="badge bg-primary rounded-pill" id="activeCount">0 Active</span>
          </div>
          <div class="card-body p-0">
            
            <!-- Tabs -->
            <div class="p-3">
              <div class="tab-container">
                <button class="tab-btn active" data-target="all">All</button>
                <button class="tab-btn" data-target="active">Active</button>
                <button class="tab-btn" data-target="completed">Completed</button>
              </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-wrapper px-3 pb-3">
              <?php
              include "php/config/config.php";
              $id = $_SESSION['user_id'];

              $sql = "SELECT d.d_id, d.booking_no, d.d_datetime, d.d_dispatcher, d.d_dispatchHub,
                      d.d_driverName, d.driver_id, d.d_truck, d.d_trailer, d.d_genset,
                      d.d_tripReceipt, d.d_ecs, d.costumer, d.workflow_stage,
                      t.trip_id, t.trip_type, t.trip_container, t.container_activity,
                      t.trip_containerStat, t.trip_haulingSegment, t.trip_haulingType,
                      t.trip_from, t.trip_to, t.km_run, t.trip_departureDateTime, t.trip_arrivalDateTime,
                      t.trip_pharrivalDateTime, t.deliver_location, t.deliver_dateTime,
                      t.withdraw_location, t.withdraw_dateTime, t.required_date, t.trip_status
                      FROM dispatch d
                      LEFT JOIN trips t ON d.d_id = t.d_id
                      WHERE d.driver_id = '$id'
                      ORDER BY d.d_datetime DESC";
              
              $result = $conn->query($sql);
              $bookings = [];
              while ($row = $result->fetch_assoc()) {
                $bookings[$row['d_id']]['info'] = $row;
                $bookings[$row['d_id']]['trips'][] = $row;
              }
              ?>

              <!-- All Bookings -->
              <div class="tab-panel active" id="all">
                <?php foreach ($bookings as $d_id => $data): 
                  $dispatch = $data['info'];
                  $trips = $data['trips'];
                  $status = end($trips)['trip_status'];
                  $statusClass = $status == "Active" ? "active" : ($status == "Done" ? "completed" : "pending");
                  $statusText = $status == "Active" ? "Active" : ($status == "Done" ? "Completed" : $status);
                ?>
                <?php
                  $wf = $dispatch['workflow_stage'] ?? 'dispatcher_assigned';
                  $isPending  = in_array($wf, ['dispatcher_assigned', 'reassigned'], true);
                  $isAccepted = in_array($wf, ['driver_accepted', 'gate_cleared', 'en_route'], true);
                  $isDelivered = in_array($wf, ['delivered', 'pod_captured', 'billing_closed', 'client_notified'], true);
                  $wfLabel = [
                    'dispatcher_assigned' => 'Awaiting your accept',
                    'reassigned'          => 'Re-assigned to you',
                    'driver_accepted'     => 'Accepted',
                    'gate_cleared'        => 'Gate cleared',
                    'en_route'            => 'En route',
                    'delivered'           => 'Delivered',
                    'pod_captured'        => 'POD captured',
                    'billing_closed'      => 'Billing closed',
                    'client_notified'     => 'Client notified',
                    'driver_declined'     => 'Declined',
                    'reassigned_from'     => 'Superseded',
                  ][$wf] ?? $wf;
                ?>
                <div class="booking-card" data-booking-id="<?= $d_id ?>" data-status="<?= strtolower($statusText) ?>" data-wf="<?= htmlspecialchars($wf) ?>">
                  <div class="booking-header">
                    <div>
                      <h6><?= $dispatch['booking_no'] ?></h6>
                      <span class="customer"><?= htmlspecialchars($dispatch['costumer']) ?></span>
                      <div class="meta">
                        <i class="ti ti-calendar"></i> <?= date("M d, Y", strtotime($dispatch['required_date'])) ?>
                        <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($wfLabel) ?></span>
                      </div>
                    </div>
                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                  </div>

                  <div class="booking-details" id="details-<?= $d_id ?>">
                    <div class="mb-3">
                      <small class="text-muted d-block mb-1">Assigned Vehicle</small>
                      <strong><?= $dispatch['d_truck'] ?></strong> • <?= $dispatch['d_trailer'] ?>
                    </div>

                    <?php if ($isPending): ?>
                      <div class="d-flex gap-2 mb-3 phase5-actions">
                        <button class="btn-modern btn-success-modern flex-fill phase5-accept" data-id="<?= $d_id ?>"><i class="ti ti-check"></i> Accept</button>
                        <button class="btn-modern btn-outline-modern flex-fill phase5-decline" data-id="<?= $d_id ?>"><i class="ti ti-x"></i> Decline</button>
                      </div>
                    <?php elseif ($isAccepted): ?>
                      <div class="phase5-status-row d-flex flex-wrap gap-1 mb-2">
                        <button class="btn-modern btn-outline-modern phase5-status" data-id="<?= $d_id ?>" data-st="picked_up"  style="flex:1;min-width:42%;font-size:12px;padding:6px;">Picked up</button>
                        <button class="btn-modern btn-outline-modern phase5-status" data-id="<?= $d_id ?>" data-st="on_the_way" style="flex:1;min-width:42%;font-size:12px;padding:6px;">On the way</button>
                        <button class="btn-modern btn-outline-modern phase5-status" data-id="<?= $d_id ?>" data-st="arrived"    style="flex:1;min-width:42%;font-size:12px;padding:6px;">Arrived</button>
                        <button class="btn-modern btn-success-modern phase5-status" data-id="<?= $d_id ?>" data-st="delivered"  style="flex:1;min-width:42%;font-size:12px;padding:6px;">Delivered</button>
                      </div>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                        <a href="driver-receipts?d_id=<?= $d_id ?>" class="btn-modern btn-outline-modern" style="flex:1;min-width:30%;font-size:12px;padding:6px;"><i class="ti ti-file"></i> Receipts</a>
                        <a href="driver-pod?d_id=<?= $d_id ?>"      class="btn-modern btn-primary-modern" style="flex:1;min-width:30%;font-size:12px;padding:6px;"><i class="ti ti-camera"></i> POD</a>
                        <a href="driver-gateless?d_id=<?= $d_id ?>" class="btn-modern btn-outline-modern" style="flex:1;min-width:30%;font-size:12px;padding:6px;"><i class="ti ti-map-pin"></i> Gateless</a>
                      </div>
                      <div class="d-flex flex-wrap gap-1 mb-3">
                        <a href="driver-jackup?d_id=<?= $d_id ?>"   class="btn-modern btn-outline-modern" style="flex:1;min-width:30%;font-size:12px;padding:6px;"><i class="ti ti-trailer"></i> Jack-up</a>
                      </div>
                    <?php endif; ?>

                    <div class="trip-list">
                      <?php foreach ($trips as $trip): ?>
                      <div class="trip-item">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <strong><?= $trip['container_activity'] ?></strong>
                            <div class="trip-route">
                              <span><?= $trip['trip_from'] ?></span>
                              <i class="ti ti-arrow-right"></i>
                              <span><?= $trip['trip_to'] ?></span>
                            </div>
                          </div>
                          <span class="badge bg-light text-dark"><?= $trip['trip_haulingType'] ?></span>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                      <button class="btn-modern btn-outline-modern flex-fill view-map" 
                              data-from="<?= htmlspecialchars(end($trips)['trip_from']) ?>"
                              data-to="<?= htmlspecialchars(end($trips)['trip_to']) ?>"
                              data-trip='<?= json_encode(end($trips), JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                        <i class="ti ti-map"></i> Map
                      </button>
                      
                      <?php if ($status == "Active"): ?>
                      <button class="btn-modern btn-success-modern flex-fill view-time"
                              data-id="<?= end($trips)['trip_id'] ?>"
                              data-container="<?= htmlspecialchars(end($trips)['trip_container'] ?? '') ?>">
                        <i class="ti ti-check"></i> Complete
                      </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
      <a href="driver-dashboard" class="nav-item active">
        <i class="ti ti-smart-home"></i>
        <span>Home</span>
      </a>
      <a href="driver-unit" class="nav-item">
        <i class="ti ti-truck"></i>
        <span>Unit</span>
      </a>
      <a href="driver-tripReport" class="nav-item">
        <i class="ti ti-clipboard-list"></i>
        <span>Reports</span>
      </a>
      <button onclick="sosAlert()" class="nav-item sos-btn">
        <i class="ti ti-alert-triangle"></i>
        <span>SOS</span>
      </button>
    </div>

  </div>

  <!-- Map Modal -->
  <div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ti ti-route"></i> Trip Route</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="map" style="height: 400px; width: 100%;"></div>
          <div id="tripDetails" class="map-info mt-3"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Time Modal -->
  <div class="modal fade" id="timeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Trip Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="trip_id">
          
          <div class="mb-3 d-none" id="containerWrapper">
            <label class="form-label">Container Number</label>
            <input type="text" id="container_no" class="form-control-modern" placeholder="Enter container no.">
          </div>

          <div class="mb-3">
            <label class="form-label">Arrival at CY</label>
            <input type="datetime-local" id="arrival_cy" class="form-control-modern">
          </div>

          <div class="mb-3">
            <label class="form-label">Departure</label>
            <input type="datetime-local" id="departure" class="form-control-modern">
          </div>

          <div class="mb-3">
            <label class="form-label">Arrival at PH</label>
            <input type="datetime-local" id="arrival_ph" class="form-control-modern">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="saveBtn" class="btn-modern btn-primary-modern">Save Progress</button>
          <button type="button" id="doneBtn" class="btn-modern btn-success-modern" style="display:none;">
            <i class="ti ti-check"></i> Mark Complete
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD4FCZJxNlXSlbV4pX18229Vh8UofzpAEk&libraries=places"></script>
  <?php /* Phase 5 — PWA registration. Optional VAPID public key surfaces push if configured. */ ?>
  <?php
    $vapidPublicKey = '';
    $vapidFile = __DIR__ . '/../php/config/vapid.php';
    if (file_exists($vapidFile)) { @include $vapidFile; if (defined('VAPID_PUBLIC_KEY')) $vapidPublicKey = VAPID_PUBLIC_KEY; }
  ?>
  <?php if ($vapidPublicKey !== ''): ?>
  <script>window.PT_VAPID_PUBLIC_KEY = <?php echo json_encode($vapidPublicKey); ?>;</script>
  <?php endif; ?>
  <script src="driver/pwa-register.js"></script>

  <script>
    // Modern Tab Switching
    $('.tab-btn').on('click', function() {
      $('.tab-btn').removeClass('active');
      $(this).addClass('active');
      
      const target = $(this).data('target');
      $('.booking-card').each(function() {
        const card = $(this);
        if (target === 'all') {
          card.show();
        } else {
          const status = card.data('status');
          card.toggle(status === target);
        }
      });
    });

    // Booking Card Toggle
    $('.booking-card').on('click', function(e) {
      if ($(e.target).closest('button').length) return;
      if ($(e.target).closest('a').length) return;

      const id = $(this).data('booking-id');
      const details = $(`#details-${id}`);
      details.toggleClass('show');
    });

    // Phase 5 — Accept / Decline a pending dispatch.
    $(document).on('click', '.phase5-accept', function (e) {
      e.stopPropagation();
      const id = $(this).data('id');
      $.post('php/operations/driver_accept_job.php', { d_id: id }, function (res) {
        Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
                    text: res.message, timer: 1500, showConfirmButton: false });
        if (res.status === 'success' || res.status === 'queued') setTimeout(() => location.reload(), 1600);
      }, 'json');
    });
    $(document).on('click', '.phase5-decline', function (e) {
      e.stopPropagation();
      const id = $(this).data('id');
      Swal.fire({ title: 'Decline this job?', input: 'text', inputPlaceholder: 'Reason (optional)',
                  showCancelButton: true, confirmButtonText: 'Decline', confirmButtonColor: '#dc3545' })
        .then(r => {
          if (!r.isConfirmed) return;
          $.post('php/operations/driver_decline_job.php', { d_id: id, reason: r.value || '' }, function (res) {
            Swal.fire({ icon: res.status === 'success' ? 'success' : 'info', text: res.message, timer: 1500, showConfirmButton: false });
            if (res.status === 'success' || res.status === 'queued') setTimeout(() => location.reload(), 1600);
          }, 'json');
        });
    });

    // Phase 5 — Status pills.
    $(document).on('click', '.phase5-status', function (e) {
      e.stopPropagation();
      const id = $(this).data('id');
      const st = $(this).data('st');
      const send = (lat, lng) => {
        $.post('php/operations/driver_update_status.php', { d_id: id, status: st, lat: lat || '', lng: lng || '' }, function (res) {
          Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
                      text: res.message, timer: 1200, showConfirmButton: false });
          if (st === 'delivered' && (res.status === 'success' || res.status === 'queued')) {
            setTimeout(() => location.href = 'driver-pod?d_id=' + id, 1300);
          }
        }, 'json');
      };
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          p => send(p.coords.latitude.toFixed(7), p.coords.longitude.toFixed(7)),
          () => send(),
          { enableHighAccuracy: true, timeout: 5000 }
        );
      } else { send(); }
    });

    // Fetch Stats
    function fetchDriverBookings() {
      $.getJSON("php/fetch/getDriverBooking.php", { driverID: $("#driverID").val() }, function(data) {
        $("#booking").text(data.active);
        $("#totalCount").text(data.done);
        $("#activeCount").text(data.active + " Active");
      });
    }
    
    fetchDriverBookings();
    setInterval(fetchDriverBookings, 5000);

    // SOS Function
    function sosAlert() {
      Swal.fire({
        title: '🚨 Emergency SOS',
        text: 'Send distress signal to dispatch?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Send SOS',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          $.post("php/operations/send_sos.php", { driver_id: $("#driverID").val() }, function() {
            Swal.fire('Sent!', 'SOS alert transmitted.', 'success');
          });
        }
      });
    }

    // Map functionality (preserved from original)
    let map, directionsService, directionsRenderer;

    function initMap(fromLat, fromLng, toLat, toLng, tripData) {
      map = new google.maps.Map(document.getElementById("map"), {
        zoom: 6,
        center: { lat: fromLat, lng: fromLng },
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
      });

      directionsService = new google.maps.DirectionsService();
      directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        polylineOptions: { strokeColor: "#2563eb", strokeWeight: 4 }
      });

      directionsService.route({
        origin: { lat: fromLat, lng: fromLng },
        destination: { lat: toLat, lng: toLng },
        travelMode: google.maps.TravelMode.DRIVING,
      }, (result, status) => {
        if (status === "OK") {
          directionsRenderer.setDirections(result);
        }
      });

      new google.maps.Marker({ 
        position: { lat: fromLat, lng: fromLng }, 
        map: map, 
        icon: { url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png" }
      });
      
      new google.maps.Marker({ 
        position: { lat: toLat, lng: toLng }, 
        map: map, 
        icon: { url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png" }
      });

      // Current location
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          new google.maps.Marker({
            position: { lat: pos.coords.latitude, lng: pos.coords.longitude },
            map: map,
            icon: { url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png" },
            title: "You are here"
          });
        });
      }

      $("#tripDetails").html(`
        <div class="map-info-row"><span>Activity</span><strong>${tripData.container_activity}</strong></div>
        <div class="map-info-row"><span>From</span><strong>${tripData.trip_from}</strong></div>
        <div class="map-info-row"><span>To</span><strong>${tripData.trip_to}</strong></div>
        <div class="map-info-row"><span>Container</span><strong>${tripData.trip_container || 'N/A'}</strong></div>
      `);
    }

    $(document).on("click", ".view-map", function() {
      const trip = JSON.parse($(this).attr("data-trip"));
      $.post("php/fetch/get_location.php", { from: trip.trip_from, to: trip.trip_to }, function(res) {
        const data = JSON.parse(res);
        $("#mapModal").modal("show");
        setTimeout(() => initMap(
          parseFloat(data.from.latitude), 
          parseFloat(data.from.longitude),
          parseFloat(data.to.latitude), 
          parseFloat(data.to.longitude), 
          trip
        ), 500);
      });
    });

    // Time Modal Logic (preserved)
    $(document).on('click', '.view-time', function() {
      const tripId = $(this).data('id');
      const container = $(this).data('container');
      
      $('#trip_id').val(tripId);
      $('#container_no').val('');
      $('#containerWrapper').toggleClass('d-none', container && container.trim() !== '');
      
      $.getJSON('php/fetch/get_tripDateTime1.php', { trip_id: tripId }, function(data) {
        if (data.status === 'success') {
          $('#container_no').val(data.trip.trip_container || '');
          $('#arrival_cy').val(data.trip.trip_arrivalDateTime || '');
          $('#departure').val(data.trip.trip_departureDateTime || '');
          $('#arrival_ph').val(data.trip.trip_pharrivalDateTime || '');
          checkInputs();
        }
      });
      
      $('#timeModal').modal('show');
    });

    function checkInputs() {
      const timeFilled = $('#arrival_cy').val() && $('#departure').val() && $('#arrival_ph').val();
      const containerRequired = !$('#containerWrapper').hasClass('d-none');
      const containerFilled = $('#container_no').val();
      
      if (timeFilled && (!containerRequired || containerFilled)) {
        $('#saveBtn').hide();
        $('#doneBtn').show();
      } else {
        $('#saveBtn').show();
        $('#doneBtn').hide();
      }
    }

    $('#arrival_cy, #departure, #arrival_ph, #container_no').on('input change', checkInputs);

    $('#saveBtn').on('click', function() {
      $.post('php/crud/update/update_tripDateTime1.php', {
        trip_id: $('#trip_id').val(),
        arrival_cy: $('#arrival_cy').val(),
        departure: $('#departure').val(),
        arrival_ph: $('#arrival_ph').val(),
        action: 'save'
      }, function(response) {
        if (response.status === 'success') {
          Swal.fire('Saved!', 'Progress updated.', 'success');
          $('#timeModal').modal('hide');
        }
      }, 'json');
    });

    $('#doneBtn').on('click', function() {
      Swal.fire({
        title: 'Complete Trip?',
        text: 'This will mark the trip as finished.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete',
        confirmButtonColor: '#10b981'
      }).then((result) => {
        if (result.isConfirmed) {
          $.post('php/crud/update/update_tripDateTime1.php', {
            trip_id: $('#trip_id').val(),
            container_no: $('#container_no').val(),
            action: 'done'
          }, function(response) {
            if (response.status === 'success') {
              Swal.fire('Completed!', 'Trip finished successfully.', 'success')
                .then(() => location.reload());
            }
          }, 'json');
        }
      });
    });
  </script>
</body>
</html>
<?php
} else {
  header("Location: driver-index.php?route=login");
  exit();
}
?>
