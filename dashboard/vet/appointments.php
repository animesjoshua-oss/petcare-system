<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'Appointments';
$isDashboard = true;
$db = getDB();

$vetStmt = $db->prepare('SELECT id FROM veterinarians WHERE user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;

$status = sanitize($_GET['status'] ?? '');
$date   = sanitize($_GET['date'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$perPage = 20;

$where = ['a.vet_id = ?']; $params = [$vetId];
if ($status) { $where[]='a.status=?'; $params[]=$status; }
if ($date)   { $where[]='a.appointment_date=?'; $params[]=$date; }
$ws = 'WHERE '.implode(' AND ',$where);

$total = $db->prepare("SELECT COUNT(*) FROM appointments a $ws");
$total->execute($params); $total=(int)$total->fetchColumn();
$pg = getPaginationData($total,$page,$perPage);

$appts = $db->prepare("
    SELECT a.*,p.name AS pet_name,p.species,p.breed,
           CONCAT(u.first_name,' ',u.last_name) AS owner_name,u.phone AS owner_phone,
           s.name AS service_name,c.name AS clinic_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users u ON a.owner_id=u.id
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    $ws ORDER BY a.appointment_date DESC,a.appointment_time DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$appts->execute($params); $appts=$appts->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Appointments</span></div>
    <h1>My Appointments</h1>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
    <div class="form-group" style="margin:0;">
      <label class="form-label">Filter by Status</label>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">All Status</option>
        <?php foreach(['pending','approved','completed','rejected','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Filter by Date</label>
      <input type="date" name="date" class="form-control" value="<?= $date ?>" onchange="this.form.submit()">
    </div>
    <?php if ($status||$date): ?><a href="appointments.php" class="btn btn-sm btn-outline" style="margin-bottom:0;">Clear</a><?php endif; ?>
  </form>

  <div class="card">
    <div class="table-wrap">
      <?php if ($appts): ?>
      <table>
        <thead><tr><th>Date & Time</th><th>Pet</th><th>Owner</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($appts as $a): ?>
        <tr>
          <td>
            <div style="font-weight:600;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
          </td>
          <td>
            <div style="font-weight:600;"><?= sanitize($a['pet_name']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['breed']?:$a['species']) ?></div>
          </td>
          <td><?= sanitize($a['owner_name']) ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['owner_phone']??'') ?></div></td>
          <td><?= sanitize($a['service_name']??ucfirst($a['appointment_type'])) ?></td>
          <td>
            <?php if (!empty($a['symptom_photo'])): ?>
            <a href="<?= assetUrl($a['symptom_photo']) ?>" target="_blank" rel="noopener noreferrer"
               title="View symptom photo"
               style="display:inline-block;margin-bottom:.35rem;">
              <img src="<?= assetUrl($a['symptom_photo']) ?>"
                   alt="Symptom photo"
                   style="width:52px;height:52px;object-fit:cover;border-radius:8px;
                          border:2px solid var(--primary);cursor:zoom-in;">
            </a><br>
            <?php endif; ?>
            <?= statusBadge($a['status']) ?>
          </td>
          <td>
            <?php
              $extraActions = '<a href="records.php?pet='.$a['pet_id'].'&appt='.$a['id'].'" class="btn btn-sm btn-outline"><i class="fas fa-file-medical"></i> Add Medical Record</a>';
              include __DIR__ . '/../../includes/appt_actions.php';
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state"><i class="fas fa-calendar"></i><h3>No appointments found</h3></div>
      <?php endif; ?>
    </div>
  </div>
  <?php renderPagination($pg); ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
