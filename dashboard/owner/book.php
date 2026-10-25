<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'Book Appointment';
$isDashboard = true;
$db = getDB();
$msg = ''; $msgType = 'success';

// Pre-selected clinic from URL
$preClinic = (int)($_GET['clinic'] ?? 0);
$prePet = (int)($_GET['pet'] ?? 0);
$preType = ($_GET['type'] ?? '') === 'grooming' ? 'grooming' : 'veterinary';

/** Return the clinic's appointment window as minutes after midnight. */
function clinicBookingWindow(?string $hoursJson, DateTimeImmutable $date): ?array {
    if ($hoursJson === null || trim($hoursJson) === '') return null;
    $hours = json_decode($hoursJson, true);
    if (!is_array($hours) || !$hours) return null;

    $dayName = strtolower($date->format('l'));
    $day = $hours[$dayName] ?? $hours[substr($dayName, 0, 3)] ?? null;
    if ($day === null || $day === 'closed' || !is_array($day) || !empty($day['closed'])) return null;

    $openValue = trim((string)($day['open'] ?? ''));
    $closeValue = trim((string)($day['close'] ?? ''));
    if (strtolower($openValue) === '24 hours' && $closeValue === '') return [0, 1440];
    if ($openValue === '' || $closeValue === '') return null;

    $toMinutes = static function (string $value): ?int {
        $timestamp = strtotime($value);
        if ($timestamp === false) return null;
        return ((int)date('G', $timestamp) * 60) + (int)date('i', $timestamp);
    };
    $openMinute = $toMinutes($openValue);
    $closeMinute = $toMinutes($closeValue);
    if ($openMinute === null || $closeMinute === null || $closeMinute <= $openMinute) return null;
    return [$openMinute, $closeMinute];
}

// Load user pets
$petsStmt = $db->prepare('SELECT id,name,species,breed,photo FROM pets WHERE owner_id=? AND is_active=1 ORDER BY name');
$petsStmt->execute([$user['id']]); $myPets = $petsStmt->fetchAll();

// Load clinics
$clinics = $db->query('SELECT id,name,city,logo,cover_photo FROM clinics WHERE is_active=1 AND is_verified=1 ORDER BY name')->fetchAll();

// Handle AJAX requests for clinic data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clinic_data') {
    header('Content-Type: application/json');
    $ajaxClinic = (int)($_GET['cid'] ?? 0);
    $result = ['services' => [], 'vets' => [], 'groomers' => [], 'operating_hours' => null];
    if ($ajaxClinic) {
        $verifiedClinic = $db->prepare('SELECT id FROM clinics WHERE id=? AND is_active=1 AND is_verified=1');
        $verifiedClinic->execute([$ajaxClinic]);
        if (!$verifiedClinic->fetchColumn()) { echo json_encode($result); exit; }
        $svcStmt = $db->prepare('SELECT id,name,price,service_type,veterinarian_discount_details,groomer_discount_details FROM services WHERE clinic_id=? AND is_active=1 ORDER BY service_type,name');
        $svcStmt->execute([$ajaxClinic]);
        $result['services'] = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

        $vetStmt = $db->prepare('SELECT v.id, CONCAT(u.first_name," ",u.last_name) AS full_name, v.specialization FROM veterinarians v JOIN users u ON v.user_id=u.id WHERE v.clinic_id=? AND v.is_available=1 AND u.is_active=1');
        $vetStmt->execute([$ajaxClinic]);
        $result['vets'] = $vetStmt->fetchAll(PDO::FETCH_ASSOC);

        $groomStmt = $db->prepare('SELECT g.id, CONCAT(u.first_name," ",u.last_name) AS full_name FROM groomers g JOIN users u ON g.user_id=u.id WHERE g.clinic_id=? AND g.is_available=1 AND u.is_active=1');
        $groomStmt->execute([$ajaxClinic]);
        $result['groomers'] = $groomStmt->fetchAll(PDO::FETCH_ASSOC);

        // Return operating hours JSON
        $clinicRow = $db->prepare('SELECT operating_hours FROM clinics WHERE id=?');
        $clinicRow->execute([$ajaxClinic]);
        $clinicData = $clinicRow->fetch(PDO::FETCH_ASSOC);
        if ($clinicData && $clinicData['operating_hours']) {
            $parsed = json_decode($clinicData['operating_hours'], true);
            $result['operating_hours'] = $parsed ?: null;
        }
    }
    echo json_encode($result);
    exit;
}

// Appointments must start from an active clinic profile, where the clinic is selected.
$requestedClinic = (int)($_GET['cid'] ?? $preClinic);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($requestedClinic <= 0) {
        header('Location: ' . APP_URL . '/pages/clinics.php');
        exit;
    }
    $activeClinic = $db->prepare('SELECT id FROM clinics WHERE id=? AND is_active=1 AND is_verified=1');
    $activeClinic->execute([$requestedClinic]);
    if (!$activeClinic->fetchColumn()) {
        header('Location: ' . APP_URL . '/pages/clinics.php');
        exit;
    }
}

