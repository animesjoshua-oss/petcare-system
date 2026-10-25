<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/clinic_admin_profile.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
if (isGroomerAdmin($user)) { header('Location: ' . APP_URL . '/auth/unauthorized.php'); exit; }
$pageTitle='Manage Veterinarians';
$isDashboard = true; $db=getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
if ($isSuperAdmin) ensureClinicAdminProfilesTable($db);
$msg=''; $msgType='success';
$temporaryPasswordReveal='';

if ($_SERVER['REQUEST_METHOD']==='POST' && !verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid.'; $msgType='danger'; }
elseif ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_vet']) && !$isSuperAdmin) {
    $remove=$db->prepare('DELETE u FROM users u JOIN veterinarians v ON v.user_id=u.id JOIN clinics c ON c.id=v.clinic_id WHERE v.id=? AND c.admin_id=? AND u.role_id=2');
    $remove->execute([(int)$_POST['delete_vet'],$user['id']]);
    $msg=$remove->rowCount()?'Veterinarian account removed from your clinic.':'Veterinarian not found in your clinic.';
    if (!$remove->rowCount()) $msgType='danger';
}
elseif ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_vet']) && !$isSuperAdmin) {
    $toggle=$db->prepare('UPDATE veterinarians v JOIN clinics c ON c.id=v.clinic_id SET v.is_available=? WHERE v.id=? AND c.admin_id=?');
    $toggle->execute([(int)($_POST['is_available'] ?? 0) === 1 ? 1 : 0,(int)$_POST['toggle_vet'],$user['id']]);
    $msg='Veterinarian availability updated.';
}
elseif ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($isSuperAdmin) { $msg='Veterinarian accounts must be added by the clinic Vet Admin.'; $msgType='danger'; }
    else {
        $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??''); $email=trim(strtolower($_POST['email']??''));
        $spec=trim($_POST['specialization']??''); $lic=trim($_POST['license_number']??''); $password=(string)($_POST['password']??'');
        $credential=$_FILES['credential']??null;
        $credentialPresent=$credential && ($credential['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        $credentialMime=''; $credentialExtension=''; $credentialError='';
        $allowedCredentialTypes=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if ($credentialPresent) {
            if (($credential['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) $credentialError='The credential file could not be uploaded. Try again.';
            elseif ((int)($credential['size']??0)>MAX_FILE_SIZE) $credentialError='The credential file must be 5 MB or smaller.';
            else {
                $finfo=finfo_open(FILEINFO_MIME_TYPE);
                $credentialMime=$finfo?(string)finfo_file($finfo,$credential['tmp_name']):'';
                if ($finfo) finfo_close($finfo);
                if (!isset($allowedCredentialTypes[$credentialMime])) $credentialError='Use a PDF, JPG, PNG, or WEBP credential file.';
                else $credentialExtension=$allowedCredentialTypes[$credentialMime];
            }
        }
        $clinicAccess=$db->prepare('SELECT id,name FROM clinics WHERE admin_id=? AND is_active=1 ORDER BY id LIMIT 1');
        $clinicAccess->execute([$user['id']]); $clinic=$clinicAccess->fetch(); $clinicId=(int)($clinic['id']??0);
        $chk=$db->prepare('SELECT u.id,r.name AS role_name,EXISTS(SELECT 1 FROM veterinarians v JOIN clinics c ON c.id=v.clinic_id WHERE v.user_id=u.id AND c.is_active=1) AS is_vet,EXISTS(SELECT 1 FROM groomers g JOIN clinics c ON c.id=g.clinic_id WHERE g.user_id=u.id AND c.is_active=1) AS is_groomer FROM users u JOIN roles r ON r.id=u.role_id WHERE LOWER(u.email)=?'); $chk->execute([$email]); $existing=$chk->fetch();
        if ($credentialError!=='') { $msg=$credentialError; $msgType='danger'; }
        elseif (!$first || !$last || !$lic || strlen($password)<8 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$clinic) { $msg='Complete the veterinarian details, enter a password of at least 8 characters, and make sure your clinic is active.'; $msgType='danger'; }
        elseif ($existing && $existing['role_name']==='pet_owner') { $msg='Error: Email is registered as a pet owner, use other email.'; $msgType='danger'; }
        elseif ($existing && (($existing['is_vet']||$existing['is_groomer']) || $existing['role_name']!=='veterinarian')) { $msg=($existing['is_vet']||$existing['is_groomer'])?'This account is already connected to an active clinic and cannot be registered with another clinic.':'Email already exists.'; $msgType='danger'; }
        else {
            $credentialAbsolutePath='';
            try {
                $credentialPath=null; $credentialOriginalName=null; $storedCredentialMime=null;
                if ($credentialPresent) {
                    $uploadDirectory=rtrim(UPLOAD_DIR,'/\\').DIRECTORY_SEPARATOR.'veterinarian-credentials';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory,0750,true) && !is_dir($uploadDirectory)) throw new RuntimeException('Credential upload directory is unavailable.');
                    $storedName=bin2hex(random_bytes(18)).'.'.$credentialExtension;
                    $credentialAbsolutePath=$uploadDirectory.DIRECTORY_SEPARATOR.$storedName;
                    if (!move_uploaded_file($credential['tmp_name'],$credentialAbsolutePath)) throw new RuntimeException('Credential upload failed.');
                    $credentialPath='uploads/veterinarian-credentials/'.$storedName;
                    $credentialOriginalName=basename((string)$credential['name']);
                    $storedCredentialMime=$credentialMime;
                }
                $db->beginTransaction();
                if ($existing) {
                    $uid=(int)$existing['id'];
                    $db->prepare('UPDATE users SET first_name=?,last_name=?,password_hash=?,is_active=1,must_change_password=0 WHERE id=?')->execute([$first,$last,hashPassword($password),$uid]);
                    $oldVet=$db->prepare('SELECT id FROM veterinarians WHERE user_id=?'); $oldVet->execute([$uid]);
                    if ($oldVet->fetchColumn()) $db->prepare('UPDATE veterinarians SET clinic_id=?,specialization=?,license_number=?,credential_path=COALESCE(?,credential_path),credential_original_name=COALESCE(?,credential_original_name),credential_mime=COALESCE(?,credential_mime) WHERE user_id=?')->execute([$clinicId,$spec?:null,$lic,$credentialPath,$credentialOriginalName,$storedCredentialMime,$uid]);
                    else $db->prepare('INSERT INTO veterinarians (user_id,clinic_id,specialization,license_number,credential_path,credential_original_name,credential_mime) VALUES (?,?,?,?,?,?,?)')->execute([$uid,$clinicId,$spec?:null,$lic,$credentialPath,$credentialOriginalName,$storedCredentialMime]);
                } else {
                    $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,password_hash,is_active,is_email_verified,must_change_password) VALUES (2,?,?,?,?,1,1,0)')->execute([$first,$last,$email,hashPassword($password)]);
                    $uid=(int)$db->lastInsertId();
                    $db->prepare('INSERT INTO veterinarians (user_id,clinic_id,specialization,license_number,credential_path,credential_original_name,credential_mime) VALUES (?,?,?,?,?,?,?)')->execute([$uid,$clinicId,$spec?:null,$lic,$credentialPath,$credentialOriginalName,$storedCredentialMime]);
                }
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                if ($credentialAbsolutePath!=='' && is_file($credentialAbsolutePath)) unlink($credentialAbsolutePath);
                error_log('Veterinarian account creation failed: '.$e->getMessage());
                $msg='The veterinarian account could not be created.'; $msgType='danger';
            }
            if ($msg==='') {
                require_once __DIR__ . '/../../includes/mailer.php';
                $msg="Veterinarian Dr. $first $last added to {$clinic['name']}.";
            }
        }
    }
}

