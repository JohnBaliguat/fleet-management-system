<?php
session_start();
if (($_SESSION['user_type'] ?? '') !== 'Driver') { header("Location: driver-index.php?route=login"); exit(); }
$pageTitle = 'Proof of Delivery';
$activeNav = 'home';
include 'driver/_layout_top.php';

include 'php/config/config.php';
$driverId = (int)$_SESSION['user_id'];
$dId = (int)($_GET['d_id'] ?? 0);

$dispatch = null;
if ($dId > 0) {
    $stmt = $conn->prepare("SELECT d_id, booking_no, costumer, driver_id, d_truck FROM dispatch WHERE d_id = ? LIMIT 1");
    $stmt->bind_param("i", $dId);
    $stmt->execute();
    $dispatch = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<div class="card mt-3"><div class="card-body">
  <h5 class="card-title">POD <?php if ($dispatch): ?><span class="text-muted small">— <?php echo htmlspecialchars($dispatch['booking_no']); ?></span><?php endif; ?></h5>
  <p class="text-muted small">At least <strong>2 photos</strong> + recipient signature required to mark this delivery complete.</p>

  <input type="hidden" id="d_id" value="<?php echo $dId; ?>">

  <div class="mb-3">
    <label class="form-label">Recipient name</label>
    <input class="form-control-modern" id="signed_by" placeholder="Who signed for the delivery?">
  </div>

  <div class="mb-3">
    <label class="form-label">Photos (min 2)</label>
    <div class="d-grid gap-2">
      <label class="btn-modern btn-outline-modern" style="cursor:pointer;text-align:center;">
        <i class="ti ti-camera"></i> Photo 1
        <input type="file" accept="image/*" capture="environment" id="photo1" hidden>
      </label>
      <label class="btn-modern btn-outline-modern" style="cursor:pointer;text-align:center;">
        <i class="ti ti-camera"></i> Photo 2
        <input type="file" accept="image/*" capture="environment" id="photo2" hidden>
      </label>
      <label class="btn-modern btn-outline-modern" style="cursor:pointer;text-align:center;">
        <i class="ti ti-camera"></i> Photo 3 (optional)
        <input type="file" accept="image/*" capture="environment" id="photo3" hidden>
      </label>
    </div>
    <div class="d-flex gap-2 mt-2" id="photoPreviews"></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Recipient signature</label>
    <canvas id="sigPad" width="600" height="220" style="border:1px dashed #ccc;border-radius:8px;width:100%;background:#fff;touch-action:none;"></canvas>
    <button type="button" id="clearSig" class="btn-modern btn-outline-modern mt-2"><i class="ti ti-eraser"></i> Clear</button>
  </div>

  <button id="submitPod" class="btn-modern btn-primary-modern w-100">
    <i class="ti ti-check"></i> Submit POD
  </button>
</div></div>

<script>
// ---- previews ----
function preview(input, slot) {
  const f = input.files && input.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => {
    let img = document.querySelector('[data-pv="' + slot + '"]');
    if (!img) {
      img = document.createElement('img');
      img.dataset.pv = slot;
      img.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ddd;';
      document.getElementById('photoPreviews').appendChild(img);
    }
    img.src = e.target.result;
  };
  r.readAsDataURL(f);
}
document.getElementById('photo1').addEventListener('change', function () { preview(this, 1); });
document.getElementById('photo2').addEventListener('change', function () { preview(this, 2); });
document.getElementById('photo3').addEventListener('change', function () { preview(this, 3); });

// ---- signature pad ----
(function () {
  const cvs = document.getElementById('sigPad');
  const ctx = cvs.getContext('2d');
  function fitCanvas() {
    const ratio = window.devicePixelRatio || 1;
    const w = cvs.clientWidth, h = cvs.clientHeight || 220;
    cvs.width = w * ratio; cvs.height = h * ratio;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.strokeStyle = '#111';
  }
  fitCanvas();
  let drawing = false;
  function pos(e) {
    const r = cvs.getBoundingClientRect();
    const t = e.touches ? e.touches[0] : e;
    return { x: t.clientX - r.left, y: t.clientY - r.top };
  }
  function start(e) { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
  function move(e)  { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }
  function end()    { drawing = false; }
  cvs.addEventListener('mousedown', start); cvs.addEventListener('mousemove', move); document.addEventListener('mouseup', end);
  cvs.addEventListener('touchstart', start, { passive: true });
  cvs.addEventListener('touchmove',  move,  { passive: false });
  cvs.addEventListener('touchend',   end);
  document.getElementById('clearSig').addEventListener('click', () => ctx.clearRect(0, 0, cvs.width, cvs.height));
  window.PT_signatureDataURL = () => cvs.toDataURL('image/png');
  window.PT_signatureIsBlank = () => {
    const d = ctx.getImageData(0, 0, cvs.width, cvs.height).data;
    for (let i = 3; i < d.length; i += 4) if (d[i] !== 0) return false;
    return true;
  };
})();

document.getElementById('submitPod').addEventListener('click', async function () {
  const dId = document.getElementById('d_id').value;
  if (!dId) { Swal.fire({ icon: 'error', text: 'No dispatch in context.' }); return; }
  const p1 = document.getElementById('photo1').files[0];
  const p2 = document.getElementById('photo2').files[0];
  const p3 = document.getElementById('photo3').files[0];
  if (!p1 || !p2) { Swal.fire({ icon: 'warning', text: 'At least 2 photos required.' }); return; }
  if (window.PT_signatureIsBlank()) { Swal.fire({ icon: 'warning', text: 'Recipient signature required.' }); return; }

  const fd = new FormData();
  fd.append('d_id', dId);
  fd.append('signed_by', document.getElementById('signed_by').value || '');
  fd.append('signature', window.PT_signatureDataURL());
  fd.append('photo1', p1); fd.append('photo2', p2);
  if (p3) fd.append('photo3', p3);
  if ('geolocation' in navigator) {
    await new Promise(resolve => navigator.geolocation.getCurrentPosition(
      pos => { fd.append('lat', pos.coords.latitude); fd.append('lng', pos.coords.longitude); resolve(); },
      () => resolve(), { enableHighAccuracy: true, timeout: 5000 }
    ));
  }
  const r = await fetch('php/operations/save_pod.php', { method: 'POST', body: fd, credentials: 'same-origin' });
  const res = await r.json();
  Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
              text: res.message, timer: 1800, showConfirmButton: false });
  if (res.status === 'success' || res.status === 'queued') {
    setTimeout(() => location.href = 'driver-dashboard', 1900);
  }
});
</script>
<?php include 'driver/_layout_bottom.php'; ?>
