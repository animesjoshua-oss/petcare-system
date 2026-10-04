<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Ratings Overview';
$isDashboard = true;
$db = getDB();

$ratings = $db->query("
    SELECT r.*,
           CONCAT(u.first_name,' ',u.last_name) AS owner_name,
           c.name AS clinic_name,
           p.name AS pet_name,
           CONCAT(vu.first_name,' ',vu.last_name) AS vet_name,
           CONCAT(gu.first_name,' ',gu.last_name) AS groomer_name
    FROM ratings r
    JOIN users u        ON r.owner_id       = u.id
    JOIN clinics c      ON r.clinic_id      = c.id
    JOIN appointments a ON r.appointment_id = a.id
    JOIN pets p         ON a.pet_id         = p.id
    LEFT JOIN veterinarians v ON r.vet_id    = v.id
    LEFT JOIN users vu        ON v.user_id   = vu.id
    LEFT JOIN groomers g      ON r.groomer_id = g.id
    LEFT JOIN users gu        ON g.user_id    = gu.id
    ORDER BY r.created_at DESC
    LIMIT 100
")->fetchAll();

$avgClinic  = (float)$db->query("SELECT COALESCE(AVG(clinic_rating),0)  FROM ratings WHERE clinic_rating  IS NOT NULL")->fetchColumn();
$avgVet     = (float)$db->query("SELECT COALESCE(AVG(vet_rating),0)     FROM ratings WHERE vet_rating     IS NOT NULL")->fetchColumn();
$avgGroomer = (float)$db->query("SELECT COALESCE(AVG(groomer_rating),0) FROM ratings WHERE groomer_rating IS NOT NULL")->fetchColumn();
$totalRatings = (int)$db->query("SELECT COUNT(*) FROM ratings")->fetchColumn();

// Clinic star distribution
$dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($ratings as $r) {
    if ($r['clinic_rating']) $dist[(int)$r['clinic_rating']] = ($dist[(int)$r['clinic_rating']] ?? 0) + 1;
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <h1>Ratings Overview</h1>
    <p>Monitor service quality and pet owner feedback.</p>
  </div>

  <!-- Top summary stats -->
  <?= renderStatsRow([
    ['value' => number_format($avgClinic,1).'★',  'label' => 'Avg Clinic Rating',     'icon' => 'fa-hospital',    'color' => 'blue'],
    ['value' => number_format($avgVet,1).'★',     'label' => 'Avg Vet Rating',         'icon' => 'fa-user-md',     'color' => 'green'],
    ['value' => number_format($avgGroomer,1).'★', 'label' => 'Avg Groomer Rating',     'icon' => 'fa-cut',         'color' => 'orange'],
    ['value' => $totalRatings,                    'label' => 'Total Reviews',           'icon' => 'fa-star',        'color' => 'purple'],
  ]) ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">

    <!-- Clinic Rating Summary -->
    <div class="card">
      <div class="card-header"><h3>Clinic Rating</h3></div>
      <div class="card-body" style="text-align:center;">
        <div style="font-size:3rem;font-weight:800;color:var(--warning);"><?= number_format($avgClinic,1) ?></div>
        <div class="stars" style="font-size:1.3rem;margin:.5rem 0;">
          <?php for ($i=1;$i<=5;$i++): ?>
            <i class="<?= $i<=round($avgClinic)?'fas':'far' ?> fa-star"></i>
          <?php endfor; ?>
        </div>
        <div style="color:var(--text-muted);font-size:.875rem;">Average Clinic Rating</div>
        <div style="color:var(--text-muted);font-size:.8rem;margin-top:.25rem;"><?= $totalRatings ?> total reviews</div>
        <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.5rem;">
          <?php for ($s=5;$s>=1;$s--): $count=$dist[$s]??0; $pct=$totalRatings>0?round($count/$totalRatings*100):0; ?>
          <div style="display:flex;align-items:center;gap:.75rem;font-size:.82rem;">
            <span style="min-width:20px;color:var(--warning);font-weight:600;"><?= $s ?>★</span>
            <div style="flex:1;background:var(--border);border-radius:50px;height:8px;">
              <div style="background:var(--warning);height:8px;border-radius:50px;width:<?= $pct ?>%;"></div>
            </div>
            <span style="min-width:30px;text-align:right;color:var(--text-muted)"><?= $count ?></span>
          </div>
          <?php endfor; ?>
        </div>

        <!-- Vet & Groomer averages mini -->
        <div style="margin-top:1.75rem;border-top:1px solid var(--border);padding-top:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.875rem;">
            <span><i class="fas fa-user-md" style="color:var(--success);margin-right:.4rem"></i>Vet Avg</span>
            <span style="font-weight:700;color:var(--warning);">
              <?= number_format($avgVet,1) ?> <i class="fas fa-star" style="font-size:.75rem"></i>
            </span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.875rem;">
            <span><i class="fas fa-cut" style="color:var(--warning);margin-right:.4rem"></i>Groomer Avg</span>
            <span style="font-weight:700;color:var(--warning);">
              <?= number_format($avgGroomer,1) ?> <i class="fas fa-star" style="font-size:.75rem"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Reviews Table -->
    <div class="card">
      <div class="card-header"><h3>All Reviews</h3></div>
      <div class="table-wrap">
        <?php if ($ratings): ?>
        <table>
          <thead>
            <tr>
              <th>Pet Owner</th>
              <th>Pet</th>
              <th>Clinic</th>
              <th style="text-align:center;">Clinic ★</th>
              <th style="text-align:center;">Vet ★</th>
              <th style="text-align:center;">Groomer ★</th>
              <th>Comment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ratings as $r): ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($r['owner_name']) ?></td>
            <td><?= sanitize($r['pet_name']) ?></td>
            <td><?= sanitize($r['clinic_name']) ?></td>

            <!-- Clinic rating -->
            <td style="text-align:center;">
              <?php if ($r['clinic_rating']): ?>
                <span style="color:var(--warning);white-space:nowrap;">
                  <?php for ($i=1;$i<=5;$i++): ?><i class="<?= $i<=(int)$r['clinic_rating']?'fas':'far' ?> fa-star" style="font-size:.75rem"></i><?php endfor; ?>
                  <span style="font-size:.78rem;font-weight:600;display:block;"><?= $r['clinic_rating'] ?>.0</span>
                </span>
              <?php else: ?><span style="color:var(--text-muted);font-size:.8rem;">—</span><?php endif; ?>
            </td>

            <!-- Vet rating -->
            <td style="text-align:center;">
              <?php if ($r['vet_rating']): ?>
                <span style="color:var(--warning);white-space:nowrap;">
                  <?php for ($i=1;$i<=5;$i++): ?><i class="<?= $i<=(int)$r['vet_rating']?'fas':'far' ?> fa-star" style="font-size:.75rem"></i><?php endfor; ?>
                  <span style="font-size:.78rem;font-weight:600;display:block;"><?= $r['vet_rating'] ?>.0</span>
                </span>
                <?php if ($r['vet_name']): ?>
                  <div style="font-size:.72rem;color:var(--text-muted);">Dr. <?= sanitize($r['vet_name']) ?></div>
                <?php endif; ?>
              <?php else: ?><span style="color:var(--text-muted);font-size:.8rem;">—</span><?php endif; ?>
            </td>

            <!-- Groomer rating -->
            <td style="text-align:center;">
              <?php if ($r['groomer_rating']): ?>
                <span style="color:var(--warning);white-space:nowrap;">
                  <?php for ($i=1;$i<=5;$i++): ?><i class="<?= $i<=(int)$r['groomer_rating']?'fas':'far' ?> fa-star" style="font-size:.75rem"></i><?php endfor; ?>
                  <span style="font-size:.78rem;font-weight:600;display:block;"><?= $r['groomer_rating'] ?>.0</span>
                </span>
                <?php if ($r['groomer_name']): ?>
                  <div style="font-size:.72rem;color:var(--text-muted);"><?= sanitize($r['groomer_name']) ?></div>
                <?php endif; ?>
              <?php else: ?><span style="color:var(--text-muted);font-size:.8rem;">—</span><?php endif; ?>
            </td>

            <td style="font-size:.85rem;max-width:200px;"><?= sanitize(substr($r['comment']??'—',0,80)) ?></td>
            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;"><?= date('M j, Y',strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <?= renderEmptyState('fa-star', 'No ratings yet', 'Reviews will appear here once pet owners submit feedback.') ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
