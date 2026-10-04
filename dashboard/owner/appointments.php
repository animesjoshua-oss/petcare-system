<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'My Appointments';
$isDashboard = true;
$db = getDB();

$status = sanitize($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage = 15;

$where  = ['a.owner_id = ?'];
$params = [$user['id']];
if ($status) { $where[] = 'a.status = ?'; $params[] = $status; }
$whereStr = 'WHERE '.implode(' AND ',$where);

$total = $db->prepare("SELECT COUNT(*) FROM appointments a $whereStr");
$total->execute($params); $total = (int)$total->fetchColumn();
$pg = getPaginationData($total, $page, $perPage);

$stmt = $db->prepare("
    SELECT a.*, c.name AS clinic_name, s.name AS service_name, p.name AS pet_name,
           CONCAT(uv.first_name,' ',uv.last_name) AS vet_name,
           CONCAT(ug.first_name,' ',ug.last_name) AS groomer_name
    FROM appointments a
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN pets p ON a.pet_id=p.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users uv ON v.user_id=uv.id
    LEFT JOIN groomers g ON a.groomer_id=g.id
    LEFT JOIN users ug ON g.user_id=ug.id
    $whereStr
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Counts per status
$counts = [];
foreach (['pending','approved','completed','cancelled','rejected'] as $s) {
    $cs = $db->prepare("SELECT COUNT(*) FROM appointments WHERE owner_id=? AND status=?");
    $cs->execute([$user['id'], $s]); $counts[$s] = (int)$cs->fetchColumn();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Appointments</span></div>
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <div><h1>My Appointments</h1><p>Track all your pet appointments in one place.</p></div>
      <a href="<?= APP_URL ?>/pages/clinics.php" class="btn btn-primary"><i class="fas fa-hospital"></i> Find a Clinic</a>
    </div>
  </div>

  <!-- Status Filter Tabs -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="appointments.php" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline' ?>">All (<?= array_sum($counts) ?>)</a>
    <?php foreach ($counts as $s => $c): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?> (<?= $c ?>)</a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <?php if ($appointments): ?>
      <table>
        <thead><tr><th>Pet</th><th>Clinic</th><th>Service</th><th>Provider</th><th>Date & Time</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($appointments as $a): ?>
        <tr>
          <td><span style="font-weight:600;"><?= sanitize($a['pet_name']) ?></span></td>
          <td><?= sanitize($a['clinic_name']) ?></td>
          <td><?= sanitize($a['service_name'] ?? ucfirst($a['appointment_type'])) ?></td>
          <td style="font-size:.85rem;">
            <?php if ($a['vet_name']): ?>Dr. <?= sanitize($a['vet_name']) ?><?php endif; ?>
            <?php if ($a['groomer_name']): ?><?= sanitize($a['groomer_name']) ?><?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
          </td>
          <td><?= statusBadge($a['status']) ?></td>
          <td>
            <div style="display:flex;gap:.4rem;">
              <a href="appointment-detail.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
              <?php if (in_array($a['status'],['pending','approved'])): ?>
              <button onclick="openCancelModal(<?= $a['id'] ?>)" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</button>
              <?php endif; ?>
              <?php if ($a['status']==='completed'): ?>
              <?php $rated = $db->prepare('SELECT id FROM ratings WHERE appointment_id=?'); $rated->execute([$a['id']]); ?>
              <?php if (!$rated->fetch()): ?>
              <a href="rate.php?appt=<?= $a['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-star"></i> Rate</a>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state"><i class="fas fa-calendar"></i><h3>No appointments found</h3>
        <p><?= $status ? 'No '.ucfirst($status).' appointments.' : 'You haven\'t booked any appointments yet.' ?></p>
        <a href="<?= APP_URL ?>/pages/clinics.php" class="btn btn-primary mt-2">Find a Clinic</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php renderPagination($pg); ?>
</main>

<!-- Cancel Reason Modal -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <h3><i class="fas fa-times-circle" style="color:var(--danger);margin-right:.5rem"></i>Cancel Appointment</h3>
      <button class="modal-close" onclick="closeModal('cancelModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/api/appointments.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="appointment_id" id="cancelApptId" value="">
      <div class="modal-body">
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.9rem;">Please let us know why you're cancelling. This helps the clinic improve their service.</p>
        <div class="form-group">
          <label class="form-label">Reason for Cancellation</label>
          <select name="cancel_reason_preset" class="form-control" id="cancelReasonPreset" onchange="updateCancelReason()" style="margin-bottom:.6rem;">
            <option value="">— Select a reason —</option>
            <option value="Schedule conflict">Schedule conflict</option>
            <option value="Pet recovered / no longer needed">Pet recovered / no longer needed</option>
            <option value="Found another clinic">Found another clinic</option>
            <option value="Financial reasons">Financial reasons</option>
            <option value="Transportation issues">Transportation issues</option>
            <option value="Other">Other (please specify)</option>
          </select>
          <textarea name="cancel_reason" id="cancelReasonText" class="form-control" rows="3" placeholder="Add more details (optional)…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('cancelModal')">Keep Appointment</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-times-circle"></i> Confirm Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
function openCancelModal(apptId) {
  document.getElementById('cancelApptId').value = apptId;
  document.getElementById('cancelReasonPreset').value = '';
  document.getElementById('cancelReasonText').value = '';
  openModal('cancelModal');
}
function updateCancelReason() {
  const preset = document.getElementById('cancelReasonPreset').value;
  if (preset && preset !== 'Other') {
    document.getElementById('cancelReasonText').value = preset;
  } else if (preset === 'Other') {
    document.getElementById('cancelReasonText').value = '';
    document.getElementById('cancelReasonText').focus();
  }
}
</script>
