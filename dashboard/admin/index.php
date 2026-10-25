<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
if (($user['role_name'] ?? '') === 'superadmin') {
    header('Location: superadmin-dashboard.php');
    exit;
}
if (isGroomerAdmin($user)) {
    $pageTitle = 'Groomer Admin Dashboard';
    $isDashboard = true;
    $db = getDB();
    $groomerCount = $db->prepare("SELECT COUNT(*) FROM groomers g JOIN users u ON u.id=g.user_id JOIN clinics c ON c.id=g.clinic_id WHERE c.admin_id=? AND c.clinic_type='grooming' AND u.is_active=1");
    $groomerCount->execute([$user['id']]);
    $groomerCount = (int)$groomerCount->fetchColumn();
    $serviceCount = $db->prepare("SELECT COUNT(*) FROM services s JOIN clinics c ON c.id=s.clinic_id WHERE c.admin_id=? AND c.clinic_type='grooming' AND s.is_active=1 AND s.service_type='grooming'");
    $serviceCount->execute([$user['id']]);
    $serviceCount = (int)$serviceCount->fetchColumn();
    $pendingGrooming = $db->prepare("SELECT COUNT(*) FROM appointments a WHERE a.status='pending' AND a.appointment_type='grooming' AND a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type='grooming')");
    $pendingGrooming->execute([$user['id']]);
    $pendingGrooming = (int)$pendingGrooming->fetchColumn();
    $groomerAppointments = $db->prepare("SELECT g.id,CONCAT(staff.first_name,' ',staff.last_name) AS staff_name,COUNT(DISTINCT a.id) AS appointment_count FROM groomers g JOIN users staff ON staff.id=g.user_id JOIN clinics c ON c.id=g.clinic_id JOIN appointments a ON a.groomer_id=g.id AND a.clinic_id=c.id WHERE c.admin_id=? AND c.clinic_type='grooming' AND a.appointment_type='grooming' GROUP BY g.id,staff.first_name,staff.last_name ORDER BY appointment_count DESC,staff.last_name,staff.first_name");
    $groomerAppointments->execute([$user['id']]);
    $groomerAppointments = $groomerAppointments->fetchAll();
    $recentGrooming = $db->prepare("SELECT a.id,a.appointment_date,a.appointment_time,a.status,a.reason,a.notes,a.rejection_reason,a.reschedule_date,a.reschedule_time,p.name AS pet_name,p.species AS pet_species,p.breed AS pet_breed,CONCAT(owner.first_name,' ',owner.last_name) AS owner_name,owner.email AS owner_email,owner.phone AS owner_phone,COALESCE(s.name,a.appointment_type) AS service_name,CONCAT(groomer.first_name,' ',groomer.last_name) AS groomer_name FROM appointments a JOIN clinics c ON c.id=a.clinic_id JOIN pets p ON p.id=a.pet_id JOIN users owner ON owner.id=a.owner_id LEFT JOIN services s ON s.id=a.service_id LEFT JOIN groomers g ON g.id=a.groomer_id LEFT JOIN users groomer ON groomer.id=g.user_id WHERE c.admin_id=? AND c.clinic_type='grooming' AND a.appointment_type='grooming' ORDER BY a.created_at DESC LIMIT 8");
    $recentGrooming->execute([$user['id']]);
    $recentGrooming = $recentGrooming->fetchAll();
    include __DIR__ . '/../../includes/header.php';
    include __DIR__ . '/../../includes/sidebar_admin.php';
    ?>
    <main class="dash-main">
      <div class="dash-header"><h1>Hello, <?= sanitize($user['first_name']) ?>!</h1><p>Manage your grooming center’s appointments, groomers, and services.</p></div>
      <?= renderStatsRow([
        ['value'=>$pendingGrooming,'label'=>'Pending Grooming Appointments','icon'=>'fa-calendar-alt','color'=>'orange'],
        ['value'=>$groomerCount,'label'=>'Groomers','icon'=>'fa-cut','color'=>'green'],
        ['value'=>$serviceCount,'label'=>'Grooming Services','icon'=>'fa-list','color'=>'blue'],
      ]) ?>
      <div class="card" style="margin-top:1.5rem;">
        <div class="card-header"><h3>Groomers with the Most Appointments</h3></div>
        <div class="table-wrap">
          <?php if ($groomerAppointments): ?>
          <table class="data-table"><thead><tr><th>Groomer</th><th>Appointments</th></tr></thead><tbody>
            <?php foreach ($groomerAppointments as $staff): ?><tr><td><?= sanitize($staff['staff_name']) ?></td><td><?= number_format((int)$staff['appointment_count']) ?></td></tr><?php endforeach; ?>
          </tbody></table>
          <?php else: ?><div class="empty-state"><i class="fas fa-cut"></i><h3>No grooming staff appointments yet</h3></div><?php endif; ?>
        </div>
      </div>
      <div class="card" style="margin-top:1.5rem;">
        <div class="card-header"><h3>Grooming Appointment History</h3><a href="appointments.php" class="btn btn-sm btn-outline">View all history</a></div>
        <div class="table-wrap">
          <?php if ($recentGrooming): ?>
          <table><thead><tr><th>#</th><th>Pet</th><th>Owner</th><th>Service</th><th>Groomer</th><th>Date &amp; Time</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($recentGrooming as $appointment): ?><tr>
              <td>#<?= (int)$appointment['id'] ?></td>
              <td><strong><?= sanitize($appointment['pet_name']) ?></strong><div class="form-hint"><?= sanitize($appointment['pet_species']) ?><?php if ($appointment['pet_breed']): ?> · <?= sanitize($appointment['pet_breed']) ?><?php endif; ?></div></td>
              <td><strong><?= sanitize($appointment['owner_name']) ?></strong><?php if ($appointment['owner_email']): ?><div class="form-hint"><?= sanitize($appointment['owner_email']) ?></div><?php endif; ?><?php if ($appointment['owner_phone']): ?><div class="form-hint"><?= sanitize($appointment['owner_phone']) ?></div><?php endif; ?></td>
              <td><strong><?= sanitize($appointment['service_name']) ?></strong><?php if ($appointment['reason']): ?><div class="form-hint">Reason: <?= sanitize($appointment['reason']) ?></div><?php endif; ?><?php if ($appointment['notes']): ?><div class="form-hint">Notes: <?= sanitize($appointment['notes']) ?></div><?php endif; ?><?php if ($appointment['rejection_reason']): ?><div class="form-hint">Rejection reason: <?= sanitize($appointment['rejection_reason']) ?></div><?php endif; ?><?php if ($appointment['reschedule_date']): ?><div class="form-hint">Rescheduled to: <?= date('M j, Y', strtotime($appointment['reschedule_date'])) ?><?php if ($appointment['reschedule_time']): ?> at <?= date('g:i A', strtotime($appointment['reschedule_time'])) ?><?php endif; ?></div><?php endif; ?></td>
              <td><?= sanitize($appointment['groomer_name'] ?? '—') ?></td>
              <td><?= date('M j, Y', strtotime($appointment['appointment_date'])) ?> · <?= date('g:i A', strtotime($appointment['appointment_time'])) ?></td>
              <td><?= statusBadge($appointment['status']) ?></td>
            </tr><?php endforeach; ?>
          </tbody></table>
          <?php else: ?><div class="empty-state"><i class="fas fa-cut"></i><h3>No grooming appointments yet</h3></div><?php endif; ?>
        </div>
      </div>
    </main>
    <?php include __DIR__ . '/../../includes/footer.php'; exit;
}
$pageTitle = 'Vet Admin Dashboard';
$isDashboard = true;
$db = getDB();
$managedClinics = $db->prepare("SELECT name,city FROM clinics WHERE admin_id=? AND clinic_type='veterinary' AND is_active=1 ORDER BY name");
$managedClinics->execute([$user['id']]);
$managedClinics = $managedClinics->fetchAll();

