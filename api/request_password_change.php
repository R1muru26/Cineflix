<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/send_otp_email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit();
}

$current = $data['current_password'] ?? '';
$new     = $data['new_password'] ?? '';
$confirm = $data['confirm_password'] ?? '';

if ($current === '' || $new === '' || $confirm === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please fill in all password fields.']);
    exit();
}
if ($new !== $confirm) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password and confirmation do not match.']);
    exit();
}
if (strlen($new) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters long.']);
    exit();
}

$db = db_get_connection();
$userId = (int)$_SESSION['user_id'];

// Verify current password
$stmt = $db->prepare("SELECT Name, Password, Email FROM CustomerUser WHERE CustomerID = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare account lookup.']);
    exit();
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($dbName, $hashedPassword, $dbEmail);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unable to load your account record.']);
    exit();
}
$stmt->close();

if (!password_verify($current, $hashedPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
    exit();
}

// Generate OTP and store in session (10-minute expiry) along with new password hash
$otp = random_int(100000, 999999);
$_SESSION['password_change'] = [
    'user_id' => $userId,
    'otp' => (string)$otp,
    'expires_at' => time() + 600,
    'new_hash' => password_hash($new, PASSWORD_DEFAULT),
];

$recipientEmail = $_SESSION['user_email'] ?: $dbEmail;
$recipientName  = $_SESSION['user_name'] ?? $dbName ?? 'CineFlix User';

$result = sendOTPEmail($recipientEmail, $recipientName, $otp);
if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send verification code. Please try again later.']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Verification code sent to your email.']);

