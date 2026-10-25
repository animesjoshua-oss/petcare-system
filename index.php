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

<!-- ========== PROMO CODES SECTION ========== -->
<section class="section promo-section" id="promo-section">
  <div class="container">
    
    <!-- Section Header -->
    <div class="section-header text-center reveal">
      <div class="promo-badge">
        <i class="fas fa-tag"></i> PetCare+ Deals
      </div>
      <h2 class="promo-main-title">
        Save <span class="promo-highlight">More</span> on PetCare
      </h2>
      <p class="promo-subtitle">
        Discover deals on veterinary visits, grooming, pet supplies, and wellness.<br>
        Quality care for a happier, healthier pet.
      </p>
    </div>

    <!-- Search Bar -->
    <div class="promo-search-wrapper reveal">
      <div class="promo-search-box">
        <i class="fas fa-search promo-search-icon"></i>
        <input type="text" id="promoSearchInput" placeholder="Search promo codes or pet services..." class="promo-search-input">
        <button class="promo-search-btn">Search</button>
      </div>
    </div>

    <!-- Category Filters -->
    <div class="promo-categories reveal-stagger">
      <button class="promo-category-btn active" data-category="all">
        <i class="fas fa-heart"></i>
        <span>All Deals</span>
      </button>
      <button class="promo-category-btn" data-category="veterinary">
        <i class="fas fa-stethoscope"></i>
        <span>Veterinary</span>
      </button>
      <button class="promo-category-btn" data-category="grooming">
        <i class="fas fa-cut"></i>
        <span>Grooming</span>
      </button>
      <button class="promo-category-btn" data-category="supplies">
        <i class="fas fa-box"></i>
        <span>Pet Supplies</span>
      </button>
      <button class="promo-category-btn" data-category="wellness">
        <i class="fas fa-spa"></i>
        <span>Health & Wellness</span>
      </button>
    </div>

    <!-- Featured Promo Codes -->
    <div class="promo-featured-header reveal">
      <h3><i class="fas fa-star"></i> Featured Promo Codes</h3>
      <a href="#" class="promo-view-all">View All Deals <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Promo Cards Grid -->
    <div class="promo-grid reveal-stagger" id="promoGrid">
      
      <!-- Promo Card 1: Veterinary -->
      <div class="promo-card" data-category="veterinary">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-stethoscope"></i>
          </div>
          <span class="promo-card-label">Veterinary</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">10% OFF</h3>
          <p class="promo-card-desc">Veterinary Checkup</p>
          <p class="promo-card-details">Get 10% off on general veterinary checkup services.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Dec 31, 2026
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code1">PETCARE10</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('PETCARE10', 'code1')">
                <i class="far fa-copy"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Promo Card 2: Grooming -->
      <div class="promo-card" data-category="grooming">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-cut"></i>
          </div>
          <span class="promo-card-label">Grooming</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">15% OFF</h3>
          <p class="promo-card-desc">Grooming Service</p>
          <p class="promo-card-details">Enjoy 15% off on all grooming services for dogs and cats.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Nov 30, 2026
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code2">GROOM15</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('GROOM15', 'code2')">
                <i class="far fa-copy"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Promo Card 3: Pet Supplies -->
      <div class="promo-card" data-category="supplies">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-box"></i>
          </div>
          <span class="promo-card-label">Pet Supplies</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">₱150 OFF</h3>
          <p class="promo-card-desc">Pet Supplies</p>
          <p class="promo-card-details">Get ₱150 off on orders ₱1,000 and above.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Dec 15, 2026
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code3">PAWS150</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('PAWS150', 'code3')">
                <i class="far fa-copy"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Promo Card 4: Wellness -->
      <div class="promo-card" data-category="wellness">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-spa"></i>
          </div>
          <span class="promo-card-label">Health & Wellness</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">Free</h3>
          <p class="promo-card-desc">Wellness Consultation</p>
          <p class="promo-card-details">Get a free wellness consultation for new clients.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Nov 30, 2026
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code4">HEALTHY</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('HEALTHY', 'code4')">
                <i class="far fa-copy"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /promo-grid -->

    <!-- How It Works -->
    <div class="promo-how-it-works reveal">
      <h3 class="promo-section-subtitle">
        <i class="fas fa-lightbulb"></i> How It Works
      </h3>
      <div class="promo-steps">
        <div class="promo-step">
          <div class="promo-step-number">1</div>
          <div class="promo-step-icon"><i class="fas fa-search"></i></div>
          <h4>Find a deal</h4>
          <p>Browse and choose a promo code that fits your pet's needs.</p>
        </div>
        <div class="promo-step">
          <div class="promo-step-number">2</div>
          <div class="promo-step-icon"><i class="far fa-copy"></i></div>
          <h4>Copy your code</h4>
          <p>Click the copy button to get the promo code.</p>
        </div>
        <div class="promo-step">
          <div class="promo-step-number">3</div>
          <div class="promo-step-icon"><i class="fas fa-shopping-cart"></i></div>
          <h4>Redeem at checkout</h4>
          <p>Use the code when booking or purchasing to enjoy your discount.</p>
        </div>
      </div>
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

<!-- ========== PROMO CODE FUNCTIONALITY ========== -->
<script>
// Copy promo code to clipboard
function copyPromoCode(code, elementId) {
  const codeElement = document.getElementById(elementId);
  const btn = codeElement.nextElementSibling;
  
  // Copy to clipboard
  navigator.clipboard.writeText(code).then(() => {
    // Show success feedback
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    btn.classList.add('copied');
    
    // Add flash animation to code
    codeElement.style.animation = 'promoFlash 0.5s ease';
    
    // Reset after 2 seconds
    setTimeout(() => {
      btn.innerHTML = originalHTML;
      btn.classList.remove('copied');
      codeElement.style.animation = '';
    }, 2000);
    
    // Show toast notification
    showToast('Promo code copied! ' + code);
  }).catch(err => {
    console.error('Failed to copy:', err);
    showToast('Failed to copy code', 'error');
  });
}

// Show toast notification
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = 'promo-toast promo-toast-' + type;
  toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
  document.body.appendChild(toast);
  
  setTimeout(() => toast.classList.add('show'), 100);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Category filter functionality
document.addEventListener('DOMContentLoaded', function() {
  const categoryBtns = document.querySelectorAll('.promo-category-btn');
  const promoCards = document.querySelectorAll('.promo-card');
  
  categoryBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const category = this.dataset.category;
      
      // Update active button
      categoryBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      
      // Filter cards
      promoCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
          card.style.display = 'block';
          setTimeout(() => card.classList.add('reveal'), 10);
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
  
  // Search functionality
  const searchInput = document.getElementById('promoSearchInput');
  const searchBtn = document.querySelector('.promo-search-btn');
  
  function performSearch() {
    const searchTerm = searchInput.value.toLowerCase();
    
    promoCards.forEach(card => {
      const title = card.querySelector('.promo-card-title').textContent.toLowerCase();
      const desc = card.querySelector('.promo-card-desc').textContent.toLowerCase();
      const details = card.querySelector('.promo-card-details').textContent.toLowerCase();
      const code = card.querySelector('.promo-code').textContent.toLowerCase();
      
      if (title.includes(searchTerm) || desc.includes(searchTerm) || 
          details.includes(searchTerm) || code.includes(searchTerm)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }
  
  searchBtn.addEventListener('click', performSearch);
  searchInput.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
      performSearch();
    }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
