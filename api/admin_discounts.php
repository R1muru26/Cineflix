<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
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

$bookingId = isset($data['bookingId']) ? trim((string)$data['bookingId']) : '';
$decision  = isset($data['decision']) ? strtolower(trim((string)$data['decision'])) : '';

if ($bookingId === '' || !in_array($decision, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'bookingId and valid decision are required.']);
    exit();
}

$conn = db_get_connection();
db_ensure_bookings_table($conn);

$newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';

$stmt = $conn->prepare("UPDATE bookings SET discount_status = ? WHERE booking_id = ? AND discount_type IS NOT NULL");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param('ss', $newStatus, $bookingId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update discount status: ' . $stmt->error]);
    $stmt->close();
    exit();
}
$stmt->close();

// When approving, recalculate total_amount to reflect the discount
if ($decision === 'approve') {
    $fetchStmt = $conn->prepare("SELECT total_amount, discount_original_total, discounted_total, addons, parking_number FROM bookings WHERE booking_id = ? LIMIT 1");
    $fetchStmt->bind_param('s', $bookingId);
    $fetchStmt->execute();
    $bk = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    if ($bk && $bk['discount_original_total'] !== null && $bk['discounted_total'] !== null) {
        $origTickets = (float)$bk['discount_original_total'];
        $discountedTickets = (float)$bk['discounted_total'];
        $currentTotal = (float)$bk['total_amount'];

        // Recalculate: replace the original ticket cost with the discounted one
        // total = currentTotal - origTickets + discountedTickets
        $newTotal = $currentTotal - $origTickets + $discountedTickets;
        if ($newTotal < 0) $newTotal = 0;

        $updateStmt = $conn->prepare("UPDATE bookings SET total_amount = ? WHERE booking_id = ?");
        $updateStmt->bind_param('ds', $newTotal, $bookingId);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// Create notification for the user
require_once __DIR__ . '/notifications.php';
$notifications = new SmartNotifications($conn);

// Fetch booking info and customer ID
$bookingQuery = "SELECT customer_id, item_name, discount_type FROM bookings WHERE booking_id = ?";
$bStmt = $conn->prepare($bookingQuery);
$bStmt->bind_param("s", $bookingId);
$bStmt->execute();
$bookingInfo = $bStmt->get_result()->fetch_assoc();

if ($bookingInfo && !empty($bookingInfo['customer_id'])) {
    $uid = (int)$bookingInfo['customer_id'];
    $type = $newStatus === 'Approved' ? 'promotion' : 'alert';
    $title = $newStatus === 'Approved' ? '✅ Discount Approved' : '❌ Discount Rejected';
    $discType = strtoupper($bookingInfo['discount_type']);
    
    if ($newStatus === 'Approved') {
        $msg = "Your {$discType} discount for \"{$bookingInfo['item_name']}\" has been approved! 20% savings applied.";
    } else {
        $msg = "Your {$discType} discount for \"{$bookingInfo['item_name']}\" was not approved. Please contact support if you believe this is an error.";
    }
    
    $notifications->createNotification($uid, $type, $title, $msg, 'medium', 'View Ticket', 'status.php');
}

echo json_encode(['success' => true]);

