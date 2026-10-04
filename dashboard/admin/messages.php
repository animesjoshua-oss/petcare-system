<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mailer.php';
requireLogin(); requireRole(['admin','superadmin']);
$user = currentUser();
$pageTitle   = 'Contact Messages';
$isDashboard = true;
$db  = getDB();
$msg = ''; $msgType = 'success';

// ── Handle reply submission ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reply'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid request.'; $msgType = 'danger';
    } else {
        $cid   = (int)($_POST['contact_id'] ?? 0);
        $reply = trim($_POST['reply_text']  ?? '');

        if (!$cid || !$reply) {
            $msg = 'Reply cannot be empty.'; $msgType = 'danger';
        } else {
            // Fetch original message
            $orig = $db->prepare('SELECT * FROM contact_messages WHERE id = ?');
            $orig->execute([$cid]);
            $orig = $orig->fetch();

            if ($orig) {
                // Save reply to DB
                $db->prepare('
                    INSERT INTO contact_replies (message_id, admin_id, reply)
                    VALUES (?,?,?)
                ')->execute([$cid, $user['id'], $reply]);

                // Mark message as replied
                $db->prepare("
                    UPDATE contact_messages SET status = 'replied' WHERE id = ?
                ")->execute([$cid]);

                // Send reply email to customer
                $emailResult = sendMail(
                    $orig['email'],
                    $orig['name'],
                    'Re: ' . $orig['subject'] . ' — PetCare Clinic',
                    adminReplyEmailHtml(
                        $orig['name'],
                        $orig['subject'],
                        $orig['message'],
                        $reply,
                        $user['first_name'] . ' ' . $user['last_name']
                    )
                );

                // In-app notification if user is registered
                if ($orig['user_id']) {
                    createNotification(
                        (int)$orig['user_id'],
                        '💬 Admin replied to your message',
                        'Your message "' . mb_substr($orig['subject'], 0, 60) . '" has been replied to.',
                        'info',
                        APP_URL . '/dashboard/owner/my-messages.php'
                    );
                }

                $msg = 'Reply sent to ' . htmlspecialchars($orig['name']) . '!';
                $msgType = 'success';
            }
        }
    }
}

// ── Handle single delete ─────────────────────────────────
if (isset($_GET['delete']) && verifyCsrfToken($_GET['csrf'] ?? '')) {
    $did = (int)$_GET['delete'];
    $db->prepare('DELETE FROM contact_replies  WHERE message_id = ?')->execute([$did]);
    $db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$did]);
    header('Location: messages.php?deleted=1'); exit;
}

// ── Handle bulk status change + bulk delete ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid request.'; $msgType = 'danger';
    } else {
        $action = $_POST['bulk_action'];
        $ids    = array_filter(array_map('intval', $_POST['msg_ids'] ?? []));
        if ($ids && in_array($action, ['read','unread','closed','reopen','delete'], true)) {
            if ($action === 'delete') {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->prepare("DELETE FROM contact_replies  WHERE message_id IN ($placeholders)")->execute($ids);
                $db->prepare("DELETE FROM contact_messages WHERE id          IN ($placeholders)")->execute($ids);
                $count = count($ids);
                $msg = "$count message(s) deleted.";
            } else {
                $newStatus = $action === 'reopen' ? 'read' : $action;
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->prepare("UPDATE contact_messages SET status=? WHERE id IN ($placeholders)")
                   ->execute(array_merge([$newStatus], $ids));
                $count = count($ids);
                $msg = "$count message(s) marked as {$newStatus}.";
            }
            $msgType = 'success';
        }
    }
}

if (isset($_GET['close']) && verifyCsrfToken($_GET['csrf'] ?? '')) {
    $db->prepare("UPDATE contact_messages SET status='closed' WHERE id=?")
       ->execute([(int)$_GET['close']]);
    header('Location: messages.php?closed=1'); exit;
}

