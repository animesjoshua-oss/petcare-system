<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();
$notifCount = $user ? unreadNotificationCount($user['id']) : 0;
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isDashboard = isset($isDashboard) ? (bool)$isDashboard : (strpos($scriptPath, '/dashboard/') !== false || strpos($requestUri, '/dashboard/') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' – ' : '' ?><?= APP_NAME ?></title>
    <meta name="description" content="PetCare+ Clinic Hub – Find veterinary clinics and grooming centers in Pangasinan, Philippines.">
    <meta name="app-url" content="<?= rtrim(str_replace(' ', '%20', APP_URL), '/') ?>">
    <meta name="csrf-token" content="<?= sanitize(generateCsrfToken()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Apply dark mode IMMEDIATELY — before ANY CSS loads to prevent flash -->
    <script>
    (function(){
      var THEME_KEY = 'petcare_theme';

      // Apply theme instantly
      var t = localStorage.getItem(THEME_KEY);
      if (t) { document.documentElement.setAttribute('data-theme', t); }
      else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem(THEME_KEY, 'dark');
      }

      // Define toggleTheme globally so onclick= works before main.js loads
      window.toggleTheme = function() {
        var html    = document.documentElement;
        var current = html.getAttribute('data-theme') || 'light';
        var next    = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem(THEME_KEY, next);
      };
    })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css?v=<?= time() ?>">
    <?= isset($extraCss) ? $extraCss : '' ?>
</head>

<body<?php
  $bodyClasses = [];
  if (isset($isDashboard) && $isDashboard) $bodyClasses[] = 'is-dashboard';
  if (isset($isHeroPage) && $isHeroPage)   $bodyClasses[] = 'hero-page';
  echo $bodyClasses ? ' class="' . implode(' ', $bodyClasses) . '"' : '';
?>>

