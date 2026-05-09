<?php
session_start();
if (($_SESSION['user_type'] ?? '') !== 'Driver') { header("Location: driver-index.php?route=login"); exit(); }
$pageTitle = 'Messages';
$activeNav = 'messages';
include 'driver/_layout_top.php';
?>
<div class="card mt-3"><div class="card-body" style="padding:0;">
  <div id="messageList" style="height:60vh;overflow-y:auto;padding:12px;background:#f5f7fa;"></div>
  <div style="border-top:1px solid #eee;padding:8px;display:flex;gap:8px;background:#fff;">
    <input id="msgInput" class="form-control-modern" placeholder="Message dispatcher…" style="flex:1;" autocomplete="off">
    <button id="sendMsg" class="btn-modern btn-primary-modern" style="width:auto;padding:8px 16px;"><i class="ti ti-send"></i></button>
  </div>
</div></div>

<script>
function escapeHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

let lastMsgId = 0;
function fetchMessages(replace) {
  $.getJSON('php/fetch/messages.php', { since: lastMsgId }, function (res) {
    if (res.status !== 'success') return;
    const $list = $('#messageList');
    if (replace) $list.empty();
    res.rows.forEach(function (m) {
      lastMsgId = Math.max(lastMsgId, parseInt(m.msg_id, 10));
      const mine = m.from_role === 'driver';
      const bubble = '<div style="display:flex;justify-content:' + (mine ? 'flex-end' : 'flex-start') + ';margin-bottom:8px;">'
        + '<div style="max-width:75%;padding:8px 12px;border-radius:12px;font-size:14px;'
        + (mine ? 'background:#0d6efd;color:#fff;' : 'background:#fff;color:#222;border:1px solid #e5e7eb;') + '">'
        + escapeHtml(m.body)
        + '<div style="font-size:10px;opacity:.7;margin-top:4px;">' + escapeHtml(m.sent_at) + '</div>'
        + '</div></div>';
      $list.append(bubble);
    });
    $list.scrollTop($list.prop('scrollHeight'));
  });
}
fetchMessages(true);
setInterval(fetchMessages, 5000);

function sendMessage() {
  const body = $('#msgInput').val().trim();
  if (!body) return;
  $('#msgInput').val('');
  $.post('php/operations/send_message.php', { body: body }, function (res) {
    if (res.status === 'success' || res.status === 'queued') fetchMessages(false);
    else Swal.fire({ icon: 'error', text: res.message });
  }, 'json').fail(function () {
    Swal.fire({ icon: 'error', text: 'Network error' });
  });
}
$('#sendMsg').on('click', sendMessage);
$('#msgInput').on('keydown', function (e) { if (e.key === 'Enter') sendMessage(); });
</script>
<?php include 'driver/_layout_bottom.php'; ?>
