<?php
include 'php/config/config.php';

$id = $_SESSION['user_id'] ?? 0;
$fname = '';
$firstLetter = '';
if ($id) {
    $stmt = $conn->prepare("SELECT user_fname, user_lname FROM user WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $fname = $row['user_fname'];
        $firstLetter = substr($row['user_lname'], 0, 1);
    }
}
?>
<header class="app-header">
  <nav class="navbar navbar-expand-lg navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link sidebartoggler d-flex align-items-center p-2 rounded" id="headerCollapse" href="javascript:void(0)" title="Menu" aria-label="Toggle sidebar">
          <i class="ti ti-menu-2 fs-5"></i>
        </a>
      </li>
    </ul>
    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
      <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
        <li class="nav-item">
          <span class="badge bg-warning text-dark me-3">Gate Guard</span>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:35px;height:35px;"><?php echo htmlspecialchars($firstLetter); ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
            <div class="message-body">
              <a href="gate-profile" class="d-flex align-items-center gap-2 dropdown-item">
                <i class="ti ti-user fs-6"></i>
                <p class="mb-0 fs-3">My Profile</p>
              </a>
              <a href="gate-logout" class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>
</header>
