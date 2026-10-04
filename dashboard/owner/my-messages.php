<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user        = currentUser();
$pageTitle   = 'My Messages';
$isDashboard = true;
$db          = getDB();

// ── Fetch this user's messages + replies ──────────────────
$messages = $db->prepare("
    SELECT m.*,
           COUNT(r.id)                          AS reply_count,
           MAX(r.created_at)                    AS last_reply_at
    FROM   contact_messages m
    LEFT JOIN contact_replies r ON r.message_id = m.id
    WHERE  m.user_id = ?
    GROUP  BY m.id
    ORDER  BY m.created_at DESC
");
$messages->execute([$user['id']]);
$messages = $messages->fetchAll();

// ── Selected message thread ────────────────────────────────
$selected = null;
$replies  = [];
if (isset($_GET['id'])) {
    $selStmt = $db->prepare(
        'SELECT * FROM contact_messages WHERE id = ? AND user_id = ?'
    );
    $selStmt->execute([(int)$_GET['id'], $user['id']]);
    $selected = $selStmt->fetch();

    if ($selected) {
        $repStmt = $db->prepare("
            SELECT r.*, CONCAT(u.first_name,' ',u.last_name) AS admin_name
            FROM   contact_replies r
            JOIN   users u ON u.id = r.admin_id
            WHERE  r.message_id = ?
            ORDER  BY r.created_at ASC
        ");
        $repStmt->execute([$selected['id']]);
        $replies = $repStmt->fetchAll();
    }
}

// Count unread replies (messages with status = 'replied' not yet seen)
$unreadReplies = 0;
foreach ($messages as $m) {
    if ($m['status'] === 'replied' && $m['reply_count'] > 0) $unreadReplies++;
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>

<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>My Messages</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
      <div>
        <h1><i class="fas fa-envelope" style="color:var(--primary);"></i> My Messages</h1>
        <p>View your contact messages and replies from our team.</p>
      </div>
      <a href="<?= APP_URL ?>/pages/contact.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Message
      </a>
    </div>
  </div>

  <?php if (empty($messages)): ?>
  <!-- ── Empty state ──────────────────────────────────── -->
  <div class="card">
    <div class="empty-state" style="padding:4rem 2rem;">
      <i class="fas fa-inbox" style="font-size:3.5rem;color:var(--border);margin-bottom:1rem;"></i>
      <h3>No messages yet</h3>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">
        Have a question or concern? Send us a message and our team will get back to you.
      </p>
      <a href="<?= APP_URL ?>/pages/contact.php" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> Send a Message
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- ── Two-column layout ────────────────────────────── -->
  <div style="display:grid;grid-template-columns:<?= $selected?'340px 1fr':'1fr' ?>;gap:1.5rem;align-items:start;">

    <!-- Message list -->
    <div class="card" style="overflow:hidden;">
      <?php foreach ($messages as $m): ?>
      <?php
        $isSelected = $selected && $selected['id'] == $m['id'];
        $hasReply   = $m['reply_count'] > 0;
        $badgeMap   = ['unread'=>'badge-danger','read'=>'badge-secondary','replied'=>'badge-success','closed'=>'badge-secondary'];
      ?>
      <a href="?id=<?= $m['id'] ?>"
         style="display:block;padding:1rem 1.25rem;border-bottom:1px solid var(--border);
                text-decoration:none;transition:background .15s;
                background:<?= $isSelected?'rgba(30,64,255,.07)':'transparent' ?>;">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.3rem;">
          <span style="font-size:.875rem;font-weight:700;color:var(--text);
                       flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:.5rem;">
            <?php if ($m['status']==='replied' && $hasReply): ?>
              <span style="display:inline-block;width:8px;height:8px;border-radius:50%;
                           background:var(--success);margin-right:.35rem;vertical-align:middle;"></span>
            <?php endif; ?>
            <?= sanitize(mb_substr($m['subject'], 0, 40)) ?>
          </span>
          <span style="font-size:.72rem;color:var(--text-muted);flex-shrink:0;">
            <?= date('M j', strtotime($m['created_at'])) ?>
          </span>
        </div>

        <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.4rem;
                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          <?= sanitize(mb_substr($m['message'], 0, 60)) ?>…
        </div>

        <div style="display:flex;align-items:center;gap:.5rem;">
          <span class="badge <?= $badgeMap[$m['status']] ?? 'badge-secondary' ?>" style="font-size:.65rem;">
            <?= $m['status'] === 'replied' ? '✅ Replied' : ucfirst($m['status']) ?>
          </span>
          <?php if ($hasReply): ?>
          <span style="font-size:.72rem;color:var(--text-muted);">
            <i class="fas fa-reply"></i> <?= $m['reply_count'] ?> reply
          </span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- ── Message detail ───────────────────────────── -->
    <?php if ($selected): ?>
    <div>
      <!-- Original message -->
      <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
          <div>
            <h3 style="margin-bottom:.25rem;"><?= sanitize($selected['subject']) ?></h3>
            <span style="font-size:.8rem;color:var(--text-muted);">
              Sent <?= date('F j, Y \a\t g:i A', strtotime($selected['created_at'])) ?>
            </span>
          </div>
          <?php
          $bm = ['unread'=>'badge-danger','read'=>'badge-secondary','replied'=>'badge-success','closed'=>'badge-secondary'];
          echo '<span class="badge '.($bm[$selected['status']]??'badge-secondary').'">'.
               ($selected['status']==='replied'?'✅ Replied':ucfirst($selected['status'])).'</span>';
          ?>
        </div>
        <div class="card-body">
          <!-- Your message bubble -->
          <div style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
              <div style="width:30px;height:30px;border-radius:50%;
                          background:linear-gradient(135deg,var(--primary),var(--secondary));
                          color:#fff;display:flex;align-items:center;justify-content:center;
                          font-size:.75rem;font-weight:700;flex-shrink:0;">
                <?= strtoupper(substr($user['first_name'],0,1)) ?>
              </div>
              <span style="font-size:.82rem;font-weight:700;color:var(--text);">
                You <span style="font-weight:400;color:var(--text-muted);">· <?= date('M j, Y g:i A', strtotime($selected['created_at'])) ?></span>
              </span>
            </div>
            <div style="background:rgba(30,64,255,.06);border:1px solid rgba(30,64,255,.15);
                        border-radius:12px;border-top-left-radius:2px;
                        padding:1rem 1.25rem;font-size:.9rem;line-height:1.8;
                        color:var(--text);white-space:pre-wrap;margin-left:38px;">
<?= htmlspecialchars($selected['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <!-- Reply thread -->
          <?php if ($replies): ?>
          <?php foreach ($replies as $r): ?>
          <div style="margin-bottom:1rem;">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
              <div style="width:30px;height:30px;border-radius:50%;
                          background:linear-gradient(135deg,var(--secondary),#10B981);
                          color:#fff;display:flex;align-items:center;justify-content:center;
                          font-size:.7rem;flex-shrink:0;">
                <i class="fas fa-user-shield"></i>
              </div>
              <span style="font-size:.82rem;font-weight:700;color:var(--secondary);">
                <?= sanitize($r['admin_name']) ?>
                <span style="font-weight:400;color:var(--text-muted);">
                  · <?= date('M j, Y g:i A', strtotime($r['created_at'])) ?>
                </span>
              </span>
            </div>
            <div style="background:rgba(0,212,170,.06);border:1px solid rgba(0,212,170,.2);
                        border-radius:12px;border-top-left-radius:2px;
                        padding:1rem 1.25rem;font-size:.9rem;line-height:1.8;
                        color:var(--text);white-space:pre-wrap;margin-left:38px;">
<?= htmlspecialchars($r['reply'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>

          <!-- No reply yet -->
          <?php if (!$replies): ?>
          <div style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:.875rem;">
            <i class="fas fa-clock" style="font-size:1.5rem;margin-bottom:.5rem;display:block;opacity:.4;"></i>
            Our team will reply within 24 hours.
          </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- Send another message CTA -->
      <div style="text-align:center;">
        <a href="<?= APP_URL ?>/pages/contact.php" class="btn btn-outline btn-sm">
          <i class="fas fa-plus"></i> Send Another Message
        </a>
        <?php if ($selected['status'] === 'closed'): ?>
        <span class="badge badge-secondary" style="margin-left:.75rem;">
          <i class="fas fa-lock"></i> This conversation is closed
        </span>
        <?php endif; ?>
      </div>
    </div>

    <?php else: ?>
    <!-- No message selected — prompt to pick one -->
    <div class="card">
      <div class="empty-state" style="padding:3rem 2rem;">
        <i class="fas fa-hand-pointer" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem;"></i>
        <h3>Select a message</h3>
        <p>Click any message on the left to view the full thread.</p>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.grid -->
  <?php endif; ?>

</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
