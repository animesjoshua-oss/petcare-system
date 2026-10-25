<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$isDashboard = true;
$db = getDB();
$msg = ''; $msgType='success';
$isSuperAdmin = $user['role_name'] === 'superadmin';
$isGroomerAdmin = isGroomerAdmin($user);
$pageTitle = $isGroomerAdmin ? 'Manage Grooming Services' : 'Manage Services';
$clinicSql = 'SELECT id,name FROM clinics WHERE is_active=1' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ' ORDER BY name';
$clinicStmt = $db->prepare($clinicSql);
$clinicStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$clinics = $clinicStmt->fetchAll();
$assignedClinicId = !$isSuperAdmin && $clinics ? (int)$clinics[0]['id'] : 0;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $clinicId = $isSuperAdmin ? (int)($_POST['clinic_id'] ?? 0) : $assignedClinicId;
        $name     = sanitize($_POST['name']??'');
        $desc     = sanitize($_POST['description']??'');
        $type     = sanitize($_POST['service_type']??'veterinary');
        $dur      = (int)($_POST['duration_minutes']??30);
        $price    = (float)($_POST['price']??0);
        $svcId    = (int)($_POST['service_id']??0);
        $veterinarianDiscount = trim(strip_tags((string)($_POST['veterinarian_discount_details'] ?? '')));
        $groomerDiscount = trim(strip_tags((string)($_POST['groomer_discount_details'] ?? '')));
        if ($type === 'veterinary') $groomerDiscount = '';
        if ($type === 'grooming' || $isGroomerAdmin) $veterinarianDiscount = '';

        if ($isGroomerAdmin && $type !== 'grooming') {
            $msg = 'Groomer Admins can manage grooming services only.'; $msgType = 'danger';
        } elseif (!in_array($type, ['veterinary','grooming','both'], true)) {
            $msg = 'Choose a valid service type.'; $msgType = 'danger';
        } elseif (strlen($veterinarianDiscount) > 500 || strlen($groomerDiscount) > 500) {
            $msg = 'Discount details must be 500 characters or fewer.'; $msgType = 'danger';
        } else {
        $clinicAccess = $db->prepare('SELECT id FROM clinics WHERE id=?' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ($isGroomerAdmin ? " AND clinic_type='grooming'" : ''));
        $clinicAccess->execute($isSuperAdmin ? [$clinicId] : [$clinicId, $user['id']]);
        if (!$clinicAccess->fetchColumn()) {
            $msg = 'Choose a clinic assigned to your account.'; $msgType = 'danger';
        } elseif ($svcId) {
            $scopeSql = 'SELECT s.id FROM services s JOIN clinics c ON c.id=s.clinic_id WHERE s.id=?' . ($isSuperAdmin ? '' : ' AND c.admin_id=?') . ($isGroomerAdmin ? " AND c.clinic_type='grooming' AND s.service_type='grooming'" : '');
            $scope = $db->prepare($scopeSql);
            $scope->execute($isSuperAdmin ? [$svcId] : [$svcId, $user['id']]);
            if (!$scope->fetchColumn()) { $msg='This service is outside your assigned clinic.'; $msgType='danger'; }
            else {
                $db->prepare('UPDATE services SET name=?,description=?,service_type=?,duration_minutes=?,price=?,veterinarian_discount_details=?,groomer_discount_details=? WHERE id=?')
                   ->execute([$name,$desc,$type,$dur,$price,$veterinarianDiscount !== '' ? $veterinarianDiscount : null,$groomerDiscount !== '' ? $groomerDiscount : null,$svcId]);
                $msg="Service updated.";
            }
        } else {
            $db->prepare('INSERT INTO services (clinic_id,name,description,service_type,duration_minutes,price,veterinarian_discount_details,groomer_discount_details) VALUES (?,?,?,?,?,?,?,?)')
               ->execute([$clinicId,$name,$desc,$type,$dur,$price,$veterinarianDiscount !== '' ? $veterinarianDiscount : null,$groomerDiscount !== '' ? $groomerDiscount : null]);
            $msg="Service added.";
        }
        }
    }
}

if (isset($_GET['delete']) && verifyCsrfToken($_GET['csrf']??'')) {
    $deleteSql = 'UPDATE services SET is_active=0 WHERE id=?' . ($isSuperAdmin ? '' : ' AND clinic_id IN (SELECT id FROM clinics WHERE admin_id=?' . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '') . ')') . ($isGroomerAdmin ? " AND service_type='grooming'" : '');
    $deleteParams = [(int)$_GET['delete']];
    if (!$isSuperAdmin) $deleteParams[] = $user['id'];
    $db->prepare($deleteSql)->execute($deleteParams);
    header('Location: services.php?deleted=1'); exit;
}

