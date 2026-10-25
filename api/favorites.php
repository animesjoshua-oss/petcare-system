<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['pet_owner']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    header('Allow: POST');
    jsonResponse(['error' => 'Method not allowed.'], 405);
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = $_POST;
$csrf = (string)($data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyCsrfToken($csrf)) {
    jsonResponse(['error' => 'Invalid CSRF token.'], 403);
}
$clinicId = (int)($data['clinic_id'] ?? 0);
$userId   = $_SESSION['user_id'];
$db       = getDB();

if (!$clinicId) { jsonResponse(['error'=>'Missing clinic ID.'],400); }

$chk = $db->prepare('SELECT id FROM favorite_clinics WHERE user_id=? AND clinic_id=?');
$chk->execute([$userId,$clinicId]); $exists = $chk->fetch();

if ($exists) {
    $db->prepare('DELETE FROM favorite_clinics WHERE user_id=? AND clinic_id=?')->execute([$userId,$clinicId]);
    jsonResponse(['success'=>true,'action'=>'removed']);
} else {
    $clinic = $db->prepare('SELECT id FROM clinics WHERE id=? AND is_active=1 AND is_verified=1');
    $clinic->execute([$clinicId]);
    if (!$clinic->fetchColumn()) jsonResponse(['error' => 'Choose an approved clinic.'], 404);
    $db->prepare('INSERT INTO favorite_clinics (user_id,clinic_id) VALUES (?,?)')->execute([$userId,$clinicId]);
    jsonResponse(['success'=>true,'action'=>'added']);
}
