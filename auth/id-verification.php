<?php
// ID verification is now part of the registration process.
// This file is no longer used — redirect accordingly.
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirectByRole();
}
header('Location: ' . APP_URL . '/auth/register.php'); exit;
