<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Verify a Veterinary Clinic';
$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$clinic = null;
if (preg_match('/^PCV-[A-F0-9]{12}$/', $code)) {
    $stmt = getDB()->prepare('SELECT name,address,city,province,verification_code FROM clinics WHERE verification_code=? AND is_verified=1 LIMIT 1');
    $stmt->execute([$code]);
    $clinic = $stmt->fetch() ?: null;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="max-width:720px;padding:3rem 1rem 4rem;">
  <div class="card"><div class="card-body" style="padding:2rem;">
    <?php if ($clinic): ?>
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(34,197,94,.12);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fas fa-shield-check"></i></div>
      <h1 style="margin:1rem 0 .5rem;">Verified PetCare+ clinic</h1>
      <p><strong><?= sanitize($clinic['name']) ?></strong></p>
      <p><?= sanitize($clinic['address']) ?><?= $clinic['city'] ? ', ' . sanitize($clinic['city']) : '' ?><?= $clinic['province'] ? ', ' . sanitize($clinic['province']) : '' ?></p>
      <p>Verification code: <code><?= sanitize($clinic['verification_code']) ?></code></p>
      <p class="form-hint">PetCare+ issued this code after a Super Admin reviewed the clinic application credentials.</p>
    <?php else: ?>
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,.12);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fas fa-circle-exclamation"></i></div>
      <h1 style="margin:1rem 0 .5rem;">Clinic code not verified</h1>
      <p>We could not find an approved clinic for that code. Check the code and try again.</p>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/pages/clinics.php" class="btn btn-outline" style="margin-top:1rem;">Find clinics</a>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
