<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'My Pets';
$isDashboard = true;
$db = getDB();
$msg = ''; $msgType = 'success';

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $petId   = (int)($_POST['pet_id'] ?? 0);
        $name    = sanitize($_POST['name'] ?? '');
        $species = sanitize($_POST['species'] ?? '');
        $breed   = sanitize($_POST['breed'] ?? '');
        $gender  = sanitize($_POST['gender'] ?? 'unknown');
        $ageY    = (int)($_POST['age_years'] ?? 0);
        $ageM    = (int)($_POST['age_months'] ?? 0);
        $weight  = (float)($_POST['weight_kg'] ?? 0);
        $notes   = sanitize($_POST['notes'] ?? '');
        $photo   = null;

        if (!$name) { $msg='Pet name is required.'; $msgType='danger'; }
        else {
            $uploadFailed = false;
            if (!empty($_FILES['photo']['name'])) {
                $photo = uploadFile($_FILES['photo'], 'pets');
                if ($photo === null) {
                    $uploadFailed = true;
                    $msg     = 'Photo upload failed. Make sure the file is a JPG/PNG/WEBP image under 5 MB, then try again.';
                    $msgType = 'danger';
                }
            }
            if (!$uploadFailed) {
                if ($petId) {
                    // Edit — verify ownership
                    $own = $db->prepare('SELECT id,photo FROM pets WHERE id=? AND owner_id=?');
                    $own->execute([$petId, $user['id']]);
                    $existing = $own->fetch();
                    if ($existing) {
                        $newPhoto = $photo ?? $existing['photo'];
                        // Delete old file from disk when a new photo replaces it
                        if ($photo && $existing['photo']) {
                            deleteUploadFile($existing['photo']);
                        }
                        $db->prepare('UPDATE pets SET name=?,species=?,breed=?,gender=?,age_years=?,age_months=?,weight_kg=?,notes=?,photo=? WHERE id=?')
                           ->execute([$name,$species,$breed,$gender,$ageY,$ageM,$weight,$notes,$newPhoto,$petId]);
                        $msg = "Pet profile updated successfully.";
                    }
                } else {
                    $db->prepare('INSERT INTO pets (owner_id,name,species,breed,gender,age_years,age_months,weight_kg,notes,photo) VALUES (?,?,?,?,?,?,?,?,?,?)')
                       ->execute([$user['id'],$name,$species,$breed,$gender,$ageY,$ageM,$weight,$notes,$photo]);
                    $msg = "Pet added successfully!";
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if (verifyCsrfToken($_GET['csrf'] ?? '')) {
        // Delete photo file from disk before soft-deleting the record
        $dp = $db->prepare('SELECT photo FROM pets WHERE id=? AND owner_id=? AND is_active=1');
        $dp->execute([$delId, $user['id']]);
        $delPet = $dp->fetch();
        if ($delPet) {
            deleteUploadFile($delPet['photo']);
        }
        $db->prepare('UPDATE pets SET is_active=0 WHERE id=? AND owner_id=?')->execute([$delId, $user['id']]);
        header('Location: pets.php?deleted=1'); exit;
    }
}

$pets = $db->prepare('SELECT * FROM pets WHERE owner_id=? AND is_active=1 ORDER BY name');
$pets->execute([$user['id']]); $pets = $pets->fetchAll();

// Edit mode
$editPet = null;
if (isset($_GET['edit'])) {
    $es = $db->prepare('SELECT * FROM pets WHERE id=? AND owner_id=?');
    $es->execute([(int)$_GET['edit'], $user['id']]);
    $editPet = $es->fetch();
}
$showForm = isset($_GET['action']) && $_GET['action']==='add' || $editPet;
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>My Pets</span></div>
    <div class="dash-page-header">
      <div>
        <h1>My Pets</h1>
        <p>Manage your pet profiles and health information.</p>
      </div>
      <a href="?action=add" class="btn btn-primary btn-add-pet"><i class="fas fa-plus"></i> Add Pet</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i><?= $msg ?></div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success" data-auto-dismiss="3000"><i class="fas fa-check-circle"></i> Pet removed.</div><?php endif; ?>

  <?php if ($showForm): ?>
  <!-- Add/Edit Form -->
  <div class="card mb-3">
    <div class="card-header">
      <h3><?= $editPet ? 'Edit' : 'Add New' ?> Pet</h3>
      <a href="pets.php" class="btn btn-sm btn-outline">Cancel</a>
    </div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <?php if ($editPet): ?><input type="hidden" name="pet_id" value="<?= $editPet['id'] ?>"><?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Pet Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Buddy" value="<?= sanitize($editPet['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Species</label>
            <select name="species" class="form-control">
              <option value="">Select species</option>
              <?php foreach (['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'] as $sp): ?>
              <option value="<?= strtolower($sp) ?>" <?= strtolower($editPet['species'] ?? '')===strtolower($sp)?'selected':'' ?>><?= $sp ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Breed</label>
            <input type="text" name="breed" class="form-control" placeholder="e.g. Golden Retriever" value="<?= sanitize($editPet['breed'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
              <option value="male"    <?= ($editPet['gender']??'')=='male'   ?'selected':'' ?>>Male</option>
              <option value="female"  <?= ($editPet['gender']??'')=='female' ?'selected':'' ?>>Female</option>
              <option value="unknown" <?= ($editPet['gender']??'')=='unknown'?'selected':'' ?>>Unknown</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Age (Years)</label>
            <input type="number" name="age_years" class="form-control" min="0" max="30" value="<?= (int)($editPet['age_years'] ?? 0) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Age (Months)</label>
            <input type="number" name="age_months" class="form-control" min="0" max="11" value="<?= (int)($editPet['age_months'] ?? 0) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Weight (kg)</label>
            <input type="number" name="weight_kg" class="form-control" step="0.1" min="0" value="<?= (float)($editPet['weight_kg'] ?? 0) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Photo</label>
            <div class="upload-area" id="petPhotoUpload">
              <?php $editPhotoUrl = assetUrl($editPet['photo'] ?? null); ?>
              <?php if ($editPet && $editPhotoUrl): ?>
              <img src="<?= sanitize($editPhotoUrl) ?>" class="upload-preview" id="photoPreview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:.5rem;" alt="">
              <?php else: ?>
              <img class="upload-preview" id="photoPreview" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:.5rem;" alt="">
              <?php endif; ?>
              <input type="file" name="photo" id="photoInput" accept="image/*" class="form-control">
              <div id="photoFilename" style="font-size:.85rem;color:var(--text-muted);margin-top:.5rem;display:none;">
                <i class="fas fa-image"></i> <span id="filenameText"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" placeholder="Any special notes, allergies, or conditions…" rows="3"><?= sanitize($editPet['notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editPet ? 'Update Pet' : 'Add Pet' ?></button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pets Grid -->
  <?php if ($pets): ?>
  <div class="pets-grid">
    <?php foreach ($pets as $pet): echo renderPetCard($pet, true); endforeach; ?>
  </div>
  <?php else: ?>
  <?= renderEmptyState('fa-dog', 'No pets added yet', 'Add your first pet to start booking appointments and tracking health.', 'Add My First Pet', '?action=add') ?>
  <?php endif; ?>
</main>

<script>
// Pet photo preview and filename display
document.getElementById('photoInput')?.addEventListener('change', function(e) {
  const file = e.target.files[0];
  const preview = document.getElementById('photoPreview');
  const filenameDiv = document.getElementById('photoFilename');
  const filenameText = document.getElementById('filenameText');
  
  if (file) {
    // Show filename
    filenameText.textContent = file.name;
    filenameDiv.style.display = 'block';
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
