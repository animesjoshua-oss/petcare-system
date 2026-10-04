<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'Medical Records';
$isDashboard = true;
$db = getDB();
$recordTypeColumn=$db->query("SHOW COLUMNS FROM pet_health_records LIKE 'record_type'")->fetch(PDO::FETCH_ASSOC);
if ($recordTypeColumn && str_starts_with(strtolower($recordTypeColumn['Type']), 'enum(')) $db->exec("ALTER TABLE pet_health_records MODIFY record_type VARCHAR(100) NOT NULL DEFAULT 'checkup'");

$vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;
$vetClinicId = (int)($vet['clinic_id'] ?? 0);

$prePet  = (int)($_GET['pet']  ?? 0);
$preAppt = (int)($_GET['appt'] ?? 0);
$msg = '';
$msgType = 'success';
$aiSummaryText = '';
$aiSummaryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_summary'])) {
    $summaryPetId = (int)($_POST['pet_id'] ?? 0);
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $aiSummaryError = 'Your session could not verify this request. Refresh the page and try again.';
    } else {
        $access = $db->prepare('SELECT p.* FROM pets p WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?)) LIMIT 1');
        $access->execute([$summaryPetId,$vetId,$vetClinicId,$vetId]);
        $summaryPet = $access->fetch();
        if (!$summaryPet) {
            $aiSummaryError = 'This pet is not assigned to your patient list.';
        } else {
            $historyStmt = $db->prepare('SELECT record_type,title,description,diagnosis,treatment,medications,record_date,next_appointment FROM pet_health_records WHERE pet_id=? ORDER BY record_date DESC');
            $historyStmt->execute([$summaryPetId]);
            $history = $historyStmt->fetchAll();
            $historyText = $history ? implode("\n", array_map(static function ($record) {
                return implode(' | ', array_filter([
                    $record['record_date'] . ' ' . $record['record_type'],
                    $record['title'], $record['description'], $record['diagnosis'],
                    $record['treatment'], $record['medications'],
                    $record['next_appointment'] ? 'Next appointment: ' . $record['next_appointment'] : '',
                ]));
            }, $history)) : 'No medical records are available yet.';
            $prompt = "Summarize this patient's documented veterinary history for the attending veterinarian in a concise, neutral paragraph. Include only facts present in the records. Do not add a diagnosis or treatment recommendation, and state when there is no documented history. This is a chart summary, not a diagnosis.\n\nPatient: {$summaryPet['name']}\nSpecies: {$summaryPet['species']}\nBreed: {$summaryPet['breed']}\nAge: {$summaryPet['age_years']} years {$summaryPet['age_months']} months\nWeight: {$summaryPet['weight_kg']} kg\nMedical records:\n{$historyText}";
            $ai = callGroq($prompt, 350);
            if ($ai['success']) {
                $aiSummaryText = trim($ai['text']);
                $db->prepare('INSERT INTO ai_summaries (pet_id,generated_by,summary,prompt_used) VALUES (?,?,?,?)')
                    ->execute([$summaryPetId, $user['id'], $aiSummaryText, $prompt]);
                $prePet = $summaryPetId;
            } else {
                $aiSummaryError = $ai['text'];
            }
        }
    }
}

