<?php
/**
 * ============================================================
 * PetCare+ — Inactivity Checker Cron Job
 * ============================================================
 * Purpose:
 *   Option A — Auto-deactivate accounts inactive for 90+ days
 *   Option B — Send "We miss you!" reminder at 30 days inactivity
 *
 * Schedule: Run DAILY via cron (server) or Windows Task Scheduler
 *
 * Linux/Mac cron (daily at 2:00 AM):
 *   0 2 * * * php /path/to/WEB petcare/cron/inactivity_checker.php >> /var/log/petcare_inactivity.log 2>&1
 *
 * XAMPP local testing (run manually):
 *   php "c:\xampp\htdocs\WEB petcare\cron\inactivity_checker.php"
 *
 * Security: This file should NOT be accessible via browser.
 *   Add to .htaccess: deny from all  (inside /cron/ folder)
 * ============================================================
 */

// ── CLI-only guard ────────────────────────────────────────
// Allow CLI always; allow browser ONLY with secret token (for shared hosting)
$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    $secret = getenv('CRON_SECRET') ?: 'petcare_cron_2026_secret_change_me';
    if (($_GET['token'] ?? '') !== $secret) {
        http_response_code(403);
        die('403 Forbidden');
    }
}

define('CRON_RUN', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// ── Config ────────────────────────────────────────────────
const WARN_DAYS       = 30;   // Days inactive before warning email
const DEACTIVATE_DAYS = 90;   // Days inactive before auto-deactivation
const ROLES_TO_CHECK  = ['pet_owner']; // Only check pet owners, not vets/admins

// ── Logging helper ────────────────────────────────────────
function cronLog(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    // Also write to log file
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    file_put_contents($logDir . 'inactivity.log', $line, FILE_APPEND | LOCK_EX);
}

cronLog('===== Inactivity Checker Started =====');

$db = getDB();

// ── Get role IDs to check ────────────────────────────────
$rolePlaceholders = implode(',', array_fill(0, count(ROLES_TO_CHECK), '?'));
$roleStmt = $db->prepare("SELECT id FROM roles WHERE name IN ($rolePlaceholders)");
$roleStmt->execute(ROLES_TO_CHECK);
$roleIds = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($roleIds)) {
    cronLog('ERROR: No matching roles found. Exiting.');
    exit(1);
}

$roleIdPlaceholders = implode(',', array_fill(0, count($roleIds), '?'));

// ── Reference time ────────────────────────────────────────
$now             = new DateTime();
$warnCutoff      = (clone $now)->modify('-' . WARN_DAYS . ' days')->format('Y-m-d H:i:s');
$deactivateCutoff = (clone $now)->modify('-' . DEACTIVATE_DAYS . ' days')->format('Y-m-d H:i:s');

cronLog("Warn cutoff     : {$warnCutoff} (inactive >= " . WARN_DAYS . " days)");
cronLog("Deactivate cutoff: {$deactivateCutoff} (inactive >= " . DEACTIVATE_DAYS . " days)");

$warned      = 0;
$deactivated = 0;
$skipped     = 0;

// ── Step 1: AUTO-DEACTIVATE (90+ days) ───────────────────
// Fetch active users whose last_login (or created_at if never logged in)
// is older than DEACTIVATE_DAYS and haven't been warned/deactivated yet
$deactivateParams = array_merge($roleIds, [$deactivateCutoff, $deactivateCutoff]);
$deactivateStmt = $db->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email,
           COALESCE(u.last_login, u.created_at) AS last_active
    FROM   users u
    WHERE  u.role_id IN ($roleIdPlaceholders)
      AND  u.is_active = 1
      AND  COALESCE(u.last_login, u.created_at) < ?
      AND  (u.inactivity_deactivated_at IS NULL)
      AND  COALESCE(u.last_login, u.created_at) < ?
    ORDER  BY last_active ASC
");
$deactivateStmt->execute($deactivateParams);
$toDeactivate = $deactivateStmt->fetchAll();

cronLog('Users to deactivate: ' . count($toDeactivate));

