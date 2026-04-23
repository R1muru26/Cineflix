<?php
/**
 * Smart Notification System
 * Contextual alerts and reminders for CineFlix
 */

class SmartNotifications {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS user_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_id VARCHAR(50) NOT NULL UNIQUE,
            type VARCHAR(20) NOT NULL DEFAULT 'info',
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            action_text VARCHAR(50) NULL,
            action_url VARCHAR(255) NULL,
            read_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (user_id, read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->conn->query($sql);
    }
    
    /**
     * Generate contextual notifications for a user
     */
    public function generateNotifications($userId, $bookingId = null, $isAdmin = false) {
        $notifications = [];
        
        // 1. Fetch persistent notifications from database
        $notifications = array_merge($notifications, $this->getPersistentNotifications($userId, $isAdmin));
        
        // 2. Dynamic notifications (existing logic)
        // Get user's upcoming bookings
        $upcomingBookings = $this->getUpcomingBookings($userId);
        
        foreach ($upcomingBookings as $booking) {
            $notifications = array_merge($notifications, $this->generateBookingNotifications($booking));
        }
        
        // Get personalized recommendations
        $notifications = array_merge($notifications, $this->generateRecommendations($userId));
        
        // Get system notifications
        $notifications = array_merge($notifications, $this->generateSystemNotifications());
        
        // Filter out read notifications (for dynamic ones)
        return $this->filterReadNotifications($userId, $notifications);
    }

