<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['groomer']);
$user = currentUser();
$pageTitle = 'Grooming Schedule';
$isDashboard = true;
$db = getDB();

$gStmt = $db->prepare('SELECT id FROM groomers WHERE user_id=?');
$gStmt->execute([$user['id']]); $g = $gStmt->fetch(); $gId = $g['id'] ?? 0;

$status = sanitize($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['page']??1)); $perPage=20;
$where=['a.groomer_id=?']; $params=[$gId];
if ($status) { $where[]='a.status=?'; $params[]=$status; }
$ws='WHERE '.implode(' AND ',$where);
$total=$db->prepare("SELECT COUNT(*) FROM appointments a $ws");
$total->execute($params); $total=(int)$total->fetchColumn();
$pg=getPaginationData($total,$page,$perPage);
$appts=$db->prepare("SELECT a.*,p.name AS pet_name,p.breed,p.species,CONCAT(u.first_name,' ',u.last_name) AS owner_name,u.phone AS owner_phone,s.name AS service_name FROM appointments a JOIN pets p ON a.pet_id=p.id JOIN users u ON a.owner_id=u.id LEFT JOIN services s ON a.service_id=s.id $ws ORDER BY a.appointment_date DESC,a.appointment_time DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$appts->execute($params); $appts=$appts->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_groomer.php'; ?>
<main class="dash-main">
  <div class="dash-header"><h1>Grooming Schedule</h1></div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="appointments.php" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline' ?>">All</a>
    <?php foreach(['pending','approved','completed','cancelled'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="table-wrap">
      <?php if ($appts): ?>
      <table>
        <thead><tr><th>Date & Time</th><th>Pet</th><th>Owner</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($appts as $a): ?>
        <tr>
          <td><div style="font-weight:600;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div><div style="font-size:.78rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div></td>
          <td><div style="font-weight:600;"><?= sanitize($a['pet_name']) ?></div><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['breed']?:$a['species']) ?></div></td>
          <td><?= sanitize($a['owner_name']) ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize($a['owner_phone']??'') ?></div></td>
          <td><?= sanitize($a['service_name']??'Grooming') ?></td>
          <td><?= statusBadge($a['status']) ?></td>
          <td>
            <?php
              $extraActions = '';
              include __DIR__ . '/../../includes/appt_actions.php';
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty-state"><i class="fas fa-cut"></i><h3>No appointments found</h3></div><?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
