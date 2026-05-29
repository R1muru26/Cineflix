<?php
/**
 * Receipt API - Returns order details for downloadable receipt.
 *
 * API USAGE:
 * - Called from: chatbot.js (Download Receipt button in order confirm and tracking panel)
 * - GET ?orderId=FD... → Returns order items, subtotal, 20% discount, final total
 *
 * External APIs: None. Reads from food_orders table.
 */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');



if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$orderId = trim($_GET['orderId'] ?? '');
if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$conn = db_get_connection();
$id = $conn->real_escape_string($orderId);
$result = $conn->query("SELECT * FROM food_orders WHERE order_id = '$id' LIMIT 1");

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$row = $result->fetch_assoc();
$items = json_decode($row['items'] ?? '[]', true);
$subtotal = (float)($row['total_amount'] ?? 0);
$discountAmount = isset($row['discount_amount']) ? (float)$row['discount_amount'] : round($subtotal * 0.2, 2);
$finalTotal = isset($row['final_total']) ? (float)$row['final_total'] : round($subtotal * 0.8, 2);
if ($discountAmount <= 0 && $finalTotal <= 0) {
    $discountAmount = round($subtotal * 0.2, 2);
    $finalTotal = round($subtotal - $discountAmount, 2);
}

echo json_encode([
    'success' => true,
    'order' => [
        'orderId' => $row['order_id'],
        'createdAt' => $row['created_at'] ?? date('Y-m-d H:i:s'),
        'seatNumber' => $row['seat_number'] ?? '',
        'items' => $items,
        'subtotal' => $subtotal,
        'discountPercent' => 20,
        'discountAmount' => $discountAmount,
        'finalTotal' => $finalTotal
    ]
]);
