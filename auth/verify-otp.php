<?php
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) { redirectByRole(); }

// Redirect if no OTP session
if (empty($_SESSION['otp_user_id']) || empty($_SESSION['otp_email'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Verify OTP';
$error     = '';
$success   = '';
$db        = getDB();

// Fetch user data
$stmt = $db->prepare('SELECT * FROM users WHERE id=?');
$stmt->execute([$_SESSION['otp_user_id']]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_purpose']);
    header('Location: login.php');
    exit;
}

// ── Handle OTP Verification ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $inputOtp = trim($_POST['otp'] ?? '');
    
    if (!$inputOtp) {
        $error = 'Please enter the OTP code sent to your email.';
    } elseif (strlen($inputOtp) !== 6 || !ctype_digit($inputOtp)) {
        $error = 'OTP must be exactly 6 digits.';
    } else {
        // Check if OTP expired
        if (empty($user['otp_expires_at']) || new DateTime() > new DateTime($user['otp_expires_at'])) {
            $error = 'OTP has expired. Please request a new one.';
        }
        // Check if too many attempts
        elseif ((int)$user['otp_attempts'] >= 5) {
            $error = 'Too many failed attempts. Please request a new OTP.';
        }
        // Verify OTP
        elseif (password_verify($inputOtp, $user['otp_code'])) {
            // ✅ OTP VERIFIED - Clear OTP data and log in
            $db->prepare('UPDATE users SET otp_code=NULL, otp_expires_at=NULL, otp_attempts=0, last_login=NOW(), inactivity_warned_at=NULL, inactivity_deactivated_at=NULL WHERE id=?')
               ->execute([$user['id']]);
            
            // Create session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_purpose']);
            
            if ((int)($user['must_change_password'] ?? 0) === 1) {
                header('Location: ' . APP_URL . '/auth/change-password.php');
                exit;
            }

            // Redirect based on role
            redirectByRole();
            exit;
        } else {
            // ❌ Wrong OTP - Increment attempts
            $newAttempts = (int)$user['otp_attempts'] + 1;
            $db->prepare('UPDATE users SET otp_attempts=? WHERE id=?')
               ->execute([$newAttempts, $user['id']]);
            
            $remaining = 5 - $newAttempts;
            if ($remaining > 0) {
                $error = 'Invalid OTP code. ' . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' remaining.';
            } else {
                $error = 'Too many failed attempts. Please request a new OTP.';
            }
            
            // Refresh user data
            $stmt->execute([$_SESSION['otp_user_id']]);
            $user = $stmt->fetch();
        }
    }
}

// ── Handle Resend OTP ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    // Generate new OTP
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_BCRYPT);
    $otpExpires = (new DateTime())->modify('+10 minutes')->format('Y-m-d H:i:s');
    
    // Update database
    $db->prepare('UPDATE users SET otp_code=?, otp_expires_at=?, otp_attempts=0 WHERE id=?')
       ->execute([$otpHash, $otpExpires, $user['id']]);
    
    // Send email
    require_once __DIR__ . '/../includes/mailer.php';
    $htmlBody = otpEmailHtml($user['first_name'], $otp);
    $mailResult = sendMail(
        $user['email'],
        $user['first_name'] . ' ' . $user['last_name'],
        'Your PetCare+ Login OTP Code',
        $htmlBody
    );
    
    if ($mailResult === true) {
        $success = 'A new OTP code has been sent to your email.';
    } else {
        $error = 'Failed to send OTP email. Please try again.';
    }
    
    // Refresh user data
    $stmt->execute([$_SESSION['otp_user_id']]);
    $user = $stmt->fetch();
}

// Check if OTP is expired
$otpExpired = false;
if (!empty($user['otp_expires_at'])) {
    $otpExpired = new DateTime() > new DateTime($user['otp_expires_at']);
}

// Check if max attempts reached
$maxAttemptsReached = (int)$user['otp_attempts'] >= 5;

?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>

