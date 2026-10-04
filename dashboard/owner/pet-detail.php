<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$ps = $db->prepare('SELECT * FROM pets WHERE id=? AND owner_id=? AND is_active=1');
$ps->execute([$id, $user['id']]);
$pet = $ps->fetch();
if (!$pet) { header('Location: pets.php'); exit; }

$pageTitle = $pet['name'] . ' – Health Profile';
$isDashboard = true;

// AI suggestions use the latest completed vet and grooming appointments only.
$historySql = "SELECT a.id, a.clinic_id, a.appointment_type, a.appointment_date, a.appointment_time,
    c.name AS clinic_name, COALESCE(s.name, a.appointment_type) AS service_name
    FROM appointments a
    JOIN clinics c ON c.id = a.clinic_id
    LEFT JOIN services s ON s.id = a.service_id
    WHERE a.pet_id = ? AND a.owner_id = ? AND a.status = 'completed'
      AND a.appointment_type = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 1";
$getLatestAppointment = $db->prepare($historySql);
$getLatestAppointment->execute([$id, $user['id'], 'veterinary']);
$lastVetAppointment = $getLatestAppointment->fetch() ?: null;
$getLatestAppointment->execute([$id, $user['id'], 'grooming']);
$lastGroomingAppointment = $getLatestAppointment->fetch() ?: null;
$hasAppointmentHistory = $lastVetAppointment || $lastGroomingAppointment;
$summaryOutput = '';
$suggestion = '';
$suggestionError = '';
$suggestionClinicId = 0;
$suggestionAppointmentType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_suggestion') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $suggestionError = 'Your session could not verify this request. Refresh the page and try again.';
    } elseif (!$hasAppointmentHistory) {
        $suggestionError = 'A suggestion is available after a completed appointment.';
    } else {
        $historyFacts = [];
        if ($lastVetAppointment) {
            $historyFacts[] = 'Last completed veterinary appointment: ' . $lastVetAppointment['appointment_date'] . '; service: ' . $lastVetAppointment['service_name'] . '; clinic: ' . $lastVetAppointment['clinic_name'] . '.';
        } else {
            $historyFacts[] = 'No completed veterinary appointment is recorded.';
        }
        if ($lastGroomingAppointment) {
            $historyFacts[] = 'Last completed grooming appointment: ' . $lastGroomingAppointment['appointment_date'] . '; service: ' . $lastGroomingAppointment['service_name'] . '; clinic: ' . $lastGroomingAppointment['clinic_name'] . '.';
        } else {
            $historyFacts[] = 'No completed grooming appointment is recorded.';
        }
        $historyText = implode("\n", $historyFacts);

        $summaryPrompt = "Summarize the latest completed veterinary and grooming appointment history in two short labeled lines. Include the date, service, and clinic exactly as recorded for each available appointment. State clearly when one type has no completed record. Use only the appointment facts below. Do not add pet names, medical conclusions, or recommendations.\n\n{$historyText}";
        $aiSummary = callGroq($summaryPrompt, 180);
        $summaryText = $aiSummary['success'] ? trim($aiSummary['text']) : '';
        $fallbackSummary = implode("\n", $historyFacts);
        $summaryOutput = (strlen($summaryText) >= 35 && preg_match('/[.!?]$/', $summaryText)) ? $summaryText : $fallbackSummary;

        $baseSuggestion = '';
        if ($lastGroomingAppointment) {
            $daysSinceGrooming = (new DateTimeImmutable($lastGroomingAppointment['appointment_date']))
                ->diff(new DateTimeImmutable('today'))->days;
            $groomDate = date('M j, Y', strtotime($lastGroomingAppointment['appointment_date']));
            if ($daysSinceGrooming < 14) {
                $baseSuggestion = "The last grooming appointment was on {$groomDate}, less than two weeks ago. Plan the next grooming visit later.";
                if ($lastVetAppointment) {
                    $vetDate = date('M j, Y', strtotime($lastVetAppointment['appointment_date']));
                    $baseSuggestion .= " The last vet visit was {$vetDate}; you can also ask {$lastVetAppointment['clinic_name']} about a routine check-up.";
                    $suggestionClinicId = (int)$lastVetAppointment['clinic_id'];
                    $suggestionAppointmentType = 'veterinary';
                }
            } else {
                $baseSuggestion = "The last grooming appointment was on {$groomDate} at {$lastGroomingAppointment['clinic_name']}. It has been at least two weeks, so consider booking another grooming visit.";
                $suggestionClinicId = (int)$lastGroomingAppointment['clinic_id'];
                $suggestionAppointmentType = 'grooming';
            }
        } elseif ($lastVetAppointment) {
            $vetDate = date('M j, Y', strtotime($lastVetAppointment['appointment_date']));
            $baseSuggestion = "The last veterinary appointment was on {$vetDate} at {$lastVetAppointment['clinic_name']}. Consider booking a routine check-up at that clinic.";
            $suggestionClinicId = (int)$lastVetAppointment['clinic_id'];
            $suggestionAppointmentType = 'veterinary';
        }

        $upsellPrompt = "Rewrite the following appointment-based offer as a warm, clear message to the pet owner. Preserve the exact recommendation, dates, and clinic names. Do not add a pet name, medical claim, fact, service, or schedule. Return complete sentences only, in 1-2 sentences.\n\nOffer: {$baseSuggestion}";
        $aiUpsell = callGroq($upsellPrompt, 140);
        $upsellText = $aiUpsell['success'] ? trim($aiUpsell['text']) : '';
        $suggestion = (strlen($upsellText) >= 35 && preg_match('/[.!?]$/', $upsellText)) ? $upsellText : $baseSuggestion;

        $db->prepare('INSERT INTO ai_recommendations (pet_id, generated_by, recommendation_type, recommendation, prompt_used) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $user['id'], 'general', $suggestion, $upsellPrompt]);
    }
}
// Health records
$records = $db->prepare("SELECT hr.*, CONCAT(u.first_name,' ',u.last_name) AS vet_name FROM pet_health_records hr LEFT JOIN veterinarians v ON hr.vet_id=v.id LEFT JOIN users u ON v.user_id=u.id WHERE hr.pet_id=? ORDER BY hr.record_date DESC");
$records->execute([$id]); $records = $records->fetchAll();

