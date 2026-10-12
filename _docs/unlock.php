<?php
require_once __DIR__ . '/includes/db.php';

$db = getDB();
$affected = $db->exec('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE locked_until IS NOT NULL');

echo "<p style='font-family:sans-serif;font-size:1.2rem;color:green;'>";
echo "Done. {$affected} account(s) unlocked.";
echo "</p>";
echo "<p style='font-family:sans-serif;'><a href='/auth/login.php'>Go to Login</a></p>";

// Self-delete after running
@unlink(__FILE__);
