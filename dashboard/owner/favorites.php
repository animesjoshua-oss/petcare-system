<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser(); $pageTitle='Favorite Clinics';
$isDashboard = true; $db=getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Please refresh the page and try again.');
    }
    $db->prepare('DELETE FROM favorite_clinics WHERE user_id=? AND clinic_id=?')->execute([$user['id'],(int)$_POST['remove']]);
    header('Location: favorites.php?removed=1'); exit;
}

$favs = $db->prepare("SELECT c.*,COALESCE(AVG(r.clinic_rating),0) AS avg_rating,COUNT(DISTINCT r.id) AS rcount FROM favorite_clinics fc JOIN clinics c ON fc.clinic_id=c.id LEFT JOIN ratings r ON r.clinic_id=c.id WHERE fc.user_id=? AND c.is_active=1 AND c.is_verified=1 GROUP BY c.id ORDER BY fc.created_at DESC");
$favs->execute([$user['id']]); $favs=$favs->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header"><h1><i class="fas fa-heart" style="color:var(--danger);margin-right:.5rem"></i> Favorite Clinics</h1></div>
  <?php if (isset($_GET['removed'])): ?><div class="alert alert-success" data-auto-dismiss="3000"><i class="fas fa-check-circle"></i> Removed from favorites.</div><?php endif; ?>
  <?php if ($favs): ?>
  <div class="clinics-grid">
    <?php foreach ($favs as $c): echo renderClinicCard($c, true); endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card"><div class="card-body">
    <?= renderEmptyState('fa-heart', 'No favorite clinics yet', 'Browse clinics and save your favorites.', 'Browse Clinics', APP_URL . '/pages/clinics.php') ?>
  </div></div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
