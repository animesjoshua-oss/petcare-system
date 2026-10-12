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
        $idType = trim($_POST['id_type'] ?? '');
        $idNum  = trim($_POST['id_number'] ?? '');

        $allowed_id_types = ['passport','drivers_license','sss','philhealth','umid','national_id','voters_id','prc'];

        if (!$first)                                     $errors[] = 'First name is required.';
        if (!$last)                                      $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email is required.';
        if (strlen($pass) < 8)                           $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[0-9]/', $pass))               $errors[] = 'Password must contain a number.';
        if ($pass !== $pass2)                            $errors[] = 'Passwords do not match.';
        if (empty($_POST['terms']))                      $errors[] = 'You must agree to the Terms of Service.';
        if (!in_array($idType, $allowed_id_types, true)) $errors[] = 'Please select a valid ID type.';
        if (!$idNum)                                     $errors[] = 'ID number is required.';

        // Validate ID front photo
        $imgInfoFront = null;
        if (empty($_FILES['id_photo_front']) || $_FILES['id_photo_front']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload the FRONT photo of your government-issued ID.';
        } else {
            $imgInfoFront = @getimagesize($_FILES['id_photo_front']['tmp_name']);
            $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
            if (!$imgInfoFront || !in_array($imgInfoFront[2], $validTypes, true)) {
                $errors[] = 'ID front photo must be JPG, PNG, WEBP, or GIF.';
            } elseif ($_FILES['id_photo_front']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'ID front photo must not exceed 5MB.';
            }
        }

        // Validate ID back photo
        $imgInfoBack = null;
        if (empty($_FILES['id_photo_back']) || $_FILES['id_photo_back']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload the BACK photo of your government-issued ID.';
        } else {
            $imgInfoBack = @getimagesize($_FILES['id_photo_back']['tmp_name']);
            $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
            if (!$imgInfoBack || !in_array($imgInfoBack[2], $validTypes, true)) {
                $errors[] = 'ID back photo must be JPG, PNG, WEBP, or GIF.';
            } elseif ($_FILES['id_photo_back']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'ID back photo must not exceed 5MB.';
            }
        }

        if (!$errors) {
            $chk = getDB()->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'This email is already registered. <a href="login.php">Sign in?</a>';
            } else {
                // Upload front and back ID photos
                $extMap = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_GIF=>'gif', IMAGETYPE_WEBP=>'webp'];
                $dir = UPLOAD_DIR . 'id_docs/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $uid_rand = bin2hex(random_bytes(8));

                // Front
                $extFront    = $extMap[$imgInfoFront[2]] ?? 'jpg';
                $filenameFront = 'id_front_' . $uid_rand . '.' . $extFront;
                $docPathFront  = null;
                if (move_uploaded_file($_FILES['id_photo_front']['tmp_name'], $dir . $filenameFront)) {
                    $docPathFront = UPLOAD_URL . 'id_docs/' . $filenameFront;
                } else {
                    $errors[] = 'Failed to upload front ID photo. Please try again.';
                }

                // Back
                $extBack     = $extMap[$imgInfoBack[2]] ?? 'jpg';
                $filenameBack  = 'id_back_' . $uid_rand . '.' . $extBack;
                $docPathBack   = null;
                if (move_uploaded_file($_FILES['id_photo_back']['tmp_name'], $dir . $filenameBack)) {
                    $docPathBack = UPLOAD_URL . 'id_docs/' . $filenameBack;
                } else {
                    $errors[] = 'Failed to upload back ID photo. Please try again.';
                }

                if (!$errors && $docPathFront && $docPathBack) {
                    $token            = null;
                    $termsAcceptedAt  = date('Y-m-d H:i:s'); // timestamp of agreement

                    // Insert user — is_active=0 (pending approval), id_verified=0
                    // terms_accepted=1 and terms_accepted_at recorded here
                    try {
                        getDB()->prepare('
                            INSERT INTO users
                                (role_id, first_name, last_name, email, phone, password_hash,
                                 email_verification_token, is_email_verified, is_active,
                                 id_document_type, id_document_number, id_document_path, id_document_path_back,
                                 terms_accepted, terms_accepted_at)
                            VALUES (?,?,?,?,?,?,?,1,0,?,?,?,?,1,?)
                        ')->execute([1, $first, $last, $email, $phone, hashPassword($pass),
                                     $token, $idType, $idNum, $docPathFront, $docPathBack,
                                     $termsAcceptedAt]);
                    } catch (\Exception $e) {
                        // Fallback: try without terms columns (old schema)
                        try {
                            getDB()->prepare('
                                INSERT INTO users
                                    (role_id, first_name, last_name, email, phone, password_hash,
                                     email_verification_token, is_email_verified, is_active,
                                     id_document_type, id_document_number, id_document_path, id_document_path_back)
                                VALUES (?,?,?,?,?,?,?,1,0,?,?,?,?)
                            ')->execute([1, $first, $last, $email, $phone, hashPassword($pass),
                                         $token, $idType, $idNum, $docPathFront, $docPathBack]);
                        } catch (\Exception $e2) {
                            // Final fallback if id columns don't exist yet
                            getDB()->prepare('
                                INSERT INTO users
                                    (role_id, first_name, last_name, email, phone, password_hash,
                                     email_verification_token, is_email_verified, is_active)
                                VALUES (?,?,?,?,?,?,?,1,0)
                            ')->execute([1, $first, $last, $email, $phone, hashPassword($pass), $token]);
                        }
                    }

                    // Registration remains pending admin review; no OTP is required for pet owners.
                    try {
                        $pendingUser = getDB()->prepare('SELECT id, first_name, last_name FROM users WHERE email=?');
                        $pendingUser->execute([$email]);
                        $pendingUser = $pendingUser->fetch();
                        if ($pendingUser) {
                            $adminStmt = getDB()->prepare("SELECT id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name IN ('admin','superadmin') LIMIT 5");
                            $adminStmt->execute();
                            foreach ($adminStmt->fetchAll() as $admin) {
                                createNotification($admin['id'], 'New Account Pending Approval', $pendingUser['first_name'] . ' ' . $pendingUser['last_name'] . ' submitted their account for approval. Review their ID.', 'warning', APP_URL . '/dashboard/admin/users.php?filter=pending');
                            }
                        }
                    } catch (\Exception $e) { /* non-critical */ }
                    header('Location: register.php?step=pending'); exit;
                }
            }
        }
    }
}

