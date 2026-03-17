<aside class="left-sidebar">
      <!-- Sidebar scroll-->
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
          <a href="#" class="text-nowrap logo-img">
            <img src="assets/images/logos/pantrucks.png" alt="" style="width: 200px;"/>
          </a>
          <div class="d-flex align-items-center gap-1">
            <button type="button" class="sidebar-toggle-btn btn btn-link text-dark d-none d-xl-inline-flex p-2 rounded sidebartoggler" id="sidebarCollapseDesktop" title="Hide sidebar" aria-label="Hide sidebar">
              <i class="ti ti-panel-left-close fs-5"></i>
            </button>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer p-2" id="sidebarCollapse" title="Close menu" aria-label="Close menu">
              <i class="ti ti-x fs-6"></i>
            </div>
          </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">

          <ul id="sidebarnav">
            <li class="nav-small-cap">
              <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
              <span class="hide-menu">Home</span>
            </li> 
            <li class="sidebar-item">
              <a class="sidebar-link" href="driver-dashboard" aria-expanded="false">
                <i class="ti ti-atom"></i>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="driver-unit" aria-expanded="false">
                <i class="ti ti-truck"></i>
                <span class="hide-menu">Assign Unit</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="false">
                <div class="d-flex align-items-center gap-3">
                  <span class="d-flex">
                    <i class="ti ti-report"></i>
                  </span>
                  <span class="hide-menu">Report</span>
                </div>
                
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="driver-tripReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Trip Report</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item" hidden>
                  <a class="sidebar-link justify-content-between"  
                    href="driver-driversReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Driver's Report</span>
                    </div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="sidebar-item mt-6">
             <button type="button" class="btn btn-danger" style="width: 100%;">SOS</button>
            </li>
          </ul>
        </nav>
        <!-- End Sidebar navigation -->
      </div>
      <!-- End Sidebar scroll-->
    </aside>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-nav">
      <a href="driver-dashboard" class="nav-item <?php echo ($_GET['route'] ?? '') == 'dashboard' ? 'active' : ''; ?>">
        <i class="ti ti-smart-home" style="font-size: 30px;"></i>
        <span>Dashboard</span>
      </a>

      <a href="driver-unit" class="nav-item <?php echo ($_GET['route'] ?? '') == 'unit' ? 'active' : ''; ?>">
        <i class="ti ti-truck" style="font-size: 30px;"></i>
        <span>Unit</span>
      </a>

      <a href="driver-tripReport" class="nav-item <?php echo ($_GET['route'] ?? '') == 'tripReport' ? 'active' : ''; ?>">
        <i class="ti ti-clipboard-list" style="font-size: 30px;"></i>
        <span>Report</span>
      </a>

      <button onclick="sosAlert()" class="nav-item sos-btn">
        <input type="hidden" class="id" id="user_Id1" name="user_Id1" value="<?php echo $_SESSION['user_id']; ?>">
        <iconify-icon icon="solar:danger-triangle-linear"></iconify-icon>
        <span>SOS</span>
      </button>
    </div>

<script>
  function sosAlert() {
    Swal.fire({
      title: '🚨 SOS Alert',
      text: 'Do you want to send an SOS request?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, Send SOS'
    }).then((result) => {
      if (result.isConfirmed) {
        let driver_id = document.getElementById("user_Id1").value;

        // Send to backend
        $.ajax({
            url: "php/send_sos.php",
            type: "POST",
            data: {
                driver_id: driver_id
            },
            success: function(response) {
                Swal.fire('SOS Sent!', response, 'success');
            },
            error: function() {
                Swal.fire('Error', 'Failed to send SOS.', 'error');
            }
        });
      }
    });
}
</script>
