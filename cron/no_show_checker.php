<?php
/**
 * ============================================================
 * PetCare+ — No-Show Checker Cron Job
 * ============================================================
 * Purpose:
 *   Scans appointments that were APPROVED but whose date/time
 *   has passed without being marked 'completed' or 'cancelled'.
 *   Marks them as 'no_show', increments the user's no_show_count,
 *   and sends a warning notification + email.
 *
 *   Warning tiers (Option B):
 *     1st no-show → gentle warning notification + email
 *     2nd no-show → stronger warning, mention booking restriction
 *     3rd no-show → booking restricted + email + admin notification
 *
 * Schedule: Run HOURLY (or every 30 min) via cron
 *
 * Linux/Mac cron (every hour):
 *   0 * * * * php /path/to/WEB petcare/cron/no_show_checker.php >> /var/log/petcare_noshow.log 2>&1
 *
 * XAMPP local testing (run manually):
 *   php "c:\xampp\htdocs\WEB petcare\cron\no_show_checker.php"
 *
 * Security: Block browser access via /cron/.htaccess
 * ============================================================
 */

// ── CLI-only guard ────────────────────────────────────────
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
require_once __DIR__ . '/../includes/functions.php';

// ── Config ────────────────────────────────────────────────
// How many minutes AFTER the appointment time before marking no-show
// e.g. 120 = 2 hours grace period
const NO_SHOW_GRACE_MINUTES    = 120;

// No-show count thresholds
const WARN_THRESHOLD_1         = 1;  // 1st no-show: gentle warning
const WARN_THRESHOLD_2         = 2;  // 2nd no-show: stern warning
const RESTRICT_THRESHOLD       = 3;  // 3rd+ no-show: restrict bookings

// ── Logging helper ────────────────────────────────────────
function cronLog(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    file_put_contents($logDir . 'no_show.log', $line, FILE_APPEND | LOCK_EX);
}

cronLog('===== No-Show Checker Started =====');

$db  = getDB();
$now = new DateTime('now', new DateTimeZone('Asia/Manila'));

// ── Find appointments to mark as no_show ─────────────────
// Conditions:
//   - status = 'approved' (confirmed but not yet completed/cancelled)
//   - appointment datetime + grace period < NOW
//   - Not already marked no_show
$graceCutoff = (clone $now)
    ->modify('-' . NO_SHOW_GRACE_MINUTES . ' minutes')
    ->format('Y-m-d H:i:s');

cronLog("Grace cutoff: {$graceCutoff} (appointments before this time)");

