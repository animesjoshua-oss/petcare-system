<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['groomer']);
$user = currentUser();
$pageTitle = 'My Profile';
$isDashboard = true;
$db = getDB();

$gStmt = $db->prepare('SELECT * FROM groomers WHERE user_id=?');
$gStmt->execute([$user['id']]); $groomer = $gStmt->fetch();
$gId = $groomer['id'] ?? 0;

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio            = trim($_POST['bio'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $years_exp      = max(0, (int)($_POST['years_experience'] ?? 0));

    if (mb_strlen($bio) > 1000) {
        $error = 'Bio must be 1000 characters or less.';
    } elseif (mb_strlen($specialization) > 255) {
        $error = 'Specialization must be 255 characters or less.';
    } else {
        try {
            if ($gId) {
                $db->prepare('UPDATE groomers SET bio=?, specialization=?, years_experience=?, updated_at=NOW() WHERE id=?')
                   ->execute([$bio ?: null, $specialization ?: null, $years_exp, $gId]);
            }
            // Refresh record
            $gStmt = $db->prepare('SELECT * FROM groomers WHERE user_id=?');
            $gStmt->execute([$user['id']]); $groomer = $gStmt->fetch();
            $success = 'Profile updated successfully.';
        } catch (PDOException $e) {
            $error = 'Could not save changes. Please try again.';
        }
    }
}

// Avg rating for display
$stmtAvg = $db->prepare('SELECT COALESCE(AVG(groomer_rating),0), COUNT(*) FROM ratings WHERE groomer_id=? AND groomer_rating IS NOT NULL');
$stmtAvg->execute([$gId]);
[$avgRating, $totalRatings] = $stmtAvg->fetch(PDO::FETCH_NUM) ?: [0, 0];
$avgRating    = (float)$avgRating;
$totalRatings = (int)$totalRatings;
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_groomer.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>My Professional Profile</span>
    </div>
    <h1>My Professional Profile</h1>
    <p>This information is displayed publicly on your clinic's page to help pet owners choose the right groomer.</p>
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
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--warning),#F97316);display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:#fff;font-weight:700;margin:0 auto 1rem;">
          <?= strtoupper(substr($user['first_name'],0,1)) ?>
        </div>
        <div style="font-weight:700;font-size:1.05rem;"><?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
        <div style="font-size:.8rem;color:var(--warning);font-weight:600;margin-bottom:.5rem;">Groomer</div>

        <?php if (!empty($groomer['specialization'])): ?>
        <div style="display:flex;flex-wrap:wrap;gap:.3rem;justify-content:center;margin-bottom:.75rem;">
          <?php foreach (array_filter(array_map('trim', explode(',', $groomer['specialization']))) as $tag): ?>
          <span style="font-size:.72rem;background:rgba(245,158,11,.1);color:#B45309;padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(245,158,11,.25);"><?= sanitize($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($groomer['years_experience'] > 0): ?>
        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem;">
          <i class="fas fa-briefcase" style="margin-right:.35rem;"></i><?= (int)$groomer['years_experience'] ?> yr<?= $groomer['years_experience']!=1?'s':'' ?> experience
        </div>
        <?php endif; ?>

        <!-- Rating summary -->
        <div style="padding:.75rem;background:var(--bg);border-radius:var(--radius-sm);margin-top:.5rem;">
          <div style="font-size:2rem;font-weight:800;color:var(--warning);line-height:1;"><?= $totalRatings > 0 ? number_format($avgRating,1) : '—' ?></div>
          <div style="font-size:.95rem;color:var(--warning);margin:.25rem 0;">
            <?php for ($i=1;$i<=5;$i++): ?>
              <i class="<?= ($totalRatings > 0 && $i <= round($avgRating)) ? 'fas' : 'far' ?> fa-star"></i>
            <?php endfor; ?>
          </div>
          <div style="font-size:.75rem;color:var(--text-muted);"><?= $totalRatings ?> review<?= $totalRatings!=1?'s':'' ?></div>
        </div>

        <a href="ratings.php" class="btn btn-outline btn-sm" style="margin-top:1rem;width:100%;justify-content:center;">
          <i class="fas fa-star"></i> View All Reviews
        </a>
      </div>
    </div>

    <!-- Edit Form -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-id-badge" style="color:var(--warning);margin-right:.5rem"></i>Edit Professional Info</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="">

          <div style="margin-bottom:1.25rem;">
            <label class="form-label">Years of Experience</label>
            <input type="number" name="years_experience" class="form-control"
                   value="<?= (int)($groomer['years_experience'] ?? 0) ?>"
                   min="0" max="60" placeholder="0" style="max-width:160px;">
          </div>

          <div style="margin-bottom:1.25rem;">
            <label class="form-label">Specializations
              <span style="font-size:.78rem;color:var(--text-muted);font-weight:400;">(comma-separated, e.g. Nail Trimming, Dog Haircut, Cat Grooming)</span>
            </label>
            <input type="text" name="specialization" class="form-control"
                   value="<?= sanitize($groomer['specialization'] ?? '') ?>"
                   placeholder="e.g. Nail Trimming, Dog Haircut, Cat Grooming, Bath & Blow Dry"
                   maxlength="255" id="specInput">
            <!-- Live tag preview -->
            <div id="tagPreview" style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.5rem;min-height:1.5rem;"></div>
          </div>

          <div style="margin-bottom:1.5rem;">
            <label class="form-label">About / Bio
              <span style="font-size:.78rem;color:var(--text-muted);font-weight:400;">(max 1000 characters — describe your skills, approach, and what makes you great)</span>
            </label>
            <textarea name="bio" class="form-control" rows="7"
                      placeholder="e.g. I've been grooming dogs and cats for 5 years. I specialize in nail trimming, breed-specific haircuts, and calming anxious pets. I treat every pet like my own..."
                      maxlength="1000" id="bioTextarea"><?= sanitize($groomer['bio'] ?? '') ?></textarea>
            <div style="text-align:right;font-size:.78rem;color:var(--text-muted);margin-top:.3rem;">
              <span id="bioCount"><?= mb_strlen($groomer['bio'] ?? '') ?></span>/1000
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
    `<span style="font-size:.72rem;background:rgba(245,158,11,.1);color:#B45309;padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(245,158,11,.25);">${t}</span>`
  ).join('');
}
specInput.addEventListener('input', renderTags);
renderTags();
</script>
