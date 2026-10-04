<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Manage Appointments';
$isDashboard = true;
$db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';

$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$date   = sanitize($_GET['date'] ?? '');
$page   = max(1,(int)($_GET['page']??1)); $perPage=20;

$where=['1=1']; $params=[];
if (!$isSuperAdmin) { $where[]='a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=?)'; $params[]=$user['id']; }
if ($status) { $where[]='a.status=?'; $params[]=$status; }
if ($date)   { $where[]='a.appointment_date=?'; $params[]=$date; }
if ($search) { $where[]='(p.name LIKE ? OR ou.first_name LIKE ? OR ou.last_name LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
$ws='WHERE '.implode(' AND ',$where);

$total=$db->prepare("SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users ou ON a.owner_id=ou.id $ws");
$total->execute($params); $total=(int)$total->fetchColumn();
$pg=getPaginationData($total,$page,$perPage);

$appts=$db->prepare("
    SELECT a.*,p.name AS pet_name,CONCAT(ou.first_name,' ',ou.last_name) AS owner_name,
           c.name AS clinic_name,s.name AS service_name,
           CONCAT(uv.first_name,' ',uv.last_name) AS vet_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users ou ON a.owner_id=ou.id
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users uv ON v.user_id=uv.id
    $ws ORDER BY a.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$appts->execute($params); $appts=$appts->fetchAll();

$statusCounts = [];
foreach(['pending','approved','completed','rejected','cancelled'] as $s) {
    $countSql="SELECT COUNT(*) FROM appointments WHERE status=?".($isSuperAdmin?'':' AND clinic_id IN (SELECT id FROM clinics WHERE admin_id=?)');
    $cs=$db->prepare($countSql); $cs->execute($isSuperAdmin?[$s]:[$s,$user['id']]); $statusCounts[$s]=(int)$cs->fetchColumn();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Appointments</span></div>
    <h1>Manage Appointments</h1>
  </div>

  <!-- Status tabs -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
    <a href="appointments.php" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline' ?>">All (<?= array_sum($statusCounts) ?>)</a>
    <?php foreach ($statusCounts as $s=>$c): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?> (<?= $c ?>)</a>
    <?php endforeach; ?>
  </div>

  <!-- Search/filter -->
  <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
    <input type="hidden" name="status" value="<?= $status ?>">
    <div class="form-group" style="margin:0;flex:1;min-width:200px;">
      <label class="form-label">Search Pet / Owner</label>
      <input type="text" name="search" class="form-control" placeholder="Pet name, owner name…" value="<?= sanitize($search) ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Date</label>
      <input type="date" name="date" class="form-control" value="<?= $date ?>">
    </div>
    <button class="btn btn-primary" style="height:44px;margin-top:auto;">Filter</button>
    <?php if ($search||$date): ?><a href="?status=<?= $status ?>" class="btn btn-outline" style="height:44px;margin-top:auto;">Clear</a><?php endif; ?>
  </form>

  <div class="card">
    <div class="table-wrap">
      <?php if ($appts): ?>
      <table>
        <thead><tr><th>#</th><th>Pet</th><th>Owner</th><th>Clinic</th><th>Service</th><th>Date & Time</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($appts as $a): ?>
        <tr>
          <td style="font-size:.8rem;color:var(--text-muted)">#<?= $a['id'] ?></td>
          <td style="font-weight:600;"><?= sanitize($a['pet_name']) ?></td>
          <td><?= sanitize($a['owner_name']) ?></td>
          <td style="font-size:.85rem;"><?= sanitize($a['clinic_name']) ?></td>
          <td style="font-size:.85rem;"><?= sanitize($a['service_name']??ucfirst($a['appointment_type'])) ?></td>
          <td>
            <div style="font-weight:600;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
          </td>
          <td><?= statusBadge($a['status']) ?></td>
          <td>
            <?php if (!empty($a['symptom_photo'])): ?>
            <a href="<?= assetUrl($a['symptom_photo']) ?>" target="_blank" rel="noopener noreferrer"
               title="View symptom photo" style="display:inline-block;margin-bottom:.35rem;">
              <img src="<?= assetUrl($a['symptom_photo']) ?>"
                   alt="Symptom"
                   style="width:48px;height:48px;object-fit:cover;border-radius:8px;
                          border:2px solid var(--primary);cursor:zoom-in;">
            </a><br>
            <?php endif; ?>
            <?php $extraActions = '<a href="records.php?pet='.$a['pet_id'].'&appt='.$a['id'].'" class="btn btn-sm btn-outline"><i class="fas fa-file-medical"></i> Add Medical Record</a>'; include __DIR__ . '/../../includes/appt_actions.php'; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty-state"><i class="fas fa-calendar"></i><h3>No appointments found</h3></div><?php endif; ?>
    </div>
  </div>
  <?php renderPagination($pg); ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
