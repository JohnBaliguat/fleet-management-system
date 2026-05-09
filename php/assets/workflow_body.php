<?php
// Shared body for the Workflow Timeline page. Required vars: $role.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Workflow Timeline</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
  <style>
    .pipeline { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .pipeline .stage { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; background:#e9ecef; color:#6c757d; }
    .pipeline .stage.done   { background:#198754; color:#fff; }
    .pipeline .stage.curr   { background:#0d6efd; color:#fff; }
    .pipeline .arrow        { color:#adb5bd; }
    .timeline { border-left:3px solid #dee2e6; padding-left:18px; margin-top:18px; }
    .timeline .ev { position:relative; margin-bottom:14px; }
    .timeline .ev:before { content:''; position:absolute; left:-25px; top:6px; width:12px; height:12px; border-radius:50%; background:#0d6efd; }
    .timeline .ev .stage { font-weight:600; }
    .timeline .ev .meta  { color:#6c757d; font-size:12px; }
  </style>
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
       data-sidebar-position="fixed" data-header-position="fixed">
    <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-5"><img src="assets/images/logos/pantrucks.png" width="122" alt=""></div>
      <h3 class="text-white mb-0 fs-5">Workflow Timeline</h3>
    </div>

    <?php include __DIR__ . '/../../' . $role . '/sidebar.php'; ?>

    <div class="body-wrapper">
      <?php include __DIR__ . '/../../' . $role . '/navbar.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="card mt-3"><div class="card-body">
            <h4 class="card-title">Workflow Timeline</h4>
            <p class="card-subtitle">9-stage pipeline: Order Created &rarr; Dispatcher Assigns &rarr; Driver Accepts &rarr; Gate Clearance &rarr; En Route &rarr; Delivered &rarr; POD Captured &rarr; Billing Closed &rarr; Client Notified.</p>

            <div class="row g-2 mt-3">
              <div class="col-md-9"><input list="bnList" class="form-control" id="bookingSearch" placeholder="Booking number"></div>
              <div class="col-md-3"><button class="btn btn-primary w-100" id="loadTimeline"><i class="ti ti-search"></i> Load</button></div>
            </div>
            <datalist id="bnList"></datalist>

            <div id="summary" class="mt-3"></div>
            <div id="pipelineBox" class="mt-3"></div>
            <div id="dispatchTable" class="mt-3"></div>
            <div id="timeline" class="timeline"></div>
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

  const STAGES = [
    'order_created', 'dispatcher_assigned', 'driver_accepted',
    'gate_cleared',  'en_route', 'delivered',
    'pod_captured',  'billing_closed', 'client_notified'
  ];
  const STAGE_LABEL = {
    order_created:       'Order Created',
    dispatcher_assigned: 'Dispatcher Assigns',
    driver_accepted:     'Driver Accepts',
    gate_cleared:        'Gate Clearance',
    en_route:            'En Route',
    delivered:           'Delivered',
    pod_captured:        'POD Captured',
    billing_closed:      'Billing Closed',
    client_notified:     'Client Notified',
  };

  function refreshBookingList(q){
    $.getJSON('php/fetch/list_booking_nos.php', { q: q || '' }, function(res){
      if (res.status !== 'success') return;
      const $dl = $('#bnList').empty();
      res.rows.forEach(r => $dl.append('<option value="' + r.booking_no + '">' + (r.costumer || '') + '</option>'));
    });
  }
  $('#bookingSearch').on('focus input', function(){ refreshBookingList($(this).val()); });

  function renderPipeline(allStagesReached, currStage){
    const html = STAGES.map((s, i) => {
      let cls = 'stage';
      if (allStagesReached.has(s) && s !== currStage) cls += ' done';
      if (s === currStage) cls += ' curr';
      const sep = i < STAGES.length - 1 ? '<span class="arrow">&rarr;</span>' : '';
      return '<span class="' + cls + '">' + escapeHtml(STAGE_LABEL[s] || s) + '</span>' + sep;
    }).join('');
    return '<div class="pipeline">' + html + '</div>';
  }

  function load(){
    const bn = ($('#bookingSearch').val() || '').trim();
    if (!bn) { Swal.fire({icon:'info',text:'Pick a booking.'}); return; }
    $.getJSON('php/fetch/workflow_timeline.php', { booking_no: bn }, function(res){
      if (res.status !== 'success') { Swal.fire({icon:'error',text:res.message}); return; }
      // Summary
      const b = res.booking || {};
      $('#summary').html('<div class="alert alert-info mb-2"><b>' + escapeHtml(b.booking_no || '?') + '</b> &mdash; '
        + '<span class="badge bg-primary">' + escapeHtml(b.booking_type || 'Local') + '</span> '
        + escapeHtml(b.costumer || '') + ' &middot; '
        + escapeHtml(b.trip_from || '') + ' &rarr; ' + escapeHtml(b.trip_to || '') + '</div>');

      // Pipeline — union of stages reached across all dispatches and events.
      const reached = new Set();
      let currStage = '';
      res.dispatches.forEach(d => { reached.add(d.workflow_stage); currStage = d.workflow_stage; });
      res.events.forEach(e => { if (STAGES.includes(e.stage)) reached.add(e.stage); });
      $('#pipelineBox').html(renderPipeline(reached, currStage));

      // Dispatch table
      if (res.dispatches.length) {
        const dt = '<div class="table-responsive mt-3"><table class="table table-sm table-bordered align-middle">'
          + '<thead class="table-light"><tr><th>#</th><th>Truck</th><th>Driver</th><th>Stage</th><th>Updated</th><th>Closed</th><th>Notified</th><th>Amount</th></tr></thead><tbody>'
          + res.dispatches.map(d => '<tr><td>' + d.d_id + '</td>'
              + '<td>' + escapeHtml(d.d_truck || '-') + '</td>'
              + '<td>' + escapeHtml(d.d_driverName || '-') + '</td>'
              + '<td><span class="badge bg-light text-dark">' + escapeHtml(d.workflow_stage) + '</span></td>'
              + '<td>' + escapeHtml(d.workflow_updated_at || '—') + '</td>'
              + '<td>' + escapeHtml(d.billing_closed_at || '—') + '</td>'
              + '<td>' + escapeHtml(d.client_notified_at || '—') + '</td>'
              + '<td>' + (d.billing_amount && parseFloat(d.billing_amount) > 0 ? escapeHtml(d.billing_currency) + ' ' + parseFloat(d.billing_amount).toFixed(2) : '—') + '</td>'
              + '</tr>').join('')
          + '</tbody></table></div>';
        $('#dispatchTable').html(dt);
      } else { $('#dispatchTable').html(''); }

      // Timeline
      if (res.events.length) {
        const tl = res.events.map(e => '<div class="ev">'
            + '<div class="stage">' + escapeHtml(STAGE_LABEL[e.stage] || e.stage) + '</div>'
            + '<div class="meta">' + escapeHtml(e.event_at)
            + ' &middot; ' + escapeHtml(e.actor_role || 'system')
            + (e.d_id ? ' &middot; dispatch #' + e.d_id : '') + '</div>'
            + (e.notes ? '<div>' + escapeHtml(e.notes) + '</div>' : '')
            + '</div>').join('');
        $('#timeline').html(tl);
      } else { $('#timeline').html('<div class="text-muted">No timeline events for this booking yet.</div>'); }
    });
  }
  $('#loadTimeline').on('click', load);
  $('#bookingSearch').on('change', load);

  // Pre-load if URL has ?booking_no
  (function(){
    const u = new URL(location.href);
    const bn = u.searchParams.get('bn') || u.searchParams.get('booking_no');
    if (bn) { $('#bookingSearch').val(bn); load(); }
  })();
  </script>
</body>
</html>
