<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['superadmin']);
$user = currentUser();
$pageTitle = 'Manage Users';
$isDashboard = true;
$db = getDB();
$msg = ''; $msgType = 'success';

// Add user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])) {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { $msg='Invalid request.'; $msgType='danger'; }
    else {
        $first  = sanitize($_POST['first_name']??'');
        $last   = sanitize($_POST['last_name']??'');
        $email  = trim(strtolower($_POST['email']??''));
        $phone  = sanitize($_POST['phone']??'');
        $roleId = (int)($_POST['role_id']??1);
        $pass   = $_POST['password']??'Admin@1234';
        $ownerRoleQuery=$db->prepare("SELECT id FROM roles WHERE name='pet_owner' LIMIT 1");
        $ownerRoleQuery->execute(); $ownerRoleId=(int)$ownerRoleQuery->fetchColumn();

        if ($roleId !== $ownerRoleId) {
            $msg = 'Clinic Vet Admins are created through approved clinic applications. Veterinarians and groomers are added by their clinic Vet Admin.';
            $msgType = 'danger';
        } elseif (!$first || !$last || !$email) {
            $msg = 'Please fill all required fields.';
            $msgType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Please provide a valid email address.';
            $msgType = 'danger';
        } else {
            $chk=$db->prepare('SELECT id FROM users WHERE email=?'); $chk->execute([$email]);
            if ($chk->fetch()) { 
                $msg='Email already exists.'; 
                $msgType='danger'; 
            } else {
                try {
                    $db->prepare('INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,is_active,is_email_verified) VALUES (?,?,?,?,?,?,1,1)')
                       ->execute([$roleId,$first,$last,$email,$phone,hashPassword($pass)]);
                    $msg="User <strong>$first $last</strong> added successfully! Default password: <code>Admin@1234</code>";
                    $msgType='success';
                } catch (\Exception $e) {
                    $msg='Failed to add user. Please try again.';
                    $msgType='danger';
                }
            }
        }
    }
}