// Appointments
$appts = $db->prepare("SELECT a.*, c.name AS clinic_name, s.name AS service_name FROM appointments a JOIN clinics c ON a.clinic_id=c.id LEFT JOIN services s ON a.service_id=s.id WHERE a.pet_id=? ORDER BY a.appointment_date DESC LIMIT 10");
$appts->execute([$id]); $appts = $appts->fetchAll();

?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <a href="pets.php">My Pets</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span><?= sanitize($pet['name']) ?></span></div>
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <h1><?= sanitize($pet['name']) ?>'s Health Profile</h1>
      <div style="display:flex;gap:.75rem;">
        <button type="button" class="btn btn-secondary" data-open-suggestions><i class="fas fa-lightbulb"></i> Suggestions</button>
        <a href="pets.php?edit=<?= $pet['id'] ?>" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
      </div>
    </div>
  </div>

  <div class="dash-two-col" style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">
    <!-- Pet Info Card -->
    <div class="card">
      <div style="background:linear-gradient(135deg,var(--primary),var(--secondary));height:140px;display:flex;align-items:center;justify-content:center;font-size:4rem;">
        <?php $petPhotoUrl = assetUrl($pet['photo'] ?? null); ?>
        <?php if ($petPhotoUrl): ?>
        <img src="<?= sanitize($petPhotoUrl) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
        <?php else: ?>
        <?= $pet['species']==='cat'?'🐱':($pet['species']==='dog'?'🐶':'🐾') ?>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:.25rem;"><?= sanitize($pet['name']) ?></h2>
        <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:1.25rem;"><?= sanitize($pet['breed'] ?: $pet['species'] ?: 'Unknown breed') ?></p>
        <?php $info = [
          ['label'=>'Species','val'=>ucfirst($pet['species'] ?? '-')],
          ['label'=>'Breed',  'val'=>$pet['breed'] ?: '-'],
          ['label'=>'Age',    'val'=>($pet['age_years']??0).'y '.($pet['age_months']??0).'m'],
          ['label'=>'Gender', 'val'=>ucfirst($pet['gender'] ?? '-')],
          ['label'=>'Weight', 'val'=>$pet['weight_kg'] ? $pet['weight_kg'].' kg' : '-'],
          ['label'=>'Microchip','val'=>$pet['microchip_number'] ?: '-'],
        ];
        foreach ($info as $i): ?>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.875rem;">
          <span style="color:var(--text-muted)"><?= $i['label'] ?></span>
          <span style="font-weight:600;"><?= sanitize($i['val']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($pet['notes']): ?>
        <div style="margin-top:1rem;"><p style="font-size:.8rem;color:var(--text-muted);"><?= nl2br(sanitize($pet['notes'])) ?></p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabs -->
    <div>
      <div class="tabs-wrap">
        <div class="tab-nav" style="display:flex;gap:.25rem;background:var(--bg);padding:.35rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;width:fit-content;">
          <button class="tab-btn active btn btn-sm" data-tab="tab-records" style="border-radius:var(--radius-sm);">Health Records</button>
          <button class="tab-btn btn btn-sm btn-outline" data-tab="tab-appts"   style="border-radius:var(--radius-sm);">Appointments</button>
          <button class="tab-btn btn btn-sm btn-outline" data-tab="tab-suggestions" style="border-radius:var(--radius-sm);">Suggestions</button>
        </div>

        <!-- Health Records -->
        <div class="tab-pane active" id="tab-records">
          <div class="card">
            <div class="card-header"><h3>Health Records (<?= count($records) ?>)</h3></div>
            <div class="table-wrap">
              <?php if ($records): ?>
              <table>
                <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Vet</th><th>Next Appt</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                  <td><?= date('M j, Y', strtotime($r['record_date'])) ?></td>
                  <td><span class="badge badge-info"><?= ucfirst($r['record_type']) ?></span></td>
                  <td>
                    <div style="font-weight:600;"><?= sanitize($r['title']) ?></div>
                    <?php if ($r['description']): ?><div style="font-size:.78rem;color:var(--text-muted);"><?= sanitize(substr($r['description'],0,80)) ?>…</div><?php endif; ?>
                  </td>
                  <td><?= sanitize($r['vet_name'] ?? '—') ?></td>
                  <td><?= $r['next_appointment'] ? date('M j, Y',strtotime($r['next_appointment'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <?php else: ?><div class="empty-state"><i class="fas fa-file-medical"></i><h3>No health records yet</h3></div><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Appointments -->
        <div class="tab-pane" id="tab-appts">
          <div class="card">
            <div class="card-header"><h3>Appointment History</h3></div>
            <div class="table-wrap">
              <?php if ($appts): ?>
              <table>
                <thead><tr><th>Date</th><th>Clinic</th><th>Service</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($appts as $a): ?>
                <tr>
                  <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?><div style="font-size:.78rem;color:var(--text-muted)"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div></td>
                  <td><?= sanitize($a['clinic_name']) ?></td>
                  <td><?= sanitize($a['service_name'] ?? ucfirst($a['appointment_type'])) ?></td>
                  <td><?= statusBadge($a['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <?php else: ?><div class="empty-state"><i class="fas fa-calendar"></i><h3>No appointments yet</h3></div><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Grooming suggestions -->
        <div class="tab-pane" id="tab-suggestions">
          <div class="card">
            <div class="card-header"><h3>AI Suggestions</h3></div>
            <div class="card-body">
              <p style="margin-bottom:1rem;color:var(--text-muted);">AI reviews the latest completed vet and grooming appointments, then suggests a relevant next service.</p>
              <?php if ($summaryOutput): ?>
              <section style="padding:1rem 1.1rem;margin-bottom:1rem;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <h4 style="font-size:.95rem;margin-bottom:.5rem;"><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:.35rem;"></i>Latest Appointment Summary</h4>
                <p style="line-height:1.65;white-space:pre-line;"><?= sanitize($summaryOutput) ?></p>
              </section>
              <?php endif; ?>
              <?php if ($suggestion): ?>
              <h4 style="font-size:.95rem;margin:.25rem 0 .5rem;"><i class="fas fa-lightbulb" style="color:var(--primary);margin-right:.35rem;"></i>Suggested Next Service</h4>
              <div class="alert alert-info" role="status" style="line-height:1.65;"><?= nl2br(sanitize($suggestion)) ?></div>
              <?php if ($suggestionClinicId > 0): ?>
              <a href="<?= APP_URL ?>/pages/clinic.php?id=<?= $suggestionClinicId ?>" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> <?= $suggestionAppointmentType === 'grooming' ? 'Book grooming again' : 'Appoint again' ?></a>
              <?php endif; ?>
              <?php elseif ($suggestionError): ?>
              <div class="alert alert-warning" role="alert"><?= sanitize($suggestionError) ?></div>
              <?php endif; ?>
              <?php if (!$hasAppointmentHistory): ?>
              <div class="empty-state"><i class="fas fa-calendar-check"></i><h3>No completed appointments yet</h3><p>Suggestions will be available after an appointment is completed.</p></div>
              <?php else: ?>
              <form method="POST" action="pet-detail.php?id=<?= $id ?>" id="suggestionForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="generate_suggestion">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-wand-magic-sparkles"></i> <?= $suggestion ? 'Refresh suggestion' : 'Get a suggestion' ?></button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<style>
.tab-pane { display:none; }
.tab-pane.active { display:block; }
.tab-btn { background:transparent; border:none; color:var(--text-muted); font-weight:500; padding:.5rem 1rem; border-radius:var(--radius-sm); cursor:pointer; transition:all .2s; }
.tab-btn.active { background:#fff; color:var(--primary); box-shadow:var(--shadow-sm); font-weight:600; }
</style>
<script>
document.querySelector('[data-open-suggestions]')?.addEventListener('click', () => {
  document.querySelector('[data-tab="tab-suggestions"]')?.click();
  document.getElementById('suggestionForm')?.requestSubmit();
});
const suggestionTab = document.querySelector('[data-tab="tab-suggestions"]');
let suggestionRequestStarted = false;
suggestionTab?.addEventListener('click', event => {
  if (!event.isTrusted || suggestionRequestStarted) return;
  const form = document.getElementById('suggestionForm');
  if (form) {
    suggestionRequestStarted = true;
    form.requestSubmit();
  }
});
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_suggestion'): ?>
document.querySelector('[data-tab="tab-suggestions"]')?.click();
<?php endif; ?>
</script>
