<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Admin Dashboard';
$isDashboard = true;
$db = getDB();

// Date range filter
$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90])) $range = 30;
$rangeLabel = match($range) { 7 => 'Last 7 days', 90 => 'Last 90 days', default => 'Last 30 days' };

function qn($db, $sql, $p = []) {
    $s = $db->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
}

// Core stats — filtered by range where relevant
$totalAppts     = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]);
$pendingAppts   = qn($db,"SELECT COUNT(*) FROM appointments WHERE status='pending'");
$completedAppts = qn($db,"SELECT COUNT(*) FROM appointments WHERE status='completed' AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]);
$cancelledAppts = qn($db,"SELECT COUNT(*) FROM appointments WHERE status='cancelled' AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]);
$todayAppts     = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date=CURDATE()");
$totalUsers     = qn($db,"SELECT COUNT(*) FROM users");
$totalClinics   = qn($db,"SELECT COUNT(*) FROM clinics WHERE is_active=1");
$totalVets      = qn($db,"SELECT COUNT(*) FROM veterinarians WHERE is_available=1");
$totalGroomers  = qn($db,"SELECT COUNT(*) FROM groomers WHERE is_available=1");
$totalPets      = qn($db,"SELECT COUNT(*) FROM pets WHERE is_active=1");
$avgRating      = round((float)$db->query("SELECT COALESCE(AVG(clinic_rating),0) FROM ratings")->fetchColumn(), 1);

// Growth — within range
$thisM = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]);
$lastM = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND appointment_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range*2, $range]);
$apptGrowth = $lastM > 0 ? round((($thisM - $lastM) / $lastM) * 100, 1) : 0;

