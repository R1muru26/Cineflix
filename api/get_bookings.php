<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');



$conn = db_get_connection();
db_ensure_bookings_table($conn);

$isAdmin = !empty($_SESSION['is_admin']);
$userId = $_SESSION['user_id'] ?? null;

if (!$isAdmin && !$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$params = [];
$types = '';
$where = '';

if ($isAdmin) {
    $where = '';
} else {
    $where = 'WHERE customer_id = ?';
    $params[] = (int)$userId;
    $types .= 'i';
}

$query = "SELECT booking_id, customer_id, customer_name, customer_email, item_type, item_name, event_option,
                 show_date, show_time, venue, seats, quantity, total_amount, payment_method, addons,
                 status, refund_reason, refund_status, cancelled_date, created_at,
                 discount_type, discount_original_total, discounted_total, discount_status, discount_id_number, discount_id_path,
                 parking_number
          FROM bookings {$where}
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$paid = [];
$refunded = [];

while ($row = $result->fetch_assoc()) {
    $row['addons'] = $row['addons'] ? json_decode($row['addons'], true) : [];
    $normalizedStatus = strtolower($row['status'] ?? 'Paid');
    if (in_array($normalizedStatus, ['cancelled', 'refunded', 'refund requested', 'refund cancelled'])) {
        $refunded[] = $row;
    } else {
        $paid[] = $row;
    }
}

$stmt->close();

echo json_encode([
    'success' => true,
    'paid' => $paid,
    'cancelled' => $refunded
]);

