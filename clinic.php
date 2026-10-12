<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: clinics.php'); exit; }

$stmt = getDB()->prepare('SELECT c.* FROM clinics c WHERE c.id=? AND c.is_active=1');
$stmt->execute([$id]);
$clinic = $stmt->fetch();
if (!$clinic) { header('Location: clinics.php'); exit; }

$pageTitle = $clinic['name'];

// Vets (with average rating)
$vets = getDB()->prepare('
    SELECT v.*, u.first_name, u.last_name, u.phone,
           COALESCE(AVG(r.vet_rating), 0) AS avg_rating,
           COUNT(r.id) AS total_ratings
    FROM veterinarians v
    JOIN users u ON v.user_id = u.id
    LEFT JOIN ratings r ON r.vet_id = v.id AND r.vet_rating IS NOT NULL
    WHERE v.clinic_id = ? AND v.is_available = 1
    GROUP BY v.id
');
$vets->execute([$id]); $vets = $vets->fetchAll();

// Groomers (with average rating)
$groomers = getDB()->prepare('
    SELECT g.*, u.first_name, u.last_name,
           COALESCE(AVG(r.groomer_rating), 0) AS avg_rating,
           COUNT(r.id) AS total_ratings
    FROM groomers g
    JOIN users u ON g.user_id = u.id
    LEFT JOIN ratings r ON r.groomer_id = g.id AND r.groomer_rating IS NOT NULL
    WHERE g.clinic_id = ? AND g.is_available = 1
    GROUP BY g.id
');
$groomers->execute([$id]); $groomers = $groomers->fetchAll();

// Services
$services = getDB()->prepare('SELECT * FROM services WHERE clinic_id=? AND is_active=1 ORDER BY service_type, name');
$services->execute([$id]); $services = $services->fetchAll();

// Ratings
// Fetch ratings - detect schema dynamically
try {
    // Detect which column links ratings to a user (owner_id vs user_id)
    $cols = getDB()->query('DESCRIBE ratings')->fetchAll(PDO::FETCH_COLUMN);
    $userCol = in_array('owner_id', $cols) ? 'owner_id' : (in_array('user_id', $cols) ? 'user_id' : null);
    $hasPublic = in_array('is_public', $cols);
    $hasApptId = in_array('appointment_id', $cols);

    if ($userCol) {
        $joinPet = $hasApptId
            ? "LEFT JOIN appointments a ON r.appointment_id=a.id LEFT JOIN pets p ON a.pet_id=p.id"
            : "";
        $petCol  = $hasApptId ? ", p.name AS pet_name" : ", NULL AS pet_name";
        $pubWhere = $hasPublic ? "AND r.is_public=1" : "";
        $rStmt = getDB()->prepare("SELECT r.*, u.first_name, u.last_name{$petCol}
            FROM ratings r
            JOIN users u ON r.{$userCol}=u.id
            {$joinPet}
            WHERE r.clinic_id=? {$pubWhere}
            ORDER BY r.created_at DESC LIMIT 10");
    } else {
        $rStmt = getDB()->prepare('SELECT r.*, NULL AS first_name, NULL AS last_name, NULL AS pet_name FROM ratings r WHERE r.clinic_id=? ORDER BY r.created_at DESC LIMIT 10');
    }
    $rStmt->execute([$id]);
    $ratings = $rStmt->fetchAll();
} catch (PDOException $e) {
    $ratings = [];
}

$avgRating = $clinic['average_rating'] ?? 0;
$isFav = false;
if (isLoggedIn()) {
    $fStmt = getDB()->prepare('SELECT id FROM favorite_clinics WHERE user_id=? AND clinic_id=?');
    $fStmt->execute([$_SESSION['user_id'], $id]);
    $isFav = (bool)$fStmt->fetch();
}

// Parse operating hours
$operatingHours = [];
if (!empty($clinic['operating_hours'])) {
    $parsed = json_decode($clinic['operating_hours'], true);
    if (is_array($parsed)) $operatingHours = $parsed;
}

// Check if open right now
$isOpenNow = false;
$todayStatus = '';
if (!empty($operatingHours)) {
    $dayNames = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    $todayKey = $dayNames[date('w')];
    $todayInfo = $operatingHours[$todayKey] ?? $operatingHours[substr($todayKey,0,3)] ?? null;
    if ($todayInfo && !($todayInfo === 'closed' || ($todayInfo['closed'] ?? false))) {
        $openTime  = $todayInfo['open']  ?? ($todayInfo[0] ?? null);
        $closeTime = $todayInfo['close'] ?? ($todayInfo[1] ?? null);
        if ($openTime && $closeTime) {
            $now   = strtotime(date('H:i'));
            $open  = strtotime($openTime);
            $close = strtotime($closeTime);
            $isOpenNow   = ($now >= $open && $now <= $close);
            $todayStatus = $openTime . ' – ' . $closeTime;
        } else {
            $isOpenNow   = true;
            $todayStatus = is_string($todayInfo) ? $todayInfo : 'Open today';
        }
    } else {
        $todayStatus = 'Closed today';
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div style="background:var(--bg);min-height:100vh;">
  <!-- Cover -->
  <div style="height:280px;background:linear-gradient(135deg,var(--primary),var(--secondary));position:relative;overflow:hidden;">
    <?php 
    $coverSrc = null;
    $clName = strtolower($clinic['name']);
    
    // Check database first
    if (isset($clinic['cover_photo']) && $clinic['cover_photo'] !== '' && $clinic['cover_photo'] !== null): 
      if (str_starts_with($clinic['cover_photo'], 'http')):
          $coverSrc = $clinic['cover_photo'];
      else:
          $coverSrc = APP_URL . '/' . ltrim($clinic['cover_photo'], '/');
      endif;
    endif;
    
    // Auto-detect from clinic name if no database entry
    if (!$coverSrc) {
      $knownCovers = [
        "d&r"          => 'assets/images/clinics/DR.jpg',
        'every'        => 'assets/images/clinics/evey.jpg',
        'nose to tail' => 'assets/images/clinics/NTTVC.jpg',
        'paws & purrs' => 'assets/images/clinics/paws.jpg',
        'paws'         => 'assets/images/clinics/paws.jpg',
        'mjm'          => 'assets/images/clinics/mjm.png',
        'amado'        => 'assets/images/clinics/Amado.jpg',
        'animal life'  => 'assets/images/clinics/Amado.jpg',
        'petdocs'      => 'assets/images/clinics/Petdocs.jpg',
        'petdoctor'    => 'assets/images/clinics/Petdocs.jpg',
        'saint'        => 'assets/images/clinics/saint.png',
        'st. francis'  => 'assets/images/clinics/saint.png',
        'st. martin'   => 'assets/images/clinics/saint.png',
        'francis'      => 'assets/images/clinics/saint.png',
        'martin'       => 'assets/images/clinics/saint.png',
        'rural'        => 'assets/images/clinics/ruralvet.png',
        'elegance'     => 'assets/images/clinics/ruralvet.png',
        'petally'      => 'assets/images/clinics/petally.png',
        'protect'      => 'assets/images/clinics/Protect.png',
      ];
      foreach ($knownCovers as $keyword => $path) {
        if (str_contains($clName, $keyword)) { 
          $coverSrc = APP_URL . '/' . $path;
          break;
        }
      }
    }
    
    if ($coverSrc):
    ?>
    <!-- DEBUG: Cover source = <?= htmlspecialchars($coverSrc) ?> -->
    <!-- DEBUG: APP_URL = <?= htmlspecialchars(APP_URL) ?> -->
    <img src="<?= htmlspecialchars($coverSrc) ?>" style="width:100%;height:100%;object-fit:cover;opacity:.7;" alt="Cover">
    <?php endif; ?>
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:flex-end;padding:2rem;">
      <div class="container" style="display:flex;align-items:flex-end;gap:1.5rem;max-width:1200px;">
        <div style="width:80px;height:80px;border-radius:16px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--primary);flex-shrink:0;box-shadow:var(--shadow);overflow:hidden;">
          <?php 
          // Auto-detect logo based on clinic name
          $clName = strtolower($clinic['name']);
          $logoSrc = null;
          
          // DEBUG: Show clinic name
          echo "<!-- DEBUG: Clinic name = " . htmlspecialchars($clName) . " -->";
          
          // Check database first
          if (isset($clinic['logo']) && $clinic['logo'] !== '' && $clinic['logo'] !== null): 
            if (str_starts_with($clinic['logo'], 'http')):
                $logoSrc = $clinic['logo'];
            else:
                $logoSrc = APP_URL . '/' . ltrim($clinic['logo'], '/');
            endif;
            echo "<!-- DEBUG: Found logo in database = " . htmlspecialchars($logoSrc) . " -->";
          endif;
          
          // Auto-detect from clinic name
          if (!$logoSrc) {
            $knownLogos = [
              'd&r'          => 'assets/images/clinics/DR.jpg',
              'every'        => 'assets/images/clinics/evey.jpg',
              'nose to tail' => 'assets/images/clinics/NTTVC.jpg',
              'paws & purrs' => 'assets/images/clinics/paws.jpg',
              'paws'         => 'assets/images/clinics/paws.jpg',
              'mjm'          => 'assets/images/clinics/mjm.png',
              'amado'        => 'assets/images/clinics/Amado.jpg',
              'animal life'  => 'assets/images/clinics/Amado.jpg',
              'petdocs'      => 'assets/images/clinics/Petdocs.jpg',
              'petdoctor'    => 'assets/images/clinics/Petdocs.jpg',
              'saint'        => 'assets/images/clinics/saint.png',
              'st. francis'  => 'assets/images/clinics/saint.png',
              'st. martin'   => 'assets/images/clinics/saint.png',
              'francis'      => 'assets/images/clinics/saint.png',
              'martin'       => 'assets/images/clinics/saint.png',
              'rural'        => 'assets/images/clinics/ruralvet.png',
              'elegance'     => 'assets/images/clinics/ruralvet.png',
              'petally'      => 'assets/images/clinics/petally.png',
              'protect'      => 'assets/images/clinics/Protect.png',
            ];
            foreach ($knownLogos as $keyword => $path) {
              if (str_contains($clName, $keyword)) { 
                $logoSrc = APP_URL . '/' . $path;
                echo "<!-- DEBUG: Matched keyword '" . $keyword . "' -> " . htmlspecialchars($logoSrc) . " -->";
                break;
              }
            }
          }
          
          // DEBUG: Show what logo source we got
          echo "<!-- DEBUG: Final logo source = " . ($logoSrc ? htmlspecialchars($logoSrc) : 'NONE - WILL SHOW ICON') . " -->";
          echo "<!-- DEBUG: APP_URL from constant = " . htmlspecialchars(APP_URL) . " -->";
          echo "<!-- DEBUG: Clinic logo field from DB = " . htmlspecialchars($clinic['logo'] ?? 'NULL') . " -->";
          
          if ($logoSrc):
          ?>
          <img src="<?= htmlspecialchars($logoSrc) ?>" style="width:100%;height:100%;object-fit:cover;" alt="Logo" onerror="console.error('Failed to load logo:', this.src)">
          <?php else: ?>
          <i class="fas fa-hospital-alt"></i>
          <?php endif; ?>
        </div>
        <div style="color:#fff;">
          <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:.25rem;"><?= sanitize($clinic['name']) ?></h1>
          <div style="display:flex;gap:1.25rem;flex-wrap:wrap;font-size:.875rem;opacity:.9;">
            <span><i class="fas fa-map-marker-alt"></i> <?= sanitize($clinic['city']) ?>, Pangasinan</span>
            <?php if ($clinic['phone']): ?><span><i class="fas fa-phone"></i> <?= sanitize($clinic['phone']) ?></span><?php endif; ?>
            <span><?= starRating((float)$avgRating) ?> (<?= $clinic['total_ratings'] ?> reviews)</span>
          </div>
        </div>
        <div style="margin-left:auto;display:flex;gap:.75rem;flex-shrink:0;">
          <?php if (isLoggedIn() && currentUser()['role_name']==='pet_owner'): ?>
          <a href="dashboard/owner/book.php?clinic=<?= $id ?>" class="btn btn-secondary"><i class="fas fa-calendar-plus"></i> Book Now</a>
          <?php else: ?>
          <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-secondary"><i class="fas fa-calendar-plus"></i> Book Now</a>
          <?php endif; ?>
          <button onclick="toggleFavorite(<?= $id ?>)" class="btn btn-outline-white" id="favBtn">
            <i class="fas fa-heart<?= $isFav?'':'-broken' ?>"></i> <?= $isFav?'Saved':'Save' ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="container" style="padding:2rem 1.5rem;max-width:1200px;">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;align-items:start;">
      <!-- Main Content -->
      <div>
        <!-- About -->
        <?php if ($clinic['description']): ?>
        <div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.75rem;"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:.5rem"></i>About</h3>
          <p style="color:var(--text-muted);line-height:1.8;"><?= nl2br(sanitize($clinic['description'])) ?></p>
        </div></div>
        <?php endif; ?>

        <!-- Operating Hours -->
        <?php if (!empty($operatingHours)): ?>
        <div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:1.1rem;font-weight:700;"><i class="fas fa-clock" style="color:var(--primary);margin-right:.5rem"></i>Operating Hours</h3>
            <?php if ($todayStatus): ?>
            <span style="display:inline-flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:700;padding:.3rem .85rem;border-radius:50px;
              <?= $isOpenNow ? 'background:rgba(16,185,129,.12);color:var(--success);border:1px solid rgba(16,185,129,.3);' : ($todayStatus==='Closed today' ? 'background:rgba(239,68,68,.1);color:var(--danger);border:1px solid rgba(239,68,68,.25);' : 'background:rgba(245,158,11,.1);color:var(--warning);border:1px solid rgba(245,158,11,.3);') ?>">
              <span style="width:7px;height:7px;border-radius:50%;background:<?= $isOpenNow ? 'var(--success)' : ($todayStatus==='Closed today' ? 'var(--danger)' : 'var(--warning)') ?>;display:inline-block;"></span>
              <?= $isOpenNow ? 'Open Now' : $todayStatus ?>
            </span>
            <?php endif; ?>
          </div>
          <?php
          $dayLabels = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday',
                        'thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
          $todayKeyHigh = $dayNames[date('w')] ?? '';
          ?>
          <div style="display:grid;gap:.4rem;">
            <?php foreach ($dayLabels as $key => $label):
              $info     = $operatingHours[$key] ?? $operatingHours[substr($key,0,3)] ?? null;
              $isClosed = !$info || $info === 'closed' || ($info['closed'] ?? false);
              $openT    = $isClosed ? null : ($info['open']  ?? (is_string($info) ? $info : null));
              $closeT   = $isClosed ? null : ($info['close'] ?? null);
              $isToday  = ($key === $todayKeyHigh);
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.45rem .75rem;border-radius:var(--radius-sm);<?= $isToday ? 'background:rgba(30,64,255,.05);border:1px solid rgba(30,64,255,.12);' : 'border:1px solid transparent;' ?>">
              <span style="font-size:.875rem;font-weight:<?= $isToday ? '700' : '500' ?>;color:<?= $isToday ? 'var(--primary)' : 'var(--text)' ?>;"><?= $label ?><?= $isToday ? ' <span style="font-size:.7rem;color:var(--primary);margin-left:.35rem;">(Today)</span>' : '' ?></span>
              <?php if ($isClosed): ?>
              <span style="font-size:.82rem;color:var(--danger);font-weight:600;">Closed</span>
              <?php elseif ($openT && $closeT): ?>
              <span style="font-size:.82rem;color:var(--text-muted);"><?= sanitize($openT) ?> – <?= sanitize($closeT) ?></span>
              <?php elseif ($openT): ?>
              <span style="font-size:.82rem;color:var(--text-muted);"><?= sanitize($openT) ?></span>
              <?php else: ?>
              <span style="font-size:.82rem;color:var(--success);font-weight:600;">Open</span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div></div>
        <?php endif; ?>

        <!-- Services -->
        <div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;"><i class="fas fa-list" style="color:var(--primary);margin-right:.5rem"></i>Services Offered</h3>
          <?php if ($services): ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <?php foreach ($services as $svc): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);border-radius:var(--radius-sm);padding:.85rem 1rem;">
              <div>
                <div style="font-weight:600;font-size:.9rem;"><?= sanitize($svc['name']) ?></div>
                <?php if ($svc['duration_minutes']): ?><div style="font-size:.78rem;color:var(--text-muted)"><?= $svc['duration_minutes'] ?> min</div><?php endif; ?>
              </div>
              <?php if ($svc['price']): ?><span style="font-weight:700;color:var(--primary);font-size:.9rem;">₱<?= number_format($svc['price'],0) ?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?><p style="color:var(--text-muted)">No services listed yet.</p><?php endif; ?>
        </div></div>

        <!-- Team -->
        <?php if ($vets || $groomers): ?>
        <div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;"><i class="fas fa-users" style="color:var(--primary);margin-right:.5rem"></i>Our Team</h3>

          <?php if ($vets): ?>
          <div style="margin-bottom:1.5rem;">
            <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--secondary);margin-bottom:.85rem;">
              <i class="fas fa-stethoscope" style="margin-right:.4rem;"></i>Veterinarians
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
              <?php foreach ($vets as $v): ?>
              <div style="background:var(--bg);border-radius:var(--radius);padding:1.25rem;border:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:.85rem;">
                  <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($v['first_name'],0,1)) ?>
                  </div>
                  <div style="min-width:0;">
                    <div style="font-weight:700;font-size:.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Dr. <?= sanitize($v['first_name'].' '.$v['last_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--secondary);font-weight:600;">Veterinarian</div>
                    <?php if ($v['years_experience'] > 0): ?>
                    <div style="font-size:.73rem;color:var(--text-muted);"><?= (int)$v['years_experience'] ?> yr<?= $v['years_experience']!=1?'s':'' ?> experience</div>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ($v['specialization']): ?>
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.6rem;">
                  <?php foreach (array_filter(array_map('trim', explode(',', $v['specialization']))) as $tag): ?>
                  <span style="font-size:.72rem;background:rgba(30,64,255,.08);color:var(--primary);padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(30,64,255,.15);"><?= sanitize($tag) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty(trim($v['bio'] ?? ''))): ?>
                <p style="font-size:.82rem;color:var(--text-muted);line-height:1.65;margin-bottom:.75rem;"><?= nl2br(sanitize($v['bio'])) ?></p>
                <?php endif; ?>

                <!-- Rating -->
                <div style="display:flex;align-items:center;gap:.4rem;padding-top:.65rem;border-top:1px solid var(--border);">
                  <?php if ($v['total_ratings'] > 0): ?>
                    <?php for ($i=1;$i<=5;$i++): ?><i class="<?= $i<=round($v['avg_rating'])?'fas':'far' ?> fa-star" style="color:var(--warning);font-size:.78rem;"></i><?php endfor; ?>
                    <span style="font-size:.82rem;font-weight:700;color:var(--text);"><?= number_format($v['avg_rating'],1) ?></span>
                    <span style="font-size:.75rem;color:var(--text-muted);">(<?= (int)$v['total_ratings'] ?> review<?= $v['total_ratings']!=1?'s':'' ?>)</span>
                  <?php else: ?>
                    <?php for ($i=1;$i<=5;$i++): ?><i class="far fa-star" style="color:#D1D5DB;font-size:.78rem;"></i><?php endfor; ?>
                    <span style="font-size:.75rem;color:var(--text-muted);">No reviews yet</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($groomers): ?>
          <div>
            <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--warning);margin-bottom:.85rem;">
              <i class="fas fa-cut" style="margin-right:.4rem;"></i>Groomers
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
              <?php foreach ($groomers as $g): ?>
              <div style="background:var(--bg);border-radius:var(--radius);padding:1.25rem;border:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:.85rem;">
                  <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--warning),#F97316);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($g['first_name'],0,1)) ?>
                  </div>
                  <div style="min-width:0;">
                    <div style="font-weight:700;font-size:.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($g['first_name'].' '.$g['last_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--warning);font-weight:600;">Groomer</div>
                    <?php if ($g['years_experience'] > 0): ?>
                    <div style="font-size:.73rem;color:var(--text-muted);"><?= (int)$g['years_experience'] ?> yr<?= $g['years_experience']!=1?'s':'' ?> experience</div>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ($g['specialization']): ?>
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.6rem;">
                  <?php foreach (array_filter(array_map('trim', explode(',', $g['specialization']))) as $tag): ?>
                  <span style="font-size:.72rem;background:rgba(245,158,11,.1);color:#B45309;padding:.2rem .6rem;border-radius:50px;border:1px solid rgba(245,158,11,.25);"><?= sanitize($tag) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty(trim($g['bio'] ?? ''))): ?>
                <p style="font-size:.82rem;color:var(--text-muted);line-height:1.65;margin-bottom:.75rem;"><?= nl2br(sanitize($g['bio'])) ?></p>
                <?php endif; ?>

                <!-- Rating -->
                <div style="display:flex;align-items:center;gap:.4rem;padding-top:.65rem;border-top:1px solid var(--border);">
                  <?php if ($g['total_ratings'] > 0): ?>
                    <?php for ($i=1;$i<=5;$i++): ?><i class="<?= $i<=round($g['avg_rating'])?'fas':'far' ?> fa-star" style="color:var(--warning);font-size:.78rem;"></i><?php endfor; ?>
                    <span style="font-size:.82rem;font-weight:700;color:var(--text);"><?= number_format($g['avg_rating'],1) ?></span>
                    <span style="font-size:.75rem;color:var(--text-muted);">(<?= (int)$g['total_ratings'] ?> review<?= $g['total_ratings']!=1?'s':'' ?>)</span>
                  <?php else: ?>
                    <?php for ($i=1;$i<=5;$i++): ?><i class="far fa-star" style="color:#D1D5DB;font-size:.78rem;"></i><?php endfor; ?>
                    <span style="font-size:.75rem;color:var(--text-muted);">No reviews yet</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div></div>
        <?php endif; ?>

        <!-- Reviews -->
        <div class="card"><div class="card-body">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;"><i class="fas fa-star" style="color:var(--warning);margin-right:.5rem"></i>Reviews (<?= count($ratings) ?>)</h3>
          <?php if ($ratings): foreach ($ratings as $r): ?>
          <div style="border-bottom:1px solid var(--border);padding:1rem 0;last-child:no-border;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
              <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;">
                  <?= strtoupper(substr($r['first_name'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.9rem;"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted)"><?= timeAgo($r['created_at']) ?></div>
                </div>
              </div>
              <div class="stars"><?= str_repeat('<i class="fas fa-star"></i>', (int)$r['clinic_rating']) ?></div>
            </div>
            <?php if ($r['comment']): ?><p style="font-size:.875rem;color:var(--text-muted);line-height:1.7;"><?= sanitize($r['comment']) ?></p><?php endif; ?>
          </div>
          <?php endforeach; else: ?>
          <div class="empty-state" style="padding:2rem;"><i class="far fa-star"></i><h3>No reviews yet</h3><p>Be the first to leave a review.</p></div>
          <?php endif; ?>
        </div></div>
      </div>

      <!-- Sidebar -->
      <div>
        <!-- Contact Card -->
        <div class="card" style="margin-bottom:1.25rem;"><div class="card-body">
          <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Contact & Location</h3>
          <?php if ($todayStatus): ?>
          <div style="display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.875rem;align-items:center;">
            <i class="fas fa-circle" style="color:<?= $isOpenNow ? 'var(--success)' : 'var(--danger)' ?>;width:16px;font-size:.55rem;margin-top:.1rem;"></i>
            <span style="font-weight:600;color:<?= $isOpenNow ? 'var(--success)' : 'var(--danger)' ?>;">
              <?= $isOpenNow ? 'Open Now' : ($todayStatus === 'Closed today' ? 'Closed Today' : 'Closed Now') ?>
            </span>
            <?php if ($todayStatus && $todayStatus !== 'Closed today'): ?>
            <span style="color:var(--text-muted);font-size:.8rem;"><?= sanitize($todayStatus) ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if ($clinic['phone']): ?><div style="display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.875rem;"><i class="fas fa-phone" style="color:var(--primary);width:16px;margin-top:.1rem;"></i><span><?= sanitize($clinic['phone']) ?></span></div><?php endif; ?>
          <?php if ($clinic['email']): ?><div style="display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.875rem;"><i class="fas fa-envelope" style="color:var(--primary);width:16px;margin-top:.1rem;"></i><a href="mailto:<?= sanitize($clinic['email']) ?>"><?= sanitize($clinic['email']) ?></a></div><?php endif; ?>
          <?php if ($clinic['address']): ?><div style="display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.875rem;"><i class="fas fa-map-marker-alt" style="color:var(--primary);width:16px;margin-top:.1rem;"></i><span><?= sanitize($clinic['address']) ?></span></div><?php endif; ?>
          <?php if ($clinic['website']): ?><div style="display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.875rem;"><i class="fas fa-globe" style="color:var(--primary);width:16px;margin-top:.1rem;"></i><a href="<?= sanitize($clinic['website']) ?>" target="_blank" rel="noopener">Visit Website</a></div><?php endif; ?>
        </div></div>

        <!-- Map -->
        <?php if ($clinic['latitude'] && $clinic['longitude']): ?>
        <div class="card" style="margin-bottom:1.25rem;">
          <div id="clinicDetailMap" style="height:220px;"></div>
        </div>
        <?php endif; ?>

        <!-- Quick Book -->
        <div class="card" style="background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff !important;">
          <div class="card-body" style="text-align:center;">
            <i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;opacity:.85;color:#fff;"></i>
            <h3 style="font-weight:700;margin-bottom:.5rem;color:#fff !important;">Ready to Book?</h3>
            <p style="opacity:.85;font-size:.875rem;margin-bottom:1.25rem;color:#fff !important;">Select a service and pick a time that works for you.</p>
            <?php if (isLoggedIn() && currentUser()['role_name']==='pet_owner'): ?>
            <a href="dashboard/owner/book.php?clinic=<?= $id ?>" class="btn" style="background:#fff;color:var(--primary);font-weight:700;width:100%;justify-content:center;">Book Appointment</a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/auth/register.php" class="btn" style="background:#fff;color:var(--primary);font-weight:700;width:100%;justify-content:center;">Get Started</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
async function toggleFavorite(clinicId) {
  if (!<?= isLoggedIn() ? 'true' : 'false' ?>) { location.href = '<?= APP_URL ?>/auth/login.php'; return; }
  try {
    const data = await apiCall('<?= APP_URL ?>/api/favorites.php', 'POST', { clinic_id: clinicId });
    const btn = document.getElementById('favBtn');
    const icon = btn.querySelector('i');
    if (data.action === 'added') { icon.className='fas fa-heart'; btn.lastChild.textContent=' Saved'; showToast('Added to favorites','success'); }
    else { icon.className='fas fa-heart-broken'; btn.lastChild.textContent=' Save'; showToast('Removed from favorites','info'); }
  } catch(e) { showToast(e.message,'error'); }
}
</script>

<?php if ($clinic['latitude'] && $clinic['longitude']): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const lat = <?= (float)$clinic['latitude'] ?>;
  const lng = <?= (float)$clinic['longitude'] ?>;
  const detailMap = L.map('clinicDetailMap').setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(detailMap);
  L.marker([lat, lng]).addTo(detailMap)
    .bindPopup('<strong><?= addslashes(sanitize($clinic['name'])) ?></strong>').openPopup();
});
</script>
<?php endif; ?>
