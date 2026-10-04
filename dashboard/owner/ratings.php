<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'My Ratings';
$isDashboard = true;
$db = getDB();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$total = $db->prepare("SELECT COUNT(*) FROM ratings WHERE owner_id = ?");
$total->execute([$user['id']]);
$total = (int)$total->fetchColumn();
$pg = getPaginationData($total, $page, $perPage);

$stmt = $db->prepare("
    SELECT r.*,
           c.name  AS clinic_name,
           p.name  AS pet_name,
           a.appointment_date,
           a.appointment_type,
           s.name  AS service_name,
           CONCAT(uv.first_name,' ',uv.last_name) AS vet_name,
           CONCAT(ug.first_name,' ',ug.last_name) AS groomer_name
    FROM ratings r
    JOIN clinics      c  ON r.clinic_id = c.id
    JOIN appointments a  ON r.appointment_id = a.id
    LEFT JOIN pets    p  ON a.pet_id = p.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN veterinarians v  ON r.vet_id = v.id
    LEFT JOIN users uv ON v.user_id = uv.id
    LEFT JOIN groomers g   ON r.groomer_id = g.id
    LEFT JOIN users ug ON g.user_id = ug.id
    WHERE r.owner_id = ?
    ORDER BY r.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$stmt->execute([$user['id']]);
$ratings = $stmt->fetchAll();

// Check how many completed appointments are still unrated
$unrated = $db->prepare("
    SELECT COUNT(*) FROM appointments a
    LEFT JOIN ratings r ON r.appointment_id = a.id
    WHERE a.owner_id = ? AND a.status = 'completed' AND r.id IS NULL
");
$unrated->execute([$user['id']]);
$unratedCount = (int)$unrated->fetchColumn();

function renderStars(int $val): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $val ? '#F59E0B' : '#D1D5DB';
        $html .= "<i class='fas fa-star' style='color:{$color};font-size:.85rem;'></i>";
    }
    return $html;
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>My Ratings</span>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
      <div>
        <h1>My Ratings</h1>
        <p>All the reviews you've submitted for your pet's appointments.</p>
      </div>
      <?php if ($unratedCount): ?>
      <a href="appointments.php?status=completed" class="btn btn-primary">
        <i class="fas fa-star"></i> Rate Pending (<?= $unratedCount ?>)
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($unratedCount): ?>
  <div class="alert alert-info" style="margin-bottom:1.5rem;">
    <i class="fas fa-info-circle"></i>
    You have <strong><?= $unratedCount ?></strong> completed appointment<?= $unratedCount > 1 ? 's' : '' ?> waiting for a review.
    <a href="appointments.php?status=completed" style="margin-left:.5rem;font-weight:600;">Leave a review &rarr;</a>
  </div>
  <?php endif; ?>

  <?php if ($ratings): ?>
  <div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($ratings as $r): ?>
    <div class="card">
      <div class="card-body">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
          <!-- Left: clinic + meta -->
          <div>
            <div style="font-weight:700;font-size:1.05rem;margin-bottom:.2rem;">
              <i class="fas fa-clinic-medical" style="color:var(--primary);margin-right:.4rem;font-size:.9rem;"></i>
              <?= sanitize($r['clinic_name']) ?>
            </div>
            <div style="font-size:.82rem;color:var(--text-muted);display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem;">
              <?php if ($r['pet_name']): ?>
              <span><i class="fas fa-dog" style="margin-right:.3rem;"></i><?= sanitize($r['pet_name']) ?></span>
              <?php endif; ?>
              <span><i class="fas fa-calendar-alt" style="margin-right:.3rem;"></i><?= date('M j, Y', strtotime($r['appointment_date'])) ?></span>
              <span><i class="fas fa-tag" style="margin-right:.3rem;"></i><?= sanitize($r['service_name'] ?? ucfirst($r['appointment_type'])) ?></span>
            </div>

            <!-- Ratings row -->
            <div style="display:flex;flex-wrap:wrap;gap:1.25rem;">
              <div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.04em;">Clinic</div>
                <div><?= renderStars($r['clinic_rating']) ?> <span style="font-size:.85rem;font-weight:600;margin-left:.3rem;"><?= $r['clinic_rating'] ?>/5</span></div>
              </div>
              <?php if ($r['vet_rating'] && $r['vet_name']): ?>
              <div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.04em;">Dr. <?= sanitize($r['vet_name']) ?></div>
                <div><?= renderStars($r['vet_rating']) ?> <span style="font-size:.85rem;font-weight:600;margin-left:.3rem;"><?= $r['vet_rating'] ?>/5</span></div>
              </div>
              <?php endif; ?>
              <?php if ($r['groomer_rating'] && $r['groomer_name']): ?>
              <div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.04em;"><?= sanitize($r['groomer_name']) ?></div>
                <div><?= renderStars($r['groomer_rating']) ?> <span style="font-size:.85rem;font-weight:600;margin-left:.3rem;"><?= $r['groomer_rating'] ?>/5</span></div>
              </div>
              <?php endif; ?>
            </div>

            <?php if (trim($r['comment'] ?? '')): ?>
            <div style="margin-top:.85rem;padding:.75rem 1rem;background:var(--bg);border-radius:var(--radius-sm);font-size:.875rem;color:var(--text);border-left:3px solid var(--primary);">
              <i class="fas fa-quote-left" style="color:var(--primary);margin-right:.4rem;font-size:.75rem;"></i>
              <?= nl2br(sanitize($r['comment'])) ?>
            </div>
            <?php endif; ?>
          </div>
          <!-- Right: date submitted -->
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:.75rem;color:var(--text-muted);">Submitted</div>
            <div style="font-size:.82rem;font-weight:600;"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php renderPagination($pg); ?>

  <?php else: ?>
  <div class="card">
    <div class="card-body empty-state">
      <i class="fas fa-star" style="font-size:3rem;color:#D1D5DB;margin-bottom:1rem;"></i>
      <h3>No Ratings Yet</h3>
      <p style="color:var(--text-muted);">After a completed appointment, you can rate the clinic and staff.</p>
      <?php if ($unratedCount): ?>
      <a href="appointments.php?status=completed" class="btn btn-primary mt-2">
        <i class="fas fa-star"></i> Leave Your First Review
      </a>
      <?php else: ?>
      <a href="<?= APP_URL ?>/pages/clinics.php" class="btn btn-primary mt-2">
        <i class="fas fa-hospital"></i> Find a Clinic
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
