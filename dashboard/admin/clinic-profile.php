<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin', 'superadmin']);
$user = currentUser();
$pageTitle = 'Clinic Profile & Hours';
$isDashboard = true;
$db = getDB();
$days = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
$isSuperAdmin = $user['role_name'] === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);
$clinicQuery = $db->prepare('SELECT id,name FROM clinics' . ($isSuperAdmin ? ' ORDER BY name' : ' WHERE admin_id=?' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ' ORDER BY name'));
$clinicQuery->execute($isSuperAdmin ? [] : [$user['id']]);
$managedClinics = $clinicQuery->fetchAll();
$clinicId = (int)($_POST['clinic_id'] ?? $_GET['id'] ?? ($managedClinics[0]['id'] ?? 0));
$allowedIds = array_map(static fn($row) => (int)$row['id'], $managedClinics);
$clinic = null;
if (in_array($clinicId, $allowedIds, true)) {
    $stmt = $db->prepare('SELECT * FROM clinics WHERE id=?' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : ''));
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch();
}
$message = '';
$messageType = 'success';
$hoursInput = [];

if ($clinic && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session could not verify this request. Refresh and try again.';
        $messageType = 'danger';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $accessibilityNotes = trim((string)($_POST['accessibility_notes'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $logoFile = $_FILES['logo'] ?? null;
        $coverFile = $_FILES['cover_photo'] ?? null;
        $imageTypes = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $savedImages = [];
        $imageError = '';
        foreach (['logo'=>$logoFile, 'cover_photo'=>$coverFile] as $field=>$file) {
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)($file['size'] ?? 0) > MAX_FILE_SIZE) { $imageError = 'Each clinic image must upload successfully and be 5 MB or smaller.'; break; }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : '';
            if ($finfo) finfo_close($finfo);
            if (!isset($imageTypes[$mime])) { $imageError = 'Clinic logo and cover must be JPG, PNG, or WEBP images.'; break; }
            $directory = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'clinics';
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) { $imageError = 'Clinic image storage is unavailable.'; break; }
            $filename = bin2hex(random_bytes(18)) . '-' . $field . '.' . $imageTypes[$mime];
            if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) { $imageError = 'Clinic image upload failed.'; break; }
            $savedImages[$field] = 'uploads/clinics/' . $filename;
        }
        $postedHours = $_POST['hours'] ?? [];
        $hours = [];
        $validHours = true;
        foreach ($days as $key => $_label) {
            $isClosed = !empty($postedHours[$key]['closed']);
            $open = trim((string)($postedHours[$key]['open'] ?? ''));
            $close = trim((string)($postedHours[$key]['close'] ?? ''));
            $hoursInput[$key] = ['closed'=>$isClosed, 'open'=>$open, 'close'=>$close];
            if ($isClosed) {
                $hours[$key] = ['closed'=>true];
            } elseif (preg_match('/^\d{2}:\d{2}$/', $open) && preg_match('/^\d{2}:\d{2}$/', $close) && $open < $close) {
                $hours[$key] = ['open'=>date('g:i A', strtotime($open)), 'close'=>date('g:i A', strtotime($close))];
            } else {
                $validHours = false;
            }
        }

        if ($imageError !== '') {
            $message = $imageError;
            $messageType = 'danger';
        } elseif ($name === '' || $address === '' || !$validHours || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $message = 'Check the clinic name, address, contact email, and weekly opening hours.';
            $messageType = 'danger';
        } else {
            $update = $db->prepare('UPDATE clinics SET name=?,description=?,accessibility_notes=?,address=?,city=?,phone=?,email=?,operating_hours=?,logo=COALESCE(?,logo),cover_photo=COALESCE(?,cover_photo) WHERE id=?' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ($isGroomerAdmin ? " AND clinic_type='grooming'" : ''));
            $params = [$name, $description ?: null, $accessibilityNotes ?: null, $address, $city ?: null, $phone ?: null, $email ?: null, json_encode($hours), $savedImages['logo'] ?? null, $savedImages['cover_photo'] ?? null, $clinicId];
            if (!$isSuperAdmin) $params[] = $user['id'];
            $update->execute($params);
            $message = 'Clinic profile and operating hours saved.';
            $stmt = $db->prepare('SELECT * FROM clinics WHERE id=?' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : ''));
            $stmt->execute([$clinicId]);
            $clinic = $stmt->fetch();
        }
    }
}