// Decide which step to show
$step = ($_GET['step'] ?? 'register') === 'pending' ? 'pending' : 'register';

$idTypes = [
    'passport'        => 'Philippine Passport',
    'drivers_license' => "Driver's License",
    'national_id'     => 'PhilSys National ID',
    'umid'            => 'UMID Card',
    'sss'             => 'SSS ID',
    'philhealth'      => 'PhilHealth ID',
    'voters_id'       => "Voter's ID",
    'prc'             => 'PRC License',
];
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

        <?php if ($step === 'pending'): ?>
        <!-- ════════════════ PENDING STEP ════════════════ -->
        <div style="text-align:center;padding:1rem 0;">
          <div style="width:80px;height:80px;background:rgba(245,158,11,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="fas fa-clock" style="font-size:2rem;color:var(--warning);"></i>
          </div>
          <h2 style="font-size:1.4rem;font-weight:700;margin-bottom:.5rem;">Account Pending Approval</h2>
          <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;margin-bottom:1.5rem;">
            Your account and ID are now being reviewed by our Clinic Admin team.<br><br>
            <strong>What happens next?</strong>
          </p>
          <div style="text-align:left;background:rgba(30,64,255,.05);border:1px solid rgba(30,64,255,.15);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
            <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem;">
              <i class="fas fa-search" style="color:var(--primary);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">Clinic Admin reviews your submitted ID photo and verifies your information</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem;">
              <i class="fas fa-check-circle" style="color:var(--success);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">Once approved, you will receive an email notification</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;">
              <i class="fas fa-sign-in-alt" style="color:var(--secondary);margin-top:.15rem;"></i>
              <span style="font-size:.875rem;">After approval, you can log in to your PetCare+ account</span>
            </div>
          </div>
          <p style="font-size:.82rem;color:var(--text-muted);">
            <i class="fas fa-envelope"></i> Make sure to check your email inbox for the approval notification.
          </p>
          <a href="login.php" class="btn btn-primary" style="margin-top:1rem;min-width:180px;">
            <i class="fas fa-sign-in-alt"></i> Go to Login
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

          <!-- ID Verification Section -->
          <div class="reg-id-section">
            <p class="reg-id-heading">
              <i class="fas fa-id-card"></i> Government ID Verification <span class="required">*</span>
            </p>
            <p class="reg-id-desc">
              Required for account security. Clinic Admin will verify your ID before activating your account.
            </p>

            <div class="form-group">
              <label class="form-label">ID Type <span class="required">*</span></label>
              <select name="id_type" class="form-control reg-select" required>
                <option value="">— Select ID Type —</option>
                <?php foreach ($idTypes as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($_POST['id_type'] ?? '') === $val ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">ID Number <span class="required">*</span></label>
              <input type="text" name="id_number" class="form-control reg-input"
                placeholder="Enter your ID number"
                value="<?= sanitize($_POST['id_number'] ?? '') ?>"
                required>
            </div>

            <div class="form-group">
              <label class="form-label">Upload ID Photos <span class="required">*</span></label>

              <!-- Front and Back — responsive grid, stacks on mobile -->
              <div class="id-upload-grid">

                <!-- Front -->
                <div class="id-upload-slot">
                  <p class="id-slot-label">
                    <i class="fas fa-id-card" style="color:var(--primary);"></i> Front Side
                  </p>
                  <div class="id-dropzone" id="dropFront">
                    <input type="file" name="id_photo_front" id="idFrontInput" accept="image/*" required
                      class="id-dropzone-input">
                    <div class="id-dropzone-content" id="dropFrontContent">
                      <i class="fas fa-cloud-upload-alt" style="color:var(--primary);"></i>
                      <p class="id-dropzone-cta">Tap to upload</p>
                      <p class="id-dropzone-hint">Front of ID</p>
                    </div>
                    <img id="previewFront" src="#" alt="Front preview" class="id-dropzone-preview">
                  </div>
                </div>

                <!-- Back -->
                <div class="id-upload-slot">
                  <p class="id-slot-label">
                    <i class="fas fa-id-card" style="color:var(--secondary);"></i> Back Side
                  </p>
                  <div class="id-dropzone" id="dropBack">
                    <input type="file" name="id_photo_back" id="idBackInput" accept="image/*" required
                      class="id-dropzone-input">
                    <div class="id-dropzone-content" id="dropBackContent">
                      <i class="fas fa-cloud-upload-alt" style="color:var(--secondary);"></i>
                      <p class="id-dropzone-cta">Tap to upload</p>
                      <p class="id-dropzone-hint">Back of ID</p>
                    </div>
                    <img id="previewBack" src="#" alt="Back preview" class="id-dropzone-preview">
                  </div>
                </div>

              </div><!-- /.id-upload-grid -->

              <p class="id-upload-note">
                <i class="fas fa-lock" style="color:var(--success);"></i>
                JPG, PNG, WEBP · Max 5 MB each · Make sure all text is clearly visible. Stored securely, visible only to Clinic Admins.
              </p>
            </div>
          </div><!-- /.reg-id-section -->

          <div class="form-group">
            <label class="form-check">
              <input type="checkbox" name="terms" id="termsCheckbox" required
                <?= !empty($_POST['terms']) ? 'checked' : '' ?>>
              <span style="font-size:.875rem;line-height:1.5;">
                I agree to the
                <a href="<?= APP_URL ?>/terms.php" target="_blank" rel="noopener noreferrer" style="font-weight:600;">Terms of Service</a>
                and
                <a href="<?= APP_URL ?>/privacy.php" target="_blank" rel="noopener noreferrer" style="font-weight:600;">Privacy Policy</a>
              </span>
            </label>
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

// Front photo preview
setupDropZone('idFrontInput', 'dropFront', 'dropFrontContent', 'previewFront', 'var(--primary)');
// Back photo preview
setupDropZone('idBackInput', 'dropBack', 'dropBackContent', 'previewBack', 'var(--secondary)');

function setupDropZone(inputId, zoneId, contentId, previewId, color) {
  const input   = document.getElementById(inputId);
  const zone    = document.getElementById(zoneId);
  const content = document.getElementById(contentId);
  const preview = document.getElementById(previewId);

  input.addEventListener('change', () => { if (input.files[0]) showPreview(input.files[0], preview, content); });
  zone.addEventListener('dragover',  e => { e.preventDefault(); zone.style.borderColor = color; });
  zone.addEventListener('dragleave', () => zone.style.borderColor = 'var(--border)');
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.style.borderColor = 'var(--border)';
    if (e.dataTransfer.files[0]) {
      input.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files[0], preview, content);
    }
  });
}

function showPreview(file, preview, content) {
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.style.display = 'block';
    content.style.display = 'none';
  };
  reader.readAsDataURL(file);
}
</script>
<?php endif; ?>