// Toggle active
if (isset($_GET['toggle']) && verifyCsrfToken($_GET['csrf']??'')) {
    $uid=(int)$_GET['toggle'];
    if ($uid !== $user['id']) { $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id=?')->execute([$uid]); }
    header('Location: users.php?updated=1'); exit;
}

// ── Approve account ───────────────────────────────────────
if (isset($_GET['approve']) && verifyCsrfToken($_GET['csrf']??'')) {
    $uid = (int)$_GET['approve'];
    $db->prepare('UPDATE users SET is_active=1, id_verified=1 WHERE id=?')->execute([$uid]);

    // Email the user
    $approvedUser = $db->prepare('SELECT first_name, last_name, email FROM users WHERE id=?');
    $approvedUser->execute([$uid]);
    $approvedUser = $approvedUser->fetch();
    if ($approvedUser) {
        require_once __DIR__ . '/../../includes/mailer.php';
        $name = $approvedUser['first_name'] . ' ' . $approvedUser['last_name'];
        $html = accountStatusEmailHtml($approvedUser['first_name'], 'approved');
        sendMail($approvedUser['email'], $name, 'Your PetCare+ Account has been Approved!', $html);
        createNotification($uid, 'Account Approved!',
            'Your account has been verified and approved. You can now log in to PetCare+.',
            'success', APP_URL . '/auth/login.php');
    }
    $msg = 'Account approved and user notified.'; $msgType = 'success';
}

// ── Reject account ────────────────────────────────────────
if (isset($_GET['reject']) && verifyCsrfToken($_GET['csrf']??'')) {
    $uid = (int)$_GET['reject'];
    $reason = sanitize($_GET['reason'] ?? 'Your submitted ID could not be verified.');
    $db->prepare('UPDATE users SET is_active=0, id_verified=0 WHERE id=?')->execute([$uid]);

    $rejectedUser = $db->prepare('SELECT first_name, last_name, email FROM users WHERE id=?');
    $rejectedUser->execute([$uid]);
    $rejectedUser = $rejectedUser->fetch();
    if ($rejectedUser) {
        require_once __DIR__ . '/../../includes/mailer.php';
        $name = $rejectedUser['first_name'] . ' ' . $rejectedUser['last_name'];
        $html = accountStatusEmailHtml($rejectedUser['first_name'], 'rejected', $reason);
        sendMail($rejectedUser['email'], $name, 'PetCare+ Account Verification Update', $html);
        createNotification($uid, 'Account Verification Issue',
            'Your account verification was not approved. Reason: ' . $reason,
            'danger', APP_URL . '/auth/register.php');
    }
    $msg = 'Account rejected and user notified.'; $msgType = 'warning';
}

// ── Reactivate inactive account ──────────────────────────
if (isset($_GET['reactivate']) && verifyCsrfToken($_GET['csrf']??'')) {
    $uid = (int)$_GET['reactivate'];
    $db->prepare('
        UPDATE users
        SET    is_active                  = 1,
               inactivity_deactivated_at  = NULL,
               inactivity_warned_at       = NULL
        WHERE  id = ?
    ')->execute([$uid]);

    $ru = $db->prepare('SELECT first_name, last_name, email FROM users WHERE id=?');
    $ru->execute([$uid]);
    $ru = $ru->fetch();
    if ($ru) {
        require_once __DIR__ . '/../../includes/mailer.php';
        sendMail($ru['email'], $ru['first_name'].' '.$ru['last_name'],
            'Your PetCare+ Account Has Been Reactivated',
            accountReactivatedEmailHtml($ru['first_name']));
        createNotification($uid, 'Account Reactivated',
            'Your account has been reactivated by an admin. You can now log in to PetCare+.',
            'success', APP_URL.'/auth/login.php');
    }
    $msg = 'Account reactivated and user notified.'; $msgType = 'success';
}

// ── Lift booking restriction ──────────────────────────────
if (isset($_GET['lift_restriction']) && verifyCsrfToken($_GET['csrf']??'')) {
    $uid = (int)$_GET['lift_restriction'];
    $db->prepare('
        UPDATE users
        SET    is_booking_restricted = 0,
               no_show_count         = 0
        WHERE  id = ?
    ')->execute([$uid]);

    $ru2 = $db->prepare('SELECT first_name, last_name, email FROM users WHERE id=?');
    $ru2->execute([$uid]);
    $ru2 = $ru2->fetch();
    if ($ru2) {
        require_once __DIR__ . '/../../includes/mailer.php';
        sendMail($ru2['email'], $ru2['first_name'].' '.$ru2['last_name'],
            'PetCare+: Your Booking Access Has Been Restored',
            bookingRestoredEmailHtml($ru2['first_name']));
        createNotification($uid, 'Booking Access Restored',
            'Your booking restriction has been lifted. You can now make new appointments on PetCare+.',
            'success', APP_URL.'/dashboard/owner/book.php');
    }
    $msg = 'Booking restriction lifted and user notified.'; $msgType = 'success';
}

$search  = sanitize($_GET['search']??'');
$roleF   = sanitize($_GET['role']??'');
$filterInactive   = isset($_GET['filter']) && $_GET['filter'] === 'inactive';
$filterRestricted = isset($_GET['filter']) && $_GET['filter'] === 'restricted';
$page    = max(1,(int)($_GET['page']??1)); $perPage=20;
$where=['u.id > 0']; $params=[];
if ($search)          { $where[]='(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }
if ($roleF)           { $where[]='r.name=?'; $params[]=$roleF; }
// Inactive filter: auto-deactivated users
if ($filterInactive) { $where[]='u.is_active=0'; $where[]='u.inactivity_deactivated_at IS NOT NULL'; }

// Restricted filter: booking-restricted users
if ($filterRestricted) { $where[]='u.is_booking_restricted=1'; }

$ws='WHERE '.implode(' AND ',$where);

// Count auto-deactivated inactive users
try {
    $inactiveCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=0 AND inactivity_deactivated_at IS NOT NULL")->fetchColumn();
} catch(\Exception $e) { $inactiveCount = 0; }

// Count booking-restricted users
try {
    $restrictedCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_booking_restricted=1")->fetchColumn();
} catch(\Exception $e) { $restrictedCount = 0; }

$total=$db->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id $ws"); $total->execute($params); $total=(int)$total->fetchColumn();
$pg=getPaginationData($total,$page,$perPage);
$users=$db->prepare("SELECT u.*,r.name AS role_name,(SELECT c.clinic_type FROM clinics c WHERE c.admin_id=u.id ORDER BY c.is_active DESC,c.id LIMIT 1) AS clinic_type FROM users u JOIN roles r ON u.role_id=r.id $ws ORDER BY u.created_at DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}");
$users->execute($params); $users=$users->fetchAll();
$roles=$db->query("SELECT * FROM roles WHERE name='pet_owner' ORDER BY id")->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h1>Manage Users</h1>
        <p>All platform users and their roles.</p>
      </div>
      <button onclick="openModal('addUserModal')" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</button>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>" data-auto-dismiss="4000"><i class="fas fa-check-circle"></i><?= $msg ?></div><?php endif; ?>

  <!-- All / Inactive / Restricted tabs -->
  <div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap;padding:10px 0;overflow:visible;">
    <a href="users.php" class="btn <?= !$filterInactive && !$filterRestricted ? 'btn-primary' : 'btn-outline' ?>" style="font-size:.875rem;">
      <i class="fas fa-users"></i> All Users
    </a>
    <a href="users.php?filter=inactive" class="btn <?= $filterInactive ? 'btn-secondary' : 'btn-outline' ?>" style="font-size:.875rem;position:relative;overflow:visible;">
      <i class="fas fa-user-slash"></i> Inactive (Auto-Deactivated)
      <?php if ($inactiveCount > 0): ?>
        <span style="position:absolute;top:-8px;right:-8px;background:#64748B;color:#fff;border-radius:50%;width:20px;height:20px;font-size:.7rem;display:flex;align-items:center;justify-content:center;font-weight:700;box-shadow:0 2px 4px rgba(0,0,0,0.2);z-index:10;"><?= $inactiveCount ?></span>
      <?php endif; ?>
    </a>
    <a href="users.php?filter=restricted" class="btn <?= $filterRestricted ? 'btn-danger' : 'btn-outline' ?>" style="font-size:.875rem;position:relative;overflow:visible;">
      <i class="fas fa-ban"></i> Booking Restricted
      <?php if ($restrictedCount > 0): ?>
        <span style="position:absolute;top:-8px;right:-8px;background:#EF4444;color:#fff;border-radius:50%;width:20px;height:20px;font-size:.7rem;display:flex;align-items:center;justify-content:center;font-weight:700;box-shadow:0 2px 4px rgba(0,0,0,0.2);z-index:10;"><?= $restrictedCount ?></span>
      <?php endif; ?>
    </a>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
    <div class="form-group" style="margin:0;flex:1;min-width:200px;">
      <input type="text" name="search" class="form-control" placeholder="Search name or email…" value="<?= sanitize($search) ?>">
    </div>
    <div class="form-group" style="margin:0;">
      <select name="role" class="form-control" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <?php foreach ($roles as $r): ?><option value="<?= $r['name'] ?>" <?= $roleF===$r['name']?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r['name'])) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" style="height:44px;">Search</button>
    <?php if ($search||$roleF): ?><a href="users.php" class="btn btn-outline" style="height:44px;">Clear</a><?php endif; ?>
  </form>

  <div class="card">
    <div class="table-wrap" style="overflow-x:auto;">
      <table style="min-width:1010px;width:100%;table-layout:fixed;">
        <colgroup>
          <col style="width:50px;"><!-- # -->
          <col style="width:200px;"><!-- Name -->
          <col style="width:220px;"><!-- Email/Phone -->
          <col style="width:130px;"><!-- Role -->
          <col style="width:140px;"><!-- Status -->
          <col style="width:130px;"><!-- Joined -->
          <col style="width:140px;"><!-- Actions -->
        </colgroup>
        <thead>
          <tr>
            <th style="vertical-align:middle;text-align:center;">#</th>
            <th style="vertical-align:middle;">Name</th>
            <th style="vertical-align:middle;">Email / Phone</th>
            <th style="vertical-align:middle;">Role</th>
            <th style="vertical-align:middle;">Status</th>
            <th style="vertical-align:middle;">Joined</th>
            <th style="vertical-align:middle;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php $rowNum = $pg['offset'] + 1; foreach ($users as $u): ?>
        <tr>
          <td style="vertical-align:middle;text-align:center;font-weight:600;font-size:.85rem;color:var(--text-muted);">
            <?= $rowNum++ ?>
          </td>
          <td style="vertical-align:middle;">
            <div style="display:flex;align-items:center;gap:.6rem;">
              <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">
                <?= strtoupper(substr($u['first_name'],0,1)) ?>
              </div>
              <span style="font-weight:600;font-size:.9rem;">
                <?= sanitize($u['first_name'].' '.$u['last_name']) ?>
              </span>
            </div>
          </td>
          <td style="vertical-align:middle;">
            <div style="font-size:.84rem;word-break:break-word;">
              <?= sanitize($u['email']) ?>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:.15rem;">
              <?= sanitize($u['phone']??'—') ?>
            </div>
          </td>
          <td style="vertical-align:middle;">
            <span class="badge badge-<?= $u['role_name']==='pet_owner'?'primary':($u['role_name']==='veterinarian'?'success':($u['role_name']==='groomer'?'warning':'danger')) ?>">
              <?= displayRoleName($u['role_name'], $u['clinic_type'] ?? null) ?>
            </span>
          </td>
          <td style="vertical-align:middle;">
            <?= $u['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?>
            <?php if (!empty($u['inactivity_deactivated_at'])): ?>
              <br><span class="badge badge-secondary" style="font-size:.7rem;margin-top:.2rem;"><i class="fas fa-user-slash"></i> Auto-deactivated</span>
            <?php endif; ?>
            <?php if (!empty($u['is_booking_restricted'])): ?>
              <br><span class="badge badge-danger" style="font-size:.7rem;margin-top:.2rem;"><i class="fas fa-ban"></i> Booking restricted</span>
            <?php endif; ?>
          </td>
          <td style="vertical-align:middle;font-size:.82rem;">
            <?= date('M j, Y',strtotime($u['created_at'])) ?>
          </td>
          <td style="vertical-align:middle;">
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
              <?php if (!$u['is_active'] && !empty($u['id_document_path']) && empty($u['inactivity_deactivated_at']) && $u['id'] != $user['id']): ?>
                <!-- Pending account — show approve/reject -->
                <a href="?approve=<?= $u['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-success"
                   data-confirm="Approve this account? User will be notified by email.">
                  <i class="fas fa-check"></i> Approve
                </a>
                <button type="button" class="btn btn-sm btn-danger"
                  onclick="rejectUser(<?= $u['id'] ?>)">
                  <i class="fas fa-times"></i> Reject
                </button>
              <?php elseif (!$u['is_active'] && !empty($u['inactivity_deactivated_at']) && $u['id'] != $user['id']): ?>
                <!-- Auto-deactivated — show reactivate -->
                <a href="?reactivate=<?= $u['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-success"
                   data-confirm="Reactivate this account? User will be notified by email.">
                  <i class="fas fa-user-check"></i> Reactivate
                </a>
              <?php elseif ($u['id'] != $user['id']): ?>
                <a href="?toggle=<?= $u['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm <?= $u['is_active']?'btn-danger':'btn-secondary' ?>"
                   data-confirm="<?= $u['is_active']?'Deactivate':'Activate' ?> this user?">
                  <?= $u['is_active']?'Disable':'Enable' ?>
                </a>
              <?php endif; ?>
              <?php if (!empty($u['is_booking_restricted']) && $u['id'] != $user['id']): ?>
                <a href="?lift_restriction=<?= $u['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                   class="btn btn-sm btn-outline"
                   style="border-color:var(--warning);color:var(--warning);"
                   data-confirm="Lift booking restriction for this user? Their no-show count will also be reset.">
                  <i class="fas fa-unlock"></i> Lift Restriction
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php renderPagination($pg); ?>
</main>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal">
    <div class="modal-header"><h3>Add New User</h3><button class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button></div>
    <div class="modal-body">
      <form method="POST">
        <?= csrfField() ?><input type="hidden" name="add_user" value="1">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input type="text" name="first_name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Last Name <span class="required">*</span></label><input type="text" name="last_name" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Role <span class="required">*</span></label><select name="role_id" class="form-control" required><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= ucfirst(str_replace('_',' ',$r['name'])) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Password</label><input type="text" name="password" class="form-control" value="Admin@1234" placeholder="Default: Admin@1234"></div>
        </div>
        <div class="modal-footer" style="padding:0;border:none;margin-top:1rem;justify-content:flex-end;display:flex;gap:.75rem;">
          <button type="button" onclick="closeModal('addUserModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-overlay" id="reviewIDModal">
  <div class="modal" style="max-width:680px;width:95%;">
    <div class="modal-header">
      <h3><i class="fas fa-id-card" style="color:var(--primary);"></i> ID Verification Review</h3>
      <button class="modal-close" onclick="closeModal('reviewIDModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">

      <!-- User info -->
      <div style="background:var(--bg);border-radius:10px;padding:1rem 1.1rem;margin-bottom:1.25rem;">
        <div class="modal-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
          <div>
            <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .2rem;">Full Name</p>
            <p style="font-weight:700;margin:0;word-break:break-word;" id="rv_name">—</p>
          </div>
          <div>
            <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .2rem;">Email</p>
            <p style="font-weight:600;margin:0;font-size:.875rem;word-break:break-all;" id="rv_email">—</p>
          </div>
        </div>
        <div class="modal-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
          <div>
            <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .2rem;">ID Type</p>
            <p style="font-weight:700;margin:0;color:var(--primary);" id="rv_type">—</p>
          </div>
          <div>
            <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .2rem;">ID Number</p>
            <p style="font-weight:700;margin:0;font-size:1rem;letter-spacing:.05em;word-break:break-all;" id="rv_number">—</p>
          </div>
        </div>
      </div>

      <!-- ID Photos -->
      <p style="font-size:.8rem;font-weight:700;color:var(--text-muted);margin-bottom:.6rem;text-transform:uppercase;letter-spacing:.05em;">
        <i class="fas fa-images"></i> Submitted ID Photos
      </p>
      <div class="modal-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem;">
        <div>
          <p style="font-size:.78rem;font-weight:600;margin-bottom:.35rem;"><i class="fas fa-id-card" style="color:var(--primary);"></i> Front Side</p>
          <a id="rv_front_link" href="#" target="_blank">
            <img id="rv_front_img" src="" alt="ID Front"
              onerror="this.style.display='none';document.getElementById('rv_front_err').style.display='flex';"
              style="width:100%;border-radius:8px;border:2px solid var(--border);object-fit:contain;max-height:200px;background:#000;cursor:zoom-in;display:none;">
          </a>
          <div id="rv_front_err" style="display:none;align-items:center;justify-content:center;height:120px;border:2px dashed var(--border);border-radius:8px;color:var(--text-muted);font-size:.8rem;flex-direction:column;gap:.4rem;">
            <i class="fas fa-image" style="font-size:1.5rem;opacity:.4;"></i> Image not available
          </div>
          <p style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;text-align:center;">Click to open full size</p>
        </div>
        <div>
          <p style="font-size:.78rem;font-weight:600;margin-bottom:.35rem;"><i class="fas fa-id-card" style="color:var(--secondary);"></i> Back Side</p>
          <a id="rv_back_link" href="#" target="_blank">
            <img id="rv_back_img" src="" alt="ID Back"
              onerror="this.style.display='none';document.getElementById('rv_back_err').style.display='flex';"
              style="width:100%;border-radius:8px;border:2px solid var(--border);object-fit:contain;max-height:200px;background:#000;cursor:zoom-in;display:none;">
          </a>
          <div id="rv_back_err" style="display:none;align-items:center;justify-content:center;height:120px;border:2px dashed var(--border);border-radius:8px;color:var(--text-muted);font-size:.8rem;flex-direction:column;gap:.4rem;">
            <i class="fas fa-image" style="font-size:1.5rem;opacity:.4;"></i> Image not available
          </div>
          <p style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;text-align:center;">Click to open full size</p>
        </div>
      </div>

      <!-- Checklist hint -->
      <div style="background:rgba(30,64,255,.05);border:1px solid rgba(30,64,255,.15);border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.8rem;color:var(--text-muted);">
        <strong style="color:var(--primary);">Verify that:</strong>
        <ul style="margin:.4rem 0 0;padding-left:1.2rem;line-height:2;">
          <li>The <strong>full name</strong> on the ID matches the registered name</li>
          <li>The <strong>ID number</strong> entered matches what's visible on the photo</li>
          <li>The ID photo is <strong>clear and readable</strong></li>
          <li>The document is a valid <strong>government-issued ID</strong></li>
        </ul>
      </div>

      <!-- Action buttons -->
      <div style="display:flex;gap:.75rem;justify-content:flex-end;">
        <button type="button" onclick="typeof closeModal==='function'?closeModal('reviewIDModal'):_closeModal('reviewIDModal')" class="btn btn-outline">Close</button>
        <button type="button" id="rv_reject_btn" onclick="(typeof closeModal==='function'?closeModal('reviewIDModal'):_closeModal('reviewIDModal'));rejectUser(currentReviewUid)" class="btn btn-danger">
          <i class="fas fa-times-circle"></i> Reject
        </button>
        <a id="rv_approve_link" href="#" class="btn btn-success">
          <i class="fas fa-check-circle"></i> Approve
        </a>
      </div>

    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Account</h3>
      <button class="modal-close" onclick="closeModal('rejectModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem;">
        The user will be notified by email with your rejection reason.
      </p>
      <div class="form-group">
        <label class="form-label">Rejection Reason</label>
        <select id="rejectReasonSelect" class="form-control" onchange="updateReason(this)">
          <option value="Your submitted ID photo is unclear or unreadable. Please resubmit with a clearer photo.">ID photo is unclear/unreadable</option>
          <option value="The ID number you entered does not match the information visible in your ID photo.">ID number does not match photo</option>
          <option value="The name on your ID does not match your registration details.">Name does not match</option>
          <option value="The uploaded document does not appear to be a valid government-issued ID.">Not a valid government ID</option>
          <option value="custom">Other (type custom reason)</option>
        </select>
      </div>
      <div class="form-group" id="customReasonGroup" style="display:none;">
        <label class="form-label">Custom Reason</label>
        <textarea id="customReason" class="form-control" rows="3" placeholder="Enter rejection reason..."></textarea>
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem;">
        <button type="button" onclick="closeModal('rejectModal')" class="btn btn-outline">Cancel</button>
        <button type="button" onclick="confirmReject()" class="btn btn-danger">
          <i class="fas fa-times-circle"></i> Reject Account
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
let rejectUid = null;
let currentReviewUid = null;
const rejectCsrf = '<?= generateCsrfToken() ?>';

const idTypeLabels = {
  passport: 'Philippine Passport',
  drivers_license: "Driver's License",
  national_id: 'PhilSys National ID',
  umid: 'UMID Card',
  sss: 'SSS ID',
  philhealth: 'PhilHealth ID',
  voters_id: "Voter's ID",
  prc: 'PRC License'
};

// Inline fallback — in case main.js hasn't loaded yet
function _openModal(id)  { const el = document.getElementById(id); if(el) el.classList.add('open'); }
function _closeModal(id) { const el = document.getElementById(id); if(el) el.classList.remove('open'); }

// Review ID — use event delegation on data-* attributes
document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.review-id-btn');
    if (!btn) return;
    const d = btn.dataset;
    reviewID(d.uid, d.name, d.email, d.idtype, d.idnum, d.front, d.back);
  });
});

