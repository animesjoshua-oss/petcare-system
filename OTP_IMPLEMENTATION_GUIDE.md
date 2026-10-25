# 🔐 OTP Email Verification Implementation Guide

## Overview

OTP (One-Time Password) email verification has been implemented for **Pet Owners only** during login. This adds an extra security layer to protect pet owner accounts.

---

## 🎯 Who Needs OTP?

| Role | Requires OTP? | Notes |
|------|---------------|-------|
| **Pet Owner** (role_id=1) | ✅ YES | Must verify OTP on every login |
| **Veterinarian** (role_id=2) | ❌ NO | Direct login |
| **Groomer** (role_id=3) | ❌ NO | Direct login |
| **Clinic Admin** (role_id=4) | ❌ NO | Direct login |
| **Super Admin** (role_id=5) | ❌ NO | Direct login |

---

## 📋 Installation Steps

### For Existing Databases

If you already have PetCare+ installed, run the migration:

1. Open **phpMyAdmin**
2. Select `petcare_plus` database
3. Go to **SQL** tab
4. Run this migration:

```sql
-- Add OTP columns to users table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `otp_code` varchar(255) DEFAULT NULL AFTER `last_login`,
  ADD COLUMN IF NOT EXISTS `otp_expires_at` datetime DEFAULT NULL AFTER `otp_code`,
  ADD COLUMN IF NOT EXISTS `otp_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `otp_expires_at`;

-- Add index for performance
CREATE INDEX IF NOT EXISTS `idx_otp_expires` ON `users` (`otp_expires_at`);
```

Or use the migration file:
```bash
# From MySQL console
mysql -u root -p petcare_plus < database/migrations/add_otp_columns.sql
```

### For New Installations

No action needed! OTP columns are already in `database/FULL_SETUP.sql`.

---

## 🔄 How It Works

### Login Flow for Pet Owners

```
1. User enters email + password on login.php
   ↓
2. System validates credentials
   ↓
3. If user is Pet Owner (role_id=1):
   ├─ Generate 6-digit OTP
   ├─ Hash and store in database (otp_code)
   ├─ Set expiration (10 minutes)
   ├─ Send OTP via email
   └─ Redirect to verify-otp.php
   ↓
4. User enters OTP on verify-otp.php
   ↓
5. System validates:
   ├─ Check if expired (10 min)
   ├─ Check attempts (max 5)
   ├─ Verify OTP code
   └─ If valid → Create session & login
       If invalid → Show error, increment attempts
   ↓
6. User redirected to dashboard/owner/
```

### Login Flow for Other Roles

```
1. User enters email + password
   ↓
2. System validates credentials
   ↓
3. Create session immediately
   ↓
