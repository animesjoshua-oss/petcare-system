<?php
// ============================================================
// PetCare+ Mailer – PHPMailer + Gmail SMTP
// ============================================================
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email via Gmail SMTP.
 * Returns true on success, error string on failure.
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool|string
{
    if (!MAIL_ENABLED) {
        // The registration flow can mark email verified and keep the account pending approval.
        return 'Email delivery is not configured.';
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return $mail->ErrorInfo;
    }
}

/**
 * Generate a 6-digit OTP and store in session with expiry.
 */
function generateOtp(): string
{
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp_code']    = password_hash($otp, PASSWORD_BCRYPT);
    $_SESSION['otp_expires'] = time() + 600; // 10 minutes
    return $otp;
}

/**
 * Verify OTP submitted by user.
 */
function verifyOtp(string $input): bool
{
    if (empty($_SESSION['otp_code']) || empty($_SESSION['otp_expires'])) return false;
    if (time() > $_SESSION['otp_expires']) return false;
    return password_verify(trim($input), $_SESSION['otp_code']);
}

/**
 * Build styled OTP email HTML.
 */
function otpEmailHtml(string $firstName, string $otp, string $purpose = 'login'): string
{
    $title = $purpose === 'login' ? 'Login Verification' : 'Email Verification';
    $message = $purpose === 'login' 
        ? "You're signing in to your <strong>PetCare+ account</strong>.<br>Use the OTP below to complete your login. It expires in <strong>10 minutes</strong>."
        : "Thank you for registering at <strong>PetCare+ Clinic Hub</strong>.<br>Use the OTP below to verify your email address. It expires in <strong>10 minutes</strong>.";
    $footer = $purpose === 'login'
        ? "If you did not attempt to log in, please secure your account immediately."
        : "If you did not register, ignore this email.";
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:36px;text-align:center;">
            <div style="font-size:2rem;">🐾</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">{$title}</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.7;margin-bottom:1.5rem;">
              {$message}
            </p>
            <!-- OTP Box -->
            <div style="text-align:center;margin:24px 0;">
              <div style="display:inline-block;background:#F1F5FF;border:2px dashed #1E40FF;
                          border-radius:12px;padding:18px 40px;">
                <span style="font-size:2.4rem;font-weight:800;letter-spacing:10px;color:#1E40FF;">
                  {$otp}
                </span>
              </div>
            </div>
            <p style="font-size:.82rem;color:#94A3B8;text-align:center;margin-top:1rem;">
              ⏱ This OTP expires in 10 minutes.<br>
              {$footer}
            </p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#F8FAFC;padding:20px;text-align:center;
                     border-top:1px solid #E2E8F0;">
            <p style="font-size:.78rem;color:#94A3B8;margin:0;">
              &copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Build account approval/rejection email HTML.
 */
function accountStatusEmailHtml(string $firstName, string $status, string $reason = ''): string
{
    $loginUrl = APP_URL . '/auth/login.php';
    $registerUrl = APP_URL . '/auth/register.php';

    if ($status === 'approved') {
        $color   = '#16A34A';
        $icon    = '✅';
        $heading = 'Account Approved!';
        $body    = "Great news! Your PetCare+ account has been <strong>verified and approved</strong>. You can now log in and start using the platform.";
        $btnText = 'Log In Now';
        $btnUrl  = $loginUrl;
    } else {
        $color   = '#DC2626';
        $icon    = '❌';
        $heading = 'Account Verification Not Approved';
        $safeReason = htmlspecialchars($reason ?: 'Your submitted ID could not be verified.');
        $body    = "Unfortunately, we were unable to verify your account at this time.<br><br>
                    <strong>Reason:</strong> {$safeReason}<br><br>
                    You may register again with a clearer ID photo and correct information.";
        $btnText = 'Register Again';
        $btnUrl  = $registerUrl;
    }

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:36px;text-align:center;">
            <div style="font-size:2rem;">🐾</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">Account Verification</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px;">
            <div style="text-align:center;font-size:2.5rem;margin-bottom:.5rem;">{$icon}</div>
            <h2 style="text-align:center;color:{$color};font-size:1.3rem;margin-bottom:1rem;">{$heading}</h2>
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.7;margin-bottom:1.5rem;">{$body}</p>
            <div style="text-align:center;margin:24px 0;">
              <a href="{$btnUrl}" style="background:{$color};color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:700;font-size:.95rem;display:inline-block;">{$btnText}</a>
            </div>
            <p style="font-size:.82rem;color:#94A3B8;text-align:center;">
              If you have questions, contact our support team.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:20px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.78rem;color:#94A3B8;margin:0;">
              &copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

/** Clinic registration decision email. Approved applicants receive a one-time password. */
function clinicApplicationStatusEmailHtml(
    string $firstName,
    string $status,
    string $clinicName,
    string $applicationCode,
    string $reason = '',
    string $email = '',
    string $temporaryPassword = '',
    string $adminLabel = 'Vet Admin'
): string {
    $safeName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $safeClinic = htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($applicationCode, ENT_QUOTES, 'UTF-8');
    $safeAdminLabel = htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8');
    $loginUrl = htmlspecialchars(APP_URL . '/auth/login.php', ENT_QUOTES, 'UTF-8');
    if ($status === 'approved') {
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $heading = 'Your clinic application was approved';
        $color = '#15803D';
        $body = "<p>Your application for <strong>{$safeClinic}</strong> was approved. Your {$safeAdminLabel} account is ready.</p>
            <p><strong>Login email:</strong> {$safeEmail}<br><strong>One-time password:</strong> {$safePassword}</p>
            <p>After signing in, PetCare+ will ask you to replace this password before opening your dashboard.</p>
            <p>Application reference: {$safeCode}</p>
            <p><a href=\"{$loginUrl}\" style=\"display:inline-block;background:{$color};color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700\">Sign in as {$safeAdminLabel}</a></p>";
    } else {
        $safeReason = htmlspecialchars($reason ?: 'The submitted application information could not be verified.', ENT_QUOTES, 'UTF-8');
        $heading = 'Your clinic application was declined';
        $color = '#B91C1C';
        $body = "<p>We could not approve the application for <strong>{$safeClinic}</strong>.</p>
            <p><strong>Review note:</strong> {$safeReason}</p>
            <p>You may submit a new application with updated information. Reference: {$safeCode}</p>";
    }

    return "<!doctype html><html><head><meta charset=\"UTF-8\"></head><body style=\"margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b\">
      <div style=\"max-width:560px;margin:32px auto;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden\">
        <div style=\"padding:24px 28px;background:#0f766e;color:#fff\"><strong>PetCare+ Clinic Hub</strong></div>
        <div style=\"padding:28px\"><p>Hello {$safeName},</p><h1 style=\"font-size:21px;color:{$color}\">{$heading}</h1>{$body}</div>
        <div style=\"padding:16px 28px;background:#f8fafc;color:#64748b;font-size:12px\">PetCare+ Clinic Hub · Pangasinan, Philippines</div>
      </div></body></html>";
}

/** Temporary account credentials for clinic-created veterinarian and groomer accounts. */
function clinicStaffCredentialsEmailHtml(string $firstName, string $roleName, string $clinicName, string $email, string $temporaryPassword): string {
    $values = array_map(static fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), [$firstName, $roleName, $clinicName, $email, $temporaryPassword]);
    [$safeName, $safeRole, $safeClinic, $safeEmail, $safePassword] = $values;
    $loginUrl = htmlspecialchars(APP_URL . '/auth/login.php', ENT_QUOTES, 'UTF-8');
    return "<!doctype html><html><head><meta charset=\"UTF-8\"></head><body style=\"margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b\">
      <div style=\"max-width:560px;margin:32px auto;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden\">
        <div style=\"padding:24px 28px;background:#0f766e;color:#fff\"><strong>PetCare+ Clinic Hub</strong></div>
        <div style=\"padding:28px\"><p>Hello {$safeName},</p><p>Your {$safeRole} account for <strong>{$safeClinic}</strong> is ready.</p>
          <p><strong>Login email:</strong> {$safeEmail}<br><strong>One-time password:</strong> {$safePassword}</p>
          <p>After signing in, choose a new private password before continuing.</p>
          <p><a href=\"{$loginUrl}\">Sign in to PetCare+</a></p></div>
        <div style=\"padding:16px 28px;background:#f8fafc;color:#64748b;font-size:12px\">PetCare+ Clinic Hub · Pangasinan, Philippines</div>
      </div></body></html>";
}
