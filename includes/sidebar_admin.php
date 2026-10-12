<?php
/**
 * Shared Admin Sidebar
 * Requires $user to be set before including.
 * Automatically highlights the active link based on the current filename.
 */
require_once __DIR__ . '/functions.php';
requireLogin(); requireRole(['admin', 'superadmin']);
$user       = currentUser();
$isDashboard = true;
$db         = getDB();

$pendingAppts = (int)$db->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();
$nc           = unreadNotificationCount($user['id']);

// Detect active page
$curPage = basename($_SERVER['PHP_SELF']);

function _adminLink(string $href, string $icon, string $label, int $badge = 0, string $cur = ''): string {
    $page   = basename($href);
    $active = ($cur === $page) ? ' active' : '';
    $bdg    = $badge > 0
        ? "<span class=\"sidebar-badge\">{$badge}</span>"
        : '';
    return "<a href=\"{$href}\" class=\"sidebar-link{$active}\"><i class=\"fas {$icon}\"></i>"
         . "<span class=\"sidebar-link-text\"> {$label}</span>{$bdg}</a>";
}
?>
<aside class="sidebar">
  <!-- Logo Section -->
  <div class="sidebar-logo">
    <a href="<?= APP_URL ?>/index.php" class="sidebar-logo-link">
      <img src="<?= APP_URL ?>/assets/images/logo.png" alt="PetCare+" class="sidebar-logo-img">
      <span class="sidebar-logo-text">PetCare<span style="color:#0EA5E9">+</span></span>
    </a>
  </div>
  
  <!-- User Info Header -->
  <div class="sidebar-header">
    <div class="sidebar-user">
      <div class="user-avatar-placeholder">
        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
      </div>
      <div>
        <div class="sidebar-user-name"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="sidebar-user-role">Clinic Admin</div>
      </div>
    </div>
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <?= _adminLink('index.php',        'fa-tachometer-alt', 'Dashboard',      0,              $curPage) ?>
    <?= _adminLink('appointments.php', 'fa-calendar-alt',   'Appointments',   $pendingAppts,  $curPage) ?>
    <?= _adminLink('records.php',      'fa-file-medical',   'Medical Records',0,              $curPage) ?>

    <div class="sidebar-section">Management</div>
    <?php if (($user['role_name'] ?? '') === 'superadmin'): ?>
    <?= _adminLink('users.php',    'fa-users',     'Users',         0, $curPage) ?>
    <?php endif; ?>
    <?= _adminLink('clinics.php',  'fa-hospital',  'Clinics',       0, $curPage) ?>
    <?= _adminLink('vets.php',     'fa-user-md',   'Veterinarians', 0, $curPage) ?>
    <?= _adminLink('groomers.php', 'fa-cut',       'Groomers',      0, $curPage) ?>
    <?= _adminLink('services.php', 'fa-list',      'Services',      0, $curPage) ?>
    <?php
    // Unread messages count for badge
    try {
        $unreadMsgs = (int)getDB()->query("SELECT COUNT(*) FROM contact_messages WHERE status='unread'")->fetchColumn();
    } catch (\Exception $e) { $unreadMsgs = 0; }
    ?>
    <?= _adminLink('messages.php', 'fa-envelope',  'Messages', $unreadMsgs, $curPage) ?>

    <div class="sidebar-section">Reports</div>
    <?= _adminLink('reports.php', 'fa-chart-bar', 'Reports', 0, $curPage) ?>
    <?= _adminLink('ratings.php', 'fa-star',      'Ratings', 0, $curPage) ?>

  </nav>

  <!-- Logout at the bottom -->
  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/auth/logout.php" class="sidebar-link sidebar-logout">
      <i class="fas fa-sign-out-alt"></i>
      <span class="sidebar-link-text"> Logout</span>
    </a>
  </div>

</aside>
