<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pageTitle = 'My Profile';
$db = getDB();
$msg = ''; $msgType='success';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $first = sanitize($_POST['first_name']??'');
        $last  = sanitize($_POST['last_name']??'');
        $phone = sanitize($_POST['phone']??'');
        $addr  = sanitize($_POST['address']??'');
        $photo = null;

        if (!empty($_FILES['profile_photo']['name'])) {
            $photo = uploadFile($_FILES['profile_photo'],'avatars');
        }

        if ($_POST['action']==='update_profile') {
            $email = trim(strtolower($_POST['email'] ?? $user['email']));
            // Check if email is taken by another user
            if ($email !== $user['email']) {
                $chk = $db->prepare('SELECT id FROM users WHERE email=? AND id!=?');
                $chk->execute([$email, $user['id']]);
                if ($chk->fetch()) { $msg='That email is already in use.'; $msgType='danger'; }
            }
            if (!$msg) {
                $sql = 'UPDATE users SET first_name=?,last_name=?,email=?,phone=?,address=?'.($photo?',profile_photo=?':'').' WHERE id=?';
                $params = $photo ? [$first,$last,$email,$phone,$addr,$photo,$user['id']] : [$first,$last,$email,$phone,$addr,$user['id']];
                $db->prepare($sql)->execute($params);
                $msg='Profile updated successfully.';
            }
        } elseif ($_POST['action']==='change_password') {
            $curr = $_POST['current_password']??'';
            $new  = $_POST['new_password']??'';
            $conf = $_POST['confirm_password']??'';
            if (!verifyPassword($curr,$user['password_hash'])) { $msg='Current password is incorrect.'; $msgType='danger'; }
            elseif (strlen($new)<8) { $msg='Password must be at least 8 characters.'; $msgType='danger'; }
            elseif ($new!==$conf) { $msg='Passwords do not match.'; $msgType='danger'; }
            else { $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([hashPassword($new),$user['id']]); $msg='Password changed successfully.'; }
        }
        // Reload
        $stmt=$db->prepare('SELECT u.*,r.name AS role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=?');
        $stmt->execute([$user['id']]); $user=$stmt->fetch();
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div style="background:var(--bg);min-height:100vh;padding-bottom:4rem;">
  <div class="container" style="max-width:800px;padding:2rem 1.5rem;">
    <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem;">My Profile</h1>

    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i><?= $msg ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start;" class="profile-grid">
      <!-- Avatar Card -->
      <div class="card text-center"><div class="card-body" style="padding:2rem;">
        <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2.5rem;color:#fff;font-weight:800;overflow:hidden;">
          <?php if ($user['profile_photo']): ?><img src="<?= sanitize($user['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;" alt=""><?php else: ?><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?><?php endif; ?>
        </div>
        <div style="font-size:1.1rem;font-weight:700;"><?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem;"><?= sanitize($user['email']) ?></div>
        <span class="badge badge-primary"><?= ucfirst(str_replace('_',' ',$user['role_name'])) ?></span>
        <div style="margin-top:1rem;font-size:.78rem;color:var(--text-muted)">Member since <?= date('M Y',strtotime($user['created_at'])) ?></div>
      </div></div>

      <!-- Forms -->
      <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <!-- Profile Form -->
        <div class="card"><div class="card-header"><h3>Personal Information</h3></div><div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?><input type="hidden" name="action" value="update_profile">
            <div class="form-row">
              <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required></div>
              <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name']) ?>" required></div>
            </div>
            <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= sanitize($user['address']??'') ?></textarea></div>
            <div class="form-group"><label class="form-label">Profile Photo</label><div class="upload-area"><img class="upload-preview" style="display:none;width:60px;height:60px;border-radius:50%;object-fit:cover;" alt=""><input type="file" name="profile_photo" accept="image/*" class="form-control"></div></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
          </form>
        </div></div>

        <!-- Password Form -->
        <div class="card"><div class="card-header"><h3>Change Password</h3></div><div class="card-body">
          <form method="POST">
            <?= csrfField() ?><input type="hidden" name="action" value="change_password">
            <div class="form-group"><label class="form-label">Current Password</label><div class="input-group"><input type="password" name="current_password" class="form-control" required><button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button></div></div>
            <div class="form-group"><label class="form-label">New Password</label><div class="input-group"><input type="password" name="new_password" class="form-control" required><button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button></div></div>
            <div class="form-group"><label class="form-label">Confirm New Password</label><div class="input-group"><input type="password" name="confirm_password" class="form-control" required><button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button></div></div>
            <button type="submit" class="btn btn-outline"><i class="fas fa-lock"></i> Change Password</button>
          </form>
        </div></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<style>
@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
