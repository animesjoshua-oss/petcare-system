<?php
require_once __DIR__ . '/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$isDashboard = true;
$db = getDB();
$pending = $db->prepare("SELECT COUNT(*) FROM appointments a JOIN veterinarians v ON a.vet_id=v.id WHERE v.user_id=? AND a.status='pending'");
$pending->execute([$user['id']]); $pendingCount = (int)$pending->fetchColumn();
$clinicNames = clinicNamesForUser($user);
$curPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-user">
      <div class="user-avatar-placeholder"><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?></div>
      <div>
        <div class="sidebar-user-name">Dr. <?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
        <div class="sidebar-user-role" style="color:var(--secondary);">Veterinarian</div>
        <?php if ($clinicNames): ?><div style="font-size:.72rem;color:var(--text-muted);max-width:165px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= sanitize(implode(', ', $clinicNames)) ?>"><?= sanitize(implode(', ', $clinicNames)) ?></div><?php endif; ?>
      </div>
    </div>
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Dashboard</div>
    <a href="<?= APP_URL ?>/dashboard/vet/index.php"        class="sidebar-link<?= $curPage==='index.php'?' active':'' ?>"><i class="fas fa-tachometer-alt"></i><span class="sidebar-link-text"> Dashboard</span></a>
    <a href="<?= APP_URL ?>/dashboard/vet/appointments.php" class="sidebar-link<?= $curPage==='appointments.php'?' active':'' ?>">
      <i class="fas fa-calendar-alt"></i><span class="sidebar-link-text"> Appointments</span>
      <?php if ($pendingCount): ?><span class="sidebar-badge"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <div class="sidebar-section">Medical</div>
    <a href="<?= APP_URL ?>/dashboard/vet/records.php"      class="sidebar-link<?= $curPage==='records.php'?' active':'' ?>"><i class="fas fa-file-medical"></i><span class="sidebar-link-text"> Medical Records</span></a>
    <a href="<?= APP_URL ?>/dashboard/vet/ratings.php"      class="sidebar-link<?= $curPage==='ratings.php'?' active':'' ?>"><i class="fas fa-star"></i><span class="sidebar-link-text"> Ratings &amp; Feedback</span></a>
    <a href="<?= APP_URL ?>/dashboard/vet/profile.php"      class="sidebar-link<?= $curPage==='profile.php'?' active':'' ?>"><i class="fas fa-id-badge"></i><span class="sidebar-link-text"> My Pro Profile</span></a>
    <a href="<?= APP_URL ?>/auth/logout.php"                     class="sidebar-link sidebar-logout"><i class="fas fa-sign-out-alt"></i><span class="sidebar-link-text"> Logout</span></a>
  </nav>
</aside>
