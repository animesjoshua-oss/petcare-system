<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

function appointmentActionError(string $message, int $status, bool $isAjax, string $fallbackPath): void {
    if ($isAjax) jsonResponse(['error' => $message], $status);

    $_SESSION['appointment_flash'] = ['type' => 'danger', 'message' => $message];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;
    $appHost = parse_url(APP_URL, PHP_URL_HOST);
    if (!$referer || ($refererHost && strcasecmp($refererHost, (string)$appHost) !== 0)) {
        $referer = APP_URL . $fallbackPath;
    }
    $separator = str_contains($referer, '?') ? '&' : '?';
    header('Location: ' . $referer . $separator . 'updated=1');
    exit;
}

/** Match the booking page's clinic-hours parser and 30-minute slot grid. */
function appointmentClinicWindow(?string $hoursJson, DateTimeImmutable $date): ?array {
    if ($hoursJson === null || trim($hoursJson) === '') return null;
    $hours = json_decode($hoursJson, true);
    if (!is_array($hours) || !$hours) return null;

    $dayName = strtolower($date->format('l'));
    $day = $hours[$dayName] ?? $hours[substr($dayName, 0, 3)] ?? null;
    if ($day === null || $day === 'closed' || !is_array($day) || !empty($day['closed'])) return null;

    $openValue = trim((string)($day['open'] ?? ''));
    $closeValue = trim((string)($day['close'] ?? ''));
    if (strtolower($openValue) === '24 hours' && $closeValue === '') return [0, 1440];
    if ($openValue === '' || $closeValue === '') return null;

    $toMinutes = static function (string $value): ?int {
        $timestamp = strtotime($value);
        if ($timestamp === false) return null;
        return ((int)date('G', $timestamp) * 60) + (int)date('i', $timestamp);
    };
    $openMinute = $toMinutes($openValue);
    $closeMinute = $toMinutes($closeValue);
    if ($openMinute === null || $closeMinute === null || $closeMinute <= $openMinute) return null;
    return [$openMinute, $closeMinute];
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = currentUser();
$isGroomerAdmin = isGroomerAdmin($user);
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);
$fallbackPath = $user['role_name'] === 'pet_owner'
    ? '/dashboard/owner/appointments.php'
    : ($user['role_name'] === 'groomer' ? '/dashboard/groomer/appointments.php' : ($user['role_name'] === 'veterinarian' ? '/dashboard/vet/appointments.php' : '/dashboard/admin/appointments.php'));

if ($method !== 'POST') {
    if ($isAjax) jsonResponse(['error' => 'Method not allowed.'], 405);
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$requestData = $_POST;
if (str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) $requestData = $decoded;
}

