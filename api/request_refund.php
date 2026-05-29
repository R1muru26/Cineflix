<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');



$conn = db_get_connection();
db_ensure_bookings_table($conn);

$userId = $_SESSION['user_id'] ?? null;
$isAdmin = !empty($_SESSION['is_admin']);

if (!$userId && !$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['bookingId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing bookingId']);
    exit();
}

$bookingId = $conn->real_escape_string($input['bookingId']);
$reason = isset($input['reason']) ? $conn->real_escape_string($input['reason']) : 'No reason provided';

$where = 'booking_id = ?';
$types = 's';
$params = [$bookingId];

if (!$isAdmin) {
    $where .= ' AND customer_id = ?';
    $types .= 'i';
    $params[] = (int)$userId;
}

$stmt = $conn->prepare("UPDATE bookings SET status = 'Refund Requested', refund_reason = ?, refund_status = 'Processing', cancelled_date = NOW() WHERE {$where}");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param('s' . $types, $reason, ...$params);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update booking: ' . $stmt->error]);
    $stmt->close();
    exit();
}

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Create notifications
require_once __DIR__ . '/notifications.php';
$notifications = new SmartNotifications($conn);

// Fetch booking info for the notification
$bookingQuery = "SELECT item_name, customer_name, total_amount FROM bookings WHERE booking_id = ?";
$bStmt = $conn->prepare($bookingQuery);
$bStmt->bind_param("s", $bookingId);
$bStmt->execute();
$bookingInfo = $bStmt->get_result()->fetch_assoc();

if ($bookingInfo) {
    // 1. Notify Admin (user_id = 0)
    $adminMsg = "Refund requested for \"{$bookingInfo['item_name']}\" by {$bookingInfo['customer_name']}. Amount: ₱{$bookingInfo['total_amount']}.";
    $notifications->createNotification(0, 'alert', '↩️ New Refund Request', $adminMsg, 'high', 'Review Request', 'admin-dashboard.php#refund-requests');
    
    // 2. Notify User (confirmation)
    if ($userId) {
        $userMsg = "Your refund request for \"{$bookingInfo['item_name']}\" has been submitted and is being processed.";
        $notifications->createNotification($userId, 'info', '↩️ Refund Requested', $userMsg, 'medium', 'View Status', 'status.php');
    }
}

echo json_encode(['success' => true]);

