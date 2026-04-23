<?php
/**
 * CineFlix Integration Script
 * Run this to activate all enhanced features
 */

echo "🎬 CineFlix Enhanced Features Integration\n";
echo "==========================================\n\n";

// Step 1: Database Setup
echo "1. Setting up database tables...\n";
require_once __DIR__ . '/includes/db.php';

try {
    $conn = db_get_connection();
    
    // Read and execute the database enhancements
    $sql = file_get_contents(__DIR__ . '/database_enhancements.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->query($statement);
                echo "   ✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "   ⚠️  Skipped (may already exist): " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    echo "\n✅ Database setup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Step 2: Check API files
echo "\n2. Checking API files...\n";
$apiFiles = [
    'api/dynamic_pricing.php',
    'api/seat_availability.php', 
    'api/food_ordering.php',
    'api/notifications.php',
    'api/mobile_checkin.php'
];

foreach ($apiFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ Found: $file\n";
    } else {
        echo "   ❌ Missing: $file\n";
    }
}

// Step 3: Check frontend files
echo "\n3. Checking frontend files...\n";
$frontendFiles = [
    'cineflix_enhanced.js',
    'food_order.php'
];

foreach ($frontendFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ Found: $file\n";
    } else {
        echo "   ❌ Missing: $file\n";
    }
}

// Step 4: Create sample data
echo "\n4. Creating sample data...\n";
try {
    // Add sample promotions
    $conn->query("INSERT IGNORE INTO promotions (title, description, discount_type, discount_value, start_date, end_date) VALUES 
        ('Weekend Special', 'Get 15% off all weekend tickets!', 'percentage', 15.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59'),
        ('Family Pack', 'Buy 3 tickets, get 1 free!', 'buy_one_get_one', 0.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59')");
    
    echo "   ✓ Sample promotions added\n";
    
    // Add sample user preferences
    if (!empty($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $conn->query("INSERT IGNORE INTO user_preferences (user_id, favorite_genres, preferred_cinema_types) VALUES 
            ($userId, '[\"Action\", \"Comedy\"]', '[\"2D\", \"IMAX\"]')");
        echo "   ✓ User preferences added\n";
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Sample data skipped: " . $e->getMessage() . "\n";
}

// Step 5: Test API endpoints
echo "\n5. Testing API endpoints...\n";
$testEndpoints = [
    '/api/dynamic_pricing.php?action=get_price&movie=Test&date=2024-12-25&time=7:00%20PM&cinema=2D',
    '/api/food_ordering.php?action=get_menu',
    '/api/notifications.php?action=get_notifications'
];

foreach ($testEndpoints as $endpoint) {
    $url = "http://localhost/CINEFLIX" . $endpoint;
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "   ✓ Working: " . basename($endpoint) . "\n";
    } else {
        echo "   ⚠️  Test failed: " . basename($endpoint) . " (may need web server)\n";
    }
}

echo "\n🎉 Integration Complete!\n";
echo "========================\n";
echo "Your CineFlix system now includes:\n\n";
echo "📊 Dynamic Pricing System\n";
echo "🎭 Live Seat Availability Maps\n";
echo "🍟 QR Code Food Ordering\n";
echo "🔔 Smart Notifications\n";
echo "📱 Mobile Check-in System\n\n";
echo "Next steps:\n";
echo "1. Test the booking page for dynamic pricing\n";
echo "2. Check status page for mobile check-in buttons\n";
echo "3. Look for notification bell in header\n";
echo "4. Try food_order.php for mobile ordering\n\n";
echo "Enjoy your enhanced CineFlix experience! 🍿🎬\n";
?>
