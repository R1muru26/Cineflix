<?php
/**
 * Live Seat Availability System
 * Real-time theater occupancy visualization
 */

class SeatAvailability {
    private $conn;
    private $totalSeats = 80;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get real-time seat availability for a show
     */
    public function getSeatAvailability($movieTitle, $date, $time, $cinema) {
        // Get booked seats from database
        $bookedSeats = $this->getBookedSeats($movieTitle, $date, $time, $cinema);
        
        // Generate seat layout
        $seatLayout = $this->generateSeatLayout($bookedSeats);
        
        // Calculate statistics
        $stats = [
            'total_seats' => $this->totalSeats,
            'available_seats' => $this->totalSeats - count($bookedSeats),
            'booked_seats' => count($bookedSeats),
            'occupancy_rate' => round((count($bookedSeats) / $this->totalSeats) * 100, 1),
            'best_available' => $this->findBestAvailableSeats($bookedSeats),
            'heat_map' => $this->generateHeatMap($bookedSeats)
        ];
        
        return [
            'layout' => $seatLayout,
            'statistics' => $stats,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getBookedSeats($movieTitle, $date, $time, $cinema) {
        // Query bookings table for this specific show
        $query = "SELECT seats FROM bookings WHERE 
                  item_name = ? AND 
                  show_date = ? AND 
                  show_time = ? AND 
                  venue LIKE ? AND 
                  status != 'Cancelled'";
        
        $stmt = $this->conn->prepare($query);
        $cinemaPattern = "%$cinema%";
        $stmt->bind_param("ssss", $movieTitle, $date, $time, $cinemaPattern);
        $stmt->execute();
        
        $bookedSeats = [];
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $seats = json_decode($row['seats'], true) ?? [];
            $bookedSeats = array_merge($bookedSeats, $seats);
        }
        
        return array_unique($bookedSeats);
    }
    
    private function generateSeatLayout($bookedSeats) {
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $seatsPerRow = 10;
        $layout = [];
        
        foreach ($rows as $row) {
            $rowLayout = [];
            for ($i = 1; $i <= $seatsPerRow; $i++) {
                $seatId = $row . $i;
                $rowLayout[] = [
                    'id' => $seatId,
                    'status' => in_array($seatId, $bookedSeats) ? 'booked' : 'available',
                    'quality' => $this->getSeatQuality($row, $i)
                ];
            }
            $layout[] = [
                'row' => $row,
                'seats' => $rowLayout
            ];
        }
        
        return $layout;
    }
    
    private function getSeatQuality($row, $seatNumber) {
        // Determine seat quality based on position
        $middleSeats = [4, 5, 6, 7]; // Best seats in the middle
        
        if (in_array($seatNumber, $middleSeats)) {
            return 'premium';
        } elseif (in_array($seatNumber, [3, 8])) {
            return 'good';
        } else {
            return 'standard';
        }
    }
    
    private function findBestAvailableSeats($bookedSeats) {
        $bestSeats = [];
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        
        // Find consecutive available seats in premium areas
        foreach ($rows as $row) {
            for ($i = 4; $i <= 7; $i++) { // Premium seats
                $seatId = $row . $i;
                if (!in_array($seatId, $bookedSeats)) {
                    $bestSeats[] = $seatId;
                    if (count($bestSeats) >= 4) break 2;
                }
            }
        }
        
        return $bestSeats;
    }
    
    private function generateHeatMap($bookedSeats) {
        $heatMap = [];
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        
        foreach ($rows as $row) {
            $rowHeat = [];
            for ($i = 1; $i <= 10; $i++) {
                $seatId = $row . $i;
                $heat = $this->calculateSeatHeat($seatId, $bookedSeats);
                $rowHeat[] = $heat;
            }
            $heatMap[] = $rowHeat;
        }
        
        return $heatMap;
    }
    
    private function calculateSeatHeat($seatId, $bookedSeats) {
        // Calculate "heat" based on nearby booked seats
        $row = $seatId[0];
        $seatNumber = (int)substr($seatId, 1);
        
        $nearbySeats = [
            $row . ($seatNumber - 1),
            $row . ($seatNumber + 1),
            chr(ord($row) - 1) . $seatNumber,
            chr(ord($row) + 1) . $seatNumber
        ];
        
        $bookedNearby = 0;
        foreach ($nearbySeats as $nearbySeat) {
            if (in_array($nearbySeat, $bookedSeats)) {
                $bookedNearby++;
            }
        }
        
        return min($bookedNearby * 25, 100); // 0-100 heat score
    }
    
    /**
     * Get real-time updates for WebSocket clients
     */
    public function getRealTimeUpdates($movieTitle, $date, $time, $cinema) {
        $availability = $this->getSeatAvailability($movieTitle, $date, $time, $cinema);
        
        return [
            'type' => 'seat_update',
            'data' => $availability,
            'timestamp' => time()
        ];
    }
}

// API endpoint for seat availability
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_seats') {
    require_once 'includes/db.php';
    $conn = db_get_connection();
    $seatAvailability = new SeatAvailability($conn);
    
    $movieTitle = $_GET['movie'] ?? '';
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $cinema = $_GET['cinema'] ?? '';
    
    $availability = $seatAvailability->getSeatAvailability($movieTitle, $date, $time, $cinema);
    
    header('Content-Type: application/json');
    echo json_encode($availability);
}
?>
