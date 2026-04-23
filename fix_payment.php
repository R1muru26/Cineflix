<?php
require_once __DIR__ . '/includes/db.php';
$conn = db_get_connection();

// 1. Find all payments that need fixing (or just sync all to match bookings)
$res = $conn->query("SELECT id, payment_method FROM bookings");
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $method = $conn->real_escape_string(ucwords(str_replace('_', ' ', $row['payment_method'])));
    
    // Update the ERD payment table to explicitly align with the actual booking method
    $stmt = $conn->query("UPDATE payment SET PaymentMethod = '$method' WHERE BookingID = $id");
}

echo "All mock payment methods have been synced correctly with your real checkout histories!\n";

$final = $conn->query("SELECT * FROM payment WHERE BookingID = 13");
print_r($final->fetch_assoc());
?>
