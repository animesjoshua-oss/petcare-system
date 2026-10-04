<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole(['groomer']);
header('Location: index.php');
exit;
