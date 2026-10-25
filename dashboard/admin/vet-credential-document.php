<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
if (isGroomerAdmin($user)) { http_response_code(403); exit('Unauthorized.'); }
$vetId = (int)($_GET['id'] ?? 0);
$stmt = getDB()->prepare('SELECT v.credential_path,v.credential_original_name,v.credential_mime,c.admin_id FROM veterinarians v JOIN clinics c ON c.id=v.clinic_id WHERE v.id=?');
$stmt->execute([$vetId]);
$document = $stmt->fetch();
$isSuperAdmin = ($user['role_name'] ?? '') === 'superadmin';
if (!$document || (!$isSuperAdmin && (int)$document['admin_id'] !== (int)$user['id']) || empty($document['credential_path'])) {
    http_response_code(404); exit('Credential document not found.');
}
if (!in_array($document['credential_mime'], ['application/pdf','image/jpeg','image/png','image/webp'], true)) {
    http_response_code(404); exit('Credential document not found.');
}

$relative = str_replace('\\', '/', (string)$document['credential_path']);
if (!preg_match('#^uploads/veterinarian-credentials/[a-f0-9]{36}\.(pdf|jpg|png|webp)$#', $relative)) {
    http_response_code(404); exit('Credential document not found.');
}
$filePath = realpath(__DIR__ . '/../../' . $relative);
$expectedDirectory = realpath(__DIR__ . '/../../uploads/veterinarian-credentials');
if (!$filePath || !$expectedDirectory || !str_starts_with($filePath, $expectedDirectory . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
    http_response_code(404); exit('Credential document not found.');
}

$downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename((string)$document['credential_original_name'])) ?: 'veterinarian-credential';
header('Content-Type: ' . $document['credential_mime']);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($filePath);
exit;
