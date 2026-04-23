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
    
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

echo "Booking 1: " . makeBooking('TEST-1', 'Movie One') . "\n";
echo "Booking 2: " . makeBooking('TEST-2', 'Movie Two') . "\n";
?>