function reviewID(uid, name, email, idType, idNum, frontUrl, backUrl) {
  currentReviewUid = uid;
  document.getElementById('rv_name').textContent   = name   || '—';
  document.getElementById('rv_email').textContent  = email  || '—';
  document.getElementById('rv_type').textContent   = idTypeLabels[idType] || idType || '—';
  document.getElementById('rv_number').textContent = idNum  || '—';

  const frontImg  = document.getElementById('rv_front_img');
  const frontLink = document.getElementById('rv_front_link');
  const frontErr  = document.getElementById('rv_front_err');
  const backImg   = document.getElementById('rv_back_img');
  const backLink  = document.getElementById('rv_back_link');
  const backErr   = document.getElementById('rv_back_err');

  // Reset error states
  frontErr.style.display = 'none';
  backErr.style.display  = 'none';

  if (frontUrl) {
    frontImg.src           = frontUrl;
    frontLink.href         = frontUrl;
    frontImg.style.display = 'block';
  } else {
    frontImg.style.display = 'none';
    frontErr.style.display = 'flex';
  }

  if (backUrl) {
    backImg.src           = backUrl;
    backLink.href         = backUrl;
    backImg.style.display = 'block';
  } else {
    backImg.style.display = 'none';
    backErr.style.display = 'flex';
  }

  document.getElementById('rv_approve_link').href =
    'users.php?approve=' + uid + '&csrf=' + rejectCsrf;

  if (typeof openModal === 'function') openModal('reviewIDModal');
  else _openModal('reviewIDModal');
}

