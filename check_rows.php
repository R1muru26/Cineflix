<?php
function makeBooking($id, $movie) {
    $data = [
        'bookingId' => $id,
        'itemName' => $movie,
        'itemType' => 'movie',
        'showDate' => '2026-04-07',
        'showTime' => '10:00',
        'seats' => 'B1',
        'quantity' => 1,
        'totalAmount' => 350.00,
        'paymentMethod' => 'card',
        'hasVehicle' => false
    ];
    
    $ch = curl_init('http://localhost/CINEFLIX/api/save_booking.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // We must pass a session cookie to bypass the Auth checks!
    // But save_booking doesn't have an auth check for POSTs! Wait, we saw it throw Authentication Required!
    curl_exec($ch);
    curl_close($ch);
}

require 'includes/db.php';
$conn = db_get_connection();

function printRowCounts($conn) {
    $r1 = $conn->query("SELECT COUNT(*) as c FROM bookings");
    $r2 = $conn->query("SELECT COUNT(*) as c FROM bookings_erd");
    $r3 = $conn->query("SELECT COUNT(*) as c FROM payment");
    $r4 = $conn->query("SELECT COUNT(*) as c FROM ticket");
    
    echo "bookings: " . $r1->fetch_assoc()['c'] . " | ";
    echo "bookings_erd: " . $r2->fetch_assoc()['c'] . " | ";
    echo "payment: " . $r3->fetch_assoc()['c'] . " | ";
    echo "ticket: " . $r4->fetch_assoc()['c'] . "\n";
}

echo "BEFORE:\n";
printRowCounts($conn);

echo "MAKING BOOKING...\n";
makeBooking('CF' . rand(1000, 9999), 'Movie Time');

echo "AFTER:\n";
printRowCounts($conn);
?>