<!-- ======= Top Navigation ======= -->
<nav class="navbar<?= (isset($isHeroPage) && $isHeroPage) ? ' on-hero-top' : '' ?>" id="mainNav">
    <div class="nav-container">
        <!-- Logo -->
        <?php $logoBase = APP_URL; ?>
        <a href="<?= APP_URL ?>/index.php" class="nav-logo">
            <img src="<?= $logoBase ?>/assets/images/logo.png" alt="PetCare+" class="nav-logo-img">
            <span class="logo-text">PetCare<span class="logo-plus">+</span></span>
        </a>

        <!-- Desktop Menu -->
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?= APP_URL ?>/index.php" class="nav-link">Home</a></li>
            <li><a href="<?= APP_URL ?>/clinics.php" class="nav-link">Clinics</a></li>
            <li><a href="<?= APP_URL ?>/promo-codes.php" class="nav-link">Promo Codes</a></li>
        </ul>

        <!-- Auth Buttons / User Menu -->
        <div class="nav-actions">
            <?php if ($user): ?>
            <!-- Dark mode toggle — always visible for logged-in users -->
            <button class="theme-toggle" id="navThemeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>
            <!-- Notification bell — always visible, outside nav-user-menu so it shows on mobile -->
            <div class="notif-bell-wrap" id="notifBellWrap">
                <button class="notif-bell" id="notifBell" title="Notifications" type="button" aria-label="Notifications" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifCount > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?= $notifCount ?></span>
                    <?php else: ?>
                    <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Notification Dropdown — outside bell-wrap, appended to body via JS -->
            <div class="notif-dropdown" id="notifDropdown" role="dialog" aria-label="Notifications panel">
                <div class="notif-dp-header">
                    <span class="notif-dp-title"><i class="fas fa-bell"></i> Notifications</span>
                    <button class="notif-dp-markall" id="notifMarkAll" type="button">Mark all as read</button>
                </div>
                <div class="notif-dp-list" id="notifList">
                    <div class="notif-dp-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading...</div>
                </div>
                <div class="notif-dp-footer">
                    <a href="<?= APP_URL ?>/notifications.php">View all notifications</a>
                </div>
            </div>

            <div class="nav-user-menu<?= $isDashboard ? ' nav-user-menu--dashboard' : '' ?>">
                <div class="user-avatar-wrap" id="userAvatarBtn">
                    <?php if ($user['profile_photo']): ?>
                    <img src="<?= sanitize($user['profile_photo']) ?>" alt="Avatar" class="user-avatar">
                    <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                    </div>
                    <?php endif; ?>
                    <span class="user-avatar-name"><?= sanitize($user['first_name']) ?></span>
                    <i class="fas fa-chevron-down user-avatar-chevron"></i>
                </div>

                <!-- User Dropdown -->
                <div id="userDropdown" class="user-dropdown-panel">
                    <!-- Header: avatar + name + chevron (mobile only) -->
                    <div class="udp-header">
                        <?php if ($user['profile_photo']): ?>
                        <img src="<?= sanitize($user['profile_photo']) ?>" alt="Avatar" class="udp-avatar">
                        <?php else: ?>
                        <div class="udp-avatar udp-avatar-placeholder">
                            <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                        </div>
                        <?php endif; ?>
                        <span class="udp-name"><?= sanitize($user['first_name']) ?> <?= sanitize($user['last_name']) ?></span>
                        <i class="fas fa-times udp-close-chevron"></i>
                    </div>

                    <!-- Menu items -->
                    <a href="<?= APP_URL ?>/profile.php" class="udp-item">
                        <i class="fas fa-user udp-icon"></i> Profile
                    </a>
                    <?php if ($user['role_name'] === 'pet_owner'): ?>
                    <a href="<?= APP_URL ?>/dashboard/owner/appointments.php" class="udp-item">
                        <i class="fas fa-calendar-alt udp-icon"></i> My Appointments
                    </a>
                    <a href="<?= APP_URL ?>/dashboard/owner/favorites.php" class="udp-item">
                        <i class="fas fa-heart udp-icon"></i> Favorites
                    </a>
                    <?php endif; ?>
                    <?php
                    $dashUrl = APP_URL . '/dashboard/';
                    switch ($user['role_name']) {
                        case 'pet_owner':    $dashUrl .= 'owner/index.php';   break;
                        case 'veterinarian': $dashUrl .= 'vet/index.php';     break;
                        case 'groomer':      $dashUrl .= 'groomer/index.php'; break;
                        default:             $dashUrl .= 'admin/index.php';
                    }
                    ?>
                    <a href="<?= $dashUrl ?>" class="udp-item">
                        <i class="fas fa-tachometer-alt udp-icon"></i> Dashboard
                    </a>

                    <div class="udp-divider"></div>

                    <!-- Dark Mode toggle row -->
                    <div class="udp-item udp-darkmode-row" id="udpDarkModeRow">
                        <i class="fas fa-moon udp-icon"></i>
                        <span>Dark Mode</span>
                        <div class="udp-toggle-switch" id="udpThemeToggle">
                            <div class="udp-toggle-thumb"></div>
                        </div>
                    </div>

                    <div class="udp-divider"></div>

                    <a href="<?= APP_URL ?>/auth/logout.php" class="udp-item udp-logout">
                        <i class="fas fa-sign-out-alt udp-icon"></i> Logout
                    </a>
                </div>
                <!-- Backdrop (mobile only) -->
                <div id="userDropdownBackdrop" class="udp-backdrop"></div>
            </div>
            <?php else: ?>
                <button class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <i class="fas fa-sun icon-sun"></i>
                    <i class="fas fa-moon icon-moon"></i>
                </button>
                <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline">Login</a>
                <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger (only for landing/public pages when NOT logged in) -->
        <!-- Hamburger (non-dashboard pages only) -->
        <?php if (!isset($isDashboard) || !$isDashboard): ?>
        <button class="nav-toggle" id="navToggle" aria-label="Open navigation" aria-controls="mobileMenu" aria-expanded="false" type="button">
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>
        
        <!-- Dashboard sidebar toggle (only for dashboard pages) -->
        <?php if (isset($isDashboard) && $isDashboard): ?>
        <button class="nav-sidebar-toggle" id="navSidebarToggle" aria-label="Open sidebar">
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>
    </div>
</nav>
<!-- End Navbar -->