function rejectUser(uid) {
  rejectUid = uid;
  if (typeof openModal === 'function') openModal('rejectModal');
  else _openModal('rejectModal');
}
function updateReason(sel) {
  document.getElementById('customReasonGroup').style.display = sel.value === 'custom' ? 'block' : 'none';
}
function confirmReject() {
  const sel = document.getElementById('rejectReasonSelect');
  const reason = sel.value === 'custom'
    ? document.getElementById('customReason').value.trim()
    : sel.value;
  if (!reason) { alert('Please enter a rejection reason.'); return; }
  window.location = 'users.php?reject=' + rejectUid + '&csrf=' + rejectCsrf + '&reason=' + encodeURIComponent(reason);
}
</script>

<?php
// ── Email: account reactivated by admin ──────────────────
function accountReactivatedEmailHtml(string $firstName): string {
    $loginUrl = APP_URL . '/auth/login.php';
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#1E40FF,#00D4AA);padding:36px;text-align:center;">
            <div style="font-size:2.5rem;">🎉</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">Account Reactivated</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              Great news! Your PetCare+ account has been <strong>reactivated</strong> by our Clinic Admin team.
              You can now log in and continue managing your pet's appointments.
            </p>
            <div style="text-align:center;margin:24px 0;">
              <a href="{$loginUrl}"
                 style="background:linear-gradient(135deg,#1E40FF,#00D4AA);color:#fff;
                        text-decoration:none;padding:14px 40px;border-radius:50px;
                        font-weight:700;font-size:1rem;display:inline-block;">
                🐾 Log In Now
              </a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:20px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.78rem;color:#94A3B8;margin:0;">&copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}

// ── Email: booking restriction lifted ────────────────────
function bookingRestoredEmailHtml(string $firstName): string {
    $bookUrl = APP_URL . '/dashboard/owner/book.php';
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#10B981,#1E40FF);padding:36px;text-align:center;">
            <div style="font-size:2.5rem;">✅</div>
            <h1 style="color:#fff;font-size:1.4rem;margin:12px 0 4px;">PetCare+ Clinic Hub</h1>
            <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0;">Booking Access Restored</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px;">
            <p style="font-size:1rem;color:#1E293B;margin-bottom:.5rem;">Hi <strong>{$firstName}</strong> 👋</p>
            <p style="font-size:.9rem;color:#64748B;line-height:1.8;margin-bottom:1.5rem;">
              Your booking access on PetCare+ has been <strong>fully restored</strong> by our Clinic Admin team.
              You can now make new appointments for your pets again.
            </p>
            <div style="background:#ECFDF5;border-left:4px solid #10B981;border-radius:8px;
                        padding:12px 16px;font-size:.85rem;color:#065F46;margin-bottom:1.5rem;">
              💡 Please remember to cancel appointments at least 24 hours in advance
              if you're unable to attend, to avoid future restrictions.
            </div>
            <div style="text-align:center;margin:24px 0;">
              <a href="{$bookUrl}"
                 style="background:linear-gradient(135deg,#10B981,#1E40FF);color:#fff;
                        text-decoration:none;padding:14px 40px;border-radius:50px;
                        font-weight:700;font-size:1rem;display:inline-block;">
                🐾 Book an Appointment
              </a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#F8FAFC;padding:20px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="font-size:.78rem;color:#94A3B8;margin:0;">&copy; 2026 PetCare+ Clinic Hub · Pangasinan, Philippines</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
?>
