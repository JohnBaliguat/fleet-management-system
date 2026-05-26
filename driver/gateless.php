<?php
session_start();
if (($_SESSION['user_type'] ?? '') !== 'Driver') { header("Location: driver-index.php?route=login"); exit(); }
$pageTitle = 'Gateless Completion';
$activeNav = 'home';
include 'driver/_layout_top.php';

$dId = (int)($_GET['d_id'] ?? 0);
?>
<div class="card mt-3"><div class="card-body">
  <h5 class="card-title">Destination has no gate?</h5>
  <p class="text-muted small">GPS will be auto-captured. Two photos minimum. Works offline — your submission will sync when you're back online.</p>
  <input type="hidden" id="d_id" value="<?php echo $dId; ?>">

  <div class="mb-3" id="gpsBox">
    <div class="alert alert-secondary mb-0">
      <span id="gpsStatus">Acquiring GPS…</span>
    </div>
  </div>

  <div class="d-grid gap-2">
    <label class="btn-modern btn-outline-modern" style="cursor:pointer;text-align:center;">
      <i class="ti ti-camera"></i> Photo 1
      <input type="file" accept="image/*" capture="environment" id="photo1" hidden>
    </label>
    <label class="btn-modern btn-outline-modern" style="cursor:pointer;text-align:center;">
      <i class="ti ti-camera"></i> Photo 2
      <input type="file" accept="image/*" capture="environment" id="photo2" hidden>
    </label>
  </div>
  <div class="d-flex gap-2 mt-2" id="photoPreviews"></div>

  <button id="submitGateless" class="btn-modern btn-primary-modern w-100 mt-3" disabled>
    <i class="ti ti-check"></i> Submit gateless completion
  </button>
</div></div>

<script>
const gpsState = { lat: null, lng: null, acc: null, stamped: null };
function refreshSubmit() {
  const ok = gpsState.lat && document.getElementById('photo1').files[0] && document.getElementById('photo2').files[0];
  document.getElementById('submitGateless').disabled = !ok;
}
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
    refreshSubmit();
  };
  r.readAsDataURL(f);
}
document.getElementById('photo1').addEventListener('change', function () { preview(this, 1); });
document.getElementById('photo2').addEventListener('change', function () { preview(this, 2); });

function captureGPS() {
  if (!navigator.geolocation) {
    document.getElementById('gpsStatus').textContent = 'Geolocation not available on this device.';
    return;
  }
  navigator.geolocation.getCurrentPosition(pos => {
    gpsState.lat = pos.coords.latitude.toFixed(7);
    gpsState.lng = pos.coords.longitude.toFixed(7);
    gpsState.acc = Math.round(pos.coords.accuracy);
    gpsState.stamped = new Date().toISOString();
    document.getElementById('gpsStatus').innerHTML = '<i class="ti ti-map-pin text-success"></i> Locked: '
      + gpsState.lat + ', ' + gpsState.lng + ' (~' + gpsState.acc + 'm)';
    refreshSubmit();
  }, err => {
    document.getElementById('gpsStatus').textContent = 'GPS error: ' + err.message + ' — try again outside.';
  }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
}
captureGPS();
$('#gpsBox').on('click', captureGPS);

document.getElementById('submitGateless').addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('d_id', document.getElementById('d_id').value);
  fd.append('lat', gpsState.lat);
  fd.append('lng', gpsState.lng);
  fd.append('accuracy_m', gpsState.acc || 0);
  fd.append('captured_at', gpsState.stamped);
  fd.append('photo1', document.getElementById('photo1').files[0]);
  fd.append('photo2', document.getElementById('photo2').files[0]);
  const r = await fetch('php/operations/save_gateless.php', { method: 'POST', body: fd, credentials: 'same-origin' });
  const res = await r.json();
  Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
              text: res.message, timer: 1800, showConfirmButton: false });
  if (res.status === 'success' || res.status === 'queued') {
    setTimeout(() => location.href = 'driver-dashboard', 1900);
  }
});
</script>
<?php include 'driver/_layout_bottom.php'; ?>
