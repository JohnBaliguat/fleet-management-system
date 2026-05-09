<?php
// Shared body for the dispatcher Trip Verification page.
// Required vars: $role.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trip Verification</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
  <style>
    .pod-photo { width:100%; height:200px; object-fit:cover; border-radius:8px; border:1px solid #ddd; cursor:zoom-in; background:#f8f9fa; }
    .sig-img   { width:100%; max-height:120px; object-fit:contain; border:1px solid #ddd; border-radius:8px; background:#fff; }
  </style>
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
       data-sidebar-position="fixed" data-header-position="fixed">
    <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-5"><img src="assets/images/logos/pantrucks.png" width="122" alt=""></div>
      <h3 class="text-white mb-0 fs-5">Trip Verification</h3>
    </div>

    <?php include __DIR__ . '/../../' . $role . '/sidebar.php'; ?>

    <div class="body-wrapper">
      <?php include __DIR__ . '/../../' . $role . '/navbar.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="card mt-3"><div class="card-body">
            <div class="d-md-flex align-items-center mb-3">
              <div>
                <h4 class="card-title mb-0">Trip Verification</h4>
                <p class="card-subtitle">Drivers' POD submissions awaiting your confirmation. Review the location, photos, and signature, then Verify (completes the trip) or Reject (sends them back to re-capture).</p>
              </div>
              <div class="ms-auto">
                <button class="btn btn-sm btn-outline-secondary" id="refreshBtn"><i class="ti ti-refresh"></i></button>
              </div>
            </div>
            <div id="cardsBox"><div class="text-muted">Loading…</div></div>
          </div></div>
          <div class="py-6 px-6 text-center"><p class="mb-0 fs-4">Design and Developed by JA Baliguat | 2025</p></div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/sidebarmenu.js"></script>
  <script src="assets/js/app.min.js"></script>
  <script src="assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="alert/node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script>
  function escapeHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
  function photoCell(path) {
    if (!path) return '<div class="pod-photo d-flex align-items-center justify-content-center text-muted">No photo</div>';
    return '<a href="' + escapeHtml(path) + '" target="_blank"><img class="pod-photo" src="' + escapeHtml(path) + '"></a>';
  }
  function gpsLink(lat, lng) {
    if (!lat || !lng) return '<span class="text-muted">No GPS captured</span>';
    return '<a href="https://www.google.com/maps?q=' + encodeURIComponent(lat) + ',' + encodeURIComponent(lng) + '" target="_blank"><i class="ti ti-map-pin"></i> ' + escapeHtml(lat) + ', ' + escapeHtml(lng) + '</a>';
  }
  function tripDuration(min) {
    if (!min || min < 0) return '<span class="text-muted">—</span>';
    const h = Math.floor(min / 60), m = min % 60;
    return (h ? h + 'h ' : '') + m + 'm';
  }
  function loadPending() {
    $.getJSON('php/fetch/pending_verifications.php', function (res) {
      if (res.status !== 'success') { $('#cardsBox').html('<div class="alert alert-danger">' + escapeHtml(res.message) + '</div>'); return; }
      if (!res.rows.length) { $('#cardsBox').html('<div class="alert alert-success mb-0">Nothing pending. All verified ✓</div>'); return; }
      const html = res.rows.map(function (r) {
        const photos = '<div class="row g-2">'
          + '<div class="col-md-4">' + photoCell(r.photo1_path) + '</div>'
          + '<div class="col-md-4">' + photoCell(r.photo2_path) + '</div>'
          + '<div class="col-md-4">' + photoCell(r.photo3_path) + '</div>'
          + '</div>';
        const sig = r.signature_path
          ? '<img class="sig-img" src="' + escapeHtml(r.signature_path) + '">'
          : '<div class="text-muted">No signature</div>';
        return '<div class="card mb-3" data-d-id="' + r.d_id + '"><div class="card-body">'
          + '<div class="d-flex align-items-center mb-2 flex-wrap gap-2">'
          +   '<h5 class="mb-0">' + escapeHtml(r.booking_no || '?') + '</h5>'
          +   '<span class="badge bg-warning text-dark">Pending Verification</span>'
          +   '<span class="text-muted small ms-auto">'
          +     'Driver: ' + escapeHtml(r.d_driverName || '-') + ' &middot; Truck: ' + escapeHtml(r.d_truck || '-') + ' &middot; Customer: ' + escapeHtml(r.costumer || '-')
          +   '</span>'
          + '</div>'
          + '<div class="row g-3">'
          +   '<div class="col-md-8">' + photos + '</div>'
          +   '<div class="col-md-4">'
          +     '<div class="mb-2"><strong>Signature</strong>' + sig + '</div>'
          +     '<div><strong>Signed by:</strong> ' + escapeHtml(r.signed_by || '—') + '</div>'
          +     '<div><strong>POD location:</strong> ' + gpsLink(r.lat, r.lng) + '</div>'
          +     '<div><strong>POD captured:</strong> ' + escapeHtml(r.captured_at || '—') + '</div>'
          +     '<div><strong>Trip duration:</strong> ' + tripDuration(parseInt(r.trip_minutes, 10)) + '</div>'
          +   '</div>'
          + '</div>'
          + '<div class="d-flex gap-2 mt-3">'
          +   '<button class="btn btn-success btn-verify"><i class="ti ti-check"></i> Verify &amp; Complete</button>'
          +   '<button class="btn btn-outline-danger btn-reject"><i class="ti ti-x"></i> Reject</button>'
          + '</div>'
          + '</div></div>';
      }).join('');
      $('#cardsBox').html(html);
    });
  }
  $('#refreshBtn').on('click', loadPending);

  function decide(decision, dId) {
    Swal.fire({
      title: decision === 'verified' ? 'Verify and complete this trip?' : 'Reject this POD?',
      input: 'textarea',
      inputPlaceholder: decision === 'verified' ? 'Verification notes (optional)' : 'Reason (driver will see this)',
      icon: decision === 'verified' ? 'success' : 'warning',
      showCancelButton: true,
      confirmButtonText: decision === 'verified' ? 'Verify' : 'Reject',
      confirmButtonColor: decision === 'verified' ? '#198754' : '#dc3545',
    }).then(r => {
      if (!r.isConfirmed) return;
      $.post('php/operations/verify_pod.php', { d_id: dId, decision: decision, notes: r.value || '' }, function (res) {
        if (res.status === 'success') {
          Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
          loadPending();
        } else {
          Swal.fire({ icon: 'error', text: res.message || 'Failed' });
        }
      }, 'json');
    });
  }
  $('#cardsBox').on('click', '.btn-verify', function(){ decide('verified', $(this).closest('[data-d-id]').data('d-id')); });
  $('#cardsBox').on('click', '.btn-reject', function(){ decide('rejected', $(this).closest('[data-d-id]').data('d-id')); });

  loadPending();
  setInterval(loadPending, 20000);
  </script>
</body>
</html>