$action = sanitize((string)($requestData['action'] ?? ''));
$appointmentId = (int)($requestData['appointment_id'] ?? 0);
$csrf = (string)($requestData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$cancelReason = sanitize((string)($requestData['cancel_reason'] ?? ''));

if (!verifyCsrfToken($csrf)) {
    appointmentActionError('Invalid request. Refresh the page and try again.', 403, $isAjax, $fallbackPath);
}
if (!$appointmentId) {
    appointmentActionError('Missing appointment ID.', 400, $isAjax, $fallbackPath);
}

$appointmentQuery = $db->prepare('SELECT a.*,c.admin_id,c.clinic_type,c.operating_hours,v.user_id AS vet_user_id,g.user_id AS groomer_user_id FROM appointments a JOIN clinics c ON a.clinic_id=c.id LEFT JOIN veterinarians v ON a.vet_id=v.id LEFT JOIN groomers g ON a.groomer_id=g.id WHERE a.id=?');
$appointmentQuery->execute([$appointmentId]);
$appointment = $appointmentQuery->fetch();
if (!$appointment) {
    appointmentActionError('Appointment not found.', 404, $isAjax, $fallbackPath);
}

// Keep clinic administrators in their own appointment type and service area.
if ($user['role_name'] === 'superadmin') {
    $canManage = true;
} elseif ($user['role_name'] === 'admin' && (int)$appointment['admin_id'] === (int)$user['id']) {
    if ($isGroomerAdmin) {
        $canManage = $appointment['clinic_type'] === 'grooming' && $appointment['appointment_type'] === 'grooming';
    } else {
        $canManage = $appointment['clinic_type'] === 'veterinary' && $appointment['appointment_type'] === 'veterinary';
    }
} elseif ($user['role_name'] === 'veterinarian' && (int)$appointment['vet_user_id'] === (int)$user['id']) {
    $canManage = true;
} elseif ($user['role_name'] === 'groomer' && (int)$appointment['groomer_user_id'] === (int)$user['id']) {
    $canManage = true;
} elseif ($user['role_name'] === 'pet_owner' && (int)$appointment['owner_id'] === (int)$user['id'] && $action === 'cancel') {
    $canManage = true;
} else {
    $canManage = false;
}

if (!$canManage) {
    appointmentActionError('You do not have permission to manage this appointment.', 403, $isAjax, $fallbackPath);
}

$currentStatus = $appointment['status'];
$newStatus = null;
if ($action === 'approve' && in_array($currentStatus, ['pending', 'rescheduled'], true)) {
    $newStatus = 'approved';
} elseif ($action === 'reject' && $currentStatus === 'pending') {
    $newStatus = 'rejected';
} elseif ($action === 'complete' && $currentStatus === 'approved') {
    $newStatus = 'completed';
} elseif ($action === 'no_show' && $currentStatus === 'approved') {
    $appointmentStart = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
    $appointmentEnd = $appointmentStart + (max(1, (int)($appointment['duration_minutes'] ?? 30)) * 60);
    if ($appointmentEnd > time()) {
        appointmentActionError('This appointment can be marked no-show after its scheduled end time.', 409, $isAjax, $fallbackPath);
    }
    $newStatus = 'no_show';
} elseif ($action === 'cancel' && in_array($currentStatus, ['pending', 'approved', 'rescheduled'], true)) {
    $newStatus = 'cancelled';
} elseif ($action === 'reschedule' && in_array($currentStatus, ['pending', 'approved', 'rescheduled'], true)) {
    $newStatus = 'rescheduled';
} else {
    appointmentActionError('This status cannot be changed with that action.', 409, $isAjax, $fallbackPath);
}

$rescheduleDate = null;
$rescheduleTime = null;
if ($action === 'reschedule') {
    $rescheduleDate = trim((string)($requestData['reschedule_date'] ?? ''));
    $rescheduleTime = trim((string)($requestData['reschedule_time'] ?? ''));
    $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $rescheduleDate);
    $timeIsValid = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/', $rescheduleTime) === 1;
    if (!$dateObject || $dateObject->format('Y-m-d') !== $rescheduleDate || !$timeIsValid) {
        appointmentActionError('Choose a valid appointment date and time.', 422, $isAjax, $fallbackPath);
    }
    if ($rescheduleDate < date('Y-m-d')) {
        appointmentActionError('The new appointment date must be today or later.', 422, $isAjax, $fallbackPath);
    }
    $normalizedTime = substr($rescheduleTime, 0, 5) . ':00';
    if ($rescheduleDate === date('Y-m-d') && strtotime($rescheduleDate . ' ' . $normalizedTime) <= time()) {
        appointmentActionError('Choose a time that has not already passed.', 422, $isAjax, $fallbackPath);
    }
    $window = appointmentClinicWindow($appointment['operating_hours'] ?? null, $dateObject);
    if (!$window) {
        appointmentActionError('The clinic is closed on that day or has not posted its hours.', 422, $isAjax, $fallbackPath);
    }
    $hour = (int)substr($normalizedTime, 0, 2);
    $minute = (int)substr($normalizedTime, 3, 2);
    $requestedMinute = ($hour * 60) + $minute;
    if ($requestedMinute < $window[0] || $requestedMinute >= $window[1] || (($requestedMinute - $window[0]) % 30) !== 0) {
        appointmentActionError('Choose a 30-minute start time within the clinic’s posted hours.', 422, $isAjax, $fallbackPath);
    }
    $rescheduleTime = $normalizedTime;
}

