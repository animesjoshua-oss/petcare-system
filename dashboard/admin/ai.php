<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin(); requireRole(['admin', 'super_admin']);
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

    $systemPrompt = "You are Clinic AdminAI, an intelligent assistant for PetCare+ Clinic Hub administrators. You help with:
- Clinic management and operations advice
- Veterinary staff management guidance
- Appointment scheduling best practices
- Pet care business insights and analytics interpretation
- Customer service and pet owner communication tips
- Health and safety protocols for veterinary clinics
- General veterinary knowledge for administrative context

Be professional, concise, and helpful. Format responses clearly with bullet points when listing items.";

    $ai = callGroq("{$systemPrompt}\n\nClinic Admin asks: {$message}", 800);
    echo json_encode(['success' => $ai['success'], 'text' => $ai['text']]);
    exit;
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<main class="dash-main">
  <div class="dash-header">
    <div class="dash-breadcrumb">
      <a href="index.php">Dashboard</a>
      <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
      <span>AI Assistant</span>
    </div>
    <h1><i class="fas fa-robot" style="color:var(--primary)"></i> Clinic AdminAI Assistant</h1>
    <p>Your intelligent assistant for clinic management and veterinary operations.</p>
  </div>

  <?php
  $csrf             = generateCsrfToken();
  $aiUserIcon       = 'fa-user-shield';
  $aiChatPlaceholder = 'Ask anything about clinic management...';
  $aiQuickPrompts   = [
    'How to reduce no-shows',
    'Best practices for vet clinics',
    'How to handle pet owner complaints',
    'Clinic scheduling tips',
    'Staff training recommendations',
  ];
  $aiWelcomeHtml = '
    <p>👋 Hello, <strong>'.sanitize($user['first_name']).'</strong>! I\'m <strong>Clinic AdminAI</strong>, your clinic management assistant.</p>
    <p style="margin-top:.5rem;">I can help you with:</p>
    <ul style="margin:.4rem 0 0 1.2rem;font-size:.875rem;">
      <li>Clinic operations and management advice</li>
      <li>Staff management best practices</li>
      <li>Pet care business insights</li>
      <li>Customer service guidance</li>
      <li>Health &amp; safety protocols</li>
    </ul>';
  include __DIR__ . '/../../includes/ai_chat_ui.php';
  ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
