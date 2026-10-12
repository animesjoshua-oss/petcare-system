<?php
// ============================================================
// PetCare+ – Shared Helper Functions
// ============================================================
require_once __DIR__ . '/db.php';

// Load component helpers
require_once __DIR__ . '/stat_widgets.php';
require_once __DIR__ . '/pet_card.php';
require_once __DIR__ . '/clinic_card.php';
require_once __DIR__ . '/empty_state.php';

// ---- Security Helpers ----

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function generateToken(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

// ---- Auth Helpers ----

function isLoggedIn(): bool {
    if (empty($_SESSION['user_id'])) return false;
    // Verify user still exists and is active
    try {
        $stmt = getDB()->prepare('SELECT id FROM users WHERE id=? AND is_active=1');
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            session_unset();
            return false;
        }
    } catch (\Exception $e) {
        // DB error — trust the session
    }
    return true;
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

function requireRole(array $roles): void {
    $user = currentUser();
    if (!$user || !in_array($user['role_name'], $roles, true)) {
        header('Location: ' . APP_URL . '/auth/unauthorized.php');
        exit;
    }
}

function redirectByRole(): void {
    $user = currentUser();
    if (!$user) {
        // Clear broken session and send to login cleanly
        session_unset();
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
    switch ($user['role_name']) {
        case 'pet_owner':    header('Location: ' . APP_URL . '/dashboard/owner/index.php');   break;
        case 'veterinarian': header('Location: ' . APP_URL . '/dashboard/vet/index.php');     break;
        case 'groomer':      header('Location: ' . APP_URL . '/dashboard/groomer/index.php'); break;
        case 'admin':
        case 'superadmin':   header('Location: ' . APP_URL . '/dashboard/admin/index.php');   break;
        default:
            session_unset();
            header('Location: ' . APP_URL . '/auth/login.php');
    }
    exit;
}

function displayRoleName(string $roleName): string {
    return match($roleName) {
        'admin'        => 'Clinic Admin',
        'superadmin'   => 'Superadmin',
        'pet_owner'    => 'Pet Owner',
        'veterinarian' => 'Veterinarian',
        'groomer'      => 'Groomer',
        default        => ucfirst(str_replace('_', ' ', $roleName))
    };
}

// ---- File Upload ----

function uploadFile(array $file, string $subdir = 'misc'): ?string {
    // Server-side MIME validation (not browser-supplied Content-Type)
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($detectedMime, $allowedMime, true)) {
        error_log("uploadFile: rejected MIME '$detectedMime' for file '{$file['name']}'");
        return null;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("uploadFile: file too large ({$file['size']} bytes) for '{$file['name']}'");
        return null;
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('', true) . '.' . $ext;
    $dir      = rtrim(UPLOAD_DIR, '/\\') . '/' . $subdir . '/';

    // Create directory if missing
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("uploadFile: mkdir failed for '$dir' — check server permissions");
            return null;
        }
    }

    // Ensure directory is writable
    if (!is_writable($dir)) {
        // Try to fix permissions once
        @chmod($dir, 0755);
        if (!is_writable($dir)) {
            error_log("uploadFile: directory not writable '$dir' — chmod failed");
            return null;
        }
    }

    $dest = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // Store root-relative path — works on any domain / environment
        return '/uploads/' . $subdir . '/' . $filename;
    }

    error_log("uploadFile: move_uploaded_file failed. tmp='{$file['tmp_name']}' dest='$dest'");
    return null;
}

/**
 * Resolve a stored photo path to a fully-qualified URL.
 * Handles three formats:
 *   1. Already a full URL  → returned as-is   (legacy rows uploaded before this fix)
 *   2. Root-relative path  → prepended with APP_URL
 *   3. null / empty string → returns null (caller shows placeholder)
 */
