<?php
include 'php/config/config.php';

$id = $_SESSION['user_id'];
$query = "SELECT * FROM drivers WHERE driver_id = '$id'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
$fname = $data['driver_fname'];
$firstLetter = substr($data['driver_lname'], 0, 1);

?>
<header class="app-header">
        <nav class="navbar navbar-expand-lg navbar-light">
          <ul class="navbar-nav" id="menu">
            <li class="nav-item">
              <a class="nav-link sidebartoggler d-flex align-items-center p-2 rounded" id="headerCollapse" href="javascript:void(0)" title="Menu" aria-label="Toggle sidebar">
                <i class="ti ti-menu-2 fs-5"></i>
              </a>
            </li>
            
          </ul>
          <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
              
              <li class="nav-item dropdown">
                <a class="nav-link " href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                  aria-expanded="false">
                  <img src="assets/images/profile/user-1.jpg" alt="" width="35" height="35" class="rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                  <div class="message-body">
                    <a href="driver-profile" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-user fs-6"></i>
                      <p class="mb-0 fs-3">My Profile</p>
                    </a>
                    <a href="driver-logout" class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </nav>
      </header>
