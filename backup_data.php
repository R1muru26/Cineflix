<?php
/**
 * Backup Movie Data Before Activation
 * This creates a backup of all your movie data
 */

require_once __DIR__ . '/includes/db.php';

echo "🎬 CineFlix Movie Data Backup\n";
echo "=============================\n\n";

$conn = db_get_connection();

// Backup Movie table
echo "1. Backing up Movie table...\n";
try {
    $result = $conn->query("SELECT * FROM Movie");
    $movies = [];
    
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
    
    // Save to backup file
    $backupFile = __DIR__ . '/movie_backup_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($backupFile, json_encode($movies, JSON_PRETTY_PRINT));
    
    echo "   ✓ Backed up " . count($movies) . " movies to: " . basename($backupFile) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error backing up movies: " . $e->getMessage() . "\n";
}

// Backup CustomerUser table
echo "\n2. Backing up CustomerUser table...\n";
try {
    $result = $conn->query("SELECT * FROM CustomerUser");
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $backupFile = __DIR__ . '/users_backup_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($backupFile, json_encode($users, JSON_PRETTY_PRINT));
    
    echo "   ✓ Backed up " . count($users) . " users to: " . basename($backupFile) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error backing up users: " . $e->getMessage() . "\n";
}

// Backup Bookings table
echo "\n3. Backing up Bookings table...\n";
try {
    $result = $conn->query("SELECT * FROM bookings");
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    $backupFile = __DIR__ . '/bookings_backup_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($backupFile, json_encode($bookings, JSON_PRETTY_PRINT));
    
    echo "   ✓ Backed up " . count($bookings) . " bookings to: " . basename($backupFile) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error backing up bookings: " . $e->getMessage() . "\n";
}

echo "\n✅ Backup Complete!\n";
echo "==================\n";
echo "Your data is safely backed up. Now we can activate features safely.\n\n";
echo "Next step: Run safe_database_setup.php\n";
?>
