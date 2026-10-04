<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole(['groomer']);
header('Location: ' . APP_URL . '/dashboard/groomer/index.php');
exit;