<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$isGroomerAdmin = isGroomerAdmin($user);
$pageTitle = 'Manage Clinics';
$isDashboard = true;
$db = getDB();
$isSuperAdmin = $user['role_name'] === 'superadmin';
$message = '';

if (isset($_GET['toggle'])) {
    if (!verifyCsrfToken($_GET['csrf'] ?? '')) {
        $message = 'This request could not be verified.';
    } else {
        $sql = 'UPDATE clinics SET is_active=NOT is_active WHERE id=?' . ($isSuperAdmin ? '' : ' AND admin_id=?') . ($isGroomerAdmin ? " AND clinic_type='grooming'" : '');
        $params = [(int)$_GET['toggle']];
        if (!$isSuperAdmin) $params[] = $user['id'];
        $db->prepare($sql)->execute($params);
        header('Location: clinics.php'); exit;
    }
}

$clinicSql = $isSuperAdmin
    ? "SELECT c.*,COALESCE(AVG(r.clinic_rating),0) AS avg_rating,COUNT(DISTINCT r.id) AS rcount FROM clinics c LEFT JOIN ratings r ON r.clinic_id=c.id"
    : 'SELECT c.* FROM clinics c';
if (!$isSuperAdmin) $clinicSql .= ' WHERE c.admin_id=?' . ($isGroomerAdmin ? " AND c.clinic_type='grooming'" : '');
$clinicSql .= ($isSuperAdmin ? ' GROUP BY c.id' : '') . ' ORDER BY c.name';
$clinicStmt = $db->prepare($clinicSql);
$clinicStmt->execute($isSuperAdmin ? [] : [$user['id']]);
$clinics = $clinicStmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header"><div class="admin-dash-header"><div><?php if ($isSuperAdmin): ?><h1>Clinics</h1><?php endif; ?><p><?= $isSuperAdmin ? 'Review verified clinic listings and applications.' : 'Manage your clinic profile and public information.' ?></p></div><?php if ($isSuperAdmin): ?><a href="clinic-applications.php" class="btn btn-primary"><i class="fas fa-file-circle-check"></i> Clinic Applications</a><?php endif; ?></div></div>
  <?php if ($message): ?><div class="alert alert-danger" role="alert"><?= sanitize($message) ?></div><?php endif; ?>
  <?php if ($isSuperAdmin): ?><div class="card"><div class="table-wrap">
    <?php if ($clinics): ?><table><thead><tr><th>Clinic</th><th>City</th><th>Contact</th><th>Rating</th><th>Reviews</th><th>Verification</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($clinics as $clinic): ?><tr>
        <td><strong><?= sanitize($clinic['name']) ?></strong><?php if ($clinic['address']): ?><div class="form-hint"><?= sanitize(substr($clinic['address'],0,70)) ?></div><?php endif; ?></td>
        <td><?= sanitize($clinic['city'] ?? '—') ?></td>
        <td><?= sanitize($clinic['email'] ?: ($clinic['phone'] ?: '—')) ?></td>
        <td><?= starRating((float)$clinic['avg_rating'], false) ?></td>
        <td><?= (int)$clinic['rcount'] ?></td>
        <td><?php if (!empty($clinic['is_verified'])): ?><span class="badge badge-success" title="<?= sanitize($clinic['verification_code'] ?? '') ?>"><i class="fas fa-shield-check"></i> Verified</span><?php else: ?><span class="badge badge-secondary">Legacy listing</span><?php endif; ?></td>
        <td><?= $clinic['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
        <td><div style="display:flex;gap:.4rem;flex-wrap:wrap;"><a href="clinic-profile.php?id=<?= (int)$clinic['id'] ?>" class="btn btn-sm btn-outline">Edit</a><a href="../../pages/clinic.php?id=<?= (int)$clinic['id'] ?>" class="btn btn-sm btn-outline" target="_blank" rel="noopener">Preview</a><a href="?toggle=<?= (int)$clinic['id'] ?>&amp;csrf=<?= generateCsrfToken() ?>" class="btn btn-sm <?= $clinic['is_active'] ? 'btn-danger' : 'btn-secondary' ?>" data-confirm="Toggle clinic status?"><?= $clinic['is_active'] ? 'Disable' : 'Enable' ?></a></div></td>
      </tr><?php endforeach; ?>
    </tbody></table><?php else: ?><div class="empty-state"><i class="fas fa-hospital"></i><h3>No clinics found</h3></div><?php endif; ?>
  </div></div><?php else: ?>
    <?php if ($clinics): ?>
      <section class="clinic-overview" aria-label="Assigned clinic">
        <?php foreach ($clinics as $clinic): ?>
          <article class="clinic-overview-row">
            <div class="clinic-overview-identity">
              <span class="clinic-overview-icon"><i class="fas fa-hospital"></i></span>
              <div><h2><?= sanitize($clinic['name']) ?></h2><p><?= sanitize($clinic['address'] ?: ($clinic['city'] ?? 'Clinic address not added')) ?></p></div>
            </div>
            <div class="clinic-overview-details">
              <span><small>City</small><?= sanitize($clinic['city'] ?: '—') ?></span>
              <span><small>Contact</small><?= sanitize($clinic['email'] ?: ($clinic['phone'] ?: '—')) ?></span>
              <span><small>Status</small><?= $clinic['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></span>
            </div>
            <div class="clinic-overview-actions"><a href="clinic-profile.php?id=<?= (int)$clinic['id'] ?>" class="btn btn-sm btn-outline">Edit</a><a href="../../pages/clinic.php?id=<?= (int)$clinic['id'] ?>" class="btn btn-sm btn-outline" target="_blank" rel="noopener">Preview</a><a href="?toggle=<?= (int)$clinic['id'] ?>&amp;csrf=<?= generateCsrfToken() ?>" class="btn btn-sm <?= $clinic['is_active'] ? 'btn-danger' : 'btn-secondary' ?>" data-confirm="Toggle clinic status?"><?= $clinic['is_active'] ? 'Disable' : 'Enable' ?></a></div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php else: ?><div class="empty-state"><i class="fas fa-hospital"></i><h3>No clinic assigned</h3><p>Approved applications will be assigned to your <?= $isGroomerAdmin ? 'Groomer Admin' : 'Vet Admin' ?> account.</p></div><?php endif; ?>
    <style>
      .clinic-overview{max-width:1100px;margin:1.5rem auto 0;border-top:1px solid var(--border-color,rgba(148,163,184,.2));}
      .clinic-overview-row{display:flex;align-items:center;gap:2rem;padding:1.5rem 0;border-bottom:1px solid var(--border-color,rgba(148,163,184,.2));}
      .clinic-overview-identity{display:flex;align-items:center;gap:1rem;min-width:240px;flex:1.1;}
      .clinic-overview-icon{display:grid;place-items:center;width:46px;height:46px;border-radius:14px;background:rgba(99,102,241,.14);color:var(--primary,#6366f1);font-size:1.1rem;flex:none;}
      .clinic-overview-identity h2{font-size:1.1rem;margin:0 0 .35rem;}.clinic-overview-identity p{margin:0;color:var(--text-muted,#94a3b8);font-size:.88rem;}
      .clinic-overview-details{display:flex;align-items:center;gap:1.75rem;flex:2;}.clinic-overview-details>span{display:flex;flex-direction:column;gap:.35rem;white-space:nowrap;}.clinic-overview-details small{color:var(--text-muted,#94a3b8);font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;}.clinic-overview-details em{font-size:.75rem;color:var(--text-muted,#94a3b8);font-style:normal;}
      .clinic-overview-actions{display:flex;gap:.45rem;flex-wrap:wrap;justify-content:flex-end;}
      @media(max-width:1000px){.clinic-overview-row{align-items:flex-start;flex-wrap:wrap;gap:1rem}.clinic-overview-identity{flex-basis:100%}.clinic-overview-details{flex-wrap:wrap;gap:1rem 1.5rem}.clinic-overview-actions{margin-left:auto}}
      @media(max-width:560px){.clinic-overview-details{display:grid;grid-template-columns:1fr 1fr;width:100%}.clinic-overview-actions{margin-left:0}}
    </style>
  <?php endif; ?>
</main>
<script>document.addEventListener('click',function(event){const link=event.target.closest('[data-confirm]');if(link&&!confirm(link.getAttribute('data-confirm'))){event.preventDefault();}});</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
