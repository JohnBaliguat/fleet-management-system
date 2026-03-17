<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Driver") {


?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="datatable/datatables.min.css">
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
      <?php include 'navbar.php';
       include "navbar.php";
      $id = $_SESSION['user_id'];
      $query = "SELECT * FROM drivers WHERE driver_id = '$id'";
      $result = mysqli_query($conn, $query);
      $data = mysqli_fetch_assoc($result);
      $fname = $data['driver_fname'];
      $firstLetter = substr($data['driver_lname'], 0, 1);
     ?>
      
      <!--  Header End -->
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <!--  Row 1 -->
          <div class="row">
            <div class="col-lg-4">
              <div class="card overflow-hidden">
                <div class="card-body pb-0">
                  <div class="card">
                    <div class="profile-image">
                    <img src="php/assets/uploads/<?php if (!empty($data['driver_image'])) {
                                              echo $data['driver_image'];
                                            } else {
                                              echo "user-profile.jpg";
                                            } ?>" class="card-img-top" alt="...">
                      <div class="update-image">
                        <input type="file" />
                        <label for="input-file1" class="profileImage"><i class='ti ti-cloud-upload'></i></label>
                      </div>
                      </div>
                    <div class="card-body text-center">
                      <h5 class="card-title"><?php echo $data['driver_fname'] . ' ' . $data['driver_mname'] . '. ' . $data['driver_lname']; ?></h5>
                      <p class="card-text">Driver</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-8 mb-5">
               <div class="card">
                <div class="card-body">
                  <div class="d-md-flex align-items-center">
                    <div>
                      <h4 class="card-title">User Information</h4>
                    </div>
                  </div>
                    <div class="row">
                      <form class="form" id="editFormDriver" action="../php/update-driverprofile.php" method="POST" enctype="multipart/form-data">
                        <div class="row">

                          <input type="file" accept="image/jpeg, image/png, image/jpg" id="input-file1" name="input-file1" hidden>
                          <input type="hidden" class="id" id="user_Id1" name="user_Id1" value="<?php echo $_SESSION['user_id']; ?>">
                          <div class="col-md-12 mb-3">
                            <div class="input-style-1">
                              <label for="name">Username</label>
                              <input type="text" class="form-control" name="username1" id="username1" value="<?php echo $data['driver_uname']; ?>">
                            </div>
                          </div>
                          
                          <div class="col-md-5 mb-3">
                            <div class="input-style-1">
                              <label for="fname">First Name</label>
                              <input type="text" class="form-control" name="user_fname1" id="user_fname1" placeholder="Enter First Name" value="<?php echo $data['driver_fname']; ?>">
                            </div>
                          </div>
                          <div class="col-md-5 mb-3">
                            <div class="input-style-1">
                              <label for="lname">Last Name</label>
                              <input type="text" class="form-control" name="user_lname1" id="user_lname1" placeholder="Enter Last Name" value="<?php echo $data['driver_lname']; ?>">
                            </div>
                          </div>
                          <div class="col-md-2 mb-3">
                            <div class="input-style-1">
                              <label for="lname">Middle Initial</label>
                              <input type="text" class="form-control" name="user_mname1" id="user_mname1" placeholder="Enter Middle Initial" value="<?php echo $data['driver_mname'] ? $data['driver_mname'] : ""; ?>">
                            </div>
                          </div>

                          <div class="col-md-6 mb-3">
                            <div class="input-style-1">
                              <label for="newPassword">New Password</label>
                              <input type="password" class="form-control" name="user_pass1" id="user_pass1" placeholder="Enter New Password">
                            </div>
                          </div>
                          <div class="col-md-6 mb-3">
                            <div class="input-style-1">
                              <label for="conPassword">Repeat New Password</label>
                              <input type="password" class="form-control" id="conPassword" placeholder="Repeat New Password">
                            </div>
                          </div>
                          <div class="col-md-12 mb-3">
                            <div class="modal-footer">
                              <button class="btn btn-primary" name="submit" id="updateDriver">Update</button>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
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
  <script src="datatable/datatables.min.js"></script>
  <!-- solar icons -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script>
    $(document).ready(function (){
      $('#editFormDriver').submit(function (e) {
        e.preventDefault(); // Prevent default form submission

        var formData = new FormData($(this)[0]); // Correctly get form data
        var user_pass1 = $('#user_pass1').val();
        var conPassword = $('#conPassword').val();
        if (user_pass1 !== '' && user_pass1 !== conPassword) {
            Swal.fire({
                text: 'Passwords do not match!',
                icon: 'error'
            });
            return; // Exit function if passwords don't match
        }
        Swal.fire({
            title: 'Confirm Update Profile',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php/update-driverprofile.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        Swal.fire({
                            text: data,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1200
                        });
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
    header("Location: driver-index.php?route=login");
    exit();
}