$apptStmt = $db->prepare("
    SELECT
        a.id           AS appt_id,
        a.owner_id,
        a.appointment_date,
        a.appointment_time,
        a.appointment_type,
        a.clinic_id,
        p.name         AS pet_name,
        c.name         AS clinic_name,
        CONCAT(u.first_name,' ',u.last_name) AS owner_name,
        u.first_name   AS owner_first,
        u.email        AS owner_email,
        u.no_show_count
    FROM  appointments a
    JOIN  pets    p ON a.pet_id    = p.id
    JOIN  users   u ON a.owner_id  = u.id
    JOIN  clinics c ON a.clinic_id = c.id
    WHERE a.status = 'approved'
      AND CONCAT(a.appointment_date, ' ', a.appointment_time) < ?
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$apptStmt->execute([$graceCutoff]);
$noShows = $apptStmt->fetchAll();

cronLog('Appointments to mark no_show: ' . count($noShows));

$marked    = 0;
$restricted = 0;
$errors    = 0;

foreach ($noShows as $appt) {
    try {
        // ── Mark appointment as no_show ───────────────────
        $db->prepare("
            UPDATE appointments
            SET    status     = 'no_show',
                   updated_at = NOW()
            WHERE  id = ?
        ")->execute([$appt['appt_id']]);

        // ── Increment user's no_show_count ────────────────
        $db->prepare("
            UPDATE users
            SET    no_show_count = no_show_count + 1
            WHERE  id = ?
        ")->execute([$appt['owner_id']]);

        // Get the new no_show_count
        $newCount = (int)$appt['no_show_count'] + 1;

        $apptDate     = date('F j, Y', strtotime($appt['appointment_date']));
        $apptTime     = date('g:i A',  strtotime($appt['appointment_time']));
        $apptDateFull = "{$apptDate} at {$apptTime}";

        // ── Determine warning tier ────────────────────────
        if ($newCount >= RESTRICT_THRESHOLD) {
            // ── Tier 3: RESTRICT BOOKINGS ─────────────────
            $db->prepare("
                UPDATE users
                SET    is_booking_restricted = 1
                WHERE  id = ?
            ")->execute([$appt['owner_id']]);

            // Notify owner — in-app
            createNotification(
                $appt['owner_id'],
                '⛔ Booking Restricted — 3 No-Shows',
                "You missed your appointment for {$appt['pet_name']} at {$appt['clinic_name']} on {$apptDateFull}. You now have {$newCount} no-shows. Your booking access has been temporarily restricted. Contact support to resolve.",
                'danger',
                APP_URL . '/dashboard/owner/appointments.php'
            );

            // Email owner
            sendMail(
                $appt['owner_email'],
                $appt['owner_name'],
                '⛔ PetCare+: Booking Access Restricted',
                noShowEmailHtml($appt['owner_first'], $appt['pet_name'], $appt['clinic_name'], $apptDateFull, $newCount, 'restricted')
            );

            // Notify all admins
            $admins = $db->query("
                SELECT u.id FROM users u
                JOIN   roles r ON u.role_id = r.id
                WHERE  r.name IN ('admin','superadmin')
                  AND  u.is_active = 1
                LIMIT  5
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($admins as $adminId) {
                createNotification(
                    (int)$adminId,
                    'User Booking Restricted — No-Shows',
                    "{$appt['owner_name']} has {$newCount} no-shows and has been booking-restricted. Review their account.",
                    'danger',
                    APP_URL . '/dashboard/admin/users.php'
                );
            }

            cronLog("  RESTRICTED uid={$appt['owner_id']} {$appt['owner_name']} no_shows={$newCount} appt_id={$appt['appt_id']}");
            $restricted++;

        } elseif ($newCount === WARN_THRESHOLD_2) {
            // ── Tier 2: STERN WARNING ─────────────────────
            createNotification(
                $appt['owner_id'],
                '⚠️ 2nd No-Show — Final Warning',
                "You missed another appointment for {$appt['pet_name']} at {$appt['clinic_name']} on {$apptDateFull}. This is your 2nd no-show. One more missed appointment will restrict your booking access.",
                'danger',
                APP_URL . '/dashboard/owner/appointments.php'
            );

            sendMail(
                $appt['owner_email'],
                $appt['owner_name'],
                '⚠️ PetCare+: 2nd No-Show — Final Warning',
                noShowEmailHtml($appt['owner_first'], $appt['pet_name'], $appt['clinic_name'], $apptDateFull, $newCount, 'final_warning')
            );

            cronLog("  FINAL_WARN uid={$appt['owner_id']} {$appt['owner_name']} no_shows={$newCount} appt_id={$appt['appt_id']}");

        } else {
            // ── Tier 1: GENTLE WARNING ────────────────────
            createNotification(
                $appt['owner_id'],
                '📋 Missed Appointment',
                "You missed your appointment for {$appt['pet_name']} at {$appt['clinic_name']} on {$apptDateFull}. Please cancel in advance next time so the clinic can serve other patients.",
                'warning',
                APP_URL . '/dashboard/owner/appointments.php'
            );

            sendMail(
                $appt['owner_email'],
                $appt['owner_name'],
                'PetCare+: You missed your appointment',
                noShowEmailHtml($appt['owner_first'], $appt['pet_name'], $appt['clinic_name'], $apptDateFull, $newCount, 'warning')
            );

            cronLog("  WARNING uid={$appt['owner_id']} {$appt['owner_name']} no_shows={$newCount} appt_id={$appt['appt_id']}");
        }

        $marked++;

    } catch (\Exception $e) {
        cronLog("  ERROR appt_id={$appt['appt_id']}: " . $e->getMessage());
        $errors++;
    }
}

// ── Summary ──────────────────────────────────────────────
cronLog('===== No-Show Checker Complete =====');
cronLog("  Marked no-show  : {$marked}");
cronLog("  Users restricted: {$restricted}");
cronLog("  Errors          : {$errors}");
cronLog('=====================================');

exit(0);

// ============================================================
// Email Template: No-show notification (3 tiers)
// ============================================================
function noShowEmailHtml(
    string $firstName,
    string $petName,
    string $clinicName,
    string $apptDateFull,
    int    $noShowCount,
    string $tier   // 'warning' | 'final_warning' | 'restricted'
): string {

    $dashUrl    = APP_URL . '/dashboard/owner/appointments.php';
    $contactUrl = APP_URL . '/index.php#contact';

    if ($tier === 'restricted') {
        $headerGrad  = 'linear-gradient(135deg,#DC2626,#7F1D1D)';
        $emoji       = '⛔';
        $headline    = 'Booking Access Restricted';
        $subheadline = 'You have reached the no-show limit';
        $color       = '#DC2626';
        $bodyText    = "Your booking access on <strong>PetCare+</strong> has been <strong>temporarily restricted</strong> because you have missed <strong>{$noShowCount} appointments</strong> without cancelling in advance.";
        $actionText  = "To resolve this, please contact our support team. We want to continue serving you and your pet!";
        $btnText     = 'Contact Support';
        $btnUrl      = $contactUrl;
        $extraNote   = "Your existing appointments are not affected. Only new bookings are restricted.";
    } elseif ($tier === 'final_warning') {
        $headerGrad  = 'linear-gradient(135deg,#F59E0B,#DC2626)';
        $emoji       = '⚠️';
        $headline    = 'Final Warning — 2nd No-Show';
        $subheadline = 'One more missed appointment will restrict bookings';
        $color       = '#D97706';
        $bodyText    = "You have now missed <strong>{$noShowCount} appointments</strong> without prior cancellation. <strong>One more no-show</strong> will restrict your ability to make new bookings on PetCare+.";
        $actionText  = "Please remember to cancel appointments at least 24 hours in advance if you can't make it. This helps the clinic serve other patients.";
        $btnText     = 'View My Appointments';
        $btnUrl      = $dashUrl;
        $extraNote   = "Tip: You can cancel appointments any time from your dashboard — it only takes a few seconds!";
    } else {
        $headerGrad  = 'linear-gradient(135deg,#1E40FF,#00D4AA)';
        $emoji       = '📋';
        $headline    = 'You Missed Your Appointment';
        $subheadline = 'No-show recorded on your account';
        $color       = '#1E40FF';
        $bodyText    = "It looks like you missed your scheduled appointment. This has been recorded as a <strong>no-show</strong> on your account (<strong>{$noShowCount} total</strong>).";
        $actionText  = "No worries — things happen! Just remember to cancel your next appointment in advance if you can't make it so the clinic can help other pet owners.";
        $btnText     = 'Book Again';
        $btnUrl      = $dashUrl;
        $extraNote   = "Repeated no-shows may result in booking restrictions. Cancel early to avoid this.";
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

        <!-- Header -->
        <tr>
          <td style="background:{$headerGrad};padding:36px;text-align:center;">
            <div style="font-size:2.5rem;">{$emoji}</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">{$subheadline}</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:36px;">
            <h2 style="color:{$color};font-size:1.2rem;margin-bottom:.75rem;">{$headline}</h2>
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">
              Hi <strong>{$firstName}</strong> 👋
            </p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.25rem;">
              {$bodyText}
            </p>

            <!-- Appointment details box -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;
                        padding:16px 20px;margin-bottom:1.25rem;font-size:.875rem;color:#475569;line-height:2;">
              <div>🐾 <strong>Pet:</strong> {$petName}</div>
              <div>🏥 <strong>Clinic:</strong> {$clinicName}</div>
              <div>📅 <strong>Missed appointment:</strong> {$apptDateFull}</div>
              <div>🔢 <strong>Total no-shows:</strong> {$noShowCount}</div>
            </div>

            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              {$actionText}
            </p>

            <!-- CTA Button -->
            <div style="text-align:center;margin:24px 0;">
              <a href="{$btnUrl}"
                 style="background:{$color};color:#fff;text-decoration:none;
                        padding:13px 36px;border-radius:50px;font-weight:700;
                        font-size:.95rem;display:inline-block;">
                {$btnText}
              </a>
            </div>

            <!-- Tip box -->
            <div style="background:#EFF6FF;border-left:4px solid #1E40FF;border-radius:6px;
                        padding:12px 16px;font-size:.82rem;color:#1E40FF;line-height:1.6;">
              💡 {$extraNote}
            </div>
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
