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
              <a class="sidebar-link" href="dashboard" aria-expanded="false">
                <i class="ti ti-layout-dashboard"></i>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="dispatchDashboard" aria-expanded="false">
                <i class="ti ti-dashboard"></i>
                <span class="hide-menu">Dispatch Dashboard</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="dispatch" aria-expanded="false">
                <i class="ti ti-truck-delivery"></i>
                <span class="hide-menu">Dispatch</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="false">
                <div class="d-flex align-items-center gap-3">
                  <span class="d-flex">
                    <i class="ti ti-book"></i>
                  </span>
                  <span class="hide-menu">Booking</span>
                </div>
                
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="addbook">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Add Booking</span>
                    </div>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="mybook">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">My Booking</span>
                    </div>
                  </a>
                </li>
                <?php
                    include "php/config/config.php";

                    // Count only active trips with customer = Del Monte
                    $sql5 = "
                        SELECT COUNT(*) AS active_cth 
                        FROM booking WHERE costumer = 'CTH'
                          AND status = 'Active' AND booking_sn != ''
                    ";
                    $result5 = $conn->query($sql5);
                    $row5 = $result5->fetch_assoc();
                    $activeCTH = $row5['active_cth'];
                    ?>
                  <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between" href="cthmonitoring">
                      <div class="d-flex align-items-center gap-3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-circle"></i>
                        </div>
                        <span class="hide-menu">CTH Booking</span>
                      </div>
                      
                      <?php if ($activeCTH > 0): ?>
                        <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeCTH ?></p></span>
                      <?php endif; ?>
                    </a>
                  </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="monitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">All Booking Monitoring</span>
                    </div>
                    
                  </a>
                </li>
                <?php
                  include "php/config/config.php";

                  // Count only active trips with customer = ABC
                  $sql = "
                      SELECT COUNT(*) AS active_abc 
                      FROM trips t
                      INNER JOIN dispatch d ON d.d_id = t.d_id
                      WHERE (d.costumer = 'ABC' OR t.costumer = 'ABC')
                        AND t.trip_status = 'Active'
                  ";
                  $result = $conn->query($sql);
                  $row = $result->fetch_assoc();
                  $activeABC = $row['active_abc'];
                  ?>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between" href="abcmonitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">ABC Monitoring</span>
                    </div>
                    
                    <?php if ($activeABC > 0): ?>
                      <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeABC ?></p></span>
                    <?php endif; ?>
                  </a>
                </li>
                <?php
                  include "php/config/config.php";

                  // Count only active trips with customer = DOLE
                  $sql1 = "
                      SELECT COUNT(*) AS active_dole 
                      FROM trips t
                      INNER JOIN dispatch d ON d.d_id = t.d_id
                      WHERE (d.costumer = 'DOLE' OR t.costumer = 'DOLE')
                        AND t.trip_status = 'Active'
                  ";
                  $result1 = $conn->query($sql1);
                  $row1 = $result1->fetch_assoc();
                  $activeDOLE = $row1['active_dole'];
                  ?>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between" href="dolemonitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">DOLE Monitoring</span>
                    </div>
                    
                    <?php if ($activeDOLE > 0): ?>
                      <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeDOLE ?></p></span>
                    <?php endif; ?>
                  </a>
                </li>

                <?php
                  include "php/config/config.php";

                  // Count only active trips with customer = Del Monte
                  $sql2 = "
                      SELECT COUNT(*) AS active_dm 
                      FROM trips t
                      INNER JOIN dispatch d ON d.d_id = t.d_id
                      WHERE (d.costumer = 'DM' OR t.costumer = 'DM')
                        AND t.trip_status = 'Active'
                  ";
                  $result2 = $conn->query($sql2);
                  $row2 = $result2->fetch_assoc();
                  $activeDM = $row2['active_dm'];
                  ?>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between" href="dmmonitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">DM Monitoring</span>
                    </div>
                    
                    <?php if ($activeDM > 0): ?>
                      <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeDM ?></p></span>
                    <?php endif; ?>
                  </a>
                </li>

                <?php
                  include "php/config/config.php";

                  // Count only active trips with customer = Del Monte
                  $sql3 = "
                      SELECT COUNT(*) AS active_farm 
                      FROM trips t
                      INNER JOIN dispatch d ON d.d_id = t.d_id
                      WHERE (d.costumer = 'FARM' OR t.costumer = 'FARM')
                        AND t.trip_status = 'Active'
                  ";
                  $result3 = $conn->query($sql3);
                  $row3 = $result3->fetch_assoc();
                  $activeFARM = $row3['active_farm'];
                  ?>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between" href="farmmonitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">FARM Monitoring</span>
                    </div>
                    
                    <?php if ($activeFARM > 0): ?>
                      <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeFARM ?></p></span>
                    <?php endif; ?>
                  </a>
                </li>

                <?php
                  include "php/config/config.php";

                  // Count only active trips with customer = Del Monte
                  $sql4 = "
                      SELECT COUNT(*) AS active_sumi 
                      FROM trips t
                      INNER JOIN dispatch d ON d.d_id = t.d_id
                      WHERE (d.costumer = 'SUMI' OR t.costumer = 'SUMI')
                        AND t.trip_status = 'Active'
                  ";
                  $result4 = $conn->query($sql4);
                  $row4 = $result4->fetch_assoc();
                  $activeSUMI = $row4['active_sumi'];
                  ?>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between" href="sumimonitoring">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">SUMI Monitoring</span>
                    </div>
                    
                    <?php if ($activeSUMI > 0): ?>
                      <span class="badge bg-primary rounded-pill"><p style="margin-bottom: -3px;"><?= $activeSUMI ?></p></span>
                    <?php endif; ?>
                  </a>
                </li>
              </ul>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="gate" aria-expanded="false">
                <i class="ti ti-table"></i>
                <span class="hide-menu">Gate</span>
              </a>
            </li>
            
            <li class="sidebar-item">
              <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="false">
                <div class="d-flex align-items-center gap-3">
                  <span class="d-flex">
                    <i class="ti ti-truck"></i>
                  </span>
                  <span class="hide-menu">Units</span>
                </div>
                
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="truck">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Truck</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="trailer">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Trailer</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="genset">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Genset</span>
                    </div>
                    
                  </a>
                </li>
              </ul>
            </li>

            <li class="sidebar-item">
              <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="false">
                <div class="d-flex align-items-center gap-3">
                  <span class="d-flex">
                    <i class="ti ti-users"></i>
                  </span>
                  <span class="hide-menu">Account</span>
                </div>
                
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="user">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">User</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="drivers">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Driver</span>
                    </div>
                    
                  </a>
                </li>
              </ul>
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
                    href="tripReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Trip Report</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="truckReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Truck Movement</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="trailerReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Trailer Movement</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item">
                  <a class="sidebar-link justify-content-between"  
                    href="attendReport">
                    <div class="d-flex align-items-center gap-3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">Attendance</span>
                    </div>
                    
                  </a>
                </li>
                <li class="sidebar-item" hidden>
                  <a class="sidebar-link justify-content-between"  
                    href="driversReport">
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

            <li class="sidebar-item">
              <a class="sidebar-link" href="bookingSegments" aria-expanded="false">
                <i class="ti ti-route"></i>
                <span class="hide-menu">Booking Segments</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="segment" aria-expanded="false">
                <i class="ti ti-list-details"></i>
                <span class="hide-menu">Segment/location</span>
              </a>
            </li>
          </ul>
        </nav>
        <!-- End Sidebar navigation -->
      </div>
      <!-- End Sidebar scroll-->
    </aside>
