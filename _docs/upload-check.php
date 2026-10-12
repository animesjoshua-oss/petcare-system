<?php
/**
 * PetCare+ Photo Sync Diagnostic — DELETE AFTER USE
 * Visit: https://petcareclinic.yzz.me/upload-check.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// ── Files physically on disk ──────────────────────────────────────────────
$diskFiles = [];
$petsDir   = rtrim(UPLOAD_DIR, '/\\') . '/pets/';
foreach (glob($petsDir . '*') ?: [] as $f) {
    $diskFiles[] = basename($f);
}

// ── Photo paths stored in DB ──────────────────────────────────────────────
$rows = $db->query('SELECT id, name, owner_id, photo FROM pets WHERE is_active=1 AND photo IS NOT NULL')->fetchAll();

// ── Analyse each DB row ───────────────────────────────────────────────────
$issues = [];
$ok     = [];

foreach ($rows as $row) {
    $raw  = $row['photo'];
    $url  = assetUrl($raw);
    $base = basename(parse_url($raw, PHP_URL_PATH) ?? $raw);

    $onDisk = in_array($base, $diskFiles, true);

    if (!$onDisk) {
        $issues[] = [
            'pet_id'   => $row['id'],
            'name'     => $row['name'],
            'db_path'  => $raw,
            'resolved' => $url,
            'filename' => $base,
            'fix'      => 'clear',   // will NULL the photo column
        ];
    } else {
        $ok[] = ['pet_id' => $row['id'], 'name' => $row['name'], 'filename' => $base, 'url' => $url];
    }
}

// ── Apply fix: NULL out DB rows whose file doesn't exist ─────────────────
$fixed = 0;
if (isset($_GET['fix'])) {
    foreach ($issues as $i) {
        $db->prepare('UPDATE pets SET photo=NULL WHERE id=?')->execute([$i['pet_id']]);
        $fixed++;
    }
    $issues = []; // re-fetch would show them gone
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Photo Sync Check</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:monospace;padding:2rem;background:#0f172a;color:#e2e8f0;font-size:.88rem}
  h2{color:#818cf8;margin-bottom:1.5rem}h3{color:#94a3b8;margin:1.5rem 0 .75rem}
  table{width:100%;border-collapse:collapse;margin-bottom:1rem}
  th{text-align:left;color:#94a3b8;padding:.35rem .6rem;border-bottom:1px solid #1e293b}
  td{padding:.4rem .6rem;border-bottom:1px solid #1e293b;vertical-align:top;word-break:break-all}
  .ok{color:#4ade80}.bad{color:#f87171}.warn{color:#fbbf24}
  .btn{display:inline-block;margin-top:1.25rem;padding:.6rem 1.4rem;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:700}
  .note{margin-top:2rem;color:#f59e0b;font-size:.82rem;line-height:1.8}
</style>
</head>
<body>
<h2>🔍 PetCare+ Photo Sync Diagnostic</h2>

<p>UPLOAD_DIR: <span style="color:#94a3b8"><?= htmlspecialchars($petsDir) ?></span></p>
<p>Files on disk: <strong class="ok"><?= count($diskFiles) ?></strong> — 
   <?= implode(', ', array_map('htmlspecialchars', $diskFiles)) ?: '<em>none</em>' ?></p>

<?php if ($fixed): ?>
<p class="ok" style="margin-top:1rem">✅ Fixed <?= $fixed ?> broken photo reference(s) — photos cleared from DB. Customers can re-upload.</p>
<?php endif; ?>

<?php if ($ok): ?>
<h3 class="ok">✅ OK — <?= count($ok) ?> pet(s) with valid photos</h3>
<table>
  <tr><th>Pet ID</th><th>Name</th><th>Filename</th><th>URL</th></tr>
  <?php foreach ($ok as $r): ?>
  <tr>
    <td><?= $r['pet_id'] ?></td>
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td class="ok"><?= htmlspecialchars($r['filename']) ?></td>
    <td><a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" style="color:#818cf8"><?= htmlspecialchars($r['url']) ?></a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($issues): ?>
<h3 class="bad">❌ Broken — <?= count($issues) ?> pet(s) with missing photo files</h3>
<table>
  <tr><th>Pet ID</th><th>Name</th><th>DB path</th><th>Filename expected</th></tr>
  <?php foreach ($issues as $i): ?>
  <tr>
    <td><?= $i['pet_id'] ?></td>
    <td><?= htmlspecialchars($i['name']) ?></td>
    <td class="bad"><?= htmlspecialchars($i['db_path']) ?></td>
    <td class="bad"><?= htmlspecialchars($i['filename']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<a class="btn" href="?fix=1">🔧 Fix: Clear broken photo references in DB</a>
<p style="color:#94a3b8;margin-top:.5rem;font-size:.8rem">This sets photo=NULL for those pets so the placeholder shows instead of a broken image. Customers can then re-upload their photo.</p>
<?php elseif (!$fixed): ?>
<p class="ok" style="margin-top:1rem">✅ All photo paths in the DB match files on disk. No issues found.</p>
<?php endif; ?>

<div class="note">
  ⚠️ Delete this file after use: <code>upload-check.php</code>
</div>
</body>
</html>
