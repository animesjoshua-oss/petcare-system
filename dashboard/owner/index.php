<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'My Dashboard';

$db = getDB();
// Widgets
$totalPets   = $db->prepare("SELECT COUNT(*) FROM pets WHERE owner_id=? AND is_active=1"); $totalPets->execute([$user['id']]); $totalPets = (int)$totalPets->fetchColumn();
$upcoming    = $db->prepare("SELECT COUNT(*) FROM appointments WHERE owner_id=? AND status IN ('pending','approved') AND appointment_date >= CURDATE()"); $upcoming->execute([$user['id']]); $upcoming = (int)$upcoming->fetchColumn();
$completed   = $db->prepare("SELECT COUNT(*) FROM appointments WHERE owner_id=? AND status='completed'"); $completed->execute([$user['id']]); $completed = (int)$completed->fetchColumn();
$totalAppts  = $db->prepare("SELECT COUNT(*) FROM appointments WHERE owner_id=?"); $totalAppts->execute([$user['id']]); $totalAppts = (int)$totalAppts->fetchColumn();

// Upcoming appointments
$stmtAppts = $db->prepare("
    SELECT a.*, c.name AS clinic_name, s.name AS service_name, p.name AS pet_name,
           u.first_name AS vet_first, u.last_name AS vet_last
    FROM appointments a
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN pets p ON a.pet_id=p.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users u ON v.user_id=u.id
    WHERE a.owner_id=? AND a.status IN ('pending','approved') AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
");
$stmtAppts->execute([$user['id']]); $upcomingAppts = $stmtAppts->fetchAll();

// My pets
$myPets = $db->prepare("SELECT * FROM pets WHERE owner_id=? AND is_active=1 ORDER BY name LIMIT 6");
$myPets->execute([$user['id']]); $myPets = $myPets->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>

<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="<?= APP_URL ?>">Home</a><i class="fas fa-chevron-right" style="font-size:.65rem"></i><span>Dashboard</span></div>
    <h1>Good <?= date('H')<12?'Morning':(date('H')<18?'Afternoon':'Evening') ?>, <?= sanitize($user['first_name']) ?>! 👋</h1>
    <p>Here's an overview of your pets' health and upcoming appointments.</p>
  </div>

  <!-- Stats -->
  <?= renderStatsRow([
    ['value' => $totalPets, 'label' => 'My Pets', 'icon' => 'fa-dog', 'color' => 'blue', 'href' => APP_URL . '/dashboard/owner/pets.php'],
    ['value' => $upcoming, 'label' => 'Upcoming', 'icon' => 'fa-calendar', 'color' => 'orange', 'href' => APP_URL . '/dashboard/owner/appointments.php?status=pending'],
    ['value' => $completed, 'label' => 'Completed', 'icon' => 'fa-check-circle', 'color' => 'green', 'href' => APP_URL . '/dashboard/owner/appointments.php?status=completed'],
    ['value' => $totalAppts, 'label' => 'Total Appointments', 'icon' => 'fa-calendar-alt', 'color' => 'purple', 'href' => APP_URL . '/dashboard/owner/appointments.php']
  ]) ?>

  <div class="dash-two-col" style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Upcoming Appointments -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:.5rem"></i>Upcoming Appointments</h3>
        <a href="<?= APP_URL ?>/dashboard/owner/appointments.php" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="table-wrap">
        <?php if ($upcomingAppts): ?>
        <table>
          <thead><tr><th>Pet</th><th>Clinic</th><th>Service</th><th>Date & Time</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($upcomingAppts as $a): ?>
          <tr>
            <td><span style="font-weight:600;"><?= sanitize($a['pet_name']) ?></span></td>
            <td><?= sanitize($a['clinic_name']) ?></td>
            <td><?= sanitize($a['service_name'] ?? $a['appointment_type']) ?></td>
            <td>
              <div style="font-weight:600;"><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
              <div style="font-size:.8rem;color:var(--text-muted)"><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
            </td>
            <td><?= statusBadge($a['status']) ?></td>
            <td>
              <?php if ($a['status']==='pending' || $a['status']==='approved'): ?>
              <form method="POST" action="<?= APP_URL ?>/api/appointments.php" onsubmit="return confirm('Cancel this appointment?')" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <?= renderEmptyState('fa-calendar', 'No upcoming appointments', '', 'Find a clinic', APP_URL . '/pages/clinics.php') ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Side Column -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <!-- Quick Actions -->
      <div class="card"><div class="card-body">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Quick Actions</h3>
        <div style="display:flex;flex-direction:column;gap:.65rem;">
          <a href="<?= APP_URL ?>/pages/clinics.php" class="btn btn-primary btn-block"><i class="fas fa-hospital"></i> Choose a Clinic</a>
          <a href="<?= APP_URL ?>/dashboard/owner/pets.php?action=add" class="btn btn-secondary btn-block"><i class="fas fa-plus"></i> Add Pet</a>
        </div>
      </div></div>

      <!-- Next Upcoming Appointment Reminder -->
      <?php if (!empty($upcomingAppts)): $next = $upcomingAppts[0]; ?>
      <div class="card" style="border-left:4px solid var(--primary);">
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;background:rgba(30,64,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-calendar-check" style="color:var(--primary);font-size:.9rem;"></i>
            </div>
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">Next Appointment</div>
              <div style="font-size:.9rem;font-weight:700;"><?= sanitize($next['pet_name']) ?></div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:.35rem;font-size:.83rem;color:var(--text-muted);">
            <div><i class="fas fa-hospital-alt" style="width:16px;color:var(--primary);"></i> <?= sanitize($next['clinic_name']) ?></div>
            <div><i class="fas fa-calendar" style="width:16px;color:var(--primary);"></i> <?= date('D, M j, Y', strtotime($next['appointment_date'])) ?></div>
            <div><i class="fas fa-clock" style="width:16px;color:var(--primary);"></i> <?= date('g:i A', strtotime($next['appointment_time'])) ?></div>
            <div><i class="fas fa-tag" style="width:16px;color:var(--primary);"></i> <?= sanitize($next['service_name'] ?? ucfirst($next['appointment_type'])) ?></div>
          </div>
          <div style="margin-top:.85rem;">
            <?= statusBadge($next['status']) ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- My Pets -->
  <div class="card mt-4">
    <div class="card-header">
      <h3><i class="fas fa-paw" style="color:var(--primary);margin-right:.5rem"></i>My Pets</h3>
      <a href="<?= APP_URL ?>/dashboard/owner/pets.php" class="btn btn-sm btn-outline">Manage Pets</a>
    </div>
    <div class="card-body">
      <?php if ($myPets): ?>
      <div class="pets-grid">
        <?php foreach ($myPets as $pet): echo renderPetCard($pet, false); endforeach; ?>
      </div>
      <?php else: ?>
      <?= renderEmptyState('fa-dog', 'No pets yet', '', 'Add your first pet', 'pets.php?action=add') ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
