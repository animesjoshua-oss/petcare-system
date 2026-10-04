<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['groomer']);
$user = currentUser();
$pageTitle = 'Groomer Dashboard';
$isDashboard = true; // Flag for header to show correct hamburger menu
$db = getDB();

$gStmt = $db->prepare('SELECT g.*, c.name AS clinic_name, c.logo AS clinic_logo, c.city AS clinic_city, c.phone AS clinic_phone FROM groomers g LEFT JOIN clinics c ON g.clinic_id = c.id WHERE g.user_id=?');
$gStmt->execute([$user['id']]); $groomer = $gStmt->fetch();
$gId = $groomer['id'] ?? 0;

$todayCount = $db->prepare("SELECT COUNT(*) FROM appointments WHERE groomer_id=? AND appointment_date=CURDATE() AND status IN ('approved','pending')");
$todayCount->execute([$gId]); $todayCount=(int)$todayCount->fetchColumn();

$pendingCount = $db->prepare("SELECT COUNT(*) FROM appointments WHERE groomer_id=? AND status='pending'");
$pendingCount->execute([$gId]); $pendingCount=(int)$pendingCount->fetchColumn();

$weekCount = $db->prepare("SELECT COUNT(*) FROM appointments WHERE groomer_id=? AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND status='approved'");
$weekCount->execute([$gId]); $weekCount=(int)$weekCount->fetchColumn();

$completedTotal = $db->prepare("SELECT COUNT(*) FROM appointments WHERE groomer_id=? AND status='completed'");
$completedTotal->execute([$gId]); $completedTotal=(int)$completedTotal->fetchColumn();

// Today list
$todayList = $db->prepare("SELECT a.*,p.name AS pet_name,p.breed,p.species,CONCAT(u.first_name,' ',u.last_name) AS owner_name,s.name AS service_name FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users u ON a.owner_id=u.id LEFT JOIN services s ON a.service_id=s.id WHERE a.groomer_id=? AND a.appointment_date=CURDATE() ORDER BY a.appointment_time ASC");
$todayList->execute([$gId]); $todayList=$todayList->fetchAll();

