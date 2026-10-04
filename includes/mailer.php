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
function otpEmailHtml(string $firstName, string $otp): string
{
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
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">Email Verification</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.7;margin-bottom:1.5rem;">
              Thank you for registering at <strong>PetCare+ Clinic Hub</strong>.<br>
              Use the OTP below to verify your email address. It expires in <strong>10 minutes</strong>.
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
              If you did not register, ignore this email.
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
