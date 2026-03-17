<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Dispatcher") {


?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dispatching</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    <link rel="stylesheet" href="assets/css/enhancements.css" />
    <link rel="stylesheet" href="datatable/datatables.min.css">
    <link rel="stylesheet" href="datatable/dataTables.columnFilter.css">
    <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
    <style>
      .done-row {
        background-color: #d4edda !important;
        /* light green */
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
        <?php include 'navbar.php';
        $id = $_SESSION['user_id'];
        $query = "SELECT * FROM user WHERE user_id = '$id'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        $fname = $data['user_fname'];
        $firstLetter = substr($data['user_lname'], 0, 1);

        $location = $data['user_assignLocation'];
        ?>
        <!--  Header End -->
        <div class="body-wrapper-inner">
          <div class="container-fluid">
            <!--  Row 1 -->
            <div class="row">
              <div class="col-lg-9">
                <div class="card w-100">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                        <h4 class="card-title"><i class="ti ti-truck"></i> Dispatching</h4>
                      </div>
                    </div>
                    <form id="addForm" action="php/addRecord.php" method="POST" enctype="multipart/form-data">
                      <div class="row">
                        <div class="col-md-9 text-end">
                          <div class="mb-1" style="margin-top: 10px;">
                            <label for="booking_no" class="form-label">Transaction No</label>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <input type="text" class="form-control" id="booking_no" name="booking_no" readonly>
                          </div>
                        </div>
                      </div>
                      <input type="hidden" id="assinglocation" name="assinglocation" value="<?php echo $location; ?>">
                      <input type="hidden" class="form-control" id="userName" name="userName" value="<?php echo strtoupper($data['user_lname']) . ', ' . strtoupper(substr($data['user_fname'], 0, 1)) . '' . strtoupper(substr($data['user_mname'], 0, 1)); ?>" required>
                      <?php
                      include 'php/config/config.php';

                      $haulingQuery = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC";
                      $haulingResult = mysqli_query($conn, $haulingQuery);

                      $haulingQuery1 = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC";
                      $haulingResult1 = mysqli_query($conn, $haulingQuery1);

                      $haulingQuery2 = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC";
                      $haulingResult2 = mysqli_query($conn, $haulingQuery2);

                      $haulingQuery3 = "SELECT hauling_segment FROM hauling ORDER BY hauling_id ASC";
                      $haulingResult3 = mysqli_query($conn, $haulingQuery3);

                      $locationQuery = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult = mysqli_query($conn, $locationQuery);

                      $locationQuery1 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult1 = mysqli_query($conn, $locationQuery1);

                      $locationQuery2 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult2 = mysqli_query($conn, $locationQuery2);

                      $locationQuery3 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult3 = mysqli_query($conn, $locationQuery3);

                      $locationQuery4 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult4 = mysqli_query($conn, $locationQuery4);

                      $locationQuery5 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult5 = mysqli_query($conn, $locationQuery5);

                      $locationQuery6 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult6 = mysqli_query($conn, $locationQuery6);

                      $locationQuery7 = "SELECT location_name FROM location ORDER BY location_id ASC";
                      $locationResult7 = mysqli_query($conn, $locationQuery7);

                      ?>
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3 position-relative">
                            <label class="form-label">Driver<span style="color: red;">*</span></label>
                            <input type="text" id="driver" name="driver" class="form-control" autocomplete="off" placeholder="-- Select Driver --" required>
                            <input type="hidden" id="driverId" name="driver_id">

                            <!-- Dropdown list -->
                            <ul id="driverList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3 position-relative">
                            <label class="form-label">Unit<span style="color: red;">*</span></label>
                            <input type="text" id="assignUnitName1" name="unit_name" class="form-control" autocomplete="off" placeholder="-- Select Truck --" required>
                            <ul id="truckList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>

                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-1">
                            <label for="tr" class="form-label">Trip Receipt<span style="color: red;">*</span></label>

                            <input type="text" class="form-control" id="tr" name="tr" placeholder="Enter Trip Receipt" required>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-1">
                            <label for="ecs" class="form-label">ECS</label>
                            <input type="text" class="form-control" id="ecs" name="ecs" placeholder="Enter ECS">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3 position-relative">
                            <label class="form-label">Trailer</label>
                            <input type="text" id="trailer" name="trailer" class="form-control" autocomplete="off" placeholder="-- Select Trailer --" required>
                            <ul id="trailerList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3 position-relative">
                            <label class="form-label">Genset</label>
                            <input type="text" id="genset" name="genset" class="form-control" autocomplete="off" placeholder="-- Select Genset --" required>
                            <ul id="gensetList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <hr style="border: 0.5px solid black; margin-top: 20px; margin-bottom: 20px;">
                        </div>

                        <div class="col-md-3">
                          <?php
                          include 'php/config/config.php';

                          $customerQuery = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                          $customerResult = mysqli_query($conn, $customerQuery);
                          ?>
                          <div class="mb-1">
                            <label for="costumer" class="form-label">Trip 1 - Customer<span style="color: red;">*</span></label>
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
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="container_seal" class="form-label">Trip 1 - Container Seal</label>
                            <input type="text" class="form-control" id="container_seal" name="container_seal" placeholder="Enter Container Seal">

                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_no" class="form-label">Trip 1 - Container No</label>
                            <input type="text" class="form-control" id="container_no" name="container_no" placeholder="Enter Container No">
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_status" class="form-label">Trip 1 - Container Status E/L<span style="color: red;">*</span></label>
                            <select class="form-select" id="container_status" name="container_status">
                              <option value="" selected>--Select Container Status--</option>
                              <option value="EMPTY">EMPTY</option>
                              <option value="LOADED">LOADED</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="booking_activity" class="form-label">Trip 1 - Activity<span style="color: red;">*</span></label>
                            <select class="form-select" id="booking_activity" name="booking_activity">
                              <option value="" selected>--Select Withdraw/Deliver--</option>
                              <option value="WITHDRAW">WITHDRAW</option>
                              <option value="DELIVER">DELIVER</option>
                              <option value="RETURN">RETURN</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="hauling_segment" class="form-label">Hauling Segment Trip 1<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment" id="hauling_segment" placeholder="Select Hauling Segment" required>
                            <datalist id="datalistOptions_hauling_segment">
                              <?php
                              while ($row4 = mysqli_fetch_assoc($haulingResult)) {
                                echo "<option value=\"{$row4['hauling_segment']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_from" class="form-label">1st Trip - Destination From<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_from" name="destination_from" id="destination_from" placeholder="Select Destination From" required>
                            <datalist id="datalistOptions_destination_from">
                              <?php
                              while ($row5 = mysqli_fetch_assoc($locationResult)) {
                                echo "<option value=\"{$row5['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_to" class="form-label">To<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_to" name="destination_to" id="destination_to" placeholder="Select Destination To" required>
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
                          <hr style="border: 0.5px solid black; margin-top: 20px; margin-bottom: 20px;">
                        </div>

                        <div class="col-md-3">
                          <div class="mb-1">
                            <?php
                            include 'php/config/config.php';

                            $customerQuery1 = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                            $customerResult1 = mysqli_query($conn, $customerQuery1);
                            ?>
                            <label for="costumer2" class="form-label">Trip 2 - Customer<span style="color: red;">*</span></label>
                            <select class="form-control" id="costumer2" name="costumer2" required>
                              <option value="" selected>-- Select Customer --</option>
                              <?php while ($row1 = mysqli_fetch_assoc($customerResult1)) { ?>
                                <option value="<?php echo htmlspecialchars($row1['customer_code']); ?>">
                                  <?php echo htmlspecialchars($row1['customer_code']); ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="container_seal2" class="form-label">Trip 2 - Container Seal</label>
                            <input type="text" class="form-control" id="container_seal2" name="container_seal2" placeholder="Enter Container Seal">

                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_no" class="form-label">Trip 2 - Container No</label>
                            <input type="text" class="form-control" id="container_no2" name="container_no2" placeholder="Enter Container No">
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_status" class="form-label">Trip 2 - Container Status E/L<span style="color: red;">*</span></label>
                            <select class="form-select" id="container_status2" name="container_status2">
                              <option value="" selected>--Select Container Status--</option>
                              <option value="EMPTY">EMPTY</option>
                              <option value="LOADED">LOADED</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="booking_activity2" class="form-label">Trip 2 - Activity<span style="color: red;">*</span></label>
                            <select class="form-select" id="booking_activity2" name="booking_activity2">
                              <option value="" selected>--Select Withdraw/Deliver--</option>
                              <option value="WITHDRAW">WITHDRAW</option>
                              <option value="DELIVER">DELIVER</option>
                              <option value="RETURN">RETURN</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="hauling_segment" class="form-label">Hauling Segment Trip 2(Optional)</label>
                            <input class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment2" id="hauling_segment2" placeholder="Select Hauling Segment">
                            <datalist id="datalistOptions_hauling_segment">
                              <?php
                              while ($row7 = mysqli_fetch_assoc($haulingResult1)) {
                                echo "<option value=\"{$row7['hauling_segment']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_from1" class="form-label">2nd Trip - Destination From<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_from1" name="destination_from1" id="destination_from1" placeholder="Select Destination From" required>
                            <datalist id="datalistOptions_destination_from1">
                              <?php
                              while ($row8 = mysqli_fetch_assoc($locationResult2)) {
                                echo "<option value=\"{$row8['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_to1" class="form-label">To<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_to1" name="destination_to1" id="destination_to1" placeholder="Select Destination To" required>
                            <datalist id="datalistOptions_destination_to1">
                              <?php
                              while ($row9 = mysqli_fetch_assoc($locationResult3)) {
                                echo "<option value=\"{$row9['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <hr style="border: 0.5px solid black; margin-top: 20px; margin-bottom: 20px;">
                        </div>


                        <!-- NEW TRIP -->



                        <div class="col-md-3">
                          <?php
                          include 'php/config/config.php';

                          $customerQuery = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                          $customerResult = mysqli_query($conn, $customerQuery);
                          ?>
                          <div class="mb-1">
                            <label for="costumer3" class="form-label">Trip 3 - Customer<span style="color: red;">*</span></label>
                            <select class="form-control" id="costumer3" name="costumer3" required>
                              <option value="" selected>-- Select Customer --</option>
                              <?php while ($row = mysqli_fetch_assoc($customerResult)) { ?>
                                <option value="<?php echo htmlspecialchars($row['customer_code']); ?>">
                                  <?php echo htmlspecialchars($row['customer_code']); ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="container_seal3" class="form-label">Trip 3 - Container Seal</label>
                            <input type="text" class="form-control" id="container_seal3" name="container_seal3" placeholder="Enter Container Seal">

                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_no3" class="form-label">Trip 3 - Container No</label>
                            <input type="text" class="form-control" id="container_no3" name="container_no3" placeholder="Enter Container No">
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_status3" class="form-label">Trip 3 - Container Status E/L<span style="color: red;">*</span></label>
                            <select class="form-select" id="container_status3" name="container_status3">
                              <option value="" selected>--Select Container Status--</option>
                              <option value="EMPTY">EMPTY</option>
                              <option value="LOADED">LOADED</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="booking_activity3" class="form-label">Trip 3 - Activity<span style="color: red;">*</span></label>
                            <select class="form-select" id="booking_activity3" name="booking_activity3">
                              <option value="" selected>--Select Withdraw/Deliver--</option>
                              <option value="WITHDRAW">WITHDRAW</option>
                              <option value="DELIVER">DELIVER</option>
                              <option value="RETURN">RETURN</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="hauling_segment3" class="form-label">Hauling Segment Trip 3<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_hauling_segment3" name="hauling_segment3" id="hauling_segment3" placeholder="Select Hauling Segment" required>
                            <datalist id="datalistOptions_hauling_segment3">
                              <?php
                              while ($row4 = mysqli_fetch_assoc($haulingResult2)) {
                                echo "<option value=\"{$row4['hauling_segment']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_from3" class="form-label">3rd Trip - Destination From<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_from3" name="destination_from3" id="destination_from3" placeholder="Select Destination From" required>
                            <datalist id="datalistOptions_destination_from3">
                              <?php
                              while ($row5 = mysqli_fetch_assoc($locationResult4)) {
                                echo "<option value=\"{$row5['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_to3" class="form-label">To<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_to3" name="destination_to3" id="destination_to3" placeholder="Select Destination To" required>
                            <datalist id="datalistOptions_destination_to3">
                              <?php
                              while ($row6 = mysqli_fetch_assoc($locationResult5)) {
                                echo "<option value=\"{$row6['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <hr style="border: 0.5px solid black; margin-top: 20px; margin-bottom: 20px;">
                        </div>
                        
                        <div class="col-md-3">
                          <div class="mb-1">
                            <?php
                            include 'php/config/config.php';

                            $customerQuery1 = "SELECT customer_id, customer_code FROM customer ORDER BY customer_id ASC";
                            $customerResult1 = mysqli_query($conn, $customerQuery1);
                            ?>
                            <label for="costumer4" class="form-label">Trip 4 - Customer<span style="color: red;">*</span></label>
                            <select class="form-control" id="costumer4" name="costumer4" required>
                              <option value="" selected>-- Select Customer --</option>
                              <?php while ($row1 = mysqli_fetch_assoc($customerResult1)) { ?>
                                <option value="<?php echo htmlspecialchars($row1['customer_code']); ?>">
                                  <?php echo htmlspecialchars($row1['customer_code']); ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="container_seal4" class="form-label">Trip 4 - Container Seal</label>
                            <input type="text" class="form-control" id="container_seal4" name="container_seal4" placeholder="Enter Container Seal">

                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_no4" class="form-label">Trip 4 - Container No</label>
                            <input type="text" class="form-control" id="container_no4" name="container_no4" placeholder="Enter Container No">
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="container_status4" class="form-label">Trip 4 - Container Status E/L<span style="color: red;">*</span></label>
                            <select class="form-select" id="container_status4" name="container_status4">
                              <option value="" selected>--Select Container Status--</option>
                              <option value="EMPTY">EMPTY</option>
                              <option value="LOADED">LOADED</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-3">
                            <label for="booking_activity4" class="form-label">Trip 4 - Activity<span style="color: red;">*</span></label>
                            <select class="form-select" id="booking_activity4" name="booking_activity4">
                              <option value="" selected>--Select Withdraw/Deliver--</option>
                              <option value="WITHDRAW">WITHDRAW</option>
                              <option value="DELIVER">DELIVER</option>
                              <option value="RETURN">RETURN</option>
                              <option value="N/A">N/A</option>
                            </select>
                          </div>
                        </div>

                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="hauling_segment4" class="form-label">Hauling Segment Trip 4(Optional)</label>
                            <input class="form-control" list="datalistOptions_hauling_segment4" name="hauling_segment4" id="hauling_segment4" placeholder="Select Hauling Segment">
                            <datalist id="datalistOptions_hauling_segment4">
                              <?php
                              while ($row7 = mysqli_fetch_assoc($haulingResult3)) {
                                echo "<option value=\"{$row7['hauling_segment']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_from4" class="form-label">4th Trip - Destination From<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_from4" name="destination_from4" id="destination_from4" placeholder="Select Destination From" required>
                            <datalist id="datalistOptions_destination_from4">
                              <?php
                              while ($row8 = mysqli_fetch_assoc($locationResult6)) {
                                echo "<option value=\"{$row8['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="mb-1">
                            <label for="destination_to4" class="form-label">To<span style="color: red;">*</span></label>
                            <input class="form-control" list="datalistOptions_destination_to4" name="destination_to4" id="destination_to4" placeholder="Select Destination To" required>
                            <datalist id="datalistOptions_destination_to4">
                              <?php
                              while ($row9 = mysqli_fetch_assoc($locationResult7)) {
                                echo "<option value=\"{$row9['location_name']}\">";
                              }
                              ?>
                            </datalist>
                          </div>
                        </div>
                        <div class="col-md-12">
                          <hr style="border: 0.5px solid black; margin-top: 20px; margin-bottom: 20px;">
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="mt-1">
                          <button name="submit" id="addRecord" class="btn btn-primary" style="width: 100%;">Dispatch</button>
                        </div>

                      </div>
                    </form>
                  </div>
                </div>
              </div>


              <?php
              include "php/config/config.php";

              // Count trailers based on status
              $query = "SELECT unit_status, COUNT(*) as count FROM units WHERE unit_name NOT LIKE 'GS%' GROUP BY unit_status";
              $result = mysqli_query($conn, $query);

              // Initialize counts
              $counts = [
                'good' => 0,
                'dispatch' => 0,
                'shop unit' => 0,
                'rescue' => 0
              ];

              while ($row = mysqli_fetch_assoc($result)) {
                $status = strtolower($row['unit_status']);
                if (isset($counts[$status])) {
                  $counts[$status] = $row['count'];
                }
              }
              ?>


              <div class="col-lg-3">
                <div class="row">
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
                            <h5 class="mb-0 fw-bolder fs-4">Truck In Base</h5>
                          </div>
                          <div class="ms-auto">
                            <span class="badge bg-secondary-subtle text-muted"><?= $counts['good'] ?></span>
                          </div>
                        </div>
                        <div class="py-3 d-flex align-items-center">
                          <span class="btn btn-success rounded-circle round-48 hstack justify-content-center">
                            <i class="ti ti-truck-delivery fs-6"></i>
                          </span>
                          <div class="ms-3">
                            <h5 class="mb-0 fw-bolder fs-4">Truck In Trip</h5>
                          </div>
                          <div class="ms-auto">
                            <span class="badge bg-secondary-subtle text-muted"><?= $counts['dispatch'] ?></span>
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
                            <span class="badge bg-secondary-subtle text-muted"><?= $counts['rescue'] ?></span>
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
                            <span class="badge bg-secondary-subtle text-muted"><?= $counts['shop unit'] ?></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="modern-card">
                      <div class="row">
                        <div class="col-md-12">
                          <div class="card-header">
                            <div>
                              <h4>Drivers</h4>
                              <p>List of Present Driver</p>
                            </div>
                            <input type="text" id="driverSearch" placeholder="Search driver...">
                          </div>
                        </div>
                        <div class="col-md-12 m-2">
                          <div class="driver-legend">
                            <span class="legend-item-driver active" data-filter="all">All</span>
                            <span class="legend-item-driver status-not-dispatch" data-filter="not-dispatch">
                              Not Dispatch
                            </span>
                            <span class="legend-item-driver status-dispatch" data-filter="dispatch">
                              Dispatch
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="card-body scroll-area" id="driverListAttend">
                        <!-- realtime drivers here -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>


              <div class="col-lg-12">
                <div class="card">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                        <h4 class="card-title">Transaction</h4>
                        <p class="card-subtitle">
                          Driver and Vehicle Transaction
                        </p>
                      </div>
                    </div>
                    <div class="table-responsive mt-4" style="overflow: hidden;">
                      <table id="table-data" class="table table-hover mb-0 text-nowrap varient-table align-middle fs-3">
                        <thead>
                          <tr>
                            <th class="px-0 text-muted"></th>
                            <th class="px-0 text-muted">Assigned</th>
                            <th class="px-0 text-muted">Dispatch Hub</th>
                            <th class="px-0 text-muted">Date/Time</th>
                            <th class="px-0 text-muted text-center">Trip</th>
                            <th class="px-0 text-muted text-center">Hauling</th>
                            <th class="px-0 text-muted">Dispatched By</th>
                            <th class="px-0 text-muted text-end">Actions</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
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


    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <form id="editForm" action="php/updateRecord.php" method="POST" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="editModalLabel">Dispatch Record</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <input type="hidden" name="record_id" id="edit_record_id">
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label">Drivers Name</label>
                  <input class="form-control" list="datalistOptions_driverName" name="drivers_name1" id="edit_drivers_name" readonly>
                  <datalist id="datalistOptions_driverName"></datalist>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Truck</label>
                  <input class="form-control" list="datalistOptions_truck" name="truck1" id="edit_truck" readonly>
                  <datalist id="datalistOptions_truck"></datalist>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trip Receipt</label>
                  <input type="text" class="form-control" id="edit_tr" name="tr1">
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">ECS</label>
                  <input type="text" class="form-control" id="edit_ecs" name="ecs1">
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trailer</label>
                  <input class="form-control" list="datalistOptions_trailer" name="trailer1" id="edit_trailer" readonly>
                  <datalist id="datalistOptions_trailer"></datalist>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Genset</label>
                  <input class="form-control" list="datalistOptions_genset" name="genset1" id="edit_genset" readonly>
                  <datalist id="datalistOptions_genset"></datalist>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trip 1 - Container No</label>
                  <input type="text" class="form-control" id="edit_container_no" name="container_no1">
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trip 1 - Container Status E/L</label>
                  <input type="text" class="form-control" id="edit_container_status" name="container_status1" list="containerStat">
                </div>

                <div class="col-md-12 mb-2">
                  <label class="form-label">Hauling Segment Trip 1</label>
                  <input class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment1" id="edit_hauling_segment" required>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">1st Trip - Destination From</label>
                  <input class="form-control" list="datalistOptions_destination_from" name="destination_from1" id="edit_destination_from" required>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">To</label>
                  <input class="form-control" list="datalistOptions_destination_to" name="destination_to1" id="edit_destination_to" required>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trip 2 - Container No</label>
                  <input type="text" class="form-control" id="edit_container_no2" name="container_no2">
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Trip 2 - Container Status E/L</label>
                  <input type="text" class="form-control" id="edit_container_status2" name="container_status2" list="containerStat">
                </div>

                <div class="col-md-12 mb-2">
                  <label class="form-label">Hauling Segment Trip 2(Optional)</label>
                  <input class="form-control" list="datalistOptions_hauling_segment" name="hauling_segment2" id="edit_hauling_segment2">
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">2nd Trip - Destination From</label>
                  <input class="form-control" list="datalistOptions_destination_from1" name="destination_from12" id="edit_destination_from1" required>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">To</label>
                  <input class="form-control" list="datalistOptions_destination_to1" name="destination_to12" id="edit_destination_to1" required>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button name="submit" id="printRecord" class="btn btn-success">Print</button>
              <button name="submit" id="editRecord" class="btn btn-primary">Update Dispatch</button>
            </div>

            <!-- Shared Datalist for Status -->
            <datalist id="containerStat">
              <option value="EMPTY">
              <option value="LOADED">
            </datalist>
          </form>
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
    <script src="datatable/datatables.min.js"></script>
    <script src="datatable/dataTables.columnFilter.js"></script>
    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script src="js/dispatch.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        fetchLatestControlNo();
        setInterval(fetchLatestControlNo, 1000);
      });
      async function fetchLatestControlNo() {
        try {
          const response = await fetch('php/fetch/get_latest_control.php');
          const data = (await response.text()).trim();

          let number = 0;

          // If it starts with PTSI-, extract the numeric part
          if (data.startsWith("PTSI-")) {
            number = parseInt(data.slice(5));
          } else {
            number = parseInt(data);
          }

          // If number is invalid or 0, set to 1
          if (isNaN(number) || number === 0) {
            number = 1;
          }

          // Format the control number
          const controlNo = `PTSI-${String(number).padStart(5, '0')}`;
          document.getElementById('booking_no').value = controlNo;

        } catch (error) {
          console.error('Failed to fetch latest control number:', error);
        }
      }


      document.addEventListener("DOMContentLoaded", function() {
        // ===== Driver Search =====
        const driverInput = document.getElementById("driver");
        const driverIdInput = document.getElementById("driverId");
        const driverList = document.getElementById("driverList");
        let allDrivers = [];

        fetch("php/fetch/get_drivers1.php")
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


      // DROP DOWN FUNCTION
      const list = document.getElementById("bookingList");
      const searchInput = document.getElementById("searchInput");
      let allBookings = [];

      let selectedClass = "ui-state-highlight"; // highlight selected items
      let clickDelay = 600; // ms
      let lastClick = 0;


      // DRIVER ATTENDANCE

      function loadDrivers() {
        fetch("php/fetch/fetch_present_drivers.php")
          .then(res => res.json())
          .then(data => {
            const list = document.getElementById("driverListAttend");
            list.innerHTML = "";

            if (data.length === 0) {
              list.innerHTML = "<p class='text-muted'>No present drivers</p>";
              return;
            }

            data.forEach(driver => {
              const timeIn = new Date(driver.da_timeIn).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
              });

              let dispatchInfo = "";
              if (driver.first_dispatch) {
                const dispatchTime = new Date(driver.first_dispatch).toLocaleTimeString([], {
                  hour: '2-digit',
                  minute: '2-digit'
                });
                dispatchInfo = ` | Dispatched: ${dispatchTime}`;
              }

              const isDispatch = driver.has_dispatch == 1;
              const activeClass = isDispatch ? "active" : "";
              const status = isDispatch ? "dispatch" : "not-dispatch";

              list.innerHTML += `
                <div class="driver-card ${activeClass}" 
                    data-name="${driver.driver_name.toLowerCase()}"
                    data-status="${status}">
                    
                    <!-- LEFT: ACTIVE BOOKING COUNT -->
                    <div class="booking-count">
                      ${driver.active_booking_count}
                    </div>

                    <div class="avatar">👤</div>

                    <div>
                      <strong>${driver.driver_name}</strong>
                      <p>Time In: ${timeIn}${dispatchInfo}</p>
                    </div>
                </div>
              `;
            });
          });
      }

      // realtime refresh
      loadDrivers();
      setInterval(loadDrivers, 5000);

      // search
      document.getElementById("driverSearch").addEventListener("keyup", function () {
        const search = this.value.toLowerCase();
        document.querySelectorAll(".driver-card").forEach(card => {
          card.style.display = card.dataset.name.includes(search) ? "flex" : "none";
        });
      });


      document.querySelectorAll(".driver-legend .legend-item-driver").forEach(item => {
        item.addEventListener("click", function () {

          // active state
          document.querySelectorAll(".driver-legend .legend-item-driver")
            .forEach(i => i.classList.remove("active"));
          this.classList.add("active");

          const filter = this.dataset.filter;

          document.querySelectorAll(".driver-card").forEach(card => {
            if (filter === "all") {
              card.style.display = "flex";
            } else {
              card.style.display =
                card.dataset.status === filter ? "flex" : "none";
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
