<?php
require_once __DIR__ . '/../includes/functions.php';
if (isLoggedIn()) { redirectByRole(); }

$pageTitle = 'Create Account';
$errors    = [];

// ── Step 1: Register ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_register'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $first  = sanitize($_POST['first_name'] ?? '');
        $last   = sanitize($_POST['last_name']  ?? '');
        $email  = trim(strtolower($_POST['email'] ?? ''));
        $phone  = sanitize($_POST['phone'] ?? '');
        $pass   = $_POST['password']  ?? '';
        $pass2  = $_POST['password2'] ?? '';

        if (!$first)                                     $errors[] = 'First name is required.';
        if (!$last)                                      $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required.';
        if (strlen($pass) < 8)                           $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[0-9]/', $pass))               $errors[] = 'Password must contain a number.';
        if ($pass !== $pass2)                            $errors[] = 'Passwords do not match.';
        if (empty($_POST['terms']))                      $errors[] = 'You must agree to the Terms of Service.';

        if (!$errors) {
            $chk = getDB()->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'This email is already registered. <a href="login.php">Sign in?</a>';
            } else {
                $token            = null;
                $termsAcceptedAt  = date('Y-m-d H:i:s');

                // Insert user — is_active=1 (immediately active), is_email_verified=1
                try {
                    getDB()->prepare('
                        INSERT INTO users
                            (role_id, first_name, last_name, email, phone, password_hash,
                             email_verification_token, is_email_verified, is_active,
                             terms_accepted, terms_accepted_at)
                        VALUES (?,?,?,?,?,?,?,1,1,1,?)
                    ')->execute([1, $first, $last, $email, $phone, hashPassword($pass),
                                 $token, $termsAcceptedAt]);
                } catch (\Exception $e) {
                    // Fallback: try without terms columns (old schema)
                    getDB()->prepare('
                        INSERT INTO users
                            (role_id, first_name, last_name, email, phone, password_hash,
                             email_verification_token, is_email_verified, is_active)
                        VALUES (?,?,?,?,?,?,?,1,1)
                    ')->execute([1, $first, $last, $email, $phone, hashPassword($pass), $token]);
                }

                // Registration complete - redirect to success page
                header('Location: register.php?step=success'); exit;
            }
        }
    }
}

// Decide which step to show
$step = ($_GET['step'] ?? 'register') === 'success' ? 'success' : 'register';
?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>

