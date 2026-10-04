<?php
require_once __DIR__ . '/functions.php';
requireLogin();
requireRole(['pet_owner']);
$user = currentUser();
$isDashboard = true;
$pendingAppts = getDB()->prepare("SELECT COUNT(*) FROM appointments WHERE owner_id=? AND status='pending'");
$pendingAppts->execute([$user['id']]);
$pendingCount = (int)$pendingAppts->fetchColumn();
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-user">
      <?php if ($user['profile_photo']): ?>
        <img src="<?= sanitize($user['profile_photo']) ?>" class="user-avatar" alt="">
      <?php else: ?>
        <div class="user-avatar-placeholder"><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?></div>
      <?php endif; ?>
      <div>
        <div class="sidebar-user-name"><?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
        <div class="sidebar-user-role">Pet Owner</div>
      </div>
    </div>
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="<?= APP_URL ?>/dashboard/owner/index.php"        class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-link-text"> Dashboard</span></a>
    <a href="<?= APP_URL ?>/dashboard/owner/pets.php"         class="sidebar-link"><i class="fas fa-dog"></i><span class="sidebar-link-text"> My Pets</span></a>

    <div class="sidebar-section">Appointments</div>
    <a href="<?= APP_URL ?>/pages/clinics.php"                 class="sidebar-link"><i class="fas fa-hospital"></i><span class="sidebar-link-text"> Find a Clinic</span></a>
    <a href="<?= APP_URL ?>/dashboard/owner/appointments.php" class="sidebar-link">
      <i class="fas fa-calendar-alt"></i><span class="sidebar-link-text"> My Appointments</span>
      <?php if ($pendingCount): ?><span class="sidebar-badge"><?= $pendingCount ?></span><?php endif; ?>
    </a>

    <a href="<?= APP_URL ?>/dashboard/owner/ratings.php"      class="sidebar-link"><i class="fas fa-star"></i><span class="sidebar-link-text"> My Ratings</span></a>
    <a href="<?= APP_URL ?>/dashboard/owner/favorites.php"    class="sidebar-link"><i class="fas fa-heart"></i><span class="sidebar-link-text"> Favorite Clinics</span></a>
    <?php
    // Unread reply count
    try {
        $ownerMsgReplies = (int)getDB()->prepare("
            SELECT COUNT(*) FROM contact_messages
            WHERE user_id = ? AND status = 'replied'
        ")->execute([$user['id']]) ? getDB()->prepare("
            SELECT COUNT(*) FROM contact_messages
            WHERE user_id = ? AND status = 'replied'
        ")->execute([$user['id']]) : 0;
        $msgsStmt = getDB()->prepare("SELECT COUNT(*) FROM contact_messages WHERE user_id=? AND status='replied'");
        $msgsStmt->execute([$user['id']]);
        $ownerMsgReplies = (int)$msgsStmt->fetchColumn();
    } catch (\Exception $e) { $ownerMsgReplies = 0; }
    ?>
    <a href="<?= APP_URL ?>/dashboard/owner/my-messages.php" class="sidebar-link">
      <i class="fas fa-envelope"></i>
      <span class="sidebar-link-text"> My Messages</span>
      <?php if ($ownerMsgReplies > 0): ?>
        <span class="sidebar-badge"><?= $ownerMsgReplies ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/auth/logout.php"                       class="sidebar-link sidebar-logout"><i class="fas fa-sign-out-alt"></i><span class="sidebar-link-text"> Logout</span></a>
  </nav>
</aside>
