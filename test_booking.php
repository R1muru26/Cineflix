<?php
$data = json_encode([
    'movie' => 'Testing',
    'itemType' => 'movie',
    'schedule' => '10:00 AM - 12:00 PM',
    'cinema' => 'Cinema: Standard',
    'date' => '2026-04-06',
    'theatreType' => 'Standard',
    'quantity' => 1,
    'seats' => 'A1',
    'price' => 350,
    'ticketTotal' => 350,
    'addons' => [
        ['id' => 'fries', 'name' => 'Fries', 'price' => 100, 'qty' => 1]
    ],
    'addonsTotal' => 100,
    'total' => 450,
    'bookingId' => 'CF12345678',
    'source' => 'online',
    'amountPaid' => 450,
    'change' => 0,
    'paymentMethod' => 'GCash'
]);

$ch = curl_init('http://localhost/CINEFLIX/api/save_booking.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
$result = curl_exec($ch);
echo "RESPONSE:\n" . $result;
if ($result === false) {
    echo "CURL ERROR: " . curl_error($ch);
}
curl_close($ch);
