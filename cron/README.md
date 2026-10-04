# PetCare+ Cron Jobs

## Scripts

### 1. `inactivity_checker.php`
Handles inactive users — runs **daily**.

| Trigger | Action |
|---|---|
| User inactive **30 days** | Sends "We miss you!" warning email + in-app notification |
| User inactive **90 days** | Deactivates account + sends deactivation email |
| User logs back in | `inactivity_warned_at` and `inactivity_deactivated_at` cleared automatically |

### 2. `no_show_checker.php`
Handles missed appointments — runs **every hour**.

| No-show count | Action |
|---|---|
| 1st | Gentle warning email + notification |
| 2nd | Final warning email + notification |
| 3rd+ | Booking restricted + admin notified |

Grace period: **2 hours** after appointment time before marking as no-show.

---

## How to Run

### Local (XAMPP) — Manual Testing
```bash
php "c:\xampp\htdocs\WEB petcare\cron\inactivity_checker.php"
php "c:\xampp\htdocs\WEB petcare\cron\no_show_checker.php"
```

### Linux/Mac Production — crontab
```bash
crontab -e
```
Add these lines:
```
# Inactivity checker — daily at 2:00 AM
0 2 * * * php /var/www/html/petcare/cron/inactivity_checker.php >> /var/log/petcare_inactivity.log 2>&1

# No-show checker — every hour
0 * * * * php /var/www/html/petcare/cron/no_show_checker.php >> /var/log/petcare_noshow.log 2>&1
```

### Shared Hosting (cPanel) — Cron Jobs
1. Go to cPanel → **Cron Jobs**
2. Add the commands above with the correct server path
3. Or use the browser URL with secret token (see below)

### Browser Trigger (Shared Hosting without SSH)
Set the `CRON_SECRET` environment variable, then call:
```
https://yourdomain.com/cron/inactivity_checker.php?token=YOUR_SECRET
https://yourdomain.com/cron/no_show_checker.php?token=YOUR_SECRET
```
**Important:** Change the default secret in both scripts!
```php
$secret = getenv('CRON_SECRET') ?: 'petcare_cron_2026_secret_change_me';
```

---

## Logs
Output is written to:
- `WEB petcare/logs/inactivity.log`
- `WEB petcare/logs/no_show.log`

---

## Database Changes Required
Run the migration in `RUN_THIS_ON_PRODUCTION.sql` (the "INACTIVE USER + NO-SHOW SYSTEM MIGRATION" section).

New columns added:
- `users.inactivity_warned_at` — when warning email was sent
- `users.inactivity_deactivated_at` — when auto-deactivated
- `users.no_show_count` — total no-shows
- `users.is_booking_restricted` — 1 if booking is blocked
- `appointments.status` — now includes `no_show` value
