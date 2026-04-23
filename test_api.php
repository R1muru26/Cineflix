<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$raw = json_encode([
    'bookingId' => 'BOOK-123',
    'itemName' => 'The Matrix',
    'itemType' => 'movie',
    'showDate' => '2026-04-07',
    'showTime' => '10:00',
    'seats' => 'B1,B2',
    'quantity' => 2,
    'totalAmount' => 700.00,
    'paymentMethod' => 'card',
    'hasVehicle' => false
]);

// overwrite file_get_contents wrapper
file_put_contents('php://temp', $raw); // actually this won't change php://input easily without stream wrapper. Let's just include the logic manually.
?>
