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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" id="addbooking">
                         Add Booking
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" id="addbooking1" hidden>
                         Add Mulitple Booking
                        </button>
                        <a href="dispatch-multibook" class="btn btn-primary">Add Mulitple Booking</a>
                        
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="table-responsive mt-4" style="overflow: hidden;">
                    <table id="booking-table" class="table table-striped table-bordered mt-4" role="grid" >
                        <thead>
                            <tr>
                                <th>Booking No</th>
                                <th>Booking Date</th>
                                <th>Required Date</th>
                                <th>Age</th>
                                <th>Customer</th>
                                <th>Container (Status)</th>
                                <th>Segment (Type)</th>
                                <th>Trip</th>
                                <th>Quantity</th>
                                <th>Used</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php
            include 'php/config/config.php';

            // Get all drivers
            $driverQuery = "SELECT driver_fname, driver_lname FROM drivers ORDER BY driver_lname ASC";
            $driverResult = mysqli_query($conn, $driverQuery);

            // Get all trucks (units)
            $truckQuery = "SELECT unit_name FROM units WHERE unit_status = 'good' AND unit_name NOT LIKE 'GS%' ORDER BY unit_name ASC";
            $truckResult = mysqli_query($conn, $truckQuery);

            $trailerQuery = "SELECT trailer_name FROM trailer WHERE trailer_status = 'good' ORDER BY trailer_name ASC";
            $trailerResult = mysqli_query($conn, $trailerQuery);

            $gensetQuery = "SELECT unit_name FROM units WHERE unit_status = 'good' AND unit_name LIKE 'GS%' ORDER BY unit_name ASC";
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
          <!-- Modal -->
          <div class="modal fade" id="AddBookingModal">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5" id="exampleModalLabel">Enter Booking</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                 <form id="addForm" action="php/crud/add/addbooking.php" method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="booking_type" class="form-label">Booking Type <span style="color: red;">*</span></label>
                        <select class="form-control booking-type-select" id="booking_type" name="booking_type" required>
                          <option value="Local" selected>Local — between any two locations</option>
                          <option value="Import">Import — port to warehouse / client</option>
                          <option value="Export">Export — warehouse to port</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3"></div>
                    <div class="col-md-3">
                      <div class="mb-3 d-flex justify-content-between align-items-center">
                        <label for="booking_no" class="form-label mb-0">Booking No</label>
                        <input type="text" class="form-control w-50" id="booking_no" name="booking_no" readonly required style="max-width: 200px;">
                      </div>
                    </div>
                  </div>
                  <div class="row port-fields" id="port_fields" style="display:none;">
                    <div class="col-12"><hr><h6 class="text-muted mb-3">Port details (Import / Export)</h6></div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="vessel_name" class="form-label">Vessel Name</label>
                        <input type="text" class="form-control" id="vessel_name" name="vessel_name">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="voyage_no" class="form-label">Voyage No</label>
                        <input type="text" class="form-control" id="voyage_no" name="voyage_no">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="container_no_port" class="form-label">Container No (Port)</label>
                        <input type="text" class="form-control" id="container_no_port" name="container_no_port">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="bill_of_lading" class="form-label">Bill of Lading</label>
                        <input type="text" class="form-control" id="bill_of_lading" name="bill_of_lading">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="port_location" class="form-label">Port Location</label>
                        <input type="text" class="form-control" id="port_location" name="port_location">
                      </div>
                    </div>
                    <div class="col-md-4 export-only" style="display:none;">
                      <div class="mb-3">
                        <label class="form-label d-block">Customs Cleared</label>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="customs_cleared" name="customs_cleared" value="1">
                          <label class="form-check-label" for="customs_cleared">Cleared (required before gate exit)</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-12"><hr></div>
                  </div>
                    <div class="row">
                      <div class="col-md-6">
                          <div class="col-md-12">
                        <div class="mb-3">
                        <label for="costumer1" class="form-label">
                          Customer Name <span style="color: red;">*</span>
                        </label>
                        <?php
                              include 'php/config/config.php';

                              $customerQuery = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                              $customerResult = mysqli_query($conn, $customerQuery);
                              ?>
                        <select class="form-control" id="costumer" name="costumer" required>
                          <option value="" selected>-- Select Customer --</option>
                                <?php while ($row = mysqli_fetch_assoc($customerResult)) { ?>
                                <option value="<?php echo htmlspecialchars($row['customer_code']); ?>">
                                    <?php echo htmlspecialchars($row['customer_code']); ?>
                                </option>
                                <?php } ?>
                        </select>
                      </div>
                        
                      </div>
                      <div class="col-md-12">
                        
                        <div class="mb-3">
                          <label for="booking_date" class="form-label">Date Booked<span style="color: red;">*</span></label>
                          <input type="date" class="form-control" id="booking_date" name="booking_date" required>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="mb-3">
                          <label for="booking_required" class="form-label">Date Required<span style="color: red;">*</span></label>
                          <input type="date" class="form-control" id="booking_required" name="booking_required" required>
                        </div>
                      </div>
                      <div class="col-md-12"> 
                        <div class="mb-3">
                          <label for="container_seal" class="form-label">Container Seal</label>
                          <input type="text" class="form-control" id="container_seal" name="container_seal" required>
                          
                        </div>
                      </div>
                      <div class="col-md-12"> 
                        <div class="mb-3">
                          <label for="container" class="form-label">Container</label>
                          <input type="text" class="form-control" id="container" name="container" required>
                          
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="mb-3">
                          <label for="booking_activity" class="form-label">Booking Activity<span style="color: red;">*</span></label>
                          <input type="text" class="form-control" list="bookingActivity" id="booking_activity" name="booking_activity" required>
                          <datalist id="bookingActivity">
                              <option value="WITHDRAW">
                              <option value="DELIVER">
                              <option value="N/A">
                            </datalist>
                        </div>
                      </div>
                      </div>
                      <div class="col-md-6">
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="container_status" class="form-label">Container Status<span style="color: red;">*</span></label>
                              <input type="text" class="form-control" list="containerStat" id="container_status" name="container_status" required>
                              <datalist id="containerStat">
                                  <option value="EMPTY">
                                  <option value="LOADED">
                                  <option value="N/A">
                                </datalist>
                            </div>
                          </div>
                          
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="hauling_segment" class="form-label">Hauling Segment<span style="color: red;">*</span></label>
                              <input type="text" class="form-control" list="datalistOptions_hauling_segment" id="hauling_segment" name="hauling_segment" required>
                              <datalist id="datalistOptions_hauling_segment">
                                  <?php
                                    while ($row4 = mysqli_fetch_assoc($haulingResult)) {
                                      echo "<option value=\"{$row4['hauling_segment']}\">";
                                    }
                                    ?>
                                </datalist>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="trip_from" class="form-label">Trip From<span style="color: red;">*</span></label>
                              <input type="text" class="form-control"  list="datalistOptions_destination_from" id="trip_from" name="trip_from" required>
                              <datalist id="datalistOptions_destination_from">
                                <?php
                                    while ($row5 = mysqli_fetch_assoc($locationResult)) {
                                      echo "<option value=\"{$row5['location_name']}\">";
                                    }
                                    ?>
                                </datalist>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="trip_to" class="form-label">Trip To<span style="color: red;">*</span></label>
                              <input type="text" class="form-control" list="datalistOptions_destination_to" id="trip_to" name="trip_to" required>
                              <datalist id="datalistOptions_destination_to">
                                  <?php
                                    while ($row6 = mysqli_fetch_assoc($locationResult1)) {
                                      echo "<option value=\"{$row6['location_name']}\">";
                                    }
                                    ?>
                                </datalist>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="quantity" class="form-label">Transaction Quantity<span style="color: red;">*</span></label>
                              <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                            </div>
                          </div>
                      </div>
                    </div>
                 </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-success" data-bs-toggle="modal" id="uploadbooking">
                         Upload Booking
                        </button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary" id="saveBookingBtn">Save</button>
                </div>
              </div>
            </div>
          </div>
          <!-- Modal End -->

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal">
            <div class="modal-dialog modal-l">
              <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Booking</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                 <form id="editForm" action="php/operations/editbooking.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" id="booking_id1" name="booking_id">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="booking_type1" class="form-label">Booking Type <span style="color: red;">*</span></label>
                        <select class="form-control booking-type-select" id="booking_type1" name="booking_type1" required>
                          <option value="Local">Local — between any two locations</option>
                          <option value="Import">Import — port to warehouse / client</option>
                          <option value="Export">Export — warehouse to port</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2"></div>
                    <div class="col-md-4">
                      <div class="mb-3 d-flex justify-content-between align-items-center">
                        <label for="booking_no1" class="form-label mb-0">Booking No</label>
                        <input type="text" class="form-control w-50" id="booking_no1" name="booking_no1" readonly required style="max-width: 200px;">
                      </div>
                    </div>
                  </div>
                  <div class="row port-fields" id="port_fields_edit" style="display:none;">
                    <div class="col-12"><hr><h6 class="text-muted mb-3">Port details (Import / Export)</h6></div>
                    <div class="col-md-4"><div class="mb-3"><label for="vessel_name1" class="form-label">Vessel Name</label><input type="text" class="form-control" id="vessel_name1" name="vessel_name1"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label for="voyage_no1" class="form-label">Voyage No</label><input type="text" class="form-control" id="voyage_no1" name="voyage_no1"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label for="container_no_port1" class="form-label">Container No (Port)</label><input type="text" class="form-control" id="container_no_port1" name="container_no_port1"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label for="bill_of_lading1" class="form-label">Bill of Lading</label><input type="text" class="form-control" id="bill_of_lading1" name="bill_of_lading1"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label for="port_location1" class="form-label">Port Location</label><input type="text" class="form-control" id="port_location1" name="port_location1"></div></div>
                    <div class="col-md-4 export-only" style="display:none;">
                      <div class="mb-3">
                        <label class="form-label d-block">Customs Cleared</label>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="customs_cleared1" name="customs_cleared1" value="1">
                          <label class="form-check-label" for="customs_cleared1">Cleared (required before gate exit)</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-12"><hr></div>
                  </div>
                    <div class="row">
                      <div class="col-md-6">
                          <div class="col-md-12">
                           <div class="mb-3">
                              <label for="costumer1" class="form-label">
                                Customer Name <span style="color: red;">*</span>
                              </label>
                              <?php
                              include 'php/config/config.php';

                              $customerQuery1 = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                              $customerResult1 = mysqli_query($conn, $customerQuery1);
                              ?>
                              <select class="form-control" id="costumer1" name="costumer1" required>
                                 <option value="" selected>-- Select Customer --</option>
                                <?php while ($row1 = mysqli_fetch_assoc($customerResult1)) { ?>
                                <option value="<?php echo htmlspecialchars($row1['customer_code']); ?>">
                                    <?php echo htmlspecialchars($row1['customer_code']); ?>
                                </option>
                                <?php } ?>
                              </select>
                            </div>
                            
                          </div>
                          <div class="col-md-12">
                            
                            <div class="mb-3">
                              <label for="booking_date1" class="form-label">Date Booked<span style="color: red;">*</span></label>
                              <input type="date" class="form-control" id="booking_date1" name="booking_date1" required>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="booking_required1" class="form-label">Date Required<span style="color: red;">*</span></label>
                              <input type="date" class="form-control" id="booking_required1" name="booking_required1" required>
                            </div>
                          </div>
                          <div class="col-md-12"> 
                            <div class="mb-3">
                              <label for="container_seal1" class="form-label">Container Seal</label>
                              <input type="text" class="form-control" id="container_seal1" name="container_seal1" required>
                              
                            </div>
                          </div>
                          <div class="col-md-12"> 
                            <div class="mb-3">
                              <label for="container1" class="form-label">Container</label>
                              <input type="text" class="form-control" id="container1" name="container1" required>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="mb-3">
                              <label for="booking_activity1" class="form-label">Booking Activity<span style="color: red;">*</span></label>
                              <input type="text" class="form-control" list="bookingActivity" id="booking_activity1" name="booking_activity1" required>
                              <datalist id="bookingActivity">
                                  <option value="WITHDRAW">
                                  <option value="DELIVER">
                                  <option value="N/A">
                                </datalist>
                            </div>
                          </div>
                      </div>
                      <div class="col-md-6">
                        <div class="col-md-12">
                          <div class="mb-3">
                            <label for="container_status1" class="form-label">Container Status<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" list="containerStat1" id="container_status1" name="container_status1" required>
                            <datalist id="containerStat1">
                                <option value="EMPTY">
                                <option value="LOADED">
                                <option value="N/A">
                              </datalist>
                          </div>
                        </div>
                        
                        <div class="col-md-12">
                          <div class="mb-3">
                            <label for="hauling_segment1" class="form-label">Hauling Segment<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" list="datalistOptions_hauling_segment1" id="hauling_segment1" name="hauling_segment1" required>
                            <datalist id="datalistOptions_hauling_segment1">
                                <?php
                                  while ($row4 = mysqli_fetch_assoc($haulingResult1)) {
                                    echo "<option value=\"{$row4['hauling_segment']}\">";
                                  }
                                  ?>
                              </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <div class="mb-3">
                            <label for="trip_from1" class="form-label">Trip From<span style="color: red;">*</span></label>
                            <input type="text" class="form-control"  list="datalistOptions_destination_from1" id="trip_from1" name="trip_from1" required>
                            <datalist id="datalistOptions_destination_from1">
                              <?php
                                  while ($row5 = mysqli_fetch_assoc($locationResult2)) {
                                    echo "<option value=\"{$row5['location_name']}\">";
                                  }
                                  ?>
                              </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <div class="mb-3">
                            <label for="trip_to1" class="form-label">Trip To<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" list="datalistOptions_destination_to1" id="trip_to1" name="trip_to1" required>
                            <datalist id="datalistOptions_destination_to1">
                                <?php
                                  while ($row6 = mysqli_fetch_assoc($locationResult3)) {
                                    echo "<option value=\"{$row6['location_name']}\">";
                                  }
                                  ?>
                              </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <div class="mb-3">
                            <label for="quantity1" class="form-label">Transaction Quantity<span style="color: red;">*</span></label>
                            <input type="number" class="form-control" id="quantity1" name="quantity1" min="1" required>
                          </div>
                        </div>
                      </div>
                    </div>
                 </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-primary" id="saveChangesBtn">Save changes</button>
                </div>
              </div>
            </div>
          </div>


          <!-- Upload Modal -->
           <div class="modal fade" id="uploadmodal">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h3 id="title">Import Excel</h3>
                  </div>
                  <div class="modal-body">
                    <form id="importForm" enctype="multipart/form-data">
                      <div class="row">
                        <div class="column">
                          <input type="file" class="form-control" name="import_file" id="import_file" required>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="importBtn">Import</button>
                    <button data-bs-dismiss="modal" class="btn btn-secondary">Cancel</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End of Modal -->

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
    $(document).ready(function() {
      // Toggle port-fields panel based on booking_type select.
      // Both modals share the same handler via the .booking-type-select class.
      function applyBookingTypeToggle($select) {
        var val = $select.val();
        var $panel = $select.closest('form').find('.port-fields');
        var $exportOnly = $panel.find('.export-only');
        if (val === 'Import' || val === 'Export') {
          $panel.show();
          $exportOnly.toggle(val === 'Export');
        } else {
          $panel.hide();
          $exportOnly.hide();
        }
      }
      $(document).on('change', '.booking-type-select', function() {
        applyBookingTypeToggle($(this));
      });
      $('.booking-type-select').each(function() { applyBookingTypeToggle($(this)); });

      $("#uploadbooking").click(function() {
        $("#AddBookingModal").modal("hide");
        $("#uploadmodal").modal("show");
      });

      $('#importBtn').click(function() {
        const fileInput = $('#import_file')[0].files[0];

        if (!fileInput) {
          Swal.fire({
            icon: 'warning',
            title: 'No File Selected',
            text: 'Please choose a file to import.'
          });
          return;
        }

        let formData = new FormData();
        formData.append('import_file', fileInput);

        $.ajax({
          url: 'php/operations/import-excel.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            const trimmedResponse = response.trim();

            if (trimmedResponse === "success") {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Data successfully imported!'
              }).then(() => {
                location.reload();
              });
            } else if (/^\d+ records inserted successfully\.$/.test(trimmedResponse)) {
              // Handle the successful import with row count message
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: trimmedResponse
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: trimmedResponse
              });
            }
          },
          error: function() {
            Swal.fire({
              icon: 'error',
              title: 'AJAX Error',
              text: 'Failed to send the request.'
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
