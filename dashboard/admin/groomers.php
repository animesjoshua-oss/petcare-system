<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser(); $pageTitle = 'Manage Groomers';
$isDashboard = true; $db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);
$msg = ''; $msgType = 'success';
$temporaryPasswordReveal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $msg = 'Invalid CSRF token.'; $msgType = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_groomer']) && !$isSuperAdmin) {
    $removeSql = 'DELETE u FROM users u JOIN groomers g ON g.user_id=u.id JOIN clinics c ON c.id=g.clinic_id WHERE g.id=? AND c.admin_id=?' . ($isGroomerAdmin ? " AND c.clinic_type='grooming'" : '') . ' AND u.role_id=3';
    $remove = $db->prepare($removeSql);
    $remove->execute([(int)$_POST['delete_groomer'], $user['id']]);
    $msg = $remove->rowCount() ? 'Groomer account removed from your clinic.' : 'Groomer not found in your clinic.';
    if (!$remove->rowCount()) $msgType = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_groomer']) && !$isSuperAdmin) {
    $toggleSql = 'UPDATE groomers g JOIN clinics c ON c.id=g.clinic_id SET g.is_available=? WHERE g.id=? AND c.admin_id=?' . ($isGroomerAdmin ? " AND c.clinic_type='grooming'" : '');
    $toggle = $db->prepare($toggleSql);
    $toggle->execute([(int)($_POST['is_available'] ?? 0) === 1 ? 1 : 0, (int)$_POST['toggle_groomer'], $user['id']]);
    $msg = 'Groomer availability updated.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim($_POST['first_name']  ?? '');
    $last     = trim($_POST['last_name']   ?? '');
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $spec     = trim($_POST['specialization'] ?? '');
    $exp      = (int)($_POST['years_experience']  ?? 0);

    if ($isSuperAdmin) {
        $msg = 'Groomer accounts must be added by the clinic administrator.'; $msgType = 'danger';
    } elseif (!$first || !$last || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $msg = 'Enter first name, last name, a valid email, and a password of at least 8 characters.'; $msgType = 'danger';
    } else {
        $clinicAccess = $db->prepare('SELECT id,name FROM clinics WHERE admin_id=? AND is_active=1' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ' ORDER BY id LIMIT 1');
        $clinicAccess->execute([$user['id']]);
        $clinic = $clinicAccess->fetch();
        $clinicId = (int)($clinic['id'] ?? 0);
        $chk = $db->prepare('SELECT u.id,r.name AS role_name,EXISTS(SELECT 1 FROM veterinarians v JOIN clinics c ON c.id=v.clinic_id WHERE v.user_id=u.id AND c.is_active=1) AS is_vet,EXISTS(SELECT 1 FROM groomers g JOIN clinics c ON c.id=g.clinic_id WHERE g.user_id=u.id AND c.is_active=1) AS is_groomer FROM users u JOIN roles r ON r.id=u.role_id WHERE LOWER(u.email)=?');
        $chk->execute([$email]);
        $existing = $chk->fetch();
        if (!$clinic) {
            $msg = 'Your clinic is inactive or unavailable.'; $msgType = 'danger';
        } elseif ($existing && $existing['role_name'] === 'pet_owner') {
            $msg = 'Error: Email is registered as a pet owner, use other email.'; $msgType = 'danger';
        } elseif ($existing && (($existing['is_vet'] || $existing['is_groomer']) || $existing['role_name'] !== 'groomer')) {
            $msg = ($existing['is_vet'] || $existing['is_groomer']) ? 'This account is already connected to an active clinic and cannot be registered with another clinic.' : 'That email is already registered.'; $msgType = 'danger';
        } else {
            $temporaryPassword = $password;
            try {
                $db->beginTransaction();
                if ($existing) {
                    $uid=(int)$existing['id'];
                    $db->prepare('UPDATE users SET first_name=?,last_name=?,password_hash=?,is_active=1,must_change_password=0 WHERE id=?')->execute([$first,$last,hashPassword($temporaryPassword),$uid]);
                    $oldGroomer=$db->prepare('SELECT id FROM groomers WHERE user_id=?'); $oldGroomer->execute([$uid]);
                    if ($oldGroomer->fetchColumn()) $db->prepare('UPDATE groomers SET clinic_id=?,specialization=?,years_experience=? WHERE user_id=?')->execute([$clinicId,$spec?:null,max(0,min(50,$exp)),$uid]);
                    else $db->prepare('INSERT INTO groomers (user_id,clinic_id,specialization,years_experience) VALUES (?,?,?,?)')->execute([$uid,$clinicId,$spec?:null,max(0,min(50,$exp))]);
                } else {
                    $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,password_hash,is_active,is_email_verified,must_change_password) VALUES (3,?,?,?,?,1,1,0)')
                       ->execute([$first, $last, $email, hashPassword($temporaryPassword)]);
                    $uid = (int)$db->lastInsertId();
                    $db->prepare('INSERT INTO groomers (user_id,clinic_id,specialization,years_experience) VALUES (?,?,?,?)')
                       ->execute([$uid, $clinicId, $spec ?: null, max(0,min(50,$exp))]);
                }
                $db->commit();
                $msg = "Groomer $first $last added to {$clinic['name']}.";
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('Groomer account creation failed: ' . $e->getMessage());
                $msg = 'The groomer account could not be created.'; $msgType = 'danger';
            }
        }
    }
}

