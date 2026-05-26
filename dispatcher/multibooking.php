<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Dispatcher") {


?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Booking</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="datatable/datatables.min.css">
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
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
          <!--  Row 1 -->
          <div class="row">
            <div class="col-12" hidden>
              <div class="card">
                <div class="card-body">
                  <div class="d-md-flex align-items-center">
                    <div>
                      <h4 class="card-title">Upload Booking</h4>
                      <p class="card-subtitle">
                        Drag & drop or select an Excel file to upload booking data.
                      </p>
                    </div>
                  </div>
                  <form id="uploadForm" enctype="multipart/form-data">
                    <div id="drop-area" style="border:2px dashed #ccc; border-radius:8px; padding:30px; text-align:center; cursor:pointer;">
                      <input type="file" name="bookingFile" id="bookingFile" accept=".xlsx,.xls" style="display:none;" />
                      <p>Drag & drop Excel file here or <span style="color:#007bff; text-decoration:underline; cursor:pointer;" onclick="document.getElementById('bookingFile').click();">browse</span></p>
                      <div id="fileName" style="margin-top:10px; color:#333;"></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Upload</button>
                  </form>
                  <div id="uploadStatus" class="mt-2"></div>
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="d-md-flex align-items-center">
                        <div>
                          <h4 class="card-title">Booking Table</h4>
                          <p class="card-subtitle">
                            Uploaded booking data will appear below.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 d-md-flex align-items-end justify-content-end">
                      <div class="d-md-flex align-items-center">
                        <div>
                          <!-- Button trigger modal -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" id="addbooking" hidden>
                         Add Booking
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" id="addbooking1" hidden>
                         Add Mulitple Booking
                        </button>
                        <a href="dispatch-addbook" class="btn btn-primary">Back</a>

                        </div>
                      </div>
                    </div>
                  </div>
                  <?php
                    include 'php/config/config.php';

                    // Get all drivers
                    $driverQuery = "SELECT driver_fname, driver_lname FROM drivers ORDER BY driver_lname ASC";
                    $driverResult = mysqli_query($conn, $driverQuery);

                    // Get all trucks (units) — Phase 10: exclude maintenance-blocked.
                    $truckQuery = "SELECT unit_name FROM units WHERE unit_status = 'good' AND maintenance_blocked = 0 AND unit_name NOT LIKE 'GS%' ORDER BY unit_name ASC";
                    $truckResult = mysqli_query($conn, $truckQuery);

                    $trailerQuery = "SELECT trailer_name FROM trailer WHERE trailer_status = 'good' AND maintenance_blocked = 0 ORDER BY trailer_name ASC";
                    $trailerResult = mysqli_query($conn, $trailerQuery);

                    $gensetQuery = "SELECT unit_name FROM units WHERE unit_status = 'good' AND maintenance_blocked = 0 AND unit_name LIKE 'GS%' ORDER BY unit_name ASC";
                    $gensetResult = mysqli_query($conn, $gensetQuery);

                    $haulingQuery = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC"; 
                    $haulingResult = mysqli_query($conn, $haulingQuery);

                    $haulingQuery1 = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC";
                    $haulingResult1 = mysqli_query($conn, $haulingQuery1);

                    $locationQuery = "SELECT location_name FROM location ORDER BY location_id ASC";
                    $locationResult = mysqli_query($conn, $locationQuery);

                    $locationQuery1 = "SELECT location_name FROM location ORDER BY location_id ASC";
                    $locationResult1 = mysqli_query($conn, $locationQuery1);

                    $locationQuery2 = "SELECT location_name FROM location ORDER BY location_id ASC";
                    $locationResult2 = mysqli_query($conn, $locationQuery2);

                    $locationQuery3 = "SELECT location_name FROM location ORDER BY location_id ASC";
                    $locationResult3 = mysqli_query($conn, $locationQuery3);

                    ?>

                    <form id="addForm1" action="php/crud/add/addbooking.php" method="POST" enctype="multipart/form-data">

                    <!-- Booking No & Customer -->
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <label for="booking_no2" class="form-label">Booking No</label>
                        <input type="text" class="form-control" id="booking_no" name="booking_no" readonly required>
                      </div>
                      
                      <div class="col-md-6">
                        <?php
                              include 'php/config/config.php';

                              $customerQuery2 = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                              $customerResult2 = mysqli_query($conn, $customerQuery2);
                              ?>
                        <label for="costumer" class="form-label">Customer Name<span class="text-danger">*</span></label>
                        <select class="form-control" id="costumer" name="costumer" required>
                          <option value="" selected>-- Select Customer --</option>
                          <?php while ($row2 = mysqli_fetch_assoc($customerResult2)) { ?>
                            <option value="<?php echo htmlspecialchars($row2['customer_code']); ?>">
                              <?php echo htmlspecialchars($row2['customer_code']); ?>
                            </option>
                          <?php } ?>
                        </select>
                      </div>
                      
                    </div>

                    <!-- Booking Dates -->
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <label for="booking_date" class="form-label">Date Requested<span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="booking_date" name="booking_date" required>
                      </div>
                      <div class="col-md-6 dateRequired">
                        <label for="booking_required" class="form-label">Date Required<span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="booking_required" name="booking_required">
                      </div>

                      <div class="col-md-3 bookingSN" style="display: none;">
                        <label for="booking_sn" class="form-label">Shipment Number</label>
                        <input type="text" class="form-control" id="booking_sn" name="booking_sn">
                      </div>
                      <div class="col-md-3 bookingDO" style="display: none;">
                        <label for="booking_do" class="form-label">DO</label>
                        <input type="text" class="form-control" id="booking_do" name="booking_do">
                      </div>
                    </div>
                    <div class="row mb-3" id="cthdate" style="display: none;">
                    <div class="col-md-3">
                      <label for="haulingStart" class="form-label">Hauling Start<span class="text-danger">*</span></label>
                      <input type="date" class="form-control" id="haulingStart" name="haulingStart">
                    </div>
                    <div class="col-md-3">
                      <label for="lastDayStorage" class="form-label">Last Day of Storage<span class="text-danger">*</span></label>
                      <input type="date" class="form-control" id="lastDayStorage" name="lastDayStorage">
                    </div>
                    <div class="col-md-3">
                      <label for="lastDayDemurrage" class="form-label">Last Day of Demurrage<span class="text-danger">*</span></label>
                      <input type="date" class="form-control" id="lastDayDemurrage" name="lastDayDemurrage">
                    </div>
                    <div class="col-md-3">
                      <label for="lastDayDetention" class="form-label">Last Day of Detention<span class="text-danger">*</span></label>
                      <input type="date" class="form-control" id="lastDayDetention" name="lastDayDetention">
                    </div>
                  </div>


                    <!-- Table for Multiple Rows -->
                    <h5 class="mt-4">Booking Details</h5>
                    <table class="table table-bordered" id="bookingTable">
                      <thead class="table-light">
                        <tr>
                          <th>Container</th>
                          <th>Seal</th>
                          <th>Activity</th>
                          <th>Status</th>
                          <th>Hauling Segment</th>
                          <th>From</th>
                          <th>To</th>
                          <th>Return Location</th>
                          <th>Quantity</th>
                          <th><button type="button" class="btn btn-success btn-sm" id="addRowBtn">+</button></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td><input type="text" name="container[]" class="form-control" required></td>
                          <td><input type="text" name="container_seal[]" class="form-control" required></td>
                          <td>
                            <input type="text" class="form-control" list="bookingActivity" name="booking_activity[]" required>
                            <datalist id="bookingActivity">
                              <option value="WITHDRAW">
                              <option value="DELIVER">
                                <option value="N/A">
                            </datalist>
                          </td>
                          <td>
                            <input type="text" class="form-control" list="containerStat" name="container_status[]" required>
                            <datalist id="containerStat">
                              <option value="EMPTY">
                              <option value="LOADED">
                              <option value="N/A">
                            </datalist>
                          </td>
                          <td>
                            <input type="text" class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment[]" required>
                            <datalist id="datalistOptions_hauling_segment">
                              <?php while ($row4 = mysqli_fetch_assoc($haulingResult)) { echo "<option value='{$row4['hauling_segment']}'>"; } ?>
                            </datalist>
                          </td>
                          <td>
                            <input type="text" class="form-control" list="datalistOptions_destination_from" name="trip_from[]" required>
                            <datalist id="datalistOptions_destination_from">
                              <?php while ($row5 = mysqli_fetch_assoc($locationResult)) { echo "<option value='{$row5['location_name']}'>"; } ?>
                            </datalist>
                          </td>
                          <td>
                            <input type="text" class="form-control" list="datalistOptions_destination_to" name="trip_to[]" required>
                            <datalist id="datalistOptions_destination_to">
                              <?php while ($row6 = mysqli_fetch_assoc($locationResult1)) { echo "<option value='{$row6['location_name']}'>"; } ?>
                            </datalist>
                          </td>
                          <td>
                            <input type="text" class="form-control" list="datalistOptions_destination_return" name="return_location[]" required>
                            <datalist id="datalistOptions_destination_return">
                              <?php while ($row7 = mysqli_fetch_assoc($locationResult2)) { echo "<option value='{$row7['location_name']}'>"; } ?>
                            </datalist>
                          </td>
                          <td><input type="number" name="quantity[]" class="form-control" min="1" value="1" readonly></td>
                          <td><button type="button" class="btn btn-danger btn-sm removeRowBtn">x</button></td>
                        </tr>
                      </tbody>
                    </table>
                  </form>

                  <div class="row">
                    <div class="col-md-12">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button button button type="submit" class="btn btn-primary" id="saveBookingBtn3">Save</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
  <script src="assets/js/dashboard.js"></script>
  <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
  <script src="datatable/datatables.min.js"></script>
  <script src="js/addbooking.js"></script>
  <!-- solar icons -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script>
    document.getElementById('costumer').addEventListener('change', function() {
      const selectedCustomer = this.value;
      const dateRequired = document.querySelector('.dateRequired');
      const bookingSN = document.querySelector('.bookingSN');
      const bookingDO = document.querySelector('.bookingDO');
      const cthdate = document.getElementById('cthdate');

      if (selectedCustomer === 'CTH') {
        // Hide Date Required
        dateRequired.style.display = 'none';
        document.getElementById('booking_required').required = false;

        // Show CTH SN and cthdate
        bookingSN.style.display = 'block';
        bookingDO.style.display = 'block';  
        cthdate.style.display = 'flex';
      } else {
        // Show Date Required
        dateRequired.style.display = 'block';
        document.getElementById('booking_required').required = true;

        // Hide CTH SN and cthdate
        bookingSN.style.display = 'none';
        bookingDO.style.display = 'none';
        cthdate.style.display = 'none';
      }
    });

    $(document).ready(function () {
      // --- Add new row ---
      $("#addRowBtn").on("click", function () {

          let selectedCustomer = $("#costumer").val(); // get selected customer
          let firstRow = $("#bookingTable tbody tr:first");

          let getVal = (name) => {
              // copy only if customer is CTH and first row exists
              if (selectedCustomer === "CTH" && firstRow.length) {
                  return firstRow.find(`input[name="${name}[]"]`).val() || "";
              }
              return "";
          };

          let newRow = `
              <tr>
                  <td><input type="text" name="container[]" class="form-control" required></td>
                  <td><input type="text" name="container_seal[]" class="form-control" value="${getVal('container_seal')}"></td>
                  <td>
                      <input type="text" class="form-control" list="bookingActivity" name="booking_activity[]" required value="${getVal('booking_activity')}">
                  </td>
                  <td>
                      <input type="text" class="form-control" list="containerStat" name="container_status[]" required value="${getVal('container_status')}">
                  </td>
                  <td>
                      <input type="text" class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment[]" required value="${getVal('hauling_segment')}">
                  </td>
                  <td>
                      <input type="text" class="form-control" list="datalistOptions_destination_from" name="trip_from[]" required value="${getVal('trip_from')}">
                  </td>
                  <td>
                      <input type="text" class="form-control" list="datalistOptions_destination_to" name="trip_to[]" required value="${getVal('trip_to')}">
                  </td>
                  <td>
                      <input type="text" class="form-control" list="datalistOptions_destination_return" name="return_location[]" required value="${getVal('return_location')}">
                  </td>
                  <td><input type="number" name="quantity[]" class="form-control" min="1" value="1" readonly></td>
                  <td><button type="button" class="btn btn-danger btn-sm removeRowBtn">x</button></td>
              </tr>
          `;

          $("#bookingTable tbody").append(newRow);
      });

      // --- Remove row ---
      $(document).on("click", ".removeRowBtn", function () {
          $(this).closest("tr").remove();
      });

      // --- Save Booking ---
      $("#saveBookingBtn3").click(function (e) {
          e.preventDefault();

          var costumer = $("#costumer").val();

        // 🟡 Apply CTH-specific required logic
        if (costumer === "CTH") {
            $('#booking_required').prop('required', false);
            $('#booking_sn, #haulingStart, #lastDayStorage, #lastDayDemurrage, #lastDayDetention')
                .prop('required', true);

            Swal.fire({
                icon: 'info',
                title: 'CTH Customer Selected',
                text: 'Date Required is not required. Other fields are now required.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6'
            });
        } else {
            $('#booking_required').prop('required', true);
            $('#booking_sn, #haulingStart, #lastDayStorage, #lastDayDemurrage, #lastDayDetention')
                .prop('required', false);
        }


          // Validate required fields in form
          let valid = true;

          $("#addForm1 [required]").each(function () {
              // Skip readonly fields (they may be auto-filled)
              if ($(this).is('[readonly]')) {
                  return true; // continue
              }

              if ($(this).val().trim() === "") {
                  valid = false;
                  $(this).focus();
                  return false; // break loop
              }
          });

          if (!valid) {
              Swal.fire({
                  text: 'Please fill in all required fields',
                  icon: 'info'
              });
              return;
          }


          Swal.fire({
              title: 'Confirm Save Booking?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Confirm'
          }).then((result) => {
              if (result.isConfirmed) {
                  var formData = new FormData($('#addForm1')[0]);

                  $.ajax({
                      url: 'php/crud/add/addbooking1.php',
                      type: 'POST',
                      data: formData,
                      contentType: false,
                      cache: false,
                      processData: false,
                      success: function (response) {
                          let res = JSON.parse(response);

                          Swal.fire({
                              html: res.message, // show multiple booking numbers
                              icon: res.status,
                              showConfirmButton: false,
                              timer: 2000
                          });

                          if (res.status === "success") {
                              $('#addForm1')[0].reset();
                              setTimeout(() => {
                                  location.reload();
                              }, 2200);
                          }
                      },
                      error: function (xhr, status, error) {
                          Swal.fire({
                              text: 'Error: ' + error,
                              icon: 'error'
                          });
                      }
                  });
              }
          });
      });
  });

  </script>
</body>

</html>
<?php
} else {
    header("Location: dispatcher-index.php?route=login");
    exit();
} ?>
