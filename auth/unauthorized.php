<?php require_once __DIR__ . '/../includes/functions.php'; $pageTitle='Unauthorized'; ?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:5rem 1.5rem;text-align:center;">
  <div>
    <div style="font-size:5rem;margin-bottom:1rem;">🚫</div>
    <h1 style="font-size:2rem;font-weight:800;margin-bottom:.75rem;">Access Denied</h1>
    <p style="color:var(--text-muted);margin-bottom:2rem;max-width:400px;margin-left:auto;margin-right:auto;">You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
    <div style="display:flex;gap:1rem;justify-content:center;">
      <a href="<?= APP_URL ?>/index.php" class="btn btn-primary">Go Home</a>
      <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
