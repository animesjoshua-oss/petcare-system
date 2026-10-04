<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser(); $pageTitle='Manage Veterinarians';
$isDashboard = true; $db=getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$msg=''; $msgType='success';

if ($_SERVER['REQUEST_METHOD']==='POST' && !verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid.'; $msgType='danger'; }
elseif ($_SERVER['REQUEST_METHOD']==='POST') {
    $first=$_POST['first_name']??''; $last=$_POST['last_name']??''; $email=trim(strtolower($_POST['email']??''));
    $clinicId=(int)$_POST['clinic_id']; $spec=sanitize($_POST['specialization']??''); $lic=sanitize($_POST['license_number']??'');
    $clinicAccess=$db->prepare('SELECT id FROM clinics WHERE id=?'.($isSuperAdmin?'':' AND admin_id=?'));
    $clinicAccess->execute($isSuperAdmin?[$clinicId]:[$clinicId,$user['id']]);
    $chk=$db->prepare('SELECT id FROM users WHERE email=?'); $chk->execute([$email]);
    if (!$clinicAccess->fetchColumn()) { $msg='Choose a clinic assigned to your account.'; $msgType='danger'; }
    elseif ($chk->fetch()) { $msg='Email already exists.'; $msgType='danger'; }
    else {
        $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,password_hash,is_active,is_email_verified) VALUES (2,?,?,?,?,1,1)')->execute([$first,$last,$email,hashPassword('Vet@1234')]);
        $uid=$db->lastInsertId();
        $db->prepare('INSERT INTO veterinarians (user_id,clinic_id,specialization,license_number) VALUES (?,?,?,?)')->execute([$uid,$clinicId,$spec,$lic]);
        $msg="Veterinarian Dr. $first $last added.";
    }
}

$vetSql="SELECT v.*,CONCAT(u.first_name,' ',u.last_name) AS full_name,u.email,c.name AS clinic_name FROM veterinarians v JOIN users u ON v.user_id=u.id JOIN clinics c ON v.clinic_id=c.id".($isSuperAdmin?'':' WHERE c.admin_id=?')." ORDER BY u.last_name";
$vetStmt=$db->prepare($vetSql); $vetStmt->execute($isSuperAdmin?[]:[$user['id']]); $vets=$vetStmt->fetchAll();
$clinicSql='SELECT id,name FROM clinics WHERE is_active=1'.($isSuperAdmin?'':' AND admin_id=?').' ORDER BY name';
$clinicStmt=$db->prepare($clinicSql); $clinicStmt->execute($isSuperAdmin?[]:[$user['id']]); $clinics=$clinicStmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;"><div><h1>Veterinarians</h1></div><button onclick="openModal('addVetModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Vet</button></div>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-check-circle"></i><?= $msg ?></div><?php endif; ?>
  <div class="card">
    <div class="table-wrap">
      <?php if ($vets): ?>
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Clinic</th><th>Specialization</th><th>License</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($vets as $v): ?>
        <tr>
          <td style="font-weight:600;">Dr. <?= sanitize($v['full_name']) ?></td>
          <td><?= sanitize($v['email']) ?></td>
          <td><?= sanitize($v['clinic_name']) ?></td>
          <td><?= sanitize($v['specialization']??'—') ?></td>
          <td><?= sanitize($v['license_number']??'—') ?></td>
          <td><?= $v['is_available']?'<span class="badge badge-success">Available</span>':'<span class="badge badge-secondary">Unavailable</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty-state"><i class="fas fa-user-md"></i><h3>No veterinarians yet</h3></div><?php endif; ?>
    </div>
  </div>
</main>
<div class="modal-overlay" id="addVetModal">
  <div class="modal"><div class="modal-header"><h3>Add Veterinarian</h3><button class="modal-close" onclick="closeModal('addVetModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><form method="POST">
      <?= csrfField() ?>
      <div class="form-row">
        <div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control" required></div>
      </div>
      <div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Clinic <span class="required">*</span></label><select name="clinic_id" class="form-control" required><option value="">— Choose —</option><?php foreach ($clinics as $c): ?><option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" placeholder="e.g. Surgery, Dermatology"></div>
        <div class="form-group"><label class="form-label">License Number</label><input type="text" name="license_number" class="form-control"></div>
      </div>
      <p style="font-size:.8rem;color:var(--text-muted)">Default password: <strong>Vet@1234</strong></p>
      <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;">
        <button type="button" onclick="closeModal('addVetModal')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Vet</button>
      </div>
    </form></div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
