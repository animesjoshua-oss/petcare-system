<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['veterinarian']);
$user = currentUser();
$pageTitle = 'AI Assistant';
$isDashboard = true;

// Handle AJAX chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    header('Content-Type: application/json');
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'text' => 'Invalid request.']);
        exit;
    }
    $message = trim(sanitize($_POST['chat_message'] ?? ''));
    if (!$message) {
        echo json_encode(['success' => false, 'text' => 'Please enter a message.']);
        exit;
    }

    $systemPrompt = "You are VetAI, an expert veterinary clinical assistant. You help veterinarians with:
- Differential diagnoses and clinical reasoning
- Treatment protocols and drug dosages
- Interpreting lab results and imaging findings
- Preventive care recommendations
- Drug interactions and contraindications
- Breed-specific health concerns
- Emergency triage guidance

Always remind users that AI recommendations should be verified with clinical judgment. Be concise, professional, and use proper medical terminology. Format responses with bullet points or numbered lists when appropriate.";

    $ai = callGroq("{$systemPrompt}\n\nVeterinarian asks: {$message}", 800);
    echo json_encode(['success' => $ai['success'], 'text' => $ai['text']]);
    exit;
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_vet.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>AI Assistant</span>
    </div>
    <h1><i class="fas fa-robot" style="color:var(--primary)"></i> VetAI Clinical Assistant</h1>
    <p>Ask anything about veterinary medicine — diagnoses, treatments, drug info, and more.</p>
  </div>

  <?php
  $csrf             = generateCsrfToken();
  $aiUserIcon       = 'fa-user-md';
  $aiChatPlaceholder = "Ask a clinical question... e.g. 'What are the differential diagnoses for a 3-year-old dog with sudden weight loss?'";
  $aiQuickPrompts   = [
    'Common signs of parvovirus',
    'Feline hyperthyroidism treatment',
    'Safe NSAID dosage for dogs',
    'Canine diabetes management',
    'Signs of GDV emergency',
  ];
  $aiWelcomeHtml = '
    <p>👋 Hello Dr. <strong>'.sanitize($user['first_name']).'</strong>! I\'m <strong>VetAI</strong>, your clinical assistant.</p>
    <p style="margin-top:.5rem;">I can help you with:</p>
    <ul style="margin:.4rem 0 0 1.2rem;font-size:.875rem;">
      <li>Differential diagnoses</li>
      <li>Treatment protocols &amp; drug dosages</li>
      <li>Lab result interpretation</li>
      <li>Breed-specific health concerns</li>
      <li>Emergency triage guidance</li>
    </ul>
    <p style="margin-top:.75rem;font-size:.8rem;color:var(--text-muted);">⚠️ Always verify AI suggestions with your clinical judgment.</p>';
  include __DIR__ . '/../../includes/ai_chat_ui.php';
  ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
