<?php
$conn = new mysqli('localhost', 'root', '', 'cineflix');
$r = $conn->query('SELECT * FROM food');
echo "Food table rows: " . $r->num_rows . "\n";
while($row = $r->fetch_assoc()) print_r($row);

$r2 = $conn->query('SELECT * FROM bookings ORDER BY created_at DESC LIMIT 1');
echo "\nLast booking:\n";
while($row = $r2->fetch_assoc()) print_r($row);
