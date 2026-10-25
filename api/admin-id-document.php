<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['admin', 'superadmin']);
$user = currentUser();
if (isGroomerAdmin($user)) { http_response_code(403); exit; }

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$side = $_GET['side'] ?? '';
if (!$userId || !in_array($side, ['front', 'back'], true)) {
    http_response_code(400);
    exit;
}

$column = $side === 'front' ? 'id_document_path' : 'id_document_path_back';
$stmt = getDB()->prepare("SELECT {$column} FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$storedPath = $stmt->fetchColumn();
$filename = is_string($storedPath) ? basename(parse_url($storedPath, PHP_URL_PATH) ?: '') : '';

if ($filename === '' || !preg_match('/^id_' . preg_quote($side, '/') . '_[a-f0-9]+\.[a-z0-9]+$/i', $filename)) {
    http_response_code(404);
    exit;
}

$documentPath = realpath(__DIR__ . '/../uploads/id_docs/' . $filename);
$documentRoot = realpath(__DIR__ . '/../uploads/id_docs');
if (!$documentPath || !$documentRoot || !str_starts_with($documentPath, $documentRoot . DIRECTORY_SEPARATOR) || !is_file($documentPath)) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($documentPath);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($documentPath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($documentPath);
