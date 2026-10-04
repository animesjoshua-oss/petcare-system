<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser(); $pageTitle = 'Manage Groomers';
$isDashboard = true; $db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $msg = 'Invalid CSRF token.'; $msgType = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = sanitize($_POST['first_name']  ?? '');
    $last     = sanitize($_POST['last_name']   ?? '');
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $clinicId = (int)($_POST['clinic_id']      ?? 0);
    $spec     = sanitize($_POST['specialization'] ?? '');
    $exp      = (int)($_POST['years_experience']  ?? 0);

    if (!$first || !$last || !$email || !$clinicId) {
        $msg = 'First name, last name, email and clinic are required.'; $msgType = 'danger';
    } else {
        $clinicAccess = $db->prepare('SELECT id FROM clinics WHERE id=?' . ($isSuperAdmin ? '' : ' AND admin_id=?'));
        $clinicAccess->execute($isSuperAdmin ? [$clinicId] : [$clinicId, $user['id']]);
        $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if (!$clinicAccess->fetchColumn()) {
            $msg = 'Choose a clinic assigned to your account.'; $msgType = 'danger';
        } elseif ($chk->fetch()) {
            $msg = 'That email is already registered.'; $msgType = 'danger';
        } else {
            $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,password_hash,is_active,is_email_verified) VALUES (3,?,?,?,?,1,1)')
               ->execute([$first, $last, $email, hashPassword('Groomer@1234')]);
            $uid = $db->lastInsertId();
            $db->prepare('INSERT INTO groomers (user_id,clinic_id,specialization,years_experience) VALUES (?,?,?,?)')
               ->execute([$uid, $clinicId, $spec, $exp]);
            $msg = "Groomer $first $last added successfully.";
        }
    }
}

$groomerSql = "
    SELECT g.*, CONCAT(u.first_name,' ',u.last_name) AS full_name,
           u.email, c.name AS clinic_name
    FROM groomers g
    JOIN users u ON g.user_id = u.id
    JOIN clinics c ON g.clinic_id = c.id
" . ($isSuperAdmin ? '' : ' WHERE c.admin_id=?') . " ORDER BY u.last_name, u.first_name";
$groomerStmt = $db->prepare($groomerSql);
$groomerStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$groomers = $groomerStmt->fetchAll();

$clinicSql = 'SELECT id,name FROM clinics WHERE is_active=1' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ' ORDER BY name';
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
      <button onclick="openModal('addGroomerModal')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Groomer
      </button>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000">
    <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $msg ?>
  </div>
  <?php endif; ?>

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
                ? '<span class="badge badge-success">Available</span>'
                : '<span class="badge badge-secondary">Unavailable</span>' ?>
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
        <button onclick="openModal('addGroomerModal')" class="btn btn-primary mt-4">
          <i class="fas fa-plus"></i> Add Groomer
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- ── Add Groomer Modal ─────────────────────────────────── -->
<div class="modal-overlay" id="addGroomerModal">
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
        <div class="form-group">
          <label class="form-label">Clinic <span class="required">*</span></label>
          <select name="clinic_id" class="form-control" required>
            <option value="">— Select Clinic —</option>
            <?php foreach ($clinics as $c): ?>
            <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
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
        <p style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem;">
          Default password: <strong>Groomer@1234</strong>
        </p>
        <div class="modal-footer" style="padding:0;border:none;margin-top:1.25rem;display:flex;gap:.75rem;justify-content:flex-end;">
          <button type="button" onclick="closeModal('addGroomerModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Groomer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
