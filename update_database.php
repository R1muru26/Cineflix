<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "cineflix");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Updating Movie Table Schema</h2>";

// Create bookings table first
$createBookingsTable = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(32) NOT NULL UNIQUE,
    customer_id INT NULL,
    customer_name VARCHAR(120) NULL,
    customer_email VARCHAR(120) NULL,
    item_type ENUM('movie','event') NOT NULL DEFAULT 'movie',
    item_name VARCHAR(255) NOT NULL,
    event_option VARCHAR(120) NULL,
    show_date DATE NULL,
    show_time VARCHAR(64) NULL,
    venue VARCHAR(120) NULL,
    seats TEXT NULL,
    quantity INT NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) NOT NULL,
    addons LONGTEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Paid',
    refund_reason TEXT NULL,
    refund_status VARCHAR(60) NULL,
    cancelled_date DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($createBookingsTable)) {
    echo "<span style='color: blue;'>+ Bookings table created/verified</span><br>";
} else {
    echo "<span style='color: red;'>✗ Failed to create bookings table: " . $conn->error . "</span><br>";
}

// Add discount-related columns to bookings table
$discountColumns = [
    'discount_type' => "ALTER TABLE bookings ADD COLUMN discount_type VARCHAR(20) NULL",
    'discount_original_total' => "ALTER TABLE bookings ADD COLUMN discount_original_total DECIMAL(10,2) NULL",
    'discounted_total' => "ALTER TABLE bookings ADD COLUMN discounted_total DECIMAL(10,2) NULL",
    'discount_status' => "ALTER TABLE bookings ADD COLUMN discount_status VARCHAR(20) NULL",
    'discount_id_number' => "ALTER TABLE bookings ADD COLUMN discount_id_number VARCHAR(255) NULL",
    'discount_id_path' => "ALTER TABLE bookings ADD COLUMN discount_id_path VARCHAR(255) NULL",
    'parking_number' => "ALTER TABLE bookings ADD COLUMN parking_number VARCHAR(20) NULL"
];

foreach ($discountColumns as $columnName => $sql) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE '" . $conn->real_escape_string($columnName) . "'");
    $exists = $check && $check->num_rows > 0;
    
    if ($exists) {
        echo "<span style='color: green;'>✓ Bookings column '$columnName' already exists</span><br>";
    } else {
        // Add column
        if ($conn->query($sql)) {
            echo "<span style='color: blue;'>+ Bookings column '$columnName' added successfully</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Failed to add bookings column '$columnName': " . $conn->error . "</span><br>";
        }
    }
    
    if ($check) {
        $check->free();
    }
}

// Columns to add
$columnsToAdd = [
    'PosterPath' => "ALTER TABLE Movie ADD COLUMN PosterPath VARCHAR(500) NULL DEFAULT NULL",
    'TrailerURL' => "ALTER TABLE Movie ADD COLUMN TrailerURL VARCHAR(500) NULL DEFAULT NULL",
    'Description' => "ALTER TABLE Movie ADD COLUMN Description TEXT NULL DEFAULT NULL",
    'section' => "ALTER TABLE Movie ADD COLUMN section VARCHAR(20) NOT NULL DEFAULT 'more_movies'"
];

foreach ($columnsToAdd as $columnName => $sql) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM Movie LIKE '" . $conn->real_escape_string($columnName) . "'");
    $exists = $check && $check->num_rows > 0;
    
    if ($exists) {
        echo "<span style='color: green;'>✓ Column '$columnName' already exists</span><br>";
    } else {
        // Add column
        if ($conn->query($sql)) {
            echo "<span style='color: blue;'>+ Column '$columnName' added successfully</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Failed to add column '$columnName': " . $conn->error . "</span><br>";
        }
    }
    
    if ($check) {
        $check->free();
    }
}

echo "<h3>Current Movie Table Structure:</h3>";
$result = $conn->query("DESCRIBE Movie");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<br><a href='homepage.php'>Go to Homepage</a><br>";
echo "<a href='login.html'>Go to Login</a><br>";
?>
