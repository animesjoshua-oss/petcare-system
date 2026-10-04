<?php
require_once __DIR__ . '/includes/config.php';
http_response_code(500);
$homeUrl = APP_URL . '/index.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Server Error | PetCare+</title>
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#101116;color:#f7f8fc;font-family:Arial,sans-serif}.card{width:min(560px,100%);padding:48px 36px;text-align:center;background:#1d202b;border:1px solid #303440;border-radius:22px;box-shadow:0 24px 60px #0005}.brand{color:#9eaeff;font-size:1.2rem;font-weight:700;letter-spacing:.02em}.code{margin:30px 0 8px;color:#6576ff;font-size:clamp(4rem,18vw,7rem);line-height:1;font-weight:800}.icon{font-size:2rem}h1{margin:12px 0;font-size:1.65rem}p{margin:0 auto 28px;max-width:390px;color:#c2c6d2;line-height:1.7}.button{display:inline-flex;align-items:center;justify-content:center;padding:13px 23px;border-radius:999px;background:#5865e8;color:white;text-decoration:none;font-weight:700}.button:hover{background:#4854d3}
  </style>
</head>
<body><main class="card"><div class="brand">🐾 PetCare+</div><div class="code">500</div><div class="icon">⚠️</div><h1>Something went wrong</h1><p>PetCare+ ran into a server error. Please try again in a moment, or return to the home page.</p><a class="button" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Return to Home</a></main></body>
</html>
