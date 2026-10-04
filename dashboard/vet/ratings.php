<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'My Ratings & Feedback';
$isDashboard = true;
$db = getDB();

$vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;

// Summary stats
$avgRating  = (float)$db->prepare('SELECT COALESCE(AVG(vet_rating),0) FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL')->execute([$vetId]) ? $db->prepare('SELECT COALESCE(AVG(vet_rating),0) FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL') : 0;
$stmtAvg = $db->prepare('SELECT COALESCE(AVG(vet_rating),0) FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL');
$stmtAvg->execute([$vetId]); $avgRating = (float)$stmtAvg->fetchColumn();

$stmtTotal = $db->prepare('SELECT COUNT(*) FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL');
$stmtTotal->execute([$vetId]); $totalRatings = (int)$stmtTotal->fetchColumn();

// Star distribution
$dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
$stmtDist = $db->prepare('SELECT vet_rating, COUNT(*) AS cnt FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL GROUP BY vet_rating');
$stmtDist->execute([$vetId]);
foreach ($stmtDist->fetchAll() as $row) $dist[(int)$row['vet_rating']] = (int)$row['cnt'];

// All reviews list
$page    = max(1,(int)($_GET['page']??1));
$perPage = 15;
$stmtCount = $db->prepare('SELECT COUNT(*) FROM ratings WHERE vet_id=? AND vet_rating IS NOT NULL');
$stmtCount->execute([$vetId]); $total = (int)$stmtCount->fetchColumn();
$pg = getPaginationData($total, $page, $perPage);

$reviews = $db->prepare("
    SELECT r.*,
           CONCAT(u.first_name,' ',u.last_name) AS owner_name,
           p.name AS pet_name, p.species, p.breed,
           a.appointment_date, a.appointment_type,
           s.name AS service_name
    FROM ratings r
    JOIN users u        ON r.owner_id       = u.id
    JOIN appointments a ON r.appointment_id = a.id
    JOIN pets p         ON a.pet_id         = p.id
    LEFT JOIN services s ON a.service_id    = s.id
    WHERE r.vet_id=? AND r.vet_rating IS NOT NULL
    ORDER BY r.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$reviews->execute([$vetId]); $reviews = $reviews->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>Ratings &amp; Feedback</span>
    </div>
    <h1>My Ratings &amp; Feedback</h1>
    <p>See how pet owners rate your veterinary services.</p>
  </div>

  <div style="display:grid;grid-template-columns:260px 1fr;gap:1.5rem;align-items:start;">

    <!-- Summary Card -->
    <div class="card">
      <div class="card-body" style="text-align:center;">
        <div style="font-size:3.5rem;font-weight:800;color:var(--warning);line-height:1;">
          <?= number_format($avgRating,1) ?>
        </div>
        <div style="font-size:1.4rem;margin:.4rem 0;color:var(--warning);">
          <?php for ($i=1;$i<=5;$i++): ?>
            <?php if ($i <= round($avgRating)): ?>
              <i class="fas fa-star"></i>
            <?php else: ?>
              <i class="far fa-star"></i>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <div style="color:var(--text-muted);font-size:.875rem;">Average Rating</div>
        <div style="color:var(--text-muted);font-size:.8rem;margin-top:.25rem;"><?= $totalRatings ?> review<?= $totalRatings!=1?'s':'' ?></div>

        <!-- Distribution bars -->
        <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.5rem;">
          <?php for ($s=5;$s>=1;$s--):
            $cnt = $dist[$s] ?? 0;
            $pct = $totalRatings > 0 ? round($cnt/$totalRatings*100) : 0;
          ?>
          <div style="display:flex;align-items:center;gap:.6rem;font-size:.82rem;">
            <span style="min-width:20px;color:var(--warning);font-weight:600;"><?= $s ?>★</span>
            <div style="flex:1;background:var(--border);border-radius:50px;height:7px;">
              <div style="background:var(--warning);height:7px;border-radius:50px;width:<?= $pct ?>%;transition:width .4s;"></div>
            </div>
            <span style="min-width:24px;text-align:right;color:var(--text-muted);"><?= $cnt ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- Reviews List -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-comments" style="color:var(--primary);margin-right:.4rem"></i>Patient Reviews</h3>
      </div>

      <?php if ($reviews): ?>
        <div class="card-body" style="padding:0;">
          <?php foreach ($reviews as $r): ?>
          <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
              <!-- Owner / Pet info -->
              <div>
                <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.2rem;">
                  <div class="user-avatar-placeholder" style="width:34px;height:34px;font-size:.75rem;flex-shrink:0;">
                    <?= strtoupper(substr($r['owner_name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:700;font-size:.9rem;"><?= sanitize($r['owner_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);">
                      Pet: <?= sanitize($r['pet_name']) ?>
                      (<?= sanitize($r['breed'] ?: $r['species']) ?>)
                    </div>
                  </div>
                </div>
              </div>

              <!-- Rating stars + date -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="color:var(--warning);font-size:1rem;">
                  <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="<?= $i<=(int)$r['vet_rating']?'fas':'far' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:.15rem;">
                  <?= date('M j, Y', strtotime($r['created_at'])) ?>
                </div>
              </div>
            </div>

            <!-- Service + Appointment info -->
            <div style="margin:.6rem 0 .5rem;font-size:.78rem;color:var(--text-muted);">
              <i class="fas fa-calendar-check" style="margin-right:.3rem"></i>
              <?= date('M j, Y', strtotime($r['appointment_date'])) ?>
              &nbsp;·&nbsp;
              <?= sanitize($r['service_name'] ?? ucfirst($r['appointment_type'])) ?>
            </div>

            <!-- Comment -->
            <?php if (!empty($r['comment'])): ?>
            <div style="background:var(--bg);border-radius:var(--radius-sm);padding:.75rem 1rem;font-size:.875rem;line-height:1.6;border-left:3px solid var(--warning);margin-top:.5rem;">
              "<?= sanitize($r['comment']) ?>"
            </div>
            <?php else: ?>
            <div style="font-size:.8rem;color:var(--text-muted);font-style:italic;margin-top:.4rem;">No written comment.</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php renderPagination($pg); ?>
      <?php else: ?>
        <?= renderEmptyState('fa-star', 'No Ratings Yet', 'Your ratings will appear here once pet owners review their completed appointments.') ?>
      <?php endif; ?>
    </div>

  </div><!-- /grid -->
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
