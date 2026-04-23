<?php
/**
 * Quick Database Connection Test
 */

echo "<h2>🔍 Database Connection Test</h2>";

try {
    require_once __DIR__ . '/includes/db.php';
    $conn = db_get_connection();
    
    if ($conn) {
        echo "<p>✅ Database connection successful!</p>";
        
        // Test a simple query
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            echo "<p>✅ Database query working!</p>";
        } else {
            echo "<p>❌ Database query failed: " . $conn->error . "</p>";
        }
        
        // Show existing tables
        $result = $conn->query("SHOW TABLES");
        echo "<p>✅ Existing tables: " . $result->num_rows . "</p>";
        
        echo "<p><strong>Everything looks good! Try running:</strong></p>";
        echo "<p><a href='simple_setup.php'>simple_setup.php</a></p>";
        
    } else {
        echo "<p>❌ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your includes/db.php file</p>";
}
?>
