<?php
// Shared body for Equipment Locations.
// Required vars: $role.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Equipment Locations</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
  <style>
    .eq-bucket { border:1px solid #e5e7eb; border-radius:10px; padding:14px; margin-bottom:14px; background:#fff; }
    .eq-bucket h6 { margin:0 0 10px; }
    .eq-chip   { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:14px; font-size:12px; margin:2px 4px 2px 0; background:#e9ecef; color:#212529; }
    .eq-chip.truck   { background:#cfe2ff; color:#0a58ca; }
    .eq-chip.genset  { background:#fff3cd; color:#664d03; }
    .eq-chip.trailer { background:#d1e7dd; color:#0f5132; }
    .eq-chip.bad     { background:#f8d7da; color:#842029; }
    .eq-counter      { font-weight:700; font-size:18px; }
  </style>
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
       data-sidebar-position="fixed" data-header-position="fixed">
    <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-5"><img src="assets/images/logos/pantrucks.png" width="122" alt=""></div>
      <h3 class="text-white mb-0 fs-5">Equipment Locations</h3>
    </div>

    <?php include __DIR__ . '/../../' . $role . '/sidebar.php'; ?>

    <div class="body-wrapper">
      <?php include __DIR__ . '/../../' . $role . '/navbar.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="card mt-3"><div class="card-body">
            <div class="d-md-flex align-items-center mb-3">
              <div>
                <h4 class="card-title mb-0">Equipment Locations</h4>
                <p class="card-subtitle">Where every truck, genset, and trailer is right now. Updated by gate scans.</p>
              </div>
              <div class="ms-auto d-flex gap-2">
                <input class="form-control form-control-sm" id="filterText" placeholder="Filter codes…" style="max-width:220px;">
                <button class="btn btn-sm btn-outline-secondary" id="refreshBtn"><i class="ti ti-refresh"></i></button>
              </div>
            </div>
            <div id="bucketsBox"><div class="text-muted">Loading…</div></div>
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

  function renderItem(kind, item, filter) {
    if (filter && !String(item.code).toLowerCase().includes(filter)) return '';
    const cls = (item.status && item.status.toLowerCase() !== 'good') ? ' bad' : '';
    const tip = (item.updated_at ? '\nUpdated: ' + item.updated_at : '') + (item.status ? '\nStatus: ' + item.status : '');
    return '<span class="eq-chip ' + kind + cls + '" title="' + escapeHtml(tip.trim()) + '">'
         + escapeHtml(item.code) + '</span>';
  }
  function renderBucket(name, group, filter) {
    const trucks   = (group.truck   || []).map(i => renderItem('truck',   i, filter)).filter(Boolean).join('');
    const gensets  = (group.genset  || []).map(i => renderItem('genset',  i, filter)).filter(Boolean).join('');
    const trailers = (group.trailer || []).map(i => renderItem('trailer', i, filter)).filter(Boolean).join('');
    if (filter && !trucks && !gensets && !trailers) return '';
    const counts = '<span class="text-muted small">'
      + (group.truck   ? group.truck.length   + ' trucks · '   : '')
      + (group.genset  ? group.genset.length  + ' gensets · '  : '')
      + (group.trailer ? group.trailer.length + ' trailers' : '')
      + '</span>';
    return '<div class="eq-bucket">'
      +   '<div class="d-flex align-items-center mb-2"><h6 class="mb-0">' + escapeHtml(name) + '</h6><span class="ms-auto">' + counts + '</span></div>'
      +   (trucks   ? '<div><strong class="text-muted small">Trucks</strong><br>'   + trucks   + '</div>' : '')
      +   (gensets  ? '<div class="mt-2"><strong class="text-muted small">Gensets</strong><br>'  + gensets  + '</div>' : '')
      +   (trailers ? '<div class="mt-2"><strong class="text-muted small">Trailers</strong><br>' + trailers + '</div>' : '')
      + '</div>';
  }

  function load() {
    const filter = ($('#filterText').val() || '').trim().toLowerCase();
    $.getJSON('php/fetch/equipment_locations.php', function (res) {
      if (res.status !== 'success') { $('#bucketsBox').html('<div class="alert alert-danger">' + escapeHtml(res.message) + '</div>'); return; }
      const html = Object.keys(res.buckets).map(k => renderBucket(k, res.buckets[k], filter)).join('');
      $('#bucketsBox').html(html || '<div class="text-muted">Nothing matches.</div>');
    });
  }
  $('#refreshBtn').on('click', load);
  $('#filterText').on('input', load);
  load();
  setInterval(load, 30000);
  </script>
  <?php include __DIR__ . '/realtime_alerts.php'; ?>
</body>
</html>
