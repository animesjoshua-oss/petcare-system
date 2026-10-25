<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'My Profile';
$isDashboard = true;
$db = getDB();

$vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_availability'])) {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'This request could not be verified. Please try again.';
        } elseif ($vetId) {
            $available = (int)($_POST['is_available'] ?? 0) === 1 ? 1 : 0;
            $db->prepare('UPDATE veterinarians SET is_available=?, updated_at=NOW() WHERE id=? AND user_id=?')->execute([$available, $vetId, $user['id']]);
            $vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
            $vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
            $success = $available ? 'You are now available for appointments.' : 'You are now unavailable for appointments.';
        }
    } else {
    $bio             = trim($_POST['bio'] ?? '');
    $specialization  = trim($_POST['specialization'] ?? '');
    $years_exp       = max(0, (int)($_POST['years_experience'] ?? 0));
    $license         = trim($_POST['license_number'] ?? '');

    // Validate
    if (mb_strlen($bio) > 1000) {
        $error = 'Bio must be 1000 characters or less.';
    } elseif (mb_strlen($specialization) > 255) {
        $error = 'Specialization must be 255 characters or less.';
    } else {
        try {
            if ($vetId) {
                $db->prepare('UPDATE veterinarians SET bio=?, specialization=?, years_experience=?, license_number=?, updated_at=NOW() WHERE id=?')
                   ->execute([$bio ?: null, $specialization ?: null, $years_exp, $license ?: null, $vetId]);
            }
            // Refresh vet record
            $vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
            $vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
            $success = 'Profile updated successfully.';
        } catch (PDOException $e) {
            $error = 'Could not save changes. Please try again.';
        }
    }
    }
}

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>My Professional Profile</span>
    </div>
    <h1>My Professional Profile</h1>
    <p>This information is displayed publicly on your clinic's page and will be used by the AI to help match pets with the right veterinarian.</p>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success" style="margin-bottom:1.5rem;">
    <i class="fas fa-check-circle"></i> <?= sanitize($success) ?>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger" style="margin-bottom:1.5rem;">
    <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">

    <!-- Preview Card -->
    <div class="card" style="position:sticky;top:1rem;">
      <div class="card-body" style="text-align:center;">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:#fff;font-weight:700;margin:0 auto 1rem;">
          <?= strtoupper(substr($user['first_name'],0,1)) ?>
        </div>
        <div style="font-weight:700;font-size:1.05rem;">Dr. <?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
        <div style="font-size:.8rem;color:var(--secondary);font-weight:600;margin-bottom:.5rem;">Veterinarian</div>
        <div style="margin:.65rem 0;"><span class="badge <?= !empty($vet['is_available']) ? 'badge-success' : 'badge-secondary' ?>"><?= !empty($vet['is_available']) ? 'Active · accepting appointments' : 'Inactive · not accepting appointments' ?></span></div>
        <form method="POST" style="margin:.75rem 0;">
          <?= csrfField() ?><input type="hidden" name="toggle_availability" value="1"><input type="hidden" name="is_available" value="<?= !empty($vet['is_available']) ? 0 : 1 ?>">
          <button type="submit" class="btn btn-sm <?= !empty($vet['is_available']) ? 'btn-outline' : 'btn-primary' ?>" style="width:100%;justify-content:center;"><?= !empty($vet['is_available']) ? 'Set yourself inactive' : 'Set yourself active' ?></button>
        </form>

        <?php if (!empty($vet['specialization'])): ?>
        <div style="display:flex;flex-wrap:wrap;gap:.3rem;justify-content:center;margin-bottom:.75rem;">
          <?php foreach (array_filter(array_map('trim', explode(',', $vet['specialization']))) as $tag): ?>
          <span style="font-size:.72rem;background:rgba(30,64,255,.08);color:var(--primary);padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(30,64,255,.15);"><?= sanitize($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($vet['years_experience'] > 0): ?>
        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem;">
          <i class="fas fa-briefcase" style="margin-right:.35rem;"></i><?= (int)$vet['years_experience'] ?> yr<?= $vet['years_experience']!=1?'s':'' ?> experience
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Edit Form -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-id-badge" style="color:var(--primary);margin-right:.5rem"></i>Edit Professional Info</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <?= csrfField() ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
            <div>
              <label class="form-label">License Number</label>
              <input type="text" name="license_number" class="form-control"
                     value="<?= sanitize($vet['license_number'] ?? '') ?>"
                     placeholder="e.g. PRC-12345" maxlength="100">
            </div>
            <div>
              <label class="form-label">Years of Experience</label>
              <input type="number" name="years_experience" class="form-control"
                     value="<?= (int)($vet['years_experience'] ?? 0) ?>"
                     min="0" max="60" placeholder="0">
            </div>
          </div>

          <div style="margin-bottom:1.25rem;">
            <label class="form-label">Specializations
              <span style="font-size:.78rem;color:var(--text-muted);font-weight:400;">(comma-separated, e.g. Small Animals, Surgery, Vaccination)</span>
            </label>
            <input type="text" name="specialization" class="form-control"
                   value="<?= sanitize($vet['specialization'] ?? '') ?>"
                   placeholder="e.g. Small Animals, Surgery, Dental Care"
                   maxlength="255" id="specInput">
            <!-- Live tag preview -->
            <div id="tagPreview" style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.5rem;min-height:1.5rem;"></div>
          </div>

          <div style="margin-bottom:1.5rem;">
            <label class="form-label">About / Bio
              <span style="font-size:.78rem;color:var(--text-muted);font-weight:400;">(max 1000 characters — describe your specialties, approach, and experience)</span>
            </label>
            <textarea name="bio" class="form-control" rows="7"
                      placeholder="e.g. I specialize in small animal surgery and have 10 years of experience handling dogs and cats. I'm skilled at nail trimming, dental cleaning, and routine check-ups..."
                      maxlength="1000" id="bioTextarea"><?= sanitize($vet['bio'] ?? '') ?></textarea>
            <div style="text-align:right;font-size:.78rem;color:var(--text-muted);margin-top:.3rem;">
              <span id="bioCount"><?= mb_strlen($vet['bio'] ?? '') ?></span>/1000
            </div>
          </div>

          <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Bio character counter
const bioTextarea = document.getElementById('bioTextarea');
const bioCount    = document.getElementById('bioCount');
bioTextarea.addEventListener('input', () => {
  const len = bioTextarea.value.length;
  bioCount.textContent = len;
  bioCount.style.color = len > 900 ? 'var(--danger)' : 'var(--text-muted)';
});

// Live specialization tag preview
const specInput  = document.getElementById('specInput');
const tagPreview = document.getElementById('tagPreview');
function renderTags() {
  const tags = specInput.value.split(',').map(t => t.trim()).filter(Boolean);
  tagPreview.innerHTML = tags.map(t =>
    `<span style="font-size:.72rem;background:rgba(30,64,255,.08);color:var(--primary);padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(30,64,255,.15);">${t}</span>`
  ).join('');
}
specInput.addEventListener('input', renderTags);
renderTags();
</script>
