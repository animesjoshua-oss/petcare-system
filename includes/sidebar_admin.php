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

$isSuperAdmin = ($user['role_name'] ?? '') === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);
$pendingSql = "SELECT COUNT(*) FROM appointments WHERE status='pending'" . ($isSuperAdmin ? '' : (' AND clinic_id IN (SELECT id FROM clinics WHERE admin_id=?' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ')')) . ($isGroomerAdmin ? " AND appointment_type='grooming'" : '');
$pendingStmt = $db->prepare($pendingSql);
$pendingStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$pendingAppts = (int)$pendingStmt->fetchColumn();
$pendingApplications = $isSuperAdmin ? (int)$db->query("SELECT COUNT(*) FROM clinic_applications WHERE status='pending'")->fetchColumn() : 0;
$clinicNames = clinicNamesForUser($user);
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
        <div class="sidebar-user-role"><?= sanitize(displayRoleName($user['role_name'], $user['clinic_type'] ?? null)) ?></div>
        <?php if ($clinicNames): ?><div style="font-size:.72rem;color:var(--text-muted);max-width:165px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= sanitize(implode(', ', $clinicNames)) ?>"><?= sanitize(implode(', ', $clinicNames)) ?></div><?php endif; ?>
      </div>
    </div>
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <?= _adminLink($isSuperAdmin ? 'superadmin-dashboard.php' : 'index.php', 'fa-tachometer-alt', 'Dashboard', 0, $curPage) ?>
    <?php if (!$isSuperAdmin): ?>
    <?= _adminLink('appointments.php', 'fa-calendar-alt',   $isGroomerAdmin ? 'Grooming Appointments' : 'Appointments',   $pendingAppts,  $curPage) ?>
    <?php if (!$isGroomerAdmin): ?>
    <?= _adminLink('records.php',      'fa-file-medical',   'Medical Records',0,              $curPage) ?>
    <?php endif; ?>
    <?php endif; ?>

    <div class="sidebar-section">Management</div>
    <?php if (($user['role_name'] ?? '') === 'superadmin'): ?>
    <?= _adminLink('users.php',    'fa-users',     'Users',         0, $curPage) ?>
    <?= _adminLink('clinic-applications.php', 'fa-file-circle-check', 'Clinic Applications', $pendingApplications, $curPage) ?>
    <?php endif; ?>
    <?= _adminLink('clinics.php',  'fa-hospital',  $isSuperAdmin ? 'Clinics' : ($isGroomerAdmin ? 'Manage Grooming Center' : 'Manage Vet Clinic'), 0, $curPage) ?>
    <?php if (!$isSuperAdmin): ?>
    <?= _adminLink('profile.php', 'fa-id-badge', 'My Profile', 0, $curPage) ?>
    <?php endif; ?>
    <?php if (!$isGroomerAdmin): ?>
    <?= _adminLink('vets.php',     'fa-user-md',   'Veterinarians', 0, $curPage) ?>
    <?php endif; ?>
    <?= _adminLink('groomers.php', 'fa-cut',       'Groomers',      0, $curPage) ?>
    <?php if (($user['role_name'] ?? '') !== 'superadmin'): ?>
    <?= _adminLink('services.php', 'fa-list',      $isGroomerAdmin ? 'Grooming Services' : 'Services',      0, $curPage) ?>
    <?php endif; ?>

  </nav>

  <!-- Logout at the bottom -->
  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/auth/logout.php" class="sidebar-link sidebar-logout">
      <i class="fas fa-sign-out-alt"></i>
      <span class="sidebar-link-text"> Logout</span>
    </a>
  </div>

</aside>
