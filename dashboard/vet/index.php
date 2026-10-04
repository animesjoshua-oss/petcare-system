<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'Vet Dashboard';
$isDashboard = true; // Flag for header to show correct hamburger menu
$db = getDB();

$vetStmt = $db->prepare('SELECT v.*, c.name AS clinic_name, c.logo AS clinic_logo, c.city AS clinic_city, c.phone AS clinic_phone FROM veterinarians v LEFT JOIN clinics c ON v.clinic_id = c.id WHERE v.user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;

$todayAppts = $db->prepare("SELECT COUNT(*) FROM appointments WHERE vet_id=? AND appointment_date=CURDATE() AND status IN ('approved','pending')");
$todayAppts->execute([$vetId]); $todayCount = (int)$todayAppts->fetchColumn();

$pendingCount = $db->prepare("SELECT COUNT(*) FROM appointments WHERE vet_id=? AND status='pending'");
$pendingCount->execute([$vetId]); $pendingCount = (int)$pendingCount->fetchColumn();

$completedCount = $db->prepare("SELECT COUNT(*) FROM appointments WHERE vet_id=? AND status='completed'");
$completedCount->execute([$vetId]); $completedCount = (int)$completedCount->fetchColumn();

$totalPets = $db->prepare("SELECT COUNT(DISTINCT a.pet_id) FROM appointments a WHERE a.vet_id=?");
$totalPets->execute([$vetId]); $totalPets = (int)$totalPets->fetchColumn();

// Today's appointments
$todayList = $db->prepare("
    SELECT a.*,p.name AS pet_name,p.breed,p.species,
           CONCAT(u.first_name,' ',u.last_name) AS owner_name,
           s.name AS service_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users u ON a.owner_id=u.id
    LEFT JOIN services s ON a.service_id=s.id
    WHERE a.vet_id=? AND a.appointment_date=CURDATE() AND a.status IN ('approved','pending')
    ORDER BY a.appointment_time ASC
");
$todayList->execute([$vetId]); $todayList = $todayList->fetchAll();

// Pending requests
$pendingList = $db->prepare("
    SELECT a.*,p.name AS pet_name,p.species,p.breed,
           CONCAT(u.first_name,' ',u.last_name) AS owner_name,
           c.name AS clinic_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users u ON a.owner_id=u.id
    JOIN clinics c ON a.clinic_id=c.id
    WHERE a.vet_id=? AND a.status='pending'
    ORDER BY a.appointment_date ASC
    LIMIT 8
");
$pendingList->execute([$vetId]); $pendingList = $pendingList->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <h1>Good <?= date('H')<12?'Morning':(date('H')<18?'Afternoon':'Evening') ?>, Dr. <?= sanitize($user['first_name']) ?>!</h1>
    <p>Here's your schedule and patient overview for today.</p>
  </div>

  <!-- Clinic Information Card -->
  <?php if ($vet && !empty($vet['clinic_name'])): ?>
  <div class="card" style="margin-bottom:1.5rem;background:linear-gradient(135deg,rgba(30,64,255,0.05),rgba(0,212,170,0.05));border:1px solid rgba(30,64,255,0.1);">
    <div class="card-body" style="display:flex;align-items:center;gap:1.5rem;">
      <?php if (!empty($vet['clinic_logo'])): ?>
        <img src="/<?= sanitize($vet['clinic_logo']) ?>" alt="Clinic Logo" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <?php else: ?>
        <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
          <i class="fas fa-hospital" style="font-size:1.5rem;color:#fff;"></i>
        </div>
      <?php endif; ?>
      <div style="flex:1;">
        <div style="font-size:.75rem;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">
          <i class="fas fa-hospital"></i> Your Clinic
        </div>
        <h3 style="font-weight:700;font-size:1.25rem;margin-bottom:.3rem;"><?= sanitize($vet['clinic_name']) ?></h3>
        <div style="display:flex;gap:1.5rem;font-size:.85rem;color:var(--text-muted);">
          <?php if (!empty($vet['clinic_city'])): ?>
            <span><i class="fas fa-map-marker-alt" style="color:var(--primary);margin-right:.3rem;"></i><?= sanitize($vet['clinic_city']) ?></span>
          <?php endif; ?>
          <?php if (!empty($vet['clinic_phone'])): ?>
            <span><i class="fas fa-phone" style="color:var(--secondary);margin-right:.3rem;"></i><?= sanitize($vet['clinic_phone']) ?></span>
          <?php endif; ?>
          <?php if (!empty($vet['specialization'])): ?>
            <span><i class="fas fa-stethoscope" style="color:var(--warning);margin-right:.3rem;"></i><?= sanitize($vet['specialization']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <a href="<?= APP_URL ?>/pages/clinic.php?id=<?= $vet['clinic_id'] ?>" class="btn btn-primary" style="flex-shrink:0;">
        <i class="fas fa-external-link-alt"></i> View Clinic Page
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <?= renderStatsRow([
    ['value' => $todayCount, 'label' => "Today's Appointments", 'icon' => 'fa-calendar-day', 'color' => 'blue'],
    ['value' => $pendingCount, 'label' => 'Pending Requests', 'icon' => 'fa-clock', 'color' => 'orange'],
    ['value' => $completedCount, 'label' => 'Completed', 'icon' => 'fa-check-circle', 'color' => 'green'],
    ['value' => $totalPets, 'label' => 'Patients Seen', 'icon' => 'fa-paw', 'color' => 'purple']
  ]) ?>

  <div class="dash-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <!-- Today Schedule -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-calendar-day" style="color:var(--primary);margin-right:.4rem"></i>Today's Schedule</h3>
        <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if ($todayList): foreach ($todayList as $a): ?>
        <div style="display:flex;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border);align-items:center;">
          <div style="min-width:60px;text-align:center;background:var(--bg);border-radius:var(--radius-sm);padding:.5rem;">
            <div style="font-weight:800;font-size:1rem;"><?= date('g:i',strtotime($a['appointment_time'])) ?></div>
            <div style="font-size:.7rem;color:var(--text-muted)"><?= date('A',strtotime($a['appointment_time'])) ?></div>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:.9rem;"><?= sanitize($a['pet_name']) ?> <span style="font-weight:400;color:var(--text-muted)">(<?= sanitize($a['breed']?:$a['species']) ?>)</span></div>
            <div style="font-size:.8rem;color:var(--text-muted)">Owner: <?= sanitize($a['owner_name']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['service_name'] ?? 'Consultation') ?></div>
          </div>
          <?= statusBadge($a['status']) ?>
        </div>
        <?php endforeach; else: ?>
        <?= renderEmptyState('fa-calendar-check', 'No appointments today', '', '', '', '2rem') ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pending Requests -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-inbox" style="color:var(--warning);margin-right:.4rem"></i>Pending Requests</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if ($pendingList): foreach ($pendingList as $a): ?>
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
          <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
            <div>
              <div style="font-weight:700;font-size:.9rem;"><?= sanitize($a['pet_name']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['owner_name']) ?> · <?= date('M j',strtotime($a['appointment_date'])) ?> at <?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
            </div>
          </div>
          <?php
            $extraActions = '<a href="records.php?pet='.$a['pet_id'].'" class="btn btn-sm btn-outline"><i class="fas fa-file-medical"></i> Records</a>';
            include __DIR__ . '/../../includes/appt_actions.php';
          ?>
        </div>
        <?php endforeach; else: ?>
        <?= renderEmptyState('fa-inbox', 'No pending requests', '', '', '', '2rem') ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
