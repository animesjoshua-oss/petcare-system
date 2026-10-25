<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Manage Appointments';
$isDashboard = true;
$db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);

if ($isGroomerAdmin) {
    $pageTitle = 'Grooming Appointments';
    $clinicType = 'grooming';
} elseif (!$isSuperAdmin) {
    $pageTitle = 'Veterinary Appointments';
    $clinicType = 'veterinary';
}

$statusOptions = [
    'pending' => 'Scheduled',
    'approved' => 'Confirmed',
    'rescheduled' => 'Rescheduled',
    'completed' => 'Completed',
    'no_show' => 'No-show',
    'rejected' => 'Rejected',
    'cancelled' => 'Cancelled',
];
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$date = sanitize($_GET['date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];
if (!$isSuperAdmin) {
    $where[] = 'a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type=?)';
    $params[] = $user['id'];
    $params[] = $clinicType;
    $where[] = 'a.appointment_type=?';
    $params[] = $clinicType;
}
if (isset($statusOptions[$status])) {
    $where[] = 'a.status=?';
    $params[] = $status;
}
if ($date) {
    $where[] = 'a.appointment_date=?';
    $params[] = $date;
}
if ($search) {
    $where[] = '(p.name LIKE ? OR ou.first_name LIKE ? OR ou.last_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$totalQuery = $db->prepare("SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users ou ON a.owner_id=ou.id $whereSql");
$totalQuery->execute($params);
$total = (int)$totalQuery->fetchColumn();
$pagination = getPaginationData($total, $page, $perPage);

$appointmentsQuery = $db->prepare("
    SELECT a.*, p.name AS pet_name,
           CONCAT(ou.first_name,' ',ou.last_name) AS owner_name,
           s.name AS service_name,
           CONCAT(uv.first_name,' ',uv.last_name) AS vet_name,
           CONCAT(ug.first_name,' ',ug.last_name) AS groomer_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users ou ON a.owner_id=ou.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users uv ON v.user_id=uv.id
    LEFT JOIN groomers g ON a.groomer_id=g.id
    LEFT JOIN users ug ON g.user_id=ug.id
    $whereSql
    ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC
    LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}
");
$appointmentsQuery->execute($params);
$appointments = $appointmentsQuery->fetchAll();

$extraCss = '<link rel="stylesheet" href="' . APP_URL . '/assets/css/admin-appointments.css?v=' . time() . '">';
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main admin-appointments-page">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Appointments</span></div>
    <h1><?= $isSuperAdmin ? 'Manage Appointments' : ($isGroomerAdmin ? 'Grooming Appointments' : 'Veterinary Appointments') ?></h1>
  </div>

  <form method="GET" class="appointment-filters">
    <div class="form-group appointment-search-field">
      <label class="form-label" for="appointmentSearch">Search pet or owner</label>
      <input id="appointmentSearch" type="search" name="search" class="form-control" placeholder="Pet name or owner" value="<?= sanitize($search) ?>">
    </div>
    <div class="form-group appointment-status-field">
      <label class="form-label" for="appointmentStatus">Status</label>
      <select id="appointmentStatus" name="status" class="form-control">
        <option value="">All statuses</option>
        <?php foreach ($statusOptions as $value => $label): ?>
        <option value="<?= sanitize($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= sanitize($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group appointment-date-field">
      <label class="form-label" for="appointmentDate">Date</label>
      <input id="appointmentDate" type="date" name="date" class="form-control" value="<?= sanitize($date) ?>">
    </div>
    <button class="btn btn-primary appointment-filter-submit" type="submit">Filter</button>
    <?php if ($search || $date || $status): ?>
    <a class="btn btn-outline appointment-filter-clear" href="appointments.php">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!empty($_SESSION['appointment_flash'])): $appointmentFlash = $_SESSION['appointment_flash']; unset($_SESSION['appointment_flash']); ?>
  <div class="alert <?= ($appointmentFlash['type'] ?? '') === 'danger' ? 'alert-danger' : 'alert-success' ?>" role="status">
    <?= sanitize($appointmentFlash['message'] ?? '') ?>
  </div>
  <?php endif; ?>

  <section class="appointment-list" aria-label="Appointments">
    <?php if ($appointments): ?>
      <?php $currentDate = null; $dateCounts = array_count_values(array_column($appointments, 'appointment_date')); ?>
      <?php foreach ($appointments as $appointment): ?>
        <?php
          $appointmentDate = $appointment['appointment_date'];
          if ($currentDate !== $appointmentDate):
              $currentDate = $appointmentDate;
        ?>
        <div class="appointment-date-heading">
          <h2><?= date('l, M j', strtotime($currentDate)) ?></h2>
          <span><?= (int)$dateCounts[$currentDate] ?> appointment<?= $dateCounts[$currentDate] === 1 ? '' : 's' ?></span>
        </div>
        <?php endif; ?>

        <?php
          $appointmentStatus = $appointment['status'];
          $statusLabel = $statusOptions[$appointmentStatus] ?? ucfirst(str_replace('_', ' ', $appointmentStatus));
          $serviceLabel = $appointment['service_name'] ?: ucfirst($appointment['appointment_type']);
          if ($appointment['appointment_type'] === 'grooming') {
              $staffName = $appointment['groomer_name'];
          } else {
              $staffName = $appointment['vet_name'] ? 'Dr. ' . $appointment['vet_name'] : '';
          }
          $appointmentStart = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
          $durationMinutes = max(1, (int)($appointment['duration_minutes'] ?? 30));
          $appointmentEnd = $appointmentStart + ($durationMinutes * 60);
          $canReschedule = in_array($appointmentStatus, ['pending', 'approved', 'rescheduled'], true);
          $canConfirm = in_array($appointmentStatus, ['pending', 'rescheduled'], true);
          $canComplete = $appointmentStatus === 'approved';
          $canNoShow = $appointmentStatus === 'approved' && $appointmentEnd <= time();
          $canCancel = $canReschedule;
          $timeValue = substr((string)$appointment['appointment_time'], 0, 5);
          $statusClass = [
              'pending' => 'scheduled', 'approved' => 'confirmed', 'rescheduled' => 'rescheduled',
              'completed' => 'completed', 'no_show' => 'no-show', 'rejected' => 'rejected', 'cancelled' => 'cancelled',
          ][$appointmentStatus] ?? 'cancelled';
        ?>
        <article class="appointment-row">
          <div class="appointment-time">
            <strong><?= date('g:i A', $appointmentStart) ?>–<?= date('g:i A', $appointmentEnd) ?></strong>
          </div>
          <div class="appointment-details">
            <strong class="appointment-service"><?= sanitize($serviceLabel) ?></strong>
            <span class="appointment-owner-staff">
              <?= sanitize($appointment['owner_name']) ?><?php if ($staffName): ?> · <?= sanitize($staffName) ?><?php endif; ?>
            </span>
          </div>
          <span class="appointment-status appointment-status-<?= sanitize($statusClass) ?>">
            <span class="appointment-status-dot" aria-hidden="true"></span><?= sanitize($statusLabel) ?>
          </span>
          <div class="appointment-actions" aria-label="Appointment actions">
            <button type="button" class="appointment-action" <?= $canReschedule ? 'onclick="openRescheduleModal(' . (int)$appointment['id'] . ',\'' . sanitize($appointmentDate) . '\',\'' . sanitize($timeValue) . '\')"' : 'disabled title="This appointment can no longer be rescheduled."' ?>>Reschedule</button>
            <?php if ($canConfirm): ?>
            <form method="POST" action="../../api/appointments.php" class="appointment-action-form">
              <?= csrfField() ?><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><input type="hidden" name="action" value="approve">
              <button class="appointment-action" type="submit">Confirmed</button>
            </form>
            <?php else: ?><button type="button" class="appointment-action" disabled title="Only scheduled appointments can be confirmed.">Confirmed</button><?php endif; ?>
            <?php if ($canComplete): ?>
            <form method="POST" action="../../api/appointments.php" class="appointment-action-form">
              <?= csrfField() ?><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><input type="hidden" name="action" value="complete">
              <button class="appointment-action" type="submit">Completed</button>
            </form>
            <?php else: ?><button type="button" class="appointment-action" disabled title="Confirm the appointment before marking it completed.">Completed</button><?php endif; ?>
            <?php if ($canNoShow): ?>
            <form method="POST" action="../../api/appointments.php" class="appointment-action-form">
              <?= csrfField() ?><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><input type="hidden" name="action" value="no_show">
              <button class="appointment-action" type="submit">No-show</button>
            </form>
            <?php else: ?><button type="button" class="appointment-action" disabled title="A confirmed appointment can be marked no-show after its scheduled end time.">No-show</button><?php endif; ?>
            <?php if ($canCancel): ?>
            <form method="POST" action="../../api/appointments.php" class="appointment-action-form">
              <?= csrfField() ?><input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>"><input type="hidden" name="action" value="cancel">
              <button class="appointment-action appointment-action-cancel" type="submit">Cancel</button>
            </form>
            <?php else: ?><button type="button" class="appointment-action" disabled title="This appointment can no longer be cancelled.">Cancel</button><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-calendar"></i><h3>No appointments found</h3></div>
    <?php endif; ?>
  </section>

  <?php renderPagination($pagination); ?>
</main>

<div class="modal-overlay" id="rescheduleModal" aria-hidden="true">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3>Reschedule appointment</h3>
      <button class="modal-close" type="button" onclick="closeModal('rescheduleModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="../../api/appointments.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="appointment_id" id="rescheduleAppointmentId" value="">
      <div class="modal-body">
        <p class="reschedule-hint">Choose a new date and a 30-minute start time within the clinic’s posted hours.</p>
        <div class="form-group">
          <label class="form-label" for="rescheduleDate">Date</label>
          <input type="date" name="reschedule_date" id="rescheduleDate" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="rescheduleTime">Start time</label>
          <input type="time" name="reschedule_time" id="rescheduleTime" class="form-control" step="1800" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('rescheduleModal')">Keep current time</button>
        <button type="submit" class="btn btn-primary">Save new time</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
function openRescheduleModal(appointmentId, appointmentDate, appointmentTime) {
  document.getElementById('rescheduleAppointmentId').value = appointmentId;
  document.getElementById('rescheduleDate').value = appointmentDate;
  document.getElementById('rescheduleTime').value = appointmentTime;
  openModal('rescheduleModal');
}
</script>
