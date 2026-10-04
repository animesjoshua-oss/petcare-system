<!-- ======= Footer ======= -->
<?php
$footerCurrentPath = $_SERVER['REQUEST_URI'] ?? '';
$footerIsAuthPage  = str_contains($footerCurrentPath, '/auth/');
$footerIsDashboard = isset($isDashboard) && $isDashboard;
?>
<?php if (!$footerIsDashboard): ?>
<footer class="footer" id="contact">
    <div class="footer-top">
        <div class="container">
            <?php if ($footerIsAuthPage): ?>
            <div class="footer-auth-row">
            <?php else: ?>
            <div class="footer-grid">
            <?php endif; ?>
                <!-- Brand -->
                <div class="footer-brand" <?= $footerIsAuthPage ? 'style="max-width:280px;"' : '' ?>>
                    <a href="<?= APP_URL ?>/index.php" class="footer-logo">
                        <i class="fas fa-paw"></i> PetCare<span>+</span>
                    </a>
                    <p>Centralizing veterinary clinics and grooming centers across Pangasinan, Philippines. Caring for your pets, one appointment at a time.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>


                <!-- Contact -->
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Pangasinan, Philippines</li>
                        <li><i class="fas fa-phone"></i> +63-900-000-0000</li>
                        <li><i class="fas fa-envelope"></i>techytropa@gmail.com</li>
                        <li><i class="fas fa-clock"></i> Mon–Sat: 8AM – 6PM</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            <div class="footer-legal">
                <a href="<?= APP_URL ?>/privacy.php">Privacy Policy</a>
                <a href="<?= APP_URL ?>/terms.php">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
<?php endif; // !$footerIsDashboard ?>

<!-- Main JS -->
<script src="<?= APP_URL ?>/assets/js/main.js?v=<?= time() ?>"></script>
<?= isset($extraJs) ? $extraJs : '' ?>

<?php if (isset($isDashboard) && $isDashboard):
  $u    = currentUser();
  $role = $u['role_name'] ?? '';
  $nc   = $u ? unreadNotificationCount($u['id']) : 0;

  // AI page URL per role
  $aiPageUrl = APP_URL . '/dashboard/';
  switch ($role) {
    case 'pet_owner':    $aiPageUrl .= 'owner/ai.php';   break;
    case 'veterinarian': $aiPageUrl .= 'vet/ai.php';     break;
    case 'groomer':      $aiPageUrl .= 'groomer/index.php'; break;
    default:             $aiPageUrl .= 'admin/ai.php';   break;
  }

  // Build bottom nav links per role
  $dashHome = APP_URL . '/dashboard/';
  $apptLink = APP_URL . '/dashboard/';
  $clinicLink = '';
  $petsLink = '';
  $aiLink   = APP_URL . '/dashboard/';

  switch ($role) {
    case 'pet_owner':
      $dashHome .= 'owner/index.php';
      $apptLink .= 'owner/appointments.php';
      $clinicLink = APP_URL . '/pages/clinics.php';
      $petsLink  = APP_URL . '/dashboard/owner/pets.php';
      $aiLink   .= 'owner/ai.php';
      break;
    case 'veterinarian':
      $dashHome .= 'vet/index.php';
      $apptLink .= 'vet/appointments.php';
      $aiLink   .= 'vet/ai.php';
      break;
    case 'groomer':
      $dashHome .= 'groomer/index.php';
      $apptLink .= 'groomer/appointments.php';
      break;
    default:
      $dashHome .= 'admin/index.php';
      $apptLink .= 'admin/appointments.php';
      $aiLink    = '';
      break;
  }

  $curPath = $_SERVER['REQUEST_URI'] ?? '';
  function mbnActive(string $link, string $cur): string {
    if (!$link) return '';
    $path = parse_url($link, PHP_URL_PATH) ?? '';
    return str_contains($cur, basename($path, '.php')) ? ' active' : '';
  }
?>

