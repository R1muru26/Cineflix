<?php
/**
 * Quick Database Setup
 */

require_once __DIR__ . '/includes/db.php';

echo "Setting up database tables...\n";

$conn = db_get_connection();

// Check existing tables
$result = $conn->query("SHOW TABLES");
$existingTables = [];
while ($row = $result->fetch_array()) {
    $existingTables[] = $row[0];
}

// Create food_orders table
if (!in_array('food_orders', $existingTables)) {
    $sql = "CREATE TABLE food_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL UNIQUE,
        booking_id VARCHAR(50) NOT NULL,
        seat_number VARCHAR(10) NOT NULL,
        items JSON NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        status ENUM('preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'preparing',
        preparation_time INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        delivery_time TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "✓ Created food_orders table\n";
}

// Create user_notifications table
if (!in_array('user_notifications', $existingTables)) {
    $sql = "CREATE TABLE user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_id VARCHAR(100) NOT NULL,
        type ENUM('alert', 'reminder', 'promotion', 'recommendation', 'feature') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        action_text VARCHAR(100),
        action_url VARCHAR(500),
        read_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "✓ Created user_notifications table\n";
}

// Create promotions table
if (!in_array('promotions', $existingTables)) {
    $sql = "CREATE TABLE promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        discount_type ENUM('percentage', 'fixed', 'buy_one_get_one') NOT NULL,
        discount_value DECIMAL(10,2) NOT NULL,
        conditions JSON,
        start_date TIMESTAMP NOT NULL,
        end_date TIMESTAMP NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "✓ Created promotions table\n";
}

// Create food_menu table
if (!in_array('food_menu', $existingTables)) {
    $sql = "CREATE TABLE food_menu (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        item_id VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        preparation_time INT DEFAULT 0,
        available BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "✓ Created food_menu table\n";
}

// Add columns to bookings table if they don't exist
$columnsToAdd = ['checked_in', 'checkin_time', 'entrance_validated', 'entrance_time', 'gate_number'];
foreach ($columnsToAdd as $column) {
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE '$column'");
    if ($result->num_rows == 0) {
        switch ($column) {
            case 'checked_in':
                $sql = "ALTER TABLE bookings ADD COLUMN checked_in BOOLEAN DEFAULT FALSE";
                break;
            case 'checkin_time':
                $sql = "ALTER TABLE bookings ADD COLUMN checkin_time TIMESTAMP NULL";
                break;
            case 'entrance_validated':
                $sql = "ALTER TABLE bookings ADD COLUMN entrance_validated BOOLEAN DEFAULT FALSE";
                break;
            case 'entrance_time':
                $sql = "ALTER TABLE bookings ADD COLUMN entrance_time TIMESTAMP NULL";
                break;
            case 'gate_number':
                $sql = "ALTER TABLE bookings ADD COLUMN gate_number VARCHAR(10)";
                break;
        }
        $conn->query($sql);
        echo "✓ Added $column to bookings table\n";
    }
}

// Insert sample food menu items
$foodItems = [
    ['popcorn', 'pop_s', 'Small Popcorn', 'Buttered popcorn, small size', 120, 3],
    ['popcorn', 'pop_m', 'Medium Popcorn', 'Buttered popcorn, medium size', 150, 3],
    ['popcorn', 'pop_l', 'Large Popcorn', 'Buttered popcorn, large size', 180, 3],
    ['drinks', 'coke_s', 'Coke (Small)', 'Coca-Cola, 12oz', 80, 1],
    ['drinks', 'coke_m', 'Coke (Medium)', 'Coca-Cola, 16oz', 100, 1],
    ['drinks', 'coke_l', 'Coke (Large)', 'Coca-Cola, 20oz', 120, 1],
    ['combos', 'combo1', 'Classic Combo', 'Medium Popcorn + Medium Coke', 200, 3],
    ['combos', 'combo2', 'Deluxe Combo', 'Large Popcorn + Large Coke + Nachos', 280, 5]
];

foreach ($foodItems as $item) {
    $sql = "INSERT IGNORE INTO food_menu (category, item_id, name, description, price, preparation_time) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdi", $item[0], $item[1], $item[2], $item[3], $item[4], $item[5]);
    $stmt->execute();
}

echo "✓ Added sample food menu items\n";

// Insert sample promotions
$promotions = [
    ['Tuesday Special', 'Get 20% off all tickets every Tuesday!', 'percentage', 20.00],
    ['Early Bird', 'Book 7+ days in advance and save 10%', 'percentage', 10.00],
    ['Weekend Special', '15% off all weekend tickets!', 'percentage', 15.00]
];

foreach ($promotions as $promo) {
    $sql = "INSERT IGNORE INTO promotions (title, description, discount_type, discount_value, start_date, end_date) VALUES (?, ?, ?, ?, '2024-01-01 00:00:00', '2025-12-31 23:59:59')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $promo[0], $promo[1], $promo[2], $promo[3]);
    $stmt->execute();
}

echo "✓ Added sample promotions\n";

echo "\n🎉 Database setup complete!\n";
echo "Your existing data is 100% safe.\n";
?>