<!-- Mobile Menu (outside nav, after it) -->
<div class="mobile-menu" id="mobileMenu">
    <a href="<?= APP_URL ?>/index.php" class="mobile-link active"><span class="mobile-icon">🏠</span> Home</a>
    <a href="<?= APP_URL ?>/clinics.php" class="mobile-link"><span class="mobile-icon">🏥</span> Clinics</a>
    <?php if ($user):
        $mobileDashUrl = APP_URL . '/dashboard/';
        switch ($user['role_name']) {
            case 'pet_owner':    $mobileDashUrl .= 'owner/index.php';   break;
            case 'veterinarian': $mobileDashUrl .= 'vet/index.php';     break;
            case 'groomer':      $mobileDashUrl .= 'groomer/index.php'; break;
            default:             $mobileDashUrl .= 'admin/index.php';
        }
    ?>
    <a href="<?= $mobileDashUrl ?>" class="mobile-link"><span class="mobile-icon">📊</span> Dashboard</a>
    <?php endif; ?>
    <div class="mobile-menu-row">
        <a href="<?= APP_URL ?>/index.php#about" class="mobile-link"><span class="mobile-icon">ⓘ</span> About</a>
    </div>
    <?php if (!$user): ?>
    <div class="mobile-menu-auth">
        <div class="mobile-menu-row">
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline">Login</a>
            <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary">Get Started</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
  // User dropdown
  var avatarBtn  = document.getElementById('userAvatarBtn');
  var dropPanel  = document.getElementById('userDropdown');
  var backdrop   = document.getElementById('userDropdownBackdrop');
  var closeChev  = dropPanel ? dropPanel.querySelector('.udp-close-chevron') : null;

  function openDrop() {
    if (!dropPanel) return;
    dropPanel.classList.add('open');
    if (backdrop) backdrop.classList.add('open');
    document.body.style.overflow = window.innerWidth <= 768 ? 'hidden' : '';
    if (window.innerWidth <= 768) document.body.classList.add('udp-open');
  }
  function closeDrop() {
    if (!dropPanel) return;
    dropPanel.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
    document.body.style.overflow = '';
    document.body.classList.remove('udp-open');
  }

  if (avatarBtn) {
    avatarBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropPanel && dropPanel.classList.contains('open') ? closeDrop() : openDrop();
    });
  }
  if (backdrop)  backdrop.addEventListener('click', closeDrop);
  if (closeChev) closeChev.addEventListener('click', function(e) { e.stopPropagation(); closeDrop(); });

  document.addEventListener('click', function(e) {
    if (!dropPanel) return;
    if (dropPanel.classList.contains('open') &&
        !dropPanel.contains(e.target) &&
        !avatarBtn.contains(e.target)) {
      closeDrop();
    }
  });

  // Dark mode toggle inside dropdown
  var dmRow = document.getElementById('udpDarkModeRow');
  if (dmRow) {
    dmRow.addEventListener('click', function(e) {
      e.stopPropagation();
      if (typeof toggleTheme === 'function') toggleTheme();
      // Sync thumb state
      var thumb = document.querySelector('.udp-toggle-thumb');
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (thumb) thumb.parentElement.classList.toggle('on', isDark);
    });
  }

  // Keep toggle thumb in sync with theme on load
  document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var sw = document.getElementById('udpThemeToggle');
    if (sw) sw.classList.toggle('on', isDark);
  });

})();
</script>

<script>
// ── Active nav link — highlight current page ──────────────
(function() {
  var current = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(function(link) {
    var href = link.getAttribute('href') || '';
    // Strip query strings for comparison
    var linkPath = href.split('?')[0];
    if (linkPath && current.indexOf(linkPath.replace(/.*\/petcare-clinic-hub[^/]*/, '')) !== -1
        && linkPath !== '/' && linkPath.length > 1) {
      link.classList.add('active');
    }
  });
  // Homepage: mark Home link active if at root or index.php
  if (current === '/' || current.endsWith('index.php')) {
    var homeLink = document.querySelector('.nav-link[href*="index.php"]');
    if (homeLink) homeLink.classList.add('active');
  }
})();