foreach ($toDeactivate as $u) {
    try {
        // Deactivate the account
        $db->prepare('
            UPDATE users
            SET    is_active = 0,
                   inactivity_deactivated_at = NOW()
            WHERE  id = ?
        ')->execute([$u['id']]);

        // Send deactivation email
        $emailResult = sendMail(
            $u['email'],
            $u['first_name'] . ' ' . $u['last_name'],
            'Your PetCare+ Account Has Been Deactivated',
            inactivityDeactivatedEmailHtml($u['first_name'])
        );

        // In-app notification (won't be seen until reactivation, but logged)
        createNotification(
            $u['id'],
            'Account Deactivated Due to Inactivity',
            'Your account has been deactivated because it has been inactive for over ' . DEACTIVATE_DAYS . ' days. Contact support to reactivate.',
            'warning',
            APP_URL . '/auth/login.php'
        );

        $lastActive = $u['last_active'];
        $mailStatus = ($emailResult === true) ? 'email sent' : 'email FAILED: ' . $emailResult;
        cronLog("  DEACTIVATED uid={$u['id']} {$u['first_name']} {$u['last_name']} <{$u['email']}> last_active={$lastActive} | {$mailStatus}");
        $deactivated++;

    } catch (\Exception $e) {
        cronLog("  ERROR deactivating uid={$u['id']}: " . $e->getMessage());
        $skipped++;
    }
}

// ── Step 2: SEND WARNING EMAIL (30+ days, not yet 90 days) ───
// Find users inactive 30–89 days who haven't received a warning yet
// (or whose warning was sent >30 days ago — re-warn if still inactive)
$warnParams = array_merge($roleIds, [$warnCutoff, $deactivateCutoff]);
$warnStmt = $db->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email,
           COALESCE(u.last_login, u.created_at) AS last_active,
           u.inactivity_warned_at
    FROM   users u
    WHERE  u.role_id IN ($roleIdPlaceholders)
      AND  u.is_active = 1
      AND  COALESCE(u.last_login, u.created_at) < ?
      AND  COALESCE(u.last_login, u.created_at) >= ?
      AND  (
               u.inactivity_warned_at IS NULL
           )
    ORDER  BY last_active ASC
");
$warnStmt->execute($warnParams);
$toWarn = $warnStmt->fetchAll();

cronLog('Users to warn: ' . count($toWarn));

foreach ($toWarn as $u) {
    try {
        // Calculate days inactive
        $lastActive  = new DateTime($u['last_active']);
        $daysInactive = (int)$now->diff($lastActive)->days;
        $daysLeft     = DEACTIVATE_DAYS - $daysInactive;

        // Send warning email
        $emailResult = sendMail(
            $u['email'],
            $u['first_name'] . ' ' . $u['last_name'],
            'We miss you! Your PetCare+ account will be deactivated soon',
            inactivityWarningEmailHtml($u['first_name'], $daysInactive, $daysLeft)
        );

        // Mark warning sent
        $db->prepare('
            UPDATE users
            SET    inactivity_warned_at = NOW()
            WHERE  id = ?
        ')->execute([$u['id']]);

        // In-app notification
        createNotification(
            $u['id'],
            'Account Inactivity Notice',
            "Your account has been inactive for {$daysInactive} days. Log in within {$daysLeft} days to keep your account active.",
            'warning',
            APP_URL . '/auth/login.php'
        );

        $mailStatus = ($emailResult === true) ? 'warning email sent' : 'email FAILED: ' . $emailResult;
        cronLog("  WARNED uid={$u['id']} {$u['first_name']} {$u['last_name']} <{$u['email']}> inactive={$daysInactive}d left={$daysLeft}d | {$mailStatus}");
        $warned++;

    } catch (\Exception $e) {
        cronLog("  ERROR warning uid={$u['id']}: " . $e->getMessage());
        $skipped++;
    }
}

// ── Reset inactivity flag when user logs back in ──────────
// (handled in login.php by clearing inactivity_warned_at on login)

// ── Summary ──────────────────────────────────────────────
cronLog('===== Inactivity Checker Complete =====');
cronLog("  Warned      : {$warned}");
cronLog("  Deactivated : {$deactivated}");
cronLog("  Skipped     : {$skipped}");
cronLog('========================================');

exit(0);

// ============================================================
// Email Template: 30-day inactivity WARNING
// ============================================================
function inactivityWarningEmailHtml(string $firstName, int $daysInactive, int $daysLeft): string
{
    $loginUrl  = APP_URL . '/auth/login.php';
    $deactivateDays = DEACTIVATE_DAYS;
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
          <td style="background:linear-gradient(135deg,#F59E0B,#EF4444);padding:36px;text-align:center;">
            <div style="font-size:2.5rem;">😿</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">Account Inactivity Notice</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">
              Hi <strong>{$firstName}</strong> 👋
            </p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.25rem;">
              We noticed that you haven't logged in to your <strong>PetCare+</strong> account
              for <strong>{$daysInactive} days</strong>. We miss you and your furry friends! 🐾
            </p>

            <!-- Warning box -->
            <div style="background:#FEF3C7;border:2px solid #F59E0B;border-radius:12px;padding:18px 20px;margin-bottom:1.5rem;text-align:center;">
              <div style="font-size:1.5rem;margin-bottom:.5rem;">⚠️</div>
              <p style="margin:0;font-size:.9rem;color:#92400E;line-height:1.7;">
                Your account will be <strong>automatically deactivated</strong> in
                <strong style="font-size:1.1rem;color:#DC2626;">{$daysLeft} days</strong>
                if you don't log in.<br>
                <small style="opacity:.8;">(Accounts inactive for {$deactivateDays}+ days are deactivated)</small>
              </p>
            </div>

            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              Log in now to keep your account active and your pet's records safe:
            </p>

            <!-- CTA Button -->
            <div style="text-align:center;margin:24px 0;">
              <a href="{$loginUrl}"
                 style="background:linear-gradient(135deg,#1E40FF,#00D4AA);color:#fff;
                        text-decoration:none;padding:14px 40px;border-radius:50px;
                        font-weight:700;font-size:1rem;display:inline-block;">
                🐾 Log In to PetCare+
              </a>
            </div>

            <p style="font-size:.82rem;color:#94A3B8;text-align:center;margin-top:1rem;">
              If you no longer wish to use PetCare+, you can ignore this email.<br>
              Your account will be safely deactivated after {$daysLeft} more days.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#F8FAFC;padding:20px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.78rem;color:#94A3B8;margin:0;">
              &copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines<br>
              You received this because you have an account on PetCare+.
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

// ============================================================
// Email Template: 90-day DEACTIVATION notice
// ============================================================
function inactivityDeactivatedEmailHtml(string $firstName): string
{
    $contactEmail = MAIL_FROM;
    $registerUrl  = APP_URL . '/auth/register.php';
    $deactivateDays = DEACTIVATE_DAYS;
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
          <td style="background:linear-gradient(135deg,#374151,#1F2937);padding:36px;text-align:center;">
            <div style="font-size:2.5rem;">😔</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin:0;">Account Deactivated</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">
              Hi <strong>{$firstName}</strong>,
            </p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.25rem;">
              Your PetCare+ account has been <strong>automatically deactivated</strong>
              due to inactivity of more than <strong>{$deactivateDays} days</strong>.
            </p>

            <!-- Info box -->
            <div style="background:#F1F5F9;border-left:4px solid #94A3B8;border-radius:8px;
                        padding:16px 20px;margin-bottom:1.5rem;font-size:.875rem;
                        color:#475569;line-height:1.8;">
              <strong>What this means:</strong><br>
              • Your account is no longer active<br>
              • Your pet records are safely stored<br>
              • You cannot log in until your account is reactivated
            </div>

            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              If you'd like to <strong>reactivate your account</strong>, please contact our support team:
            </p>

            <!-- Contact -->
            <div style="text-align:center;background:#EFF6FF;border-radius:10px;
                        padding:16px;margin-bottom:1.5rem;">
              <p style="margin:0;font-size:.9rem;color:#1E40FF;">
                📧 <a href="mailto:{$contactEmail}" style="color:#1E40FF;font-weight:700;">
                  {$contactEmail}
                </a>
              </p>
              <p style="margin:.5rem 0 0;font-size:.8rem;color:#64748B;">
                Include your registered email address in the message.
              </p>
            </div>

            <p style="font-size:.82rem;color:#94A3B8;text-align:center;">
              Alternatively, you may
              <a href="{$registerUrl}" style="color:#1E40FF;">create a new account</a>
              anytime.
            </p>
          </td>
        </tr>

        <!-- Footer -->
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
