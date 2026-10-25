<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/clinic_admin_profile.php';
requireLogin(); requireRole(['superadmin']);
$user = currentUser();
$db = getDB();
$pageTitle = 'Super Admin Dashboard';
$isDashboard = true;
$clinicCount = (int)$db->query('SELECT COUNT(*) FROM clinics')->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM clinic_applications WHERE status='pending'")->fetchColumn();
ensureClinicAdminProfilesTable($db);
$vetCount = (int)$db->query("SELECT (SELECT COUNT(*) FROM veterinarians v JOIN users u ON u.id=v.user_id WHERE u.is_active=1) + (SELECT COUNT(DISTINCT c.admin_id) FROM clinics c JOIN users u ON u.id=c.admin_id WHERE u.is_active=1)")->fetchColumn();
$pendingApplicationRows = $db->query("SELECT id,application_code,application_type,clinic_name,admin_first_name,admin_last_name,email,city,created_at FROM clinic_applications WHERE status='pending' ORDER BY created_at ASC LIMIT 8")->fetchAll();
$veterinarians = $db->query("SELECT CONCAT(u.first_name,' ',u.last_name) AS full_name,u.email,v.license_number,v.specialization,c.name AS clinic_name,c.is_verified,'Veterinarian' AS member_role FROM veterinarians v JOIN users u ON u.id=v.user_id JOIN clinics c ON c.id=v.clinic_id WHERE u.is_active=1 ORDER BY c.name,u.last_name,u.first_name")->fetchAll();
$vetAdmins = $db->query("SELECT CONCAT(u.first_name,' ',u.last_name) AS full_name,u.email,NULL AS license_number,ap.specialization,c.name AS clinic_name,c.is_verified,'Vet Admin' AS member_role FROM clinics c JOIN users u ON u.id=c.admin_id LEFT JOIN clinic_admin_profiles ap ON ap.user_id=u.id WHERE u.is_active=1 AND c.clinic_type='veterinary' ORDER BY c.name,u.last_name,u.first_name")->fetchAll();
$veterinarians = array_merge($veterinarians, $vetAdmins);
usort($veterinarians, static fn($a, $b) => [$a['clinic_name'], $a['full_name']] <=> [$b['clinic_name'], $b['full_name']]);
$veterinarians = array_slice($veterinarians, 0, 12);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header"><div class="admin-dash-header"><div><div class="dash-breadcrumb"><a href="<?= APP_URL ?>/index.php">Home</a> / <span>Super Admin</span></div><h1>Super Admin Dashboard</h1><p>Review clinic registrations, verify credentials, and view the veterinary directory.</p></div><a href="clinic-applications.php" class="btn btn-primary"><i class="fas fa-file-circle-check"></i> Review applications<?= $pendingCount ? ' (' . $pendingCount . ')' : '' ?></a></div></div>
  <div class="stats-row">
    <div class="stat-widget"><div class="stat-widget-icon sw-blue"><i class="fas fa-hospital"></i></div><div class="stat-widget-info"><strong><?= number_format($clinicCount) ?></strong><span>Clinic listings</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-orange"><i class="fas fa-hourglass-half"></i></div><div class="stat-widget-info"><strong><?= number_format($pendingCount) ?></strong><span>Applications to review</span></div></div>
    <div class="stat-widget"><div class="stat-widget-icon sw-purple"><i class="fas fa-user-md"></i></div><div class="stat-widget-info"><strong><?= number_format($vetCount) ?></strong><span>Veterinarians &amp; Vet Admins</span></div></div>
  </div>
  <section class="card" style="margin-bottom:1.25rem;"><div class="card-header"><h2>Clinic applications</h2><a href="clinic-applications.php" class="btn btn-sm btn-outline">Open application list</a></div><div class="table-wrap">
    <?php if ($pendingApplicationRows): ?><table><thead><tr><th>Reference</th><th>Type</th><th>Clinic</th><th>Applicant</th><th>Location</th><th>Submitted</th><th></th></tr></thead><tbody><?php foreach ($pendingApplicationRows as $application): ?><tr><td><?= sanitize($application['application_code']) ?></td><td><?= ($application['application_type'] ?? 'veterinary') === 'grooming' ? 'Groomer Admin' : 'Vet Admin' ?></td><td><?= sanitize($application['clinic_name']) ?></td><td><?= sanitize($application['admin_first_name'] . ' ' . $application['admin_last_name']) ?><div class="form-hint"><?= sanitize($application['email']) ?></div></td><td><?= sanitize(trim(($application['city'] ?? '') . ', Pangasinan', ', ')) ?></td><td><?= date('M j, Y', strtotime($application['created_at'])) ?></td><td><a href="clinic-applications.php" class="btn btn-sm btn-primary">Review</a></td></tr><?php endforeach; ?></tbody></table><?php else: ?><div class="empty-state"><i class="fas fa-circle-check"></i><h3>No pending applications</h3><p>New clinic applications will appear here.</p></div><?php endif; ?>
  </div></section>
  <section class="card"><div class="card-header"><h2>Veterinary team directory</h2><a href="vets.php" class="btn btn-sm btn-outline">View veterinary team</a></div><div class="table-wrap">
    <?php if ($veterinarians): ?><table><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Clinic</th><th>Specialization</th><th>License</th><th>Clinic status</th></tr></thead><tbody><?php foreach ($veterinarians as $vet): ?><tr><td><?= $vet['member_role'] === 'Veterinarian' ? 'Dr. ' : '' ?><?= sanitize($vet['full_name']) ?></td><td><?= sanitize($vet['member_role']) ?></td><td><?= sanitize($vet['email']) ?></td><td><?= sanitize($vet['clinic_name']) ?></td><td><?= sanitize($vet['specialization'] ?: '—') ?></td><td><?= sanitize($vet['license_number'] ?: '—') ?></td><td><?= $vet['is_verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-secondary">Not verified</span>' ?></td></tr><?php endforeach; ?></tbody></table><?php else: ?><div class="empty-state"><i class="fas fa-user-md"></i><h3>No veterinary staff yet</h3><p>Clinic Vet Admins and veterinarians will appear here.</p></div><?php endif; ?>
  </div></section>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
