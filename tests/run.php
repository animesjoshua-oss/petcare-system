<?php
declare(strict_types=1);

session_save_path(__DIR__);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$testSessionId = session_id();
register_shutdown_function(static function () use ($testSessionId): void {
    session_write_close();
    $sessionFile = __DIR__ . '/sess_' . $testSessionId;
    if (is_file($sessionFile)) unlink($sessionFile);
});

$checks = 0;

function checkSame(mixed $expected, mixed $actual, string $message): void
{
    global $checks;
    $checks++;
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function checkTrue(bool $actual, string $message): void
{
    checkSame(true, $actual, $message);
}

function checkFalse(bool $actual, string $message): void
{
    checkSame(false, $actual, $message);
}

checkSame('alert(&quot;x&quot;)', sanitize(' <script>alert("x")</script> '), 'sanitize removes markup and escapes text');

$token = generateCsrfToken();
checkTrue(verifyCsrfToken($token), 'generated CSRF token validates');
checkFalse(verifyCsrfToken('invalid-token'), 'incorrect CSRF token is rejected');

$password = hashPassword('Petcare-123');
checkTrue(verifyPassword('Petcare-123', $password), 'hashed password validates');
checkFalse(verifyPassword('wrong-password', $password), 'wrong password is rejected');

$page = getPaginationData(101, 999, 10);
checkSame(11, $page['totalPages'], 'pagination calculates page count');
checkSame(11, $page['page'], 'pagination clamps page to last available page');
checkSame(100, $page['offset'], 'pagination calculates offset after clamping');
checkSame(0, getPaginationData(0, 1, 10)['offset'], 'empty results have a safe offset');

checkTrue(str_contains(statusBadge('approved'), 'badge-success'), 'appointment status maps to success badge');
checkTrue(str_contains(statusBadge('unknown'), 'badge-secondary'), 'unknown statuses use a neutral badge');

$otp = generateOtp();
checkSame(6, strlen($otp), 'OTP has six digits');
checkTrue(verifyOtp($otp), 'fresh OTP validates');
checkFalse(verifyOtp($otp === '000000' ? '000001' : '000000'), 'incorrect OTP is rejected');
$_SESSION['otp_expires'] = time() - 1;
checkFalse(verifyOtp($otp), 'expired OTP is rejected');

fwrite(STDOUT, "Passed {$checks} checks.\n");