$serviceSql = 'SELECT s.*,c.name AS clinic_name FROM services s JOIN clinics c ON s.clinic_id=c.id WHERE s.is_active=1' . ($isSuperAdmin ? '' : ' AND c.admin_id=?') . ($isGroomerAdmin ? " AND c.clinic_type='grooming' AND s.service_type='grooming'" : '') . ' ORDER BY c.name,s.name';
$serviceStmt = $db->prepare($serviceSql);
$serviceStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$services = $serviceStmt->fetchAll();
$editingId = (int)($_GET['edit'] ?? 0);
$editingService = null;
if ($editingId) {
    $editSql = 'SELECT s.* FROM services s JOIN clinics c ON c.id=s.clinic_id WHERE s.id=?' . ($isSuperAdmin ? '' : ' AND c.admin_id=?') . ($isGroomerAdmin ? " AND c.clinic_type='grooming' AND s.service_type='grooming'" : '');
    $editStmt = $db->prepare($editSql);
    $editStmt->execute($isSuperAdmin ? [$editingId] : [$editingId, $user['id']]);
    $editingService = $editStmt->fetch();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div><h1><?= $isGroomerAdmin ? 'Manage Grooming Services' : 'Manage Services' ?></h1><p><?= $isGroomerAdmin ? 'Add and manage grooming services offered by your center.' : 'Add and manage services offered by clinics.' ?></p></div>
      <button onclick="resetServiceForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Service</button>
    </div>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-check-circle"></i><?= $msg ?></div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success" data-auto-dismiss="3000"><i class="fas fa-check-circle"></i>Service removed.</div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <?php if ($services): ?>
      <table>
        <thead><tr><th>Service Name</th><th>Clinic</th><th>Type</th><th>Duration</th><th>Price</th><th>Discount</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
        <tr>
          <td><div style="font-weight:600;"><?= sanitize($s['name']) ?></div><?php if($s['description']): ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize(substr($s['description'],0,60)) ?></div><?php endif; ?></td>
          <td><?= sanitize($s['clinic_name']) ?></td>
          <td><span class="badge badge-<?= $s['service_type']==='veterinary'?'primary':($s['service_type']==='grooming'?'warning':'success') ?>"><?= ucfirst($s['service_type']) ?></span></td>
          <td><?= $s['duration_minutes'] ?> min</td>
          <td><?= $s['price']?'₱'.number_format($s['price'],2):'Free' ?></td>
          <td><?php $discounts = serviceDiscountsForService($s); if ($discounts): foreach ($discounts as $discount): ?><div><strong><?= sanitize($discount['label']) ?>:</strong> <?= sanitize($discount['details']) ?></div><?php endforeach; else: ?><span class="form-hint">—</span><?php endif; ?></td>
          <td>
            <div style="display:flex;gap:.4rem;">
              <a href="?edit=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline" aria-label="Edit <?= sanitize($s['name']) ?>"><i class="fas fa-pen"></i> Edit</a>
              <a href="?delete=<?= $s['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-danger" data-confirm="Remove this service from the clinic's public list?"><i class="fas fa-trash"></i> Remove</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty-state"><i class="fas fa-list"></i><h3>No services yet</h3></div><?php endif; ?>
    </div>
  </div>
</main>

<div class="modal-overlay" id="addServiceModal">
  <div class="modal">
    <div class="modal-header"><h3 id="serviceModalTitle"><?= $editingService ? 'Edit Service' : 'Add Service' ?></h3><button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="service_id" value="<?= (int)($editingService['id'] ?? 0) ?>">
        <?php if ($isSuperAdmin): ?><div class="form-group"><label class="form-label">Clinic <span class="required">*</span></label><select name="clinic_id" class="form-control" required><option value="">— Choose —</option><?php foreach ($clinics as $c): ?><option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="form-group"><label class="form-label">Service Name <span class="required">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Type</label><select name="service_type" class="form-control"><?php if ($isGroomerAdmin): ?><option value="grooming">Grooming</option><?php else: ?><option value="veterinary">Veterinary</option><option value="grooming">Grooming</option><option value="both">Both</option><?php endif; ?></select></div>
          <div class="form-group"><label class="form-label">Duration (min)</label><input type="number" name="duration_minutes" class="form-control" value="30" min="5"></div>
        </div>
        <div class="form-group"><label class="form-label">Price (₱)</label><input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0 = Free"></div>
        <?php if (!$isGroomerAdmin): ?><div class="form-group" id="veterinarianDiscountField"><label class="form-label" for="veterinarianDiscount">Veterinarian discount details</label><textarea id="veterinarianDiscount" name="veterinarian_discount_details" class="form-control" rows="2" maxlength="500"></textarea></div><?php endif; ?>
        <div class="form-group" id="groomerDiscountField"><label class="form-label" for="groomerDiscount">Groomer discount details</label><textarea id="groomerDiscount" name="groomer_discount_details" class="form-control" rows="2" maxlength="500"></textarea></div>
        <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;display:flex;gap:.75rem;justify-content:flex-end;">
          <button type="button" onclick="closeModal('addServiceModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Service</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
const serviceForm = document.querySelector('#addServiceModal form');
function updateDiscountFields() {
  const type = serviceForm.elements.namedItem('service_type').value;
  const veterinarianField = document.getElementById('veterinarianDiscountField');
  const groomerField = document.getElementById('groomerDiscountField');
  if (veterinarianField) {
    const input = veterinarianField.querySelector('textarea');
    const visible = type === 'veterinary' || type === 'both';
    veterinarianField.hidden = !visible;
    input.disabled = !visible;
  }
  const groomerInput = groomerField.querySelector('textarea');
  const groomerVisible = type === 'grooming' || type === 'both';
  groomerField.hidden = !groomerVisible;
  groomerInput.disabled = !groomerVisible;
}
serviceForm.elements.namedItem('service_type').addEventListener('change', updateDiscountFields);
function resetServiceForm() {
  serviceForm.reset();
  serviceForm.elements.namedItem('service_id').value = '0';
  document.getElementById('serviceModalTitle').textContent = 'Add Service';
  updateDiscountFields();
  openModal('addServiceModal');
}
const editingService = <?= json_encode($editingService ?: null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
if (editingService) {
  for (const field of ['service_id','clinic_id','name','description','service_type','duration_minutes','price','veterinarian_discount_details','groomer_discount_details']) {
    if (serviceForm.elements.namedItem(field)) serviceForm.elements.namedItem(field).value = editingService[field] ?? '';
  }
  updateDiscountFields();
  openModal('addServiceModal');
} else updateDiscountFields();
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
