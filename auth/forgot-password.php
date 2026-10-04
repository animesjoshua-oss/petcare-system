<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
$pageTitle = 'Forgot Password';
$msg = ''; $msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid request.'; $msgType = 'danger';
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Please enter a valid email.'; $msgType = 'danger';
        } else {
            $stmt = getDB()->prepare('SELECT id, first_name FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token   = generateToken();
                $expires = date('Y-m-d H:i:s', time() + 3600);
                getDB()->prepare('UPDATE users SET password_reset_token=?, password_reset_expires=? WHERE id=?')
                    ->execute([$token, $expires, $user['id']]);

                $resetLink = APP_URL . '/auth/reset-password.php?token=' . $token;
                $firstName = $user['first_name'];

                $subject = 'Reset Your PetCare+ Password';
                $body = '
                <!DOCTYPE html>
                <html>
                <body style="font-family:Poppins,sans-serif;background:#f8fafc;padding:2rem;">
                  <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,.08);">
                    <div style="text-align:center;margin-bottom:1.5rem;">
                      <div style="width:56px;height:56px;background:linear-gradient(135deg,#1E40FF,#00D4AA);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;">🔒</div>
                    </div>
                    <h2 style="text-align:center;font-size:1.4rem;font-weight:700;margin-bottom:.5rem;">Reset Your Password</h2>
                    <p style="color:#64748b;text-align:center;margin-bottom:1.5rem;">Hi ' . htmlspecialchars($firstName) . ', click the button below to reset your password. This link expires in 1 hour.</p>
                    <div style="text-align:center;margin-bottom:1.5rem;">
                      <a href="' . $resetLink . '" style="display:inline-block;background:#1E40FF;color:#fff;padding:.85rem 2rem;border-radius:50px;font-weight:600;text-decoration:none;">Reset Password</a>
                    </div>
                    <p style="color:#94a3b8;font-size:.8rem;text-align:center;">If you didn\'t request this, ignore this email. Your password won\'t change.</p>
                  </div>
                </body>
                </html>';

                sendMail($email, $firstName, $subject, $body);
            }
            // Always show same message — don't reveal if email exists
            $msg = 'If that email is registered, a reset link has been sent. Check your inbox.';
            $msgType = 'success';
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header-auth.php'; ?>
<div style="min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:3rem 1.5rem;">
  <div style="width:100%;max-width:440px;">
    <div class="card"><div class="card-body" style="padding:2.5rem;">
      <div style="text-align:center;margin-bottom:1.75rem;">
        <div style="width:60px;height:60px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#fff;"><i class="fas fa-lock"></i></div>
        <h2 style="font-size:1.5rem;font-weight:700;">Forgot Password?</h2>
        <p style="color:var(--text-muted);font-size:.9rem;">Enter your email and we'll send a reset link.</p>
      </div>
      <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>"><i class="fas fa-info-circle"></i><div><?= $msg ?></div></div>
      <?php endif; ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@email.com" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
      </form>
      <div class="auth-footer-text"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