// Add record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $petId   = (int)$_POST['pet_id'];
        $type    = sanitize($_POST['record_type']);
        $title   = sanitize($_POST['title']);
        $desc    = sanitize($_POST['description'] ?? '');
        $diag    = sanitize($_POST['diagnosis'] ?? '');
        $treat   = sanitize($_POST['treatment'] ?? '');
        $meds    = sanitize($_POST['medications'] ?? '');
        $nextAppt= sanitize($_POST['next_appointment'] ?? '') ?: null;
        $recDate = sanitize($_POST['record_date']);

        $access = $db->prepare('SELECT COUNT(*) FROM pets p WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?))');
        $access->execute([$petId,$vetId,$vetClinicId,$vetId]);
        if (!(int)$access->fetchColumn()) {
            $msg = 'This pet is not assigned to your patient list.';
            $msgType = 'danger';
        } else {
            $db->prepare('INSERT INTO pet_health_records (pet_id,vet_id,record_type,title,description,diagnosis,treatment,medications,next_appointment,record_date) VALUES (?,?,?,?,?,?,?,?,?,?)')
               ->execute([$petId,$vetId,$type,$title,$desc,$diag,$treat,$meds,$nextAppt,$recDate]);

            if ($preAppt) {
                $db->prepare("UPDATE appointments SET status='completed',completed_at=NOW() WHERE id=? AND vet_id=? AND pet_id=?")
                    ->execute([$preAppt, $vetId, $petId]);
            }
            $msg = 'Health record saved successfully.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_record']) || isset($_POST['delete_record']))) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $petId = (int)($_POST['pet_id'] ?? 0);
        $access = $db->prepare('SELECT hr.id FROM pet_health_records hr JOIN pets p ON p.id=hr.pet_id WHERE hr.id=? AND hr.pet_id=? AND p.is_active=1 AND (hr.vet_id=? OR EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?))) LIMIT 1');
        $access->execute([$recordId,$petId,$vetId,$vetId,$vetClinicId]);
        if (!$access->fetchColumn()) { $msg='Record not found in your patient list.'; $msgType='danger'; }
        elseif (isset($_POST['delete_record'])) { $db->prepare('DELETE FROM pet_health_records WHERE id=?')->execute([$recordId]); $msg='Health record deleted.'; }
        else {
            $type = sanitize($_POST['record_type'] ?? 'other');
            if ($type === '' || strlen($type)>100 || trim($_POST['title'] ?? '') === '' || empty($_POST['record_date'])) { $msg='Record type, title, and date are required.'; $msgType='danger'; }
            else {
                $db->prepare('UPDATE pet_health_records SET record_type=?,title=?,description=?,diagnosis=?,treatment=?,medications=?,next_appointment=?,record_date=? WHERE id=?')->execute([$type,sanitize($_POST['title']),sanitize($_POST['description'] ?? ''),sanitize($_POST['diagnosis'] ?? ''),sanitize($_POST['treatment'] ?? ''),sanitize($_POST['medications'] ?? ''),sanitize($_POST['next_appointment'] ?? '') ?: null,sanitize($_POST['record_date']),$recordId]);
                $msg='Health record updated.';
            }
        }
        $prePet=$petId;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_walkin_record'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $ownerId=(int)($_POST['owner_id'] ?? 0); $petId=(int)($_POST['walkin_pet_id'] ?? 0);
        $type=trim(sanitize($_POST['walkin_record_type'] ?? '')); $title=$type; $date=sanitize($_POST['walkin_record_date'] ?? '');
        $ownerCheck=$db->prepare("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND r.name='pet_owner' AND u.is_active=1"); $ownerCheck->execute([$ownerId]);
        if (!$ownerCheck->fetchColumn()) { $msg='Choose an active pet owner.'; $msgType='danger'; }
        elseif ($type==='' || strlen($type)>100 || !$date || (!$petId && trim($_POST['new_pet_name'] ?? '')==='')) { $msg='Choose an existing pet or enter a new pet name, and complete the record type and date.'; $msgType='danger'; }
        else {
            if ($petId) { $petCheck=$db->prepare('SELECT id FROM pets WHERE id=? AND owner_id=? AND is_active=1'); $petCheck->execute([$petId,$ownerId]); }
            else {
                $db->prepare('INSERT INTO pets (owner_id,name,species,breed,gender,age_years,age_months,weight_kg) VALUES (?,?,?,?,?,?,?,?)')->execute([$ownerId,sanitize($_POST['new_pet_name']),sanitize($_POST['new_species'] ?? 'Dog'),sanitize($_POST['new_breed'] ?? ''),in_array($_POST['new_gender'] ?? '',['male','female','unknown'],true)?$_POST['new_gender']:'unknown',(int)($_POST['new_age_years'] ?? 0),(int)($_POST['new_age_months'] ?? 0),(float)($_POST['new_weight'] ?? 0) ?: null]);
                $petId=(int)$db->lastInsertId(); $petCheck=$db->prepare('SELECT id FROM pets WHERE id=? AND owner_id=?'); $petCheck->execute([$petId,$ownerId]);
            }
            if (!$petCheck->fetchColumn()) { $msg='That pet does not belong to the selected owner.'; $msgType='danger'; }
            else {
                $db->prepare('INSERT INTO pet_health_records (pet_id,vet_id,record_type,title,description,diagnosis,treatment,medications,next_appointment,record_date) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$petId,$vetId,$type,sanitize($title),sanitize($_POST['walkin_notes'] ?? ''),sanitize($_POST['walkin_diagnosis'] ?? ''),sanitize($_POST['walkin_treatment'] ?? ''),null,sanitize($_POST['walkin_next_appointment'] ?? '') ?: null,$date]);
                $prePet=$petId; $msg='Walk-in patient medical record saved.';
            }
        }
    }
}

