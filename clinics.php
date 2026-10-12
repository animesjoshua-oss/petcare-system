<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Find Clinics';

$search  = sanitize($_GET['search'] ?? '');
$city    = sanitize($_GET['city'] ?? '');
$service = sanitize($_GET['service'] ?? '');
$sort    = sanitize($_GET['sort'] ?? 'name'); // Default to A-Z with custom city order
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20; // Increased to show all clinics on one page

$where  = ['c.is_active = 1'];
$params = [];

if ($search) { $where[] = '(c.name LIKE ? OR c.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($city)   { $where[] = 'c.city = ?'; $params[] = $city; }

$whereStr = 'WHERE ' . implode(' AND ', $where);

// Custom city order: Calasiao → Dagupan → San Fabian
$cityOrder = "CASE 
    WHEN c.city = 'Calasiao' THEN 1 
    WHEN c.city = 'Dagupan' THEN 2 
    WHEN c.city = 'San Fabian' THEN 3 
    ELSE 4 
END";

$orderBy  = match($sort) { 
    'name' => "$cityOrder, c.name ASC", 
    'newest' => 'c.created_at DESC', 
    default => 'c.average_rating DESC, c.total_ratings DESC, c.name ASC' 
};

$totalStmt = getDB()->prepare("SELECT COUNT(*) FROM clinics c $whereStr");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pg    = getPaginationData($total, $page, $perPage);

$stmt = getDB()->prepare("
    SELECT c.*, c.average_rating as avg_rating, c.total_ratings as rcount,
           COUNT(DISTINCT a.id) as appt_count
    FROM clinics c
    LEFT JOIN appointments a ON a.clinic_id = c.id
    $whereStr
    GROUP BY c.id
    ORDER BY $orderBy
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$stmt->execute($params);
$clinics = $stmt->fetchAll();

$cities = getDB()->query("
    SELECT DISTINCT city 
    FROM clinics 
    WHERE is_active=1 AND city IS NOT NULL 
    ORDER BY 
        CASE 
            WHEN city = 'Calasiao' THEN 1 
            WHEN city = 'Dagupan' THEN 2 
            WHEN city = 'San Fabian' THEN 3 
            ELSE 4 
        END, 
        city
")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="background:var(--bg);min-height:100vh;">
  <!-- Page Header -->
  <div style="background:linear-gradient(135deg,var(--primary),var(--secondary));padding:3rem 0;color:#fff !important;text-align:center;">
    <div class="container">
      <h1 style="font-size:2.25rem;font-weight:800;margin-bottom:.5rem;color:#fff !important;">Find Clinics in Pangasinan</h1>
      <p style="opacity:.9;font-size:1rem;color:#fff !important;">Browse <?= $total ?> verified veterinary and grooming centers</p>
    </div>
  </div>

  <div class="container" style="padding:2rem 1.5rem;">
    <!-- Search & Filter Bar -->
    <div class="card" style="margin-bottom:2rem;">
      <div class="card-body">
        <form method="GET" style="display:grid;grid-template-columns:1fr auto auto auto;gap:1rem;align-items:end;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Search</label>
            <div style="position:relative;">
              <i class="fas fa-search" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
              <input type="text" name="search" class="form-control" placeholder="Clinic name, description…" value="<?= sanitize($search) ?>" style="padding-left:2.5rem;">
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">City</label>
            <select name="city" class="form-control">
              <option value="">All Cities</option>
              <?php foreach ($cities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $city===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Sort By</label>
            <select name="sort" class="form-control">
              <option value="rating"  <?= $sort==='rating' ?'selected':'' ?>>Top Rated</option>
              <option value="name"    <?= $sort==='name'   ?'selected':'' ?>>A – Z</option>
              <option value="newest"  <?= $sort==='newest' ?'selected':'' ?>>Newest</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="height:44px;white-space:nowrap;"><i class="fas fa-filter"></i> Filter</button>
        </form>
      </div>
    </div>

    <!-- Results -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <p style="color:var(--text-muted);font-size:.9rem;">Showing <?= count($clinics) ?> of <?= $total ?> clinics</p>
      <?php if ($search || $city): ?>
      <a href="clinics.php" style="font-size:.85rem;color:var(--danger);"><i class="fas fa-times"></i> Clear filters</a>
      <?php endif; ?>
    </div>

    <div class="clinics-grid">
      <?php 
      if ($clinics) {
        foreach ($clinics as $cl) {
          echo renderClinicCard($cl, false);
        }
      } else {
        echo renderEmptyState('fa-search', 'No clinics found', 'Try adjusting your search filters.');
      }
      ?>
    </div>

    <!-- Pagination -->
    <?php if ($pg['totalPages'] > 1): ?>
    <div class="pagination">
      <?php if ($pg['page'] > 1): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']-1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php for ($i=1; $i<=$pg['totalPages']; $i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-btn <?= $i===$pg['page']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($pg['page'] < $pg['totalPages']): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']+1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
