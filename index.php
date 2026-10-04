<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) {
    $loggedInUser = currentUser();
    $dashboardByRole = [
        'veterinarian' => APP_URL . '/dashboard/vet/index.php',
        'admin'        => APP_URL . '/dashboard/admin/index.php',
        'superadmin'   => APP_URL . '/dashboard/admin/index.php',
        'groomer'      => APP_URL . '/dashboard/groomer/index.php',
    ];
    $roleName = $loggedInUser['role_name'] ?? '';
    if (isset($dashboardByRole[$roleName])) {
        header('Location: ' . $dashboardByRole[$roleName]);
        exit;
    }
}
require_once __DIR__ . '/includes/clinic_card.php';
$pageTitle = 'Home';
$isHeroPage = true; // Enables dark glassmorphism navbar over hero

// Fetch featured clinics — prioritize clinics with cover photos first, then by name
$stmt = getDB()->query("SELECT c.*, c.average_rating AS avg_rating, c.total_ratings AS rcount, svc.service_tags
    FROM clinics c
    LEFT JOIN (SELECT clinic_id, GROUP_CONCAT(DISTINCT name ORDER BY name SEPARATOR '||') AS service_tags
               FROM services WHERE is_active=1 GROUP BY clinic_id) svc ON svc.clinic_id=c.id
    WHERE c.is_active=1
    ORDER BY (c.cover_photo IS NOT NULL AND c.cover_photo != '') DESC, c.city ASC, c.name ASC LIMIT 6");
$featuredClinics = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ========== HERO (Premium Redesign) ========== -->
<section class="hero-premium" id="home">

  <!-- Background image with overlay -->
  <div class="hero-bg">
    <picture>
      <source media="(max-width: 768px)" srcset="assets/logo/ground1.png" data-light-src="assets/logo/ground1.png" data-dark-src="assets/logo/ground1.png">
      <img src="assets/logo/ground.png" alt="Happy pet owner with dog" class="hero-bg-img" id="heroBgImg" data-light-src="assets/logo/ground.png" data-dark-src="assets/logo/ground.png">
    </picture>
    <div class="hero-bg-overlay"></div>
    <!-- Decorative gradient orbs -->
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <!-- Floating paw particles (populated by JS) -->
    <div class="paw-particles" id="pawParticles"></div>
  </div>

  <!-- Main hero layout -->
  <div class="hero-premium-inner">

    <!-- LEFT: Text Content -->
    <div class="hero-left" id="heroLeft">

      <!-- Headline -->
      <h1 class="hero-premium-heading">
        <span class="hero-heading-line">Your Pet Deserves</span>
        <span class="hero-heading-line">the <span class="hero-heading-accent-inline">Best Care</span> in</span>
        <span class="hero-heading-line">Pangasinan</span>
      </h1>

      <!-- Description -->
      <p class="hero-premium-desc">
        Find top rated veterinary clinics &amp; grooming centers, book appointments in minutes,
        and get AI-assisted health insights for your furry family members.
      </p>

      <!-- CTA Buttons -->
      <div class="hero-premium-actions">
        <a href="<?= isLoggedIn() ? 'pages/clinics.php' : 'auth/register.php' ?>" class="btn-hero-primary">
          <i class="fas fa-hospital"></i> Find a Clinic
        </a>
        <a href="pages/clinics.php" class="btn-hero-secondary">
          <i class="fas fa-map-marker-alt"></i> Find Clinics
        </a>
      </div>

    </div><!-- /hero-left -->

  </div><!-- /hero-premium-inner -->


</section>

<!-- ========== MARQUEE TICKER ========== -->
<div class="marquee-strip" aria-hidden="true">
  <div class="marquee-track" id="marqueeTrack">
    <span class="marquee-item"><i class="fas fa-paw"></i> Trusted Veterinary Network <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-calendar-check"></i> Easy Online Booking <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-robot"></i> AI Health Insights <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-star"></i> Verified Clinics &amp; Groomers <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-shield-alt"></i> Licensed Professionals <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-map-marker-alt"></i> Serving All of Pangasinan <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-file-medical"></i> Digital Pet Health Records <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-bell"></i> Smart Appointment Reminders <span class="marquee-dot"></span></span>
    <!-- Duplicate for seamless loop -->
    <span class="marquee-item"><i class="fas fa-paw"></i> Trusted Veterinary Network <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-calendar-check"></i> Easy Online Booking <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-robot"></i> AI Health Insights <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-star"></i> Verified Clinics &amp; Groomers <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-shield-alt"></i> Licensed Professionals <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-map-marker-alt"></i> Serving All of Pangasinan <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-file-medical"></i> Digital Pet Health Records <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-bell"></i> Smart Appointment Reminders <span class="marquee-dot"></span></span>
  </div>
</div>

<!-- ========== FEATURED CLINICS ========== -->
<section class="section" id="clinics-section">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-tag">Clinics</div>
      <h2>Featured Clinics in Pangasinan</h2>
      <p>Discover top-rated veterinary clinics and grooming centers near you.</p>
    </div>
    <div class="clinics-grid reveal-stagger">
      <?php if ($featuredClinics): foreach ($featuredClinics as $cl): echo renderClinicCard($cl); endforeach; else: ?>
      <div class="empty-state" style="grid-column:1/-1">
        <i class="fas fa-hospital"></i>
        <h3>Clinics coming soon</h3>
        <p>No clinics registered yet. <a href="auth/register.php">Register your clinic</a></p>
      </div>
      <?php endif; ?>
    </div>
    <div class="text-center mt-4">
      <a href="pages/clinics.php" class="btn btn-primary">View All Clinics <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ========== PAW BACKGROUND WRAP (About → CTA) ========== -->
<div class="paw-bg-wrap">

<!-- ========== CTA BANNER ========== -->


</div><!-- /paw-bg-wrap -->

<!-- Hero navbar glass effect script -->
<script>
(function () {
  var nav  = document.getElementById('mainNav');
  var hero = document.querySelector('.hero-premium');
  if (!nav || !hero) return;

  function syncNav() {
    var scrollY     = window.scrollY || window.pageYOffset;
    var heroHeight  = hero.offsetHeight;

    if (scrollY < heroHeight) {
      // Still over the hero — keep transparent
      nav.classList.add('on-hero-top');
      nav.classList.remove('scrolled');
    } else {
      // Past the hero — solid dark background
      nav.classList.remove('on-hero-top');
      nav.classList.add('scrolled');
    }
  }

  syncNav();
  window.addEventListener('scroll', syncNav, { passive: true });
  window.addEventListener('resize', syncNav, { passive: true });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
