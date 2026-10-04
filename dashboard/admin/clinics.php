<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']); $user=currentUser(); $pageTitle='Manage Clinics';
$isDashboard = true; $db=getDB();
$msg=''; $msgType='success';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid.'; $msgType='danger'; }
    else {
        $name  = sanitize($_POST['name']??''); $desc=sanitize($_POST['description']??'');
        $addr  = sanitize($_POST['address']??''); $city=sanitize($_POST['city']??'');
        $phone = sanitize($_POST['phone']??''); $email=sanitize($_POST['email']??'');
        $lat   = (float)($_POST['latitude']??0); $lng=(float)($_POST['longitude']??0);
        $cid   = (int)($_POST['clinic_id']??0);

        $logo = $cover = null;
        if (!empty($_FILES['logo']['name']))  $logo  = uploadFile($_FILES['logo'],'clinics');
        if (!empty($_FILES['cover']['name'])) $cover = uploadFile($_FILES['cover'],'clinics');

        if ($cid) {
            $sql = 'UPDATE clinics SET name=?,description=?,address=?,city=?,phone=?,email=?,latitude=?,longitude=?';
            $params = [$name,$desc,$addr,$city,$phone,$email,$lat,$lng];
            if ($logo) { $sql .= ',logo=?'; $params[] = $logo; }
            if ($cover) { $sql .= ',cover_photo=?'; $params[] = $cover; }
            $sql .= ' WHERE id=?';
            $params[] = $cid;
            if ($user['role_name'] !== 'superadmin') { $sql .= ' AND admin_id=?'; $params[] = $user['id']; }
            $db->prepare($sql)->execute($params);
            $msg = 'Clinic updated.';
        } else {
            $db->prepare('INSERT INTO clinics (admin_id,name,description,address,city,phone,email,latitude,longitude,logo,cover_photo) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$user['id'],$name,$desc,$addr,$city,$phone,$email,$lat,$lng,$logo,$cover]);
            $msg="Clinic added.";
        }
    }
}

if (isset($_GET['toggle']) && verifyCsrfToken($_GET['csrf']??'')) {
    $toggleSql = 'UPDATE clinics SET is_active=NOT is_active WHERE id=?' . ($user['role_name'] === 'superadmin' ? '' : ' AND admin_id=?');
    $toggleParams = [(int)$_GET['toggle']];
    if ($user['role_name'] !== 'superadmin') $toggleParams[] = $user['id'];
    $db->prepare($toggleSql)->execute($toggleParams);
    header('Location: clinics.php'); exit;
}

$clinicSql = "SELECT c.*,COALESCE(AVG(r.clinic_rating),0) AS avg_rating,COUNT(DISTINCT r.id) AS rcount FROM clinics c LEFT JOIN ratings r ON r.clinic_id=c.id";
if ($user['role_name'] !== 'superadmin') $clinicSql .= ' WHERE c.admin_id=?';
$clinicSql .= ' GROUP BY c.id ORDER BY c.name';
$clinicStmt = $db->prepare($clinicSql);
$clinicStmt->execute($user['role_name'] === 'superadmin' ? [] : [$user['id']]);
$clinics = $clinicStmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header"><div style="display:flex;justify-content:space-between;align-items:center;"><div><h1>Clinics</h1><p>Manage PetCare+ partner clinics.</p></div><button onclick="openModal('addClinicModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Clinic</button></div></div>
  <?php if ($msg): ?><div class="alert alert-success" data-auto-dismiss="4000"><i class="fas fa-check-circle"></i><?= $msg ?></div><?php endif; ?>
  <div class="card"><div class="table-wrap">
    <?php if ($clinics): ?>
    <table><thead><tr><th>Clinic Name</th><th>City</th><th>Phone</th><th>Rating</th><th>Reviews</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($clinics as $c): ?><tr>
      <td><div style="font-weight:600;"><?= sanitize($c['name']) ?></div><?php if($c['address']): ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize(substr($c['address'],0,50)) ?></div><?php endif; ?></td>
      <td><?= sanitize($c['city']??'—') ?></td>
      <td><?= sanitize($c['phone']??'—') ?></td>
      <td><?= starRating((float)$c['avg_rating'],false) ?></td>
      <td><?= $c['rcount'] ?></td>
      <td><?= $c['is_active']?'<span class="badge badge-success">Active</span>':'<span class="badge badge-secondary">Inactive</span>' ?></td>
      <td><div style="display:flex;gap:.4rem;">
        <a href="clinic-profile.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" aria-label="Edit <?= sanitize($c['name']) ?> profile and hours"><i class="fas fa-pen"></i> Edit</a>
        <a href="../../pages/clinic.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-eye"></i></a>
        <a href="?toggle=<?= $c['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm <?= $c['is_active']?'btn-danger':'btn-secondary' ?>" data-confirm="Toggle clinic status?"><?= $c['is_active']?'Disable':'Enable' ?></a>
      </div></td>
    </tr><?php endforeach; ?>
    </tbody></table>
    <?php else: ?><div class="empty-state"><i class="fas fa-hospital"></i><h3>No clinics yet</h3></div><?php endif; ?>
  </div></div>
</main>

<div class="modal-overlay" id="addClinicModal">
  <div class="modal" style="max-width:640px;"><div class="modal-header"><h3>Add Clinic</h3><button class="modal-close" onclick="closeModal('addClinicModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body"><form method="POST" enctype="multipart/form-data"><?= csrfField() ?>
      <div class="form-group"><label class="form-label">Clinic Name <span class="required">*</span></label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div class="form-group"><label class="form-label">Address <span class="required">*</span></label><input type="text" name="address" class="form-control" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">City</label><input type="text" name="city" class="form-control" placeholder="e.g. Dagupan"></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
        <div class="form-group"><label class="form-label">Latitude</label><input type="number" name="latitude" step="0.000001" class="form-control" placeholder="16.0430"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Longitude</label><input type="number" name="longitude" step="0.000001" class="form-control" placeholder="120.3330"></div>
        <div class="form-group"><label class="form-label">Logo</label><input type="file" name="logo" accept="image/*" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Cover Photo</label><input type="file" name="cover" accept="image/*" class="form-control"></div>
      <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;"><button type="button" onclick="closeModal('addClinicModal')" class="btn btn-outline">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Clinic</button></div>
    </form></div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
