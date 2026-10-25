<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pageTitle = 'Set Your Password';
$db = getDB();
$isRequired = (int)($user['must_change_password'] ?? 0) === 1;
$message = '';
$messageType = 'danger';

if (!$isRequired) {
    redirectByRole();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session could not verify this request. Refresh and try again.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password2'] ?? '');
        if (strlen($password) < 8 || !preg_match('/[0-9]/', $password)) {
            $message = 'Use at least 8 characters and include a number.';
        } elseif ($password !== $confirm) {
            $message = 'The passwords do not match.';
        } else {
            $db->prepare('UPDATE users SET password_hash=?,must_change_password=0 WHERE id=?')
                ->execute([hashPassword($password), $user['id']]);
            $dashboardPath = match ($user['role_name']) {
                'admin' => 'admin/index.php',
                'veterinarian' => 'vet/index.php',
                'groomer' => 'groomer/index.php',
                default => 'admin/index.php',
            };
            header('Location: ' . APP_URL . '/dashboard/' . $dashboardPath . '?password_updated=1');
            exit;
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>
<div style="min-height:100vh;padding:5rem 1.5rem;display:flex;align-items:center;justify-content:center;background:var(--bg);">
  <div style="width:100%;max-width:460px;">
    <div class="card"><div class="card-body" style="padding:2.5rem;">
      <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem;text-align:center;">Set your password</h1>
      <p style="color:var(--text-muted);text-align:center;margin-bottom:1.5rem;">Your account uses a one-time password. Choose a private password to continue.</p>
      <?php if ($message): ?><div class="alert alert-<?= $messageType ?>" role="alert"><?= sanitize($message) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="form-group"><label class="form-label" for="password">New password</label><input id="password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
        <div class="form-group"><label class="form-label" for="password2">Confirm password</label><input id="password2" type="password" name="password2" class="form-control" minlength="8" autocomplete="new-password" required></div>
        <button type="submit" class="btn btn-primary btn-block">Save password</button>
      </form>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