// Upcoming week
$weekList = $db->prepare("SELECT a.*,p.name AS pet_name,p.breed,p.species,CONCAT(u.first_name,' ',u.last_name) AS owner_name FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users u ON a.owner_id=u.id WHERE a.groomer_id=? AND a.appointment_date BETWEEN DATE_ADD(CURDATE(),INTERVAL 1 DAY) AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND a.status='approved' ORDER BY a.appointment_date ASC,a.appointment_time ASC LIMIT 8");
$weekList->execute([$gId]); $weekList=$weekList->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_groomer.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <h1>Hello, <?= sanitize($user['first_name']) ?>! ✂️</h1>
    <p>Your grooming schedule and appointments.</p>
  </div>

  <!-- Clinic Information Card -->
  <?php if ($groomer && !empty($groomer['clinic_name'])): ?>
  <div class="card" style="margin-bottom:1.5rem;background:linear-gradient(135deg,rgba(245,158,11,0.05),rgba(239,68,68,0.05));border:1px solid rgba(245,158,11,0.1);">
    <div class="card-body" style="display:flex;align-items:center;gap:1.5rem;">
      <?php if (!empty($groomer['clinic_logo'])): ?>
        <img src="/<?= sanitize($groomer['clinic_logo']) ?>" alt="Clinic Logo" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <?php else: ?>
        <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#F59E0B,#EF4444);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
          <i class="fas fa-cut" style="font-size:1.5rem;color:#fff;"></i>
        </div>
      <?php endif; ?>
      <div style="flex:1;">
        <div style="font-size:.75rem;font-weight:600;color:var(--warning);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">
          <i class="fas fa-building"></i> Your Grooming Center
        </div>
        <h3 style="font-weight:700;font-size:1.25rem;margin-bottom:.3rem;"><?= sanitize($groomer['clinic_name']) ?></h3>
        <div style="display:flex;gap:1.5rem;font-size:.85rem;color:var(--text-muted);">
          <?php if (!empty($groomer['clinic_city'])): ?>
            <span><i class="fas fa-map-marker-alt" style="color:var(--primary);margin-right:.3rem;"></i><?= sanitize($groomer['clinic_city']) ?></span>
          <?php endif; ?>
          <?php if (!empty($groomer['clinic_phone'])): ?>
            <span><i class="fas fa-phone" style="color:var(--secondary);margin-right:.3rem;"></i><?= sanitize($groomer['clinic_phone']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <a href="<?= APP_URL ?>/pages/clinic.php?id=<?= $groomer['clinic_id'] ?>" class="btn" style="background:var(--warning);color:#fff;flex-shrink:0;">
        <i class="fas fa-external-link-alt"></i> View Clinic Page
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?= renderStatsRow([
    ['value' => $todayCount, 'label' => 'Today', 'icon' => 'fa-calendar-day', 'color' => 'orange'],
    ['value' => $pendingCount, 'label' => 'Pending', 'icon' => 'fa-clock', 'color' => 'blue'],
    ['value' => $weekCount, 'label' => 'This Week', 'icon' => 'fa-calendar-week', 'color' => 'green'],
    ['value' => $completedTotal, 'label' => 'Completed', 'icon' => 'fa-check-circle', 'color' => 'purple']
  ]) ?>

  <div class="dash-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <!-- Today -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-calendar-day" style="color:var(--warning);margin-right:.4rem"></i>Today's Appointments</h3><a href="appointments.php" class="btn btn-sm btn-outline">All</a></div>
      <div class="card-body" style="padding:0;">
        <?php if ($todayList): foreach ($todayList as $a): ?>
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;gap:1rem;align-items:center;">
          <div style="min-width:55px;text-align:center;background:var(--bg);border-radius:var(--radius-sm);padding:.4rem;">
            <div style="font-weight:800;font-size:.95rem;"><?= date('g:i',strtotime($a['appointment_time'])) ?></div>
            <div style="font-size:.65rem;color:var(--text-muted)"><?= date('A',strtotime($a['appointment_time'])) ?></div>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;"><?= sanitize($a['pet_name']) ?> <span style="font-weight:400;font-size:.8rem;color:var(--text-muted)">(<?= sanitize($a['breed']?:$a['species']) ?>)</span></div>
            <div style="font-size:.8rem;color:var(--text-muted)"><?= sanitize($a['owner_name']) ?> · <?= sanitize($a['service_name']??'Grooming') ?></div>
          </div>
          <div style="display:flex;flex-direction:column;gap:.35rem;">
            <?= statusBadge($a['status']) ?>
            <?php if ($a['status']==='pending'): ?>
            <?php $extraActions = ''; include __DIR__ . '/../../includes/appt_actions.php'; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <?= renderEmptyState('fa-calendar-check', 'No appointments today', '', '', '', '2rem') ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming Week -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-calendar-week" style="color:var(--primary);margin-right:.4rem"></i>Upcoming This Week</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if ($weekList): foreach ($weekList as $a): ?>
        <div style="padding:.85rem 1.25rem;border-bottom:1px solid var(--border);display:flex;gap:.75rem;align-items:center;">
          <div style="min-width:50px;text-align:center;background:rgba(30,64,255,.06);border-radius:var(--radius-sm);padding:.35rem;">
            <div style="font-weight:800;font-size:.8rem;color:var(--primary);"><?= date('M j',strtotime($a['appointment_date'])) ?></div>
            <div style="font-size:.65rem;color:var(--text-muted)"><?= date('D',strtotime($a['appointment_date'])) ?></div>
          </div>
          <div>
            <div style="font-weight:600;font-size:.875rem;"><?= sanitize($a['pet_name']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['owner_name']) ?> · <?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
          </div>
        </div>
        <?php endforeach; else: ?>
        <?= renderEmptyState('fa-calendar', 'No upcoming appointments', '', '', '', '2rem') ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
