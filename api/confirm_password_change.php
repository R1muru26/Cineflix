<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
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

$code = trim((string)($data['code'] ?? ''));
if ($code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Verification code is required.']);
    exit();
}

$sessionData = $_SESSION['password_change'] ?? null;
if (!$sessionData || empty($sessionData['otp']) || empty($sessionData['new_hash']) || empty($sessionData['expires_at'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No pending password change request was found. Please start again.']);
    exit();
}

if (time() > (int)$sessionData['expires_at']) {
    unset($_SESSION['password_change']);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
    exit();
}

if ($code !== (string)$sessionData['otp']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Incorrect verification code.']);
    exit();
}

$userId = (int)$sessionData['user_id'];
$newHash = (string)$sessionData['new_hash'];

$db = db_get_connection();
$stmt = $db->prepare("UPDATE CustomerUser SET Password = ? WHERE CustomerID = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare update statement.']);
    exit();
}
$stmt->bind_param('si', $newHash, $userId);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update password. Please try again.']);
    exit();
}
$stmt->close();

unset($_SESSION['password_change']);

echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);

