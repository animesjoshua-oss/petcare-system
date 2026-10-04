/**
 * PetCare+ Main JavaScript
 * Handles theme system, navigation, modals, and UI interactions
 * Last updated: December 2024
 */

'use strict';

// Theme system - light/dark mode
const THEME_KEY = 'petcare_theme';

/**
 * Apply theme to the document
 * @param {string} theme - 'light' or 'dark'
 * @param {boolean} animate - Whether to animate the transition
 */
function applyTheme(theme, animate = false) {
  const html = document.documentElement;

  if (animate) {
    html.classList.add('theme-transitioning');
    setTimeout(() => html.classList.remove('theme-transitioning'), 350);
  }

  html.setAttribute('data-theme', theme);
  localStorage.setItem(THEME_KEY, theme);

  // Update all theme toggle buttons
  document.querySelectorAll('.theme-toggle').forEach((btn) => {
    const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    btn.setAttribute('aria-label', label);
    btn.title = label;
  });

  // Sync dropdown toggle if exists
  const sw = document.getElementById('udpThemeToggle');
  if (sw) sw.classList.toggle('on', theme === 'dark');

  // ============================================================
  // HERO IMAGE THEME SWITCH - Change image when theme changes
  // ============================================================
  const heroImg = document.getElementById('heroBgImg');
  if (heroImg) {
    const lightSrc = heroImg.getAttribute('data-light-src');
    const darkSrc = heroImg.getAttribute('data-dark-src');
    
    if (lightSrc && darkSrc) {
      // Smoothly fade out, change image, fade back in
      heroImg.style.opacity = '0';
      setTimeout(() => {
        heroImg.src = theme === 'dark' ? darkSrc : lightSrc;
        // Fade back in
        setTimeout(() => {
          heroImg.style.opacity = '1';
        }, 50);
      }, 250);
    }
  }
}

/**
 * Toggle between light and dark theme
 */
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  applyTheme(current === 'dark' ? 'light' : 'dark', true);
}

/**
 * Initialize theme on page load
 */
function initTheme() {
  const saved = localStorage.getItem(THEME_KEY);
  if (saved) {
    applyTheme(saved, false);
    return;
  }
  // Fallback to system preference
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyTheme(prefersDark ? 'dark' : 'light', false);
}

// Run immediately (before DOMContentLoaded) to prevent flash
initTheme();

// Listen for OS preference changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
  // Only follow OS if user hasn't set a manual preference
  if (!localStorage.getItem(THEME_KEY)) {
    applyTheme(e.matches ? 'dark' : 'light', true);
  }
});

/**
 * Create a theme toggle button element
 * @returns {HTMLButtonElement} Theme toggle button
 */
function createThemeToggle() {
  const btn = document.createElement('button');
  btn.className = 'theme-toggle';
  btn.setAttribute('aria-label', 'Toggle dark mode');
  btn.title = 'Toggle dark mode';
  btn.type = 'button';
  btn.innerHTML = `
    <i class="fas fa-sun icon-sun" aria-hidden="true"></i>
    <i class="fas fa-moon icon-moon" aria-hidden="true"></i>`;
  btn.addEventListener('click', toggleTheme);
  return btn;
}

/**
 * Inject toggle into navbar .nav-actions once DOM is ready
 */
function injectNavbarToggle() {
  // Static .theme-toggle buttons are now in header.php for both guest + logged-in
  // Just wire click events — no injection needed
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    if (!btn.dataset.themeWired) {
      btn.dataset.themeWired = '1';
      btn.addEventListener('click', toggleTheme);
    }
  });
}


// ============================================================
// NAVBAR SCROLL EFFECT
// ============================================================
const navbar = document.getElementById('mainNav');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 20);
  });
}

// ============================================================
// MOBILE NAVIGATION TOGGLE
// ============================================================
let mobileNavOpen = false;

/**
 * Close mobile navigation menu
 */
function closeMobileNav() {
  document.getElementById('mobileMenu')?.classList.remove('open');
  const button = document.getElementById('navToggle');
  button?.classList.remove('open');
  button?.setAttribute('aria-expanded', 'false');
  button?.setAttribute('aria-label', 'Open navigation');
  mobileNavOpen = false;
}

/**
 * Toggle mobile navigation menu
 */
