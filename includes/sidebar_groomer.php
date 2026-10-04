<?php
/**
 * Shared Groomer Sidebar
 * Requires $user to be set before including.
 * Automatically highlights the active link based on the current filename.
 */
require_once __DIR__ . '/functions.php';
requireLogin(); requireRole(['groomer']);
$user        = currentUser();
$isDashboard  = true;
$db           = getDB();

$gStmt = $db->prepare("SELECT id FROM groomers WHERE user_id=?");
$gStmt->execute([$user['id']]);
$gId = (int)($gStmt->fetchColumn() ?: 0);

$pStmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE groomer_id=? AND status='pending'");
$pStmt->execute([$gId]);
$pendingCount = (int)$pStmt->fetchColumn();

$nc      = unreadNotificationCount($user['id']);
$curPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-user">
      <div class="user-avatar-placeholder">
        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
      </div>
      <div>
        <div class="sidebar-user-name"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="sidebar-user-role" style="color:var(--warning);">Groomer</div>
      </div>
    </div>
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="<?= APP_URL ?>/dashboard/groomer/index.php"        class="sidebar-link<?= $curPage==='index.php'?' active':'' ?>">
      <i class="fas fa-tachometer-alt"></i><span class="sidebar-link-text"> Dashboard</span>
    </a>
    <a href="appointments.php" class="sidebar-link<?= $curPage==='appointments.php'?' active':'' ?>">
      <i class="fas fa-calendar-alt"></i><span class="sidebar-link-text"> Schedule</span>
      <?php if ($pendingCount > 0): ?><span class="sidebar-badge"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="ratings.php" class="sidebar-link<?= $curPage==='ratings.php'?' active':'' ?>">
      <i class="fas fa-star"></i><span class="sidebar-link-text"> Ratings &amp; Feedback</span>
    </a>
    <a href="profile.php" class="sidebar-link<?= $curPage==='profile.php'?' active':'' ?>">
      <i class="fas fa-id-badge"></i><span class="sidebar-link-text"> My Pro Profile</span>
    </a>

    <a href="<?= APP_URL ?>/auth/logout.php" class="sidebar-link sidebar-logout">
      <i class="fas fa-sign-out-alt"></i><span class="sidebar-link-text"> Logout</span>
    </a>
  </nav>
</aside>
