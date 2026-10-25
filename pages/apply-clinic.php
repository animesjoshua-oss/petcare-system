<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Apply to Register a Clinic';
$db = getDB();
$errors = [];
$applicationType = (string)($_POST['application_type'] ?? 'veterinary');
$submittedCode = $_SESSION['clinic_application_submitted'] ?? '';
unset($_SESSION['clinic_application_submitted']);
$values = [
    'clinic_name' => '', 'admin_first_name' => '', 'admin_last_name' => '', 'email' => '',
    'phone' => '', 'address' => '', 'city' => '', 'description' => '',
    'accessibility_notes' => '', 'license_number' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $values['email'] = strtolower($values['email']);
    // Groomer Admin applications do not collect or store veterinarian ID details.
    if ($applicationType === 'grooming') $values['license_number'] = '';
    if (!in_array($applicationType, ['veterinary', 'grooming'], true)) {
        $errors[] = 'Choose Vet Admin or Groomer Admin as the application type.';
        $applicationType = 'veterinary';
    }
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) $errors[] = 'The form expired. Refresh the page and submit again.';
    if ($values['clinic_name'] === '' || $values['admin_first_name'] === '' || $values['admin_last_name'] === '' || $values['address'] === '') {
        $errors[] = 'Clinic name, administrator name, and address are required.';
    }
    if ($applicationType === 'veterinary' && $values['license_number'] === '') $errors[] = 'Veterinarian license number is required for a Vet Admin application.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address for the clinic administrator account.';
    if (strlen($values['clinic_name']) > 255 || strlen($values['admin_first_name']) > 100 || strlen($values['admin_last_name']) > 100 || strlen($values['email']) > 191 || ($applicationType === 'veterinary' && strlen($values['license_number']) > 100)) {
        $errors[] = 'One or more fields exceed their allowed length.';
    }
    if (empty($_POST['terms'])) $errors[] = 'Please agree to the Privacy Policy before applying.';

    $credential = $_FILES['credential'] ?? null;
    $allowedMime = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $credentialMime = '';
    if ($applicationType === 'veterinary' && (!$credential || ($credential['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
        $errors[] = 'Upload the veterinarian or clinic credentials as a PDF or image.';
    } elseif ($applicationType === 'veterinary' && (int)$credential['size'] > MAX_FILE_SIZE) {
        $errors[] = 'The credentials file must be 5 MB or smaller.';
    } elseif ($applicationType === 'veterinary') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $credentialMime = $finfo ? (string)finfo_file($finfo, $credential['tmp_name']) : '';
        if ($finfo) finfo_close($finfo);
        if (!isset($allowedMime[$credentialMime])) $errors[] = 'Use a PDF, JPG, PNG, or WEBP credentials file.';
    }

    if (!$errors) {
        $emailCheck = $db->prepare('SELECT id FROM users WHERE LOWER(email)=? LIMIT 1');
        $emailCheck->execute([$values['email']]);
        $pendingCheck = $db->prepare("SELECT id FROM clinic_applications WHERE LOWER(email)=? AND status='pending' LIMIT 1");
        $pendingCheck->execute([$values['email']]);
        if ($emailCheck->fetchColumn()) $errors[] = 'This email is already tied to an account. Use a separate email for the clinic administrator account.';
        elseif ($pendingCheck->fetchColumn()) $errors[] = 'An application from this email is already under review.';
    }

    if (!$errors) {
        $relativePath = '';
        $credentialName = '';
        $absolutePath = null;
        if ($applicationType === 'veterinary') {
            $uploadDirectory = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'clinic-applications';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                $errors[] = 'The secure credential upload folder is unavailable. Please contact support.';
            } else {
                $storedName = bin2hex(random_bytes(18)) . '.' . $allowedMime[$credentialMime];
                $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedName;
                $relativePath = 'uploads/clinic-applications/' . $storedName;
                $credentialName = basename((string)$credential['name']);
                if (!move_uploaded_file($credential['tmp_name'], $absolutePath)) {
                    $errors[] = 'The credentials file could not be saved. Please try again.';
                }
            }
        }
        if (!$errors) {
            $applicationCode = 'PCA-' . strtoupper(bin2hex(random_bytes(5)));
            try {
                $db->prepare('INSERT INTO clinic_applications
                    (application_code,application_type,clinic_name,admin_first_name,admin_last_name,email,phone,address,city,description,accessibility_notes,license_number,credential_path,credential_original_name,credential_mime)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([
                        $applicationCode, $applicationType, $values['clinic_name'], $values['admin_first_name'], $values['admin_last_name'],
                        $values['email'], $values['phone'] ?: null, $values['address'], $values['city'] ?: null,
                        $values['description'] ?: null, $values['accessibility_notes'] ?: null, $values['license_number'],
                        $relativePath, $credentialName, $credentialMime,
                    ]);
                $_SESSION['clinic_application_submitted'] = $applicationCode;
                header('Location: apply-clinic.php?submitted=1');
                exit;
            } catch (Throwable $e) {
                if ($absolutePath && is_file($absolutePath)) unlink($absolutePath);
                error_log('Clinic application submission failed: ' . $e->getMessage());
                $errors[] = 'Your application could not be saved. Please check the database migration and try again.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="max-width:860px;padding:3rem 1rem 4rem;">
  <div class="dash-breadcrumb"><a href="<?= APP_URL ?>/pages/common-questions.php">Common Questions</a> / <span>Apply to Register</span></div>
  <div style="margin:1.25rem 0 2rem;">
    <h1 style="margin:.75rem 0 .5rem;">Register a veterinary clinic or grooming center</h1>
    <p style="color:var(--text-muted);max-width:700px;line-height:1.7;">Submit an application for a Vet Admin or Groomer Admin account. A Super Admin reviews each application before creating the clinic listing and administrator account.</p>
  </div>
  <?php if ($submittedCode): ?>
    <div class="card"><div class="card-body" style="padding:2rem;">
      <h2><i class="fas fa-circle-check" style="color:var(--success);"></i> Application received</h2>
      <p>Reference code: <strong><?= sanitize($submittedCode) ?></strong></p>
      <p>We will email the applicant after the Super Admin approves or declines the application.</p>
      <a class="btn btn-primary" href="<?= APP_URL ?>/pages/common-questions.php">Read common questions</a>
    </div></div>
  <?php else: ?>
  <?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul style="margin:0;padding-left:1.25rem;"><?php foreach ($errors as $error): ?><li><?= sanitize($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data" class="card">
    <div class="card-body" style="padding:1.5rem;">
      <?= csrfField() ?>
      <div class="form-group"><label class="form-label" for="applicationType">Application type <span class="required">*</span></label><select id="applicationType" name="application_type" class="form-control" required><option value="veterinary" <?= $applicationType === 'veterinary' ? 'selected' : '' ?>>Vet Admin</option><option value="grooming" <?= $applicationType === 'grooming' ? 'selected' : '' ?>>Groomer Admin</option></select></div>
      <h2 style="font-size:1.15rem;margin-bottom:1rem;">Clinic details</h2>
      <div class="form-group"><label class="form-label" for="clinicName">Clinic name <span class="required">*</span></label><input id="clinicName" name="clinic_name" class="form-control" maxlength="255" required value="<?= sanitize($values['clinic_name']) ?>"></div>
      <div class="form-group"><label class="form-label" for="description">Clinic description</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="2000"><?= sanitize($values['description']) ?></textarea></div>
      <div class="form-group"><label class="form-label" for="address">Street address <span class="required">*</span></label><input id="address" name="address" class="form-control" required value="<?= sanitize($values['address']) ?>"></div>
      <div class="form-row"><div class="form-group"><label class="form-label" for="city">City or municipality</label><input id="city" name="city" class="form-control" value="<?= sanitize($values['city']) ?>"></div><div class="form-group"><label class="form-label" for="phone">Clinic phone</label><input id="phone" name="phone" type="tel" class="form-control" value="<?= sanitize($values['phone']) ?>"></div></div>
      <div class="form-group"><label class="form-label" for="accessibility">Accessibility accommodations</label><textarea id="accessibility" name="accessibility_notes" class="form-control" rows="2" maxlength="1500" placeholder="For example, step-free entrance, accessible parking, or assistance by request."><?= sanitize($values['accessibility_notes']) ?></textarea><small class="form-hint">Share practical details that help visitors with disabilities plan their visit.</small></div>
      <h2 id="adminApplicantHeading" style="font-size:1.15rem;margin:1.5rem 0 1rem;"><?= $applicationType === 'grooming' ? 'Groomer Admin applicant' : 'Veterinarian who will be Vet Admin' ?></h2>
      <div class="form-row"><div class="form-group"><label class="form-label" for="firstName">First name <span class="required">*</span></label><input id="firstName" name="admin_first_name" class="form-control" maxlength="100" required value="<?= sanitize($values['admin_first_name']) ?>"></div><div class="form-group"><label class="form-label" for="lastName">Last name <span class="required">*</span></label><input id="lastName" name="admin_last_name" class="form-control" maxlength="100" required value="<?= sanitize($values['admin_last_name']) ?>"></div></div>
      <div class="form-row"><div class="form-group"><label class="form-label" for="email"><span id="adminEmailLabel"><?= $applicationType === 'grooming' ? 'Groomer Admin email' : 'Vet Admin email' ?></span> <span class="required">*</span></label><input id="email" name="email" type="email" class="form-control" maxlength="191" required value="<?= sanitize($values['email']) ?>"><small class="form-hint">This becomes the clinic administrator’s sign-in email. Application decisions and, if approved, the one-time password will be sent here.</small></div><div class="form-group" id="licenseField" <?= $applicationType === 'grooming' ? 'hidden' : '' ?>><label class="form-label" for="license">Veterinarian license number <span class="required">*</span></label><input id="license" name="license_number" class="form-control" maxlength="100" <?= $applicationType === 'veterinary' ? 'required' : '' ?> value="<?= sanitize($values['license_number']) ?>"></div></div>
      <div class="form-group" id="credentialField" <?= $applicationType === 'grooming' ? 'hidden' : '' ?>><label class="form-label" for="credential">Supporting veterinarian or clinic credential <span class="required">*</span></label><input id="credential" name="credential" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-control" <?= $applicationType === 'veterinary' ? 'required' : '' ?>><small class="form-hint">Upload a clear copy of a professional veterinarian license or an official clinic credential that supports this application. Accepted formats: PDF, JPG, PNG, or WEBP (up to 5 MB). Only Super Admins can view the document.</small></div>
      <label class="form-check" style="margin:.75rem 0 1.25rem;"><input type="checkbox" name="terms" value="1" required <?= !empty($_POST['terms'])?'checked':'' ?>><span>I agree to the <a href="<?= APP_URL ?>/pages/privacy.php" target="_blank" rel="noopener">Privacy Policy</a> for review of my application.</span></label>
      <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit application</button>
    </div>
  </form>
  <?php endif; ?>
</main>
<script>
const applicationTypeSelect = document.getElementById('applicationType');
function updateApplicationTypeFields() {
  const isGrooming = applicationTypeSelect.value === 'grooming';
  const licenseField = document.getElementById('licenseField');
  const credentialField = document.getElementById('credentialField');
  const licenseInput = document.getElementById('license');
  const credentialInput = document.getElementById('credential');
  licenseField.hidden = isGrooming;
  credentialField.hidden = isGrooming;
  licenseInput.required = !isGrooming;
  credentialInput.required = !isGrooming;
  licenseInput.disabled = isGrooming;
  credentialInput.disabled = isGrooming;
  if (isGrooming) {
    licenseInput.value = '';
    credentialInput.value = '';
  }
  document.getElementById('adminApplicantHeading').textContent = isGrooming ? 'Groomer Admin applicant' : 'Veterinarian who will be Vet Admin';
  document.getElementById('adminEmailLabel').textContent = isGrooming ? 'Groomer Admin email' : 'Vet Admin email';
}
applicationTypeSelect.addEventListener('change', updateApplicationTypeFields);
updateApplicationTypeFields();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
