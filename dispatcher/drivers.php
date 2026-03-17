<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Dispatcher") {


?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Driver Page</title>
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
              <div class="col-lg-12">
                <div class="card">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                        <h4 class="card-title">Driver Management List</h4>
                      </div>
                      <div class="ms-auto mt-3 mt-md-0">
                        <button class="btn btn-primary btn-sm" type="button" id="attendanceModalBtn"><i class="ti ti-plus"></i> Attendance</button>
                        <button class="btn btn-primary btn-sm" type="button" id="AddModal" hidden><i class="ti ti-plus"></i> Add Driver</button>
                      </div>
                    </div>
                    <div class="table-responsive mt-4">
                      <table class="table mb-0 text-nowrap varient-table align-middle fs-3" id="table-data">
                        <thead>
                          <tr>
                            <th scope="col" class="px-0 text-muted">ID Number</th>
                            <th scope="col" class="px-0 text-muted">First Name</th>
                            <th scope="col" class="px-0 text-muted">Middle Name</th>
                            <th scope="col" class="px-0 text-muted">Last Name</th>
                            <th scope="col" class="px-0 text-muted">Assign Unit</th>
                            <th scope="col" class="px-0 text-muted">Assign Hauling</th>
                            <th scope="col" class="px-0 text-muted">Assign Base</th>
                            <th scope="col" class="px-0 text-muted">Attendance Status</th>
                            <th scope="col" class="px-0 text-muted">Trip Status</th>
                            <th scope="col" class="px-0 text-muted text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          include "php/config/config.php";

                          // ✅ Select drivers + their latest attendance record
                          $query = "SELECT d.*, da.da_status, da.da_date
                                    FROM drivers d
                                    LEFT JOIN (
                                      SELECT driver_id, da_status, da_date
                                      FROM (
                                        SELECT da1.*, 
                                              ROW_NUMBER() OVER (PARTITION BY driver_id ORDER BY da_date DESC) AS rn
                                        FROM drivers_attendance da1
                                        WHERE DATE(da1.da_date) = CURDATE()
                                      ) ranked
                                      WHERE rn = 1
                                    ) da ON d.driver_id = da.driver_id
                                    ORDER BY d.driver_id DESC";

                          $result = mysqli_query($conn, $query);

                          while ($row = mysqli_fetch_assoc($result)) {
                          ?>
                            <tr>
                              <td class="px-0">
                                <div class="d-flex align-items-center">
                                  <img src="assets/images/profile/user-1.jpg" class="rounded-circle" width="40" alt="profile" />
                                  <div class="ms-3">
                                    <h6 class="mb-0 fw-bolder"><?php echo $row['driver_IdNumber']; ?></h6>
                                  </div>
                                </div>
                              </td>
                              <td class="px-0"><?php echo $row['driver_fname']; ?></td>
                              <td class="px-0"><?php echo $row['driver_mname']; ?></td>
                              <td class="px-0"><?php echo $row['driver_lname']; ?></td>
                              <td class="px-0"><?php echo $row['driver_assignUnit']; ?></td>
                              <td class="px-0"><?php echo $row['driver_assignSegment']; ?></td>
                              <td class="px-0"><?php echo $row['driver_assignBase']; ?></td>

                              <!-- ✅ Display Attendance Status -->
                              <td class="px-0">
                                <?php
                                if (!empty($row['da_status'])) {
                                  if ($row['da_status'] == 'Present') {
                                    echo '<span class="badge bg-success">Present</span>';
                                  } elseif ($row['da_status'] == 'Absent') {
                                    echo '<span class="badge bg-danger">Absent</span>';
                                  } elseif ($row['da_status'] == 'On Leave') {
                                    echo '<span class="badge bg-warning text-dark">On Leave</span>';
                                  } else {
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($row['da_status']) . '</span>';
                                  }
                                } else {
                                  echo '<span class="badge bg-secondary">No Record</span>';
                                }
                                ?>
                              </td>

                              <!-- Trip Status -->
                              <td class="px-0"><span class="badge bg-secondary"><?php echo $row['driver_status']; ?></span></td>



                              <td class="px-0 text-dark fw-medium text-end">
                                <div class="d-grid gap-2 d-md-block">
                                  <button class="btn btn-primary btn-sm" type="button" onclick="openUpdateDriver('<?php echo $row['driver_id']; ?>', 
                                    '<?php echo $row['driver_IdNumber']; ?>', 
                                    '<?php echo $row['driver_rfid']; ?>', 
                                    '<?php echo $row['driver_fname']; ?>', 
                                    '<?php echo $row['driver_mname']; ?>',
                                    '<?php echo $row['driver_lname']; ?>',
                                    '<?php echo $row['driver_assignUnit']; ?>',
                                    '<?php echo $row['driver_assignSegment']; ?>',
                                    '<?php echo $row['driver_uname']; ?>',
                                    '<?php echo $row['driver_pass']; ?>',
                                    '<?php echo $row['driver_account_status']; ?>',
                                    '<?php echo $row['driver_assignBase']; ?>');"><i class="ti ti-edit"></i></button>
                                  <button class="btn btn-danger btn-sm" type="button" onclick="deleteDriver('<?php echo $row['driver_id']; ?>');" hidden><i class="ti ti-trash"></i></button>
                                </div>
                              </td>
                            </tr>
                          <?php
                          }
                          ?>
                        </tbody>
                      </table>
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
    <!-- Add Modal -->
    <div class="modal fade" id="addmodal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 id="title">Add Driver</h3>
          </div>
          <div class="modal-body">
            <form id="addForm" action="php/adddriver.php" method="POST" enctype="multipart/form-data">
              <div class="row">
                <div class="input-style-1">
                  <div class="column" hidden>
                    <label for="driver_rfid">Driver RFID<span style="color: red;">*</span></label>
                    <input type="password" class="form-control" name="driver_rfid" id="driver_rfid" placeholder="Scan RFID">
                  </div>
                  <div class="column">
                    <label for="driver_IdNumber">Driver ID Number<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_IdNumber" id="driver_IdNumber" placeholder="Enter Driver ID" required>
                  </div>
                  <div class="column">
                    <label for="driver_fname">First Name<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_fname" id="driver_fname" placeholder="Enter Firstname" required>
                  </div>
                  <div class="column">
                    <label for="driver_mname">Middle Name</label>
                    <input type="text" class="form-control" name="driver_mname" id="driver_mname" placeholder="Enter Middle Name">
                  </div>
                  <div class="column">
                    <label for="driver_lname">Last Name<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_lname" id="driver_lname" placeholder="Enter Lastname" required>
                  </div>
                  <div class="column position-relative">
                    <label for="driver_assignUnit">Assign Unit<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_assignUnit" id="driver_assignUnit" placeholder="Select Unit" required>
                    <ul id="truckList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                  </div>
                  <?php
                  include 'php/config/config.php';

                  $haulingQuery = "SELECT hauling_id, hauling_segment FROM hauling ORDER BY hauling_id ASC";
                  $haulingResult = mysqli_query($conn, $haulingQuery);
                  ?>

                  <div class="column">
                    <label for="driver_assignSegment">Hauling Segment <span style="color: red;">*</span></label>
                    <select class="form-select" name="driver_assignSegment" id="driver_assignSegment" required>
                      <option value="" selected disabled>-- Select Hauling Segment --</option>
                      <?php while ($row = mysqli_fetch_assoc($haulingResult)) { ?>
                        <option value="<?php echo htmlspecialchars($row['hauling_segment']); ?>">
                          <?php echo htmlspecialchars($row['hauling_segment']); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                  <div class="column" hidden>
                    <label for="driver_username">Username<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_username" id="driver_username" placeholder="Enter Driver Username">
                  </div>
                  <div class="column" hidden>
                    <label for="driver_pass">Password<span style="color: red;">*</span></label>
                    <input type="password" class="form-control" name="driver_pass" id="driver_pass" placeholder="Enter Password">
                  </div>
                  <div class="column" hidden>
                    <label for="driver_image">Upload Image</label>
                    <input type="file" class="form-control" name="driver_image" id="driver_image" placeholder="Upload Image">
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-success" name="submit" id="addDriver">Save</button>
            <button data-bs-dismiss="modal" class="btn btn-secondary">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    <!-- End of Modal -->

    <!-- Update Driver Modal -->
    <div class="modal fade" id="editModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 id="title">Update Driver</h3>
          </div>
          <div class="modal-body">
            <form id="editForm" action="php/updatedriver.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="driver_id1" id="driver_id1">
              <div class="row">
                <div class="input-style-1">
                  <div class="column">
                    <label for="driver_rfid1">Driver RFID<span style="color: red;">*</span></label>
                    <input type="password" class="form-control" name="driver_rfid1" id="driver_rfid1" placeholder="Scan RFID">
                  </div>
                  <div class="column">
                    <label for="driver_IdNumber1">Driver ID Number<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_IdNumber1" id="driver_IdNumber1" required>
                  </div>
                  <div class="column">
                    <label for="driver_fname1">First Name<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_fname1" id="driver_fname1" required>
                  </div>
                  <div class="column">
                    <label for="driver_mname1">Middle Name</label>
                    <input type="text" class="form-control" name="driver_mname1" id="driver_mname1" required>
                  </div>
                  <div class="column">
                    <label for="driver_lname1">Last Name<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_lname1" id="driver_lname1" required>
                  </div>
                  <div class="column position-relative">
                    <label for="driver_assignUnit">Assign Unit<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_assignUnit1" id="driver_assignUnit1" placeholder="Select Unit" required>
                    <ul id="truckList1" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                  </div>
                  <?php
                  include 'php/config/config.php';

                  $haulingQuery = "SELECT hauling_id, hauling_segment FROM hauling ORDER BY hauling_id ASC";
                  $haulingResult = mysqli_query($conn, $haulingQuery);
                  ?>

                  <div class="column">
                    <label for="driver_assignSegment1">Hauling Segment <span style="color: red;">*</span></label>
                    <select class="form-select" name="driver_assignSegment1" id="driver_assignSegment1" required>
                      <option value="" selected disabled>-- Select Hauling Segment --</option>
                      <?php while ($row = mysqli_fetch_assoc($haulingResult)) { ?>
                        <option value="<?php echo htmlspecialchars($row['hauling_segment']); ?>">
                          <?php echo htmlspecialchars($row['hauling_segment']); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="column">
                    <div class="select-style-1">
                      <label for="driver_assignBase">Select Assign Location</label>
                      <div class="select-position">
                        <select class="form-select" name="driver_assignBase1" id="driver_assignBase1">
                          <option value="PTSI">PTSI</option>
                          <option value="CONSOL">CONSOL</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="column" hidden>
                    <label for="driver_username1">Username<span style="color: red;">*</span></label>
                    <input type="text" class="form-control" name="driver_username1" id="driver_username1" placeholder="Enter Driver Username">
                  </div>
                  <div class="column" hidden>
                    <label for="driver_pass1">Password<span style="color: red;">*</span></label>
                    <input type="password" class="form-control" name="driver_pass1" id="driver_pass1" placeholder="Enter Password">
                  </div>
                  <div class="select-style-1" hidden>
                    <label for="status">Account Status</label>
                    <div class="select-position">
                      <select class="form-select" name="status" id="status">
                        <option value=""></option>
                        <option value="Approve">Approved</option>
                        <option value="Pending">Pending</option>
                        <option value="Decline">Decline</option>
                      </select>
                    </div>
                  </div>
                  <div class="column" hidden>
                    <label for="driver_image1">Upload Image</label>
                    <input type="file" class="form-control" name="driver_image1" id="driver_image1" placeholder="Upload Image">
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-success" name="submit" id="UpdateDriver">Save</button>
            <button data-bs-dismiss="modal" class="btn btn-secondary">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    <!-- MODAL -->

    <div class="modal fade" id="attendanceModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Attendance Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="php/operations/save_attend.php" method="post">
            <div class="modal-body">
              <div class="row">
                <div class="col-7 text-end mt-2">
                  <label class="form-label">Control No:</label>
                </div>
                <div class="col-5">
                   <input type="text" class="form-control" id="controlNo" name="controlNo">
                </div>
              </div>

              <!-- Radio Buttons -->
              <div class="mb-3 position-relative">
                <label class="form-label">Driver<span style="color: red;">*</span></label>
                <input type="text" id="driver" name="driver" class="form-control" autocomplete="off" placeholder="-- Select Driver --" required>
                <input type="hidden" id="driverId" name="driver_id">

                <!-- Dropdown list -->
                <ul id="driverList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Select Status:</label>

                <div class="btn-group w-100" role="group">

                  <input type="radio" class="btn-check status-radio"
                    name="attendance_status" id="present" value="Present" autocomplete="off">
                  <label class="btn btn-outline-success" for="present">Present</label>

                  <input type="radio" class="btn-check status-radio"
                    name="attendance_status" id="absent" value="Absent" autocomplete="off">
                  <label class="btn btn-outline-danger" for="absent">Absent</label>

                  <input type="radio" class="btn-check status-radio"
                    name="attendance_status" id="vl" value="VL" autocomplete="off">
                  <label class="btn btn-outline-info" for="vl">VL</label>

                  <input type="radio" class="btn-check status-radio"
                    name="attendance_status" id="sl" value="SL" autocomplete="off">
                  <label class="btn btn-outline-warning" for="sl">SL</label>

                </div>
              </div>

              <!-- Leave Section -->
              <div class="leave-section border rounded p-3 bg-light">
                <div class="mb-3">
                  <label class="form-label">Date Prepared</label>
                  <input type="date" class="form-control" id="datePrepared">
                </div>

                <div class="mb-3">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" id="leave_start">
                </div>

                <div class="mb-3">
                  <label class="form-label">End Date</label>
                  <input type="date" class="form-control" id="leave_end">
                </div>

                <div class="mb-3">
                  <label class="form-label">Remarks</label>
                  <textarea class="form-control" rows="3" id="leave_remarks"></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-success" id="saveAttend">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- End of Modal -->
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebarmenu.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <script src="datatable/datatables.min.js"></script>
    <script src="js/drivers.js"></script>
    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
          fetchLatestControlNo();
      });

      async function fetchLatestControlNo() {
          try {
              const response = await fetch('php/fetch/get_latest_control2.php');
              const data = (await response.text()).trim();

              document.getElementById('controlNo').value = data;

          } catch (error) {
              console.error('Failed to fetch latest control number:', error);
          }
      }
      $(document).ready(function() {
        $("#attendanceModalBtn").click(function() {
          $("#attendanceModal").modal("show");
        });

        $('#saveAttend').click(function(e) {
          e.preventDefault();

          let controlNo = $('#controlNo').val();
          let driver_id = $('#driverId').val();
          let status = $('input[name="attendance_status"]:checked').val();
          let dateStart = $('#leave_start').val();
          let dateEnd = $('#leave_end').val();
          let remarks = $('#leave_remarks').val();
          let datePrepared = $('#datePrepared').val();

          if (!driver_id) {
            Swal.fire("Warning", "Please select a driver.", "warning");
            return;
          }

          if (!status) {
            Swal.fire("Warning", "Please select attendance status.", "warning");
            return;
          }

          // If Leave, validate dates
          if ((status === "VL" || status === "SL") && (!dateStart || !dateEnd)) {
            Swal.fire("Warning", "Start and End date required for Leave.", "warning");
            return;
          }

          $.ajax({
            url: "php/operations/save_attend.php",
            type: "POST",
            dataType: "json",
            data: {
              driver_id: driver_id,
              controlNo: controlNo,
              status: status,
              datePrepared: datePrepared,
              dateStart: dateStart,
              dateEnd: dateEnd,
              remarks: remarks
            },
            success: function(response) {

              if (response.status === "success") {

                Swal.fire({
                  icon: "success",
                  title: "Saved!",
                  text: response.message
                }).then(() => {

                  $('#attendanceModal').modal('hide');
                  $('#attendanceModal form')[0].reset();
                  $('.leave-section').hide();

                  setTimeout(() => {
                    if (status === "Present") {
                      window.open(
                        "dispatcher-index.php?route=printJob&id=" + response.insert_id,
                        "_blank"
                      );
                    }
                    location.reload();
                  }, 1300);

                });

              } else {
                Swal.fire("Error", response.message, "error");
              }
            },
            error: function() {
              Swal.fire("Error", "Unable to connect to the server.", "error");
            }
          });
        });
      });

      document.addEventListener("DOMContentLoaded", function() {
        // ===== Driver Search =====
        const driverInput = document.getElementById("driver");
        const driverIdInput = document.getElementById("driverId");
        const driverList = document.getElementById("driverList");
        let allDrivers = [];

        fetch("php/fetch/get_drivers2.php")
          .then(res => res.json())
          .then(data => {
            allDrivers = data;
          });

        driverInput.addEventListener("input", function() {
          filterDropdown(this, driverList, allDrivers.map(d => d.name), (name) => {
            driverInput.value = name;
            const selected = allDrivers.find(d => d.name === name);
            driverIdInput.value = selected ? selected.id : "";
          });
        });
        // ===== Truck Search =====
        const truckInput = document.getElementById("assignUnitName1");
        const truckList = document.getElementById("truckList");
        let allTrucks = [];

        fetch("php/fetch/get_trucks1.php")
          .then(res => res.json())
          .then(data => {
            allTrucks = data;
          });

        truckInput.addEventListener("input", function() {
          filterDropdown(this, truckList, allTrucks, (name) => {
            truckInput.value = name;
          });
        });

        // ===== Genset Search =====
        const gensetInput = document.getElementById("genset");
        const gensetList = document.getElementById("gensetList");
        let allGensets = [];

        fetch("php/fetch/get_gensets.php")
          .then(res => res.json())
          .then(data => {
            allGensets = data;
          });

        gensetInput.addEventListener("input", function() {
          filterDropdown(this, gensetList, allGensets, (name) => {
            gensetInput.value = name;
          });
        });

        // ===== Trailer Search =====
        const trailerInput = document.getElementById("trailer");
        const trailerList = document.getElementById("trailerList");
        let allTrailers = [];

        fetch("php/fetch/get_trailers.php")
          .then(res => res.json())
          .then(data => {
            allTrailers = data;
          });

        trailerInput.addEventListener("input", function() {
          filterDropdown(this, trailerList, allTrailers, (name) => {
            trailerInput.value = name;
          });
        });

        // ===== Shared Function =====
        function filterDropdown(inputElem, listElem, dataArr, onSelect) {
          const searchVal = inputElem.value.toLowerCase();
          listElem.innerHTML = "";

          if (!searchVal) {
            listElem.style.display = "none";
            return;
          }

          const filtered = dataArr.filter(item => item.toLowerCase().includes(searchVal));

          if (filtered.length === 0) {
            listElem.style.display = "none";
            return;
          }

          filtered.forEach((item, index) => {
            const li = document.createElement("li");
            li.className = "list-group-item";
            li.textContent = item;

            // highlight first suggestion
            if (index === 0) {
              li.classList.add("active-suggestion");
            }

            li.addEventListener("click", function() {
              onSelect(item);
              listElem.style.display = "none";
            });
            listElem.appendChild(li);
          });

          listElem.style.display = "block";
        }

        // ===== Autofill + Navigation Support =====
        function attachKeyboardNav(inputElem, listElem, onSelect) {
          let activeIndex = 0;

          inputElem.addEventListener("keydown", function(e) {
            const items = listElem.querySelectorAll("li");
            if (!items.length) return;

            if (e.key === "ArrowDown") {
              e.preventDefault();
              activeIndex = (activeIndex + 1) % items.length;
              updateActive(items, activeIndex);
            } else if (e.key === "ArrowUp") {
              e.preventDefault();
              activeIndex = (activeIndex - 1 + items.length) % items.length;
              updateActive(items, activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {
              const activeItem = items[activeIndex];
              if (activeItem) {
                onSelect(activeItem.textContent);
                listElem.style.display = "none";
              }
            }
          });

          function updateActive(items, index) {
            items.forEach(i => i.classList.remove("active-suggestion"));
            items[index].classList.add("active-suggestion");
          }
        }

        // Attach keyboard nav to each input/list
        attachKeyboardNav(driverInput, driverList, (val) => {
          driverInput.value = val;
          const selected = allDrivers.find(d => d.name === val);
          driverIdInput.value = selected ? selected.id : "";
        });

        attachKeyboardNav(truckInput, truckList, (val) => {
          truckInput.value = val;
        });

        attachKeyboardNav(gensetInput, gensetList, (val) => {
          gensetInput.value = val;
        });

        attachKeyboardNav(trailerInput, trailerList, (val) => {
          trailerInput.value = val;
        });

        // ===== Hide all dropdowns on click outside =====
        document.addEventListener("click", function(e) {
          [driverList, truckList, gensetList, trailerList].forEach(list => {
            if (!list.contains(e.target) &&
              !driverInput.contains(e.target) &&
              !truckInput.contains(e.target) &&
              !gensetInput.contains(e.target) &&
              !trailerInput.contains(e.target)) {
              list.style.display = "none";
            }
          });
        });
      });
      document.addEventListener("DOMContentLoaded", function() {

        // ===== Truck Search =====
        const truckInput = document.getElementById("driver_assignUnit");
        const truckList = document.getElementById("truckList");
        let allTrucks = [];

        fetch("php/fetch/get_trucks.php")
          .then(res => res.json())
          .then(data => {
            allTrucks = data;
          });

        truckInput.addEventListener("input", function() {
          filterDropdown(this, truckList, allTrucks, (name) => {
            truckInput.value = name;
          });
        });

        // ===== Truck Search =====
        const truckInput1 = document.getElementById("driver_assignUnit1");
        const truckList1 = document.getElementById("truckList1");
        let allTrucks1 = [];

        fetch("php/fetch/get_trucks.php")
          .then(res => res.json())
          .then(data => {
            allTrucks1 = data;
          });

        truckInput1.addEventListener("input", function() {
          filterDropdown(this, truckList1, allTrucks1, (name) => {
            truckInput1.value = name;
          });
        });


        // ===== Shared Function =====
        function filterDropdown(inputElem, listElem, dataArr, onSelect) {
          const searchVal = inputElem.value.toLowerCase();
          listElem.innerHTML = "";

          if (!searchVal) {
            listElem.style.display = "none";
            return;
          }

          const filtered = dataArr.filter(item => item.toLowerCase().includes(searchVal));

          if (filtered.length === 0) {
            listElem.style.display = "none";
            return;
          }

          filtered.forEach((item, index) => {
            const li = document.createElement("li");
            li.className = "list-group-item";
            li.textContent = item;

            // highlight first suggestion
            if (index === 0) {
              li.classList.add("active-suggestion");
            }

            li.addEventListener("click", function() {
              onSelect(item);
              listElem.style.display = "none";
            });
            listElem.appendChild(li);
          });

          listElem.style.display = "block";
        }

        // ===== Autofill + Navigation Support =====
        function attachKeyboardNav(inputElem, listElem, onSelect) {
          let activeIndex = 0;

          inputElem.addEventListener("keydown", function(e) {
            const items = listElem.querySelectorAll("li");
            if (!items.length) return;

            if (e.key === "ArrowDown") {
              e.preventDefault();
              activeIndex = (activeIndex + 1) % items.length;
              updateActive(items, activeIndex);
            } else if (e.key === "ArrowUp") {
              e.preventDefault();
              activeIndex = (activeIndex - 1 + items.length) % items.length;
              updateActive(items, activeIndex);
            } else if (e.key === "Enter" || e.key === "Tab") {
              const activeItem = items[activeIndex];
              if (activeItem) {
                onSelect(activeItem.textContent);
                listElem.style.display = "none";
              }
            }
          });

          function updateActive(items, index) {
            items.forEach(i => i.classList.remove("active-suggestion"));
            items[index].classList.add("active-suggestion");
          }
        }

        attachKeyboardNav(truckInput, truckList, (val) => {
          truckInput.value = val;
        });

        attachKeyboardNav(truckInput1, truckList1, (val) => {
          truckInput1.value = val;
        });

        // ===== Hide all dropdowns on click outside =====
        document.addEventListener("click", function(e) {
          [truckList].forEach(list => {
            if (!list.contains(e.target) &&
              !truckInput.contains(e.target) &&
              !truckInput1.contains(e.target)) {
              list.style.display = "none";
            }
          });
        });
      });

      const radios = document.querySelectorAll('.status-radio');
      const leaveSection = document.querySelector('.leave-section');

      radios.forEach(radio => {
        radio.addEventListener('change', function() {
          if (this.value === 'VL' || this.value === 'SL') {
            leaveSection.style.display = 'block';
          } else {
            leaveSection.style.display = 'none';
          }
        });
      });
    </script>
  </body>

  </html>
<?php
} else {
  header("Location: dispatcher-index.php?route=login");
  exit();
}
