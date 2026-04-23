<?php
require_once __DIR__ . '/includes/db.php';
$conn = db_get_connection();

echo "--- BOOKINGS ---\n";
$res = $conn->query("SELECT BookingID, id, booking_id FROM bookings");
while ($row = $res->fetch_assoc()) print_r($row);

echo "\n--- PAYMENT ---\n";
$res = $conn->query("SELECT * FROM payment");
if (!$res) echo "Error: " . $conn->error . "\n";
else while ($row = $res->fetch_assoc()) print_r($row);

echo "\n--- TICKET ---\n";
$res = $conn->query("SELECT * FROM ticket");
if (!$res) echo "Error: " . $conn->error . "\n";
else while ($row = $res->fetch_assoc()) print_r($row);

?>