    /**
     * Fetch persistent notifications from database
     */
    private function getPersistentNotifications($userId, $isAdmin = false) {
        $notifications = [];
        
        // Fetch notifications for specific user OR for all admins if current user is admin
        $whereClause = "user_id = ? AND read_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())";
        if ($isAdmin) {
            $whereClause = "(user_id = ? OR user_id = 0) AND read_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())";
        }
        
        $query = "SELECT * FROM user_notifications WHERE $whereClause ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['notification_id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'message' => $row['message'],
                'priority' => $row['priority'],
                'action_text' => $row['action_text'],
                'action_url' => $row['action_url'],
                'created_at' => $row['created_at'],
                'persistent' => true,
                'db_id' => $row['id']
            ];
        }
        
        return $notifications;
    }

    /**
     * Create a new persistent notification
     */
    public function createNotification($userId, $type, $title, $message, $priority = 'medium', $actionText = null, $actionUrl = null, $expiresInDays = 30) {
        $notificationId = uniqid($type . '_');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresInDays days"));
        
        $query = "INSERT INTO user_notifications (user_id, notification_id, type, title, message, priority, action_text, action_url, expires_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->bind_param("issssssss", $userId, $notificationId, $type, $title, $message, $priority, $actionText, $actionUrl, $expiresAt);
    }

    /**
     * Filter out notifications that have been marked as read
     */
    private function filterReadNotifications($userId, $notifications) {
        if (empty($notifications)) return [];
        
        $notificationIds = array_column($notifications, 'id');
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        
        $query = "SELECT notification_id FROM user_notifications WHERE user_id = ? AND notification_id IN ($placeholders) AND read_at IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        
        $types = "i" . str_repeat("s", count($notificationIds));
        $params = array_merge([$userId], $notificationIds);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $readIds = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $readIds[] = $row['notification_id'];
        }
        
        return array_values(array_filter($notifications, function($n) use ($readIds) {
            return !in_array($n['id'], $readIds);
        }));
    }

    private function generateBookingNotifications($booking) {
        $notifications = [];
        $showDateTime = new DateTime($booking['show_date'] . ' ' . explode(' - ', $booking['show_time'])[0]);
        $now = new DateTime();
        $hoursUntilShow = ($showDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
        
        // Traffic alert (2 hours before)
        if ($hoursUntilShow > 1.5 && $hoursUntilShow < 2.5) {
            $trafficData = $this->getTrafficData($booking['venue']);
            if ($trafficData['heavy']) {
                $notifications[] = [
                    'id' => 'traffic_' . $booking['booking_id'],
                    'type' => 'alert',
                    'title' => '⚠️ Heavy Traffic Alert',
                    'message' => "Traffic is heavy near {$booking['venue']}. Consider leaving 15 minutes earlier.",
                    'priority' => 'high',
                    'action_text' => 'Get Directions',
                    'action_url' => "https://maps.google.com/?q=CineFlix+{$booking['venue']}",
                    'expires_at' => $showDateTime->format('Y-m-d H:i:s')
                ];
            }
        }
        
        // Check-in reminder (1 hour before)
        if ($hoursUntilShow > 0.5 && $hoursUntilShow < 1.5) {
            $notifications[] = [
                'id' => 'checkin_' . $booking['booking_id'],
                'type' => 'reminder',
                'title' => '🎬 Time to Check In',
                'message' => "Your movie \"{$booking['item_name']}\" starts soon. Check in now and skip the queue!",
                'priority' => 'medium',
                'action_text' => 'Check In Now',
                'action_url' => "mobile_checkin.php?booking=" . $booking['booking_id'],
                'expires_at' => $showDateTime->format('Y-m-d H:i:s')
            ];
        }
        
        // Don't forget reminders (24 hours before)
        if ($hoursUntilShow > 23 && $hoursUntilShow < 25) {
            $notifications[] = [
                'id' => 'reminder_' . $booking['booking_id'],
                'type' => 'reminder',
                'title' => '🎟️ Movie Tomorrow',
                'message' => "Don't forget! \"{$booking['item_name']}\" tomorrow at {$booking['show_time']}",
                'priority' => 'low',
                'action_text' => 'View Ticket',
                'action_url' => "status.php#booking-" . $booking['booking_id'],
                'expires_at' => $showDateTime->format('Y-m-d H:i:s')
            ];
        }
        
        // Special format reminder
        if (strpos($booking['venue'], '3D') !== false || strpos($booking['venue'], 'IMAX') !== false) {
            if ($hoursUntilShow > 1 && $hoursUntilShow < 3) {
                $notifications[] = [
                    'id' => 'format_' . $booking['booking_id'],
                    'type' => 'info',
                    'title' => '🥽 3D/IMAX Reminder',
                    'message' => "Don't forget your 3D glasses for this special format screening!",
                    'priority' => 'medium',
                    'action_text' => 'Learn More',
                    'action_url' => "#",
                    'expires_at' => $showDateTime->format('Y-m-d H:i:s')
                ];
            }
        }
        
        return $notifications;
    }
    
    private function generateRecommendations($userId) {
        $recommendations = [];
        
        // Get user's viewing history
        $history = $this->getUserViewingHistory($userId);
        $favoriteGenres = $this->analyzeFavoriteGenres($history);
        
        // Get upcoming movies in favorite genres
        $upcomingMovies = $this->getUpcomingMoviesByGenre($favoriteGenres);
        
        foreach ($upcomingMovies as $movie) {
            $recommendations[] = [
                'id' => 'rec_' . $movie['id'],
                'type' => 'recommendation',
                'title' => '🎬 New Movie Alert',
                'message' => "\"{$movie['title']}\" is coming soon! Based on your love for {$movie['genre']} movies.",
                'priority' => 'low',
                'action_text' => 'Set Reminder',
                'action_url' => "comingsoon.php#movie-" . $movie['id'],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ];
        }
        
        return $recommendations;
    }
    
    private function generateSystemNotifications() {
        $notifications = [];
        
        // Special promotions
        $promotions = $this->getActivePromotions();
        foreach ($promotions as $promo) {
            $notifications[] = [
                'id' => 'promo_' . $promo['id'],
                'type' => 'promotion',
                'title' => '🎉 Special Offer',
                'message' => $promo['message'],
                'priority' => 'medium',
                'action_text' => 'Book Now',
                'action_url' => $promo['url'],
                'expires_at' => $promo['expires_at']
            ];
        }
        
        // New features
        $notifications[] = [
            'id' => 'feature_food_ordering',
            'type' => 'feature',
            'title' => '🍟 New Feature',
            'message' => 'Order food from your seat! Scan the QR code in the cinema.',
            'priority' => 'low',
            'action_text' => 'Learn More',
            'action_url' => '#',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];
        
        return $notifications;
    }
    
    private function getUpcomingBookings($userId) {
        $query = "SELECT * FROM bookings WHERE customer_id = ? AND show_date >= CURDATE() AND status != 'Cancelled' ORDER BY show_date, show_time";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $bookings = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    private function getTrafficData($venue) {
        // Simulated traffic data - in real implementation, this would call a traffic API
        $hour = (int)date('H');
        $heavyTrafficHours = [7, 8, 9, 17, 18, 19]; // Rush hours
        
        return [
            'heavy' => in_array($hour, $heavyTrafficHours),
            'delay_minutes' => in_array($hour, $heavyTrafficHours) ? 15 : 5
        ];
    }
    
    private function getUserViewingHistory($userId) {
        $query = "SELECT item_name FROM bookings WHERE customer_id = ? AND status = 'Completed' ORDER BY created_at DESC LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $history = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row['item_name'];
        }
        
        return $history;
    }
    
    private function analyzeFavoriteGenres($history) {
        // Simple genre analysis based on movie titles
        $genres = [];
        
        foreach ($history as $movieTitle) {
            if (strpos(strtolower($movieTitle), 'action') !== false) $genres[] = 'Action';
            if (strpos(strtolower($movieTitle), 'horror') !== false) $genres[] = 'Horror';
            if (strpos(strtolower($movieTitle), 'comedy') !== false) $genres[] = 'Comedy';
            if (strpos(strtolower($movieTitle), 'romance') !== false) $genres[] = 'Romance';
        }
        
        return array_count_values($genres);
    }
    
    private function getUpcomingMoviesByGenre($favoriteGenres) {
        // Simulated upcoming movies
        return [
            ['id' => 1, 'title' => 'Action Hero 2', 'genre' => 'Action'],
            ['id' => 2, 'title' => 'Comedy Night', 'genre' => 'Comedy']
        ];
    }
    
    private function getActivePromotions() {
        return [
            [
                'id' => 1,
                'message' => 'Tuesday Special: 20% off all tickets!',
                'url' => 'booking.php',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ]
        ];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($userId, $notificationId) {
        // First, check if it's an existing persistent notification
        $checkQuery = "SELECT id FROM user_notifications WHERE user_id = ? AND notification_id = ?";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bind_param("is", $userId, $notificationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            // Update existing persistent notification
            $updateQuery = "UPDATE user_notifications SET read_at = NOW() WHERE user_id = ? AND notification_id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            return $stmt->bind_param("is", $userId, $notificationId)->execute();
        } else {
            // Insert new read record for dynamic notification
            // We need some default values if we're inserting into the full table
            $query = "INSERT INTO user_notifications (user_id, notification_id, type, title, message, read_at) 
                      VALUES (?, ?, 'alert', 'Read Notification', 'Dynamic notification marked as read', NOW())";
            $stmt = $this->conn->prepare($query);
            return $stmt->bind_param("is", $userId, $notificationId);
        }
    }
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once __DIR__ . '/../includes/db.php';
    $conn = db_get_connection();
    
    $userId = $_SESSION['user_id'] ?? 0;
    $isAdmin = !empty($_SESSION['is_admin']);
    $notifications = new SmartNotifications($conn);
    
    $userNotifications = $notifications->generateNotifications($userId, null, $isAdmin);
    
    header('Content-Type: application/json');
    echo json_encode($userNotifications);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once __DIR__ . '/../includes/db.php';
    $conn = db_get_connection();
    
    $userId = $_SESSION['user_id'] ?? 0;
    $notificationId = $_POST['notification_id'] ?? '';
    
    $notifications = new SmartNotifications($conn);
    $result = $notifications->markAsRead($userId, $notificationId);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
}
?>
