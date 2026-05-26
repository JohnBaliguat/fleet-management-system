<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PTSI Login – Pantrucks Fleet Management</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
</head>

<body>
  <div class="page-wrapper login-card-enhance" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6"
    data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center py-4">
      <div class="d-flex align-items-center justify-content-center w-100">
        <div class="row justify-content-center w-100 g-0">
          <div class="col-11 col-sm-10 col-md-8 col-lg-5 col-xxl-4">
            <div class="card mb-0 shadow-sm">
              <div class="card-body">
                <a href="login" class="text-nowrap logo-img text-center d-block py-4 w-100">
                  <img src="assets/images/logos/pantrucks1.png" class="img-fluid" style="max-width: 280px; height: auto;" alt="Pantrucks" />
                </a>
                <p class="text-center text-muted mb-4 small text-uppercase tracking-wide">Pantrucks Fleet Management</p>
                <form action="login-php.php" method="post">
                  <?php if (isset($_GET['error'])) { ?>
                    <div class="alert alert-danger mb-3 py-3" role="alert">
                      <p class="mb-0 small"><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                  <?php } ?>
                  <div class="mb-3">
                    <label for="uname" class="form-label">Username</label>
                    <input type="text" class="form-control" id="uname" name="uname" placeholder="Enter your username" required autocomplete="username">
                  </div>
                  <div class="mb-4">
                    <label for="pass" class="form-label">Password</label>
                    <input type="password" class="form-control" id="pass" name="pass" placeholder="Enter your password" required autocomplete="current-password">
                  </div>
                  <button type="submit" name="login-btn" class="btn btn-primary w-100 py-3 fs-6 fw-semibold mb-3">Sign In</button>
                </form>

                <div class="d-flex align-items-center my-3">
                  <div class="flex-grow-1 border-top"></div>
                  <span class="px-3 text-muted small text-uppercase">or</span>
                  <div class="flex-grow-1 border-top"></div>
                </div>

                <a href="ms-login" class="btn btn-outline-dark w-100 py-3 fw-semibold mb-4 d-flex align-items-center justify-content-center gap-2">
                  <!-- Inline Microsoft 4-square logo (no external dependency). -->
                  <svg width="20" height="20" viewBox="0 0 23 23" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="1"  y="1"  width="10" height="10" fill="#F35325"/>
                    <rect x="12" y="1"  width="10" height="10" fill="#81BC06"/>
                    <rect x="1"  y="12" width="10" height="10" fill="#05A6F0"/>
                    <rect x="12" y="12" width="10" height="10" fill="#FFBA08"/>
                  </svg>
                  Sign in with Microsoft work account
                </a>

                <div class="text-center pt-2">
                  <span class="text-muted small">No account yet?</span>
                  <a class="small fw-semibold ms-1 text-primary text-decoration-none" href="register">Create an account</a>
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
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>
