<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Driver") {


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
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/driver.css" />
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
        <div class="body-wrapper-inner mb-5">
          <div class="container-fluid">
            <div class="row g-3 mb-3">
              <div class="col-6">
                <div class="card overflow-hidden h-100">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center flex-wrap">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <input type="hidden" id="driverID" value="<?php echo $_SESSION['user_id']; ?>">
                        <i class="ti ti-clock fs-6"></i>
                      </span>
                      <div class="ms-2">
                        <h6 class="mb-0 fw-bolder" style="font-size: 12px;">Available Booking</h6>
                      </div>
                      <div class="ms-auto mt-2 mt-sm-0">
                        <span id="booking" class="badge bg-secondary-subtle text-dark"
                          style="font-size: 16px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-6">
                <div class="card overflow-hidden h-100">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center flex-wrap">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-chart-line fs-6"></i>
                      </span>
                      <div class="ms-2">
                        <h6 class="mb-0 fw-bolder" style="font-size: 12px;">Total Transaction</h6>
                      </div>
                      <div class="ms-auto mt-2 mt-sm-0">
                        <span id="totalCount" class="badge bg-secondary-subtle text-dark"
                          style="font-size: 16px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php
            include "php/config/config.php";
            $id = $_SESSION['user_id'];

            // Query dispatch & trips for this driver
            $sql = "
                SELECT 
                    d.d_id, d.booking_no, d.d_datetime, d.d_dispatcher, d.d_dispatchHub,
                    d.d_driverName, d.driver_id, d.d_truck, d.d_trailer, d.d_genset,
                    d.d_tripReceipt, d.d_ecs, d.costumer,
                    t.trip_id, t.trip_type, t.trip_container, t.container_activity, 
                    t.trip_containerStat, t.trip_haulingSegment, t.trip_haulingType, 
                    t.trip_from, t.trip_to, t.km_run, t.trip_departureDateTime, t.trip_arrivalDateTime,
                    t.trip_pharrivalDateTime, t.deliver_location, t.deliver_dateTime, 
                    t.withdraw_location, t.withdraw_dateTime, t.required_date, t.trip_status
                FROM dispatch d
                LEFT JOIN trips t ON d.d_id = t.d_id
                WHERE d.driver_id = '$id'
                ORDER BY d.d_datetime DESC
            ";
            $result = $conn->query($sql);

            // group by booking_no
            $bookings = [];
            while ($row = $result->fetch_assoc()) {
              $bookings[$row['d_id']]['info'] = $row; // main dispatch info
              $bookings[$row['d_id']]['trips'][] = $row; // trip rows
            }
            ?>
            <!--  Row 1 -->
            <div class="row">
              <div class="col-lg-10 mx-auto">
                <div class="card w-100">
                  <div class="card-body">
                    <h4 class="card-title">Assigned Bookings</h4>
                    <p class="card-subtitle">Trips assigned to you</p>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all">All</button></li>
                      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inprogress">In-Progress</button></li>
                      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#completed">Completed</button></li>
                      <li class="nav-item" hidden><button class="nav-link" data-bs-toggle="tab" data-bs-target="#canceled">Canceled</button></li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content">
                      <!-- All Bookings -->
                      <div class="tab-pane fade show active" id="all">
                        <?php foreach ($bookings as $d_id => $data):
                          $dispatch = $data['info'];
                          $trips = $data['trips'];
                          // pick status of last trip
                          $status = end($trips)['trip_status'];
                        ?>
                          <div class="booking-card" data-bs-toggle="collapse" data-bs-target="#booking<?= $d_id ?>">
                            <div class="d-flex justify-content-between">
                              <div>
                                <h6 class="mb-1"><?= $dispatch['booking_no'] ?>
                                  <span class="text-danger"><?= htmlspecialchars($dispatch['costumer']) ?></span>
                                </h6>
                                <small><strong>Booked Date:</strong> <?= date("d M Y", strtotime($dispatch['d_datetime'])) ?> || <strong>Required Date:</strong> <?= date("d M Y", strtotime($dispatch['required_date'])) ?></small>
                              </div>
                              <div class="status <?php if ($status == "Active") {
                                                    echo "in-progress";
                                                  } else if ($status == "Done") {
                                                    echo "completed";
                                                  } else {
                                                    $status;
                                                  } ?>"><?php if ($status == "Active") {  
                                                    echo "Active";
                                                  } else if ($status == "Done") {
                                                    echo "Completed";
                                                    }?></div>
                            </div>
                          </div>
                          <div id="booking<?= $d_id ?>" class="collapse">
                            <div class="p-3 border rounded mb-3">
                              <p><strong>Truck:</strong> <?= $dispatch['d_truck'] ?></p>
                              <p><strong>Trailer:</strong> <?= $dispatch['d_trailer'] ?></p>
                              <p><strong>Genset:</strong> <?= $dispatch['d_genset'] ?></p>
                              <hr>
                              <h6><?= $dispatch['trip_type'] ?> </h6>
                              <ul class="list-group mb-3">
                                <?php foreach ($trips as $trip): ?>
                                  <li class="list-group-item">
                                    <p><strong>Hauling:</strong> <?= $trip['trip_haulingSegment'] ?> || <strong>Type:</strong> <?= $trip['trip_haulingType'] ?></p>
                                    <strong>Activity:</strong> <?= $trip['container_activity'] ?> |
                                    <strong>From:</strong> <?= $trip['trip_from'] ?> <i class="ti ti-arrow-narrow-right"></i>
                                    <strong>To:</strong> <?= $trip['trip_to'] ?>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                              <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary view-map"
                                  data-from="<?= htmlspecialchars($trip['trip_from']) ?>"
                                  data-to="<?= htmlspecialchars($trip['trip_to']) ?>"
                                  data-trip='<?= json_encode($trip, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                  View Map
                                </button>
                                <?php if ($status == "Active") {?>
                                <button class="btn btn-sm btn-success view-time"
                                        data-id="<?= $trip['trip_id'] ?>"
                                        data-container="<?= htmlspecialchars($trip['container_no'] ?? '') ?>">
                                  Done Trip
                                </button>
                                <?php }?>

                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <!-- Filtered tabs -->
                      <div class="tab-pane fade" id="inprogress">
                        <?php foreach ($bookings as $d_id => $data):
                          $status = end($data['trips'])['trip_status'];
                          if ($status == "Active"): ?>
                            <div class="booking-card" data-bs-toggle="collapse" data-bs-target="#booking<?= $d_id ?>">
                              <div class="d-flex justify-content-between">
                                <div>
                                  <h6 class="mb-1"><?= $dispatch['booking_no'] ?>
                                    <span class="text-danger"><?= htmlspecialchars($dispatch['costumer']) ?></span>
                                  </h6>
                                  <small><strong>Booked Date:</strong> <?= date("d M Y", strtotime($dispatch['d_datetime'])) ?> || <strong>Required Date:</strong> <?= date("d M Y", strtotime($dispatch['required_date'])) ?></small>
                                </div>
                                <div class="status in-progress">Active</div>
                              </div>
                            </div>
                            <div id="booking<?= $d_id ?>" class="collapse">
                              <div class="p-3 border rounded mb-3">
                                <p><strong>Truck:</strong> <?= $dispatch['d_truck'] ?></p>
                                <p><strong>Trailer:</strong> <?= $dispatch['d_trailer'] ?></p>
                                <p><strong>Genset:</strong> <?= $dispatch['d_genset'] ?></p>
                                <hr>
                                <h6><?= $dispatch['trip_type'] ?> </h6>
                                <ul class="list-group mb-3">
                                  <?php foreach ($trips as $trip): ?>
                                    <li class="list-group-item">
                                      <p><strong>Hauling:</strong> <?= $trip['trip_haulingSegment'] ?> || <strong>Type:</strong> <?= $trip['trip_haulingType'] ?></p>
                                      <strong>Activity:</strong> <?= $trip['container_activity'] ?> |
                                      <strong>From:</strong> <?= $trip['trip_from'] ?> <i class="ti ti-arrow-narrow-right"></i>
                                      <strong>To:</strong> <?= $trip['trip_to'] ?>
                                    </li>
                                  <?php endforeach; ?>
                                </ul>
                                <div class="d-flex gap-2">
                                  <button class="btn btn-sm btn-primary view-map"
                                    data-from="<?= htmlspecialchars($trip['trip_from']) ?>"
                                    data-to="<?= htmlspecialchars($trip['trip_to']) ?>"
                                    data-trip='<?= json_encode($trip, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    View Map
                                  </button>
                                  <button class="btn btn-sm btn-success view-time"
                                          data-id="<?= $trip['trip_id'] ?>"
                                          data-container="<?= htmlspecialchars($trip['container_no'] ?? '') ?>">
                                    Done Trip
                                  </button>

                                </div>
                              </div>
                            </div>
                        <?php endif;
                        endforeach; ?>
                      </div>

                      <div class="tab-pane fade" id="completed">
                        <?php foreach ($bookings as $d_id => $data):
                          $status = end($data['trips'])['trip_status'];
                          if ($status == "Done"): ?>
                            <div class="booking-card" data-bs-toggle="collapse" data-bs-target="#booking<?= $d_id ?>">
                              <div class="d-flex justify-content-between">
                                <div>
                                  <h6 class="mb-1"><?= $dispatch['booking_no'] ?>
                                    <span class="text-danger"><?= htmlspecialchars($dispatch['costumer']) ?></span>
                                  </h6>
                                  <small><strong>Booked Date:</strong> <?= date("d M Y", strtotime($dispatch['d_datetime'])) ?> || <strong>Required Date:</strong> <?= date("d M Y", strtotime($dispatch['required_date'])) ?></small>
                                </div>
                                <div class="status completed">Completed</div>
                              </div>
                              <div id="booking<?= $d_id ?>" class="collapse">
                                <div class="p-3 border rounded mb-3">
                                  <p><strong>Truck:</strong> <?= $dispatch['d_truck'] ?></p>
                                  <p><strong>Trailer:</strong> <?= $dispatch['d_trailer'] ?></p>
                                  <p><strong>Genset:</strong> <?= $dispatch['d_genset'] ?></p>
                                  <hr>
                                  <h6><?= $dispatch['trip_type'] ?> </h6>
                                  <ul class="list-group mb-3">
                                    <?php foreach ($trips as $trip): ?>
                                      <li class="list-group-item">
                                        <p><strong>Hauling:</strong> <?= $trip['trip_haulingSegment'] ?> || <strong>Type:</strong> <?= $trip['trip_haulingType'] ?></p>
                                        <strong>Activity:</strong> <?= $trip['container_activity'] ?> |
                                        <strong>From:</strong> <?= $trip['trip_from'] ?> <i class="ti ti-arrow-narrow-right"></i>
                                        <strong>To:</strong> <?= $trip['trip_to'] ?>
                                      </li>
                                    <?php endforeach; ?>
                                  </ul>
                                  <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary view-map"
                                      data-from="<?= htmlspecialchars($trip['trip_from']) ?>"
                                      data-to="<?= htmlspecialchars($trip['trip_to']) ?>"
                                      data-trip='<?= json_encode($trip, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                      View Map
                                    </button>

                                  </div>
                                </div>
                              </div>
                          <?php endif;
                        endforeach; ?>
                            </div>

                            <div class="tab-pane fade" id="canceled">
                              <?php foreach ($bookings as $d_id => $data):
                                $status = end($data['trips'])['trip_status'];
                                if ($status == "Canceled"): ?>
                                  <div class="booking-card">
                                    <div class="d-flex justify-content-between">
                                      <div>
                                        <h6 class="mb-1"><?= $data['info']['booking_no'] ?>
                                          <span class="text-danger"><?= htmlspecialchars($data['info']['costumer']) ?></span>
                                        </h6>
                                        <small><?= date("d M Y | H:i", strtotime($data['info']['d_datetime'])) ?></small>
                                      </div>
                                      <div class="status canceled">Canceled</div>
                                    </div>
                                  </div>
                              <?php endif;
                              endforeach; ?>
                            </div>
                      </div><!-- tab-content -->
                    </div>


                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Map Modal -->
      <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Trip Route Map</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div id="map" style="height:500px; width:100%;"></div>
              <hr>
              <div id="tripDetails" class="p-2"></div>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal -->
      <div class="modal fade" id="timeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            
            <div class="modal-header">
              <h5 class="modal-title">Trip Time Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
              <input type="hidden" id="trip_id">

               <!-- Container input (hidden initially) -->
              <div class="mb-3 d-none" id="containerWrapper">
                <label class="form-label">Container No.</label>
                <input type="text" id="container_no" class="form-control" placeholder="Enter container number">
              </div>

              <div class="mb-3">
                <label class="form-label">Arrival CY</label>
                <input type="datetime-local" id="arrival_cy" class="form-control">
              </div>

              <div class="mb-3">
                <label class="form-label">Departure</label>
                <input type="datetime-local" id="departure" class="form-control">
              </div>

              <div class="mb-3">
                <label class="form-label">Arrival PH</label>
                <input type="datetime-local" id="arrival_ph" class="form-control">
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" id="saveBtn" class="btn btn-primary">Save</button>
              <button type="button" id="doneBtn" class="btn btn-success" style="display:none;">Done</button>
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
      <!-- solar icons -->
      <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
      <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD4FCZJxNlXSlbV4pX18229Vh8UofzpAEk&libraries=places"></script>
      <!-- <script src="js/driver-dashboard.js"></script> -->
      <script>
        function fetchDriverBookings() {
          let driverID = $("#driverID").val();
          $.ajax({
              url: "php/fetch/getDriverBooking.php",
              type: "GET",
              data: { driverID: driverID },
              dataType: "json",
              success: function(data) {
                  $("#booking").text(data.active);     // Available Booking
                  $("#totalCount").text(data.done);    // Total Transaction
              }
          });
      }

      // Run on page load
      fetchDriverBookings();

      // Refresh every 5 seconds
      setInterval(fetchDriverBookings, 5000);
      let map, directionsService, directionsRenderer;

      // Init map function
      function initMap(fromLat, fromLng, toLat, toLng, tripData) {
        map = new google.maps.Map(document.getElementById("map"), {
          zoom: 6,
          center: { lat: fromLat, lng: fromLng }
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
          map: map,
          polylineOptions: { strokeColor: "blue" }
        });

        // Request route
        directionsService.route(
          {
            origin: { lat: fromLat, lng: fromLng },
            destination: { lat: toLat, lng: toLng },
            travelMode: google.maps.TravelMode.DRIVING,
          },
          (result, status) => {
            if (status === "OK") {
              directionsRenderer.setDirections(result);
            } else {
              alert("Directions request failed: " + status);
            }
          }
        );

        // Add markers
        new google.maps.Marker({ position: { lat: fromLat, lng: fromLng }, map: map, label: "A" });
        new google.maps.Marker({ position: { lat: toLat, lng: toLng }, map: map, label: "B" });

        // Show current location
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(pos => {
            let userLoc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            new google.maps.Marker({
              position: userLoc,
              map: map,
              icon: { url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png" },
              title: "Your Current Location"
            });
          });
        }

        // Trip details under map
        let detailsHtml = `
          <strong>Trip ID:</strong> ${tripData.trip_id}<br>
          <strong>Activity:</strong> ${tripData.container_activity} (${tripData.trip_containerStat})<br>
          <strong>From:</strong> ${tripData.trip_from}<br>
          <strong>To:</strong> ${tripData.trip_to}<br>
          <strong>Type:</strong> ${tripData.trip_type}<br>
          <strong>Hauling:</strong> ${tripData.trip_haulingSegment} - ${tripData.trip_haulingType}<br>
          <strong>KM Run:</strong> ${tripData.km_run}<br>
          <strong>Status:</strong> ${tripData.trip_status}<br>
        `;
        document.getElementById("tripDetails").innerHTML = detailsHtml;
      }

      // When user clicks "View Map"
      $(document).on("click", ".view-map", function() {
        let trip = JSON.parse($(this).attr("data-trip"));

        // Fetch latitude/longitude from hidden location table (AJAX call to get location coords)
        $.post("php/fetch/get_location.php", 
          { from: trip.trip_from, to: trip.trip_to }, 
          function(res) {
            let data = JSON.parse(res);
            let fromLat = parseFloat(data.from.latitude);
            let fromLng = parseFloat(data.from.longitude);
            let toLat   = parseFloat(data.to.latitude);
            let toLng   = parseFloat(data.to.longitude);

            $("#mapModal").modal("show");

            // Delay map init until modal is visible
            setTimeout(() => initMap(fromLat, fromLng, toLat, toLng, trip), 500);
          }
        );
      });
       $(document).on('click', '.view-time', function () {
          let tripId = $(this).data('id');
          let container = $(this).data('container');

          $('#trip_id').val(tripId);

          // Reset
          $('#container_no').val('');
          $('#containerWrapper').addClass('d-none');

          $('#saveBtn').show();
          $('#doneBtn').hide();

          // If NO container → show input
          if (!container || container.trim() === '') {
            $('#containerWrapper').removeClass('d-none');
          }

          $.getJSON('php/get_tripDateTime1.php', { trip_id: tripId }, function (data) {
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

      // Function to check inputs
      function checkInputs() {
        let timeFilled =
          $('#arrival_cy').val() &&
          $('#departure').val() &&
          $('#arrival_ph').val();

        let containerRequired = !$('#containerWrapper').hasClass('d-none');
        let containerFilled = $('#container_no').val();

        if (timeFilled && (!containerRequired || containerFilled)) {
          $('#saveBtn').hide();
          $('#doneBtn').show();
        } else {
          $('#saveBtn').show();
          $('#doneBtn').hide();
        }
      }

      // Bind input events
      $('#arrival_cy, #departure, #arrival_ph, #container_no').on('input change', checkInputs);

      // Save button click
      $('#saveBtn').on('click', function () {
        let tripData = {
          trip_id: $('#trip_id').val(),
          arrival_cy: $('#arrival_cy').val(),
          departure: $('#departure').val(),
          arrival_ph: $('#arrival_ph').val(),
          action: 'save'
        };

        $.post('php/update_tripDateTime1.php', tripData, function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Saved!',
              text: response.message,
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              $('#timeModal').modal('hide');
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: response.message
            });
          }
        }, 'json');
      });

      // Done button click
      $('#doneBtn').on('click', function () {

        Swal.fire({
          title: 'Are you sure?',
          text: 'This will mark the trip as completed.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, complete it!',
          cancelButtonText: 'Cancel',
          reverseButtons: true
        }).then((result) => {

          if (result.isConfirmed) {

            let tripData = {
              trip_id: $('#trip_id').val(),
              arrival_cy: $('#arrival_cy').val(),
              departure: $('#departure').val(),
              arrival_ph: $('#arrival_ph').val(),
              container_no: $('#container_no').val(),
              action: 'done'
            };

            $.post('php/update_tripDateTime1.php', tripData, function (response) {
              if (response.status === 'success') {
                Swal.fire({
                  icon: 'success',
                  title: 'Trip Completed!',
                  timer: 2000,
                  showConfirmButton: false
                }).then(() => {
                  $('#timeModal').modal('hide');
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: response.message || 'Something went wrong.'
                });
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
} ?>
