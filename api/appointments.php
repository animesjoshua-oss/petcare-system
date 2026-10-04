<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$user   = currentUser();
$requestData = $_POST;
if ($method === 'POST' && str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) $requestData = $decoded;
}

// Support both POST form and GET with action param
$action = '';
$apptId = 0;

if ($method === 'POST') {
    $action = sanitize((string)($requestData['action'] ?? ''));
    $apptId = (int)($requestData['appointment_id'] ?? 0);
    $csrf   = (string)($requestData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $cancelReason = sanitize((string)($requestData['cancel_reason'] ?? ''));
} else {
    $action = sanitize($_GET['action'] ?? '');
    $apptId = (int)($_GET['id'] ?? 0);
    $csrf   = $_GET['csrf'] ?? '';
    $cancelReason = '';
}

if (!verifyCsrfToken($csrf)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['error'=>'Invalid CSRF token.'],403);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php?error=csrf');
    exit;
}

if (!$apptId) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['error'=>'Missing appointment ID.'],400);
    header('Location: ' . APP_URL . '/login.php'); exit;
}

// Fetch appointment
$stmt = $db->prepare('SELECT a.*,c.admin_id,v.user_id AS vet_user_id,g.user_id AS groomer_user_id FROM appointments a JOIN clinics c ON a.clinic_id=c.id LEFT JOIN veterinarians v ON a.vet_id=v.id LEFT JOIN groomers g ON a.groomer_id=g.id WHERE a.id=?');
$stmt->execute([$apptId]); $appt = $stmt->fetch();

if (!$appt) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['error'=>'Appointment not found.'],404);
    header('Location: ' . APP_URL . '/dashboard/owner/appointments.php'); exit;
}

// Permission check
$canManage = $user['role_name']==='superadmin'
    || ($user['role_name']==='admin' && $appt['admin_id']==$user['id'])
    || ($user['role_name']==='veterinarian' && $appt['vet_user_id']==$user['id'])
    || ($user['role_name']==='groomer'       && $appt['groomer_user_id']==$user['id'])
    || ($user['role_name']==='pet_owner'     && $appt['owner_id']==$user['id'] && in_array($action,['cancel']));

if (!$canManage) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['error'=>'Unauthorized.'],403);
    header('Location: ' . APP_URL . '/unauthorized.php'); exit;
}

$newStatus = match($action) {
    'approve'  => 'approved',
    'reject'   => 'rejected',
    'complete' => 'completed',
    'cancel'   => 'cancelled',
    default    => null
};

if (!$newStatus) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) jsonResponse(['error'=>'Invalid action.'],400);
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
$ownerMsg  = match($newStatus){
    'approved'  => "Your appointment on ".date('M j, Y',strtotime($appt['appointment_date']))." has been approved!",
    'rejected'  => "Your appointment has been rejected. Please try booking another time.",
    'completed' => "Your appointment has been marked as completed. Please rate your experience!",
    'cancelled' => "Your appointment has been cancelled." . ($cancelReason ? " Reason: $cancelReason" : ""),
    default     => ""
};
if ($ownerMsg) createNotification($appt['owner_id'], 'Appointment '.ucfirst($newStatus), $ownerMsg, 'appointment', APP_URL.'/dashboard/owner/appointments.php');

// Notify clinic admin when owner cancels, including reason
if ($newStatus === 'cancelled' && $appt['owner_id'] == $user['id'] && $cancelReason) {
    $clinicMsg = "An appointment was cancelled by the pet owner." . " Reason: $cancelReason";
    createNotification($appt['admin_id'], 'Appointment Cancelled', $clinicMsg, 'warning', APP_URL.'/dashboard/admin/appointments.php');
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    jsonResponse(['success'=>true,'message'=>'Appointment '.ucfirst($newStatus).'!','status'=>$newStatus]);
}

// Redirect back
$ref = $_SERVER['HTTP_REFERER'] ?? APP_URL.'/dashboard/admin/appointments.php';
header('Location: '.$ref.'?updated=1'); exit;
