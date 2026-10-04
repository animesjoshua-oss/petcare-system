<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = currentUser();
$pageTitle = 'Notifications';
$db = getDB();

// Mark all read
if (isset($_GET['mark_all'])) {
    $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
    header('Location: notifications.php'); exit;
}

$notifs = $db->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
$notifs->execute([$user['id']]); $notifs=$notifs->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div style="background:var(--bg);min-height:100vh;">
  <div class="container" style="padding:1.5rem;max-width:700px;padding-top:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
      <h1 style="font-size:1.5rem;font-weight:700;">Notifications</h1>
      <?php if ($notifs): ?><a href="?mark_all=1" class="btn btn-sm btn-outline"><i class="fas fa-check-double"></i> Mark all read</a><?php endif; ?>
    </div>
    <div class="card">
      <?php if ($notifs): foreach ($notifs as $n): ?>
      <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;gap:1rem;align-items:flex-start;<?= !$n['is_read']?'background:rgba(30,64,255,.03);':'' ?>">
        <div style="width:40px;height:40px;border-radius:50%;background:<?= !$n['is_read']?'var(--primary)':'var(--border)' ?>;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:.9rem;">
          <i class="fas fa-<?= $n['type']==='appointment'?'calendar':($n['type']==='warning'?'exclamation-triangle':'bell') ?>"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:<?= !$n['is_read']?'700':'600' ?>;font-size:.9rem;margin-bottom:.2rem;"><?= sanitize($n['title']) ?></div>
          <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.3rem;"><?= sanitize($n['message']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)"><?= timeAgo($n['created_at']) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
        <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:.35rem;"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; else: ?>
      <div class="empty-state" style="padding:4rem;"><i class="fas fa-bell-slash"></i><h3>No notifications</h3><p>You're all caught up!</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
