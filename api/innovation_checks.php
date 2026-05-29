<?php
/**
 * Innovation Checks API
 * Handles "Silence Mode" reminders and "Seat Upgrade" offers.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

require_once __DIR__ . '/notifications.php';

if (!isset($_SESSION['user_id'])) {
        exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$userId = (int)$_SESSION['user_id'];
$conn = db_get_connection();
$notifications = new SmartNotifications($conn);

// 1. Check for movies starting in ~5 minutes (Silence Mode)
$fiveMinQuery = "SELECT item_name, show_time, booking_id FROM bookings 
                 WHERE customer_id = ? AND status = 'Paid' 
                 AND show_date = CURDATE() 
                 AND STR_TO_DATE(SUBSTRING_INDEX(show_time, ' - ', 1), '%h:%i %p') BETWEEN DATE_ADD(NOW(), INTERVAL 4 MINUTE) AND DATE_ADD(NOW(), INTERVAL 6 MINUTE)";

$stmt = $conn->prepare($fiveMinQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $notifId = "silence_" . $row['booking_id'];
    $checkNotif = $conn->prepare("SELECT id FROM user_notifications WHERE notification_id = ?");
    $checkNotif->bind_param("s", $notifId);
    $checkNotif->execute();
    if ($checkNotif->get_result()->num_rows === 0) {
        $notifications->createNotification(
            $userId, 
            'info', 
            '🍿 Movie Starting Soon!', 
            "\"{$row['item_name']}\" is about to start. Please set your phone to Silence Mode. Enjoy!", 
            'medium',
            null,
            null,
            1
        );
    }
}

// 2. Check for Seat Upgrade Auctions (15 minutes before start)
$upgradeQuery = "SELECT b.item_name, b.show_time, b.booking_id, b.venue
                 FROM bookings b
                 WHERE b.customer_id = ? AND b.status = 'Paid' 
                 AND b.venue LIKE '%Standard%'
                 AND b.show_date = CURDATE() 
                 AND STR_TO_DATE(SUBSTRING_INDEX(b.show_time, ' - ', 1), '%h:%i %p') BETWEEN DATE_ADD(NOW(), INTERVAL 14 MINUTE) AND DATE_ADD(NOW(), INTERVAL 16 MINUTE)";

$stmt = $conn->prepare($upgradeQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $notifId = "upgrade_" . $row['booking_id'];
    $checkNotif = $conn->prepare("SELECT id FROM user_notifications WHERE notification_id = ?");
    $checkNotif->bind_param("s", $notifId);
    $checkNotif->execute();
    
    if ($checkNotif->get_result()->num_rows === 0) {
        $notifications->createNotification(
            $userId, 
            'promotion', 
            '✨ Exclusive Upgrade Offer!', 
            "Upgrade your \"{$row['item_name']}\" seat to Directors Club for only ₱150! Limited spots left.", 
            'high',
            'Upgrade Now',
            "booking.php?movie=" . urlencode($row['item_name']) . "&upgrade=" . $row['booking_id'],
            1
        );
    }
}

echo json_encode(['success' => true]);