4. Redirect to appropriate dashboard
```

---

## ✨ Features

### ✅ Security Features
- **10-minute expiration** - OTP expires after 10 minutes
- **5 failed attempts limit** - Max 5 wrong OTP entries before requiring new code
- **Bcrypt hashing** - OTP stored as hash in database
- **Rate limiting** - Prevents brute force attacks
- **Role-based** - Only pet owners require OTP

### ✅ User Experience
- **Real-time countdown** - Shows remaining time
- **Resend OTP** - Request new code anytime
- **Clear error messages** - Shows remaining attempts
- **Auto-focus input** - OTP field focused on page load
- **Numeric-only input** - Prevents non-digit characters
- **Beautiful email template** - Styled OTP email with branding

---

## 📧 Email Configuration

OTP emails are sent using the existing mailer configuration in `includes/config.php`:

```php
// Make sure these are configured
define('MAIL_ENABLED', true);
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_PORT', 587);
```

### Gmail Setup
1. Enable 2-factor authentication on your Google account
2. Generate an App Password:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and generate password
   - Use that password in `MAIL_PASS`

---

## 🧪 Testing

### Test Pet Owner Login

1. **Create or use existing pet owner account:**
   - Email: `owner@test.com`
   - Password: `password123`
   - Role: Pet Owner (role_id=1)

2. **Login Test:**
   ```
   1. Go to /auth/login.php
   2. Enter email and password
   3. Click "Continue"
   4. Should redirect to /auth/verify-otp.php
   5. Check email for 6-digit OTP
   6. Enter OTP
   7. Should login to /dashboard/owner/
   ```

3. **Test Scenarios:**

   ✅ **Valid OTP:**
   - Enter correct 6-digit code → Should login successfully

   ❌ **Invalid OTP:**
   - Enter wrong code → Should show "Invalid OTP. X attempts remaining"

   ⏱️ **Expired OTP:**
   - Wait 10+ minutes → Should show "OTP has expired. Please request a new one."

   🔁 **Resend OTP:**
   - Click "Resend OTP" → Should receive new code via email

   🚫 **Max Attempts:**
   - Enter wrong OTP 5 times → Should force resend

### Test Other Roles (No OTP Required)

```
Veterinarian: vet@petcareplus.com / password123
Groomer: groomer@petcareplus.com / password123
Admin: clinicadmin@petcareplus.com / password123
```

These should login **directly** without OTP verification.

---

## 📁 Modified Files

### Core Implementation
- ✅ `database/FULL_SETUP.sql` - Added OTP columns
- ✅ `auth/login.php` - OTP generation for pet owners
- ✅ `auth/verify-otp.php` - OTP verification page (completely rewritten)
- ✅ `includes/mailer.php` - Enhanced OTP email template

### New Files
- ✅ `database/migrations/add_otp_columns.sql` - Migration for existing DBs
- ✅ `database/migrations/README.md` - Migration instructions
- ✅ `OTP_IMPLEMENTATION_GUIDE.md` - This guide

---

## 🗄️ Database Schema

### New Columns in `users` Table

| Column | Type | Description |
|--------|------|-------------|
| `otp_code` | varchar(255) | Bcrypt hash of 6-digit OTP |
| `otp_expires_at` | datetime | OTP expiration timestamp |
| `otp_attempts` | tinyint(3) | Failed verification attempts (0-5) |

### Example Query
```sql
-- Check OTP status for a user
SELECT 
  id, 
  first_name, 
  email, 
  otp_expires_at,
  otp_attempts,
  CASE 
    WHEN otp_expires_at IS NULL THEN 'No OTP'
    WHEN otp_expires_at < NOW() THEN 'Expired'
    ELSE 'Active'
  END as otp_status
FROM users 
WHERE email = 'owner@test.com';
```

---

## 🔧 Troubleshooting

### Issue: Email not sending

**Solution:**
1. Check `includes/config.php` - Verify `MAIL_ENABLED = true`
2. Check Gmail App Password is correct
3. Check error logs: `error_log()` in mailer.php

### Issue: OTP expired immediately

**Solution:**
1. Check server timezone settings
2. Verify database timezone matches PHP timezone
3. Check `otp_expires_at` value in database

### Issue: "Invalid OTP" even with correct code

**Solution:**
1. Check if OTP was hashed correctly (password_hash)
2. Verify no extra spaces in input
3. Check if user refreshed page (session lost)

### Issue: Stuck on verify-otp.php

**Solution:**
1. Check session variables: `$_SESSION['otp_user_id']`, `$_SESSION['otp_email']`
2. Clear browser cache and cookies
3. Check if user record exists in database

---

## 🚀 Future Enhancements

Potential improvements:

- [ ] SMS OTP option (via Semaphore/Twilio)
- [ ] "Remember this device" checkbox (skip OTP for 30 days)
- [ ] Admin panel to view OTP statistics
- [ ] WhatsApp OTP notifications
- [ ] Biometric authentication option
- [ ] OTP for password reset flow

---

## 📞 Support

For questions or issues:
1. Check this guide first
2. Review migration README: `database/migrations/README.md`
3. Check PHP error logs
4. Verify email configuration

---

## ✅ Quick Checklist

Before going live:

- [ ] Database migration completed
- [ ] Email configuration tested
- [ ] Pet owner can receive OTP emails
- [ ] Pet owner can verify OTP successfully
- [ ] Other roles login without OTP
- [ ] Expired OTP shows resend option
- [ ] Max attempts limit works
- [ ] Countdown timer displays correctly
- [ ] Mobile responsive (test on phone)

---

**Implementation Date:** October 12, 2026  
**Version:** 1.0  
**Status:** ✅ Complete - Ready for Testing
