<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['pet_owner']);
$user = currentUser();
$pageTitle = 'AI Health Insights';
$isDashboard = true;
$db = getDB();

$prePet = (int)($_GET['pet'] ?? 0);

$petsStmt = $db->prepare('SELECT * FROM pets WHERE owner_id=? AND is_active=1 ORDER BY name');
$petsStmt->execute([$user['id']]); $myPets = $petsStmt->fetchAll();

$result = ''; $resultType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $result='Invalid request.'; $resultType='danger'; }
    else {
        $petId  = (int)($_POST['pet_id'] ?? 0);
        $aiType = sanitize($_POST['ai_type'] ?? 'veterinary'); // veterinary | grooming | summary

        $petStmt = $db->prepare('SELECT * FROM pets WHERE id=? AND owner_id=?');
        $petStmt->execute([$petId, $user['id']]); $pet = $petStmt->fetch();

        if (!$pet) { $result='Invalid pet selected.'; $resultType='danger'; }
        else {
            // Gather health context
            $records = $db->prepare('SELECT title,diagnosis,treatment,record_type,record_date FROM pet_health_records WHERE pet_id=? ORDER BY record_date DESC LIMIT 5');
            $records->execute([$petId]); $records = $records->fetchAll();
            $healthCtx = implode('; ', array_map(fn($r)=>"{$r['record_type']} on {$r['record_date']}: {$r['title']}".($r['diagnosis']?" — {$r['diagnosis']}":''), $records));

            // Build prompt with professional veterinary guidelines
            $petInfo = "Pet: {$pet['name']}, Species: {$pet['species']}, Breed: {$pet['breed']}, Age: {$pet['age_years']}y {$pet['age_months']}m, Gender: {$pet['gender']}, Weight: {$pet['weight_kg']}kg";
            
            if ($aiType === 'veterinary') {
                $prompt = "You are a professional veterinary AI assistant. Based on the following pet information and medical history, provide 5 specific preventive care recommendations.

Pet Information:
{$petInfo}

Recent Health Records:
{$healthCtx}

Please provide:
1. Preventive care recommendations
2. Suggested checkups based on age and breed
3. Health monitoring advice specific to this breed
4. Any age-related considerations
5. General wellness tips

IMPORTANT: Do not use bold formatting or asterisks. Use plain text with clear headings and bullet points using \"-\". Keep your response clear, professional, and easy to understand for a pet owner.";
            } elseif ($aiType === 'grooming') {
                $prompt = "You are a professional pet grooming consultant. Based on the following pet information, provide specific grooming recommendations.

Pet Information:
{$petInfo}

Please provide:
1. Recommended grooming frequency for this breed
2. Specific coat care tips and techniques
3. Recommended grooming services
4. Seasonal grooming considerations
5. At-home maintenance tips

IMPORTANT: Do not use bold formatting or asterisks. Use plain text with clear headings and bullet points using \"-\". Be practical and easy to follow.";
            } else {
                $prompt = "You are a veterinary medical assistant. Generate a concise medical summary (3-4 sentences) for this pet.

Pet Information:
{$petInfo}

Recent Health Records:
{$healthCtx}

Provide a brief, professional summary of the pet's current health status, any notable conditions, and general health outlook. Do not use bold formatting or asterisks. Use plain text only.";
            }

            // Call Groq
            $ai = callGroq($prompt, 600);
            if ($ai['success']) {
                $aiText = $ai['text'];
                $result = $aiText; $resultType = 'success';

                // Save to DB
                if ($aiType === 'summary') {
                    $db->prepare('INSERT INTO ai_summaries (pet_id,generated_by,summary,prompt_used) VALUES (?,?,?,?)')
                       ->execute([$petId,$user['id'],$aiText,$prompt]);
                } else {
                    $db->prepare('INSERT INTO ai_recommendations (pet_id,generated_by,recommendation_type,recommendation,prompt_used) VALUES (?,?,?,?,?)')
                       ->execute([$petId,$user['id'],$aiType,$aiText,$prompt]);
                }
            } else {
                $result = $ai['text'];
                $resultType = 'warning';
            }
        }
    }
}