$groomerSql = "
    SELECT g.*, CONCAT(u.first_name,' ',u.last_name) AS full_name,
           u.email, c.name AS clinic_name
    FROM groomers g
    JOIN users u ON g.user_id = u.id
    JOIN clinics c ON g.clinic_id = c.id
" . ($isSuperAdmin ? '' : ' WHERE c.admin_id=?') . ($isGroomerAdmin ? " AND c.clinic_type='grooming'" : '') . " ORDER BY u.last_name, u.first_name";
$groomerStmt = $db->prepare($groomerSql);
$groomerStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$groomers = $groomerStmt->fetchAll();

$clinicSql = 'SELECT id,name FROM clinics WHERE is_active=1' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ' ORDER BY name';
$clinicStmt = $db->prepare($clinicSql);
$clinicStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$clinics = $clinicStmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> / <span>Groomers</span></div>
        <h1>Groomers</h1>
        <p><?= count($groomers) ?> groomer<?= count($groomers) !== 1 ? 's' : '' ?> registered</p>
      </div>
      <?php if (!$isSuperAdmin): ?><button onclick="openModal('addGroomerModal')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Groomer
      </button><?php endif; ?>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000">
    <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= sanitize($msg) ?>
  </div>
  <?php endif; ?>
  <?php if ($temporaryPasswordReveal): ?><div class="alert alert-warning" role="status">One-time password: <code><?= sanitize($temporaryPasswordReveal) ?></code>. The groomer must change it at first sign-in.</div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <?php if ($groomers): ?>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Clinic</th>
            <th>Specialization</th>
            <th>Experience</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groomers as $g): ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($g['full_name']) ?></td>
            <td style="font-size:.88rem;color:var(--text-muted);"><?= sanitize($g['email']) ?></td>
            <td><?= sanitize($g['clinic_name']) ?></td>
            <td><?= sanitize($g['specialization'] ?? '—') ?></td>
            <td><?= $g['years_experience'] ? $g['years_experience'].' yr'.($g['years_experience']>1?'s':'') : '—' ?></td>
            <td>
              <?= $g['is_available']
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>' ?>
              <?php if (!$isSuperAdmin): ?><form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="toggle_groomer" value="<?= (int)$g['id'] ?>"><input type="hidden" name="is_available" value="<?= $g['is_available'] ? 0 : 1 ?>"><button class="btn btn-sm btn-outline" type="submit"><?= $g['is_available'] ? 'Set Inactive' : 'Set Active' ?></button></form> <form method="POST" style="display:inline" onsubmit="return confirm('Remove this groomer account from your clinic?')"><?= csrfField() ?><input type="hidden" name="delete_groomer" value="<?= (int)$g['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Remove</button></form><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-cut"></i>
        <h3>No groomers yet</h3>
        <p>Add your first groomer to get started.</p>
        <?php if (!$isSuperAdmin): ?><button onclick="openModal('addGroomerModal')" class="btn btn-primary mt-4">
          <i class="fas fa-plus"></i> Add Groomer
        </button><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- ── Add Groomer Modal ─────────────────────────────────── -->
<?php if (!$isSuperAdmin): ?><div class="modal-overlay" id="addGroomerModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-cut" style="color:var(--primary);margin-right:.5rem;"></i>Add Groomer</h3>
      <button class="modal-close" onclick="closeModal('addGroomerModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <?= csrfField() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name <span class="required">*</span></label>
            <input type="text" name="first_name" class="form-control" required placeholder="Juan">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name <span class="required">*</span></label>
            <input type="text" name="last_name" class="form-control" required placeholder="dela Cruz">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" required placeholder="groomer@example.com">
        </div>
        <div class="form-group"><label class="form-label">Password <span class="required">*</span></label><input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Specialization</label>
            <input type="text" name="specialization" class="form-control" placeholder="e.g. All Breeds, Cats">
          </div>
          <div class="form-group">
            <label class="form-label">Years of Experience</label>
            <input type="number" name="years_experience" class="form-control" min="0" max="50" value="0">
          </div>
        </div>
        <p style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem;">This account will be assigned automatically to your active clinic.</p>
        <div class="modal-footer" style="padding:0;border:none;margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
          <button type="button" onclick="closeModal('addGroomerModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Groomer</button>
        </div>
      </form>
    </div>
  </div>
</div><?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