function toggleMobileNav() {
  if (mobileNavOpen) {
    closeMobileNav();
  } else {
    document.getElementById('mobileMenu')?.classList.add('open');
    const button = document.getElementById('navToggle');
    button?.classList.add('open');
    button?.setAttribute('aria-expanded', 'true');
    button?.setAttribute('aria-label', 'Close navigation');
    mobileNavOpen = true;
  }
}

/**
 * Execute function when DOM is ready
 * @param {Function} fn - Function to execute
 */
function ready(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    fn();
  }
}

// Close mobile menu when a link is clicked
ready(() => {
  // Wire hamburger button
  document.getElementById('navToggle')?.addEventListener('click', toggleMobileNav);

  // Close when any mobile link is clicked
  document.querySelectorAll('.mobile-link').forEach(link => {
    link.addEventListener('click', () => closeMobileNav());
  });
});

// Close nav when clicking outside the navbar
document.addEventListener('click', e => {
  if (!mobileNavOpen) return;
  const nav = document.getElementById('mainNav');
  if (nav && !nav.contains(e.target)) {
    closeMobileNav();
  }
});

// Close nav with Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && mobileNavOpen) {
    closeMobileNav();
  }
});

// ---- Sidebar toggle (mobile) ----
function createSidebarOverlay() {
  const existing = document.querySelector('.sidebar-overlay');
  if (existing) return existing;
  const overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  overlay.onclick = () => {
    const sidebar = document.querySelector('.sidebar');
    const button  = document.getElementById('navSidebarToggle');
    sidebar?.classList.remove('open');
    overlay.classList.remove('open');
    button?.classList.remove('active');
    document.body.classList.remove('sidebar-open');
    document.body.style.overflow = '';
  };
  document.body.appendChild(overlay);
  return overlay;
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;
  const overlay = createSidebarOverlay();
  const button  = document.getElementById('navSidebarToggle');
  const isOpen  = sidebar.classList.contains('open');
  sidebar.classList.toggle('open', !isOpen);
  overlay.classList.toggle('open', !isOpen);
  button?.classList.toggle('active', !isOpen);
  document.body.classList.toggle('sidebar-open', !isOpen);
  document.body.style.overflow = !isOpen ? 'hidden' : '';
}

function attachSidebarToggle() {
  const button = document.getElementById('navSidebarToggle');
  if (button && !button.dataset.sidebarWired) {
    button.dataset.sidebarWired = '1';
    button.addEventListener('click', toggleSidebar);
  }
}

// Auto-close sidebar when a sidebar link is clicked on mobile
document.addEventListener('click', e => {
  if (e.target.closest('.sidebar .sidebar-link')) {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const button  = document.getElementById('navSidebarToggle');
    if (sidebar?.classList.contains('open')) {
      sidebar.classList.remove('open');
      overlay?.classList.remove('open');
      button?.classList.remove('active');
      document.body.classList.remove('sidebar-open');
      document.body.style.overflow = '';
    }
  }
});

// Inject sidebar overlay into dashboard pages and wire dashboard toggles
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) {
    createSidebarOverlay();
  }
  attachSidebarToggle();
});

// ---- Modal helpers ----
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ---- Toast notifications ----
function showToast(message, type = 'info', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fas ${icons[type] || icons.info} toast-icon"></i>
    <span class="toast-msg">${message}</span>
    <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), duration);
}

// ---- Password toggle ----
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.input-group')?.querySelector('input');
    if (!input) return;
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
  });
});

// ---- Active nav link (homepage anchors) ----
const sections = document.querySelectorAll('section[id]');
if (sections.length) {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        document.querySelectorAll('.nav-link').forEach(link => {
          link.classList.toggle('active', link.getAttribute('href')?.includes('#' + entry.target.id));
        });
      }
    });
  }, { threshold: 0.4 });
  sections.forEach(s => observer.observe(s));
}

// ---- Smooth scroll for anchor links ----
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

// ---- Star rating input ----
document.querySelectorAll('.star-rating-input').forEach(group => {
  const stars = group.querySelectorAll('.star-btn');
  const input = group.querySelector('input[type="hidden"]');
  stars.forEach((star, idx) => {
    star.addEventListener('click', () => {
      if (input) input.value = idx + 1;
      stars.forEach((s, i) => s.classList.toggle('active', i <= idx));
    });
    star.addEventListener('mouseover', () => stars.forEach((s, i) => s.classList.toggle('hover', i <= idx)));
    star.addEventListener('mouseout',  () => stars.forEach(s => s.classList.remove('hover')));
  });
});