function assetUrl(?string $path): ?string {
    if (!$path) return null;
    // Already absolute
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Delete a stored upload file from disk.
 * Accepts both root-relative paths (/uploads/pets/…) and full URLs.
 */
function deleteUploadFile(?string $path): void {
    if (!$path) return;
    // Convert full URL → root-relative if needed
    $baseUrl = rtrim(APP_URL, '/') . '/uploads/';
    if (str_starts_with($path, $baseUrl)) {
        $path = '/uploads/' . substr($path, strlen($baseUrl));
    }
    if (str_starts_with($path, '/uploads/')) {
        $abs = rtrim(UPLOAD_DIR, '/') . substr($path, strlen('/uploads'));
        if (is_file($abs)) @unlink($abs);
    }
}

// ---- Notification ----

function createNotification(int $userId, string $title, string $message, string $type = 'info', string $link = ''): void {
    $stmt = getDB()->prepare('INSERT INTO notifications (user_id, title, message, type, link) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

function unreadNotificationCount(int $userId): int {
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ---- Rating update ----

function refreshClinicRating(int $clinicId): void {
    $stmt = getDB()->prepare('
        UPDATE clinics
        SET average_rating = (SELECT COALESCE(AVG(clinic_rating),0) FROM ratings WHERE clinic_id = ? AND clinic_rating IS NOT NULL),
            total_ratings   = (SELECT COUNT(*) FROM ratings WHERE clinic_id = ? AND clinic_rating IS NOT NULL)
        WHERE id = ?
    ');
    $stmt->execute([$clinicId, $clinicId, $clinicId]);
}

// ---- JSON Response ----

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

// ---- AI / Groq (Llama 3) ----

function callGroq(string $prompt, int $maxTokens = 700): array {
    if (GROQ_API_KEY === '') {
        return ['success' => false, 'text' => 'AI is not configured yet. Add GROQ_API_KEY to your Apache environment and restart Apache.'];
    }

    $body = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'max_tokens'  => $maxTokens,
        'temperature' => 0.7,
    ]);
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle cURL errors
    if ($response === false || $curlError) {
        error_log("Groq API cURL Error: " . $curlError);
        return ['success' => false, 'text' => "I'm having trouble connecting right now. Please check your internet connection and try again in a moment. 🔌"];
    }

    if ($httpCode === 200) {
        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Groq API JSON Parse Error: " . json_last_error_msg() . " | Response: " . substr($response, 0, 500));
            return ['success' => false, 'text' => "I received an unexpected response. Our team has been notified. Please try again in a moment. 🔧"];
        }
        $text = trim((string)($json['choices'][0]['message']['content'] ?? ''));
        if ($text === '') {
            error_log('Groq API returned an empty chat completion.');
            return ['success' => false, 'text' => 'The AI service returned an empty reply. Please try again.'];
        }
        return ['success' => true, 'text' => $text];
    }

    // Handle API errors with helpful messages
    $errJson = json_decode($response, true);
    $errMsg  = $errJson['error']['message'] ?? $response;
    error_log("Groq API Error (HTTP $httpCode): " . $errMsg);
    
    // User-friendly error messages
    if ($httpCode === 401) {
        return ['success' => false, 'text' => "Authentication issue detected. Please contact support. 🔑"];
    } elseif ($httpCode === 429) {
        return ['success' => false, 'text' => "I'm receiving a lot of requests right now. Please wait a moment and try again. ⏳"];
    } elseif ($httpCode === 500 || $httpCode === 503) {
        return ['success' => false, 'text' => "The AI service is temporarily unavailable. Please try again in a few minutes. 🔄"];
    } else {
        return ['success' => false, 'text' => "I encountered an issue (Error {$httpCode}). Please try again or contact support if this continues. 💬"];
    }
}

/**
 * Call Groq vision model with an image (base64) + text prompt.
 * Supports: meta-llama/llama-4-scout-17b-16e-instruct
 * @param string $prompt - Text prompt for the AI
 * @param string $imageBase64 - Base64 encoded image
 * @param string $mimeType - Image MIME type (default: image/jpeg)
 * @return array - ['success' => bool, 'text' => string]
 */
function callVision(string $prompt, string $imageBase64, string $mimeType = 'image/jpeg'): array {
    if (GROQ_API_KEY === '') {
        return ['success' => false, 'text' => 'AI is not configured yet. Add GROQ_API_KEY to your Apache environment and restart Apache.'];
    }
    // Groq vision uses the same chat completions endpoint
    $body = json_encode([
        'model'    => defined('GROQ_VISION_MODEL') ? GROQ_VISION_MODEL : 'meta-llama/llama-4-scout-17b-16e-instruct',
        'messages' => [[
            'role'    => 'user',
            'content' => [
                [
                    'type'      => 'text',
                    'text'      => $prompt,
                ],
                [
                    'type'      => 'image_url',
                    'image_url' => [
                        'url'    => "data:{$mimeType};base64,{$imageBase64}",
                        'detail' => 'low',   // 'low' = faster + smaller payload
                    ],
                ],
            ],
        ]],
        'max_tokens'  => 600,
        'temperature' => 0.4,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT    => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log all details for debugging
    if ($response === false || $curlError) {
        error_log("Vision API cURL Error: " . $curlError);
        return ['success' => false, 'text' => "Connection error: {$curlError}"];
    }

    if ($httpCode === 200) {
        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Vision API JSON Error: " . json_last_error_msg());
            return ['success' => false, 'text' => "Invalid response format"];
        }
        $text = $json['choices'][0]['message']['content'] ?? 'No response received.';
        return ['success' => true, 'text' => $text];
    }

    $errJson = json_decode($response, true);
    $errMsg  = $errJson['error']['message'] ?? $response;
    error_log("Vision API Error HTTP {$httpCode}: {$errMsg}");
    return ['success' => false, 'text' => "Vision AI error (HTTP {$httpCode}): {$errMsg}"];
}

// ---- Utility ----

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff/60)   . ' min ago';
    if ($diff < 86400)     return floor($diff/3600)  . ' hr ago';
    if ($diff < 2592000)   return floor($diff/86400) . ' days ago';
    return date('M j, Y', $time);
}

function starRating(float $rating, bool $showNumber = true): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating)              $html .= '<i class="fas fa-star"></i>';
        elseif ($i - 0.5 <= $rating)   $html .= '<i class="fas fa-star-half-alt"></i>';
        else                            $html .= '<i class="far fa-star"></i>';
    }
    $html .= '</span>';
    if ($showNumber) $html .= ' <small>' . number_format($rating, 1) . '</small>';
    return $html;
}

function statusBadge(string $status): string {
    $map = [
        'pending'     => 'badge-warning',
        'approved'    => 'badge-success',
        'rejected'    => 'badge-danger',
        'completed'   => 'badge-primary',
        'cancelled'   => 'badge-secondary',
        'rescheduled' => 'badge-info',
    ];
    $cls = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . ucfirst($status) . '</span>';
}

function getPaginationData(int $total, int $page, int $perPage = 10): array {
    $total = max(0, $total);
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return compact('total', 'page', 'perPage', 'totalPages', 'offset');
}

/**
 * Render a pagination bar.
 * @param array $pg   Result of getPaginationData()
 * @param array $get  Extra GET params to merge (defaults to current $_GET)
 */
function renderPagination(array $pg, array $get = []): void {
    if ($pg['totalPages'] <= 1) return;
    $params = array_merge($_GET, $get);
    echo '<div class="pagination">';
    for ($i = 1; $i <= $pg['totalPages']; $i++) {
        $params['page'] = $i;
        $active = ($i === (int)$pg['page']) ? ' active' : '';
        $url    = '?' . http_build_query($params);
        echo "<a href=\"{$url}\" class=\"page-btn{$active}\">{$i}</a>";
    }
    echo '</div>';
}
