<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle = 'Reports';
$isDashboard = true;
$db = getDB();

$from = sanitize($_GET['from'] ?? date('Y-m-01'));
$to   = sanitize($_GET['to']   ?? date('Y-m-t'));

// Key metrics
$totalAppts    = (int)$db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?")->execute([$from,$to]) ? $db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?")->execute([$from,$to]) : 0;
function qGet($db,$sql,$params){ $s=$db->prepare($sql); $s->execute($params); return $s->fetchColumn(); }

$totalAppts    = (int)qGet($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?",[$from,$to]);
$completedAppts= (int)qGet($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status='completed'",[$from,$to]);
$cancelledAppts= (int)qGet($db,"SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status='cancelled'",[$from,$to]);
$newUsers      = (int)qGet($db,"SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?",[$from.' 00:00:00',$to.' 23:59:59']);
$avgRating     = round((float)qGet($db,"SELECT COALESCE(AVG(clinic_rating),0) FROM ratings r JOIN appointments a ON r.appointment_id=a.id WHERE a.appointment_date BETWEEN ? AND ?",[$from,$to]),2);
$vetRevenue    = (int)qGet($db,"SELECT COUNT(*) FROM appointments WHERE appointment_type='veterinary' AND appointment_date BETWEEN ? AND ?",[$from,$to]);
$groomRevenue  = (int)qGet($db,"SELECT COUNT(*) FROM appointments WHERE appointment_type='grooming' AND appointment_date BETWEEN ? AND ?",[$from,$to]);

// Top clinics
$topClinics = $db->prepare("SELECT c.name,COUNT(a.id) AS appt_count,COALESCE(AVG(r.clinic_rating),0) AS avg_rating FROM clinics c LEFT JOIN appointments a ON a.clinic_id=c.id AND a.appointment_date BETWEEN ? AND ? LEFT JOIN ratings r ON r.clinic_id=c.id GROUP BY c.id ORDER BY appt_count DESC LIMIT 5");
$topClinics->execute([$from,$to]); $topClinics=$topClinics->fetchAll();

// Daily breakdown
$daily = $db->prepare("SELECT appointment_date,COUNT(*) AS cnt FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY appointment_date ORDER BY appointment_date ASC");
$daily->execute([$from,$to]); $daily=$daily->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <div><h1>Reports &amp; Analytics</h1><p>Platform performance for <?= date('M j',strtotime($from)) ?> – <?= date('M j, Y',strtotime($to)) ?></p></div>
      <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;">
        <div class="form-group" style="margin:0;"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= $from ?>"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= $to ?>"></div>
        <button class="btn btn-primary" style="height:44px;">Apply</button>
      </form>
    </div>
  </div>

  <!-- Key Metrics -->
  <div class="stats-row stats-odd">
    <div class="stat-widget"><div class="stat-widget-icon sw-blue"><i class="fas fa-calendar"></i></div><div class="stat-widget-info"><strong><?= $totalAppts ?></strong><span>Total Appointments</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-green"><i class="fas fa-check-circle"></i></div><div class="stat-widget-info"><strong><?= $completedAppts ?></strong><span>Completed</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-red"><i class="fas fa-times-circle"></i></div><div class="stat-widget-info"><strong><?= $cancelledAppts ?></strong><span>Cancelled</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-orange"><i class="fas fa-user-plus"></i></div><div class="stat-widget-info"><strong><?= $newUsers ?></strong><span>New Users</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-purple"><i class="fas fa-star"></i></div><div class="stat-widget-info"><strong><?= $avgRating ?>★</strong><span>Avg Rating</span></div></div>
  </div>

  <div class="dash-two-col" style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <!-- Daily chart -->
    <div class="card">
      <div class="card-header"><h3>Daily Appointments</h3></div>
      <div class="card-body"><canvas id="dailyChart" height="100"></canvas></div>
    </div>
    <!-- Service breakdown -->
    <div class="card">
      <div class="card-header"><h3>Service Type Breakdown</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;justify-content:center;align-items:center;">
        <canvas id="typeChart" width="180" height="180"></canvas>
        <div style="display:flex;gap:1.5rem;margin-top:1rem;font-size:.85rem;">
          <span><span style="display:inline-block;width:12px;height:12px;background:var(--primary);border-radius:50%;margin-right:.3rem;"></span>Veterinary (<?= $vetRevenue ?>)</span>
          <span><span style="display:inline-block;width:12px;height:12px;background:var(--warning);border-radius:50%;margin-right:.3rem;"></span>Grooming (<?= $groomRevenue ?>)</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Clinics -->
  <div class="card">
    <div class="card-header"><h3>Top Performing Clinics</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Clinic</th><th>Appointments</th><th>Avg Rating</th><th>Performance</th></tr></thead>
        <tbody>
        <?php foreach ($topClinics as $tc): ?>
        <tr>
          <td style="font-weight:600;"><?= sanitize($tc['name']) ?></td>
          <td><?= $tc['appt_count'] ?></td>
          <td><?= starRating((float)$tc['avg_rating']) ?></td>
          <td>
            <div style="background:var(--border);border-radius:50px;height:8px;width:160px;">
              <div style="background:var(--primary);height:8px;border-radius:50px;width:<?= $topClinics[0]['appt_count']>0 ? min(100,round($tc['appt_count']/$topClinics[0]['appt_count']*100)) : 0 ?>%;"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const dLabels = <?= json_encode(array_map(fn($d)=>date('M j',strtotime($d['appointment_date'])),$daily)) ?>;
const dData   = <?= json_encode(array_map('intval',array_column($daily,'cnt'))) ?>;
new Chart(document.getElementById('dailyChart'), {
  type:'line',
  data:{ labels:dLabels, datasets:[{ label:'Appointments', data:dData, borderColor:'#1E40FF', backgroundColor:'rgba(30,64,255,.1)', fill:true, tension:.4, pointRadius:4 }] },
  options:{ plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1}}, x:{grid:{display:false}} } }
});
new Chart(document.getElementById('typeChart'), {
  type:'doughnut',
  data:{ labels:['Veterinary','Grooming'], datasets:[{ data:[<?= $vetRevenue ?>,<?= $groomRevenue ?>], backgroundColor:['#1E40FF','#F59E0B'], borderWidth:0 }] },
  options:{ plugins:{legend:{display:false}}, cutout:'65%' }
});
</script>