// On clinic selection (initial page load): load services/vets/groomers
$selClinic = (int)($_GET['cid'] ?? ($_POST['clinic_id'] ?? $preClinic));
$clinicServices = $clinicVets = $clinicGroomers = [];
if ($selClinic) {
    $clinicServices = $db->prepare('SELECT * FROM services WHERE clinic_id=? AND is_active=1 ORDER BY service_type,name');
    $clinicServices->execute([$selClinic]); $clinicServices = $clinicServices->fetchAll();
    $clinicVets = $db->prepare('SELECT v.*,CONCAT(u.first_name," ",u.last_name) AS full_name FROM veterinarians v JOIN users u ON v.user_id=u.id WHERE v.clinic_id=? AND v.is_available=1 AND u.is_active=1');
    $clinicVets->execute([$selClinic]); $clinicVets = $clinicVets->fetchAll();
    $clinicGroomers = $db->prepare('SELECT g.*,CONCAT(u.first_name," ",u.last_name) AS full_name FROM groomers g JOIN users u ON g.user_id=u.id WHERE g.clinic_id=? AND g.is_available=1 AND u.is_active=1');
    $clinicGroomers->execute([$selClinic]); $clinicGroomers = $clinicGroomers->fetchAll();
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $petId    = (int)($_POST['pet_id'] ?? 0);
        $clinicId = (int)($_POST['clinic_id'] ?? 0);
        $svcId    = (int)($_POST['service_id'] ?? 0) ?: null;
        $vetId    = (int)($_POST['vet_id'] ?? 0) ?: null;
        $groomId  = (int)($_POST['groomer_id'] ?? 0) ?: null;
        $apptType = sanitize($_POST['appointment_type'] ?? 'veterinary');
        $apptDate = sanitize($_POST['appointment_date'] ?? '');
        $apptTime = sanitize($_POST['appointment_time'] ?? '');
        $reason   = sanitize($_POST['reason'] ?? '');
        $appointmentDate = DateTimeImmutable::createFromFormat('!Y-m-d', $apptDate);
        $timeValue = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/', $apptTime) ? $apptTime : '';

        if (!$petId || !$clinicId || !$apptDate || !$apptTime) {
            $msg = 'Please fill in all required fields.'; $msgType = 'danger';
        } elseif (!$appointmentDate || $appointmentDate->format('Y-m-d') !== $apptDate || !$timeValue) {
            $msg = 'Choose a valid appointment date and time.'; $msgType = 'danger';
        } elseif ($appointmentDate->format('Y-m-d') < date('Y-m-d')) {
            $msg = 'Appointment date must be today or later.'; $msgType = 'danger';
        } elseif ($appointmentDate->format('Y-m-d') === date('Y-m-d') && strtotime($apptDate . ' ' . $timeValue) <= time()) {
            $msg = 'Choose an appointment time that has not already passed.'; $msgType = 'danger';
        } elseif (!in_array($apptType, ['veterinary', 'grooming'], true)) {
            $msg = 'Choose a valid appointment type.'; $msgType = 'danger';
        } else {
            // Verify pet belongs to user
            $pc = $db->prepare('SELECT id FROM pets WHERE id=? AND owner_id=?');
            $pc->execute([$petId, $user['id']]);
            if (!$pc->fetch()) { $msg='Invalid pet selected.'; $msgType='danger'; }
            else {
                $verifiedClinic = $db->prepare('SELECT id,admin_id,operating_hours FROM clinics WHERE id=? AND is_active=1 AND is_verified=1');
                $verifiedClinic->execute([$clinicId]);
                $clinicRow = $verifiedClinic->fetch(PDO::FETCH_ASSOC);
                if (!$clinicRow) { $msg='Choose an approved clinic before booking.'; $msgType='danger'; }
                else {
                $window = clinicBookingWindow($clinicRow['operating_hours'] ?? null, $appointmentDate);
                $hour = (int)substr($timeValue, 0, 2);
                $minute = (int)substr($timeValue, 3, 2);
                $requestedMinute = $hour * 60 + $minute;
                if (!$window) {
                    $schedule = json_decode((string)($clinicRow['operating_hours'] ?? ''), true);
                    $msg = is_array($schedule) && $schedule
                        ? 'The clinic is closed on that day. Choose another date.'
                        : 'This clinic has not posted its opening hours yet. Contact the clinic before booking.';
                    $msgType = 'danger';
                } elseif ($requestedMinute < $window[0] || $requestedMinute >= $window[1] || (($requestedMinute - $window[0]) % 30) !== 0) {
                    $msg = 'Choose a time within the clinic’s opening hours.'; $msgType = 'danger';
                } else {
                if ($apptType === 'veterinary') $groomId = null;
                else $vetId = null;

                $validSelection = true;
                if ($svcId) {
                    $serviceCheck = $db->prepare('SELECT id FROM services WHERE id=? AND clinic_id=? AND is_active=1 AND service_type IN (?,\'both\')');
                    $serviceCheck->execute([$svcId, $clinicId, $apptType]);
                    $validSelection = (bool)$serviceCheck->fetchColumn();
                }
                if ($validSelection && $vetId) {
                    $vetCheck = $db->prepare('SELECT v.id FROM veterinarians v JOIN users u ON u.id=v.user_id WHERE v.id=? AND v.clinic_id=? AND v.is_available=1 AND u.is_active=1');
                    $vetCheck->execute([$vetId, $clinicId]);
                    $validSelection = (bool)$vetCheck->fetchColumn();
                }
                if ($validSelection && $groomId) {
                    $groomerCheck = $db->prepare('SELECT g.id FROM groomers g JOIN users u ON u.id=g.user_id WHERE g.id=? AND g.clinic_id=? AND g.is_available=1 AND u.is_active=1');
                    $groomerCheck->execute([$groomId, $clinicId]);
                    $validSelection = (bool)$groomerCheck->fetchColumn();
                }
                if (!$validSelection) {
                    $msg = 'The selected service or staff member is not available at this clinic.'; $msgType = 'danger';
                } else {
                // Handle symptom photo upload
                $symptomPhotoPath = null;
                if (!empty($_FILES['symptom_photo']['tmp_name']) && $_FILES['symptom_photo']['error'] === UPLOAD_ERR_OK) {
                    $uploaded = uploadFile($_FILES['symptom_photo'], 'symptom_photos');
                    if ($uploaded) $symptomPhotoPath = $uploaded;
                }

                $db->prepare('INSERT INTO appointments (owner_id,pet_id,clinic_id,service_id,vet_id,groomer_id,appointment_type,appointment_date,appointment_time,reason,symptom_photo,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,\'pending\')')
                   ->execute([$user['id'],$petId,$clinicId,$svcId,$vetId,$groomId,$apptType,$apptDate,$apptTime,$reason,$symptomPhotoPath]);
                // Notify clinic admin
                $admStmt = $db->prepare('SELECT admin_id FROM clinics WHERE id=?'); $admStmt->execute([$clinicId]); $adm = $admStmt->fetch();
                if ($adm) createNotification($adm['admin_id'], 'New Appointment Request', "A new appointment has been requested.", 'appointment', APP_URL.'/dashboard/admin/appointments.php');
                $msg = 'Appointment booked successfully! Waiting for clinic confirmation.';
                $msgType = 'success';
                }
                }
                }
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="booking-page">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Book Appointment</span></div>
    <h1>Book an Appointment</h1><p>Find a clinic, pick a service and time slot.</p>
  </div>

  <!-- Step progress bar -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body" style="padding:1.25rem 1.5rem;">
      <div class="booking-steps">
        <div class="booking-step active" id="step1">
          <div class="booking-step-num">1</div>
          <div class="booking-step-label">Pet</div>
        </div>
        <div class="booking-step-line"></div>
        <div class="booking-step" id="step2">
          <div class="booking-step-num">2</div>
          <div class="booking-step-label">Clinic</div>
        </div>
        <div class="booking-step-line"></div>
        <div class="booking-step" id="step3">
          <div class="booking-step-num">3</div>
          <div class="booking-step-label">Service</div>
        </div>
        <div class="booking-step-line"></div>
        <div class="booking-step" id="step4">
          <div class="booking-step-num">4</div>
          <div class="booking-step-label">Date &amp; Time</div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="6000"><i class="fas fa-<?= $msgType==='success'?'check':'exclamation' ?>-circle"></i><?= $msg ?></div>
  <?php endif; ?>

  <?php if (empty($myPets)): ?>
  <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> You need to <a href="pets.php?action=add" style="font-weight:700;">add a pet</a> before booking an appointment.</div>
  <?php else: ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" id="bookForm" enctype="multipart/form-data">
        <?= csrfField() ?>

        <!-- Step 1: Select Pet -->
        <!-- Step 1: Select Pet -->
        <div style="margin-bottom:2rem;">
          <h3 class="booking-step-heading"><span class="booking-step-badge">1</span> Select Your Pet</h3>
          <div class="booking-pet-row">
            <?php foreach ($myPets as $pet): ?>
            <label style="cursor:pointer;">
              <input type="radio" name="pet_id" value="<?= $pet['id'] ?>" style="display:none;" class="pet-radio" <?= $prePet === (int)$pet['id'] ? 'checked' : '' ?>>
              <div class="pet-select-card">
                <?php if (!empty($pet['photo'])): ?>
                  <img src="<?= assetUrl($pet['photo']) ?>"
                       alt="<?= sanitize($pet['name']) ?>"
                       style="width:56px;height:56px;border-radius:50%;object-fit:cover;
                              margin-bottom:.35rem;border:2px solid var(--border);">
                <?php else: ?>
                  <div style="font-size:1.5rem;margin-bottom:.35rem;">
                    <?= $pet['species']==='cat' ? '🐱' : ($pet['species']==='dog' ? '🐶' : '🐾') ?>
                  </div>
                <?php endif; ?>
                <div style="font-weight:600;font-size:.875rem;"><?= sanitize($pet['name']) ?></div>
                <div style="font-size:.75rem;color:var(--text-muted)"><?= sanitize($pet['species'] ?? '') ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Step 2: Select Clinic -->
        <div style="margin-bottom:2rem;">
          <h3 class="booking-step-heading"><span class="booking-step-badge">2</span> Select Clinic</h3>
          <select name="clinic_id" id="clinicSelect" class="form-control booking-clinic-select" onchange="loadClinicData(this.value)" data-selected="<?= $selClinic ?>" aria-hidden="true" tabindex="-1">
            <option value="">— Choose a clinic —</option>
            <?php foreach ($clinics as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $selClinic==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?> (<?= sanitize($c['city']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <div class="booking-clinic-grid">
            <?php foreach ($clinics as $c):
              $cover = !empty($c['cover_photo']) ? (str_starts_with($c['cover_photo'], 'http') ? $c['cover_photo'] : APP_URL . '/' . ltrim($c['cover_photo'], '/')) : '';
              $logo = !empty($c['logo']) ? (str_starts_with($c['logo'], 'http') ? $c['logo'] : APP_URL . '/' . ltrim($c['logo'], '/')) : '';
            ?>
              <button type="button" class="booking-clinic-option <?= $selClinic === (int)$c['id'] ? 'selected' : '' ?>" data-clinic-id="<?= (int)$c['id'] ?>" aria-pressed="<?= $selClinic === (int)$c['id'] ? 'true' : 'false' ?>">
                <span class="booking-clinic-cover" <?= $cover ? 'style="background-image:url(\'' . sanitize($cover) . '\')"' : '' ?>></span>
                <?php if ($logo): ?><img class="booking-clinic-logo" src="<?= sanitize($logo) ?>" alt="<?= sanitize($c['name']) ?> logo"><?php else: ?><span class="booking-clinic-logo booking-clinic-logo-placeholder"><i class="fas fa-hospital"></i></span><?php endif; ?>
                <span class="booking-clinic-name"><?= sanitize($c['name']) ?></span>
                <span class="booking-clinic-city"><i class="fas fa-location-dot"></i> <?= sanitize($c['city'] ?: 'Pangasinan') ?></span>
                <span class="booking-clinic-check"><i class="fas fa-check"></i></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Step 3: Service & Provider -->
        <div id="serviceSection" style="margin-bottom:2rem;display:none;">
          <h3 class="booking-step-heading">
            <span class="booking-step-badge">3</span> Service &amp; Provider
          </h3>

          <!-- Loading skeleton -->
          <div id="clinicLoadingSpinner" style="display:none;text-align:center;padding:1.5rem 0;">
            <div class="spinner" style="margin:0 auto .75rem;"></div>
            <p style="color:var(--text-muted);font-size:.875rem;">Loading clinic details…</p>
          </div>

          <!-- Operating hours info -->
          <div id="operatingHoursInfo" style="display:none;margin-bottom:1rem;"></div>

          <div id="clinicDataFields">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Appointment Type</label>
                <select name="appointment_type" id="apptType" class="form-control" onchange="toggleProvider()">
                  <option value="veterinary" <?= $preType === 'veterinary' ? 'selected' : '' ?>>Veterinary</option>
                  <option value="grooming" <?= $preType === 'grooming' ? 'selected' : '' ?>>Grooming</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Service</label>
                <select name="service_id" id="serviceSelect" class="form-control">
                  <option value="">— Select service —</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group" id="vetSection">
                <label class="form-label">Veterinarian</label>
                <select name="vet_id" id="vetSelect" class="form-control">
                  <option value="">— Any available vet —</option>
                </select>
              </div>
              <div class="form-group" id="groomerSection" style="display:none;">
                <label class="form-label">Groomer</label>
                <select name="groomer_id" id="groomerSelect" class="form-control">
                  <option value="">— Any available groomer —</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 4: Date & Time -->
        <div id="dateSection" style="margin-bottom:2rem;display:none;">
          <h3 class="booking-step-heading">
            <span class="booking-step-badge">4</span> Date &amp; Time
          </h3>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Preferred Date <span class="required">*</span></label>
              <input type="date" name="appointment_date" id="apptDate" class="form-control" min="<?= date('Y-m-d') ?>" required>
              <div id="dateHint" class="form-hint" style="display:none;"></div>
            </div>
            <div class="form-group">
              <label class="form-label">Preferred Time <span class="required">*</span></label>
              <select name="appointment_time" id="apptTime" class="form-control" required>
                <option value="">— Choose a date first —</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Reason -->
        <div class="form-group">
          <label class="form-label">Reason / Notes</label>
          <textarea name="reason" class="form-control" rows="3" placeholder="Describe the reason for your visit…"></textarea>
        </div>

        <!-- Symptom Photo Upload -->
        <div class="form-group">
          <label class="form-label">
            <i class="fas fa-camera" style="color:var(--primary);"></i>
            Symptom / Condition Photo
            <span style="color:var(--text-muted);font-weight:400;">(optional)</span>
          </label>
          <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem;">
            Upload a photo of your pet's condition, wound, or symptom so the vet can prepare before your visit.
          </p>

          <!-- Drop zone -->
          <div class="symptom-dropzone" id="symptomDropzone">
            <input type="file" name="symptom_photo" id="symptomPhotoInput"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   style="display:none;">
            <div class="symptom-dropzone-content" id="symptomDropContent">
              <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:var(--primary);margin-bottom:.5rem;"></i>
              <p style="font-weight:600;margin-bottom:.25rem;">Tap to upload photo</p>
              <p style="font-size:.78rem;color:var(--text-muted);">JPG, PNG, WEBP · Max 5MB</p>
            </div>
            <img id="symptomPreview" src="#" alt="Symptom preview"
                 style="display:none;width:100%;max-height:220px;object-fit:contain;border-radius:8px;">
          </div>

          <!-- Remove button -->
          <button type="button" id="symptomRemoveBtn"
                  style="display:none;margin-top:.5rem;"
                  class="btn btn-sm btn-outline"
                  onclick="removeSymptomPhoto()">
            <i class="fas fa-times"></i> Remove Photo
          </button>

          <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">
            <i class="fas fa-lock" style="color:var(--success);"></i>
            Visible only to the assigned veterinarian and clinic staff.
          </p>
        </div>

        <!-- Promo Code Section -->
        <div class="form-group promo-code-section">
          <label class="form-label">
            <i class="fas fa-tag" style="color:var(--primary);"></i>
            Have a Promo Code?
            <a href="<?= APP_URL ?>/promo-codes.php" target="_blank" style="font-size:.8rem;font-weight:400;margin-left:.5rem;">
              <i class="fas fa-external-link-alt"></i> Browse Promos
            </a>
          </label>
          <div class="promo-code-input-wrapper">
            <input type="text" 
                   name="promo_code" 
                   id="promoCodeInput" 
                   class="form-control" 
                   placeholder="Enter promo code (e.g., PETCARE10)"
                   style="text-transform: uppercase;">
            <button type="button" 
                    class="btn btn-outline" 
                    id="applyPromoBtn"
                    onclick="applyPromoCode()">
              Apply
            </button>
          </div>
          <div id="promoMessage" class="promo-message" style="display:none;"></div>
          <div id="promoDiscount" class="promo-discount-display" style="display:none;">
            <i class="fas fa-check-circle"></i>
            <span id="promoDiscountText"></span>
            <button type="button" class="promo-remove-btn" onclick="removePromoCode()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" id="confirmBookingBtn"><i class="fas fa-calendar-check"></i> Confirm Booking</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/book.css">

<script>
// ── Operating hours data passed from PHP ──
let clinicHours = {};
let allServices = []; // full list for filtering by appointment type

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Step progress tracker ────────────────────────────────────────
function updateSteps() {
  const hasPet    = !!document.querySelector('.pet-radio:checked');
  const hasClinic = !!document.getElementById('clinicSelect').value;
  const hasDate   = !!document.getElementById('apptDate').value;
  const hasTime   = !!document.getElementById('apptTime').value;

  const steps = ['step1','step2','step3','step4'];
  const done  = [hasPet, hasClinic && hasPet, hasDate, hasTime && hasDate];

  steps.forEach((id, i) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('active','done');
    if (done[i]) el.classList.add('done');
    else if (i === 0 || done[i-1]) el.classList.add('active');
  });
}

// ── Render operating hours panel ─────────────────────────────────
function renderOperatingHours(hours) {
  const panel = document.getElementById('operatingHoursInfo');
  if (!panel) return;

  if (!hours || !Object.keys(hours).length) {
    panel.style.display = 'none';
    return;
  }

  const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
  const labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const todayIdx = (new Date().getDay() + 6) % 7; // 0=Mon

  let html = '<div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;">';
  html += '<div style="font-size:.82rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;"><i class="fas fa-clock" style="margin-right:.4rem;color:var(--primary)"></i>Operating Hours</div>';
  html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.25rem;text-align:center;">';

  days.forEach((day, i) => {
    const info = hours[day] || hours[day.substring(0,3)] || null;
    const isToday = i === todayIdx;
    const isClosed = !info || info.closed || info === 'closed';
    const open = isClosed ? 'Closed' : (info.open || info);
    const close = isClosed ? '' : (info.close || '');

    html += `<div style="padding:.35rem .2rem;border-radius:6px;${isToday ? 'background:rgba(30,64,255,.08);border:1px solid rgba(30,64,255,.2);' : ''}">`;
    html += `<div style="font-size:.7rem;font-weight:700;color:${isToday ? 'var(--primary)' : 'var(--text-muted)'};">${labels[i]}</div>`;
    if (isClosed) {
      html += `<div style="font-size:.65rem;color:var(--danger);font-weight:600;">Closed</div>`;
    } else {
      html += `<div style="font-size:.65rem;color:var(--success);font-weight:600;">${open}</div>`;
      if (close) html += `<div style="font-size:.6rem;color:var(--text-muted);">${close}</div>`;
    }
    html += '</div>';
  });

  html += '</div></div>';
  panel.innerHTML = html;
  panel.style.display = 'block';
}

// Clinic hours and appointment slot helpers.
function parseClinicTime(value) {
  const match = String(value || '').trim().match(/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/i);
  if (!match) return null;
  let hour = Number(match[1]);
  const minute = Number(match[2]);
  const meridiem = (match[3] || '').toUpperCase();
  if (minute > 59 || hour > (meridiem ? 12 : 23) || (meridiem && hour < 1)) return null;
  if (meridiem) hour = (hour % 12) + (meridiem === 'PM' ? 12 : 0);
  return hour * 60 + minute;
}

function selectedClinicWindow(dateStr) {
  if (!Object.keys(clinicHours).length) return null;
  if (!dateStr) return null;
  const [year, month, day] = dateStr.split('-').map(Number);
  const selectedDate = new Date(year, month - 1, day);
  const dayKey = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'][selectedDate.getDay()];
  const info = clinicHours[dayKey] || clinicHours[dayKey.substring(0,3)] || null;
  if (!info || info.closed || info === 'closed') return null;
  const openValue = typeof info === 'string' ? info : info.open;
  const closeValue = typeof info === 'object' ? info.close : '';
  if (String(openValue || '').trim().toLowerCase() === '24 hours' && !closeValue) return [0, 1440];
  const open = parseClinicTime(openValue);
  const close = parseClinicTime(closeValue);
  return open !== null && close !== null && close > open ? [open, close] : null;
}

function renderAppointmentTimes() {
  const select = document.getElementById('apptTime');
  const dateStr = document.getElementById('apptDate')?.value || '';
  if (!select) return;
  select.innerHTML = '';
  const window = selectedClinicWindow(dateStr);
  if (!dateStr) {
    select.add(new Option('— Choose a date first —', ''));
    return;
  }
  if (!window) {
    const hasHours = Object.keys(clinicHours).length > 0;
    select.add(new Option(hasHours ? '— Clinic closed on this day —' : '— Clinic hours not posted yet —', ''));
    return;
  }

  const dateParts = dateStr.split('-').map(Number);
  const chosenDay = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
  const now = new Date();
  const today = now.getFullYear() === chosenDay.getFullYear()
    && now.getMonth() === chosenDay.getMonth()
    && now.getDate() === chosenDay.getDate();
  let added = 0;
  for (let minute = window[0]; minute < window[1]; minute += 30) {
    if (today && minute <= now.getHours() * 60 + now.getMinutes()) continue;
    const hour = Math.floor(minute / 60);
    const mins = minute % 60;
    const value = `${String(hour).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00`;
    const label = new Date(2000, 0, 1, hour, mins).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    select.add(new Option(label, value));
    added++;
  }
  if (!added) select.add(new Option('— No appointment times available —', ''));
  else select.add(new Option('— Select a time —', ''), 0);
}

// ── Load clinic data via AJAX ────────────────────────────────────
function checkDateAvailability(dateStr) {
  const hint = document.getElementById('dateHint');
  if (!hint || !dateStr) {
    if (hint) hint.style.display = 'none';
    renderAppointmentTimes();
    return;
  }

  const window = selectedClinicWindow(dateStr);
  if (!window) {
    hint.className = 'form-error';
    if (Object.keys(clinicHours).length) {
      const dateParts = dateStr.split('-').map(Number);
      const chosenDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
      const dayName = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][chosenDate.getDay()];
      hint.innerHTML = '<i class="fas fa-exclamation-circle"></i> This clinic is <strong>closed</strong> on ' + dayName + '. Please pick another date.';
    } else {
      hint.innerHTML = '<i class="fas fa-exclamation-circle"></i> This clinic has not posted its opening hours yet. Please contact the clinic before booking.';
    }
    hint.style.display = 'block';
    document.getElementById('apptDate').classList.add('error');
  } else {
    const openLabel = new Date(2000, 0, 1, Math.floor(window[0] / 60), window[0] % 60).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    const closeLabel = window[1] === 1440 ? '12:00 AM next day' : new Date(2000, 0, 1, Math.floor(window[1] / 60), window[1] % 60).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    hint.className = 'form-hint';
    hint.innerHTML = '<i class="fas fa-check-circle" style="color:var(--success)"></i> Open ' + openLabel + ' – ' + closeLabel;
    hint.style.display = 'block';
    document.getElementById('apptDate').classList.remove('error');
  }
  renderAppointmentTimes();
}

function loadClinicData(cid) {
  const serviceSection = document.getElementById('serviceSection');
  const dateSection    = document.getElementById('dateSection');
  const spinner        = document.getElementById('clinicLoadingSpinner');
  const dataFields     = document.getElementById('clinicDataFields');
  const svcSelect      = document.getElementById('serviceSelect');
  const vetSelect      = document.getElementById('vetSelect');
  const groomerSelect  = document.getElementById('groomerSelect');

  if (!cid) {
    serviceSection.style.display = 'none';
    dateSection.style.display    = 'none';
    clinicHours = {};
    renderOperatingHours({});
    checkDateAvailability('');
    updateSteps();
    return;
  }

  serviceSection.style.display = 'block';
  dateSection.style.display    = 'block';
  // Trigger reveal animation
  setTimeout(() => {
    serviceSection.classList.add('visible');
    dateSection.classList.add('visible');
  }, 10);

  // Show spinner, hide data fields
  if (spinner)    spinner.style.display    = 'block';
  if (dataFields) dataFields.style.display = 'none';

  fetch('book.php?ajax=clinic_data&cid=' + encodeURIComponent(cid))
    .then(r => r.json())
    .then(data => {
      // Store and render operating hours
      clinicHours = data.operating_hours || {};
      renderOperatingHours(clinicHours);
      checkDateAvailability(document.getElementById('apptDate').value);

      // Store all services; filtered rendering is handled by renderServices()
      allServices = data.services || [];

      // Populate vets
      vetSelect.innerHTML = '<option value="">— Any available vet —</option>';
      if (data.vets && data.vets.length) {
        data.vets.forEach(v => {
          const spec = v.specialization ? ` (${v.specialization})` : '';
          vetSelect.innerHTML += `<option value="${v.id}">Dr. ${escHtml(v.full_name)}${spec}</option>`;
        });
        vetSelect.value = String(data.vets[0].id);
      } else {
        vetSelect.innerHTML += '<option value="" disabled>No vets available</option>';
      }

      // Populate groomers
      groomerSelect.innerHTML = '<option value="">— Any available groomer —</option>';
      if (data.groomers && data.groomers.length) {
        data.groomers.forEach(g => {
          groomerSelect.innerHTML += `<option value="${g.id}">${escHtml(g.full_name)}</option>`;
        });
        groomerSelect.value = String(data.groomers[0].id);
      } else {
        groomerSelect.innerHTML += '<option value="" disabled>No groomers available</option>';
      }

      toggleProvider();
      if (spinner)    spinner.style.display    = 'none';
      if (dataFields) dataFields.style.display = 'block';
      updateSteps();
    })
    .catch(() => {
      clinicHours = {};
      checkDateAvailability(document.getElementById('apptDate').value);
      if (spinner)    spinner.style.display    = 'none';
      if (dataFields) dataFields.style.display = 'block';
      svcSelect.innerHTML    = '<option value="">— Select service —</option>';
      vetSelect.innerHTML    = '<option value="">— Any available vet —</option>';
      groomerSelect.innerHTML = '<option value="">— Any available groomer —</option>';
    });
}

function toggleProvider() {
  const type = document.getElementById('apptType').value;
  document.getElementById('vetSection').style.display     = type === 'veterinary' ? '' : 'none';
  document.getElementById('groomerSection').style.display = type === 'grooming'   ? '' : 'none';
  const providerSelect = document.getElementById(type === 'veterinary' ? 'vetSelect' : 'groomerSelect');
  if (providerSelect && !providerSelect.value && providerSelect.options.length > 1 && !providerSelect.options[1].disabled) providerSelect.selectedIndex = 1;
  renderServices(); // re-filter services when type changes
}

// ── Render service dropdown filtered by current appointment type ──
function renderServices() {
  const svcSelect = document.getElementById('serviceSelect');
  if (!svcSelect) return;
  const type = document.getElementById('apptType').value; // 'veterinary' or 'grooming'

  // Filter: include services whose type matches, or type='both'
  const filtered = allServices.filter(s =>
    s.service_type === type || s.service_type === 'both'
  );

  svcSelect.innerHTML = '<option value="">— Select service —</option>';
  if (filtered.length) {
    filtered.forEach(s => {
      const discountLabel = type === 'veterinary' ? 'Veterinarian discount' : 'Groomer discount';
      const basePrice = Number(s.price || 0);
      const price = basePrice > 0 ? ` – ₱${basePrice.toLocaleString('en-PH', {maximumFractionDigits: 2})}` : '';
      const details = String(type === 'veterinary' ? (s.veterinarian_discount_details || '') : (s.groomer_discount_details || '')).trim();
      const discount = details ? ` – ${discountLabel}: ${escHtml(details)}` : '';
      svcSelect.innerHTML += `<option value="${s.id}">${escHtml(s.name)}${price}${discount}</option>`;
    });
  } else {
    svcSelect.innerHTML += '<option value="" disabled>No services listed for this type</option>';
  }
}

// ── Wait for DOM to be ready ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  
  // ── Inline form validation ─────────────────────────────────────
  const bookForm = document.getElementById('bookForm');
  if (bookForm) {
    bookForm.addEventListener('submit', function(e) {
      const errors = [];
      if (!document.querySelector('.pet-radio:checked'))     errors.push('Please select a pet.');
      if (!document.getElementById('clinicSelect').value)    errors.push('Please select a clinic.');
      if (!document.getElementById('apptDate').value)        errors.push('Please pick a preferred date.');
      if (!document.getElementById('apptTime').value)        errors.push('Please select a preferred time.');

      // Block if date is a closed day
      const dateVal = document.getElementById('apptDate').value;
      if (dateVal && Object.keys(clinicHours).length) {
        const dateParts = dateVal.split('-').map(Number);
        const d = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
        const dayKey = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'][d.getDay()];
        const info = clinicHours[dayKey] || clinicHours[dayKey.substring(0,3)] || null;
        if (!info || info.closed || info === 'closed') {
          errors.push('The selected date is a closed day for this clinic.');
        }
      }

      if (errors.length) {
        e.preventDefault();
        const old = document.getElementById('clientValidationAlert');
        if (old) old.remove();
        const alertEl = document.createElement('div');
        alertEl.id        = 'clientValidationAlert';
        alertEl.className = 'alert alert-danger';
        alertEl.style.marginBottom = '1.25rem';
        alertEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> <strong>Please fix the following:</strong>'
          + '<ul style="margin:.5rem 0 0 1.25rem;">'
          + errors.map(err => `<li>${err}</li>`).join('')
          + '</ul>';
        bookForm.prepend(alertEl);
        alertEl.scrollIntoView({ behavior:'smooth', block:'center' });
      }
    });
  }

  // ── Pet radio card interaction ──────────────────────────────────
  document.querySelectorAll('.pet-radio').forEach(r => {
    r.addEventListener('change', () => {
      document.querySelectorAll('.pet-select-card').forEach(c => {
        c.style.borderColor = 'var(--border)';
        c.style.background  = '';
      });
      r.nextElementSibling.style.borderColor = 'var(--primary)';
      r.nextElementSibling.style.background  = 'rgba(30,64,255,.06)';
      updateSteps();
    });
  });
  document.querySelector('.pet-radio:checked')?.dispatchEvent(new Event('change'));

  document.querySelectorAll('.booking-clinic-option').forEach(option => {
    option.addEventListener('click', () => {
      const select = document.getElementById('clinicSelect');
      select.value = option.dataset.clinicId;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      document.querySelectorAll('.booking-clinic-option').forEach(card => {
        const selected = card === option;
        card.classList.toggle('selected', selected);
        card.setAttribute('aria-pressed', selected ? 'true' : 'false');
      });
    });
  });

  // Date change — check operating hours
  const apptDate = document.getElementById('apptDate');
  if (apptDate) {
    apptDate.addEventListener('change', function() {
      checkDateAvailability(this.value);
      updateSteps();
    });
  }
  
  const apptTime = document.getElementById('apptTime');
  if (apptTime) {
    apptTime.addEventListener('change', updateSteps);
  }

  // ── Auto-load if clinic pre-selected ───────────────────────────
  const sel = document.getElementById('clinicSelect');
  if (sel && sel.value) loadClinicData(sel.value);
  updateSteps();
  
});
</script>

<style>
/* Symptom photo dropzone */
.symptom-dropzone {
  border: 2px dashed var(--border);
  border-radius: var(--radius-sm);
  padding: 2rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: border-color .25s, background .25s;
  background: var(--card);
  position: relative;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.symptom-dropzone:hover,
.symptom-dropzone.dragover {
  border-color: var(--primary);
  background: rgba(30,64,255,.04);
}

.symptom-dropzone-content p {
  margin: 0;
  color: var(--text-muted);
  font-size: .875rem;
}
</style>

<script>
// ── Symptom photo upload ─────────────────────────────────
(function () {
  const dropzone  = document.getElementById('symptomDropzone');
  const input     = document.getElementById('symptomPhotoInput');
  const preview   = document.getElementById('symptomPreview');
  const content   = document.getElementById('symptomDropContent');
  const removeBtn = document.getElementById('symptomRemoveBtn');

  if (!dropzone || !input) return;

  // Click dropzone → trigger file picker
  dropzone.addEventListener('click', () => input.click());

  // Drag events
  dropzone.addEventListener('dragover',  e => { e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
  dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
      input.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files[0]);
    }
  });

  // File selected
  input.addEventListener('change', () => {
    if (input.files[0]) showPreview(input.files[0]);
  });

  function showPreview(file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('File too large. Maximum size is 5MB.');
      input.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = e => {
      preview.src           = e.target.result;
      preview.style.display = 'block';
      content.style.display = 'none';
      if (removeBtn) removeBtn.style.display = 'inline-flex';
      dropzone.style.padding = '.5rem';
    };
    reader.readAsDataURL(file);
  }

  window.removeSymptomPhoto = function () {
    input.value           = '';
    preview.src           = '#';
    preview.style.display = 'none';
    content.style.display = 'flex';
    if (removeBtn) removeBtn.style.display = 'none';
    dropzone.style.padding = '2rem 1rem';
  };
})();

// ── PROMO CODE FUNCTIONALITY ────────────────────────────────────
// Valid promo codes with their discounts
const promoCodes = {
  'PETCARE10': { type: 'percentage', value: 10, description: '10% off Veterinary Checkup' },
  'GROOM15': { type: 'percentage', value: 15, description: '15% off Grooming Service' },
  'PAWS150': { type: 'fixed', value: 150, description: '₱150 off Pet Supplies (min ₱1000)' },
  'HEALTHY': { type: 'free', value: 0, description: 'Free Wellness Consultation' },
  'VAXX20': { type: 'percentage', value: 20, description: '20% off Pet Vaccination' },
  'DENTAL200': { type: 'fixed', value: 200, description: '₱200 off Dental Cleaning' }
};

let appliedPromo = null;

function applyPromoCode() {
  const input = document.getElementById('promoCodeInput');
  const code = input.value.trim().toUpperCase();
  const messageDiv = document.getElementById('promoMessage');
  const discountDiv = document.getElementById('promoDiscount');
  const discountText = document.getElementById('promoDiscountText');
  
  if (!code) {
    showPromoMessage('Please enter a promo code', 'error');
    return;
  }
  
  if (!promoCodes[code]) {
    showPromoMessage('Invalid promo code. Please check and try again.', 'error');
    return;
  }
  
  appliedPromo = {
    code: code,
    ...promoCodes[code]
  };
  
  // Show success message
  let discountMsg = '';
  if (appliedPromo.type === 'percentage') {
    discountMsg = `${appliedPromo.value}% discount applied!`;
  } else if (appliedPromo.type === 'fixed') {
    discountMsg = `₱${appliedPromo.value} discount applied!`;
  } else if (appliedPromo.type === 'free') {
    discountMsg = 'Free service applied!';
  }
  
  discountText.textContent = `${code}: ${discountMsg} - ${appliedPromo.description}`;
  discountDiv.style.display = 'flex';
  messageDiv.style.display = 'none';
  input.disabled = true;
  document.getElementById('applyPromoBtn').disabled = true;
  
  // Add hidden input for form submission
  let promoInput = document.getElementById('appliedPromoCode');
  if (!promoInput) {
    promoInput = document.createElement('input');
    promoInput.type = 'hidden';
    promoInput.id = 'appliedPromoCode';
    promoInput.name = 'applied_promo_code';
    document.getElementById('bookForm').appendChild(promoInput);
  }
  promoInput.value = code;
}

function removePromoCode() {
  appliedPromo = null;
  document.getElementById('promoCodeInput').value = '';
  document.getElementById('promoCodeInput').disabled = false;
  document.getElementById('applyPromoBtn').disabled = false;
  document.getElementById('promoDiscount').style.display = 'none';
  document.getElementById('promoMessage').style.display = 'none';
  
  const promoInput = document.getElementById('appliedPromoCode');
  if (promoInput) promoInput.remove();
}

function showPromoMessage(text, type) {
  const messageDiv = document.getElementById('promoMessage');
  messageDiv.textContent = text;
  messageDiv.className = 'promo-message ' + type;
  messageDiv.style.display = 'block';
  
  if (type === 'error') {
    setTimeout(() => {
      messageDiv.style.display = 'none';
    }, 3000);
  }
}

// Auto-fill promo code from URL parameter (if user came from promo codes page)
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const promoFromUrl = urlParams.get('promo');
  if (promoFromUrl) {
    document.getElementById('promoCodeInput').value = promoFromUrl.toUpperCase();
  }
});
</script>>
