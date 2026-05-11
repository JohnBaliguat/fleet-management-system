<?php
// Shared body for the dispatcher / admin Chat page.
// Required vars: $role.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Driver Chat</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
  <link rel="stylesheet" href="assets/css/enhancements.css" />
  <link rel="stylesheet" href="alert/node_modules/sweetalert2/dist/sweetalert2.min.css">
  <style>
    .chat-wrap   { display:grid; grid-template-columns: 320px 1fr; gap:12px; min-height:70vh; }
    @media (max-width: 768px) { .chat-wrap { grid-template-columns: 1fr; } }
    .chat-list   { background:#fff; border:1px solid #e5e7eb; border-radius:10px; max-height:75vh; overflow-y:auto; }
    .chat-thread { background:#fff; border:1px solid #e5e7eb; border-radius:10px; display:flex; flex-direction:column; min-height:70vh; }
    .thread-row  { padding:10px 12px; border-bottom:1px solid #f1f5f9; cursor:pointer; }
    .thread-row.active { background:#eef4ff; }
    .thread-row:hover { background:#f8fafc; }
    .thread-row .name  { font-weight:600; }
    .thread-row .last  { font-size:12px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .thread-row .meta  { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#94a3b8; }
    .messages-area    { flex:1; overflow-y:auto; padding:14px; background:#f5f7fa; }
    .composer        { border-top:1px solid #e5e7eb; padding:10px; background:#fff; display:flex; gap:8px; }
    .bubble          { max-width:75%; padding:8px 12px; border-radius:12px; font-size:14px; margin-bottom:8px; }
    .bubble.driver   { background:#fff; color:#222; border:1px solid #e5e7eb; }
    .bubble.disp     { background:#0d6efd; color:#fff; margin-left:auto; }
    .bubble .ts      { font-size:10px; opacity:0.7; margin-top:4px; }
    .empty-state     { text-align:center; padding:40px; color:#94a3b8; }
  </style>
</head>
<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
       data-sidebar-position="fixed" data-header-position="fixed">
    <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-5"><img src="assets/images/logos/pantrucks.png" width="122" alt=""></div>
      <h3 class="text-white mb-0 fs-5">Driver Chat</h3>
    </div>

    <?php include __DIR__ . '/../../' . $role . '/sidebar.php'; ?>

    <div class="body-wrapper">
      <?php include __DIR__ . '/../../' . $role . '/navbar.php'; ?>
      <div class="body-wrapper-inner">
        <div class="container-fluid">
          <div class="card mt-3"><div class="card-body">
            <div class="d-md-flex align-items-center mb-3">
              <div>
                <h4 class="card-title mb-0">Driver Chat</h4>
                <p class="card-subtitle">Messages from drivers to dispatch. Click a name to open the thread; replies go straight to that driver.</p>
              </div>
              <div class="ms-auto">
                <button class="btn btn-sm btn-outline-secondary" id="refreshThreads"><i class="ti ti-refresh"></i></button>
              </div>
            </div>

            <div class="chat-wrap">
              <div class="chat-list" id="threadsBox"><div class="empty-state">Loading…</div></div>
              <div class="chat-thread">
                <div id="threadHeader" style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-weight:600;background:#fafbfd;">
                  Pick a driver on the left to open the thread.
                </div>
                <div id="messagesArea" class="messages-area">
                  <div class="empty-state">No thread selected.</div>
                </div>
                <div class="composer">
                  <input id="msgInput" class="form-control" placeholder="Type a reply…" autocomplete="off" disabled>
                  <button id="sendBtn" class="btn btn-primary" disabled><i class="ti ti-send"></i></button>
                </div>
              </div>
            </div>
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

  let activeDriverId = null;
  let activeDriverName = '';
  let lastMsgId = 0;

  function loadThreads() {
    $.getJSON('php/fetch/chat_threads.php', function (res) {
      if (res.status !== 'success') return;
      if (!res.rows.length) {
        $('#threadsBox').html('<div class="empty-state">No conversations yet. Drivers can chat from their PWA.</div>');
        return;
      }
      const html = res.rows.map(function (r) {
        const unread = parseInt(r.unread, 10) || 0;
        const badge = unread ? '<span class="badge bg-danger">' + unread + '</span>' : '';
        const cls = (parseInt(activeDriverId, 10) === parseInt(r.driver_id, 10)) ? 'active' : '';
        return '<div class="thread-row ' + cls + '" data-id="' + r.driver_id + '" data-name="' + escapeHtml(r.driver_name) + '">'
          + '<div class="d-flex align-items-center gap-2">'
          +   '<span class="name flex-fill">' + escapeHtml(r.driver_name) + '</span>' + badge
          + '</div>'
          + '<div class="last">' + escapeHtml(r.last_body || '(no messages)') + '</div>'
          + '<div class="meta"><span>' + escapeHtml(r.shift_truck || '—') + '</span><span>' + escapeHtml(r.last_at || '') + '</span></div>'
          + '</div>';
      }).join('');
      $('#threadsBox').html(html);
    });
  }
  $('#refreshThreads').on('click', loadThreads);

  function loadMessages(append) {
    if (!activeDriverId) return;
    $.getJSON('php/fetch/messages.php', { driver_id: activeDriverId, since: append ? lastMsgId : 0 }, function (res) {
      if (res.status !== 'success') return;
      const $area = $('#messagesArea');
      if (!append) $area.empty();
      if (!res.rows.length && !append) {
        $area.html('<div class="empty-state">No messages in this thread yet.</div>');
        return;
      }
      res.rows.forEach(function (m) {
        lastMsgId = Math.max(lastMsgId, parseInt(m.msg_id, 10));
        const cls = m.from_role === 'driver' ? 'driver' : 'disp';
        $area.append('<div class="bubble ' + cls + '">' + escapeHtml(m.body)
          + '<div class="ts">' + escapeHtml(m.sent_at || '') + '</div></div>');
      });
      $area.scrollTop($area.prop('scrollHeight'));
    });
  }

  $('#threadsBox').on('click', '.thread-row', function () {
    activeDriverId = $(this).data('id');
    activeDriverName = $(this).data('name');
    lastMsgId = 0;
    $('.thread-row').removeClass('active');
    $(this).addClass('active');
    $('#threadHeader').html('<i class="ti ti-user"></i> ' + escapeHtml(activeDriverName));
    $('#msgInput').prop('disabled', false).focus();
    $('#sendBtn').prop('disabled', false);
    loadMessages(false);
    // Refresh the threads list so the unread badge clears.
    setTimeout(loadThreads, 800);
  });

  function sendReply() {
    if (!activeDriverId) return;
    const body = $('#msgInput').val().trim();
    if (!body) return;
    $('#msgInput').val('');
    $.post('php/operations/send_message.php', { body: body, to_role: 'driver', to_id: activeDriverId }, function (res) {
      if (res.status === 'success' || res.status === 'queued') loadMessages(false);
      else Swal.fire({ icon: 'error', text: res.message });
    }, 'json').fail(function () { Swal.fire({icon:'error',text:'Network error'}); });
  }
  $('#sendBtn').on('click', sendReply);
  $('#msgInput').on('keydown', function (e) { if (e.key === 'Enter') sendReply(); });

  // Initial + periodic refresh.
  loadThreads();
  setInterval(loadThreads, 15000);
  setInterval(function () { if (activeDriverId) loadMessages(true); }, 5000);
  </script>
  <?php include __DIR__ . '/realtime_alerts.php'; ?>
</body>
</html>