$thisU = qn($db,"SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range]);
$lastU = qn($db,"SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)", [$range*2, $range]);
$userGrowth = $lastU > 0 ? round((($thisU - $lastU) / $lastU) * 100, 1) : 0;

// Monthly chart (within range)
$monthly = $db->prepare("
    SELECT DATE_FORMAT(appointment_date,'%b %Y') AS lbl,
           DATE_FORMAT(appointment_date,'%Y-%m') AS ym,
           COUNT(*) AS tot,
           SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS done
    FROM appointments
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY ym, lbl ORDER BY ym ASC
");
$monthly->execute([$range]); $monthly = $monthly->fetchAll();

// Service split
$vetCount    = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_type='veterinary'");
$groomCount  = qn($db,"SELECT COUNT(*) FROM appointments WHERE appointment_type='grooming'");

// Recent appointments
$recentAppts = $db->query("
    SELECT a.*, p.name AS pet_name,
           CONCAT(ou.first_name,' ',ou.last_name) AS owner_name,
           c.name AS clinic_name,
           COALESCE(s.name, a.appointment_type) AS svc
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users ou ON a.owner_id=ou.id
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    ORDER BY a.created_at DESC LIMIT 10
")->fetchAll();

// Recent users
$recentUsers = $db->query("
    SELECT u.*, r.name AS role_name
    FROM users u JOIN roles r ON u.role_id=r.id
    ORDER BY u.created_at DESC LIMIT 6
")->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

<!-- ── MAIN ──────────────────────────────────────────────── -->
<main class="dash-main">

  <!-- Page Header -->
  <div class="dash-header">
    <div class="admin-dash-header">
      <div class="admin-dash-title">
        <div class="dash-breadcrumb"><a href="#">Home</a> / <span>Dashboard</span></div>
        <h1>Overview</h1>
        <p>Welcome back, <?= sanitize($user['first_name']) ?> &nbsp;·&nbsp; <?= date('l, F j, Y') ?></p>
      </div>
      <div class="admin-dash-actions">
        <select class="form-control admin-range-select" onchange="window.location.href='?range='+this.value">
          <option value="7"  <?= $range===7  ?'selected':'' ?>>Last 7 days</option>
          <option value="30" <?= $range===30 ?'selected':'' ?>>Last 30 days</option>
          <option value="90" <?= $range===90 ?'selected':'' ?>>Last 90 days</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="location.reload()"><i class="fas fa-rotate-right"></i> <span class="btn-label">Refresh</span></button>
        <a href="reports.php" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> <span class="btn-label">Export</span></a>
      </div>
    </div>
  </div>

  <!-- ── KPI STAT CARDS ───────────────────────────────────── -->
  <div class="stats-row stats-odd">

    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue"><i class="fas fa-calendar-check"></i></div>
      <div class="stat-widget-info">
        <strong><?= number_format($totalAppts) ?></strong>
        <span>Total Appointments</span>
        <div class="stat-change <?= $apptGrowth >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $apptGrowth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($apptGrowth) ?>% vs last month
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-green"><i class="fas fa-check-circle"></i></div>
      <div class="stat-widget-info">
        <strong><?= number_format($completedAppts) ?></strong>
        <span>Completed</span>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> On track</div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-orange"><i class="fas fa-clock"></i></div>
      <div class="stat-widget-info">
        <strong><?= $pendingAppts ?></strong>
        <span>Pending Review</span>
        <div class="stat-change <?= $pendingAppts > 5 ? 'down' : 'up' ?>">
          <i class="fas fa-<?= $pendingAppts > 5 ? 'triangle-exclamation' : 'check' ?>"></i>
          <?= $pendingAppts > 5 ? 'Needs attention' : 'Looks good' ?>
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-purple"><i class="fas fa-users"></i></div>
      <div class="stat-widget-info">
        <strong><?= number_format($totalUsers) ?></strong>
        <span>Total Users</span>
        <div class="stat-change <?= $userGrowth >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $userGrowth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($userGrowth) ?>% vs last month
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-orange"><i class="fas fa-star"></i></div>
      <div class="stat-widget-info">
        <strong><?= $avgRating ?>★</strong>
        <span>Avg Rating</span>
        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Platform average</div>
      </div>
    </div>

  </div><!-- /.stats-row -->

  <!-- ── SECONDARY STATS ─────────────────────────────────── -->
  <div class="stats-row" style="margin-bottom:2rem;">
    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue"><i class="fas fa-hospital"></i></div>
      <div class="stat-widget-info"><strong><?= $totalClinics ?></strong><span>Active Clinics</span></div>
    </div>
    <div class="stat-widget">
      <div class="stat-widget-icon sw-green"><i class="fas fa-user-md"></i></div>
      <div class="stat-widget-info"><strong><?= $totalVets ?></strong><span>Veterinarians</span></div>
    </div>
    <div class="stat-widget">
      <div class="stat-widget-icon sw-purple"><i class="fas fa-cut"></i></div>
      <div class="stat-widget-info"><strong><?= $totalGroomers ?></strong><span>Groomers</span></div>
    </div>
    <div class="stat-widget">
      <div class="stat-widget-icon sw-green"><i class="fas fa-paw"></i></div>
      <div class="stat-widget-info"><strong><?= $totalPets ?></strong><span>Active Pets</span></div>
    </div>
    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue"><i class="fas fa-calendar-day"></i></div>
      <div class="stat-widget-info"><strong><?= $todayAppts ?></strong><span>Today's Appts</span></div>
    </div>
    <div class="stat-widget">
      <div class="stat-widget-icon sw-red"><i class="fas fa-times-circle"></i></div>
      <div class="stat-widget-info"><strong><?= $cancelledAppts ?></strong><span>Cancelled</span></div>
    </div>
  </div>

  <!-- ── CHARTS ROW ────────────────────────────────────────── -->
  <div class="dash-two-col" style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Appointment Overview Chart -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-chart-area" style="color:var(--primary);margin-right:.5rem;"></i>Appointment Overview</h3>
        <div style="display:flex;align-items:center;gap:1rem;font-size:.8rem;color:var(--text-muted);">
          <span><span style="display:inline-block;width:10px;height:10px;background:#111;border-radius:50%;margin-right:.3rem;"></span>Total</span>
          <span><span style="display:inline-block;width:10px;height:10px;background:var(--success);border-radius:50%;margin-right:.3rem;"></span>Completed</span>
        </div>
      </div>
      <div class="card-body">
        <?php if($totalAppts > 0): ?>
          <canvas id="mainChart" height="100"></canvas>
        <?php else: ?>
          <div class="empty-state" style="padding:3rem;text-align:center;">
            <i class="fas fa-chart-line" style="font-size:2rem;color:var(--text-muted);margin-bottom:1rem;display:block;"></i>
            <p style="color:var(--text-muted);">No appointment data available yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status Split Donut -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:.5rem;"></i>Status Split</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;min-height:200px;">
        <?php if($totalAppts > 0): ?>
          <canvas id="donutChart" width="140" height="140"></canvas>
          <div style="width:100%;display:flex;flex-direction:column;gap:.5rem;font-size:.83rem;">
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-muted);"><i class="fas fa-check" style="color:var(--success);margin-right:.4rem;"></i>Completed</span>
              <strong><?= $completedAppts ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-muted);"><i class="fas fa-clock" style="color:var(--warning);margin-right:.4rem;"></i>Pending</span>
              <strong><?= $pendingAppts ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-muted);"><i class="fas fa-minus" style="color:var(--text-muted);margin-right:.4rem;"></i>Others</span>
              <strong><?= max(0,$totalAppts - $completedAppts - $pendingAppts) ?></strong>
            </div>
          </div>
          <div style="display:flex;gap:1.5rem;font-size:.78rem;border-top:1px solid var(--border);padding-top:.75rem;width:100%;">
            <span><span style="display:inline-block;width:10px;height:10px;background:var(--primary);border-radius:50%;margin-right:.3rem;"></span>Veterinary (<?= $vetCount ?>)</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:var(--warning);border-radius:50%;margin-right:.3rem;"></span>Grooming (<?= $groomCount ?>)</span>
          </div>
        <?php else: ?>
          <div class="empty-state" style="text-align:center;">
            <i class="fas fa-chart-pie" style="font-size:2rem;color:var(--text-muted);margin-bottom:1rem;display:block;"></i>
            <p style="color:var(--text-muted);">No appointment data available yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- ── APPOINTMENTS TABLE ────────────────────────────────── -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
      <h3><i class="fas fa-calendar-days" style="color:var(--primary);margin-right:.5rem;"></i>Appointment Management</h3>
      <div style="display:flex;gap:.5rem;">
        <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
        <a href="appointments.php?status=pending" class="btn btn-sm btn-primary">
          Review Pending<?php if($pendingAppts>0): ?> (<?= $pendingAppts ?>)<?php endif; ?>
        </a>
      </div>
    </div>
    <div class="table-wrap">
      <?php if($recentAppts): ?>
      <table>
        <thead>
          <tr>
            <th>Pet</th><th>Owner</th><th>Service</th><th>Clinic</th><th>Date</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recentAppts as $a):
            $badgeClass = match($a['status']) {
              'completed'  => 'badge-success',
              'approved'   => 'badge-info',
              'pending'    => 'badge-warning',
              'cancelled'  => 'badge-secondary',
              'rejected'   => 'badge-danger',
              default      => 'badge-secondary'
            };
          ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($a['pet_name']) ?></td>
            <td><?= sanitize($a['owner_name']) ?></td>
            <td style="font-size:.85rem;"><?= sanitize($a['svc']) ?></td>
            <td style="font-size:.85rem;"><?= sanitize($a['clinic_name']) ?></td>
            <td>
              <div style="font-size:.85rem;font-weight:500;"><?= date('M j, Y',strtotime($a['appointment_date'])) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);"><?= date('g:i A',strtotime($a['appointment_time'])) ?></div>
            </td>
            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($a['status']) ?></span></td>
            <td>
              <div class="table-actions">
                <?php include __DIR__ . '/../../includes/appt_actions.php'; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state" style="padding:3rem;">
        <i class="fas fa-calendar-xmark"></i>
        <h3>No appointments yet</h3>
        <p>Appointments will appear here once users start booking.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── BOTTOM ROW: Recent Users + Quick Actions ──────────── -->
  <div class="dash-two-col" style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Recent Users -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-users" style="color:var(--primary);margin-right:.5rem;"></i>Recent Users</h3>
        <a href="users.php" class="btn btn-sm btn-outline">Manage All</a>
      </div>
      <div class="table-wrap">
        <?php if($recentUsers): ?>
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
          <tbody>
            <?php foreach($recentUsers as $u):
              $roleBadge = match($u['role_name']) {
                'pet_owner'    => 'badge-info',
                'veterinarian' => 'badge-success',
                'groomer'      => 'badge-primary',
                'admin'        => 'badge-warning',
                'superadmin'   => 'badge-danger',
                default        => 'badge-secondary'
              };
            ?>
            <tr>
              <td style="font-weight:600;"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></td>
              <td style="font-size:.85rem;color:var(--text-muted);"><?= sanitize($u['email']) ?></td>
              <td><span class="badge <?= $roleBadge ?>"><?= ucwords(str_replace('_',' ',$u['role_name'])) ?></span></td>
              <td style="font-size:.8rem;color:var(--text-muted);"><?= timeAgo($u['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state" style="padding:2rem;">
          <i class="fas fa-users"></i><h3>No users yet</h3>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-bolt" style="color:var(--warning);margin-right:.5rem;"></i>Quick Actions</h3>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:.75rem;">
        <a href="appointments.php?status=pending" class="btn btn-primary btn-block">
          <i class="fas fa-inbox"></i> Review Pending
          <?php if($pendingAppts>0): ?><span style="background:rgba(255,255,255,.25);border-radius:50px;padding:0 .5rem;font-size:.78rem;margin-left:.3rem;"><?= $pendingAppts ?></span><?php endif; ?>
        </a>
        <a href="users.php" class="btn btn-outline btn-block"><i class="fas fa-user-plus"></i> Manage Users</a>
        <a href="clinics.php" class="btn btn-outline btn-block"><i class="fas fa-hospital"></i> Manage Clinics</a>
        <a href="vets.php" class="btn btn-outline btn-block"><i class="fas fa-user-md"></i> Manage Vets</a>
        <a href="services.php" class="btn btn-outline btn-block"><i class="fas fa-list"></i> Manage Services</a>
        <a href="reports.php" class="btn btn-outline btn-block"><i class="fas fa-file-arrow-down"></i> Download Report</a>
      </div>
    </div>

  </div>

</main><!-- /.dash-main -->

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const mLabels = <?= json_encode(array_column($monthly,'lbl')) ?>;
const mTotal  = <?= json_encode(array_map('intval', array_column($monthly,'tot'))) ?>;
const mDone   = <?= json_encode(array_map('intval', array_column($monthly,'done'))) ?>;

// Main bar+line chart
const mainChartEl = document.getElementById('mainChart');
if (mainChartEl) {
new Chart(mainChartEl, {
  data: {
    labels: mLabels,
    datasets: [
      {
        type: 'bar',
        label: 'Total',
        data: mTotal,
        backgroundColor: 'rgba(30,64,255,.12)',
        borderColor: 'rgba(30,64,255,.3)',
        borderWidth: 1,
        borderRadius: 4,
        yAxisID: 'y'
      },
      {
        type: 'line',
        label: 'Completed',
        data: mDone,
        borderColor: '#10B981',
        backgroundColor: 'rgba(16,185,129,.08)',
        fill: true,
        tension: .4,
        pointRadius: 4,
        pointBackgroundColor: '#10B981',
        yAxisID: 'y'
      }
    ]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } },
      x: { grid: { display: false } }
    }
  }
});
}

// Donut chart
const donutChartEl = document.getElementById('donutChart');
if (donutChartEl) {
new Chart(donutChartEl, {
  type: 'doughnut',
  data: {
    labels: ['Completed','Pending','Others'],
    datasets: [{
      data: [
        <?= $completedAppts ?>,
        <?= $pendingAppts ?>,
        <?= max(0,$totalAppts-$completedAppts-$pendingAppts) ?>
      ],
      backgroundColor: ['#10B981','#F59E0B','#E2E8F0'],
      borderWidth: 0
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    cutout: '68%'
  }
});
}
</script>
