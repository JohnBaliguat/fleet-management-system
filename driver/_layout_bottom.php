      </div><!-- /.container-fluid -->
    </div><!-- /.body-wrapper -->

    <div class="mobile-nav">
      <a href="driver-dashboard" class="nav-item <?php echo ($activeNav ?? '') === 'home' ? 'active' : ''; ?>">
        <i class="ti ti-smart-home"></i><span>Home</span>
      </a>
      <a href="driver-checklist" class="nav-item <?php echo ($activeNav ?? '') === 'checklist' ? 'active' : ''; ?>">
        <i class="ti ti-list-check"></i><span>Checklist</span>
      </a>
      <a href="driver-messages" class="nav-item <?php echo ($activeNav ?? '') === 'messages' ? 'active' : ''; ?>">
        <i class="ti ti-message-circle"></i><span>Chat</span>
      </a>
      <a href="driver-breakdown" class="nav-item <?php echo ($activeNav ?? '') === 'breakdown' ? 'active' : ''; ?>" style="color:#dc3545;">
        <i class="ti ti-alert-triangle"></i><span>SOS</span>
      </a>
    </div>
  </div>

  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <?php
    $vapidPublicKey = '';
    $vapidFile = __DIR__ . '/../php/config/vapid.php';
    if (file_exists($vapidFile)) { @include $vapidFile; if (defined('VAPID_PUBLIC_KEY')) $vapidPublicKey = VAPID_PUBLIC_KEY; }
  ?>
  <?php if ($vapidPublicKey !== ''): ?>
  <script>window.PT_VAPID_PUBLIC_KEY = <?php echo json_encode($vapidPublicKey); ?>;</script>
  <?php endif; ?>
  <script src="driver/pwa-register.js"></script>
</body>
</html>
