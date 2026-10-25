<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$user   = currentUser();
$isGroomerAdmin = isGroomerAdmin($user);
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);
if ($method !== 'POST') {
    if ($isAjax) jsonResponse(['error' => 'Method not allowed.'], 405);
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
$requestData = $_POST;
if ($method === 'POST' && str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) $requestData = $decoded;
}

$action = sanitize((string)($requestData['action'] ?? ''));
$apptId = (int)($requestData['appointment_id'] ?? 0);
$csrf   = (string)($requestData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$cancelReason = sanitize((string)($requestData['cancel_reason'] ?? ''));

if (!verifyCsrfToken($csrf)) {
    if ($isAjax) jsonResponse(['error'=>'Invalid CSRF token.'],403);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php?error=csrf');
    exit;
}

if (!$apptId) {
    if ($isAjax) jsonResponse(['error'=>'Missing appointment ID.'],400);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php'); exit;
}

// Fetch appointment
$stmt = $db->prepare('SELECT a.*,c.admin_id,c.clinic_type,v.user_id AS vet_user_id,g.user_id AS groomer_user_id FROM appointments a JOIN clinics c ON a.clinic_id=c.id LEFT JOIN veterinarians v ON a.vet_id=v.id LEFT JOIN groomers g ON a.groomer_id=g.id WHERE a.id=?');
$stmt->execute([$apptId]); $appt = $stmt->fetch();

if (!$appt) {
    if ($isAjax) jsonResponse(['error'=>'Appointment not found.'],404);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php'); exit;
}

// Permission check keeps each clinic admin within their service area.
if ($user['role_name'] === 'superadmin') {
    $canManage = true;
} elseif ($user['role_name'] === 'admin' && (int)$appt['admin_id'] === (int)$user['id']) {
    if ($isGroomerAdmin) {
        $canManage = $appt['clinic_type'] === 'grooming' && $appt['appointment_type'] === 'grooming';
    } else {
        $canManage = $appt['clinic_type'] === 'veterinary' && $appt['appointment_type'] === 'veterinary';
    }
} elseif ($user['role_name'] === 'veterinarian' && (int)$appt['vet_user_id'] === (int)$user['id']) {
    $canManage = true;
} elseif ($user['role_name'] === 'groomer' && (int)$appt['groomer_user_id'] === (int)$user['id']) {
    $canManage = true;
} elseif ($user['role_name'] === 'pet_owner' && (int)$appt['owner_id'] === (int)$user['id'] && $action === 'cancel') {
    $canManage = true;
} else {
    $canManage = false;
}

if (!$canManage) {
    if ($isAjax) jsonResponse(['error'=>'Unauthorized.'],403);
    header('Location: ' . APP_URL . '/unauthorized.php'); exit;
}

if ($action === 'approve') {
    $newStatus = 'approved';
} elseif ($action === 'reject') {
    $newStatus = 'rejected';
} elseif ($action === 'complete') {
    $newStatus = 'completed';
} elseif ($action === 'cancel') {
    $newStatus = 'cancelled';
} else {
    $newStatus = null;
}

if (!$newStatus) {
    if ($isAjax) jsonResponse(['error'=>'Invalid action.'],400);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php'); exit;
}

// Update
$update = $db->prepare('UPDATE appointments SET status=?, updated_at=NOW() '.($newStatus==='completed'?', completed_at=NOW()':'').' WHERE id=?');
$update->execute([$newStatus, $apptId]);

// Save cancel reason into the `reason` field if cancelling
if ($newStatus === 'cancelled' && $cancelReason) {
    $db->prepare('UPDATE appointments SET reason=? WHERE id=?')->execute([$cancelReason, $apptId]);
}

// Notifications
if ($newStatus === 'approved') {
    $ownerMsg = "Your appointment on " . date('M j, Y', strtotime($appt['appointment_date'])) . " has been approved!";
} elseif ($newStatus === 'rejected') {
    $ownerMsg = 'Your appointment has been rejected. Please try booking another time.';
} elseif ($newStatus === 'completed') {
    $ownerMsg = 'Your appointment has been marked as completed. Please rate your experience!';
} elseif ($newStatus === 'cancelled') {
    $ownerMsg = 'Your appointment has been cancelled.';
    if ($cancelReason) $ownerMsg .= ' Reason: ' . $cancelReason;
} else {
    $ownerMsg = '';
}
if ($ownerMsg) createNotification($appt['owner_id'], 'Appointment '.ucfirst($newStatus), $ownerMsg, 'appointment', APP_URL.'/dashboard/owner/appointments.php');

// Notify clinic admin when owner cancels, including reason
if ($newStatus === 'cancelled' && $appt['owner_id'] == $user['id'] && $cancelReason) {
    $clinicMsg = "An appointment was cancelled by the pet owner." . " Reason: $cancelReason";
    createNotification($appt['admin_id'], 'Appointment Cancelled', $clinicMsg, 'warning', APP_URL.'/dashboard/admin/appointments.php');
}

if ($isAjax) {
    jsonResponse(['success'=>true,'message'=>'Appointment '.ucfirst($newStatus).'!','status'=>$newStatus]);
}

// Redirect back
$ref = $_SERVER['HTTP_REFERER'] ?? APP_URL.'/dashboard/admin/appointments.php';
header('Location: '.$ref.'?updated=1'); exit;
