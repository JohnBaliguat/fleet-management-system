<?php
session_start();
if (($_SESSION['user_type'] ?? '') !== 'Driver') { header("Location: driver-index.php?route=login"); exit(); }
$pageTitle = 'Jack-up Trailer';
$activeNav = 'home';
include 'driver/_layout_top.php';

include 'php/config/config.php';
$driverId = (int)$_SESSION['user_id'];
$dId = (int)($_GET['d_id'] ?? 0);

$dispatch = null;
if ($dId > 0) {
    $stmt = $conn->prepare("SELECT d_id, booking_no, d_truck, d_trailer FROM dispatch WHERE d_id = ? AND driver_id = ? LIMIT 1");
    $stmt->bind_param("ii", $dId, $driverId);
    $stmt->execute();
    $dispatch = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<div class="card mt-3"><div class="card-body">
  <h5 class="card-title">Detach trailer at site</h5>
  <p class="text-muted small">Trailer billing keeps running until the trailer returns to compound. Capture GPS + a photo of the detached trailer.</p>

  <input type="hidden" id="d_id" value="<?php echo (int)($dispatch['d_id'] ?? 0); ?>">

  <div class="mb-3">
    <label class="form-label">Trailer code</label>
    <input id="trailer_code" class="form-control-modern" value="<?php echo htmlspecialchars($dispatch['d_trailer'] ?? ''); ?>" placeholder="e.g. TR007">
  </div>

  <div class="mb-3" id="gpsBox">
    <div class="alert alert-secondary mb-0"><span id="gpsStatus">Acquiring GPS…</span></div>
  </div>

  <label class="btn-modern btn-outline-modern w-100" style="cursor:pointer;text-align:center;">
    <i class="ti ti-camera"></i> Photo of detached trailer
    <input type="file" accept="image/*" capture="environment" id="photo" hidden>
  </label>
  <div id="photoPreview" class="mt-2"></div>

  <button id="submitJackup" class="btn-modern btn-primary-modern w-100 mt-3" disabled>
    <i class="ti ti-trailer"></i> Mark Jacked Up
  </button>
</div></div>

<script>
const gps = { lat: null, lng: null };
function refresh() {
  const ok = gps.lat && document.getElementById('photo').files[0] && document.getElementById('trailer_code').value.trim();
  document.getElementById('submitJackup').disabled = !ok;
}
function captureGPS() {
  if (!navigator.geolocation) { document.getElementById('gpsStatus').textContent = 'Geolocation not available.'; return; }
  navigator.geolocation.getCurrentPosition(p => {
    gps.lat = p.coords.latitude.toFixed(7); gps.lng = p.coords.longitude.toFixed(7);
    document.getElementById('gpsStatus').innerHTML = '<i class="ti ti-map-pin text-success"></i> Locked: ' + gps.lat + ', ' + gps.lng;
    refresh();
  }, err => document.getElementById('gpsStatus').textContent = 'GPS error: ' + err.message,
    { enableHighAccuracy: true, timeout: 15000 });
}
captureGPS();
$('#gpsBox').on('click', captureGPS);
$('#trailer_code').on('input', refresh);
document.getElementById('photo').addEventListener('change', function () {
  const f = this.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => document.getElementById('photoPreview').innerHTML =
    '<img src="' + e.target.result + '" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">';
  r.readAsDataURL(f);
  refresh();
});

document.getElementById('submitJackup').addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('d_id', document.getElementById('d_id').value);
  fd.append('trailer_code', document.getElementById('trailer_code').value.trim());
  fd.append('lat', gps.lat); fd.append('lng', gps.lng);
  fd.append('photo', document.getElementById('photo').files[0]);
  const r = await fetch('php/operations/save_trailer_jackup.php', { method: 'POST', body: fd, credentials: 'same-origin' });
  const res = await r.json();
  Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
              text: res.message, timer: 1800, showConfirmButton: false });
  if (res.status === 'success' || res.status === 'queued') {
    setTimeout(() => location.href = 'driver-dashboard', 1900);
  }
});
</script>
<?php include 'driver/_layout_bottom.php'; ?>
