<?php
/**
 * SAFE Database Setup for Enhanced Features
 * This script only ADDS new tables, never deletes existing data
 */

require_once __DIR__ . '/includes/db.php';

echo "🎬 CineFlix SAFE Database Setup\n";
echo "=================================\n";
echo "⚠️  This script ONLY ADDS new tables - NO data will be deleted!\n\n";

$conn = db_get_connection();

// Check existing tables first
echo "1. Checking existing tables...\n";
$existingTables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $existingTables[] = $row[0];
}
echo "   ✓ Found " . count($existingTables) . " existing tables\n";

// Safe table creation - only creates if doesn't exist
echo "\n2. Creating new tables (safe mode)...\n";

// 1. Food Orders table
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_booking_id (booking_id),
        INDEX idx_status (status)
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created food_orders table\n";
    } else {
        echo "   ❌ Error creating food_orders: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ food_orders table already exists (skipped)\n";
}

// 2. User Notifications table
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (user_id, read_at),
        INDEX idx_expires (expires_at)
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created user_notifications table\n";
    } else {
        echo "   ❌ Error creating user_notifications: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ user_notifications table already exists (skipped)\n";
}

// 3. Promotions table
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
    
    if ($conn->query($sql)) {
        echo "   ✓ Created promotions table\n";
    } else {
        echo "   ❌ Error creating promotions: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ promotions table already exists (skipped)\n";
}

// 4. User Preferences table
if (!in_array('user_preferences', $existingTables)) {
    $sql = "CREATE TABLE user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        favorite_genres JSON,
        preferred_cinema_types JSON,
        preferred_show_times JSON,
        price_sensitivity ENUM('low', 'medium', 'high') DEFAULT 'medium',
        notification_preferences JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created user_preferences table\n";
    } else {
        echo "   ❌ Error creating user_preferences: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ user_preferences table already exists (skipped)\n";
}

// 5. Food Menu table
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created food_menu table\n";
    } else {
        echo "   ❌ Error creating food_menu: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ food_menu table already exists (skipped)\n";
}

// 6. Pricing History table
if (!in_array('pricing_history', $existingTables)) {
    $sql = "CREATE TABLE pricing_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        movie_title VARCHAR(255) NOT NULL,
        show_date DATE NOT NULL,
        show_time VARCHAR(50) NOT NULL,
        cinema_type VARCHAR(50) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        final_price DECIMAL(10,2) NOT NULL,
        occupancy_rate DECIMAL(5,2) NOT NULL,
        pricing_factors JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_movie_date (movie_title, show_date)
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created pricing_history table\n";
    } else {
        echo "   ❌ Error creating pricing_history: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ pricing_history table already exists (skipped)\n";
}

// 7. Seat Heat Data table
if (!in_array('seat_heat_data', $existingTables)) {
    $sql = "CREATE TABLE seat_heat_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        movie_title VARCHAR(255) NOT NULL,
        show_date DATE NOT NULL,
        show_time VARCHAR(50) NOT NULL,
        cinema_type VARCHAR(50) NOT NULL,
        seat_id VARCHAR(10) NOT NULL,
        heat_score INT DEFAULT 0,
        booking_count INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_show (movie_title, show_date, show_time),
        INDEX idx_seat (seat_id)
    )";
    
    if ($conn->query($sql)) {
        echo "   ✓ Created seat_heat_data table\n";
    } else {
        echo "   ❌ Error creating seat_heat_data: " . $conn->error . "\n";
    }
} else {
    echo "   ✓ seat_heat_data table already exists (skipped)\n";
}

// Safe column additions to existing tables
echo "\n3. Adding new columns to existing tables (safe mode)...\n";

// Add columns to bookings table if they don't exist
$bookingColumns = ['checked_in', 'checkin_time', 'entrance_validated', 'entrance_time', 'gate_number'];
foreach ($bookingColumns as $column) {
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
        
        if ($conn->query($sql)) {
            echo "   ✓ Added $column to bookings table\n";
        } else {
            echo "   ❌ Error adding $column: " . $conn->error . "\n";
        }
    } else {
        echo "   ✓ $column already exists in bookings (skipped)\n";
    }
}

// Insert sample data
echo "\n4. Adding sample data...\n";

// Sample food menu items
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

echo "   ✓ Added sample food menu items\n";

// Sample promotions
$promotions = [
    ['Tuesday Special', 'Get 20% off all tickets every Tuesday!', 'percentage', 20.00],
    ['Early Bird', 'Book 7+ days in advance and save 10%', 'percentage', 10.00],
    ['Matinee Special', 'All shows before 12 PM at discounted prices', 'percentage', 15.00]
];

foreach ($promotions as $promo) {
    $sql = "INSERT IGNORE INTO promotions (title, description, discount_type, discount_value, start_date, end_date) VALUES (?, ?, ?, ?, '2024-01-01 00:00:00', '2025-12-31 23:59:59')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $promo[0], $promo[1], $promo[2], $promo[3]);
    $stmt->execute();
}

echo "   ✓ Added sample promotions\n";

echo "\n✅ SAFE Database Setup Complete!\n";
echo "===============================\n";
echo "🎉 All new tables created without touching existing data!\n\n";
echo "Your movies and existing data are 100% safe.\n\n";
echo "Next step: Add the enhanced features to your pages.\n";
echo "Run: activate_features.php\n";
?>
