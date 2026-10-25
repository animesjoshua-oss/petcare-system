<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Promo Codes & Deals';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ========== PROMO CODES PAGE ========== -->
<section class="promo-section" id="promo-section" style="padding-top: 120px; min-height: 100vh;">
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
      <p style="color: #94a3b8; font-size: 14px;">Verified deals from trusted petcare partners in Pangasinan.</p>
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
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=PETCARE10" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
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
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=GROOM15" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
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
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=PAWS150" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
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
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=HEALTHY" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
          </div>
        </div>
      </div>

      <!-- Promo Card 5: Vaccination -->
      <div class="promo-card" data-category="veterinary">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-syringe"></i>
          </div>
          <span class="promo-card-label">Veterinary</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">20% OFF</h3>
          <p class="promo-card-desc">Pet Vaccination</p>
          <p class="promo-card-details">Save 20% on all vaccination packages for your pets.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Dec 31, 2026
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code5">VAXX20</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('VAXX20', 'code5')">
                <i class="far fa-copy"></i>
              </button>
            </div>
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=VAXX20" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
          </div>
        </div>
      </div>

      <!-- Promo Card 6: Dental Care -->
      <div class="promo-card" data-category="wellness">
        <div class="promo-card-header" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
          <div class="promo-card-icon">
            <i class="fas fa-tooth"></i>
          </div>
          <span class="promo-card-label">Health & Wellness</span>
        </div>
        <div class="promo-card-body">
          <h3 class="promo-card-title">₱200 OFF</h3>
          <p class="promo-card-desc">Dental Cleaning</p>
          <p class="promo-card-details">Professional dental cleaning service with ₱200 discount.</p>
          <div class="promo-card-validity">
            <i class="far fa-calendar"></i> Valid until Jan 31, 2027
          </div>
          <div class="promo-card-footer">
            <div class="promo-code-box">
              <code class="promo-code" id="code6">DENTAL200</code>
              <button class="promo-copy-btn" onclick="copyPromoCode('DENTAL200', 'code6')">
                <i class="far fa-copy"></i>
              </button>
            </div>
            <a href="<?= APP_URL ?>/dashboard/owner/book.php?promo=DENTAL200" class="promo-use-btn">
              <i class="fas fa-calendar-check"></i> Use Now
            </a>
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
