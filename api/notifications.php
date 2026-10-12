<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = sanitize($_GET['action'] ?? 'list');

if ($action === 'mark_read') {
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
    $nid = (int)($data['id'] ?? 0);
    if ($nid) { $db->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$nid,$userId]); }
    else { $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$userId]); }
    jsonResponse(['success'=>true]);
}

if ($method !== 'GET') {
    header('Allow: GET');
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$notifs = $db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30');
$notifs->execute([$userId]); $notifs=$notifs->fetchAll();
$unread = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$unread->execute([$userId]);
jsonResponse(['notifications'=>$notifs,'unread'=>(int)$unread->fetchColumn()]);