// ---- File upload preview ----
document.querySelectorAll('.upload-area input[type="file"]').forEach(input => {
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const preview = input.closest('.upload-area')?.querySelector('.upload-preview');
    if (preview) {
      const reader = new FileReader();
      reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
      reader.readAsDataURL(file);
    }
  });
});

// ---- Confirm delete dialogs ----
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
});

// ---- Auto-dismiss alerts ----
document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
  setTimeout(() => el.remove(), parseInt(el.dataset.autoDismiss) || 4000);
});

// ---- Tabs ----
document.querySelectorAll('.tab-nav').forEach(nav => {
  nav.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      nav.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const panel = document.getElementById(target);
      if (panel) {
        panel.closest('.tabs-wrap')?.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        panel.classList.add('active');
      }
    });
  });
});

// ---- Fetch wrapper (JSON API calls) ----
async function apiCall(url, method = 'GET', body = null) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const options = { method, headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken } };
  if (body) options.body = JSON.stringify(body);
  const res  = await fetch(url, options);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Request failed');
  return data;
}

// ---- Appointment status change via AJAX ----
document.querySelectorAll('.appt-status-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const { appointmentId, action } = btn.dataset;
    if (!confirm(`Mark appointment as ${action}?`)) return;
    try {
      const appUrl = document.querySelector('meta[name="app-url"]')?.content || '';
      const data = await apiCall(`${appUrl}/api/appointments.php`, 'POST', { appointment_id: appointmentId, action });
      showToast(data.message || 'Updated!', 'success');
      setTimeout(() => location.reload(), 1000);
    } catch (err) {
      showToast(err.message, 'error');
    }
  });
});

// ---- Search filter debounce ----
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

const searchInput = document.getElementById('clinicSearchInput');
if (searchInput) {
  searchInput.addEventListener('input', debounce(e => {
    const q = e.target.value.toLowerCase();
    let visibleCount = 0;
    document.querySelectorAll('.clinic-card').forEach(card => {
      const name = card.querySelector('.clinic-card-title')?.textContent.toLowerCase() || '';
      const addr = card.querySelector('.clinic-card-addr')?.textContent.toLowerCase() || '';
      const visible = name.includes(q) || addr.includes(q);
      const wrapper = card.closest('.col, li, .clinic-card-wrap') || card;
      wrapper.style.display = visible ? '' : 'none';
      card.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    // Show / hide empty state message
    let emptyMsg = document.getElementById('clinicSearchEmpty');
    if (!emptyMsg) {
      emptyMsg = document.createElement('div');
      emptyMsg.id = 'clinicSearchEmpty';
      emptyMsg.className = 'empty-state';
      emptyMsg.style.gridColumn = '1/-1';
      emptyMsg.innerHTML = '<i class="fas fa-search"></i><h3>No clinics found</h3><p>Try a different search term.</p>';
      const grid = document.querySelector('.clinics-grid');
      if (grid) grid.appendChild(emptyMsg);
    }
    emptyMsg.style.display = visibleCount === 0 && q ? 'block' : 'none';
  }));
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', () => {
  // Mark active sidebar link — compare pathnames to avoid query-string mismatches
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    const linkPath = (link.getAttribute('href') || '').split('?')[0];
    if (linkPath && linkPath.length > 1 && currentPath.endsWith(linkPath.split('/').pop())) {
      // More precise: match the last segment of the href path
      const linkFile = linkPath.split('/').pop();
      const currFile = currentPath.split('/').pop().split('?')[0];
      if (linkFile && linkFile === currFile) {
        link.classList.add('active');
      }
    }
  });

  // If page has a sidebar, mark body as dashboard page
  if (document.querySelector('.sidebar')) {
    document.body.classList.add('is-dashboard');
  }

  // If user is logged in (has nav-user-menu), mark body
  if (document.querySelector('.nav-user-menu')) {
    document.body.classList.add('logged-in');
  }

  // Inject theme toggle into navbar
  injectNavbarToggle();

  // Sidebar collapse toggle (desktop) / close toggle (mobile)
  const collapseBtn = document.getElementById('sidebarCollapseBtn');
  if (collapseBtn) {
    collapseBtn.addEventListener('click', () => {
      const sidebar = document.querySelector('.sidebar');
      if (!sidebar) return;

      if (window.innerWidth <= 768) {
        // On mobile: close the drawer
        const overlay = document.querySelector('.sidebar-overlay');
        const navBtn  = document.getElementById('navSidebarToggle');
        sidebar.classList.remove('open');
        overlay?.classList.remove('open');
        navBtn?.classList.remove('active');
        document.body.classList.remove('sidebar-open');
        document.body.style.overflow = '';
      } else {
        // On desktop: collapse/expand
        const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
        collapseBtn.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
      }
    });
  }

  // Restore sidebar collapse state
  const sidebar = document.querySelector('.sidebar');
  if (sidebar && localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar.classList.add('sidebar-collapsed');
    const btn = document.getElementById('sidebarCollapseBtn');
    if (btn) btn.title = 'Expand sidebar';
  }
});


