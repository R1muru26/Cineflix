<?php
/**
 * CineFlix Food Order Tracking API
 * GET ?orderId=FD... returns order status, progress stages, timer, and items
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/db.php';

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

$conn   = db_get_connection();
$id     = $conn->real_escape_string($orderId);
$result = $conn->query("SELECT * FROM food_orders WHERE order_id = '$id' LIMIT 1");

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$row    = $result->fetch_assoc();
$status = $row['status'] ?? 'received';

/* Stage mapping */
$statusOrder = ['received', 'preparing', 'ready', 'delivering', 'delivered'];
$stageLabels = ['Order Received', 'Preparing Food', 'Ready for Delivery', 'Out for Delivery', 'Delivered to Seat'];
$currentIdx  = array_search($status, $statusOrder);
if ($currentIdx === false) $currentIdx = 0;

/* Build stages array */
$stages = [];
foreach ($stageLabels as $i => $label) {
    $stages[] = [
        'label'   => $label,
        'done'    => $i <= $currentIdx,
        'current' => $i === $currentIdx,
    ];
}

/* Items */
$items = json_decode($row['items'] ?? '[]', true);
if (!is_array($items)) $items = [];
foreach ($items as &$item) {
    $item['price'] = (float)($item['price'] ?? 0);
    $item['qty']   = (int)($item['qty']   ?? 1);
}
unset($item);

/* Timer calculation */
$estimatedMins = (int)($row['estimated_minutes'] ?? 15);
if ($estimatedMins <= 0) $estimatedMins = 15;

$createdAtStr = $row['created_at'] ?? null;
if ($createdAtStr) {
    $createdTs     = strtotime($createdAtStr);
    $elapsedMins   = (time() - $createdTs) / 60;
    $remainingMins = max(0, $estimatedMins - $elapsedMins);
} else {
    $remainingMins = $estimatedMins;
}

if ($status === 'delivered') $remainingMins = 0;

/* Total amount — prefer final_total (after discount), fall back to total_amount */
$totalAmount = (float)($row['final_total'] ?? $row['total_amount'] ?? 0);

echo json_encode([
    'success' => true,
    'order'   => [
        'orderId'           => $row['order_id'],
        'status'            => $status,
        'stages'            => $stages,
        'stageLabels'       => $stageLabels,
        'currentStageIndex' => $currentIdx,
        'estimatedMinutes'  => $estimatedMins,
        'remainingMinutes'  => round($remainingMins, 1),
        'seatNumber'        => $row['seat_number'] ?? '',
        'items'             => $items,
        'totalAmount'       => $totalAmount,
        'createdAt'         => $createdAtStr,
    ]
]);