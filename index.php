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
    WHERE c.is_active=1 AND c.is_verified=1
    ORDER BY (c.cover_photo IS NOT NULL AND c.cover_photo != '') DESC, c.city ASC, c.name ASC LIMIT 6");
$featuredClinics = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ========== HERO (Premium Redesign) ========== -->
<section class="hero-premium" id="home">

  <!-- Background image with overlay -->
  <div class="hero-bg">
    <picture>
      <source media="(max-width: 768px)" srcset="assets/images/landing page - for moblie run/mobile0.png">
      <source media="(min-width: 769px)" srcset="assets/images/landing page/image0.png">
      <img src="assets/images/landing page - for moblie run/mobile0.png" alt="Happy pet owner with dog" class="hero-bg-img" id="heroBgImg">
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
        <a href="<?= isLoggedIn() ? APP_URL . '/clinics.php' : APP_URL . '/auth/register.php' ?>" class="btn-hero-primary">
          <i class="fas fa-rocket"></i> Get Started
        </a>
        <a href="<?= APP_URL ?>/clinics.php" class="btn-hero-secondary">
          <i class="fas fa-map-marker-alt"></i> Find Clinics
        </a>
      </div>

    </div><!-- /hero-left -->

  </div><!-- /hero-premium-inner -->


</section>

<!-- ========== PROMO CODE SECTION ========== -->
<section class="promo-section">
  <div class="container">
    <div class="promo-banner">
      <div class="promo-content">
        <div class="promo-icon">
          <i class="fas fa-gift"></i>
        </div>
        <div class="promo-text">
          <h3 class="promo-title">Special Offer for New Pet Owners!</h3>
          <p class="promo-desc">Get <strong>20% OFF</strong> your first veterinary appointment</p>
        </div>
      </div>
      <div class="promo-code-wrapper">
        <div class="promo-code-box">
          <span class="promo-label">Promo Code:</span>
          <code class="promo-code" id="promoCode">PAWCARE20</code>
          <button class="promo-copy-btn" id="copyPromoBtn" onclick="copyPromoCode()">
            <i class="fas fa-copy"></i>
            <span class="copy-text">Copy</span>
          </button>
        </div>
        <p class="promo-expiry">
          <i class="fas fa-clock"></i> Valid until December 31, 2024
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ========== MARQUEE TICKER ========== -->
<div class="marquee-strip" aria-hidden="true">
  <div class="marquee-track" id="marqueeTrack">
    <span class="marquee-item"><i class="fas fa-paw"></i> Trusted Veterinary Network <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-calendar-check"></i> Easy line Booking <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-robot"></i> AI Health Insights <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-star"></i> Veterinary Clinics &amp; Groomers <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-shield-alt"></i> Licensed Professionals <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-map-marker-alt"></i> Serving All of Pangasinan <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-file-medical"></i> Digital Pet Health Records <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-bell"></i> Smart Appointment Reminders <span class="marquee-dot"></span></span>
    <!-- Duplicate for seamless loop -->
    <span class="marquee-item"><i class="fas fa-paw"></i> Trusted Veterinary Network <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-calendar-check"></i> Easy line Booking <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-robot"></i> AI Health Insights <span class="marquee-dot"></span></span>
    <span class="marquee-item"><i class="fas fa-star"></i> Veterinary Clinics &amp; Groomers <span class="marquee-dot"></span></span>
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
      <h2>Featured Clinics in Pangasinan</h2>
      <p>Discover top-rated veterinary clinics and grooming centers near you.</p>
    </div>
    <div class="clinics-grid reveal-stagger">
      <?php if ($featuredClinics): foreach ($featuredClinics as $cl): echo renderClinicCard($cl); endforeach; else: ?>
      <div class="empty-state" style="grid-column:1/-1">
        <i class="fas fa-hospital"></i>
        <h3>Clinics coming soon</h3>
        <p>No clinics registered yet. <a href="pages/apply-clinic.php">Apply to register your clinic</a></p>
      </div>
      <?php endif; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= APP_URL ?>/clinics.php" class="btn btn-primary">View All Clinics <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ========== PAW BACKGROUND WRAP (About → CTA) ========== -->
<div class="paw-bg-wrap">

<!-- ========== CTA BANNER ========== -->


</div><!-- /paw-bg-wrap -->

<!-- Promo Code Copy Function -->
<script>
function copyPromoCode() {
  const promoCode = document.getElementById('promoCode').textContent;
  const copyBtn = document.getElementById('copyPromoBtn');
  const copyText = copyBtn.querySelector('.copy-text');
  
  // Copy to clipboard
  navigator.clipboard.writeText(promoCode).then(() => {
    // Change button text to "Copied!"
    copyText.textContent = 'Copied!';
    copyBtn.classList.add('copied');
    
    // Reset after 2 seconds
    setTimeout(() => {
      copyText.textContent = 'Copy';
      copyBtn.classList.remove('copied');
    }, 2000);
  }).catch(err => {
    console.error('Failed to copy:', err);
    alert('Promo code: ' + promoCode);
  });
}
</script>

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

<script>
// ========== HERO IMAGE SLIDER (Auto-rotate every 5 seconds) ==========
(function() {
  // Desktop images
  const desktopImages = [
    'assets/images/landing page/image0.png',  // START IMAGE
    'assets/images/landing page/image1.jpg',
    'assets/images/landing page/image2.jpg',
    'assets/images/landing page/image3.jpg',
    'assets/images/landing page/image4.jpg',
    'assets/images/landing page/image5.jpg',
    'assets/images/landing page/image6.png'
  ];
  
  // Mobile images (portrait-optimized)
  const mobileImages = [
    'assets/images/landing page - for moblie run/mobile0.png',
    'assets/images/landing page - for moblie run/mobile1.png',
    'assets/images/landing page - for moblie run/mobile2.png',
    'assets/images/landing page - for moblie run/mobile3.png',
    'assets/images/landing page - for moblie run/mobile4.png',
    'assets/images/landing page - for moblie run/mobile5.png',
    'assets/images/landing page - for moblie run/mobile6.png'
  ];
  
  // Detect if mobile device (screen width <= 768px)
  const isMobile = window.innerWidth <= 768;
  const images = isMobile ? mobileImages : desktopImages;
  
  let currentIndex = 0;
  const heroImg = document.getElementById('heroBgImg');
  const heroSource = document.querySelector('.hero-bg picture source');
  
  if (heroImg && heroSource) {
    // Set initial image based on device
    heroImg.src = images[0];
    heroSource.srcset = images[0];
    
    // Preload all images
    images.forEach(src => {
      const img = new Image();
      img.src = src;
    });
    
    // Change image every 5 seconds with smooth fade transition
    setInterval(() => {
      currentIndex = (currentIndex + 1) % images.length;
      const nextImage = images[currentIndex];
      
      // Add fade-out effect
      heroImg.style.transition = 'opacity 0.8s ease-in-out';
      heroImg.style.opacity = '0';
      
      // Change image after fade-out
      setTimeout(() => {
        heroImg.src = nextImage;
        heroSource.srcset = nextImage;
        
        // Fade back in
        heroImg.style.opacity = '1';
      }, 800);
      
    }, 5000); // 5000ms = 5 seconds
  }
  
  // Update images on window resize (if user rotates device or resizes browser)
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      const nowMobile = window.innerWidth <= 768;
      if (nowMobile !== isMobile) {
        location.reload(); // Reload to switch image set
      }
    }, 250);
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