// Past recommendations
$pastRecs = [];
if ($prePet) {
    $ps = $db->prepare('SELECT * FROM ai_recommendations WHERE pet_id=? ORDER BY created_at DESC LIMIT 6');
    $ps->execute([$prePet]); $pastRecs = $ps->fetchAll();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_owner.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb"><a href="index.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>AI Insights</span></div>
    <h1><i class="fas fa-robot" style="color:var(--primary)"></i> AI Health Insights</h1>
    <p>Get personalised veterinary and grooming recommendations powered by Groq · Llama 3.3.</p>
  </div>

  <div class="dash-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Generate Form -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-magic" style="color:var(--primary);margin-right:.5rem"></i>Generate Insights</h3></div>
      <div class="card-body">
        <?php if (empty($myPets)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <a href="pets.php?action=add">Add a pet first</a> to generate AI insights.</div>
        <?php else: ?>
        <form method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">Select Pet <span class="required">*</span></label>
            <select name="pet_id" class="form-control" required>
              <option value="">— Choose pet —</option>
              <?php foreach ($myPets as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $prePet==$p['id']?'selected':'' ?>><?= sanitize($p['name']) ?> (<?= sanitize($p['breed']?:$p['species']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Insight Type <span class="required">*</span></label>
            <div class="insight-type-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;">
              <?php $types=[['veterinary','fa-stethoscope','Vet Recommendations'],['grooming','fa-cut','Grooming Tips'],['summary','fa-file-alt','Medical Summary']]; ?>
              <?php foreach ($types as [$val,$icon,$label]): ?>
              <label style="cursor:pointer;text-align:center;">
                <input type="radio" name="ai_type" value="<?= $val ?>" style="display:none;" class="ai-type-radio">
                <div class="ai-type-card" style="border:2px solid var(--border);border-radius:var(--radius-sm);padding:1rem .75rem;transition:all .2s;">
                  <i class="fas <?= $icon ?>" style="font-size:1.5rem;color:var(--text-muted);display:block;margin-bottom:.4rem;"></i>
                  <span style="font-size:.8rem;font-weight:600;"><?= $label ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-robot"></i> Generate with AI</button>
          <p style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:.75rem;">Powered by Groq · Llama 3.3 · Results are informational only</p>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Result -->
    <div class="card">
      <div class="card-header"><h3><i class="fas fa-lightbulb" style="color:var(--warning);margin-right:.5rem"></i>AI Result</h3></div>
      <div class="card-body">
        <?php if ($result): ?>
        <div class="alert alert-<?= $resultType === 'success' ? 'info' : $resultType ?>">
          <i class="fas fa-<?= $resultType==='success'?'robot':'exclamation-triangle' ?>"></i>
          <div style="white-space:pre-wrap;font-size:.9rem;line-height:1.8;"><?= sanitize($result) ?></div>
        </div>
        <?php if ($resultType === 'success'): ?>
        <div style="display:flex;align-items:center;gap:.5rem;margin-top:.75rem;padding:.6rem 1rem;background:rgba(16,185,129,.1);border-radius:var(--radius-sm);border:1px solid rgba(16,185,129,.3);">
          <i class="fas fa-check-circle" style="color:var(--success);"></i>
          <span style="font-size:.82rem;color:var(--success);font-weight:600;">AI recommendation saved to your records successfully!</span>
        </div>
        <?php endif; ?>
        <?php elseif ($pastRecs): ?>
        <div>
          <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:1rem;">Previous recommendations:</p>
          <?php foreach (array_slice($pastRecs,0,2) as $rec): ?>
          <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;margin-bottom:.75rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
              <span class="badge badge-<?= $rec['recommendation_type']==='veterinary'?'primary':'success' ?>"><?= ucfirst($rec['recommendation_type']) ?></span>
              <span style="font-size:.75rem;color:var(--text-muted)"><?= timeAgo($rec['created_at']) ?></span>
            </div>
            <p style="font-size:.875rem;line-height:1.7;"><?= sanitize(substr($rec['recommendation'],0,300)) ?>…</p>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:3rem 1rem;">
          <i class="fas fa-robot"></i>
          <h3>No insights yet</h3>
          <p>Select a pet and insight type, then click Generate.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- All Past Recommendations -->
  <?php if ($prePet && $pastRecs): ?>
  <div class="card mt-4">
    <div class="card-header"><h3>Past AI Recommendations</h3></div>
    <div class="card-body">
      <div style="display:grid;gap:1rem;">
        <?php foreach ($pastRecs as $rec): ?>
        <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:1.25rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
            <span class="badge badge-<?= $rec['recommendation_type']==='veterinary'?'primary':($rec['recommendation_type']==='grooming'?'success':'info') ?>"><?= ucfirst($rec['recommendation_type']) ?></span>
            <span style="font-size:.75rem;color:var(--text-muted)"><?= date('M j, Y g:i A',strtotime($rec['created_at'])) ?></span>
          </div>
          <p style="font-size:.875rem;line-height:1.8;white-space:pre-wrap;"><?= sanitize($rec['recommendation']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<style>
input.ai-type-radio:checked + .ai-type-card { border-color:var(--primary);background:rgba(30,64,255,.06); }
input.ai-type-radio:checked + .ai-type-card i { color:var(--primary); }
.ai-type-card:hover { border-color:var(--primary); }
</style>
<script>
document.querySelectorAll('.ai-type-radio').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('.ai-type-card').forEach(c=>{ c.style.borderColor='var(--border)'; c.querySelector('i').style.color='var(--text-muted)'; });
    r.nextElementSibling.style.borderColor='var(--primary)';
    r.nextElementSibling.querySelector('i').style.color='var(--primary)';
  });
});
</script>
