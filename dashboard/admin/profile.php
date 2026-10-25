<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/clinic_admin_profile.php';
requireLogin();
requireRole(['admin']);

$user = currentUser();
$adminLabel = displayRoleName($user['role_name'], $user['clinic_type'] ?? null);
$db = getDB();
ensureClinicAdminProfilesTable($db);
$pageTitle = 'My Clinic Team Profile';
$isDashboard = true;
$message = '';
$messageType = 'success';

$profileStmt = $db->prepare('SELECT specialization,bio FROM clinic_admin_profiles WHERE user_id=?');
$profileStmt->execute([$user['id']]);
$profile = $profileStmt->fetch() ?: ['specialization'=>'', 'bio'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session could not verify this request. Refresh the page and try again.';
        $messageType = 'danger';
    } else {
        $specialization = trim((string)($_POST['specialization'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        if (mb_strlen($specialization) > 255 || mb_strlen($bio) > 1000) {
            $message = 'Specialization must be 255 characters or less and description must be 1000 characters or less.';
            $messageType = 'danger';
        } else {
            $save = $db->prepare('INSERT INTO clinic_admin_profiles (user_id,specialization,bio) VALUES (?,?,?) ON DUPLICATE KEY UPDATE specialization=VALUES(specialization),bio=VALUES(bio)');
            $save->execute([$user['id'], $specialization ?: null, $bio ?: null]);
            $profile = ['specialization'=>$specialization, 'bio'=>$bio];
            $message = 'Your clinic team profile has been updated.';
        }
    }
}

$clinicStmt = $db->prepare('SELECT name FROM clinics WHERE admin_id=? AND is_active=1' . (isGroomerAdmin($user) ? " AND clinic_type='grooming'" : '') . ' ORDER BY name');
$clinicStmt->execute([$user['id']]);
$clinicNames = $clinicStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a><i class="fas fa-chevron-right" style="font-size:.65rem"></i><span>My Profile</span></div>
    <h1>My Clinic Team Profile</h1>
    <p>Edit how your <?= sanitize($adminLabel) ?> profile appears on your assigned clinic’s public page.</p>
  </div>
  <?php if ($message): ?><div class="alert alert-<?= $messageType ?>" role="status"><?= sanitize($message) ?></div><?php endif; ?>
  <div class="card" style="max-width:900px;">
    <div class="card-header"><h3><i class="fas fa-id-badge" style="color:var(--primary);margin-right:.5rem"></i>Public profile</h3></div>
    <div class="card-body">
      <div style="margin-bottom:1.25rem;">
        <strong><?= sanitize($user['first_name'].' '.$user['last_name']) ?></strong>
        <div style="color:var(--text-muted);font-size:.9rem;"><?= sanitize($adminLabel) ?><?= $clinicNames ? ' · '.sanitize(implode(', ', $clinicNames)) : '' ?></div>
      </div>
      <form method="POST">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label" for="specialization">Specialization or role description</label>
          <input id="specialization" name="specialization" class="form-control" maxlength="255" value="<?= sanitize($profile['specialization'] ?? '') ?>" placeholder="e.g. Clinic Administration">
          <small class="form-hint">This appears under your name in the Clinic Team section.</small>
        </div>
        <div class="form-group">
          <label class="form-label" for="bio">Description</label>
          <textarea id="bio" name="bio" class="form-control" rows="5" maxlength="1000" placeholder="Share a short introduction for pet owners."><?= sanitize($profile['bio'] ?? '') ?></textarea>
          <small class="form-hint">Up to 1000 characters. Shown on the public page of each clinic assigned to you.</small>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
      </form>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
