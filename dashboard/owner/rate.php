<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$apptId = (int)($_GET['appt'] ?? 0);
$db = getDB();
$pageTitle = 'Rate Service';
$isDashboard = true;

$stmt = $db->prepare("SELECT a.*,c.name AS clinic_name,p.name AS pet_name FROM appointments a JOIN clinics c ON a.clinic_id=c.id JOIN pets p ON a.pet_id=p.id WHERE a.id=? AND a.owner_id=? AND a.status='completed'");
$stmt->execute([$apptId, $user['id']]); $appt = $stmt->fetch();
if (!$appt) { header('Location: appointments.php'); exit; }

// Check already rated
$rated = $db->prepare('SELECT id FROM ratings WHERE appointment_id=?');
$rated->execute([$apptId]);
if ($rated->fetch()) { header('Location: appointments.php?already_rated=1'); exit; }

$msg = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $msg='Invalid request.'; }
    else {
        $cRating = (float)($_POST['clinic_rating'] ?? 0);
        $vRating = (float)($_POST['vet_rating'] ?? 0) ?: null;
        $gRating = (float)($_POST['groomer_rating'] ?? 0) ?: null;
        $comment = sanitize($_POST['comment'] ?? '');

        // Validate rating is between 0.5 and 5.0 in 0.5 increments
        if ($cRating < 0.5 || $cRating > 5 || fmod($cRating * 2, 1) != 0) { 
            $msg = 'Please select a valid clinic rating.'; 
        }
        else {
            $db->prepare('INSERT INTO ratings (appointment_id,owner_id,clinic_id,vet_id,groomer_id,clinic_rating,vet_rating,groomer_rating,comment) VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute([$apptId,$user['id'],$appt['clinic_id'],$appt['vet_id'],$appt['groomer_id'],$cRating,$vRating,$gRating,$comment]);
            refreshClinicRating($appt['clinic_id']);
            $done = true;
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="appointments.php">Appointments</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Rate Service</span></div>
    <h1>Rate Your Experience</h1><p>Your feedback helps other pet owners find the best care.</p>
  </div>

  <div style="max-width:560px;">
    <?php if ($done): ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:3rem;">
      <div style="font-size:4rem;margin-bottom:1rem;">⭐</div>
      <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem;">Thank You!</h2>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Your review has been submitted successfully.</p>
      <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
    </div></div>
    <?php else: ?>
    <div class="card">
      <div class="card-header">
        <h3>Review: <?= sanitize($appt['clinic_name']) ?></h3>
      </div>
      <div class="card-body">
        <div style="background:var(--bg);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.5rem;font-size:.875rem;">
          <strong>Pet:</strong> <?= sanitize($appt['pet_name']) ?> &nbsp;|&nbsp;
          <strong>Date:</strong> <?= date('M j, Y',strtotime($appt['appointment_date'])) ?> &nbsp;|&nbsp;
          <strong>Type:</strong> <?= ucfirst($appt['appointment_type']) ?>
        </div>

        <?php if ($msg): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= $msg ?></div><?php endif; ?>

        <form method="POST">
          <?= csrfField() ?>
          <!-- Clinic Rating -->
          <div class="form-group">
            <label class="form-label">Clinic Rating <span class="required">*</span></label>
            <div class="star-rating-input" id="clinicStars">
              <?php for ($i=1;$i<=5;$i++): ?>
              <button type="button" class="star-btn" data-val="<?= $i ?>" style="background:none;font-size:1.8rem;color:#D1D5DB;padding:.1rem;"><i class="fas fa-star"></i></button>
              <?php endfor; ?>
              <input type="hidden" name="clinic_rating" id="clinic_rating_val">
            </div>
          </div>
          <?php if ($appt['vet_id']): ?>
          <div class="form-group">
            <label class="form-label">Veterinarian Rating</label>
            <div class="star-rating-input" id="vetStars">
              <?php for ($i=1;$i<=5;$i++): ?><button type="button" class="star-btn" style="background:none;font-size:1.8rem;color:#D1D5DB;padding:.1rem;"><i class="fas fa-star"></i></button><?php endfor; ?>
              <input type="hidden" name="vet_rating" id="vet_rating_val">
            </div>
          </div>
          <?php endif; ?>
          <?php if ($appt['groomer_id']): ?>
          <div class="form-group">
            <label class="form-label">Groomer Rating</label>
            <div class="star-rating-input" id="groomerStars">
              <?php for ($i=1;$i<=5;$i++): ?><button type="button" class="star-btn" style="background:none;font-size:1.8rem;color:#D1D5DB;padding:.1rem;"><i class="fas fa-star"></i></button><?php endfor; ?>
              <input type="hidden" name="groomer_rating" id="groomer_rating_val">
            </div>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Comment / Review</label>
            <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience…"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Submit Review</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<style>
.star-btn { position: relative; }
.star-btn i { pointer-events: none; }
</style>
<script>
function initStars(container, inputId) {
  const stars = container.querySelectorAll('.star-btn');
  const input = document.getElementById(inputId);
  
  // Function to update star display
  function updateStars(rating) {
    stars.forEach((btn, idx) => {
      const icon = btn.querySelector('i');
      const starValue = idx + 1;
      
      if (rating >= starValue) {
        // Full star
        icon.className = 'fas fa-star';
        icon.style.color = '#F59E0B';
      } else if (rating >= starValue - 0.5) {
        // Half star
        icon.className = 'fas fa-star-half-alt';
        icon.style.color = '#F59E0B';
      } else {
        // Empty star
        icon.className = 'fas fa-star';
        icon.style.color = '#D1D5DB';
      }
    });
  }
  
  stars.forEach((btn, idx) => {
    btn.addEventListener('click', (e) => {
      const rect = btn.getBoundingClientRect();
      const clickX = e.clientX - rect.left;
      const width = rect.width;
      
      // If clicked on left half, set half star; if right half, set full star
      let rating;
      if (clickX < width / 2) {
        rating = idx + 0.5;
      } else {
        rating = idx + 1;
      }
      
      input.value = rating;
      updateStars(rating);
    });
    
    btn.addEventListener('mousemove', (e) => {
      const rect = btn.getBoundingClientRect();
      const mouseX = e.clientX - rect.left;
      const width = rect.width;
      
      let hoverRating;
      if (mouseX < width / 2) {
        hoverRating = idx + 0.5;
      } else {
        hoverRating = idx + 1;
      }
      
      updateStars(hoverRating);
    });
    
    btn.addEventListener('mouseout', () => {
      const val = parseFloat(input.value || 0);
      updateStars(val);
    });
  });
  
  container.addEventListener('mouseleave', () => {
    const val = parseFloat(input.value || 0);
    updateStars(val);
  });
}

initStars(document.getElementById('clinicStars'), 'clinic_rating_val');
<?php if ($appt['vet_id']): ?>initStars(document.getElementById('vetStars'), 'vet_rating_val');<?php endif; ?>
<?php if ($appt['groomer_id']): ?>initStars(document.getElementById('groomerStars'), 'groomer_rating_val');<?php endif; ?>
</script>
