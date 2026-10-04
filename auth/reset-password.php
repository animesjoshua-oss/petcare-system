<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle='Reset Password'; $db=getDB(); $msg=''; $msgType='info'; $valid=false;

$token = sanitize($_GET['token']??'');
if ($token) {
    $stmt=$db->prepare('SELECT id FROM users WHERE password_reset_token=? AND password_reset_expires > NOW()');
    $stmt->execute([$token]); $valid = (bool)$stmt->fetch();
    if (!$valid) { $msg='This reset link has expired or is invalid. Please request a new one.'; $msgType='danger'; }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $pass=$_POST['password']??''; $pass2=$_POST['password2']??'';
        $t=sanitize($_POST['token']??'');
        $stmt=$db->prepare('SELECT id FROM users WHERE password_reset_token=? AND password_reset_expires > NOW()');
        $stmt->execute([$t]); $row=$stmt->fetch();
        if (!$row) { $msg='Invalid or expired token.'; $msgType='danger'; }
        elseif (strlen($pass)<8) { $msg='Password must be at least 8 characters.'; $msgType='danger'; }
        elseif ($pass!==$pass2) { $msg='Passwords do not match.'; $msgType='danger'; }
        else {
            $db->prepare('UPDATE users SET password_hash=?,password_reset_token=NULL,password_reset_expires=NULL WHERE id=?')->execute([hashPassword($pass),$row['id']]);
            $msg='Password reset successfully! You can now log in.'; $msgType='success'; $valid=false;
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>
<div style="min-height:100vh;padding:5rem 1.5rem;display:flex;align-items:center;justify-content:center;background:var(--bg);">
  <div style="width:100%;max-width:440px;">
    <div class="card"><div class="card-body" style="padding:2.5rem;">
      <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem;text-align:center;">Reset Password</h2>
      <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-info-circle"></i><?= $msg ?></div><?php endif; ?>
      <?php if ($valid): ?>
      <form method="POST">
        <?= csrfField() ?><input type="hidden" name="token" value="<?= sanitize($token) ?>">
        <div class="form-group"><label class="form-label">New Password</label><div class="input-group"><input type="password" name="password" class="form-control" required><button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button></div></div>
        <div class="form-group"><label class="form-label">Confirm Password</label><div class="input-group"><input type="password" name="password2" class="form-control" required><button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button></div></div>
        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
      </form>
      <?php else: ?>
      <div class="text-center"><a href="forgot-password.php" class="btn btn-primary">Request New Reset Link</a></div>
      <?php endif; ?>
      <div class="auth-footer-text"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
