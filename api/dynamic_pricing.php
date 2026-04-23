<?php
/**
 * Fixed Theatre Pricing for CineFlix
 * Returns a fixed ticket price based on cinema/theatre type.
 *
 * 2D/Standard = ₱350
 * 3D          = ₱420
 * IMAX        = ₱520
 * Directors Club = ₱650
 */

class DynamicPricing {
    private $theatrePrices = [
        'standard'       => 350,
        '2d'             => 350,
        '3d'             => 420,
        'imax'           => 520,
        'directors club' => 650,
        'directors'      => 650,
    ];
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function calculatePrice($movieTitle, $date, $time, $cinemaType) {
        $lower = strtolower(trim($cinemaType));

        foreach ($this->theatrePrices as $key => $price) {
            if (strpos($lower, $key) !== false) {
                return (float)$price;
            }
        }

        return 350.0; // Default: 2D/Standard
    }

    public function getPricingBreakdown($movieTitle, $date, $time, $cinemaType) {
        $price = $this->calculatePrice($movieTitle, $date, $time, $cinemaType);
        return [
            'base_price'  => $price,
            'final_price' => $price,
            'savings'     => 0,
            'factors'     => ['Fixed theatre price']
        ];
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_price') {
    require_once dirname(__DIR__) . '/includes/db.php';
    $conn = db_get_connection();
    $pricing = new DynamicPricing($conn);

    $movieTitle = $_GET['movie'] ?? '';
    $date       = $_GET['date']  ?? '';
    $time       = $_GET['time']  ?? '';
    $cinemaType = $_GET['cinema'] ?? '';

    $price     = $pricing->calculatePrice($movieTitle, $date, $time, $cinemaType);
    $breakdown = $pricing->getPricingBreakdown($movieTitle, $date, $time, $cinemaType);

    header('Content-Type: application/json');
    echo json_encode([
        'price'     => $price,
        'breakdown' => $breakdown
    ]);
    exit();
}
?>
