<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pageTitle = 'Contact Us';
$success   = false;
$errors    = [];
$formData  = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => '', 'pet_id' => ''];

// ── Pre-fill name/email if logged in ─────────────────────
$loggedUser = isLoggedIn() ? currentUser() : null;
if ($loggedUser && !$_POST) {
    $formData['name']  = $loggedUser['first_name'] . ' ' . $loggedUser['last_name'];
    $formData['email'] = $loggedUser['email'];
    $formData['phone'] = $loggedUser['phone'] ?? '';
}

// ── Fetch user's pets for Book Appointment selector ───────
$userPets = [];
if ($loggedUser) {
    $pStmt = getDB()->prepare('SELECT id, name, species, breed FROM pets WHERE owner_id = ? ORDER BY name ASC');
    $pStmt->execute([$loggedUser['id']]);
    $userPets = $pStmt->fetchAll();
}

// ── Handle form submission ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_contact'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $formData['name']    = sanitize($_POST['name']    ?? '');
        $formData['email']   = trim($_POST['email']       ?? '');
        $formData['phone']   = sanitize($_POST['phone']   ?? '');
        $formData['subject'] = sanitize($_POST['subject'] ?? '');
        $formData['message'] = sanitize($_POST['message'] ?? '');
        $formData['pet_id']  = (int)($_POST['pet_id']     ?? 0);

        // If Book Appointment + pet selected, prepend pet info to message
        if ($formData['subject'] === 'Book Appointment' && $formData['pet_id'] && $loggedUser) {
            $petRow = getDB()->prepare('SELECT name, species, breed FROM pets WHERE id = ? AND owner_id = ?');
            $petRow->execute([$formData['pet_id'], $loggedUser['id']]);
            $petRow = $petRow->fetch();
            if ($petRow) {
                $petInfo = '[Pet: ' . $petRow['name'] . ' (' . ucfirst($petRow['species']) . ($petRow['breed'] ? ' — ' . $petRow['breed'] : '') . ')]' . "\n\n";
                $formData['message'] = $petInfo . $formData['message'];
            }
        }

        // Validation
        if (!$formData['name'])                                    $errors[] = 'Your name is required.';
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (!$formData['subject'])                                 $errors[] = 'Please enter a subject.';
        if (strlen($formData['message']) < 10)                    $errors[] = 'Message must be at least 10 characters.';

        if (!$errors) {
            $db = getDB();

            // ── Save to DB ────────────────────────────────
            $userId = isLoggedIn() ? currentUser()['id'] : null;
            try {
                $db->prepare('
                    INSERT INTO contact_messages
                        (user_id, name, email, phone, subject, message, status)
                    VALUES (?,?,?,?,?,?,\'unread\')
                ')->execute([$userId, $formData['name'], $formData['email'],
                             $formData['phone'], $formData['subject'], $formData['message']]);
                $msgId = (int)$db->lastInsertId();
            } catch (\Exception $e) {
                $msgId = 0; // DB save failed gracefully — still send email
            }

            // ── Notify all admins in-app ──────────────────
            if ($msgId) {
                try {
                    $admins = $db->query("
                        SELECT u.id FROM users u
                        JOIN   roles r ON u.role_id = r.id
                        WHERE  r.name IN ('admin','superadmin') AND u.is_active = 1
                        LIMIT  5
                    ")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($admins as $aid) {
                        createNotification(
                            (int)$aid,
                            '📬 New Contact Message',
                            $formData['name'] . ' sent a message: "' . mb_substr($formData['subject'], 0, 60) . '"',
                            'info',
                            APP_URL . '/dashboard/admin/messages.php?id=' . $msgId
                        );
                    }
                } catch (\Exception $e) {}
            }

            // ── Send email notification to clinic ─────────
            $emailHtml = contactEmailHtml(
                $formData['name'],
                $formData['email'],
                $formData['phone'],
                $formData['subject'],
                $formData['message']
            );
            $result = sendMail(
                MAIL_FROM,
                APP_NAME,
                '📬 New Contact Message: ' . $formData['subject'],
                $emailHtml
            );

            // ── Auto-reply to sender ───────────────────────
            $replyHtml = contactAutoReplyHtml($formData['name'], $formData['subject']);
            sendMail($formData['email'], $formData['name'],
                     'We received your message — PetCare Clinic', $replyHtml);

            if ($result === true) {
                $success  = true;
                $formData = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
            } else {
                $errors[] = 'Failed to send your message. Please try again or email us directly.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
/* ── Contact page ─────────────────────────────────────── */
.contact-hero {
  background: linear-gradient(135deg, #0F1F6E 0%, var(--primary) 60%, var(--secondary) 100%);
  padding: 3.5rem 0 2.5rem;
  text-align: center;
  color: #fff;
}

.contact-hero h1 {
  font-size: 2.25rem;
  font-weight: 800;
  margin-bottom: .5rem;
}

.contact-hero p {
  opacity: .85;
  font-size: 1rem;
  max-width: 500px;
  margin: 0 auto;
}

.contact-hero .hero-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.25);
  padding: .35rem 1rem;
  border-radius: 50px;
  font-size: .8rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.contact-page {
  padding: 3rem 0 5rem;
}

.contact-layout {
  display: grid;
  grid-template-columns: 1fr 1.6fr;
  gap: 2.5rem;
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* ── Info cards ─────────────────────────────────────────── */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-info-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
  transition: transform var(--transition), box-shadow var(--transition);
}

.contact-info-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.contact-info-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.icon-blue   { background: rgba(30,64,255,.1);  color: var(--primary); }
.icon-green  { background: rgba(0,212,170,.1);  color: var(--secondary); }
.icon-orange { background: rgba(245,158,11,.1); color: var(--warning); }
.icon-purple { background: rgba(139,92,246,.1); color: #8B5CF6; }
.icon-red    { background: rgba(239,68,68,.1);  color: var(--danger); }

.contact-info-body strong {
  display: block;
  font-size: .875rem;
  font-weight: 700;
  margin-bottom: .2rem;
  color: var(--text);
}

.contact-info-body span,
.contact-info-body a {
  font-size: .85rem;
  color: var(--text-muted);
  line-height: 1.6;
}

.contact-info-body a:hover {
  color: var(--primary);
}

/* Social links */
.contact-social {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  box-shadow: var(--shadow-sm);
}

.contact-social h4 {
  font-size: .8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.social-row {
  display: flex;
  gap: .6rem;
  flex-wrap: wrap;
}

.social-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .5rem 1rem;
  border-radius: 50px;
  font-size: .82rem;
  font-weight: 600;
  text-decoration: none;
  transition: all var(--transition);
  border: 1.5px solid var(--border);
  color: var(--text);
  background: var(--card);
}

.social-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

.social-btn.fb:hover   { background: #1877F2; color: #fff; border-color: #1877F2; }
.social-btn.ig:hover   { background: linear-gradient(135deg,#E1306C,#833AB4); color: #fff; border-color: transparent; }
.social-btn.tw:hover   { background: #1DA1F2; color: #fff; border-color: #1DA1F2; }
.social-btn.yt:hover   { background: #FF0000; color: #fff; border-color: #FF0000; }

/* ── Contact form ────────────────────────────────────────── */
.contact-form-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2rem 2.25rem;
  box-shadow: var(--shadow-sm);
}

.contact-form-card h2 {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: .3rem;
}

.contact-form-card > p {
  color: var(--text-muted);
  font-size: .875rem;
  margin-bottom: 1.5rem;
}

.contact-subject-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: .5rem;
  margin-bottom: 1.25rem;
}

.subject-chip {
  padding: .5rem .75rem;
  border: 1.5px solid var(--border);
  border-radius: 50px;
  font-size: .8rem;
  font-weight: 600;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--card);
  color: var(--text-muted);
  user-select: none;
}

.subject-chip:hover,
.subject-chip.selected {
  border-color: var(--primary);
  background: rgba(30,64,255,.08);
  color: var(--primary);
}

/* Success state */
.contact-success {
  text-align: center;
  padding: 2rem 1rem;
}

.contact-success-icon {
  width: 80px;
  height: 80px;
  background: rgba(16,185,129,.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  font-size: 2rem;
  color: var(--success);
}

/* Map */
.contact-map {
  margin-top: 2.5rem;
  max-width: 1100px;
  margin-left: auto;
  margin-right: auto;
  padding: 0 1.5rem;
}

.contact-map h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}

.map-embed {
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.map-embed iframe {
  display: block;
  width: 100%;
  height: 380px;
  border: none;
}

/* Responsive */
@media (max-width: 768px) {
  .contact-layout {
    grid-template-columns: 1fr;
  }
  .contact-hero h1 { font-size: 1.75rem; }
  .contact-form-card { padding: 1.5rem 1.25rem; }
}
</style>

<!-- ── Hero ─────────────────────────────────────────────── -->
<div class="contact-hero">
  <div class="container">
    <div class="hero-badge"><i class="fas fa-envelope"></i> Get in Touch</div>
    <h1><i class="fas fa-paw" style="margin-right:.5rem;"></i> Contact Us</h1>
    <p>Have a question, concern, or just want to say hello? We'd love to hear from you and your furry friends.</p>
  </div>
</div>

<!-- ── Main layout ──────────────────────────────────────── -->
<div class="contact-page">
  <div class="contact-layout">

    <!-- Left: Info cards -->
    <div class="contact-info">

      <div class="contact-info-card">
        <div class="contact-info-icon icon-blue"><i class="fas fa-map-marker-alt"></i></div>
        <div class="contact-info-body">
          <strong>Our Address</strong>
          <span>[Your Clinic Address]<br>Pangasinan, Philippines</span>
        </div>
      </div>

      <div class="contact-info-card">
        <div class="contact-info-icon icon-green"><i class="fas fa-phone"></i></div>
        <div class="contact-info-body">
          <strong>Phone / Mobile</strong>
          <a href="tel:+639000000000">+63-900-000-0000</a>
        </div>
      </div>

      <div class="contact-info-card">
        <div class="contact-info-icon icon-orange"><i class="fas fa-envelope"></i></div>
        <div class="contact-info-body">
          <strong>Email Address</strong>
          <a href="mailto:techytropa@gmail.com">techytropa@gmail.com</a>
        </div>
      </div>

      <div class="contact-info-card">
        <div class="contact-info-icon icon-purple"><i class="fas fa-clock"></i></div>
        <div class="contact-info-body">
          <strong>Business Hours</strong>
          <span>Monday – Saturday<br>8:00 AM – 6:00 PM (PHT)</span>
        </div>
      </div>

      <div class="contact-info-card">
        <div class="contact-info-icon icon-red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="contact-info-body">
          <strong>Emergency</strong>
          <span>For pet emergencies outside<br>business hours, call the nearest<br>24-hour veterinary clinic.</span>
        </div>
      </div>

      <!-- Social links -->
      <div class="contact-social">
        <h4><i class="fas fa-share-nodes" style="margin-right:.4rem;"></i> Follow Us</h4>
        <div class="social-row">
          <a href="#" class="social-btn fb"><i class="fab fa-facebook-f"></i> Facebook</a>
          <a href="#" class="social-btn ig"><i class="fab fa-instagram"></i> Instagram</a>
          <a href="#" class="social-btn tw"><i class="fab fa-twitter"></i> Twitter</a>
          <a href="#" class="social-btn yt"><i class="fab fa-youtube"></i> YouTube</a>
        </div>
      </div>

    </div><!-- /.contact-info -->

    <!-- Right: Form -->
    <div class="contact-form-card">

      <?php if ($success): ?>
      <!-- ── Success state ─────────────────────────────── -->
      <div class="contact-success">
        <div class="contact-success-icon">
          <i class="fas fa-check"></i>
        </div>
        <h2 style="margin-bottom:.5rem;">Message Sent! 🎉</h2>
        <p style="color:var(--text-muted);font-size:.9rem;line-height:1.8;margin-bottom:1.5rem;">
          Thank you for reaching out! We've received your message and will get back to you within <strong>24 hours</strong>.<br>
          A confirmation has also been sent to your email.
        </p>

        <a href="contact.php" class="btn btn-primary">
          <i class="fas fa-envelope"></i> Send Another Message
        </a>
        <br><br>
        <a href="<?= APP_URL ?>/index.php" class="btn btn-outline btn-sm">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>
      </div>

      <script>
        setTimeout(function () {
          window.location.href = 'contact.php';
        }, 3000);
      </script>

      <?php else: ?>
      <!-- ── Form ──────────────────────────────────────── -->
      <h2><i class="fas fa-paper-plane" style="color:var(--primary);margin-right:.5rem;"></i> Send us a Message</h2>
      <p>Fill out the form below and we'll respond as soon as possible.</p>

      <?php if ($errors): ?>
      <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php foreach ($errors as $e): ?><div><?= $e ?></div><?php endforeach; ?></div>
      </div>
      <?php endif; ?>

      <form method="POST" id="contactForm">
        <?= csrfField() ?>
        <input type="hidden" name="do_contact" value="1">
        <input type="hidden" name="subject" id="subjectHidden" value="<?= sanitize($formData['subject']) ?>">

        <!-- Name + Email -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-control"
              placeholder="Juan Dela Cruz"
              value="<?= sanitize($formData['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" name="email" class="form-control"
              placeholder="juan@email.com"
              value="<?= sanitize($formData['email']) ?>" required>
          </div>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label class="form-label">Phone Number <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <input type="tel" name="phone" class="form-control"
            placeholder="+63-900-000-0000"
            value="<?= sanitize($formData['phone']) ?>">
        </div>

        <!-- Subject chips -->
        <div class="form-group">
          <label class="form-label">Subject <span class="required">*</span></label>
          <div class="contact-subject-grid" id="subjectChips">
            <?php
            $subjects = [
              'General Inquiry'    => 'fas fa-question-circle',
              'Book Appointment'   => 'fas fa-calendar-plus',
              'Technical Issue'    => 'fas fa-wrench',
              'Pet Health Question'=> 'fas fa-paw',
              'Feedback'           => 'fas fa-star',
              'Partnership'        => 'fas fa-handshake',
              'Other'              => 'fas fa-ellipsis',
            ];
            foreach ($subjects as $label => $icon):
              $sel = ($formData['subject'] === $label) ? ' selected' : '';
            ?>
            <div class="subject-chip<?= $sel ?>" data-value="<?= $label ?>">
              <i class="<?= $icon ?>"></i> <?= $label ?>
            </div>
            <?php endforeach; ?>
          </div>
          <!-- Fallback text input if no chip selected -->
          <input type="text" name="subject_text" class="form-control"
            placeholder="Or type your subject here…"
            value="<?= sanitize($formData['subject']) ?>"
            style="margin-top:.5rem;"
            id="subjectText">
        </div>

        <!-- Pet selector — shows only when Book Appointment is selected -->
        <div class="form-group" id="petSelectorGroup"
             style="display:<?= $formData['subject']==='Book Appointment'?'block':'none' ?>;">
          <label class="form-label">
            <i class="fas fa-paw" style="color:var(--primary);"></i>
            Select Pet <span class="required">*</span>
          </label>
          <?php if ($loggedUser && $userPets): ?>
          <select name="pet_id" id="petSelector" class="form-control">
            <option value="">— Select your pet —</option>
            <?php foreach ($userPets as $pet): ?>
            <option value="<?= $pet['id'] ?>"
              <?= $formData['pet_id'] == $pet['id'] ? 'selected' : '' ?>>
              🐾 <?= sanitize($pet['name']) ?>
              <?php if ($pet['species']): ?>(<?= sanitize(ucfirst($pet['species'])) ?><?= $pet['breed'] ? ' · ' . sanitize($pet['breed']) : '' ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php elseif ($loggedUser && !$userPets): ?>
          <div class="alert alert-warning" style="margin:0;">
            <i class="fas fa-triangle-exclamation"></i>
            You have no registered pets yet.
            <a href="<?= APP_URL ?>/dashboard/owner/pets.php">Add a pet</a> first.
          </div>
          <?php else: ?>
          <div class="alert alert-info" style="margin:0;">
            <i class="fas fa-circle-info"></i>
            <a href="<?= APP_URL ?>/auth/login.php">Log in</a> to select your pet for the appointment.
          </div>
          <?php endif; ?>
          <div class="form-hint">
            <i class="fas fa-info-circle"></i>
            Your pet's info will be included in the message to the clinic.
          </div>
        </div>

        <!-- Message -->
        <div class="form-group">
          <label class="form-label">Message <span class="required">*</span></label>
          <textarea name="message" class="form-control" rows="5"
            placeholder="Tell us how we can help you…"
            required><?= sanitize($formData['message']) ?></textarea>
          <div class="form-hint">Minimum 10 characters.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="sendBtn">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>

        <div style="text-align:center;margin-top:1rem;">
          <a href="<?= APP_URL ?>/index.php" class="auth-nav-link">
            <i class="fas fa-arrow-left"></i> Back to Home
          </a>
        </div>

      </form>
      <?php endif; ?>

    </div><!-- /.contact-form-card -->
  </div><!-- /.contact-layout -->
</div><!-- /.contact-page -->

<script>
// Subject chip selection + pet selector toggle
(function () {
  const chips         = document.querySelectorAll('.subject-chip');
  const hidden        = document.getElementById('subjectHidden');
  const textInput     = document.getElementById('subjectText');
  const petGroup      = document.getElementById('petSelectorGroup');
  const petSelector   = document.getElementById('petSelector');

  function togglePetSelector(subject) {
    if (!petGroup) return;
    const isBook = subject === 'Book Appointment';
    petGroup.style.display = isBook ? 'block' : 'none';
    if (petSelector) petSelector.required = isBook;
    // Smooth reveal
    if (isBook) {
      petGroup.style.animation = 'fadeDown .25s ease';
    }
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('selected'));
      chip.classList.add('selected');
      const val = chip.dataset.value;
      if (hidden)    hidden.value    = val;
      if (textInput) textInput.value = val;
      togglePetSelector(val);
    });
  });

  // Sync text input → hidden field
  if (textInput) {
    textInput.addEventListener('input', () => {
      if (hidden) hidden.value = textInput.value;
      chips.forEach(c => c.classList.remove('selected'));
      togglePetSelector(''); // hide pet selector when typing custom subject
    });
  }

  // Init on page load (for repopulated form after error)
  const initSubject = hidden ? hidden.value : '';
  togglePetSelector(initSubject);

  // Send button loading state
  const form    = document.getElementById('contactForm');
  const sendBtn = document.getElementById('sendBtn');
  if (form && sendBtn) {
    form.addEventListener('submit', () => {
      sendBtn.disabled = true;
      sendBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending…';
    });
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// Email template: contact form notification to admin
// ============================================================
function contactEmailHtml(string $name, string $email, string $phone, string $subject, string $message): string {
    $phone   = $phone ?: 'Not provided';
    $msgHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $date    = date('F j, Y \a\t g:i A');
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:32px;text-align:center;">
            <div style="font-size:2rem;">📬</div>
            <h1 style="color:#fff;font-size:1.3rem;margin:10px 0 4px;">New Contact Message</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.85rem;margin:0;">PetCare+ Clinic Hub</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#F8FAFC;border-radius:10px;padding:16px 20px;margin-bottom:20px;font-size:.875rem;line-height:2;">
              <tr><td style="color:#64748B;width:120px;">From</td><td style="font-weight:700;color:#1E293B;">{$name}</td></tr>
              <tr><td style="color:#64748B;">Email</td><td><a href="mailto:{$email}" style="color:#1E40FF;">{$email}</a></td></tr>
              <tr><td style="color:#64748B;">Phone</td><td style="color:#1E293B;">{$phone}</td></tr>
              <tr><td style="color:#64748B;">Subject</td><td style="color:#1E40FF;font-weight:700;">{$subject}</td></tr>
              <tr><td style="color:#64748B;">Received</td><td style="color:#1E293B;">{$date}</td></tr>
            </table>
            <p style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:.5rem;">Message</p>
            <div style="background:#fff;border:1px solid #E2E8F0;border-left:4px solid #1E40FF;border-radius:8px;
                        padding:16px 20px;font-size:.9rem;color:#1E293B;line-height:1.8;">
              {$msgHtml}
            </div>
            <div style="text-align:center;margin-top:24px;">
              <a href="mailto:{$email}"
                 style="background:linear-gradient(135deg,#1E40FF,#00D4AA);color:#fff;text-decoration:none;
                        padding:12px 32px;border-radius:50px;font-weight:700;font-size:.9rem;display:inline-block;">
                Reply to {$name}
              </a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:16px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.75rem;color:#94A3B8;margin:0;">&copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}

// ============================================================
// Email template: auto-reply to the person who contacted
// ============================================================
function contactAutoReplyHtml(string $firstName, string $subject): string {
    $homeUrl = APP_URL . '/index.php';
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:32px;text-align:center;">
            <div style="font-size:2rem;">🐾</div>
            <h1 style="color:#fff;font-size:1.3rem;margin:10px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.85rem;margin:0;">We got your message!</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.25rem;">
              Thank you for reaching out to <strong>PetCare+ Clinic Hub</strong>!
              We've received your message about <strong>"{$subject}"</strong> and our team will get back to you within <strong>24 hours</strong>.
            </p>
            <div style="background:#F0FDF4;border:1px solid #86EFAC;border-radius:10px;padding:16px 20px;
                        margin-bottom:1.5rem;font-size:.875rem;color:#166534;line-height:1.8;">
              ✅ <strong>What happens next?</strong><br>
              Our team will review your message and respond to <strong>{$firstName}</strong> via email.<br>
              For urgent concerns, call us at <strong>+63-900-000-0000</strong>.
            </div>
            <div style="text-align:center;margin:24px 0;">
              <a href="{$homeUrl}"
                 style="background:linear-gradient(135deg,#1E40FF,#00D4AA);color:#fff;text-decoration:none;
                        padding:12px 32px;border-radius:50px;font-weight:700;font-size:.9rem;display:inline-block;">
                🏠 Back to PetCare+
              </a>
            </div>
            <p style="font-size:.8rem;color:#94A3B8;text-align:center;">
              Please do not reply to this automated email. Use the reply we send you shortly.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:16px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.75rem;color:#94A3B8;margin:0;">&copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
?>