<!-- ── Sidebar overlay (mobile) ──────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Mobile bottom navigation ─────────────────── -->
<nav class="mobile-bottom-nav" role="navigation" aria-label="Bottom navigation">

  <a href="<?= $dashHome ?>" class="mbn-item<?= mbnActive($dashHome, $curPath) ?>">
    <i class="fas fa-home"></i><span>Home</span>
  </a>

  <?php if ($role === 'pet_owner'): ?>
  <a href="<?= $petsLink ?>" class="mbn-item<?= mbnActive($petsLink, $curPath) ?>">
    <i class="fas fa-paw"></i><span>Pets</span>
  </a>
  <a href="<?= $clinicLink ?>" class="mbn-item mbn-center<?= mbnActive($clinicLink, $curPath) ?>" aria-label="Find a clinic">
    <span class="mbn-center-btn"><i class="fas fa-hospital"></i></span><span>Clinics</span>
  </a>
  <a href="<?= $apptLink ?>" class="mbn-item<?= mbnActive($apptLink, $curPath) ?>">
    <i class="fas fa-calendar-alt"></i><span>Appts</span>
  </a>

  <?php elseif ($role === 'veterinarian'): ?>
  <a href="<?= $apptLink ?>" class="mbn-item<?= mbnActive($apptLink, $curPath) ?>">
    <i class="fas fa-calendar-alt"></i><span>Appts</span>
  </a>
  <a href="<?= APP_URL ?>/dashboard/vet/records.php" class="mbn-item<?= mbnActive(APP_URL.'/dashboard/vet/records.php', $curPath) ?>">
    <i class="fas fa-file-medical"></i><span>Records</span>
  </a>
  <a href="<?= $aiLink ?>" class="mbn-item<?= mbnActive($aiLink, $curPath) ?>">
    <i class="fas fa-robot"></i><span>AI</span>
  </a>

  <?php elseif ($role === 'groomer'): ?>
  <a href="<?= $apptLink ?>" class="mbn-item<?= mbnActive($apptLink, $curPath) ?>">
    <i class="fas fa-calendar-alt"></i><span>Schedule</span>
  </a>

  <?php else: // admin ?>
  <a href="<?= $apptLink ?>" class="mbn-item<?= mbnActive($apptLink, $curPath) ?>">
    <i class="fas fa-calendar-alt"></i><span>Appts</span>
  </a>
  <a href="<?= APP_URL ?>/dashboard/admin/users.php" class="mbn-item<?= mbnActive(APP_URL.'/dashboard/admin/users.php', $curPath) ?>">
    <i class="fas fa-users"></i><span>Users</span>
  </a>
  <a href="<?= APP_URL ?>/dashboard/admin/reports.php" class="mbn-item<?= mbnActive(APP_URL.'/dashboard/admin/reports.php', $curPath) ?>">
    <i class="fas fa-chart-bar"></i><span>Reports</span>
  </a>
  <?php endif; ?>

  <a href="<?= APP_URL ?>/notifications.php" class="mbn-item<?= str_contains($curPath, 'notification') ? ' active' : '' ?>">
    <i class="fas fa-bell"></i>
    <?php if ($nc > 0): ?><span class="mbn-badge"><?= $nc > 9 ? '9+' : $nc ?></span><?php endif; ?>
    <span>Notifs</span>
  </a>

</nav>

<!-- ── Floating AI Button ─────────────────────────── -->
<?php if ($role !== 'groomer'): ?>
<a href="<?= $aiPageUrl ?>" class="ai-fab" id="aiFab" title="AI Assistant" aria-label="Open AI Assistant">
  <i class="fas fa-robot"></i>
  <span class="ai-fab-label">AI</span>
</a>
<?php endif; ?>

<script>
(function() {
  var fab = document.getElementById('aiFab');
  if (fab && window.location.pathname.endsWith('ai.php')) fab.style.display = 'none';
})();
(function() {
  var cur = window.location.pathname;
  document.querySelectorAll('.mbn-item').forEach(function(item) {
    var href = item.getAttribute('href') || '';
    var page = href.split('/').pop().split('?')[0];
    if (page && cur.endsWith(page)) item.classList.add('active');
  });
})();
</script>

<?php endif; ?>

<!-- ======= PetCareAI Floating Chat Widget ======= -->
<?php if ($user && ($user['role_name'] ?? '') === 'pet_owner'): ?>
<?php $widgetCsrf = generateCsrfToken(); ?>

<!-- Widget toggle button -->
<button class="pcai-toggle" id="pcaiToggle" aria-label="Open PetCareAI Chat">
  <i class="fas fa-paw pcai-toggle-icon-open"></i>
  <i class="fas fa-times pcai-toggle-icon-close" style="display:none;"></i>
  <span class="pcai-toggle-badge" id="pcaiBadge" style="display:none;"></span>
</button>