// ── Notification Dropdown ─────────────────────────────────
<?php if ($user): ?>
(function() {
  var bell      = document.getElementById('notifBell');
  var dropdown  = document.getElementById('notifDropdown');
  var badge     = document.getElementById('notifBadge');
  var list      = document.getElementById('notifList');
  var markAll   = document.getElementById('notifMarkAll');
  var isOpen    = false;
  var isLoaded  = false;

  // Detach dropdown from navbar and re-attach to body
  // so backdrop-filter stacking context doesn't trap it
  if (dropdown && dropdown.parentNode !== document.body) {
    document.body.appendChild(dropdown);
  }
  if (!bell || !dropdown) return;

  // ── Icon map by notification type ──
  function iconFor(type) {
    var map = {
      appointment : 'fa-calendar-check',
      approved    : 'fa-circle-check',
      cancelled   : 'fa-circle-xmark',
      reminder    : 'fa-clock',
      rating      : 'fa-star',
      message     : 'fa-envelope',
      system      : 'fa-gear'
    };
    var key = (type || '').toLowerCase();
    for (var k in map) { if (key.indexOf(k) !== -1) return map[k]; }
    return 'fa-bell';
  }

  // ── Relative time ──
  function timeAgo(dateStr) {
    var diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return 'Just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
  }

  // ── Render notifications ──
  function render(notifs) {
    if (!notifs || notifs.length === 0) {
      list.innerHTML = '<div class="notif-dp-empty"><i class="fas fa-bell-slash"></i><span>No notifications yet</span></div>';
      return;
    }
    var html = '';
    notifs.forEach(function(n) {
      var unread = !parseInt(n.is_read);
      var link = n.link || '';
      html += '<div class="notif-dp-item-wrapper" data-id="' + n.id + '">' +
        '<div class="notif-dp-item' + (unread ? ' unread' : '') + '" data-link="' + link + '" style="cursor:pointer;">' +
          '<div class="notif-dp-icon"><i class="fas ' + iconFor(n.type) + '"></i></div>' +
          '<div class="notif-dp-body">' +
            '<div class="notif-dp-msg">' + (n.message || '') + '</div>' +
            '<div class="notif-dp-time">' + timeAgo(n.created_at) + '</div>' +
          '</div>' +
          (unread ? '<div class="notif-dp-unread-dot"></div>' : '') +
        '</div>' +
        '<button class="notif-dp-delete" data-id="' + n.id + '" title="Delete notification"><i class="fas fa-trash"></i></button>' +
      '</div>';
    });
    list.innerHTML = html;

    // Click item → mark as read and redirect
    list.querySelectorAll('.notif-dp-item').forEach(function(el) {
      var wrapper = el.parentElement;
      var startX = 0, currentX = 0, isDragging = false;
      
      // Touch/Mouse events for swipe
      el.addEventListener('touchstart', handleStart, { passive: true });
      el.addEventListener('mousedown', handleStart);
      
      function handleStart(e) {
        startX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        isDragging = true;
        el.style.transition = 'none';
      }
      
      el.addEventListener('touchmove', handleMove, { passive: false });
      el.addEventListener('mousemove', handleMove);
      
      function handleMove(e) {
        if (!isDragging) return;
        currentX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        var diff = currentX - startX;
        
        // Only allow left swipe (negative diff)
        if (diff < 0 && diff > -100) {
          e.preventDefault();
          el.style.transform = 'translateX(' + diff + 'px)';
        }
      }
      
      el.addEventListener('touchend', handleEnd);
      el.addEventListener('mouseup', handleEnd);
      el.addEventListener('mouseleave', handleEnd);
      
      function handleEnd(e) {
        if (!isDragging) return;
        isDragging = false;
        el.style.transition = 'transform 0.3s ease';
        
        var diff = currentX - startX;
        
        // If swiped more than 60px to the left, show delete button
        if (diff < -60) {
          el.style.transform = 'translateX(-80px)';
          wrapper.classList.add('swiped');
        } else {
          el.style.transform = 'translateX(0)';
          wrapper.classList.remove('swiped');
        }
        
        currentX = 0;
        startX = 0;
      }
      
      // Click to navigate
      el.addEventListener('click', function(e) {
        // Don't navigate if swiped
        if (wrapper.classList.contains('swiped')) {
          el.style.transform = 'translateX(0)';
          wrapper.classList.remove('swiped');
          return;
        }
        
        var id = wrapper.dataset.id;
        var link = el.dataset.link;
        
        // Mark as read
        fetch('<?= APP_URL ?>/api/notifications.php?action=mark_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
          body: JSON.stringify({ id: id })
        }).then(function() {
          // Redirect to notification link if available
          if (link && link !== '' && link !== '#') {
            window.location.href = link;
          }
        });
        
        el.classList.remove('unread');
        var dot = el.querySelector('.notif-dp-unread-dot');
        if (dot) dot.remove();
        updateBadge();
      });
    });
    
    // Delete button click
    list.querySelectorAll('.notif-dp-delete').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var id = btn.dataset.id;
        var wrapper = btn.parentElement;
        
        // Animate removal
        wrapper.style.transition = 'all 0.3s ease';
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateX(-100%)';
        
        setTimeout(function() {
          // Delete from server
          fetch('<?= APP_URL ?>/api/notifications.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ id: id })
          }).then(function() {
            wrapper.remove();
            updateBadge();
            
            // Check if no notifications left
            if (list.querySelectorAll('.notif-dp-item-wrapper').length === 0) {
              list.innerHTML = '<div class="notif-dp-empty"><i class="fas fa-bell-slash"></i><span>No notifications yet</span></div>';
            }
          });
        }, 300);
      });
    });
  }

  // ── Update badge count ──
  function updateBadge() {
    var unreadCount = list.querySelectorAll('.notif-dp-item.unread').length;
    if (unreadCount > 0) {
      badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
      badge.style.display = '';
    } else {
      badge.style.display = 'none';
    }
  }

  // ── Fetch from API ──
  function fetchNotifs() {
    list.innerHTML = '<div class="notif-dp-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading...</div>';
    fetch('<?= APP_URL ?>/api/notifications.php?action=list', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      render(data.notifications || []);
      var count = data.unread || 0;
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = '';
      } else {
        badge.style.display = 'none';
      }
      isLoaded = true;
    })
    .catch(function() {
      list.innerHTML = '<div class="notif-dp-empty"><i class="fas fa-triangle-exclamation"></i> Could not load notifications</div>';
    });
  }

  // ── Open / Close ──
  function positionDropdown() {
    var rect     = bell.getBoundingClientRect();
    var winW     = window.innerWidth;
    var dropW    = Math.min(320, winW - 16);
    var leftPos  = rect.right - dropW;
    if (leftPos < 8) leftPos = 8;
    dropdown.style.position = 'fixed';
    dropdown.style.top      = (rect.bottom + 8) + 'px';
    dropdown.style.left     = leftPos + 'px';
    dropdown.style.right    = 'auto';
    dropdown.style.width    = dropW + 'px';
  }

  function openDropdown() {
    positionDropdown();
    dropdown.classList.add('open');
    bell.classList.add('active');
    bell.setAttribute('aria-expanded', 'true');
    isOpen = true;
    fetchNotifs();
  }
  function closeDropdown() {
    dropdown.classList.remove('open');
    bell.classList.remove('active');
    bell.setAttribute('aria-expanded', 'false');
    isOpen = false;
  }

  bell.addEventListener('click', function(e) {
    e.stopPropagation();
    isOpen ? closeDropdown() : openDropdown();
  });

  document.addEventListener('click', function(e) {
    if (isOpen && !dropdown.contains(e.target) && !bell.contains(e.target)) {
      closeDropdown();
    }
  });

  // ── Mark all as read ──
  markAll && markAll.addEventListener('click', function(e) {
    e.stopPropagation();
    fetch('<?= APP_URL ?>/api/notifications.php?action=mark_read', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({})
    });
    list.querySelectorAll('.notif-dp-item').forEach(function(el) {
      el.classList.remove('unread');
      var dot = el.querySelector('.notif-dp-unread-dot');
      if (dot) dot.remove();
    });
    badge.style.display = 'none';
  });

  // ── Background polling every 60s ──
  setInterval(function() {
    fetch('<?= APP_URL ?>/api/notifications.php?action=list', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var count = data.unread || 0;
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = '';
        var bellIcon = bell.querySelector('.fa-bell');
        if (bellIcon) {
          bellIcon.classList.add('notif-pulse');
          setTimeout(function() { bellIcon.classList.remove('notif-pulse'); }, 1000);
        }
      } else {
        badge.style.display = 'none';
      }
    })
    .catch(function() {});
  }, 60000);

})();
<?php endif; ?>
</script>