// Pet list (vet's patients)
$patientPets = $db->prepare('SELECT DISTINCT p.* FROM pets p WHERE p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?)) ORDER BY p.name');
$patientPets->execute([$vetId,$vetClinicId,$vetId]);
$patientPets = $patientPets->fetchAll();
$petOwners=$db->query("SELECT u.id,u.first_name,u.last_name,u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='pet_owner' AND u.is_active=1 ORDER BY u.last_name,u.first_name")->fetchAll();
$ownerPets=$db->query('SELECT p.id,p.owner_id,p.name,p.species,p.breed FROM pets p WHERE p.is_active=1 ORDER BY p.name')->fetchAll();

// If a pet is selected, load its records
$selectedPet = null; $petRecords = [];
if ($prePet) {
    $ps = $db->prepare('SELECT p.*,CONCAT(u.first_name," ",u.last_name) AS owner_name FROM pets p JOIN users u ON p.owner_id=u.id WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?))');
    $ps->execute([$prePet,$vetId,$vetClinicId,$vetId]); $selectedPet = $ps->fetch();
    if ($selectedPet) {
        $rs = $db->prepare('SELECT hr.*,CONCAT(u.first_name," ",u.last_name) AS vet_name FROM pet_health_records hr LEFT JOIN veterinarians v ON hr.vet_id=v.id LEFT JOIN users u ON v.user_id=u.id WHERE hr.pet_id=? ORDER BY hr.record_date DESC');
        $rs->execute([$prePet]); $petRecords = $rs->fetchAll();
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;"><div><h1>Medical Records</h1><p>Manage appointment patients and digitize records for walk-in visits.</p></div><button type="button" class="btn btn-primary" onclick="openModal('walkinRecordModal')"><i class="fas fa-user-plus"></i> Add Walk-in Record</button></div>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000" role="status"><i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i><?= sanitize($msg) ?></div><?php endif; ?>
  <?php if ($aiSummaryError): ?><div class="alert alert-danger" role="alert"><?= sanitize($aiSummaryError) ?></div><?php endif; ?>
  <?php if ($aiSummaryText): ?><section class="card" aria-labelledby="aiSummaryTitle" style="margin-bottom:1rem;"><div class="card-body"><h2 id="aiSummaryTitle" style="font-size:1rem;margin-bottom:.5rem;">AI chart summary</h2><p style="line-height:1.7;"><?= nl2br(sanitize($aiSummaryText)) ?></p><small style="color:var(--text-muted);">AI-generated summary. Review it against the medical record before relying on it.</small></div></section><?php endif; ?>

  <div class="dash-two-col" style="display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start;">
    <!-- Patient List -->
    <div class="card"><div class="card-header"><h3 style="font-size:.95rem;">Patients</h3></div>
      <div style="padding:.5rem 0;">
        <?php if ($patientPets): foreach ($patientPets as $p): ?>
        <a href="records.php?pet=<?= $p['id'] ?>" class="sidebar-link <?= $prePet==$p['id']?'active':'' ?>">
          <i class="fas fa-<?= $p['species']==='cat'?'cat':'dog' ?>"></i>
          <div><div style="font-weight:600;font-size:.875rem;"><?= sanitize($p['name']) ?></div><div style="font-size:.75rem;color:var(--text-muted)"><?= sanitize($p['breed']?:$p['species']) ?></div></div>
        </a>
        <?php endforeach; else: ?>
        <p style="padding:1rem;font-size:.875rem;color:var(--text-muted)">No patients yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Records Panel -->
    <div>
      <?php if ($selectedPet): ?>
      <!-- Pet Info -->
      <div class="card mb-3">
        <div class="card-body" style="display:flex;align-items:center;gap:1.25rem;">
          <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;">🐾</div>
          <div>
            <h3 style="font-size:1.1rem;font-weight:700;"><?= sanitize($selectedPet['name']) ?></h3>
            <p style="font-size:.875rem;color:var(--text-muted)"><?= sanitize($selectedPet['breed']?:$selectedPet['species']) ?> · <?= sanitize($selectedPet['gender']) ?> · <?= $selectedPet['age_years'] ?>y · <?= $selectedPet['weight_kg'] ?>kg · Owner: <?= sanitize($selectedPet['owner_name']) ?></p>
          </div>
          <div style="margin-left:auto;display:flex;gap:.5rem;">
            <button onclick="openModal('addRecordModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Record</button>
            <form method="POST" action="records.php?pet=<?= (int)$selectedPet['id'] ?>">
              <?= csrfField() ?>
              <input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>">
              <button type="submit" name="generate_summary" value="1" class="btn btn-secondary"><i class="fas fa-robot"></i> AI Summary</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Records Table -->
      <div class="card">
        <div class="card-header"><h3>Health Records (<?= count($petRecords) ?>)</h3></div>
        <div class="table-wrap">
          <?php if ($petRecords): ?>
          <table>
            <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Diagnosis</th><th>Treatment</th><th>Next Visit</th><th>Manage</th></tr></thead>
            <tbody>
            <?php foreach ($petRecords as $r): ?>
            <tr>
              <td><?= date('M j, Y',strtotime($r['record_date'])) ?></td>
              <td><span class="badge badge-info"><?= ucfirst($r['record_type']) ?></span></td>
              <td><div style="font-weight:600;"><?= sanitize($r['title']) ?></div><?php if($r['description']): ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize(substr($r['description'],0,60)) ?>…</div><?php endif; ?></td>
              <td style="font-size:.85rem;"><?= sanitize($r['diagnosis']??'—') ?></td>
              <td style="font-size:.85rem;"><?= sanitize($r['treatment']??'—') ?></td>
              <td><?= $r['next_appointment']?date('M j, Y',strtotime($r['next_appointment'])):'—' ?></td>
              <td><details><summary class="btn btn-sm btn-outline">Edit</summary><form method="POST" style="min-width:220px;padding:.5rem;"><?= csrfField() ?><input type="hidden" name="update_record" value="1"><input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>"><input type="hidden" name="record_id" value="<?= (int)$r['id'] ?>"><input name="record_type" class="form-control" value="<?= sanitize($r['record_type']) ?>" required maxlength="100"><input type="date" name="record_date" class="form-control" value="<?= sanitize($r['record_date']) ?>" required><input name="title" class="form-control" value="<?= sanitize($r['title']) ?>" required placeholder="Title"><textarea name="description" class="form-control" placeholder="Description"><?= sanitize($r['description'] ?? '') ?></textarea><textarea name="diagnosis" class="form-control" placeholder="Diagnosis"><?= sanitize($r['diagnosis'] ?? '') ?></textarea><textarea name="treatment" class="form-control" placeholder="Treatment"><?= sanitize($r['treatment'] ?? '') ?></textarea><textarea name="medications" class="form-control" placeholder="Medications"><?= sanitize($r['medications'] ?? '') ?></textarea><label>Next appointment</label><input type="date" name="next_appointment" class="form-control" value="<?= sanitize($r['next_appointment'] ?? '') ?>"><button class="btn btn-primary btn-sm" type="submit">Save</button></form><form method="POST" onsubmit="return confirm('Delete this medical record?')"><?= csrfField() ?><input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>"><input type="hidden" name="record_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" name="delete_record" value="1">Delete</button></form></details></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="empty-state"><i class="fas fa-file-medical"></i><h3>No records yet</h3></div>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card"><div class="card-body"><div class="empty-state"><i class="fas fa-hand-point-left"></i><h3>Select a patient</h3><p>Choose a patient from the list to view or add health records.</p></div></div></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add Record Modal -->
  <?php if ($selectedPet): ?>
  <div class="modal-overlay" id="addRecordModal">
    <div class="modal" style="max-width:620px;">
      <div class="modal-header"><h3>Add Health Record – <?= sanitize($selectedPet['name']) ?></h3><button class="modal-close" onclick="closeModal('addRecordModal')"><i class="fas fa-times"></i></button></div>
      <div class="modal-body">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="add_record" value="1">
          <input type="hidden" name="pet_id" value="<?= $selectedPet['id'] ?>">
          <div class="form-row"><div class="form-group"><label class="form-label">Pet</label><input class="form-control" value="<?= sanitize($selectedPet['name']) ?>" readonly></div><div class="form-group"><label class="form-label">Owner</label><input class="form-control" value="<?= sanitize($selectedPet['owner_name']) ?>" readonly></div></div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Record Type <span class="required">*</span></label>
              <select name="record_type" class="form-control" required>
                <?php foreach(['vaccination','diagnosis','treatment','checkup','surgery','other'] as $rt): ?><option value="<?= $rt ?>"><?= ucfirst($rt) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Record Date <span class="required">*</span></label>
              <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Title <span class="required">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Annual vaccination, Skin allergy checkup" required>
          </div>
          <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Detailed notes…"></textarea></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Diagnosis</label><input type="text" name="diagnosis" class="form-control" placeholder="e.g. Dermatitis"></div>
            <div class="form-group"><label class="form-label">Treatment</label><input type="text" name="treatment" class="form-control" placeholder="e.g. Topical cream, antibiotics"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Medications</label><input type="text" name="medications" class="form-control" placeholder="Medication names, dosages"></div>
            <div class="form-group"><label class="form-label">Next Appointment</label><input type="date" name="next_appointment" class="form-control" min="<?= date('Y-m-d') ?>"></div>
          </div>
          <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;">
            <button type="button" onclick="closeModal('addRecordModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <div class="modal-overlay" id="walkinRecordModal"><div class="modal" style="max-width:720px;"><div class="modal-header"><h3>New Walk-in Medical Record</h3><button class="modal-close" onclick="closeModal('walkinRecordModal')"><i class="fas fa-times"></i></button></div><div class="modal-body"><p style="color:var(--text-muted);margin-bottom:1rem;">For an in-person visit without an online appointment. Select the owner and an existing pet, or enter a new pet profile.</p><form method="POST"><?= csrfField() ?><input type="hidden" name="add_walkin_record" value="1"><div class="form-group"><label class="form-label">Pet Owner</label><select name="owner_id" id="walkinOwner" class="form-control" required onchange="filterWalkinPets()"><option value="">Choose owner</option><?php foreach($petOwners as $owner): ?><option value="<?= (int)$owner['id'] ?>"><?= sanitize($owner['first_name'].' '.$owner['last_name'].' ('.$owner['email'].')') ?></option><?php endforeach; ?></select></div><div class="form-group"><label class="form-label">Existing pet</label><select name="walkin_pet_id" id="walkinPet" class="form-control" onchange="toggleNewWalkinPet()"><option value="">— Create a new pet profile —</option><?php foreach($ownerPets as $op): ?><option value="<?= (int)$op['id'] ?>" data-owner="<?= (int)$op['owner_id'] ?>" hidden><?= sanitize($op['name'].' — '.($op['breed'] ?: $op['species'])) ?></option><?php endforeach; ?></select></div><div id="newWalkinPetFields" class="card" style="padding:1rem;margin-bottom:1rem;"><h4 style="margin-bottom:.75rem;">New pet profile</h4><div class="form-row"><div class="form-group"><label class="form-label">Pet name</label><input name="new_pet_name" id="newWalkinName" class="form-control"></div><div class="form-group"><label class="form-label">Species</label><input name="new_species" class="form-control" value="Dog"></div><div class="form-group"><label class="form-label">Breed</label><input name="new_breed" class="form-control"></div></div><div class="form-row"><div class="form-group"><label class="form-label">Gender</label><select name="new_gender" class="form-control"><option value="unknown">Unknown</option><option value="female">Female</option><option value="male">Male</option></select></div><div class="form-group"><label class="form-label">Age (years)</label><input type="number" min="0" name="new_age_years" class="form-control"></div><div class="form-group"><label class="form-label">Age (months)</label><input type="number" min="0" max="11" name="new_age_months" class="form-control"></div><div class="form-group"><label class="form-label">Weight (kg)</label><input type="number" min="0" step="0.01" name="new_weight" class="form-control"></div></div></div><div class="form-row"><div class="form-group"><label class="form-label">Record type</label><input type="text" name="walkin_record_type" class="form-control" placeholder="e.g. Vaccination, wellness exam" maxlength="100" required></div><div class="form-group"><label class="form-label">Record date</label><input type="date" name="walkin_record_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div></div><div class="form-row"><div class="form-group"><label class="form-label">Notes</label><textarea name="walkin_notes" class="form-control"></textarea></div><div class="form-group"><label class="form-label">Diagnosis</label><textarea name="walkin_diagnosis" class="form-control"></textarea></div></div><div class="form-row"><div class="form-group"><label class="form-label">Treatment</label><textarea name="walkin_treatment" class="form-control"></textarea></div></div><div class="form-group"><label class="form-label">Next appointment</label><input type="date" name="walkin_next_appointment" class="form-control"></div><div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('walkinRecordModal')">Cancel</button><button class="btn btn-primary">Save Walk-in Record</button></div></form></div></div></div>
</main>
<script>
function filterWalkinPets(){const owner=document.getElementById('walkinOwner').value;const select=document.getElementById('walkinPet');let found=false;Array.from(select.options).forEach((option,index)=>{if(index===0)return;const show=owner!==''&&option.dataset.owner===owner;option.hidden=!show;if(!show&&option.selected)select.value='';if(show)found=true;});toggleNewWalkinPet();}
function toggleNewWalkinPet(){const isNew=!document.getElementById('walkinPet').value;document.getElementById('newWalkinPetFields').style.display=isNew?'block':'none';document.getElementById('newWalkinName').required=isNew;}
<?php if ($preAppt && $selectedPet): ?>openModal('addRecordModal');<?php endif; ?>
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
