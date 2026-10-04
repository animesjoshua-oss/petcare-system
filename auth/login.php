<?php
require_once __DIR__ . '/../includes/functions.php';
if (isLoggedIn()) { redirectByRole(); }

$pageTitle = 'Login';
$error     = '';
$lockoutTimestamp = null;

// ── Step 1: Email + Password ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON u.role_id=r.id
             WHERE LOWER(u.email)=?'
        );
        $stmt->execute([$email]);
        $loginUser = $stmt->fetch();

        if ($loginUser) {
            // ── Lockout check ─────────────────────────────────────
            if (!empty($loginUser['locked_until']) && new DateTime() < new DateTime($loginUser['locked_until'])) {
                $lockedUntilTs = (new DateTime($loginUser['locked_until']))->getTimestamp();
                $error = '__LOCKOUT__';
                $lockoutTimestamp = $lockedUntilTs;
            } elseif (password_verify($password, $loginUser['password_hash'])) {
                // ── Correct password — reset failure counters ──────
                $db->prepare(
                    'UPDATE users SET failed_login_attempts=0, locked_until=NULL WHERE id=?'
                )->execute([$loginUser['id']]);

                // Check if account is pending approval
                if (!$loginUser['is_active']) {
                    if (!empty($loginUser['id_document_path'])) {
                        $error = 'Your account is pending admin approval. You will be notified by email once approved.';
                    } else {
                        $error = 'Your account is inactive. Please contact support.';
                    }
                } else {
                    $role = $loginUser['role_name'];

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $loginUser['id'];
                    $db->prepare('UPDATE users SET last_login=NOW(), inactivity_warned_at=NULL, inactivity_deactivated_at=NULL WHERE id=?')->execute([$loginUser['id']]);
                    redirectByRole(); exit;
                }
            } else {
                // ── Wrong password — increment attempt counter ─────
                $attempts = (int)$loginUser['failed_login_attempts'] + 1;
                $lockUntil = null;

                if ($attempts >= 5) {
                    $lockUntil = (new DateTime())->modify('+5 minutes')->format('Y-m-d H:i:s');
                    $db->prepare(
                        'UPDATE users SET failed_login_attempts=?, locked_until=? WHERE id=?'
                    )->execute([$attempts, $lockUntil, $loginUser['id']]);
                    $lockoutTimestamp = (new DateTime($lockUntil))->getTimestamp();
                    $error = '__LOCKOUT__';
                } else {
                    $remaining = 5 - $attempts;
                    $db->prepare(
                        'UPDATE users SET failed_login_attempts=? WHERE id=?'
                    )->execute([$attempts, $loginUser['id']]);
                    $error = 'Invalid email or password. '
                           . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' remaining before lockout.';
                }
            }
        } else {
            // Email not found — generic message to avoid user enumeration
            $error = 'Invalid email or password.';
        }
    }
}

?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>

