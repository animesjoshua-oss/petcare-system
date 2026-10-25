<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'Medical Records';
$isDashboard = true;
$db = getDB();
$recordTypeColumn=$db->query("SHOW COLUMNS FROM pet_health_records LIKE 'record_type'")->fetch(PDO::FETCH_ASSOC);
if ($recordTypeColumn && str_starts_with(strtolower($recordTypeColumn['Type']), 'enum(')) $db->exec("ALTER TABLE pet_health_records MODIFY record_type VARCHAR(100) NOT NULL DEFAULT 'checkup'");
foreach (['test_performed'=>'TEXT NULL AFTER diagnosis','test_result'=>'TEXT NULL AFTER test_performed','comments'=>'TEXT NULL AFTER medications'] as $column=>$definition) {
    if (!$db->query("SHOW COLUMNS FROM pet_health_records LIKE '{$column}'")->fetch(PDO::FETCH_ASSOC)) $db->exec("ALTER TABLE pet_health_records ADD COLUMN {$column} {$definition}");
}

$vetStmt = $db->prepare('SELECT * FROM veterinarians WHERE user_id=?');
$vetStmt->execute([$user['id']]); $vet = $vetStmt->fetch();
$vetId = $vet['id'] ?? 0;
$vetClinicId = (int)($vet['clinic_id'] ?? 0);

$prePet  = (int)($_GET['pet']  ?? 0);
$preAppt = (int)($_GET['appt'] ?? 0);
$appointmentPrefill = null;
if ($preAppt) {
    $appointmentQuery=$db->prepare('SELECT a.id,a.pet_id,a.appointment_date,a.appointment_type,s.name AS service_name FROM appointments a LEFT JOIN services s ON s.id=a.service_id WHERE a.id=? AND a.pet_id=? AND (a.vet_id=? OR a.clinic_id=?) LIMIT 1');
    $appointmentQuery->execute([$preAppt,$prePet,$vetId,$vetClinicId]);
    $appointmentPrefill=$appointmentQuery->fetch() ?: null;
    if (!$appointmentPrefill) $preAppt=0;
}
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
            $historyStmt = $db->prepare('SELECT hr.record_type,hr.title,hr.description,hr.diagnosis,hr.test_performed,hr.test_result,hr.treatment,hr.medications,hr.comments,hr.record_date,hr.next_appointment FROM pet_health_records hr JOIN veterinarians v ON v.id=hr.vet_id WHERE hr.pet_id=? AND v.clinic_id=? ORDER BY hr.record_date DESC');
            $historyStmt->execute([$summaryPetId,$vetClinicId]);
            $history = $historyStmt->fetchAll();
            $historyText = $history ? implode("\n", array_map(static function ($record) {
                return implode(' | ', array_filter([
                    $record['record_date'] . ' ' . $record['record_type'],
                    $record['title'], $record['description'], $record['diagnosis'],
                    $record['test_performed'], $record['test_result'], $record['treatment'], $record['medications'], $record['comments'],
                    $record['next_appointment'] ? 'Next appointment: ' . $record['next_appointment'] : '',
                ]));
            }, $history)) : 'No medical records are available yet.';
            $prompt = "Summarize this patient's documented veterinary history for the attending veterinarian in a concise, neutral paragraph. Include only facts present in the records. Do not add a diagnosis or treatment recommendation, and state when there is no documented history. This is a chart summary, not a diagnosis.\n\nPatient: {$summaryPet['name']}\nSpecies: {$summaryPet['species']}\nBreed: {$summaryPet['breed']}\nAge: {$summaryPet['age_years']} years {$summaryPet['age_months']} months\nWeight: {$summaryPet['weight_kg']} kg\nMedical records:\n{$historyText}";
            $ai = callGroq($prompt, 350);
            if ($ai['success']) {
                $aiSummaryText = trim($ai['text']);
                $prePet = $summaryPetId;
            } else {
                $aiSummaryError = $ai['text'];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pet_note'])) {
    $petId=(int)($_POST['pet_id'] ?? 0); $note=trim($_POST['pet_note'] ?? '');
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    elseif (mb_strlen($note)>4000) { $msg='Pet note must be 4000 characters or less.'; $msgType='danger'; }
    else {
        $access=$db->prepare('SELECT p.id FROM pets p WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?)) LIMIT 1');
        $access->execute([$petId,$vetId,$vetClinicId,$vetId]);
        if (!$access->fetchColumn()) { $msg='This pet is not assigned to your patient list.'; $msgType='danger'; }
        else { $db->prepare('UPDATE pets SET notes=? WHERE id=?')->execute([$note ?: null,$petId]); $prePet=$petId; $msg='Pet note saved.'; }
    }
}