/* ============================================================
   PWA — Service Worker Registration
   (Handled by footer.php with correct APP_URL base path)
   ============================================================ */

/* ============================================================
   PWA — Install Prompt (Add to Home Screen)
   ============================================================ */
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;

  // Show a subtle install banner if not already installed
  showInstallBanner();
});

function showInstallBanner() {
  if (document.getElementById('pwa-install-banner')) return;

  // Derive base URL from a known meta tag or from location origin + path segment
  var logoUrl = (function() {
    var base = document.querySelector('meta[name="app-url"]');
    if (base) return base.content + '/assets/logo/petcare.png';
    // Fallback: find the logo from the navbar (already loaded correctly)
    var navLogo = document.querySelector('.nav-logo-img');
    if (navLogo) return navLogo.src;
    return '/assets/logo/petcare.png';
  })();

  const banner = document.createElement('div');
  banner.id = 'pwa-install-banner';
  banner.innerHTML = `
    <div style="
      position:fixed; bottom:1rem; left:50%; transform:translateX(-50%);
      background:rgba(8,18,33,0.95); color:#fff;
      padding:0.85rem 1.25rem; border-radius:14px;
      display:flex; align-items:center; gap:0.75rem;
      box-shadow:0 8px 32px rgba(0,0,0,0.4);
      border:1px solid rgba(0,212,170,0.3);
      z-index:9999; font-family:Poppins,sans-serif;
      font-size:0.85rem; max-width:calc(100vw - 2rem);
      backdrop-filter:blur(16px);
    ">
      <img src="${logoUrl}" style="width:36px;height:36px;border-radius:8px;object-fit:contain;background:#081d35;padding:2px;">
      <div>
        <strong style="display:block;font-size:0.88rem;">Install PetCare+</strong>
        <span style="opacity:0.75;font-size:0.78rem;">Add to home screen for quick access</span>
      </div>
      <button id="pwa-install-btn" style="
        background:#00D4AA; color:#081221; border:none;
        padding:0.45rem 1rem; border-radius:50px;
        font-weight:700; font-size:0.8rem; cursor:pointer;
        white-space:nowrap; font-family:inherit;
      ">Install</button>
      <button id="pwa-dismiss-btn" style="
        background:none; border:none; color:rgba(255,255,255,0.6);
        font-size:1.1rem; cursor:pointer; padding:0.2rem 0.4rem;
        line-height:1;
      ">✕</button>
    </div>`;
  document.body.appendChild(banner);

  document.getElementById('pwa-install-btn').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('[PetCare+ PWA] Install outcome:', outcome);
    deferredPrompt = null;
    banner.remove();
  });

  document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
    banner.remove();
    // Don't show again for 7 days
    localStorage.setItem('pwa_dismissed', Date.now());
  });
}

// Don't show if dismissed within last 7 days
window.addEventListener('DOMContentLoaded', () => {
  const dismissed = localStorage.getItem('pwa_dismissed');
  if (dismissed && Date.now() - parseInt(dismissed) < 7 * 24 * 60 * 60 * 1000) {
    window.removeEventListener('beforeinstallprompt', showInstallBanner);
  }
});

// Hide banner if already installed
window.addEventListener('appinstalled', () => {
  const banner = document.getElementById('pwa-install-banner');
  if (banner) banner.remove();
  console.log('[PetCare+ PWA] App installed successfully!');
});


