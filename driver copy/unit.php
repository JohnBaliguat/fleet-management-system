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
    <link rel="stylesheet" href="assets/css/driver.css"/>
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
          <div class="container-fluid mb-5">
            <div class="row g-3 mb-5">
              <div class="col-12">
                <div class="product-card variant-pale">
                  <div class="thumb">
                    <img src="assets/images/profile/primemover.png" alt="Truck">
                  </div>
                  <div class="content">
                    <input type="hidden" id="driverID" value="<?php echo $_SESSION['user_id']; ?>">
                    <h5 id="truckName">Truck Name</h5>
                    <p id="truckDetails">Truck important Details</p>
                  </div>
                  <div class="meta">
                    <p id="truckKm">Total Km Run Ex.(0 km)</p>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="product-card variant-rose soft-border">
                  <div class="thumb">
                    <img src="assets/images/profile/generator.png" alt="Genset">
                  </div>
                  <div class="content">
                    <h5 id="gensetName">Genset Name</h5>
                    <p id="gensetDetails">Genset important Details</p>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="product-card variant-blue soft-border">
                  <div class="thumb">
                    <img src="assets/images/profile/trailers.png" alt="Trailer">
                  </div>
                  <div class="content">
                    <h5 id="trailerName">Trailer Name</h5>
                    <p id="trailerDetails">Trailer important Details</p>
                  </div>
                  <div class="meta" id="trailerMeta">
                  </div>
                </div>
              </div>
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
    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script>
      function fetchDriverUnits() {
      var driverID = $("#driverID").val();

      $.ajax({
        url: "php/fetch/fetch_units.php",
        method: "GET",
        data: { driver_id: driverID },
        dataType: "json",
        success: function(data) {
          // Reset cards
          $("#truckName").text("No Truck Assigned");
          $("#truckDetails").text("");
          $("#truckKm").text("Total Km Run Ex.(0 km)");
          $("#trailerName").text("No Trailer Assigned");
          $("#trailerDetails").text("");

          // Update cards
          data.truck.forEach(function(unit) {
            $("#truckName").text(unit.unit_name);
            $("#truckDetails").text(
              "Plate: " + unit.unit_plate +
              (unit.genset_name ? " | Genset: " + unit.genset_name + " (" + unit.genset_status + ")" : "")
            );
            $("#truckKm").text((unit.total_km ?? 0) + " km");
          });

          data.trailer.forEach(function(tr) {
            $("#trailerName").text(tr.trailer_name);
            $("#trailerDetails").text("Plate: " + tr.trailer_plateNo + " | Status: " + tr.trailer_status);
            $("#trailerMeta").html('<button id="pulloutBtn" class="buy-btn bg-primary" data-id="' + tr.trailer_id + '">Drop Trailer</button>');
          });
        }
      });
    }

    // Fetch immediately and then every 5 seconds
    fetchDriverUnits();
    setInterval(fetchDriverUnits, 5000);

    $(document).on("click", "#pulloutBtn", function() {
        let trailerId = $(this).data("id");

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to Drop this trailer?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Pullout"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "php/operations/pullout_trailer.php",
                    method: "POST",
                    data: { trailer_id: trailerId },
                    success: function(response) {
                        if (response.trim() === "success") {
                            Swal.fire("Pulled Out!", "Trailer has been removed.", "success");
                            fetchDriverUnits(); // refresh cards
                        } else {
                            Swal.fire("Error!", "Something went wrong: " + response, "error");
                        }
                    }
                });
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
