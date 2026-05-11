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

  <?php
    // JS deps now load in _layout_top.php (head) so inline page-body
    // scripts can use $ / Swal. Only PWA-specific bits stay here.
    $vapidPublicKey = '';
    $vapidFile = __DIR__ . '/../php/config/vapid.php';
    if (file_exists($vapidFile)) { @include $vapidFile; if (defined('VAPID_PUBLIC_KEY')) $vapidPublicKey = VAPID_PUBLIC_KEY; }
  ?>
  <?php if ($vapidPublicKey !== ''): ?>
  <script>window.PT_VAPID_PUBLIC_KEY = <?php echo json_encode($vapidPublicKey); ?>;</script>
  <?php endif; ?>
  <script src="driver/pwa-register.js"></script>
  <?php include __DIR__ . '/../php/assets/realtime_alerts.php'; ?>
</body>
</html>
