<?php
/**
 * Simple Database Setup with Error Handling
 */

echo "<h2>🎬 CineFlix Database Setup</h2>";
echo "<p>Setting up enhanced features database...</p>";

try {
    require_once __DIR__ . '/includes/db.php';
    $conn = db_get_connection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check existing tables
    $result = $conn->query("SHOW TABLES");
    $existingTables = [];
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
    echo "<p>✅ Found " . count($existingTables) . " existing tables</p>";
    
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "<p>✅ Created food_orders table</p>";
        } else {
            echo "<p>❌ Error creating food_orders: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ food_orders table already exists</p>";
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "<p>✅ Created food_menu table</p>";
        } else {
            echo "<p>❌ Error creating food_menu: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ food_menu table already exists</p>";
    }
    
    // Add sample food items
    $foodItems = [
        ['popcorn', 'pop_s', 'Small Popcorn', 'Buttered popcorn, small size', 120],
        ['popcorn', 'pop_m', 'Medium Popcorn', 'Buttered popcorn, medium size', 150],
        ['popcorn', 'pop_l', 'Large Popcorn', 'Buttered popcorn, large size', 180],
        ['drinks', 'coke_s', 'Coke (Small)', 'Coca-Cola, 12oz', 80],
        ['drinks', 'coke_m', 'Coke (Medium)', 'Coca-Cola, 16oz', 100],
        ['drinks', 'coke_l', 'Coke (Large)', 'Coca-Cola, 20oz', 120]
    ];
    
    foreach ($foodItems as $item) {
        $sql = "INSERT IGNORE INTO food_menu (category, item_id, name, description, price, preparation_time) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdi", $item[0], $item[1], $item[2], $item[3], $item[4], $item[5]);
        $stmt->execute();
    }
    echo "<p>✅ Added sample food menu items</p>";
    
    echo "<h3>🎉 Database Setup Complete!</h3>";
    echo "<p>Your existing data is 100% safe.</p>";
    echo "<p><a href='booking.php'>Test the booking page</a></p>";
    echo "<p><a href='demo_enhanced.php'>Test the demo page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection in includes/db.php</p>";
}
?>