$storedHours = $clinic && !empty($clinic['operating_hours']) ? json_decode($clinic['operating_hours'], true) : [];
foreach ($days as $key => $_label) {
    if (!isset($hoursInput[$key])) {
        $current = $storedHours[$key] ?? [];
        $open = is_array($current) ? ($current['open'] ?? '') : '';
        $close = is_array($current) ? ($current['close'] ?? '') : '';
        $hoursInput[$key] = [
            'closed' => is_array($current) ? !empty($current['closed']) : ($current === 'closed'),
            'open' => $open ? date('H:i', strtotime($open)) : '',
            'close' => $close ? date('H:i', strtotime($close)) : '',
        ];
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="clinics.php">Clinics</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Profile &amp; Hours</span></div>
    <h1>Clinic Profile &amp; Hours</h1>
    <p>Update the public clinic description, accessibility details, contact information, and weekly availability.</p>
  </div>
  <?php if ($message): ?><div class="alert alert-<?= $messageType ?>" role="status"><?= sanitize($message) ?></div><?php endif; ?>
  <?php if (!$managedClinics): ?>
    <div class="card"><div class="card-body"><h2>No assigned clinic</h2><p>Ask a system administrator to assign a clinic to this account.</p></div></div>
  <?php elseif ($clinic): ?>
  <?php if ($isSuperAdmin && count($managedClinics) > 1): ?>
  <form method="GET" class="card" style="margin-bottom:1rem;"><div class="card-body" style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap;"><div class="form-group" style="margin:0;min-width:260px;"><label class="form-label" for="clinicPicker">Clinic</label><select id="clinicPicker" name="id" class="form-control" onchange="this.form.submit()"><?php foreach ($managedClinics as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$item['id']===$clinicId?'selected':'' ?>><?= sanitize($item['name']) ?></option><?php endforeach; ?></select></div><button class="btn btn-outline" type="submit">Choose clinic</button></div></form>
  <?php endif; ?>
  <div class="card"><div class="card-body">
    <form method="POST" enctype="multipart/form-data" action="clinic-profile.php?id=<?= $clinicId ?>">
      <?= csrfField() ?><input type="hidden" name="clinic_id" value="<?= $clinicId ?>">
      <h2 style="font-size:1.05rem;margin-bottom:1rem;">Public profile</h2>
      <div class="form-group"><label class="form-label" for="clinicName">Clinic name <span class="required">*</span></label><input id="clinicName" name="name" class="form-control" maxlength="255" required value="<?= sanitize($clinic['name']) ?>"></div>
      <div class="form-group"><label class="form-label" for="clinicDescription">About the clinic</label><textarea id="clinicDescription" name="description" class="form-control" rows="4" maxlength="2000" aria-describedby="clinicDescriptionHelp"><?= sanitize($clinic['description'] ?? '') ?></textarea><small id="clinicDescriptionHelp" class="form-hint">Describe the care and services clients can expect. These details also help the AI service finder recommend your clinic.</small></div>
      <div class="form-group"><label class="form-label" for="clinicAccessibility">Accessibility accommodations</label><textarea id="clinicAccessibility" name="accessibility_notes" class="form-control" rows="3" maxlength="1500" placeholder="For example, step-free entrance, accessible parking, or assistance by request."><?= sanitize($clinic['accessibility_notes'] ?? '') ?></textarea><small class="form-hint">Share practical details that help visitors with disabilities plan a visit.</small></div>
      <div class="form-group"><label class="form-label" for="clinicAddress">Address <span class="required">*</span></label><input id="clinicAddress" name="address" class="form-control" required value="<?= sanitize($clinic['address']) ?>"></div>
      <div class="form-row"><div class="form-group"><label class="form-label" for="clinicCity">City</label><input id="clinicCity" name="city" class="form-control" value="<?= sanitize($clinic['city'] ?? '') ?>"></div><div class="form-group"><label class="form-label" for="clinicPhone">Phone</label><input id="clinicPhone" name="phone" type="tel" class="form-control" value="<?= sanitize($clinic['phone'] ?? '') ?>"></div><div class="form-group"><label class="form-label" for="clinicEmail">Email</label><input id="clinicEmail" name="email" type="email" class="form-control" value="<?= sanitize($clinic['email'] ?? '') ?>"></div></div>
      <h2 style="font-size:1.05rem;margin:1.5rem 0 .75rem;">Clinic branding</h2>
      <div class="form-row"><div class="form-group"><label class="form-label" for="clinicLogo">Clinic logo</label><?php if (!empty($clinic['logo'])): ?><img src="<?= APP_URL . '/' . ltrim(sanitize($clinic['logo']), '/') ?>" alt="Current clinic logo" style="display:block;width:72px;height:72px;object-fit:cover;border-radius:50%;margin-bottom:.5rem;"><?php endif; ?><input id="clinicLogo" name="logo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="form-hint">Optional. JPG, PNG, or WEBP, up to 5 MB. Leave blank to keep the current logo.</small></div><div class="form-group"><label class="form-label" for="clinicCover">Cover photo</label><?php if (!empty($clinic['cover_photo'])): ?><img src="<?= APP_URL . '/' . ltrim(sanitize($clinic['cover_photo']), '/') ?>" alt="Current clinic cover" style="display:block;width:100%;max-width:360px;height:90px;object-fit:cover;border-radius:8px;margin-bottom:.5rem;"><?php endif; ?><input id="clinicCover" name="cover_photo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="form-hint">Optional. JPG, PNG, or WEBP, up to 5 MB. Leave blank to keep the current cover.</small></div></div>
      <h2 style="font-size:1.05rem;margin:1.5rem 0 .35rem;">Weekly hours</h2>
      <p class="form-hint" style="margin-bottom:.75rem;">Choose an opening and closing time for each day, or mark the clinic closed.</p>
      <div class="table-wrap"><table><thead><tr><th scope="col">Day</th><th scope="col">Opens</th><th scope="col">Closes</th><th scope="col">Closed</th></tr></thead><tbody>
      <?php foreach ($days as $key=>$label): ?><tr><th scope="row"><?= $label ?></th><td><input aria-label="<?= $label ?> opening time" type="time" name="hours[<?= $key ?>][open]" class="form-control" value="<?= sanitize($hoursInput[$key]['open']) ?>" <?= $hoursInput[$key]['closed']?'disabled':'' ?>></td><td><input aria-label="<?= $label ?> closing time" type="time" name="hours[<?= $key ?>][close]" class="form-control" value="<?= sanitize($hoursInput[$key]['close']) ?>" <?= $hoursInput[$key]['closed']?'disabled':'' ?>></td><td><label><input type="checkbox" name="hours[<?= $key ?>][closed]" value="1" <?= $hoursInput[$key]['closed']?'checked':'' ?> onchange="const row=this.closest('tr');row.querySelectorAll('input[type=time]').forEach(i=>i.disabled=this.checked);"> Closed</label></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem;"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save clinic profile</button><a href="services.php" class="btn btn-outline">Manage service tags</a><a href="../../pages/clinic.php?id=<?= $clinicId ?>" target="_blank" rel="noopener" class="btn btn-outline">Preview public page</a></div>
    </form>
  </div></div>
  <?php else: ?><div class="alert alert-danger" role="alert">This clinic is not assigned to your account.</div><?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
