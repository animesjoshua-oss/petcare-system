<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['superadmin']);
$user = currentUser();
$db = getDB();
$pageTitle = 'Clinic Applications';
$isDashboard = true;
$message = '';
$messageType = 'success';
$temporaryPasswordReveal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session could not verify this action. Refresh and try again.';
        $messageType = 'danger';
    } else {
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $decision = (string)($_POST['decision'] ?? '');
        $reviewNotes = trim((string)($_POST['review_notes'] ?? ''));
        $applicationQuery = $db->prepare("SELECT * FROM clinic_applications WHERE id=? AND status='pending'");
        $applicationQuery->execute([$applicationId]);
        $application = $applicationQuery->fetch();

        if (!$application) {
            $message = 'This application is no longer pending.';
            $messageType = 'warning';
        } elseif (!in_array($decision, ['approve', 'decline'], true)) {
            $message = 'Choose approve or decline.';
            $messageType = 'danger';
        } elseif ($decision === 'decline' && $reviewNotes === '') {
            $message = 'Add a short reason so the applicant knows what to correct.';
            $messageType = 'danger';
        } else {
            require_once __DIR__ . '/../../includes/mailer.php';
            $applicationType = ($application['application_type'] ?? 'veterinary') === 'grooming' ? 'grooming' : 'veterinary';
            $adminLabel = $applicationType === 'grooming' ? 'Groomer Admin' : 'Vet Admin';
            if ($decision === 'decline') {
                $db->prepare("UPDATE clinic_applications SET status='declined',review_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='pending'")
                    ->execute([$reviewNotes, $user['id'], $applicationId]);
                $mail = sendMail(
                    $application['email'],
                    $application['admin_first_name'] . ' ' . $application['admin_last_name'],
                    'PetCare+ clinic application update',
                    clinicApplicationStatusEmailHtml($application['admin_first_name'], 'declined', $application['clinic_name'], $application['application_code'], $reviewNotes, '', '', $adminLabel)
                );
                $message = $mail === true ? 'Application declined and the applicant was emailed.' : 'Application declined, but the email could not be sent. Check the mail configuration.';
                $messageType = $mail === true ? 'success' : 'warning';
            } else {
                $emailCheck = $db->prepare('SELECT id FROM users WHERE LOWER(email)=? LIMIT 1');
                $emailCheck->execute([strtolower($application['email'])]);
                $adminRole = $db->prepare("SELECT id FROM roles WHERE name='admin' LIMIT 1");
                $adminRole->execute();
                $adminRoleId = (int)$adminRole->fetchColumn();
                if ($emailCheck->fetchColumn()) {
                    $message = 'A user already has this email. Resolve the duplicate account before approval.';
                    $messageType = 'danger';
                } elseif (!$adminRoleId) {
                    $message = 'The clinic administrator role is missing from the roles table.';
                    $messageType = 'danger';
                } else {
                    $temporaryPassword = bin2hex(random_bytes(8));
                    $verificationCode = 'PCV-' . strtoupper(bin2hex(random_bytes(6)));
                    try {
                        $db->beginTransaction();
                        $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,is_active,is_email_verified,must_change_password) VALUES (?,?,?,?,?,?,1,1,1)')
                            ->execute([$adminRoleId, $application['admin_first_name'], $application['admin_last_name'], strtolower($application['email']), $application['phone'], hashPassword($temporaryPassword)]);
                        $adminId = (int)$db->lastInsertId();
                        $db->prepare('INSERT INTO clinics (admin_id,clinic_type,name,description,accessibility_notes,address,city,phone,email,is_active,is_verified,verification_code) VALUES (?,?,?,?,?,?,?,?,?,1,1,?)')
                            ->execute([$adminId, $applicationType, $application['clinic_name'], $application['description'], $application['accessibility_notes'], $application['address'], $application['city'], $application['phone'], strtolower($application['email']), $verificationCode]);
                        $clinicId = (int)$db->lastInsertId();
                        $db->prepare("UPDATE clinic_applications SET status='approved',review_notes=?,reviewed_by=?,reviewed_at=NOW(),clinic_id=? WHERE id=? AND status='pending'")
                            ->execute([$reviewNotes ?: null, $user['id'], $clinicId, $applicationId]);
                        $db->commit();

                        $mail = sendMail(
                            $application['email'],
                            $application['admin_first_name'] . ' ' . $application['admin_last_name'],
                            'PetCare+ clinic application approved',
                            clinicApplicationStatusEmailHtml($application['admin_first_name'], 'approved', $application['clinic_name'], $application['application_code'], '', strtolower($application['email']), $temporaryPassword, $adminLabel)
                        );
                        if ($mail === true) {
                            $message = "Application approved. The clinic listing and {$adminLabel} account were created, and login details were emailed.";
                            $messageType = 'success';
                        } else {
                            $message = 'Application approved and account created, but email delivery failed. Give the one-time password below to the applicant and check the mail configuration.';
                            $messageType = 'warning';
                            $temporaryPasswordReveal = $temporaryPassword;
                        }
                    } catch (Throwable $e) {
                        if ($db->inTransaction()) $db->rollBack();
                        error_log('Clinic application approval failed: ' . $e->getMessage());
                        $message = 'The application could not be approved. No clinic account was created.';
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

$applications = $db->query("SELECT ca.*, CONCAT(reviewer.first_name,' ',reviewer.last_name) AS reviewer_name, c.name AS approved_clinic_name FROM clinic_applications ca LEFT JOIN users reviewer ON reviewer.id=ca.reviewed_by LEFT JOIN clinics c ON c.id=ca.clinic_id ORDER BY (ca.status='pending') DESC, ca.created_at DESC LIMIT 100")->fetchAll();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM clinic_applications WHERE status='pending'")->fetchColumn();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header"><div class="dash-breadcrumb"><a href="superadmin-dashboard.php">Dashboard</a> / <span>Clinic Applications</span></div><h1>Clinic Applications</h1><p>Review Vet Admin and Groomer Admin applications before creating a clinic listing and administrator account.</p></div>
  <?php if ($message): ?><div class="alert alert-<?= $messageType ?>" role="status"><?= sanitize($message) ?></div><?php endif; ?>
  <?php if ($temporaryPasswordReveal): ?><div class="card" style="margin-bottom:1rem;border-color:var(--warning);"><div class="card-body"><strong>One-time password:</strong> <code><?= sanitize($temporaryPasswordReveal) ?></code><p class="form-hint">Share this privately with the applicant. They must replace it at first sign-in.</p></div></div><?php endif; ?>
  <div class="stats-row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:1rem;"><div class="stat-widget"><div class="stat-widget-icon sw-orange"><i class="fas fa-hourglass-half"></i></div><div class="stat-widget-info"><strong><?= $pendingCount ?></strong><span>Pending applications</span></div></div></div>
  <div class="card"><div class="table-wrap">
    <?php if ($applications): ?><table><thead><tr><th>Application</th><th>Type</th><th>Applicant</th><th>Clinic</th><th>License</th><th>Credentials</th><th>Status</th><th>Review</th></tr></thead><tbody>
      <?php foreach ($applications as $application): ?><tr>
        <td><strong><?= sanitize($application['application_code']) ?></strong><div class="form-hint"><?= date('M j, Y', strtotime($application['created_at'])) ?></div></td>
        <td><span class="badge badge-<?= ($application['application_type'] ?? 'veterinary') === 'grooming' ? 'warning' : 'primary' ?>"><?= ($application['application_type'] ?? 'veterinary') === 'grooming' ? 'Groomer Admin' : 'Vet Admin' ?></span></td>
        <td><?= sanitize($application['admin_first_name'] . ' ' . $application['admin_last_name']) ?><div class="form-hint"><?= sanitize($application['email']) ?></div></td>
        <td><strong><?= sanitize($application['clinic_name']) ?></strong><div class="form-hint"><?= sanitize(trim(($application['city'] ?? '') . ' · ' . $application['address'], ' ·')) ?></div></td>
        <td><?= ($application['application_type'] ?? 'veterinary') === 'grooming' ? '—' : sanitize($application['license_number'] ?? '') ?></td>
        <td><?php if (!empty($application['credential_path'])): ?><a class="btn btn-sm btn-outline" href="clinic-application-document.php?id=<?= (int)$application['id'] ?>" target="_blank" rel="noopener"><i class="fas fa-file-shield"></i> View credential</a><?php else: ?>—<?php endif; ?></td>
        <td><?php if ($application['status'] === 'approved'): ?><span class="badge badge-success">Approved</span><?php elseif ($application['status'] === 'declined'): ?><span class="badge badge-danger">Declined</span><?php else: ?><span class="badge badge-warning">Pending</span><?php endif; ?></td>
        <td>
          <?php if ($application['status'] === 'pending'): ?>
            <form method="POST" style="min-width:250px;display:grid;gap:.4rem;">
              <?= csrfField() ?><input type="hidden" name="application_id" value="<?= (int)$application['id'] ?>">
              <textarea name="review_notes" class="form-control" rows="2" maxlength="1000" placeholder="Optional approval note; required when declining"></textarea>
              <div style="display:flex;gap:.4rem;"><button class="btn btn-sm btn-primary" name="decision" value="approve"><i class="fas fa-check"></i> Approve</button><button class="btn btn-sm btn-danger" name="decision" value="decline" onclick="return confirm('Decline this clinic application?')"><i class="fas fa-times"></i> Decline</button></div>
            </form>
          <?php else: ?><span class="form-hint">Reviewed <?= $application['reviewed_at'] ? date('M j, Y', strtotime($application['reviewed_at'])) : '' ?><?= $application['reviewer_name'] ? ' by ' . sanitize($application['reviewer_name']) : '' ?></span><?php if ($application['status'] === 'declined' && $application['review_notes']): ?><div class="form-hint"><?= sanitize($application['review_notes']) ?></div><?php endif; ?><?php endif; ?>
        </td>
      </tr><?php endforeach; ?>
    </tbody></table><?php else: ?><div class="empty-state"><i class="fas fa-file-circle-check"></i><h3>No clinic applications yet</h3><p>Applications submitted through the public form will appear here.</p></div><?php endif; ?>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
