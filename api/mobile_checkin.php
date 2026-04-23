<?php
/**
 * Mobile Check-in System
 * Skip the counter with digital ticket validation
 */

class MobileCheckin {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Process mobile check-in
     */
    public function processCheckin($bookingId, $userId) {
        // Verify booking ownership
        $booking = $this->verifyBooking($bookingId, $userId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Invalid booking or user'];
        }
        
        // Check if already checked in
        if ($booking['checked_in']) {
            return ['success' => false, 'message' => 'Already checked in'];
        }
        
        // Check if it's time to check in (within 2 hours of showtime)
        $showDateTime = new DateTime($booking['show_date'] . ' ' . explode(' - ', $booking['show_time'])[0]);
        $now = new DateTime();
        $hoursUntilShow = ($showDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
        
        if ($hoursUntilShow > 2) {
            return ['success' => false, 'message' => 'Check-in available 2 hours before showtime'];
        }
        
        if ($hoursUntilShow < 0) {
            return ['success' => false, 'message' => 'Showtime has passed'];
        }
        
        // Process check-in
        $this->updateCheckinStatus($bookingId);
        
        // Generate digital ticket
        $digitalTicket = $this->generateDigitalTicket($booking);
        
        return [
            'success' => true,
            'message' => 'Check-in successful!',
            'ticket' => $digitalTicket,
            'gate_number' => $this->assignGate($booking),
            'qr_code' => $this->generateCheckinQR($bookingId)
        ];
    }
    
    /**
     * Validate digital ticket at entrance
     */
    public function validateTicket($bookingId, $qrData) {
        $booking = $this->getBookingByQR($qrData);
        
        if (!$booking || $booking['booking_id'] !== $bookingId) {
            return ['valid' => false, 'message' => 'Invalid ticket'];
        }
        
        if (!$booking['checked_in']) {
            return ['valid' => false, 'message' => 'Not checked in yet'];
        }
        
        // Check if already used
        if ($booking['entrance_validated']) {
            return ['valid' => false, 'message' => 'Ticket already used'];
        }
        
        // Validate showtime
        $showDateTime = new DateTime($booking['show_date'] . ' ' . explode(' - ', $booking['show_time'])[0]);
        $now = new DateTime();
        $minutesUntilShow = ($showDateTime->getTimestamp() - $now->getTimestamp()) / 60;
        
        if ($minutesUntilShow > 30) {
            return ['valid' => false, 'message' => 'Too early to enter'];
        }
        
        if ($minutesUntilShow < -15) {
            return ['valid' => false, 'message' => 'Showtime passed (15min grace period)'];
        }
        
        // Mark as validated
        $this->markAsValidated($bookingId);
        
        return [
            'valid' => true,
            'message' => 'Welcome to CineFlix!',
            'booking' => $booking,
            'seat_info' => $this->getSeatInfo($booking)
        ];
    }
    
    private function verifyBooking($bookingId, $userId) {
        $query = "SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ? AND status = 'Paid'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $bookingId, $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function updateCheckinStatus($bookingId) {
        $query = "UPDATE bookings SET checked_in = 1, checkin_time = NOW() WHERE booking_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $bookingId);
        return $stmt->execute();
    }
    
    private function generateDigitalTicket($booking) {
        return [
            'booking_id' => $booking['booking_id'],
            'movie_title' => $booking['item_name'],
            'show_date' => date('F j, Y', strtotime($booking['show_date'])),
            'show_time' => $booking['show_time'],
            'cinema' => $booking['venue'],
            'seats' => json_decode($booking['seats'], true),
            'customer_name' => $booking['customer_name'],
            'checkin_time' => date('h:i A'),
            'barcode' => $this->generateBarcode($booking['booking_id']),
            'security_code' => $this->generateSecurityCode()
        ];
    }
    
    private function assignGate($booking) {
        // Assign gate based on cinema type
        if (strpos($booking['venue'], 'IMAX') !== false) {
            return 'Gate A';
        } elseif (strpos($booking['venue'], '3D') !== false) {
            return 'Gate B';
        } else {
            return 'Gate C';
        }
    }
    
    private function generateCheckinQR($bookingId) {
        $qrData = [
            'type' => 'cinema_ticket',
            'booking_id' => $bookingId,
            'timestamp' => time(),
            'validated' => false
        ];
        
        // This would use a QR code library
        return base64_encode(json_encode($qrData));
    }
    
    private function getBookingByQR($qrData) {
        try {
            $decoded = json_decode(base64_decode($qrData), true);
            if ($decoded['type'] === 'cinema_ticket') {
                $query = "SELECT * FROM bookings WHERE booking_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s", $decoded['booking_id']);
                $stmt->execute();
                return $stmt->get_result()->fetch_assoc();
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }
    
    private function markAsValidated($bookingId) {
        $query = "UPDATE bookings SET entrance_validated = 1, entrance_time = NOW() WHERE booking_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $bookingId);
        return $stmt->execute();
    }
    
    private function getSeatInfo($booking) {
        $seats = json_decode($booking['seats'], true);
        return [
            'row' => substr($seats[0], 0, 1),
            'seat_numbers' => array_map(function($seat) {
                return substr($seat, 1);
            }, $seats),
            'section' => $this->getSeatSection($seats[0])
        ];
    }
    
    private function getSeatSection($seat) {
        $row = substr($seat, 0, 1);
        $sections = [
            'A', 'B' => 'Front',
            'C', 'D', 'E' => 'Middle',
            'F', 'G', 'H' => 'Back'
        ];
        
        foreach ($sections as $sectionRows => $section) {
            if (strpos($sectionRows, $row) !== false) {
                return $section;
            }
        }
        
        return 'Middle';
    }
    
    private function generateBarcode($bookingId) {
        // Generate a simple barcode representation
        return 'CF' . str_pad($bookingId, 8, '0', STR_PAD_LEFT);
    }
    
    private function generateSecurityCode() {
        return strtoupper(substr(md5(uniqid()), 0, 6));
    }
    
    /**
     * Get check-in statistics
     */
    public function getCheckinStats($date = null) {
        $date = $date ?: date('Y-m-d');
        
        $query = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN checked_in = 1 THEN 1 ELSE 0 END) as checked_in,
                    SUM(CASE WHEN entrance_validated = 1 THEN 1 ELSE 0 END) as entered
                  FROM bookings 
                  WHERE show_date = ? AND status = 'Paid'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'total_bookings' => (int)$result['total_bookings'],
            'checked_in' => (int)$result['checked_in'],
            'entered' => (int)$result['entered'],
            'checkin_rate' => $result['total_bookings'] > 0 ? 
                round(($result['checked_in'] / $result['total_bookings']) * 100, 1) : 0,
            'arrival_rate' => $result['checked_in'] > 0 ? 
                round(($result['entered'] / $result['checked_in']) * 100, 1) : 0
        ];
    }
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    require_once 'includes/db.php';
    $conn = db_get_connection();
    $checkin = new MobileCheckin($conn);
    
    switch ($_POST['action']) {
        case 'checkin':
            $bookingId = $_POST['booking_id'] ?? '';
            $userId = $_SESSION['user_id'] ?? 0;
            $result = $checkin->processCheckin($bookingId, $userId);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'validate':
            $bookingId = $_POST['booking_id'] ?? '';
            $qrData = $_POST['qr_data'] ?? '';
            $result = $checkin->validateTicket($bookingId, $qrData);
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    require_once 'includes/db.php';
    $conn = db_get_connection();
    $checkin = new MobileCheckin($conn);
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $stats = $checkin->getCheckinStats($date);
    
    header('Content-Type: application/json');
    echo json_encode($stats);
}
?>
