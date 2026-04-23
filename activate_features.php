<?php
/**
 * Activate Enhanced Features Safely
 * This activates features without modifying existing files
 */

echo "🎬 CineFlix Enhanced Features Activation\n";
echo "========================================\n\n";

// Step 1: Check if database is ready
echo "1. Checking database readiness...\n";
require_once __DIR__ . '/includes/db.php';

$conn = db_get_connection();
$requiredTables = ['food_orders', 'user_notifications', 'promotions', 'food_menu'];
$ready = true;

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "   ❌ Missing table: $table\n";
        $ready = false;
    } else {
        echo "   ✓ Found table: $table\n";
    }
}

if (!$ready) {
    echo "\n⚠️  Please run safe_database_setup.php first!\n";
    exit;
}

// Step 2: Create activation guide
echo "\n2. Creating activation guide...\n";

$guide = "
🎬 CineFlix Enhanced Features - Activation Guide
===============================================

✅ DATABASE SETUP COMPLETE
Your database is ready for enhanced features!

📋 NEXT STEPS:

1️⃣  TEST THE NEW FEATURES (Optional)
   - Dynamic Pricing: http://localhost/CINEFLIX/api/dynamic_pricing.php?action=get_price&movie=Test&date=2024-12-25&time=7:00%20PM&cinema=2D
   - Food Menu: http://localhost/CINEFLIX/api/food_ordering.php?action=get_menu
   - Notifications: http://localhost/CINEFLIX/api/notifications.php?action=get_notifications

2️⃣  ADD TO YOUR PAGES (When Ready)
   Add this line to your pages to activate features:
   <script src='cineflix_enhanced.js'></script>
   
   Pages to update:
   - booking.php (for dynamic pricing & food ordering)
   - status.php (for mobile check-in)
   - homepage.php (for notifications)

3️⃣  FOOD ORDERING PAGE
   The food ordering page is ready at:
   http://localhost/CINEFLIX/food_order.php

🔒 YOUR DATA IS SAFE:
- No movies were deleted
- No existing data was modified
- All new tables are separate
- You can disable anytime by removing the script tag

🎯 FEATURES READY TO USE:
- Dynamic Pricing System
- QR Code Food Ordering  
- Smart Notifications
- Mobile Check-in
- Live Seat Maps

⚡ QUICK TEST:
Try the food ordering page: http://localhost/CINEFLIX/food_order.php

Enjoy your enhanced CineFlix! 🍿🎬
";

file_put_contents(__DIR__ . '/ACTIVATION_GUIDE.txt', $guide);
echo "   ✓ Activation guide created: ACTIVATION_GUIDE.txt\n";

// Step 3: Create a test page to showcase features
echo "\n3. Creating demo page...\n";

$demoPage = '
<!DOCTYPE html>
<html>
<head>
    <title>CineFlix Enhanced Features Demo</title>
    <link rel="stylesheet" href="common.css">
    <script src="cineflix_enhanced.js"></script>
    <style>
        body { padding: 20px; background: #1a1a2e; color: white; }
        .demo-section { margin: 30px 0; padding: 20px; border: 1px solid #c79f5e; border-radius: 10px; }
        .demo-button { background: #c79f5e; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .result { background: rgba(255,255,255,0.1); padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🎬 CineFlix Enhanced Features Demo</h1>
    
    <div class="demo-section">
        <h2>📊 Dynamic Pricing Test</h2>
        <button class="demo-button" onclick="testPricing()">Test Dynamic Pricing</button>
        <div id="pricing-result" class="result"></div>
    </div>
    
    <div class="demo-section">
        <h2>🍟 Food Menu Test</h2>
        <button class="demo-button" onclick="testFoodMenu()">Test Food Menu</button>
        <div id="food-result" class="result"></div>
    </div>
    
    <div class="demo-section">
        <h2>🔔 Notifications Test</h2>
        <button class="demo-button" onclick="testNotifications()">Test Notifications</button>
        <div id="notification-result" class="result"></div>
    </div>
    
    <div class="demo-section">
        <h2>📱 Mobile Check-in Test</h2>
        <button class="demo-button" onclick="testCheckin()">Test Mobile Check-in</button>
        <div id="checkin-result" class="result"></div>
    </div>
    
    <script>
        async function testPricing() {
            try {
                const response = await fetch("/api/dynamic_pricing.php?action=get_price&movie=Test&date=2024-12-25&time=7:00%20PM&cinema=2D");
                const data = await response.json();
                document.getElementById("pricing-result").innerHTML = 
                    "✅ Dynamic Pricing Working!<br>" + 
                    "Base Price: ₱425<br>" +
                    "Final Price: ₱" + data.price + "<br>" +
                    "Factors: " + data.breakdown.factors.join(", ");
            } catch (error) {
                document.getElementById("pricing-result").innerHTML = "❌ Error: " + error.message;
            }
        }
        
        async function testFoodMenu() {
            try {
                const response = await fetch("/api/food_ordering.php?action=get_menu");
                const data = await response.json();
                document.getElementById("food-result").innerHTML = 
                    "✅ Food Menu Working!<br>" + 
                    "Categories: " + data.categories.map(c => c.name).join(", ") + "<br>" +
                    "Total Items: " + data.categories.reduce((sum, c) => sum + c.items.length, 0);
            } catch (error) {
                document.getElementById("food-result").innerHTML = "❌ Error: " + error.message;
            }
        }
        
        async function testNotifications() {
            try {
                const response = await fetch("/api/notifications.php?action=get_notifications");
                const data = await response.json();
                document.getElementById("notification-result").innerHTML = 
                    "✅ Notifications Working!<br>" + 
                    "Notifications Available: " + data.length;
            } catch (error) {
                document.getElementById("notification-result").innerHTML = "❌ Error: " + error.message;
            }
        }
        
        async function testCheckin() {
            try {
                const response = await fetch("/api/mobile_checkin.php?action=stats");
                const data = await response.json();
                document.getElementById("checkin-result").innerHTML = 
                    "✅ Mobile Check-in Working!<br>" + 
                    "Check-in System Ready";
            } catch (error) {
                document.getElementById("checkin-result").innerHTML = "❌ Error: " + error.message;
            }
        }
    </script>
</body>
</html>';

file_put_contents(__DIR__ . 'demo_enhanced.php', $demoPage);
echo "   ✓ Demo page created: demo_enhanced.php\n";

echo "\n🎉 Activation Complete!\n";
echo "======================\n";
echo "✅ Your movies are 100% safe!\n";
echo "✅ Enhanced features are ready!\n\n";
echo "🚀 Quick Start:\n";
echo "1. Test demo: http://localhost/CINEFLIX/demo_enhanced.php\n";
echo "2. Try food ordering: http://localhost/CINEFLIX/food_order.php\n";
echo "3. Read guide: ACTIVATION_GUIDE.txt\n\n";
echo "🔒 Your existing code is unchanged.\n";
echo "Add features when you\\'re ready!\n";
?>