<div class="auth-container">
  <div class="auth-card-wrapper">

    <!-- Logo -->
    <?php $logoBase = APP_URL; ?>
    <div class="auth-logo">
      <img src="<?= $logoBase ?>/assets/images/logo.png" alt="PetCare+" class="auth-logo-img">
    </div>

    <div class="card auth-card<?= $error ? ' auth-card--shake' : '' ?>">
      <div class="card-body auth-card-body">

        <!-- Error Alert -->
        <?php if ($error): ?>
        <div style="
          margin-bottom: 1.25rem;
          border-radius: 12px;
          padding: .9rem 1rem;
          display: flex;
          align-items: flex-start;
          gap: .75rem;
          animation: shakeAlert 0.45s ease;
          background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.35); border-left:4px solid #ef4444;
        ">
          <div style="
            width:30px; height:30px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            background:rgba(239,68,68,0.2);
          ">
            <i class="fas fa-exclamation" style="font-size:.75rem; color:#ef4444;"></i>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="font-size:.78rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; margin-bottom:.2rem; color:#fca5a5;">
              Verification Failed
            </div>
            <div style="font-size:.875rem; color:#e2e8f0; line-height:1.4;">
              <?= htmlspecialchars($error) ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if ($success): ?>
        <div style="
          margin-bottom: 1.25rem;
          border-radius: 12px;
          padding: .9rem 1rem;
          display: flex;
          align-items: flex-start;
          gap: .75rem;
          background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.35); border-left:4px solid #22c55e;
        ">
          <div style="
            width:30px; height:30px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            background:rgba(34,197,94,0.2);
          ">
            <i class="fas fa-check" style="font-size:.75rem; color:#22c55e;"></i>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="font-size:.78rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; margin-bottom:.2rem; color:#86efac;">
              OTP Sent
            </div>
            <div style="font-size:.875rem; color:#e2e8f0; line-height:1.4;">
              <?= htmlspecialchars($success) ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- OTP Verification -->
        <div class="auth-header">
          <h1 class="auth-title">Verify Your Email</h1>
          <p class="auth-subtitle">
            We've sent a 6-digit code to<br>
            <strong><?= htmlspecialchars($user['email']) ?></strong>
          </p>
        </div>

        <form method="POST" class="auth-form" id="otpForm">
          <input type="hidden" name="verify_otp" value="1">

          <div class="form-group">
            <label class="form-label">Enter OTP Code <span class="required">*</span></label>
            <div class="input-group">
              <input type="text" name="otp" id="otpInput" class="form-control" 
                placeholder="000000"
                maxlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                autocomplete="one-time-code"
                style="font-size:1.5rem; letter-spacing:0.5rem; text-align:center; font-weight:700;"
                required
                <?= ($otpExpired || $maxAttemptsReached) ? 'disabled' : '' ?>>
              <i class="fas fa-shield-alt input-group-icon"></i>
            </div>
            
            <?php if (!empty($user['otp_expires_at']) && !$otpExpired): ?>
            <div style="font-size:.82rem; color:var(--text-muted); margin-top:.5rem; text-align:center;">
              <i class="fas fa-clock"></i> 
              Code expires in <span id="countdown" style="font-weight:700; color:var(--primary);"></span>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($otpExpired || $maxAttemptsReached): ?>
          <!-- Show resend button if expired or max attempts -->
          <div style="text-align:center; margin:1.5rem 0;">
            <p style="color:var(--warning); font-size:.9rem; margin-bottom:1rem;">
              <i class="fas fa-exclamation-triangle"></i>
              <?= $otpExpired ? 'Your OTP has expired.' : 'Too many failed attempts.' ?>
            </p>
          </div>
          <?php else: ?>
          <button type="submit" class="btn btn-primary btn-block btn-lg auth-submit-btn">
            <i class="fas fa-check-circle"></i> Verify & Continue
          </button>
          <?php endif; ?>
        </form>

        <!-- Resend OTP -->
        <form method="POST" style="margin-top:1.5rem;">
          <input type="hidden" name="resend_otp" value="1">
          <div style="text-align:center;">
            <p style="font-size:.875rem; color:var(--text-muted); margin-bottom:.5rem;">
              Didn't receive the code?
            </p>
            <button type="submit" class="btn btn-outline-secondary" style="min-width:180px;">
              <i class="fas fa-redo"></i> Resend OTP
            </button>
          </div>
        </form>

        <!-- Cancel link -->
        <div class="auth-nav-links" style="margin-top:1.5rem;">
          <a href="login.php" class="auth-nav-link">
            <i class="fas fa-arrow-left"></i> Back to Login
          </a>
        </div>

      </div>
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

<?php if (!empty($user['otp_expires_at']) && !$otpExpired): ?>
<script>
(function(){
  var expiresAt = new Date('<?= (new DateTime($user['otp_expires_at']))->format('Y-m-d H:i:s') ?>').getTime();
  var countdownEl = document.getElementById('countdown');
  var otpInput = document.getElementById('otpInput');
  
  function updateCountdown() {
    var now = Date.now();
    var diff = Math.max(0, Math.floor((expiresAt - now) / 1000));
    
    if (diff <= 0) {
      countdownEl.textContent = 'expired';
      countdownEl.style.color = 'var(--danger)';
      if (otpInput) otpInput.disabled = true;
      location.reload(); // Reload to show resend button
      return;
    }
    
    var minutes = Math.floor(diff / 60);
    var seconds = diff % 60;
    countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    
    setTimeout(updateCountdown, 1000);
  }
  
  updateCountdown();
})();
</script>
<?php endif; ?>

<script>
// Auto-focus OTP input
document.getElementById('otpInput')?.focus();

// Only allow digits
document.getElementById('otpInput')?.addEventListener('input', function(e) {
  this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
