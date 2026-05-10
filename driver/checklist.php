<?php
session_start();
if (($_SESSION['user_type'] ?? '') !== 'Driver') { header("Location: driver-index.php?route=login"); exit(); }
$pageTitle = 'Pre-Departure Checklist';
$activeNav = 'checklist';
include 'driver/_layout_top.php';

include 'php/config/config.php';
$driverId = (int)$_SESSION['user_id'];

// Latest active dispatch for this driver — for the truck/trailer/genset prefill.
$dispatch = null;
$stmt = $conn->prepare(
    "SELECT d_id, d_truck, d_trailer, d_genset, booking_no
     FROM dispatch
     WHERE driver_id = ? AND workflow_stage IN ('dispatcher_assigned', 'driver_accepted', 'gate_cleared')
     ORDER BY d_id DESC LIMIT 1"
);
$stmt->bind_param("i", $driverId);
$stmt->execute();
$dispatch = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Driver's truck options if no active dispatch yet.
$trucks = [];
$res = mysqli_query($conn, "SELECT unit_name FROM units WHERE unit_type='truck' AND unit_status='Good' AND maintenance_blocked = 0 ORDER BY unit_name ASC");
while ($r = mysqli_fetch_assoc($res)) { $trucks[] = $r['unit_name']; }
?>
<div class="card mt-3"><div class="card-body">
  <h5 class="card-title">Truck for this shift</h5>
  <p class="text-muted small">Select the truck you're driving today. This starts machine-hour tracking.</p>
  <input list="truckList" class="form-control-modern" id="shift_truck"
         value="<?php echo htmlspecialchars($dispatch['d_truck'] ?? ''); ?>"
         placeholder="e.g. PM651">
  <datalist id="truckList">
    <?php foreach ($trucks as $t) echo '<option value="' . htmlspecialchars($t) . '">'; ?>
  </datalist>
</div></div>

<div class="card mt-3"><div class="card-body">
  <h5 class="card-title">Pre-Departure Inspection</h5>
  <p class="text-muted small">Tap each item to confirm it's good. All must pass before you can flip to <em>Available</em>.</p>

  <input type="hidden" id="d_id"          value="<?php echo (int)($dispatch['d_id'] ?? 0); ?>">
  <input type="hidden" id="trailer_code"  value="<?php echo htmlspecialchars($dispatch['d_trailer'] ?? ''); ?>">
  <input type="hidden" id="genset_code"   value="<?php echo htmlspecialchars($dispatch['d_genset']  ?? ''); ?>">

  <div class="d-grid gap-2 mt-3" id="checklistItems">
    <?php $items = [
      ['fuel_ok',       'ti ti-gas-station',  'Fuel topped up'],
      ['tyres_ok',      'ti ti-circle-dot',   'Tyres inflated, no damage'],
      ['lights_ok',     'ti ti-bulb',         'Headlights, signals, brake lights'],
      ['cargo_area_ok', 'ti ti-box',          'Cargo area clean and secure'],
      ['genset_ok',     'ti ti-engine',       'Genset functional (if applicable)'],
    ];
    foreach ($items as $it): ?>
      <button type="button" class="btn-modern btn-outline-modern check-item d-flex align-items-center" data-key="<?php echo $it[0]; ?>" style="justify-content:space-between;">
        <span><i class="<?php echo $it[1]; ?> me-2"></i><?php echo $it[2]; ?></span>
        <span class="check-state"><i class="ti ti-circle"></i></span>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="mt-3">
    <label class="form-label">Remarks (optional)</label>
    <textarea id="remarks" class="form-control-modern" rows="2" placeholder="Anything the workshop should know"></textarea>
  </div>

  <button id="submitChecklist" class="btn-modern btn-primary-modern w-100 mt-3" disabled>
    <i class="ti ti-check"></i> Submit and Go Available
  </button>
</div></div>

<script>
const state = { fuel_ok:0, tyres_ok:0, lights_ok:0, cargo_area_ok:0, genset_ok:0 };
function refreshSubmit() {
  const allOk = Object.values(state).every(v => v === 1);
  document.getElementById('submitChecklist').disabled = !allOk;
}
$('.check-item').on('click', function () {
  const k = $(this).data('key');
  state[k] = state[k] ? 0 : 1;
  $(this).find('.check-state').html(state[k]
    ? '<i class="ti ti-circle-check text-success"></i>'
    : '<i class="ti ti-circle"></i>');
  $(this).toggleClass('btn-success-modern', !!state[k]).toggleClass('btn-outline-modern', !state[k]);
  refreshSubmit();
});

$('#submitChecklist').on('click', function () {
  const fd = new FormData();
  fd.append('d_id',         $('#d_id').val());
  fd.append('truck_code',   $('#shift_truck').val());
  fd.append('trailer_code', $('#trailer_code').val());
  fd.append('genset_code',  $('#genset_code').val());
  Object.keys(state).forEach(k => fd.append(k, state[k]));
  fd.append('remarks',      $('#remarks').val());
  fetch('php/operations/save_pre_departure.php', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(r => r.json()).then(res => {
      Swal.fire({ icon: res.status === 'success' ? 'success' : (res.status === 'queued' ? 'info' : 'error'),
                  title: res.status === 'success' ? 'You are Available' : '',
                  text: res.message, timer: 1800, showConfirmButton: false });
      if (res.status === 'success' || res.status === 'queued') {
        setTimeout(() => location.href = 'driver-dashboard', 1900);
      }
    });
});
</script>
<?php include 'driver/_layout_bottom.php'; ?>
