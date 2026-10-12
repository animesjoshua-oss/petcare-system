<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Clinic Admin Dashboard';
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
        <div class="dash-breadcrumb">
          <a href="<?= APP_URL ?>/index.php"><i class="fas fa-home"></i> Home</a>
          <span class="dash-breadcrumb-sep">/</span>
          <span>Dashboard</span>
        </div>
        <h1>Business Dashboard</h1>
        <p>Welcome back, <?= sanitize($user['first_name']) ?> · <?= date('l, F j, Y') ?></p>
      </div>
      <div class="admin-dash-actions">
        <select class="admin-range-select" onchange="window.location.href='?range='+this.value">
          <option value="7"  <?= $range===7  ?'selected':'' ?>><i class="far fa-calendar"></i> Last 7 days</option>
          <option value="30" <?= $range===30 ?'selected':'' ?>><i class="far fa-calendar"></i> Last 30 days</option>
          <option value="90" <?= $range===90 ?'selected':'' ?>><i class="far fa-calendar"></i> Last 90 days</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="location.reload()" title="Refresh dashboard">
          <i class="fas fa-sync-alt"></i>
          <span class="btn-label">Refresh</span>
        </button>
        <a href="reports.php" class="btn btn-primary btn-sm" title="Export report">
          <i class="fas fa-download"></i>
          <span class="btn-label">Export</span>
        </a>
      </div>
    </div>
  </div>

  <!-- ── KPI STAT CARDS ───────────────────────────────────── -->
  <div class="stats-row">

    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue">
        <i class="fas fa-calendar-check"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= number_format($totalAppts) ?></strong>
        <span>Total Appointments</span>
        <div class="stat-change <?= $apptGrowth >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $apptGrowth >= 0 ? 'up' : 'down' ?>"></i>
          <?= abs($apptGrowth) ?>% vs last period
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-green">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= number_format($completedAppts) ?></strong>
        <span>Completed</span>
        <div class="stat-change up">
          <i class="fas fa-check"></i>
          On track
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-orange">
        <i class="fas fa-clock"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $pendingAppts ?></strong>
        <span>Pending Review</span>
        <div class="stat-change <?= $pendingAppts > 5 ? 'down' : 'up' ?>">
          <?= $pendingAppts > 5 ? 'Needs attention' : 'Looks good' ?>
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-purple">
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= number_format($totalUsers) ?></strong>
        <span>Total Users</span>
        <div class="stat-change <?= $userGrowth >= 0 ? 'up' : 'down' ?>">
          <i class="fas fa-arrow-<?= $userGrowth >= 0 ? 'up' : 'down' ?>"></i>
          <?= abs($userGrowth) ?>% growth
        </div>
      </div>
    </div>

    <div class="stat-widget">
      <div class="stat-widget-icon sw-orange">
        <i class="fas fa-star"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $avgRating ?>★</strong>
        <span>Avg Rating</span>
        <div class="stat-change up">
          Platform average
        </div>
      </div>
    </div>

  </div><!-- /.stats-row -->

  <!-- ── SECONDARY STATS ─────────────────────────────────── -->
  <div class="stats-row">
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue">
        <i class="fas fa-hospital"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $totalClinics ?></strong>
        <span>Active Clinics</span>
      </div>
    </div>
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-green">
        <i class="fas fa-user-md"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $totalVets ?></strong>
        <span>Veterinarians</span>
      </div>
    </div>
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-purple">
        <i class="fas fa-cut"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $totalGroomers ?></strong>
        <span>Groomers</span>
      </div>
    </div>
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-green">
        <i class="fas fa-paw"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $totalPets ?></strong>
        <span>Active Pets</span>
      </div>
    </div>
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-blue">
        <i class="fas fa-calendar-day"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $todayAppts ?></strong>
        <span>Today's Appointments</span>
      </div>
    </div>
    
    <div class="stat-widget">
      <div class="stat-widget-icon sw-red">
        <i class="fas fa-times-circle"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $cancelledAppts ?></strong>
        <span>Cancelled</span>
      </div>
    </div>
    
  </div><!-- /.stats-row -->

  <!-- ── CHARTS ROW ────────────────────────────────────────── -->
  <div class="dash-two-col">

    <!-- Appointment Overview Chart -->
    <div class="card">
      <div class="card-header">
        <h3>
          <i class="fas fa-chart-line"></i>
          Appointment Overview
        </h3>
        <div class="chart-legend">
          <span class="legend-item">
            <span class="legend-dot" style="background:#1F2937"></span>
            Total
          </span>
          <span class="legend-item">
            <span class="legend-dot" style="background:#10B981"></span>
            Completed
          </span>
        </div>
      </div>
      <div class="card-body">
        <?php if($totalAppts > 0): ?>
          <canvas id="mainChart" height="100"></canvas>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <h3>No Data Available</h3>
            <p>Appointment data will appear here once bookings are made.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Appointment Status Breakdown -->
    <div class="card">
      <div class="card-header">
        <h3>
          <i class="fas fa-chart-pie"></i>
          Appointment Status
        </h3>
      </div>
      <div class="card-body">
        <?php if($totalAppts > 0): ?>
          <div class="donut-chart-container">
            <canvas id="donutChart" width="160" height="160"></canvas>
          </div>
          <div class="chart-stats">
            <div class="chart-stat-item">
              <span class="chart-stat-label">
                <i class="fas fa-circle" style="color:#10B981"></i>
                Completed
              </span>
              <strong><?= $completedAppts ?></strong>
            </div>
            <div class="chart-stat-item">
              <span class="chart-stat-label">
                <i class="fas fa-circle" style="color:#F59E0B"></i>
                Pending
              </span>
              <strong><?= $pendingAppts ?></strong>
            </div>
            <div class="chart-stat-item">
              <span class="chart-stat-label">
                <i class="fas fa-circle" style="color:#E2E8F0"></i>
                Others
              </span>
              <strong><?= max(0,$totalAppts - $completedAppts - $pendingAppts) ?></strong>
            </div>
          </div>
          <div class="chart-footer">
            <div class="chart-footer-item">
              <span class="legend-dot" style="background:#3B82F6"></span>
              <span>Veterinary: <?= $vetCount ?></span>
            </div>
            <div class="chart-footer-item">
              <span class="legend-dot" style="background:#F59E0B"></span>
              <span>Grooming: <?= $groomCount ?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-chart-pie"></i>
            <h3>No Data Available</h3>
            <p>Status breakdown will appear here.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /.dash-two-col -->

  <!-- ── APPOINTMENTS TABLE ────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <h3>
        <i class="fas fa-calendar-check"></i>
        Appointment Management
      </h3>
      <div class="card-header-actions">
        <?php if($pendingAppts > 0): ?>
        <a href="appointments.php?status=pending" class="btn btn-primary btn-sm">
          <i class="fas fa-inbox"></i>
          Review Pending (<?= $pendingAppts ?>)
        </a>
        <?php endif; ?>
        <a href="appointments.php" class="btn btn-outline btn-sm">
          View All
        </a>
      </div>
    </div>
    
    <!-- Status Filter Tabs -->
    <div class="table-tabs">
      <button class="table-tab active" data-status="all">
        All
        <span class="tab-count"><?= count($recentAppts) ?></span>
      </button>
      <button class="table-tab" data-status="pending">
        Pending
        <span class="tab-count"><?= $pendingAppts ?></span>
      </button>
      <button class="table-tab" data-status="confirmed">
        Confirmed
      </button>
      <button class="table-tab" data-status="completed">
        Completed
      </button>
      <button class="table-tab" data-status="cancelled">
        Cancelled
      </button>
    </div>
    
    <div class="table-wrap">
      <?php if($recentAppts): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Pet</th>
            <th>Owner</th>
            <th>Service</th>
            <th>Clinic</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recentAppts as $a):
            $badgeClass = match($a['status']) {
              'completed'  => 'badge-success',
              'approved'   => 'badge-info',
              'confirmed'  => 'badge-info',
              'pending'    => 'badge-warning',
              'cancelled'  => 'badge-secondary',
              'rejected'   => 'badge-danger',
              default      => 'badge-secondary'
            };
          ?>
          <tr>
            <td>
              <div class="table-cell-primary"><?= sanitize($a['pet_name']) ?></div>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['owner_name']) ?></div>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['svc']) ?></div>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['clinic_name']) ?></div>
            </td>
            <td>
              <div class="table-cell-primary"><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
              <div class="table-cell-meta"><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
            </td>
            <td>
              <span class="badge <?= $badgeClass ?>"><?= ucfirst($a['status']) ?></span>
            </td>
            <td class="text-right">
              <div class="table-actions">
                <?php include __DIR__ . '/../../includes/appt_actions.php'; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-calendar-xmark"></i>
        <h3>No Appointments Yet</h3>
        <p>Appointments will appear here once users start booking.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── BOTTOM ROW: Recent Users + Quick Actions ──────────── -->
  <div class="dash-two-col" style="grid-template-columns: 2fr 1fr;">

    <!-- Recent Users -->
    <div class="card">
      <div class="card-header">
        <h3>
          <i class="fas fa-users"></i>
          Recent Users
        </h3>
        <a href="users.php" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="table-wrap">
        <?php if($recentUsers): ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Joined</th>
            </tr>
          </thead>
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
              <td>
                <div class="table-cell-primary"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></div>
              </td>
              <td>
                <div class="table-cell-secondary"><?= sanitize($u['email']) ?></div>
              </td>
              <td>
                <span class="badge <?= $roleBadge ?>"><?= displayRoleName($u['role_name']) ?></span>
              </td>
              <td>
                <div class="table-cell-meta"><?= timeAgo($u['created_at']) ?></div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-users"></i>
          <h3>No Users Yet</h3>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">
        <h3>
          <i class="fas fa-bolt"></i>
          Quick Actions
        </h3>
      </div>
      <div class="card-body">
        <div class="quick-actions">
          <?php if($pendingAppts > 0): ?>
          <a href="appointments.php?status=pending" class="btn btn-primary btn-block">
            <i class="fas fa-inbox"></i>
            Review Pending
            <span class="action-badge"><?= $pendingAppts ?></span>
          </a>
          <?php endif; ?>
          <a href="users.php" class="btn btn-outline btn-block">
            <i class="fas fa-user-plus"></i>
            Manage Users
          </a>
          <a href="clinics.php" class="btn btn-outline btn-block">
            <i class="fas fa-hospital"></i>
            Manage Clinics
          </a>
          <a href="vets.php" class="btn btn-outline btn-block">
            <i class="fas fa-user-md"></i>
            Manage Vets
          </a>
          <a href="services.php" class="btn btn-outline btn-block">
            <i class="fas fa-list"></i>
            Manage Services
          </a>
          <a href="reports.php" class="btn btn-outline btn-block">
            <i class="fas fa-download"></i>
            Download Report
          </a>
        </div>
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