if (isset($_GET['reopen']) && verifyCsrfToken($_GET['csrf'] ?? '')) {
    $db->prepare("UPDATE contact_messages SET status='read' WHERE id=?")
       ->execute([(int)$_GET['reopen']]);
    header('Location: messages.php?reopened=1'); exit;
}

// ── Filters ───────────────────────────────────────────────
$status  = sanitize($_GET['status'] ?? '');
$search  = sanitize($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where  = ['1=1']; $params = [];
if ($status) { $where[] = 'm.status = ?';  $params[] = $status; }
if ($search) {
    $where[] = '(m.name LIKE ? OR m.email LIKE ? OR m.subject LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$ws = 'WHERE ' . implode(' AND ', $where);

// Auto-mark as read when viewed
if (isset($_GET['id'])) {
    $db->prepare("UPDATE contact_messages SET status='read' WHERE id=? AND status='unread'")
       ->execute([(int)$_GET['id']]);
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM contact_messages m $ws");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pg    = getPaginationData($total, $page, $perPage);

$messages = $db->prepare("
    SELECT m.*,
           r.reply         AS last_reply,
           r.created_at    AS reply_at,
           CONCAT(u.first_name,' ',u.last_name) AS admin_name
    FROM   contact_messages m
    LEFT JOIN (
        SELECT cr.message_id, cr.reply, cr.created_at, cr.admin_id
        FROM   contact_replies cr
        INNER JOIN (SELECT message_id, MAX(id) AS max_id FROM contact_replies GROUP BY message_id) lm
               ON cr.id = lm.max_id
    ) r ON r.message_id = m.id
    LEFT JOIN users u ON u.id = r.admin_id
    $ws
    ORDER BY
        CASE m.status WHEN 'unread' THEN 0 WHEN 'read' THEN 1 WHEN 'replied' THEN 2 ELSE 3 END,
        m.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$messages->execute($params);
$messages = $messages->fetchAll();

// Status counts
$counts = [];
foreach (['unread','read','replied','closed'] as $s) {
    $cs = $db->prepare("SELECT COUNT(*) FROM contact_messages WHERE status=?");
    $cs->execute([$s]);
    $counts[$s] = (int)$cs->fetchColumn();
}
$counts['all'] = array_sum($counts);

// Selected message for detail view
$selected = null;
$replies  = [];
if (isset($_GET['id'])) {
    $selStmt = $db->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $selStmt->execute([(int)$_GET['id']]);
    $selected = $selStmt->fetch();

    if ($selected) {
        $repStmt = $db->prepare('
            SELECT r.*, CONCAT(u.first_name,\' \',u.last_name) AS admin_name
            FROM   contact_replies r
            JOIN   users u ON u.id = r.admin_id
            WHERE  r.message_id = ?
            ORDER  BY r.created_at ASC
        ');
        $repStmt->execute([$selected['id']]);
        $replies = $repStmt->fetchAll();
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>Contact Messages</span>
    </div>
    <h1><i class="fas fa-envelope" style="color:var(--primary);"></i> Contact Messages</h1>
    <p>Manage and reply to messages sent through the contact form.</p>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000">
    <i class="fas fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= $msg ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($_GET['closed'])): ?>
  <div class="alert alert-info" data-auto-dismiss="3000"><i class="fas fa-check-circle"></i> Message closed.</div>
  <?php endif; ?>
  <?php if (!empty($_GET['deleted'])): ?>
  <div class="alert alert-success" data-auto-dismiss="3000"><i class="fas fa-trash"></i> Message(s) deleted.</div>
  <?php endif; ?>

  <!-- ── Status tabs ──────────────────────────────────── -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <a href="messages.php" class="btn btn-sm <?= !$status?'btn-primary':'btn-outline' ?>">
      All <span style="opacity:.7;">(<?= $counts['all'] ?>)</span>
    </a>
    <?php foreach (['unread'=>'btn-danger','read'=>'btn-info','replied'=>'btn-success','closed'=>'btn-secondary'] as $s=>$cls): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?$cls:'btn-outline' ?>" style="position:relative;">
      <?= ucfirst($s) ?>
      <?php if ($counts[$s]>0): ?>
        <span style="background:var(--<?= $s==='unread'?'danger':($s==='read'?'info':($s==='replied'?'success':'text-muted')) ?>);
               color:#fff;font-size:.65rem;font-weight:700;padding:1px 5px;border-radius:50px;margin-left:.25rem;">
          <?= $counts[$s] ?>
        </span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Search ──────────────────────────────────────── -->
  <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <input type="hidden" name="status" value="<?= $status ?>">
    <div class="form-group" style="margin:0;flex:1;min-width:220px;">
      <input type="text" name="search" class="form-control"
        placeholder="Search name, email, subject…"
        value="<?= sanitize($search) ?>">
    </div>
    <button class="btn btn-primary" style="height:44px;">
      <i class="fas fa-search"></i> Search
    </button>
    <?php if ($search): ?>
    <a href="?status=<?= $status ?>" class="btn btn-outline" style="height:44px;">Clear</a>
    <?php endif; ?>
  </form>

  <!-- ── Two-column layout: list + detail ─────────────── -->
  <div style="display:grid;grid-template-columns:<?= $selected?'380px 1fr':'1fr' ?>;gap:1.5rem;align-items:start;">

    <!-- Message list -->
    <div class="card" style="overflow:hidden;">
      <?php if ($messages): ?>
      <div class="table-wrap" style="<?= $selected?'':'overflow-x:auto;' ?>">
        <?php if (!$selected): ?>
        <!-- Full table view when no message selected -->
        <form method="POST" id="bulkForm">
          <?= csrfField() ?>
          <input type="hidden" name="bulk_action" id="bulkActionInput" value="">

          <!-- Bulk action toolbar — shows when checkboxes selected -->
          <div id="bulkToolbar" style="display:none;padding:.75rem 1rem;
               background:rgba(30,64,255,.07);border-bottom:1px solid var(--border);
               align-items:center;gap:.75rem;flex-wrap:wrap;">
            <span id="selectedCount" style="font-size:.85rem;font-weight:600;color:var(--primary);">
              0 selected
            </span>
            <button type="button" class="btn btn-sm btn-success" onclick="bulkAction('read')">
              <i class="fas fa-envelope-open"></i> Mark Read
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('unread')">
              <i class="fas fa-envelope"></i> Mark Unread
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="bulkAction('closed')">
              <i class="fas fa-check"></i> Close
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmBulkDelete()">
              <i class="fas fa-trash"></i> Delete
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="deselectAll()" style="margin-left:auto;">
              <i class="fas fa-times"></i> Deselect All
            </button>
          </div>

        <table>
          <thead>
            <tr>
              <th style="width:36px;">
                <input type="checkbox" id="selectAll" title="Select all"
                       style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer;">
              </th>
              <th>#</th>
              <th>From</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Received</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($messages as $m): ?>
          <tr style="<?= $m['status']==='unread'?'font-weight:600;background:rgba(30,64,255,.03);':'' ?>"
              class="msg-row" data-id="<?= $m['id'] ?>">
            <td>
              <input type="checkbox" name="msg_ids[]" value="<?= $m['id'] ?>"
                     class="msg-checkbox"
                     style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer;">
            </td>
            <td style="font-size:.8rem;color:var(--text-muted);">#<?= $m['id'] ?></td>
            <td>
              <div style="font-weight:600;"><?= sanitize($m['name']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted);"><?= sanitize($m['email']) ?></div>
            </td>
            <td style="max-width:200px;">
              <?php if ($m['status']==='unread'): ?>
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;
                             background:var(--primary);margin-right:.4rem;vertical-align:middle;"></span>
              <?php endif; ?>
              <?= sanitize(mb_substr($m['subject'],0,50)) ?>
            </td>
            <td>
              <?php
              $badgeMap = ['unread'=>'badge-danger','read'=>'badge-info','replied'=>'badge-success','closed'=>'badge-secondary'];
              echo '<span class="badge '.($badgeMap[$m['status']]??'badge-secondary').'">'.ucfirst($m['status']).'</span>';
              ?>
            </td>
            <td style="font-size:.82rem;"><?= date('M j, Y', strtotime($m['created_at'])) ?><br>
              <span style="color:var(--text-muted);font-size:.75rem;"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
            </td>
            <td>
              <div style="display:flex;gap:.35rem;">
                <a href="?id=<?= $m['id'] ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                   class="btn btn-sm btn-primary">
                  <i class="fas fa-eye"></i> View
                </a>
                <?php if ($m['status']!=='closed'): ?>
                <a href="?close=<?= $m['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-outline"
                   data-confirm="Mark this message as closed?">
                  <i class="fas fa-check"></i>
                </a>
                <?php else: ?>
                <a href="?reopen=<?= $m['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-outline"
                   data-confirm="Reopen this message?">
                  <i class="fas fa-rotate-left"></i>
                </a>
                <?php endif; ?>
                <a href="?delete=<?= $m['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-danger"
                   data-confirm="Delete this message permanently? This cannot be undone.">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </form>
        <?php else: ?>
        <!-- Compact list when message is selected on the right -->
        <?php foreach ($messages as $m): ?>
        <a href="?id=<?= $m['id'] ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
           style="display:block;padding:.9rem 1.1rem;border-bottom:1px solid var(--border);text-decoration:none;
                  background:<?= (isset($selected['id']) && $selected['id']==$m['id'])?'rgba(30,64,255,.07)':'transparent' ?>;
                  transition:background .15s;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.2rem;">
            <span style="font-weight:<?= $m['status']==='unread'?'700':'600' ?>;font-size:.875rem;color:var(--text);">
              <?php if ($m['status']==='unread'): ?>
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--primary);margin-right:.35rem;"></span>
              <?php endif; ?>
              <?= sanitize($m['name']) ?>
            </span>
            <span style="font-size:.72rem;color:var(--text-muted);"><?= date('M j', strtotime($m['created_at'])) ?></span>
          </div>
          <div style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= sanitize(mb_substr($m['subject'],0,45)) ?>
          </div>
          <?php
          $bm=['unread'=>'badge-danger','read'=>'badge-info','replied'=>'badge-success','closed'=>'badge-secondary'];
          echo '<span class="badge '.($bm[$m['status']]??'badge-secondary').'" style="font-size:.65rem;margin-top:.3rem;">'.ucfirst($m['status']).'</span>';
          ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>No messages<?= $status ? ' with status "'.$status.'"' : '' ?></h3>
        <p>Messages sent through the contact form will appear here.</p>
      </div>
      <?php endif; ?>
      <?php if (!$selected): renderPagination($pg); endif; ?>
    </div>

    <!-- ── Message detail + reply ───────────────────── -->
    <?php if ($selected): ?>
    <div>
      <!-- Message card -->
      <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
          <div>
            <h3 style="margin-bottom:.2rem;"><?= sanitize($selected['subject']) ?></h3>
            <span style="font-size:.8rem;color:var(--text-muted);">
              From <strong><?= sanitize($selected['name']) ?></strong>
              &lt;<?= sanitize($selected['email']) ?>&gt;
              <?php if ($selected['phone']): ?> · <?= sanitize($selected['phone']) ?><?php endif; ?>
              · <?= date('F j, Y \a\t g:i A', strtotime($selected['created_at'])) ?>
            </span>
          </div>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <?php
            $bm=['unread'=>'badge-danger','read'=>'badge-info','replied'=>'badge-success','closed'=>'badge-secondary'];
            echo '<span class="badge '.($bm[$selected['status']]??'badge-secondary').'">'.ucfirst($selected['status']).'</span>';
            ?>
            <?php if ($selected['status']!=='closed'): ?>
            <a href="?close=<?= $selected['id'] ?>&csrf=<?= generateCsrfToken() ?>"
               class="btn btn-sm btn-outline"
               data-confirm="Close this message?">
              <i class="fas fa-check"></i> Close
            </a>
            <?php else: ?>
            <a href="?reopen=<?= $selected['id'] ?>&csrf=<?= generateCsrfToken() ?>"
               class="btn btn-sm btn-outline"
               data-confirm="Reopen?">
              <i class="fas fa-rotate-left"></i> Reopen
            </a>
            <?php endif; ?>
            <a href="messages.php?status=<?= $status ?>"
               class="btn btn-sm btn-outline">
              <i class="fas fa-times"></i>
            </a>
          </div>
        </div>
        <!-- Original message -->
        <div class="card-body">
          <div style="background:var(--bg);border-left:4px solid var(--primary);border-radius:8px;
                      padding:1rem 1.25rem;font-size:.9rem;line-height:1.8;color:var(--text);white-space:pre-wrap;">
<?= htmlspecialchars($selected['message'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>

        <!-- Previous replies thread -->
        <?php if ($replies): ?>
        <div style="border-top:1px solid var(--border);padding:1rem 1.5rem;">
          <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
                    color:var(--text-muted);margin-bottom:.75rem;">
            <i class="fas fa-comments"></i> Reply Thread (<?= count($replies) ?>)
          </p>
          <?php foreach ($replies as $r): ?>
          <div style="background:rgba(0,212,170,.06);border:1px solid rgba(0,212,170,.2);
                      border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.65rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
              <span style="font-size:.8rem;font-weight:700;color:var(--secondary);">
                <i class="fas fa-user-shield"></i> <?= sanitize($r['admin_name']) ?>
              </span>
              <span style="font-size:.75rem;color:var(--text-muted);">
                <?= date('M j, Y g:i A', strtotime($r['created_at'])) ?>
              </span>
            </div>
            <p style="font-size:.875rem;line-height:1.7;margin:0;white-space:pre-wrap;color:var(--text);">
              <?= htmlspecialchars($r['reply'], ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Reply form -->
      <?php if ($selected['status'] !== 'closed'): ?>
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-reply" style="color:var(--primary);"></i> Reply to <?= sanitize($selected['name']) ?></h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="do_reply" value="1">
            <input type="hidden" name="contact_id" value="<?= $selected['id'] ?>">
            <div class="form-group">
              <label class="form-label">Your Reply</label>
              <textarea name="reply_text" class="form-control" rows="5"
                placeholder="Type your reply here…" required
                style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Send Reply
              </button>
              <span style="font-size:.8rem;color:var(--text-muted);">
                <i class="fas fa-envelope"></i>
                Will be emailed to <strong><?= sanitize($selected['email']) ?></strong>
                <?php if ($selected['user_id']): ?>
                  + in-app notification
                <?php endif; ?>
              </span>
            </div>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-info">
        <i class="fas fa-lock"></i> This message is closed. Reopen it to send a reply.
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div><!-- /.grid -->

</main>

<script>
// ── Checkbox bulk selection ───────────────────────────────
(function () {
  var selectAll  = document.getElementById('selectAll');
  var toolbar    = document.getElementById('bulkToolbar');
  var countEl    = document.getElementById('selectedCount');

  function getCheckboxes() {
    return document.querySelectorAll('.msg-checkbox');
  }

  function getChecked() {
    return document.querySelectorAll('.msg-checkbox:checked');
  }

  function updateToolbar() {
    var checked = getChecked();
    var n = checked.length;
    if (toolbar) {
      toolbar.style.display = n > 0 ? 'flex' : 'none';
    }
    if (countEl) {
      countEl.textContent = n + ' selected';
    }
    // Sync select-all state
    if (selectAll) {
      var total = getCheckboxes().length;
      selectAll.indeterminate = n > 0 && n < total;
      selectAll.checked = n === total && total > 0;
    }
    // Highlight selected rows
    getCheckboxes().forEach(function (cb) {
      var row = cb.closest('tr');
      if (row) row.style.background = cb.checked ? 'rgba(30,64,255,.08)' : '';
    });
  }

  // Select All toggle
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      getCheckboxes().forEach(function (cb) { cb.checked = selectAll.checked; });
      updateToolbar();
    });
  }

  // Individual checkbox change
  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('msg-checkbox')) updateToolbar();
  });

  // Bulk action
  window.bulkAction = function (action) {
    var checked = getChecked();
    if (!checked.length) return;
    document.getElementById('bulkActionInput').value = action;
    document.getElementById('bulkForm').submit();
  };

  // Bulk delete with confirm
  window.confirmBulkDelete = function () {
    var checked = getChecked();
    if (!checked.length) return;
    if (confirm('Delete ' + checked.length + ' selected message(s)? This cannot be undone.')) {
      document.getElementById('bulkActionInput').value = 'delete';
      document.getElementById('bulkForm').submit();
    }
  };

  // Deselect all
  window.deselectAll = function () {
    getCheckboxes().forEach(function (cb) { cb.checked = false; });
    if (selectAll) selectAll.checked = false;
    updateToolbar();
  };

  // Click on row (outside checkbox/button) → toggle checkbox
  document.querySelectorAll('.msg-row').forEach(function (row) {
    row.addEventListener('click', function (e) {
      if (e.target.tagName === 'INPUT' || e.target.closest('a') || e.target.closest('button')) return;
      var cb = row.querySelector('.msg-checkbox');
      if (cb) { cb.checked = !cb.checked; updateToolbar(); }
    });
  });
})();
</script>

<?php
// ── Email: admin reply to customer ───────────────────────
function adminReplyEmailHtml(
    string $customerName,
    string $subject,
    string $originalMessage,
    string $replyText,
    string $adminName
): string {
    $contactUrl  = APP_URL . '/pages/contact.php';
    $origHtml    = nl2br(htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8'));
    $replyHtml   = nl2br(htmlspecialchars($replyText,       ENT_QUOTES, 'UTF-8'));
    $date        = date('F j, Y \a\t g:i A');
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:32px;text-align:center;">
            <div style="font-size:2rem;">💬</div>
            <h1 style="color:#fff;font-size:1.3rem;margin:10px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.85rem;margin:0;">Reply to your message</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:32px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">
              Hi <strong>{$customerName}</strong> 👋
            </p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              <strong>{$adminName}</strong> from the PetCare+ team has replied to your message
              about <strong>"{$subject}"</strong>:
            </p>
            <!-- Reply box -->
            <div style="background:#F0F9FF;border:1px solid #BAE6FD;border-left:4px solid #00D4AA;
                        border-radius:10px;padding:18px 20px;margin-bottom:1.5rem;
                        font-size:.9rem;color:#0C4A6E;line-height:1.8;">
              {$replyHtml}
            </div>
            <!-- Original message -->
            <details style="margin-bottom:1.5rem;">
              <summary style="font-size:.8rem;color:#64748B;cursor:pointer;padding:.5rem 0;">
                📩 Your original message
              </summary>
              <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;
                          padding:14px 18px;margin-top:.5rem;font-size:.85rem;
                          color:#475569;line-height:1.7;">
                {$origHtml}
              </div>
            </details>
            <p style="font-size:.875rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              If you have further questions, feel free to contact us again:
            </p>
            <div style="text-align:center;margin:20px 0;">
              <a href="{$contactUrl}"
                 style="background:linear-gradient(135deg,#1E40FF,#00D4AA);color:#fff;
                        text-decoration:none;padding:12px 32px;border-radius:50px;
                        font-weight:700;font-size:.9rem;display:inline-block;">
                📬 Send Another Message
              </a>
            </div>
            <p style="font-size:.78rem;color:#94A3B8;text-align:center;">
              Replied by <strong>{$adminName}</strong> on {$date}
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:16px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.75rem;color:#94A3B8;margin:0;">
              &copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
?>