try {
    $db->beginTransaction();
    if ($action === 'reschedule') {
        $update = $db->prepare("UPDATE appointments SET status='rescheduled',appointment_date=?,appointment_time=?,reschedule_date=?,reschedule_time=?,updated_at=NOW() WHERE id=? AND status=?");
        $update->execute([$rescheduleDate, $rescheduleTime, $rescheduleDate, $rescheduleTime, $appointmentId, $currentStatus]);
    } else {
        $updateSql = 'UPDATE appointments SET status=?, updated_at=NOW()' . ($newStatus === 'completed' ? ', completed_at=NOW()' : '') . ' WHERE id=? AND status=?';
        $update = $db->prepare($updateSql);
        $update->execute([$newStatus, $appointmentId, $currentStatus]);
    }
    if ($update->rowCount() !== 1) {
        $db->rollBack();
        appointmentActionError('The appointment changed before this action could be saved. Refresh and try again.', 409, $isAjax, $fallbackPath);
    }
    if ($newStatus === 'cancelled' && $cancelReason !== '') {
        $db->prepare('UPDATE appointments SET reason=? WHERE id=?')->execute([$cancelReason, $appointmentId]);
    }
    if ($newStatus === 'no_show') {
        $db->prepare('UPDATE users SET no_show_count=LEAST(COALESCE(no_show_count,0)+1,255) WHERE id=?')->execute([$appointment['owner_id']]);
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Appointment action failed: ' . $error->getMessage());
    appointmentActionError('The appointment update could not be saved. Please try again.', 500, $isAjax, $fallbackPath);
}

if ($newStatus === 'approved') {
    $ownerMessage = 'Your appointment on ' . date('M j, Y', strtotime($appointment['appointment_date'])) . ' has been confirmed.';
} elseif ($newStatus === 'rejected') {
    $ownerMessage = 'Your appointment has been declined. Please try booking another time.';
} elseif ($newStatus === 'completed') {
    $ownerMessage = 'Your appointment has been marked as completed. Please rate your experience!';
} elseif ($newStatus === 'no_show') {
    $ownerMessage = 'Your appointment was marked as a no-show.';
} elseif ($newStatus === 'rescheduled') {
    $ownerMessage = 'Your appointment has been rescheduled to ' . date('M j, Y', strtotime($rescheduleDate)) . ' at ' . date('g:i A', strtotime($rescheduleTime)) . '.';
} elseif ($newStatus === 'cancelled') {
    $ownerMessage = 'Your appointment has been cancelled.';
    if ($cancelReason !== '') $ownerMessage .= ' Reason: ' . $cancelReason;
} else {
    $ownerMessage = '';
}
if ($ownerMessage !== '') {
    createNotification((int)$appointment['owner_id'], 'Appointment ' . ucfirst(str_replace('_', ' ', $newStatus)), $ownerMessage, 'appointment', APP_URL . '/dashboard/owner/appointments.php');
}

// Alert the clinic if the pet owner cancels with a reason.
if ($newStatus === 'cancelled' && (int)$appointment['owner_id'] === (int)$user['id'] && $cancelReason !== '') {
    $clinicMessage = 'An appointment was cancelled by the pet owner. Reason: ' . $cancelReason;
    createNotification((int)$appointment['admin_id'], 'Appointment Cancelled', $clinicMessage, 'warning', APP_URL . '/dashboard/admin/appointments.php');
}

if ($isAjax) {
    jsonResponse(['success' => true, 'message' => 'Appointment ' . ucfirst(str_replace('_', ' ', $newStatus)) . '!', 'status' => $newStatus]);
}

$_SESSION['appointment_flash'] = ['type' => 'success', 'message' => 'Appointment ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.'];
$referer = $_SERVER['HTTP_REFERER'] ?? (APP_URL . $fallbackPath);
$refererHost = parse_url($referer, PHP_URL_HOST);
$appHost = parse_url(APP_URL, PHP_URL_HOST);
if ($refererHost && strcasecmp($refererHost, (string)$appHost) !== 0) $referer = APP_URL . $fallbackPath;
$separator = str_contains($referer, '?') ? '&' : '?';
header('Location: ' . $referer . $separator . 'updated=1');
exit;
