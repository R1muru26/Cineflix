<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../includes/db.php';

$conn = db_get_connection();
db_ensure_bookings_table($conn);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['bookingId']) || empty($input['decision'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing bookingId or decision']);
    exit();
}

$bookingId = $conn->real_escape_string($input['bookingId']);
$decision  = strtolower(trim($input['decision']));

if (!in_array($decision, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid decision']);
    exit();
}

if ($decision === 'approve') {
    $status = 'Refunded';
    $refundStatus = 'Approved';
} else {
    // Mark as refund cancelled so it appears in history/cancelled tab
    $status = 'Refund Cancelled';
    $refundStatus = 'Rejected';
}

$stmt = $conn->prepare("UPDATE bookings 
    SET status = ?, refund_status = ?, cancelled_date = IF(? IN ('Refunded','Refund Cancelled'), NOW(), cancelled_date)
    WHERE booking_id = ? AND status IN ('Refund Requested','Paid')");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param('ssss', $status, $refundStatus, $status, $bookingId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update booking: ' . $stmt->error]);
    $stmt->close();
    exit();
}

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found or already processed.']);
    $stmt->close();
    exit();
}
$stmt->close();

// Create notification for the user
require_once __DIR__ . '/notifications.php';
$notifications = new SmartNotifications($conn);

// Fetch booking info and customer ID
$bookingQuery = "SELECT customer_id, item_name, total_amount FROM bookings WHERE booking_id = ?";
$bStmt = $conn->prepare($bookingQuery);
$bStmt->bind_param("s", $bookingId);
$bStmt->execute();
$bookingInfo = $bStmt->get_result()->fetch_assoc();

if ($bookingInfo && !empty($bookingInfo['customer_id'])) {
    $uid = (int)$bookingInfo['customer_id'];
    $type = ($refundStatus === 'Approved') ? 'alert' : 'info';
    $title = ($refundStatus === 'Approved') ? '✅ Refund Approved' : '❌ Refund Rejected';
    
    if ($refundStatus === 'Approved') {
        $msg = "Your refund for \"{$bookingInfo['item_name']}\" (₱{$bookingInfo['total_amount']}) has been approved.";
    } else {
        $msg = "Your refund request for \"{$bookingInfo['item_name']}\" was not approved.";
    }
    
    $notifications->createNotification($uid, $type, $title, $msg, 'high', 'View Details', 'status.php');
}

echo json_encode(['success' => true]);