<div class="auth-container auth-container--wide">
  <div class="auth-card-wrapper auth-card-wrapper--wide">

    <div class="auth-logo">
      <?php $logoBase = APP_URL; ?>
      <img src="<?= $logoBase ?>/assets/images/logo.png" alt="PetCare+" class="auth-logo-img">
    </div>

    <div class="card auth-card<?= $errors ? ' auth-card--shake' : '' ?>">
      <div class="card-body auth-card-body">

        <?php if ($errors): ?>
        <div class="alert alert-danger" style="margin-bottom:1.25rem;">
          <i class="fas fa-exclamation-circle"></i>
          <div><?php foreach ($errors as $e): ?><div><?= $e ?></div><?php endforeach; ?></div>
        </div>
        <?php endif; ?>

        <?php if ($step === 'success'): ?>
        <!-- ════════════════ SUCCESS STEP ════════════════ -->
        <div style="text-align:center;padding:1rem 0;">
          <div style="width:80px;height:80px;background:rgba(34,197,94,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="fas fa-check-circle" style="font-size:2rem;color:var(--success);"></i>
          </div>
          <h2 style="font-size:1.4rem;font-weight:700;margin-bottom:.5rem;">Account Created Successfully!</h2>
          <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;margin-bottom:1.5rem;">
            Welcome to PetCare+! Your account is now active and ready to use.
          </p>
          <div style="text-align:left;background:rgba(30,64,255,.05);border:1px solid rgba(30,64,255,.15);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
            <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem;">
              <i class="fas fa-calendar-check" style="color:var(--primary);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">Book appointments with veterinary clinics and groomers</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem;">
              <i class="fas fa-paw" style="color:var(--success);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">Manage your pet's health records and track their care</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;">
              <i class="fas fa-robot" style="color:var(--secondary);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">Get AI-powered pet care advice and recommendations</span>
            </div>
          </div>
          <a href="login.php" class="btn btn-primary" style="margin-top:1rem;min-width:180px;">
            <i class="fas fa-sign-in-alt"></i> Sign In Now
          </a>
        </div>

        <?php else: ?>
        <!-- ════════════════ REGISTER STEP ════════════════ -->
        <div class="auth-header">
          <h2 class="auth-title">Create Your Account</h2>
          <p class="auth-subtitle">Join PetCare+ and start managing your pet's health today.</p>
        </div>

        <form method="POST" enctype="multipart/form-data" id="registerForm">
          <?= csrfField() ?>
          <input type="hidden" name="do_register" value="1">

          <!-- Personal Info -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name <span class="required">*</span></label>
              <input type="text" name="first_name" class="form-control"
                placeholder="Juan" value="<?= sanitize($_POST['first_name'] ?? '') ?>" required spellcheck="false" autocomplete="given-name">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name <span class="required">*</span></label>
              <input type="text" name="last_name" class="form-control"
                placeholder="Dela Cruz" value="<?= sanitize($_POST['last_name'] ?? '') ?>" required spellcheck="false" autocomplete="family-name">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" name="email" class="form-control"
              placeholder="juan@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control"
              placeholder="+63-900-000-0000" value="<?= sanitize($_POST['phone'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <div class="input-group">
              <input type="password" name="password" class="form-control" placeholder="Min. 8 characters with a number" required>
              <button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Confirm Password <span class="required">*</span></label>
            <div class="input-group">
              <input type="password" name="password2" class="form-control" placeholder="Repeat your password" required>
              <button type="button" class="toggle-pw"><i class="fas fa-eye-slash"></i></button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-check">
              <input type="checkbox" name="terms" id="termsCheckbox" required
                <?= !empty($_POST['terms']) ? 'checked' : '' ?>>
              <span style="font-size:.875rem;line-height:1.5;">
                I agree to the
                <a href="<?= APP_URL ?>/pages/terms.php" target="_blank" rel="noopener noreferrer" style="font-weight:600;">Terms of Service</a>
                and
                <a href="<?= APP_URL ?>/pages/privacy.php" target="_blank" rel="noopener noreferrer" style="font-weight:600;">Privacy Policy</a>
              </span>
            </label>
            
            <!-- Collapsible Summary -->
            <div style="margin-top:.75rem;">
              <button type="button" id="toggleSummary" style="background:none;border:none;color:var(--primary);font-size:.8rem;padding:0;cursor:pointer;display:flex;align-items:center;gap:.4rem;font-weight:500;">
                <i class="fas fa-chevron-down" id="summaryIcon"></i>
                <span>Quick summary of key points</span>
              </button>
              <div id="termsSummary" style="display:none;margin-top:.75rem;padding:1rem;background:rgba(30,64,255,.04);border:1px solid rgba(30,64,255,.15);border-radius:8px;font-size:.8rem;line-height:1.7;color:var(--text);">
                <p style="margin:0 0 .75rem;font-weight:600;color:var(--primary);"><i class="fas fa-info-circle"></i> In short:</p>
                <ul style="margin:0;padding-left:1.25rem;list-style:disc;">
                  <li>Booking platform lang — hindi kami ang vet/groomer</li>
                  <li>Libre ang platform, bayad sa clinic ay direkta sa kanila</li>
                  <li>Pwede mag-cancel (24 hrs notice preferred)</li>
                  <li>Your personal info is kept secure and private</li>
                  <li>Bawal fake info, abuse, o hacking</li>
                  <li>AI advice ay general info lang — consult vet for medical issues</li>
                </ul>
                <p style="margin:.75rem 0 0;font-size:.75rem;color:var(--text-muted);"><i class="fas fa-external-link-alt"></i> Click the links above for complete details</p>
              </div>
            </div>
            
            <?php if (in_array('You must agree to the Terms of Service.', $errors ?? [])): ?>
            <div class="form-error"><i class="fas fa-circle-exclamation"></i> You must agree to the Terms of Service and Privacy Policy to register.</div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg auth-submit-btn">
            <i class="fas fa-user-plus"></i> Create Account
          </button>
        </form>

        <div class="auth-footer-text">
          Already have an account? <a href="login.php" class="auth-link-bold">Sign in</a>
        </div>
        <div class="auth-nav-links">
          <a href="<?= APP_URL ?>/index.php" class="auth-nav-link">
            <i class="fas fa-arrow-left"></i> Back to Home
          </a>
        </div>
        <?php endif; ?>

      </div><!-- /.auth-card-body -->
    </div><!-- /.auth-card -->
  </div><!-- /.auth-card-wrapper -->
</div><!-- /.auth-container -->

<?php if ($step === 'register'): ?>
<script>
// Password toggle
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', function () {
    const inp = this.closest('.input-group').querySelector('input');
    const hidden = inp.type === 'password';
    inp.type = hidden ? 'text' : 'password';
    this.querySelector('i').className = hidden ? 'fas fa-eye' : 'fas fa-eye-slash';
  });
});

// Collapsible Terms Summary
const toggleBtn = document.getElementById('toggleSummary');
const summary = document.getElementById('termsSummary');
const icon = document.getElementById('summaryIcon');

if (toggleBtn && summary && icon) {
  toggleBtn.addEventListener('click', function() {
    const isHidden = summary.style.display === 'none';
    summary.style.display = isHidden ? 'block' : 'none';
    icon.className = isHidden ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
  });
}
</script>
<?php endif; ?>