// Date range filter
$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90])) $range = 30;
if ($range === 7) $rangeLabel = 'Last 7 days';
elseif ($range === 90) $rangeLabel = 'Last 90 days';
else $rangeLabel = 'Last 30 days';

function qn($db, $sql, $p = []) {
    $s = $db->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
}

// Veterinary dashboard data is limited to veterinary appointments and clinics.
$appointmentScope = " AND a.appointment_type='veterinary' AND a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type='veterinary')";
$totalAppts     = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)".$appointmentScope, [$range,$user['id']]);
$pendingAppts   = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.status='pending'".$appointmentScope, [$user['id']]);
$completedAppts = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.status='completed' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)".$appointmentScope, [$range,$user['id']]);
$cancelledAppts = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.status='cancelled' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)".$appointmentScope, [$range,$user['id']]);
$todayAppts     = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.appointment_date=CURDATE()".$appointmentScope, [$user['id']]);
$totalVets      = qn($db,"SELECT COUNT(*) FROM veterinarians v JOIN clinics c ON c.id=v.clinic_id JOIN users u ON u.id=v.user_id WHERE v.is_available=1 AND u.is_active=1 AND c.admin_id=? AND c.clinic_type='veterinary'", [$user['id']]);

// Growth — within range
$thisM = $totalAppts;
$lastM = qn($db,"SELECT COUNT(*) FROM appointments a WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND a.appointment_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)".$appointmentScope, [$range*2,$range,$user['id']]);
$apptGrowth = $lastM > 0 ? round((($thisM - $lastM) / $lastM) * 100, 1) : 0;