$vetSql="SELECT v.*,CONCAT(u.first_name,' ',u.last_name) AS full_name,u.email,c.name AS clinic_name,'Veterinarian' AS member_role FROM veterinarians v JOIN users u ON v.user_id=u.id JOIN clinics c ON v.clinic_id=c.id".($isSuperAdmin?'':' WHERE c.admin_id=?')." ORDER BY u.last_name";
$vetStmt=$db->prepare($vetSql); $vetStmt->execute($isSuperAdmin?[]:[$user['id']]); $vets=$vetStmt->fetchAll();
if ($isSuperAdmin) {
    $vetAdmins=$db->query("SELECT c.admin_id AS id,NULL AS user_id,ap.specialization,NULL AS license_number,NULL AS credential_path,1 AS is_available,CONCAT(u.first_name,' ',u.last_name) AS full_name,u.email,c.name AS clinic_name,'Vet Admin' AS member_role FROM clinics c JOIN users u ON u.id=c.admin_id LEFT JOIN clinic_admin_profiles ap ON ap.user_id=u.id WHERE u.is_active=1 AND c.clinic_type='veterinary' ORDER BY u.last_name,u.first_name,c.name")->fetchAll();
    $vets=array_merge($vets,$vetAdmins);
    usort($vets, static fn($a, $b) => [$a['clinic_name'], $a['full_name']] <=> [$b['clinic_name'], $b['full_name']]);
}
$clinicSql='SELECT id,name FROM clinics WHERE is_active=1'.($isSuperAdmin?'':' AND admin_id=?').' ORDER BY name';
$clinicStmt=$db->prepare($clinicSql); $clinicStmt->execute($isSuperAdmin?[]:[$user['id']]); $clinics=$clinicStmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;"><div><h1>Veterinarians</h1><p><?= $isSuperAdmin ? 'Clinic veterinarian directory.' : 'Manage veterinarian accounts for your clinic.' ?></p></div><?php if (!$isSuperAdmin): ?><button onclick="openModal('addVetModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Vet</button><?php endif; ?></div>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-check-circle"></i><?= sanitize($msg) ?></div><?php endif; ?>
  <?php if ($temporaryPasswordReveal): ?><div class="alert alert-warning" role="status">One-time password: <code><?= sanitize($temporaryPasswordReveal) ?></code>. The veterinarian must change it at first sign-in.</div><?php endif; ?>
  <div class="card">
    <div class="table-wrap">
      <?php if ($vets): ?>
      <table>
        <thead><tr><th>Name</th><?php if ($isSuperAdmin): ?><th>Role</th><?php endif; ?><th>Email</th><th>Clinic</th><th>Specialization</th><th>License</th><th>Credential</th><?php if (!$isSuperAdmin): ?><th>Status / Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($vets as $v): ?>
        <tr>
          <td style="font-weight:600;"><?= $isSuperAdmin && $v['member_role'] === 'Veterinarian' ? 'Dr. ' : '' ?><?= sanitize($v['full_name']) ?></td>
          <?php if ($isSuperAdmin): ?><td><?= sanitize($v['member_role']) ?></td><?php endif; ?>
          <td><?= sanitize($v['email']) ?></td>
          <td><?= sanitize($v['clinic_name']) ?></td>
          <td><?= sanitize($v['specialization']??'—') ?></td>
          <td><?= sanitize($v['license_number']??'—') ?></td>
          <td><?php if (!empty($v['credential_path'])): ?><a class="btn btn-sm btn-outline" href="vet-credential-document.php?id=<?= (int)$v['id'] ?>" target="_blank" rel="noopener">View file</a><?php elseif ($isSuperAdmin): ?>—<?php else: ?><span class="form-hint">Not provided</span><?php endif; ?></td>
          <?php if (!$isSuperAdmin): ?><td><?= $v['is_available']?'<span class="badge badge-success">Active</span>':'<span class="badge badge-secondary">Inactive</span>' ?> <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="toggle_vet" value="<?= (int)$v['id'] ?>"><input type="hidden" name="is_available" value="<?= $v['is_available'] ? 0 : 1 ?>"><button class="btn btn-sm btn-outline" type="submit"><?= $v['is_available'] ? 'Set Inactive' : 'Set Active' ?></button></form> <form method="POST" style="display:inline" onsubmit="return confirm('Remove this veterinarian account from your clinic?')"><?= csrfField() ?><input type="hidden" name="delete_vet" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Remove</button></form></td><?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty-state"><i class="fas fa-user-md"></i><h3>No veterinarians yet</h3></div><?php endif; ?>
    </div>
  </div>
</main>
<?php if (!$isSuperAdmin): ?><div class="modal-overlay" id="addVetModal">
  <div class="modal"><div class="modal-header"><h3>Add Veterinarian</h3><button class="modal-close" onclick="closeModal('addVetModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="form-row">
        <div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control" required></div>
      </div>
      <div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" name="email" class="form-control" required></div>
      <p class="form-hint">This account will be assigned automatically to your active clinic.</p>
      <div class="form-group"><label class="form-label">Password <span class="required">*</span></label><input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" placeholder="e.g. Surgery, Dermatology"></div>
        <div class="form-group"><label class="form-label">Professional Veterinarian License Number <span class="required">*</span></label><input type="text" name="license_number" class="form-control" maxlength="100" autocomplete="off" required><small class="form-hint">Enter the veterinarian’s valid PRC license number exactly as shown on their professional license. Make sure the veterinarian’s full name matches the license so the clinic can verify their credentials.</small></div>
      </div>
      <div class="form-group"><label class="form-label" for="vetCredential">License or credential file</label><input id="vetCredential" type="file" name="credential" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"><small class="form-hint">Optional. PDF, JPG, PNG, or WEBP, up to 5 MB. The file is visible only to this clinic's Vet Admin and the Super Admin.</small></div>
      <p style="font-size:.8rem;color:var(--text-muted)">Use an email address the veterinarian can access. Their one-time password will be sent there, and they must change it at first sign-in.</p>
      <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;">
        <button type="button" onclick="closeModal('addVetModal')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Vet</button>
      </div>
    </form></div>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