// Add record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $petId   = (int)$_POST['pet_id'];
        $type    = 'checkup';
        $title   = sanitize($_POST['title']);
        $desc    = sanitize($_POST['description'] ?? '');
        $diag    = sanitize($_POST['diagnosis'] ?? '');
        $testPerformed = sanitize($_POST['test_performed'] ?? '');
        $testResult = sanitize($_POST['test_result'] ?? '');
        $treat   = sanitize($_POST['treatment'] ?? '');
        $meds    = sanitize($_POST['medications'] ?? '');
        $comments = sanitize($_POST['comments'] ?? '');
        $nextAppt= sanitize($_POST['next_appointment'] ?? '') ?: null;
        $recDate = sanitize($_POST['record_date']);

        $access = $db->prepare('SELECT COUNT(*) FROM pets p WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?))');
        $access->execute([$petId,$vetId,$vetClinicId,$vetId]);
        if (!(int)$access->fetchColumn()) {
            $msg = 'This pet is not assigned to your patient list.';
            $msgType = 'danger';
        } else {
            $db->prepare('INSERT INTO pet_health_records (pet_id,vet_id,record_type,title,description,diagnosis,test_performed,test_result,treatment,medications,comments,next_appointment,record_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$petId,$vetId,$type,$title,$desc,$diag,$testPerformed,$testResult,$treat,$meds,$comments,$nextAppt,$recDate]);

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
        $access = $db->prepare('SELECT hr.id FROM pet_health_records hr JOIN pets p ON p.id=hr.pet_id JOIN veterinarians record_vet ON record_vet.id=hr.vet_id WHERE hr.id=? AND hr.pet_id=? AND p.is_active=1 AND record_vet.clinic_id=? LIMIT 1');
        $access->execute([$recordId,$petId,$vetClinicId]);
        if (!$access->fetchColumn()) { $msg='Record not found in your patient list.'; $msgType='danger'; }
        elseif (isset($_POST['delete_record'])) { $db->prepare('DELETE FROM pet_health_records WHERE id=?')->execute([$recordId]); $msg='Health record deleted.'; }
        else {
            $type = 'checkup';
            if (trim($_POST['title'] ?? '') === '' || empty($_POST['record_date'])) { $msg='Record title and date are required.'; $msgType='danger'; }
            else {
                $db->prepare('UPDATE pet_health_records SET title=?,description=?,diagnosis=?,test_performed=?,test_result=?,treatment=?,medications=?,comments=?,record_date=? WHERE id=?')->execute([sanitize($_POST['title']),sanitize($_POST['description'] ?? ''),sanitize($_POST['diagnosis'] ?? ''),sanitize($_POST['test_performed'] ?? ''),sanitize($_POST['test_result'] ?? ''),sanitize($_POST['treatment'] ?? ''),sanitize($_POST['medications'] ?? ''),sanitize($_POST['comments'] ?? ''),sanitize($_POST['record_date']),$recordId]);
                $msg='Health record updated.';
            }
        }
        $prePet=$petId;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_walkin_record'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $ownerName=trim($_POST['owner_name'] ?? ''); $ownerEmail=strtolower(trim($_POST['owner_email'] ?? '')); $ownerPhone=trim($_POST['owner_phone'] ?? '');
        $type='checkup'; $title=trim($_POST['walkin_title'] ?? ''); $date=sanitize($_POST['walkin_record_date'] ?? '');
        if (!$vetId || !$vetClinicId) { $msg='Your veterinarian account is not assigned to an active clinic.'; $msgType='danger'; }
        elseif ($ownerName==='' || trim($_POST['new_pet_name'] ?? '')==='' || $title==='' || strlen($title)>255 || !$date) { $msg='Enter the pet owner name, pet name, record title, and date.'; $msgType='danger'; }
        elseif ($ownerEmail!=='' && !filter_var($ownerEmail,FILTER_VALIDATE_EMAIL)) { $msg='Enter a valid email address or leave it blank.'; $msgType='danger'; }
        else {
            $ownerId=0; $emailTaken=false;
            if ($ownerEmail!=='') { $emailCheck=$db->prepare("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE LOWER(u.email)=? AND r.name='pet_owner' AND u.is_active=1"); $emailCheck->execute([$ownerEmail]); $ownerId=(int)$emailCheck->fetchColumn(); if (!$ownerId) { $takenCheck=$db->prepare('SELECT 1 FROM users WHERE LOWER(email)=?'); $takenCheck->execute([$ownerEmail]); $emailTaken=(bool)$takenCheck->fetchColumn(); } }
            if ($emailTaken) { $msg='That email is already used by a different account. Leave it blank or use the owner’s pet-owner account email.'; $msgType='danger'; }
            else {
                if (!$ownerId) {
                    $nameParts=preg_split('/\s+/', $ownerName, 2); $firstName=sanitize($nameParts[0] ?? 'Walk-in'); $lastName=sanitize($nameParts[1] ?? '');
                    $ownerRole=(int)$db->query("SELECT id FROM roles WHERE name='pet_owner' LIMIT 1")->fetchColumn();
                    if (!$ownerRole) { $msg='Pet owner role is unavailable. Contact support.'; $msgType='danger'; }
                    else { $accountEmail=$ownerEmail!==''?$ownerEmail:'walkin+'.bin2hex(random_bytes(12)).'@walkin.petcare.invalid'; $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,is_active,is_email_verified) VALUES (?,?,?,?,?,?,1,0)')->execute([$ownerRole,$firstName,$lastName,$accountEmail,$ownerPhone!==''?sanitize($ownerPhone):null,hashPassword(bin2hex(random_bytes(24)))]); $ownerId=(int)$db->lastInsertId(); }
                }
                if ($ownerId) {
                $db->prepare('INSERT INTO pets (owner_id,name,species,breed,gender,age_years,age_months,weight_kg) VALUES (?,?,?,?,?,?,?,?)')->execute([$ownerId,sanitize($_POST['new_pet_name']),sanitize($_POST['new_species'] ?? 'Dog'),sanitize($_POST['new_breed'] ?? ''),in_array($_POST['new_gender'] ?? '',['male','female','unknown'],true)?$_POST['new_gender']:'unknown',(int)($_POST['new_age_years'] ?? 0),(int)($_POST['new_age_months'] ?? 0),(float)($_POST['new_weight'] ?? 0) ?: null]);
                $petId=(int)$db->lastInsertId();
                $db->prepare('INSERT INTO pet_health_records (pet_id,vet_id,record_type,title,description,diagnosis,test_performed,test_result,treatment,medications,comments,next_appointment,record_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$petId,$vetId,$type,sanitize($title),sanitize($_POST['walkin_description'] ?? ''),sanitize($_POST['walkin_diagnosis'] ?? ''),sanitize($_POST['walkin_test_performed'] ?? ''),sanitize($_POST['walkin_test_result'] ?? ''),sanitize($_POST['walkin_treatment'] ?? ''),sanitize($_POST['walkin_medications'] ?? ''),sanitize($_POST['walkin_comments'] ?? ''),sanitize($_POST['walkin_next_appointment'] ?? '') ?: null,$date]);
                $prePet=$petId; $msg='Medical record saved.';
                }
            }
        }
    }
}

// Pet list (vet's patients)
$patientPets = $db->prepare('SELECT DISTINCT p.*,CONCAT(u.first_name," ",u.last_name) AS owner_name FROM pets p JOIN users u ON u.id=p.owner_id WHERE p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?)) ORDER BY p.name');
$patientPets->execute([$vetId,$vetClinicId,$vetId]);
$patientPets = $patientPets->fetchAll();

// If a pet is selected, load its records
$selectedPet = null; $petRecords = [];
if ($prePet) {
    $ps = $db->prepare('SELECT p.*,CONCAT(u.first_name," ",u.last_name) AS owner_name FROM pets p JOIN users u ON p.owner_id=u.id WHERE p.id=? AND p.is_active=1 AND (EXISTS (SELECT 1 FROM appointments a WHERE a.pet_id=p.id AND (a.vet_id=? OR a.clinic_id=?)) OR EXISTS (SELECT 1 FROM pet_health_records hr WHERE hr.pet_id=p.id AND hr.vet_id=?))');
    $ps->execute([$prePet,$vetId,$vetClinicId,$vetId]); $selectedPet = $ps->fetch();
    if ($selectedPet) {
        $rs = $db->prepare('SELECT hr.*,CONCAT(u.first_name," ",u.last_name) AS vet_name FROM pet_health_records hr JOIN veterinarians v ON hr.vet_id=v.id LEFT JOIN users u ON v.user_id=u.id WHERE hr.pet_id=? AND v.clinic_id=? ORDER BY hr.record_date DESC');
        $rs->execute([$prePet,$vetClinicId]); $petRecords = $rs->fetchAll();
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;"><div><h1>Medical Records</h1><p>Manage records for your clinic patients.</p></div><button type="button" class="btn btn-primary" onclick="openModal('walkinRecordModal')"><i class="fas fa-user-plus"></i> Add Medical Record</button></div>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000" role="status"><i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i><?= sanitize($msg) ?></div><?php endif; ?>
  <?php if ($aiSummaryError): ?><div class="alert alert-danger" role="alert"><?= sanitize($aiSummaryError) ?></div><?php endif; ?>
  <?php if ($aiSummaryText): ?><section class="card" aria-labelledby="aiSummaryTitle" style="margin-bottom:1rem;"><div class="card-body"><h2 id="aiSummaryTitle" style="font-size:1rem;margin-bottom:.5rem;">AI chart summary</h2><p style="line-height:1.7;"><?= nl2br(sanitize($aiSummaryText)) ?></p><small style="color:var(--text-muted);">AI-generated summary. Review it against the medical record before relying on it.</small></div></section><?php endif; ?>

  <div class="dash-two-col" style="display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start;">
    <!-- Patient List -->
    <div class="card"><div class="card-header"><h3 style="font-size:.95rem;">Patients</h3></div><div style="padding:.5rem"><input type="search" id="patientSearch" class="form-control" placeholder="Search pet or owner"></div>
      <div style="padding:.5rem 0;">
        <?php if ($patientPets): foreach ($patientPets as $p): ?>
        <a data-patient-item data-search="<?= sanitize(strtolower($p['name'].' '.$p['species'].' '.$p['breed'].' '.$p['owner_name'])) ?>" href="records.php?pet=<?= $p['id'] ?>" class="sidebar-link <?= $prePet==$p['id']?'active':'' ?>">
          <i class="fas fa-<?= $p['species']==='cat'?'cat':'dog' ?>"></i>
          <div><div style="font-weight:600;font-size:.875rem;"><?= sanitize($p['name']) ?></div><div style="font-size:.75rem;color:var(--text-muted)"><?= sanitize($p['species']) ?><?= $p['breed']?' · '.sanitize($p['breed']):'' ?></div><div style="font-size:.72rem;color:var(--text-muted)">Owner: <?= sanitize($p['owner_name']) ?></div></div>
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

      <div class="card mb-3"><div class="card-body"><form method="POST"><?= csrfField() ?><input type="hidden" name="save_pet_note" value="1"><input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>"><label class="form-label" for="vetPetNote">Pet Note</label><textarea id="vetPetNote" name="pet_note" class="form-control" rows="3" maxlength="4000" placeholder="Ongoing information about this pet for the care team…"><?= sanitize($selectedPet['notes'] ?? '') ?></textarea><button class="btn btn-outline btn-sm" style="margin-top:.65rem"><i class="fas fa-save"></i> Save Pet Note</button></form></div></div>

      <!-- Records Table -->
      <div class="card">
        <div class="card-header"><h3>Health Records (<?= count($petRecords) ?>)</h3></div>
        <div class="table-wrap">
          <?php if ($petRecords): ?>
          <table>
            <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Diagnosis</th><th>Treatment</th><th>Next Visit</th><th>Veterinarian</th><th>Manage</th></tr></thead>
            <tbody>
            <?php foreach ($petRecords as $r): ?>
            <tr>
              <td><?= date('M j, Y',strtotime($r['record_date'])) ?></td>
              <td><span class="badge badge-info"><?= ucfirst($r['record_type']) ?></span></td>
              <td><div style="font-weight:600;"><?= sanitize($r['title']) ?></div><?php if($r['description']): ?><div style="font-size:.78rem;color:var(--text-muted)"><?= sanitize(substr($r['description'],0,60)) ?>…</div><?php endif; ?></td>
              <td style="font-size:.85rem;"><?= sanitize($r['diagnosis']??'—') ?></td>
              <td style="font-size:.85rem;"><?= sanitize($r['treatment']??'—') ?></td>
              <td><?= $r['next_appointment']?date('M j, Y',strtotime($r['next_appointment'])):'—' ?></td>
              <td><?= sanitize($r['vet_name'] ?: '—') ?></td>
              <td><button type="button" class="btn btn-sm btn-primary" data-history-prefix="vetRecordView" data-record-id="<?= (int)$r["id"] ?>" onclick="openModal(this.dataset.historyPrefix + this.dataset.recordId)"><i class="fas fa-eye"></i> Show</button> <details><summary class="btn btn-sm btn-outline">Edit</summary><form method="POST" style="min-width:300px;padding:.75rem;"><?= csrfField() ?><input type="hidden" name="update_record" value="1"><input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>"><input type="hidden" name="record_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="record_type" value="checkup"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= sanitize($r['title']) ?>" required maxlength="255"><label class="form-label">Date</label><input type="date" name="record_date" class="form-control" value="<?= sanitize($r['record_date']) ?>" required><label class="form-label">Description</label><textarea name="description" class="form-control"><?= sanitize($r['description'] ?? '') ?></textarea><label class="form-label">Diagnosis</label><textarea name="diagnosis" class="form-control"><?= sanitize($r['diagnosis'] ?? '') ?></textarea><label class="form-label">Test Performed</label><textarea name="test_performed" class="form-control"><?= sanitize($r['test_performed'] ?? '') ?></textarea><label class="form-label">Test Result</label><textarea name="test_result" class="form-control"><?= sanitize($r['test_result'] ?? '') ?></textarea><label class="form-label">Action</label><textarea name="treatment" class="form-control"><?= sanitize($r['treatment'] ?? '') ?></textarea><label class="form-label">Medication</label><textarea name="medications" class="form-control"><?= sanitize($r['medications'] ?? '') ?></textarea><label class="form-label">Comments / Notes</label><textarea name="comments" class="form-control"><?= sanitize($r['comments'] ?? '') ?></textarea><button class="btn btn-primary btn-sm" type="submit">Save</button></form><form method="POST" onsubmit="return confirm('Delete this medical record?')"><?= csrfField() ?><input type="hidden" name="pet_id" value="<?= (int)$selectedPet['id'] ?>"><input type="hidden" name="record_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" name="delete_record" value="1">Delete</button></form></details></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="empty-state"><i class="fas fa-file-medical"></i><h3>No records yet</h3></div>
          <?php endif; ?>
        </div>
      </div>
      <?php foreach ($petRecords as $r): ?><div class="modal-overlay" id="vetRecordView<?= (int)$r['id'] ?>"><div class="modal" style="max-width:760px;max-height:85vh;overflow:auto;"><div class="modal-header"><div><h3><?= sanitize($r['title']) ?></h3><small style="color:var(--text-muted)"><?= date('M j, Y',strtotime($r['record_date'])) ?> · <?= sanitize(ucfirst($r['record_type'])) ?> · Added by <?= sanitize($r['vet_name'] ?: 'Unknown veterinarian') ?></small></div><button class="modal-close" type="button" onclick="closeModal('vetRecordView<?= (int)$r['id'] ?>')"><i class="fas fa-times"></i></button></div><div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;"><?php foreach(['Description'=>'description','Diagnosis'=>'diagnosis','Test Performed'=>'test_performed','Test Result'=>'test_result','Action'=>'treatment','Medication'=>'medications','Comments / Notes'=>'comments'] as $label=>$field): ?><section style="padding:.85rem;background:var(--bg);border-radius:10px;min-height:90px;"><strong style="display:block;margin-bottom:.4rem;"><?= $label ?></strong><div style="white-space:pre-wrap;color:var(--text-muted)"><?= trim((string)($r[$field] ?? ''))!=='' ? nl2br(sanitize($r[$field])) : '—' ?></div></section><?php endforeach; ?></div></div></div><?php endforeach; ?>
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
          <input type="hidden" name="record_type" value="checkup">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Title <span class="required">*</span></label><input type="text" name="title" class="form-control" value="<?= sanitize($appointmentPrefill['service_name'] ?? ($appointmentPrefill['appointment_type'] ?? '')) ?>" placeholder="e.g. Annual check-up" required maxlength="255"></div>
            <div class="form-group">
              <label class="form-label">Record Date <span class="required">*</span></label>
              <input type="date" name="record_date" class="form-control" value="<?= sanitize($appointmentPrefill['appointment_date'] ?? date('Y-m-d')) ?>" required>
            </div>
          </div>
          <div class="form-row"><div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div><div class="form-group"><label class="form-label">Diagnosis</label><textarea name="diagnosis" class="form-control" rows="3"></textarea></div></div>
          <div class="form-row"><div class="form-group"><label class="form-label">Test Performed</label><textarea name="test_performed" class="form-control" rows="3"></textarea></div><div class="form-group"><label class="form-label">Test Result</label><textarea name="test_result" class="form-control" rows="3"></textarea></div></div>
          <div class="form-row"><div class="form-group"><label class="form-label">Action</label><textarea name="treatment" class="form-control" rows="3"></textarea></div><div class="form-group"><label class="form-label">Medication</label><textarea name="medications" class="form-control" rows="3"></textarea></div></div>
          <div class="form-group"><label class="form-label">Comments / Notes</label><textarea name="comments" class="form-control" rows="3"></textarea></div>
          <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;">
            <button type="button" onclick="closeModal('addRecordModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <div class="modal-overlay" id="walkinRecordModal"><div class="modal" style="max-width:720px;"><div class="modal-header"><h3>New Medical Record</h3><button class="modal-close" onclick="closeModal('walkinRecordModal')"><i class="fas fa-times"></i></button></div><div class="modal-body"><p style="color:var(--text-muted);margin-bottom:1rem;">For a walk-in visit without an online appointment. Enter the owner and pet details below. Email and phone are optional.</p><form method="POST"><?= csrfField() ?><input type="hidden" name="add_walkin_record" value="1"><div class="form-row"><div class="form-group"><label class="form-label">Pet owner name</label><input id="walkinOwnerName" name="owner_name" class="form-control" required placeholder="Owner full name"></div><div class="form-group"><label class="form-label">Email (optional)</label><input type="email" id="walkinOwnerEmail" name="owner_email" class="form-control" placeholder="owner@example.com"></div><div class="form-group"><label class="form-label">Phone (optional)</label><input type="tel" id="walkinOwnerPhone" name="owner_phone" class="form-control" placeholder="09xx xxx xxxx"></div></div><div id="newWalkinPetFields" class="card" style="padding:1rem;margin-bottom:1rem;"><h4 style="margin-bottom:.75rem;">New pet profile</h4><div class="form-row"><div class="form-group"><label class="form-label">Pet name</label><input name="new_pet_name" id="newWalkinName" class="form-control" required></div><div class="form-group"><label class="form-label">Species</label><input name="new_species" class="form-control" value="Dog"></div><div class="form-group"><label class="form-label">Breed</label><input name="new_breed" class="form-control"></div></div><div class="form-row"><div class="form-group"><label class="form-label">Gender</label><select name="new_gender" class="form-control"><option value="unknown">Unknown</option><option value="female">Female</option><option value="male">Male</option></select></div><div class="form-group"><label class="form-label">Age (years)</label><input type="number" min="0" name="new_age_years" class="form-control"></div><div class="form-group"><label class="form-label">Age (months)</label><input type="number" min="0" max="11" name="new_age_months" class="form-control"></div><div class="form-group"><label class="form-label">Weight (kg)</label><input type="number" min="0" step="0.01" name="new_weight" class="form-control"></div></div></div><input type="hidden" name="walkin_record_type" value="checkup"><div class="form-row"><div class="form-group"><label class="form-label">Title</label><input name="walkin_title" class="form-control" required maxlength="255"></div><div class="form-group"><label class="form-label">Record date</label><input type="date" name="walkin_record_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div></div><div class="form-row"><div class="form-group"><label class="form-label">Description</label><textarea name="walkin_description" class="form-control"></textarea></div><div class="form-group"><label class="form-label">Diagnosis</label><textarea name="walkin_diagnosis" class="form-control"></textarea></div></div><div class="form-row"><div class="form-group"><label class="form-label">Test Performed</label><textarea name="walkin_test_performed" class="form-control"></textarea></div><div class="form-group"><label class="form-label">Test Result</label><textarea name="walkin_test_result" class="form-control"></textarea></div></div><div class="form-row"><div class="form-group"><label class="form-label">Action</label><textarea name="walkin_treatment" class="form-control"></textarea></div><div class="form-group"><label class="form-label">Medication</label><textarea name="walkin_medications" class="form-control"></textarea></div></div><div class="form-group"><label class="form-label">Comments / Notes</label><textarea name="walkin_comments" class="form-control"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('walkinRecordModal')">Cancel</button><button class="btn btn-primary">Save Medical Record</button></div></form></div></div></div>
</main>
<script>


<?php if ($preAppt && $selectedPet): ?>openModal('addRecordModal');<?php endif; ?>
document.getElementById('patientSearch')?.addEventListener('input',function(){const term=this.value.trim().toLowerCase();document.querySelectorAll('[data-patient-item]').forEach(item=>{item.hidden=!item.dataset.search.includes(term);});});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