// Monthly chart (within range)
$monthly = $db->prepare("
    SELECT DATE_FORMAT(a.appointment_date,'%b %Y') AS lbl,
           DATE_FORMAT(a.appointment_date,'%Y-%m') AS ym,
           COUNT(*) AS tot,
           SUM(CASE WHEN a.status='completed' THEN 1 ELSE 0 END) AS done
    FROM appointments a
    WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND a.appointment_type='veterinary' AND a.clinic_id IN (SELECT id FROM clinics WHERE admin_id=? AND clinic_type='veterinary')
    GROUP BY ym, lbl ORDER BY ym ASC
");
$monthly->execute([$range,$user['id']]); $monthly = $monthly->fetchAll();

// Veterinary appointments used by the dashboard status summary.
$vetCount = qn($db,"SELECT COUNT(*) FROM appointments a WHERE 1=1".$appointmentScope, [$user['id']]);

// Staff appointment totals are based on existing veterinary appointment records.
$vetAppointmentStats = $db->prepare("SELECT v.id,CONCAT(staff.first_name,' ',staff.last_name) AS staff_name,COUNT(DISTINCT a.id) AS appointment_count FROM veterinarians v JOIN users staff ON staff.id=v.user_id JOIN clinics c ON c.id=v.clinic_id JOIN appointments a ON a.vet_id=v.id AND a.clinic_id=c.id WHERE c.admin_id=? AND c.clinic_type='veterinary' AND a.appointment_type='veterinary' GROUP BY v.id,staff.first_name,staff.last_name ORDER BY appointment_count DESC,staff.last_name,staff.first_name");
$vetAppointmentStats->execute([$user['id']]);
$vetAppointmentStats = $vetAppointmentStats->fetchAll();

// Recent appointments
$recentApptsStmt = $db->prepare("
    SELECT a.*, p.name AS pet_name,p.species AS pet_species,p.breed AS pet_breed,
           CONCAT(ou.first_name,' ',ou.last_name) AS owner_name,ou.email AS owner_email,ou.phone AS owner_phone,
           (SELECT COUNT(*) FROM pet_health_records hr
            JOIN veterinarians record_v ON record_v.id=hr.vet_id
            JOIN clinics record_c ON record_c.id=record_v.clinic_id
            WHERE hr.pet_id=p.id AND record_c.admin_id=? AND record_c.clinic_type='veterinary') AS medical_record_count,
           c.name AS clinic_name,
           COALESCE(s.name, a.appointment_type) AS svc,
           CONCAT(vu.first_name,' ',vu.last_name) AS veterinarian_name
    FROM appointments a
    JOIN pets p ON a.pet_id=p.id
    JOIN users ou ON a.owner_id=ou.id
    JOIN clinics c ON a.clinic_id=c.id
    LEFT JOIN services s ON a.service_id=s.id
    LEFT JOIN veterinarians v ON a.vet_id=v.id
    LEFT JOIN users vu ON v.user_id=vu.id
    WHERE a.appointment_type='veterinary' AND c.clinic_type='veterinary' AND c.admin_id=?
    ORDER BY a.created_at DESC LIMIT 10
");
$recentApptsStmt->execute([$user['id'],$user['id']]); $recentAppts = $recentApptsStmt->fetchAll();

// Recent users
$recentUsersStmt = $db->prepare("
    SELECT u.*, r.name AS role_name,
           (SELECT c.clinic_type FROM clinics c WHERE c.admin_id=u.id ORDER BY c.is_active DESC,c.id LIMIT 1) AS clinic_type
    FROM users u JOIN roles r ON u.role_id=r.id
    WHERE u.id IN (
        SELECT v.user_id FROM veterinarians v JOIN clinics c ON c.id=v.clinic_id WHERE c.admin_id=? AND c.clinic_type='veterinary'
        UNION SELECT a.owner_id FROM appointments a JOIN clinics c ON c.id=a.clinic_id WHERE c.admin_id=? AND c.clinic_type='veterinary' AND a.appointment_type='veterinary'
    ) OR u.id=?
    ORDER BY u.created_at DESC LIMIT 6
");
$recentUsersStmt->execute([$user['id'],$user['id'],$user['id']]); $recentUsers = $recentUsersStmt->fetchAll();
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
        <h1>Clinic Status Dashboard</h1>
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
  <?php if ($managedClinics): ?>
  <section class="card" style="margin-bottom:1.25rem;"><div class="card-body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;"><i class="fas fa-hospital" style="font-size:1.35rem;color:var(--primary);"></i><strong>Your clinic<?= count($managedClinics) === 1 ? '' : 's' ?>:</strong><div style="display:flex;gap:.5rem;flex-wrap:wrap;"><?php foreach ($managedClinics as $clinic): ?><span class="badge badge-primary"><?= sanitize($clinic['name']) ?><?= !empty($clinic['city']) ? ' · ' . sanitize($clinic['city']) : '' ?></span><?php endforeach; ?></div></div></section>
  <?php endif; ?>

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

  </div><!-- /.stats-row -->

  <!-- ── SECONDARY STATS ─────────────────────────────────── -->
  <div class="stats-row">
    
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
      <div class="stat-widget-icon sw-blue">
        <i class="fas fa-calendar-day"></i>
      </div>
      <div class="stat-widget-info">
        <strong><?= $todayAppts ?></strong>
        <span>Today's Appointments</span>
      </div>
    </div>
    
  </div><!-- /.stats-row -->

  <!-- ── CHARTS ROW ────────────────────────────────────────── -->
  <div class="dash-two-col">

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-header"><h3>Veterinarians with the Most Appointments</h3></div>
      <div class="table-wrap">
        <?php if ($vetAppointmentStats): ?>
        <table class="data-table"><thead><tr><th>Veterinarian</th><th>Appointments</th></tr></thead><tbody>
          <?php foreach ($vetAppointmentStats as $staff): ?><tr><td><?= sanitize($staff['staff_name']) ?></td><td><?= number_format((int)$staff['appointment_count']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <?php else: ?><div class="empty-state"><i class="fas fa-user-md"></i><h3>No veterinary staff appointments yet</h3></div><?php endif; ?>
      </div>
    </div>

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
              <span>Veterinary appointments: <?= $vetCount ?></span>
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
        Veterinary Appointment History
      </h3>
      <div class="card-header-actions">
        <?php if($pendingAppts > 0): ?>
        <a href="appointments.php?status=pending" class="btn btn-primary btn-sm">
          <i class="fas fa-inbox"></i>
          Review Pending (<?= $pendingAppts ?>)
        </a>
        <?php endif; ?>
        <a href="appointments.php" class="btn btn-outline btn-sm">
          View All History
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
            <th>#</th>
            <th>Pet</th>
            <th>Pet Owner</th>
            <th>Service / Details</th>
            <th>Veterinarian</th>
            <th>Clinic</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th>Medical Records</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recentAppts as $a): ?>
          <tr>
            <td style="font-size:.8rem;color:var(--text-muted);">#<?= (int)$a['id'] ?></td>
            <td>
              <div class="table-cell-primary"><?= sanitize($a['pet_name']) ?></div>
              <div class="table-cell-meta"><?= sanitize($a['pet_species']) ?><?php if ($a['pet_breed']): ?> · <?= sanitize($a['pet_breed']) ?><?php endif; ?></div>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['owner_name']) ?></div>
              <?php if ($a['owner_email']): ?><div class="table-cell-meta"><?= sanitize($a['owner_email']) ?></div><?php endif; ?>
              <?php if ($a['owner_phone']): ?><div class="table-cell-meta"><?= sanitize($a['owner_phone']) ?></div><?php endif; ?>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['svc']) ?></div>
              <?php if ($a['reason']): ?><div class="table-cell-meta">Reason: <?= sanitize($a['reason']) ?></div><?php endif; ?>
              <?php if ($a['notes']): ?><div class="table-cell-meta">Notes: <?= sanitize($a['notes']) ?></div><?php endif; ?>
              <?php if ($a['rejection_reason']): ?><div class="table-cell-meta">Rejection reason: <?= sanitize($a['rejection_reason']) ?></div><?php endif; ?>
              <?php if ($a['reschedule_date']): ?><div class="table-cell-meta">Rescheduled to: <?= date('M j, Y', strtotime($a['reschedule_date'])) ?><?php if ($a['reschedule_time']): ?> at <?= date('g:i A', strtotime($a['reschedule_time'])) ?><?php endif; ?></div><?php endif; ?>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['veterinarian_name'] ?? '—') ?></div>
            </td>
            <td>
              <div class="table-cell-secondary"><?= sanitize($a['clinic_name']) ?></div>
            </td>
            <td>
              <div class="table-cell-primary"><?= date('M j, Y', strtotime($a['appointment_date'])) ?></div>
              <div class="table-cell-meta"><?= date('g:i A', strtotime($a['appointment_time'])) ?></div>
            </td>
            <td>
              <?= statusBadge($a['status']) ?>
            </td>
            <td>
              <?php $medicalRecordCount = (int)$a['medical_record_count']; ?>
              <a href="records.php?pet=<?= (int)$a['pet_id'] ?>&amp;appt=<?= (int)$a['id'] ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-file-medical"></i> <?= $medicalRecordCount ? 'Records (' . $medicalRecordCount . ')' : 'Add Record' ?>
              </a>
            </td>
            <td class="text-right">
              <div class="table-actions">
                <?php $extraActions = ''; ?>
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
              if ($u['role_name'] === 'pet_owner') $roleBadge = 'badge-info';
              elseif ($u['role_name'] === 'veterinarian') $roleBadge = 'badge-success';
              elseif ($u['role_name'] === 'groomer') $roleBadge = 'badge-primary';
              elseif ($u['role_name'] === 'admin') $roleBadge = 'badge-warning';
              elseif ($u['role_name'] === 'superadmin') $roleBadge = 'badge-danger';
              else $roleBadge = 'badge-secondary';
            ?>
            <tr>
              <td>
                <div class="table-cell-primary"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></div>
              </td>
              <td>
                <div class="table-cell-secondary"><?= sanitize($u['email']) ?></div>
              </td>
              <td>
                <span class="badge <?= $roleBadge ?>"><?= displayRoleName($u['role_name'], $u['clinic_type'] ?? null) ?></span>
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
