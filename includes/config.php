<?php
/**
 * PetCare+ Configuration
 * 
 * Configuration file for database, APIs, and environment settings
 * 
 * Development Notes:
 * - Switch between production and local by commenting/uncommenting sections
 * - Update DB credentials when deploying to production
 * - API keys are loaded from environment variables or hardcoded below
 * - For Gmail SMTP: Enable 2-Step Verification and create App Password
 * 
 * Last Updated: December 2024
 */

// ============================================================
// ENVIRONMENT SETTINGS
// ============================================================
define('APP_NAME', 'PetCare+ Clinic Hub');
define('APP_VERSION', '1.0.0');

// ============================================================
// DATABASE CONFIGURATION - Production
// ============================================================
define('APP_ENV', 'production');
define('APP_URL', 'https://petcareplus.yzz.me');
define('DB_HOST', 'localhost');
define('DB_NAME', 'yzzme_43078279');
define('DB_USER', 'yzzme_43078279');
define('DB_PASS', '4DfvsKy9U01X');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// DATABASE CONFIGURATION - Local Development (XAMPP)
// ============================================================
/*
define('APP_ENV', 'development');
define('APP_URL', 'http://localhost/WEB%20petcare');
define('DB_HOST', 'localhost');
define('DB_NAME', 'petcare_clinic_hub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
*/

// ============================================================
// API KEYS & EXTERNAL SERVICES
// ============================================================
// Optional local-only secrets file for XAMPP. Copy local-secrets.example.php
// to local-secrets.php and keep the copy out of version control.
$localSecretsFile = __DIR__ . '/local-secrets.php';
if (is_file($localSecretsFile)) {
    require_once $localSecretsFile;
}

// Groq AI (Primary)
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: (defined('LOCAL_GROQ_API_KEY') ? LOCAL_GROQ_API_KEY : ''));
define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'openai/gpt-oss-120b');
define('GROQ_VISION_MODEL', getenv('GROQ_VISION_MODEL') ?: 'qwen/qwen3.8-27b');

// Google Maps
define('GOOGLE_MAPS_KEY', getenv('GOOGLE_MAPS_KEY') ?: '');

// ============================================================
// EMAIL CONFIGURATION (PHPMailer / Gmail SMTP)
// ============================================================
// How to get Gmail App Password:
// 1. Go to https://myaccount.google.com/security
// 2. Enable 2-Step Verification
// 3. Go to https://myaccount.google.com/apppasswords
// 4. Create App Password → select "Mail" → copy the 16-character password
// 5. Set MAIL_PASS in your local-only secrets file or process environment.
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', trim(getenv('MAIL_USER') ?: (defined('LOCAL_MAIL_USER') ? LOCAL_MAIL_USER : '')));
// Google displays App Passwords with spaces; remove them for SMTP authentication.
define('MAIL_PASS', preg_replace('/\s+/', '', getenv('MAIL_PASS') ?: (defined('LOCAL_MAIL_PASS') ? LOCAL_MAIL_PASS : '')));
define('MAIL_FROM', getenv('MAIL_FROM') ?: MAIL_USER);
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_ENABLED', (getenv('MAIL_ENABLED') ?: '1') === '1' && MAIL_USER !== '' && MAIL_PASS !== '');

// ============================================================
// FILE UPLOAD SETTINGS
// ============================================================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// ============================================================
// SESSION CONFIGURATION
// ============================================================
define('SESSION_LIFETIME', 7200); // 2 hours

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Asia/Manila');

// ============================================================
// ERROR REPORTING
// ============================================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================================
// SECURE SESSION INITIALIZATION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name('PETCARE_SESSION');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    // Keep session alive
    if (empty($_SESSION['_init'])) {
        $_SESSION['_init'] = time();
    }
}
