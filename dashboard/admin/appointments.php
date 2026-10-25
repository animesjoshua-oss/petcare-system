<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Manage Appointments';
$isDashboard = true;
$db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);
if ($isSuperAdmin) {
    $pageTitle = 'Manage Appointments';
    $clinicType = '';
} elseif ($isGroomerAdmin) {
    $pageTitle = 'Grooming Appointments';
    $clinicType = 'grooming';
} else {
    $pageTitle = 'Veterinary Appointments';
    $clinicType = 'veterinary';
}

$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$date   = sanitize($_GET['date'] ?? '');
$page   = max(1,(int)($_GET['page']??1)); $perPage=20;

$where=['1=1']; $params=[];
if (!$isSuperAdmin) {
    $where[]='a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type=?)';
    $params[]=$user['id'];
    $params[]=$clinicType;
    $where[]='a.appointment_type=?';
    $params[]=$clinicType;
}
if ($status) { $where[]='a.status=?'; $params[]=$status; }
if ($date)   { $where[]='a.appointment_date=?'; $params[]=$date; }
if ($search) { $where[]='(p.name LIKE ? OR ou.first_name LIKE ? OR ou.last_name LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
$ws='WHERE '.implode(' AND ',$where);

$total=$db->prepare("SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users ou ON a.owner_id=ou.id $ws");
$total->execute($params); $total=(int)$total->fetchColumn();
$pg=getPaginationData($total,$page,$perPage);

$appts=$db->prepare("
    SELECT a.*,p.name AS pet_name,p.species AS pet_species,p.breed AS pet_breed,
           CONCAT(ou.first_name,' ',ou.last_name) AS owner_name,ou.email AS owner_email,ou.phone AS owner_phone,
           c.name AS clinic_name,s.name AS service_name,
           CONCAT(uv.first_name,' ',uv.last_name) AS vet_name,
           CONCAT(ug.first_name,' ',ug.last_name) AS groomer_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users ou ON a.owner_id=ou.id
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users uv ON v.user_id=uv.id
    LEFT JOIN groomers g ON a.groomer_id=g.id
    LEFT JOIN users ug ON g.user_id=ug.id
    $ws ORDER BY a.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$appts->execute($params); $appts=$appts->fetchAll();

$statusCounts = [];
foreach(['pending','approved','completed','rejected','cancelled'] as $s) {
    $countSql='SELECT COUNT(*) FROM appointments WHERE status=?';
    $countParams=[$s];
    if (!$isSuperAdmin) {
        $countSql.=' AND clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type=?) AND appointment_type=?';
        $countParams[]=$user['id'];
        $countParams[]=$clinicType;
        $countParams[]=$clinicType;
    }
    $cs=$db->prepare($countSql); $cs->execute($countParams); $statusCounts[$s]=(int)$cs->fetchColumn();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Appointments</span></div>
    <h1><?php if ($isSuperAdmin): ?>Manage Appointments<?php elseif ($isGroomerAdmin): ?>Grooming Appointments<?php else: ?>Veterinary Appointments<?php endif; ?></h1>
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
        <thead><tr><th>#</th><th>Pet</th><th>Pet Owner</th><th>Clinic</th><th>Service / Details</th><?php if ($isSuperAdmin): ?><th>Veterinarian</th><th>Groomer</th><?php elseif ($isGroomerAdmin): ?><th>Groomer</th><?php else: ?><th>Veterinarian</th><?php endif; ?><th>Date &amp; Time</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($appts as $a): ?>
        <?php if ($a['service_name']) $serviceLabel = $a['service_name']; else $serviceLabel = ucfirst($a['appointment_type']); ?>
        <tr>
          <td style="font-size:.8rem;color:var(--text-muted)">#<?= $a['id'] ?></td>
          <td style="font-weight:600;"><?= sanitize($a['pet_name']) ?><div class="form-hint"><?= sanitize($a['pet_species']) ?><?php if ($a['pet_breed']): ?> · <?= sanitize($a['pet_breed']) ?><?php endif; ?></div></td>
          <td><?= sanitize($a['owner_name']) ?><?php if ($a['owner_email']): ?><div class="form-hint"><?= sanitize($a['owner_email']) ?></div><?php endif; ?><?php if ($a['owner_phone']): ?><div class="form-hint"><?= sanitize($a['owner_phone']) ?></div><?php endif; ?></td>
          <td style="font-size:.85rem;"><?= sanitize($a['clinic_name']) ?></td>
          <td style="font-size:.85rem;"><strong><?= sanitize($serviceLabel) ?></strong><?php if ($a['reason']): ?><div class="form-hint">Reason: <?= sanitize($a['reason']) ?></div><?php endif; ?><?php if ($a['notes']): ?><div class="form-hint">Notes: <?= sanitize($a['notes']) ?></div><?php endif; ?><?php if ($a['rejection_reason']): ?><div class="form-hint">Rejection reason: <?= sanitize($a['rejection_reason']) ?></div><?php endif; ?><?php if ($a['reschedule_date']): ?><div class="form-hint">Rescheduled to: <?= date('M j, Y', strtotime($a['reschedule_date'])) ?><?php if ($a['reschedule_time']): ?> at <?= date('g:i A', strtotime($a['reschedule_time'])) ?><?php endif; ?></div><?php endif; ?></td>
          <?php if ($isSuperAdmin): ?><td><?= sanitize($a['vet_name'] ?? '—') ?></td><td><?= sanitize($a['groomer_name'] ?? '—') ?></td><?php elseif ($isGroomerAdmin): ?><td><?= sanitize($a['groomer_name'] ?? '—') ?></td><?php else: ?><td><?= sanitize($a['vet_name'] ?? '—') ?></td><?php endif; ?>
          <td>
            <div style="font-weight:600;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
          </td>
          <td><?= statusBadge($a['status']) ?></td>
          <td>
            <?php if (!$isGroomerAdmin && !empty($a['symptom_photo'])): ?>
            <a href="<?= assetUrl($a['symptom_photo']) ?>" target="_blank" rel="noopener noreferrer"
               title="View symptom photo" style="display:inline-block;margin-bottom:.35rem;">
              <img src="<?= assetUrl($a['symptom_photo']) ?>"
                   alt="Symptom"
                   style="width:48px;height:48px;object-fit:cover;border-radius:8px;
                          border:2px solid var(--primary);cursor:zoom-in;">
            </a><br>
            <?php endif; ?>
            <?php if ($isGroomerAdmin) $extraActions = ''; else $extraActions = '<a href="records.php?pet='.$a['pet_id'].'&appt='.$a['id'].'" class="btn btn-sm btn-outline"><i class="fas fa-file-medical"></i> Add Medical Record</a>'; include __DIR__ . '/../../includes/appt_actions.php'; ?>
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