<div class="auth-container">
  <div class="auth-card-wrapper">

    <!-- Logo -->
    <?php $logoBase = APP_URL; ?>
    <div class="auth-logo">
      <img src="<?= $logoBase ?>/assets/logo/petcare.png" alt="PetCare+" class="auth-logo-img">
    </div>

    <div class="card auth-card<?= $error ? ' auth-card--shake' : '' ?>">
      <div class="card-body auth-card-body">

        <?php if ($error): ?>
        <div id="login-alert" style="
          margin-bottom: 1.25rem;
          border-radius: 12px;
          padding: .9rem 1rem;
          display: flex;
          align-items: flex-start;
          gap: .75rem;
          animation: shakeAlert 0.45s ease;
          <?= $error === '__LOCKOUT__'
            ? 'background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.35); border-left:4px solid #ef4444;'
            : (str_contains($error,'pending')
                ? 'background:rgba(234,179,8,0.12); border:1px solid rgba(234,179,8,0.35); border-left:4px solid #eab308;'
                : 'background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-left:4px solid #ef4444;')
          ?>
        ">
          <div style="
            width:30px; height:30px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            <?= $error === '__LOCKOUT__' ? 'background:rgba(239,68,68,0.2);' : (str_contains($error,'pending') ? 'background:rgba(234,179,8,0.2);' : 'background:rgba(239,68,68,0.2);') ?>
          ">
            <i class="fas fa-<?= $error === '__LOCKOUT__' ? 'lock' : (str_contains($error,'pending') ? 'clock' : 'exclamation') ?>"
               style="font-size:.75rem; <?= str_contains($error,'pending') ? 'color:#eab308;' : 'color:#ef4444;' ?>"></i>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="font-size:.78rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; margin-bottom:.2rem;
              <?= str_contains($error,'pending') ? 'color:#fde047;' : 'color:#fca5a5;' ?>">
              <?= $error === '__LOCKOUT__' ? 'Account Locked' : (str_contains($error,'pending') ? 'Pending Approval' : 'Login Failed') ?>
            </div>
            <div style="font-size:.875rem; color:#e2e8f0; line-height:1.4;">
              <?php if ($error === '__LOCKOUT__'): ?>
                Too many failed attempts. Try again in&nbsp;<span id="lockout-countdown" style="
                  display:inline-block; background:rgba(239,68,68,0.25); border:1px solid rgba(239,68,68,0.4);
                  border-radius:6px; padding:1px 8px; font-weight:700; color:#fca5a5; font-size:.9rem;
                "></span>
              <?php else: ?>
                <?= htmlspecialchars($error) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <style>
          @keyframes shakeAlert {
            0%,100%{ transform:translateX(0); }
            15%    { transform:translateX(-7px); }
            30%    { transform:translateX(7px); }
            45%    { transform:translateX(-5px); }
            60%    { transform:translateX(5px); }
            75%    { transform:translateX(-2px); }
            90%    { transform:translateX(2px); }
          }
        </style>
        <?php if ($error === '__LOCKOUT__'): ?>
        <script>
          (function(){
            var until = <?= (int)$lockoutTimestamp ?> * 1000;
            var el = document.getElementById('lockout-countdown');
            function tick(){
              var diff = Math.max(0, Math.floor((until - Date.now()) / 1000));
              if(diff <= 0){
                document.getElementById('login-alert').style.borderLeftColor = '#22c55e';
                document.getElementById('login-alert').style.background = 'rgba(34,197,94,0.1)';
                document.getElementById('login-alert').style.borderColor = 'rgba(34,197,94,0.3)';
                document.getElementById('login-alert').innerHTML =
                  '<div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,0.2);">'
                  +'<i class="fas fa-check" style="font-size:.75rem;color:#22c55e;"></i></div>'
                  +'<div style="flex:1;"><div style="font-size:.78rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#86efac;margin-bottom:.2rem;">Account Unlocked</div>'
                  +'<div style="font-size:.875rem;color:#e2e8f0;">You may now try logging in again.</div></div>';
                return;
              }
              var m = Math.floor(diff/60), s = diff%60;
              el.textContent = m > 0 ? m+'m '+s+'s' : s+'s';
              setTimeout(tick, 1000);
            }
            tick();
          })();
        </script>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($_GET['registered'])): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;">
          <i class="fas fa-check-circle"></i> Account created! Sign in below.
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['verified']) && !empty($_SESSION['verification_success'])): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;">
          <i class="fas fa-check-circle"></i> <strong>Email verified successfully!</strong> You can now sign in to your account.
        </div>
        <?php unset($_SESSION['verification_success']); endif; ?>

        <!-- LOGIN -->
        <div class="auth-header">
          <h1 class="auth-title">Welcome back</h1>
          <p class="auth-subtitle">Sign in to your PetCare+ account</p>
        </div>

        <form method="POST" class="auth-form">
          <input type="hidden" name="do_login" value="1">

          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <div class="input-group">
              <input type="email" name="email" class="form-control"
                placeholder="Enter your email address"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required autocomplete="email">
              <i class="fas fa-envelope input-group-icon"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <div class="input-group">
              <input type="password" name="password" class="form-control"
                placeholder="••••••••"
                required autocomplete="current-password">
              <button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button>
            </div>
            <div class="auth-link-right">
              <a href="forgot-password.php" class="auth-forgot-link">
                <i class="fas fa-key"></i> Forgot password?
              </a>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg auth-submit-btn">
            <i class="fas fa-arrow-right"></i> Continue
          </button>
        </form>

        <div class="auth-nav-links">

          <!-- Register link -->
          <a href="register.php" class="auth-nav-link">
            <i class="fas fa-user-plus"></i>
            Don't have an account? <strong>Create one free</strong>
          </a>

          <!-- Home link -->
          <a href="<?= APP_URL ?>/index.php" class="auth-nav-link">
            <i class="fas fa-arrow-left"></i> Back to Home
          </a>

        </div>

      </div>
    </div>
  </div>
</div>

<script>
// Password toggle
document.querySelector('.toggle-pw')?.addEventListener('click', function () {
  var inp = this.closest('.input-group').querySelector('input');
  var isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  this.querySelector('i').className = isHidden ? 'fas fa-eye' : 'fas fa-eye-slash';
});
</script>
