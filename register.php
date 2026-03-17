<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div
      class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center justify-content-center w-100">
        <div class="row justify-content-center w-100">
          <div class="col-md-8 col-lg-6 col-xxl-3">
            <div class="card mb-0">
              <div class="card-body">
                <a href="login" class="text-nowrap logo-img text-center d-block py-3 w-100">
                  <img src="assets/images/logos/pantrucks1.png" style="width: 300px;" alt="">
                </a>
                <form action="checkID.php" method="post" id="checkId">
                    <p class="text-center">Enter Your Company Id</p>
                  <div class="mb-3">
                    <input type="text" class="form-control" id="driver_id" name="driver_id" required>
                  </div>
                  <button type="submit" name="login-btn" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">Create</button>
                  <div class="d-flex align-items-center justify-content-center">
                    <p class="fs-4 mb-0 fw-bold">Already have an Account?</p>
                    <a class="text-primary fw-bold ms-2" href="login.php">Sign In</a>
                  </div>
                </form>

                <form id="register" style="display: none;">
                    <p class="text-center">Create Your Account</p>
                    <input type="hidden" id="driver_id" name="driver_id" required>

                    <div class="mb-3">
                        <label for="uname" class="form-label">Username</label>
                        <input type="text" class="form-control" id="uname" name="uname" required>
                    </div>

                    <div class="mb-4">
                        <label for="pass" class="form-label">Password</label>
                        <input type="password" class="form-control" id="pass" name="pass" required>
                    </div>

                    <div class="mb-4">
                        <label for="conpass" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="conpass" name="conpass" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fs-4 mb-4 rounded-2">Sign Up</button>
                    <div class="d-flex align-items-center justify-content-center">
                        <p class="fs-4 mb-0 fw-bold">Already have an Account?</p>
                        <a class="text-primary fw-bold ms-2" href="login">Sign In</a>
                    </div>
                    </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
  <!-- solar icons -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="js/register.js"></script>
</body>

</html>
