<?php
/**
 * Reserved Seats API - Returns list of already-booked seats for a show.
 *
 * API USAGE:
 * - Called from: booking.js (when loading seat selection grid)
 * - GET ?itemName=&itemType=movie&showDate=&showTime=&venue= → Returns reserved seat IDs
 *
 * External APIs: None. Reads from bookings table.
 */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$itemName = isset($_GET['itemName']) ? trim($_GET['itemName']) : '';
$itemType = isset($_GET['itemType']) ? trim($_GET['itemType']) : 'movie';
$showDate = isset($_GET['showDate']) ? trim($_GET['showDate']) : '';
$showTime = isset($_GET['showTime']) ? trim($_GET['showTime']) : '';
$venue    = isset($_GET['venue']) ? trim($_GET['venue']) : '';

if ($itemName === '' || $showDate === '' || $showTime === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit();
}

$conn = db_get_connection();
db_ensure_bookings_table($conn);

$sql = "SELECT seats FROM bookings
        WHERE item_type = ?
          AND item_name = ?
          AND show_date = ?
          AND show_time = ?
          AND status NOT IN ('Cancelled','Refunded')";

if ($venue !== '') {
    $sql .= " AND venue = ?";
}

$types = $venue !== '' ? 'sssss' : 'ssss';
$params = [$itemType, $itemName, $showDate, $showTime];
if ($venue !== '') {
    $params[] = $venue;
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$seats = [];
while ($row = $result->fetch_assoc()) {
    $parts = array_filter(array_map('trim', explode(',', (string)$row['seats'])));
    $seats = array_merge($seats, $parts);
}

$stmt->close();

echo json_encode([
    'success' => true,
    'seats' => array_values(array_unique($seats))
]);

