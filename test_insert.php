<?php
require_once __DIR__ . '/includes/db.php';
$conn = db_get_connection();

$nativeBookingIdInt = 13; // We know Booking ID 13 exists

$totalAmount = 500;
$paymentMethod = "Cash";
$status = "Paid";
$tktNum = "TKT-TEST";
$seatId = 1; // Assuming SeatID 1 exists 
// Check SeatID 1 exists
$conn->query("INSERT IGNORE INTO theater (TheaterID, TheaterName, TheaterType) VALUES (1, 'Test', 'Standard')");
$conn->query("INSERT IGNORE INTO Seat (SeatID, TheaterID, SeatNumber, AvailabilityStatus) VALUES (1, 1, 'A1', 'Booked')");

echo "Attempting payment insert:\n";
$q1 = "INSERT INTO payment (BookingID, Amount, PaymentMethod, PaymentDate, PaymentStatus) VALUES ($nativeBookingIdInt, $totalAmount, 'Cash', NOW(), 'Paid')";
if (!$conn->query($q1)) {
    echo "Error 1: " . $conn->error . "\n";
} else {
    echo "Payment inserted: " . $conn->insert_id . "\n";
}

echo "Attempting ticket insert:\n";
$q2 = "INSERT INTO ticket (BookingID, SeatID, TicketNumber, Status) VALUES ($nativeBookingIdInt, $seatId, '$tktNum', 'Valid')";
if (!$conn->query($q2)) {
    echo "Error 2: " . $conn->error . "\n";
} else {
    echo "Ticket inserted: " . $conn->insert_id . "\n";
}
?>
