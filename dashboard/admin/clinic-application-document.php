<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['superadmin']);
$id = (int)($_GET['id'] ?? 0);
$stmt = getDB()->prepare('SELECT credential_path,credential_original_name,credential_mime FROM clinic_applications WHERE id=?');
$stmt->execute([$id]);
$document = $stmt->fetch();
if (!$document) { http_response_code(404); exit('Credential document not found.'); }
if (!in_array($document['credential_mime'], ['application/pdf','image/jpeg','image/png','image/webp'], true)) {
    http_response_code(404); exit('Credential document not found.');
}

$relative = str_replace('\\', '/', (string)$document['credential_path']);
if (!preg_match('#^uploads/clinic-applications/[a-f0-9]{36}\.(pdf|jpg|png|webp)$#', $relative)) {
    http_response_code(404); exit('Credential document not found.');
}
$filePath = realpath(__DIR__ . '/../../' . $relative);
$expectedDirectory = realpath(__DIR__ . '/../../uploads/clinic-applications');
if (!$filePath || !$expectedDirectory || !str_starts_with($filePath, $expectedDirectory . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
    http_response_code(404); exit('Credential document not found.');
}

$downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename((string)$document['credential_original_name'])) ?: 'clinic-credential';
header('Content-Type: ' . $document['credential_mime']);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($filePath);
exit;
