<?php
include 'php/config/config.php';

$id = $_SESSION['user_id'];
$query = "SELECT * FROM drivers WHERE driver_id = '$id'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
$fname = $data['driver_fname'];
$firstLetter = substr($data['driver_lname'], 0, 1);
$fullName = $data['driver_fname'] . ' ' . $data['driver_lname'];
$profileImage = !empty($data['driver_image']) ? 'php/assets/uploads/' . $data['driver_image'] : 'assets/images/profile/user-1.jpg';
?>

<!-- Modern Glassmorphism Navbar -->
<header class="modern-navbar">
  <div class="navbar-container">
    
    <!-- Left: Menu Toggle -->
    <!-- <button class="menu-toggle" id="headerCollapse" aria-label="Toggle menu">
      <i class="ti ti-menu-2"></i>
    </button> -->

    <!-- Center: Page Title (Mobile) / Breadcrumbs (Desktop) -->
    <div class="navbar-brand">
      <span class="brand-text d-none d-lg-block">Pantrucks Fleet</span>
      <span class="page-title d-lg-none" id="pageTitle">Dashboard</span>
    </div>

    <!-- Right: Actions -->
    <div class="navbar-actions">
      
      <!-- Notifications -->
      <button class="action-btn" id="notifToggle" aria-label="Notifications">
        <i class="ti ti-bell"></i>
        <span class="notif-badge" id="notifCount">0</span>
      </button>

      <!-- Profile Dropdown -->
      <div class="profile-dropdown">
        <button class="profile-trigger" id="profileToggle" aria-label="User menu">
          <img src="<?= $profileImage ?>" alt="<?= $fname ?>" class="avatar">
          <div class="user-info d-none d-md-block">
            <span class="user-name"><?= $fname ?> <?= $firstLetter ?>.</span>
            <span class="user-role">Driver</span>
          </div>
          <i class="ti ti-chevron-down ms-2 d-none d-md-block"></i>
        </button>

        <!-- Dropdown Menu -->
        <div class="dropdown-menu-modern" id="profileMenu">
          <div class="dropdown-header">
            <img src="<?= $profileImage ?>" alt="" class="dropdown-avatar">
            <div>
              <strong><?= $fullName ?></strong>
              <small>Driver ID: <?= $id ?></small>
            </div>
          </div>
          
          <div class="dropdown-body">
            <a href="driver-profile" class="dropdown-item">
              <i class="ti ti-user"></i>
              <span>My Profile</span>
              <i class="ti ti-chevron-right ms-auto"></i>
            </a>
            <a href="driver-unit" class="dropdown-item">
              <i class="ti ti-truck"></i>
              <span>My Units</span>
              <i class="ti ti-chevron-right ms-auto"></i>
            </a>
            <a href="driver-tripReport" class="dropdown-item">
              <i class="ti ti-clipboard-list"></i>
              <span>Trip Reports</span>
              <i class="ti ti-chevron-right ms-auto"></i>
            </a>
          </div>
          
          <div class="dropdown-footer">
            <a href="driver-logout" class="btn-logout">
              <i class="ti ti-logout"></i>
              <span>Sign Out</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Mobile Search/Filter Bar (Optional) -->
  <div class="mobile-search-bar d-lg-none" id="mobileSearch">
    <div class="search-input-wrapper">
      <i class="ti ti-search"></i>
      <input type="text" placeholder="Search trips, bookings..." id="globalSearch">
    </div>
  </div>
</header>

<!-- Notifications Panel (Slide-over) -->
<div class="notif-panel" id="notifPanel">
  <div class="notif-header">
    <h6>Notifications</h6>
    <button class="btn-close-modern" id="closeNotif">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <div class="notif-list" id="notifList">
    <div class="notif-empty">
      <i class="ti ti-bell-off"></i>
      <p>No new notifications</p>
    </div>
  </div>
</div>

<!-- Overlay for mobile -->
<div class="navbar-overlay" id="navOverlay"></div>


<script>
// Toggle Profile Dropdown
document.getElementById('profileToggle').addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('profileMenu').classList.toggle('show');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.profile-dropdown')) {
    document.getElementById('profileMenu').classList.remove('show');
  }
});

// Notifications Panel
document.getElementById('notifToggle').addEventListener('click', function() {
  document.getElementById('notifPanel').classList.add('show');
  document.getElementById('navOverlay').classList.add('show');
});

document.getElementById('closeNotif').addEventListener('click', function() {
  document.getElementById('notifPanel').classList.remove('show');
  document.getElementById('navOverlay').classList.remove('show');
});

document.getElementById('navOverlay').addEventListener('click', function() {
  document.getElementById('notifPanel').classList.remove('show');
  document.getElementById('navOverlay').classList.remove('show');
});

// Fetch notifications count (optional)
function fetchNotifications() {
  $.getJSON('php/get_notifications.php', { driver_id: '<?= $id ?>' }, function(data) {
    const badge = document.getElementById('notifCount');
    if (data.count > 0) {
      badge.textContent = data.count > 99 ? '99+' : data.count;
      badge.classList.add('has-count');
    }
  });
}

// Update page title based on current page
const pageTitles = {
  'driver-dashboard': 'Dashboard',
  'driver-unit': 'My Units',
  'driver-tripReport': 'Trip Reports',
  'driver-profile': 'My Profile'
};

const currentPage = window.location.pathname.split('/').pop() || 'driver-dashboard';
document.getElementById('pageTitle').textContent = pageTitles[currentPage] || 'Pantrucks';
</script>
