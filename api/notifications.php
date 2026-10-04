<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db     = getDB();
$userId = $_SESSION['user_id'];
$action = sanitize($_GET['action'] ?? 'list');

if ($action === 'mark_read') {
    $nid = (int)($_GET['id'] ?? 0);
    if ($nid) { $db->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$nid,$userId]); }
    else { $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$userId]); }
    jsonResponse(['success'=>true]);
}

$notifs = $db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30');
$notifs->execute([$userId]); $notifs=$notifs->fetchAll();
$unread = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$unread->execute([$userId]);
jsonResponse(['notifications'=>$notifs,'unread'=>(int)$unread->fetchColumn()]);