/* ============================================================
   PETLINK-STYLE ANIMATIONS
   Scroll-reveal · Paw particles · Stat counters
   ============================================================ */

/* ── Scroll-reveal via IntersectionObserver ─────────────── */
(function initScrollReveal() {
  if (typeof IntersectionObserver === 'undefined') {
    // Fallback: just show everything immediately
    document.querySelectorAll('.reveal, .reveal-stagger, .section-header').forEach(el => {
      el.classList.add('reveal--done');
    });
    return;
  }

  const THRESHOLD = 0.15; // 15% of element visible before trigger

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('reveal--done');
      observer.unobserve(entry.target); // fire once only
    });
  }, { threshold: THRESHOLD, rootMargin: '0px 0px -40px 0px' });

  // Observe .reveal elements
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // Observe .reveal-stagger grids
  document.querySelectorAll('.reveal-stagger').forEach(el => observer.observe(el));

  // Observe section headers
  document.querySelectorAll('.section-header').forEach(el => {
    el.classList.add('reveal'); // ensure base reveal styles apply
    observer.observe(el);
  });
})();

/* ── Paw particle spawner ───────────────────────────────── */
(function initPawParticles() {
  const container = document.getElementById('pawParticles');
  if (!container) return;

  // Respect reduced-motion preference
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const PAWS = ['🐾', '🐾', '🐾', '🐾', '🐾'];
  const COUNT = 14;

  for (let i = 0; i < COUNT; i++) {
    const el = document.createElement('span');
    el.className = 'paw-particle';
    el.textContent = PAWS[i % PAWS.length];

    // Random horizontal position
    el.style.left = `${Math.random() * 100}%`;
    // Random bottom start (so they emerge from different heights)
    el.style.bottom = `${Math.random() * 30}%`;

    // Random size variation
    const size = 0.9 + Math.random() * 1.2;
    el.style.fontSize = `${size}rem`;

    // Random duration 6–12s, random delay 0–10s
    const dur   = (6 + Math.random() * 6).toFixed(1);
    const delay = (Math.random() * 10).toFixed(1);
    el.style.setProperty('--dur',   `${dur}s`);
    el.style.setProperty('--delay', `${delay}s`);

    container.appendChild(el);
  }
})();

/* ── Stat counter animation in About section ────────────── */
(function initStatCounters() {
  // Targets: elements with data-count attribute inside .about-visual
  const about = document.querySelector('.about-visual');
  if (!about) return;

  // Wrap each stat number so we can animate it
  about.querySelectorAll('strong').forEach(el => {
    const raw = el.textContent.trim();           // e.g. "5K+", "4.8★", "200+"
    const numMatch = raw.match(/^[\d.]+/);
    if (!numMatch) return;

    const target   = parseFloat(numMatch[0]);
    const suffix   = raw.slice(numMatch[0].length); // "+", "K+", "★", etc.
    const isFloat  = raw.includes('.');
    const duration = 1400; // ms
    const steps    = 40;
    const interval = duration / steps;
    let   current  = 0;

    el.dataset.target  = target;
    el.dataset.suffix  = suffix;
    el.dataset.counted = 'false';
    el.classList.add('stat-pop');
    el.textContent = '0' + suffix; // reset to 0 so animation is visible

    el._startCount = function() {
      if (el.dataset.counted === 'true') return;
      el.dataset.counted = 'true';
      let step = 0;
      const timer = setInterval(() => {
        step++;
        current = target * (step / steps);
        const display = isFloat ? current.toFixed(1) : Math.round(current);
        el.textContent = display + suffix;
        if (step >= steps) {
          clearInterval(timer);
          el.textContent = raw; // snap to exact final value
          el.classList.add('counting');
          setTimeout(() => el.classList.remove('counting'), 400);
        }
      }, interval);
    };
  });

  // Trigger counters when about section enters view
  if (typeof IntersectionObserver === 'undefined') {
    about.querySelectorAll('strong[data-counted]').forEach(el => el._startCount && el._startCount());
    return;
  }

  const counterObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.querySelectorAll('strong[data-counted]').forEach(el => {
        if (el._startCount) el._startCount();
      });
      counterObs.unobserve(entry.target);
    });
  }, { threshold: 0.3 });

  counterObs.observe(about);
})();