<!-- Chat window — data-csrf passes token to external JS -->
<div class="pcai-widget" id="pcaiWidget" aria-hidden="true" data-csrf="<?= $widgetCsrf ?>" data-api-url="<?= APP_URL ?>/api/chat.php">
  <div class="pcai-header">
    <div class="pcai-header-info">
      <div class="pcai-avatar"><i class="fas fa-paw"></i></div>
      <div>
        <div class="pcai-name">PetCareAI</div>
        <div class="pcai-status"><span class="pcai-dot"></span> Online</div>
      </div>
    </div>
    <div class="pcai-header-actions">
      <button class="pcai-btn-icon" id="pcaiClear" title="Clear chat"><i class="fas fa-rotate-right"></i></button>
      <button class="pcai-btn-icon" id="pcaiClose" title="Close"><i class="fas fa-times"></i></button>
    </div>
  </div>

  <div class="pcai-messages" id="pcaiMessages">
    <div class="pcai-date-sep"><?= date('F j, Y') ?></div>
    <div class="pcai-msg pcai-msg-bot">
      <div class="pcai-msg-avatar"><i class="fas fa-paw"></i></div>
      <div class="pcai-msg-body">
        <div class="pcai-msg-name">PetCareAI</div>
        <div class="pcai-bubble">
          👋 Hi <strong><?= sanitize($user['first_name']) ?></strong>! Need help finding a groomer or have a pet care question?
        </div>
        <div class="pcai-msg-time"><?= date('g:i A') ?></div>
      </div>
    </div>
  </div>

  <div class="pcai-chips" id="pcaiChips" aria-label="Suggested questions">
    <button type="button" class="pcai-chip" data-msg="Help me find a clinic">Find a clinic</button>
    <button type="button" class="pcai-chip" data-msg="What grooming services are available?">Grooming services</button>
    <button type="button" class="pcai-chip" data-msg="How do I book an appointment?">Book an appointment</button>
  </div>

  <div class="pcai-input-row">
    <button type="button" class="pcai-img-btn" id="pcaiImgBtn" title="Send a photo of your pet">
      <i class="fas fa-image"></i>
    </button>
    <input type="file" id="pcaiImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;position:absolute;">
    <input type="text" class="pcai-input" id="pcaiInput" placeholder="Ask about groomers or pet care..." maxlength="300" autocomplete="off">
    <button class="pcai-send" id="pcaiSend" aria-label="Send">
      <i class="fas fa-paper-plane"></i>
    </button>
  </div>

  <div class="pcai-img-preview-bar" id="pcaiImgPreviewBar" style="display:none;">
    <img id="pcaiImgPreview" src="" alt="preview" class="pcai-img-preview-thumb">
    <span class="pcai-img-preview-name" id="pcaiImgName"></span>
    <button class="pcai-img-remove" id="pcaiImgRemove" title="Remove image"><i class="fas fa-times"></i></button>
  </div>
  <div class="pcai-footer-label">Powered by <strong>PetCare Hub AI</strong></div>
</div>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pcai_widget.css">
<script src="<?= APP_URL ?>/assets/js/pcai_widget.js?v=<?= time() ?>"></script>

<?php endif; // end if ($user) ?>

<!-- ======= PWA: Service Worker Registration ======= -->
<script>
(function() {
  if (!('serviceWorker' in navigator)) return;
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js', { scope: '<?= APP_URL ?>/' })
      .then(function(reg) { reg.update(); })
      .catch(function(err) { console.warn('[PWA] SW failed:', err); });
  });

  var deferredPrompt = null;
  var installBanner  = document.getElementById('pwaInstallBanner');
  var installBtn     = document.getElementById('pwaInstallBtn');
  var dismissBtn     = document.getElementById('pwaInstallDismiss');

  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault(); deferredPrompt = e;
    if (installBanner) installBanner.style.display = 'flex';
  });
  if (installBtn) {
    installBtn.addEventListener('click', function() {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function() {
        deferredPrompt = null;
        if (installBanner) installBanner.style.display = 'none';
      });
    });
  }
  if (dismissBtn) {
    dismissBtn.addEventListener('click', function() {
      if (installBanner) installBanner.style.display = 'none';
      deferredPrompt = null;
    });
  }
  window.addEventListener('appinstalled', function() {
    if (installBanner) installBanner.style.display = 'none';
    deferredPrompt = null;
  });
})();
</script>

<!-- PWA Install Banner -->
<?php $pwaLogoUrl = rtrim(str_replace(' ', '%20', APP_URL), '/') . '/assets/logo/petcare.png'; ?>
<div id="pwaInstallBanner" style="display:none;position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);background:#0f2137;border:1px solid #00D4AA;border-radius:12px;padding:0.85rem 1.25rem;align-items:center;gap:0.75rem;z-index:9999;box-shadow:0 4px 24px rgba(0,0,0,0.4);min-width:280px;max-width:90vw;font-family:'Poppins',sans-serif;">
  <img src="<?= $pwaLogoUrl ?>" alt="PetCare+" style="width:40px;height:40px;border-radius:10px;flex-shrink:0;object-fit:contain;background:#081d35;padding:3px;">
  <div style="flex:1;min-width:0;">
    <div style="font-weight:600;color:#e2e8f0;font-size:0.88rem;line-height:1.3;">Install PetCare+</div>
    <div style="color:#94a3b8;font-size:0.78rem;">Add to home screen for quick access</div>
  </div>
  <button id="pwaInstallBtn" style="background:#00D4AA;color:#081221;border:none;padding:0.45rem 1rem;border-radius:7px;font-weight:600;font-size:0.82rem;cursor:pointer;font-family:inherit;flex-shrink:0;">Install</button>
  <button id="pwaInstallDismiss" style="background:transparent;border:none;color:#64748b;cursor:pointer;font-size:1.1rem;padding:0 0.25rem;line-height:1;flex-shrink:0;" aria-label="Dismiss">✕</button>
</div>

</body>
</html>
