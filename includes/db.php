<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PORTFOLIO DEMO MODE: Auto-login
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_email'] = 'demo@portfolio.com';
    $_SESSION['user_name'] = 'Portfolio Demo User';
    $_SESSION['username'] = 'demouser';
}

function db_get_connection(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cineflix";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_errno) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function db_ensure_bookings_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (!$conn->query($sql)) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Failed to ensure bookings table: ' . $conn->error
        ]));
    }

    // Ensure newer discount-related columns exist for PWD/Senior verification.
    // Use SHOW COLUMNS to remain compatible with older MySQL versions.
    $needCols = [
        'discount_type' => "ALTER TABLE bookings ADD COLUMN discount_type VARCHAR(20) NULL",
        'discount_original_total' => "ALTER TABLE bookings ADD COLUMN discount_original_total DECIMAL(10,2) NULL",
        'discounted_total' => "ALTER TABLE bookings ADD COLUMN discounted_total DECIMAL(10,2) NULL",
        'discount_status' => "ALTER TABLE bookings ADD COLUMN discount_status VARCHAR(20) NULL",
        'discount_id_number' => "ALTER TABLE bookings ADD COLUMN discount_id_number VARCHAR(255) NULL",
        'discount_id_path' => "ALTER TABLE bookings ADD COLUMN discount_id_path VARCHAR(255) NULL",
        'parking_number' => "ALTER TABLE bookings ADD COLUMN parking_number VARCHAR(20) NULL",
    ];
    foreach ($needCols as $col => $alterSql) {
        $check = $conn->query("SHOW COLUMNS FROM bookings LIKE '" . $conn->real_escape_string($col) . "'");
        $exists = $check && $check->num_rows > 0;
        if ($check) { $check->free(); }
        if (!$exists) {
            $conn->query($alterSql);
        }
    }
}

function db_ensure_parking_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS parking_spaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parking_number VARCHAR(20) NOT NULL UNIQUE,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        booking_id VARCHAR(32) NULL,
        assigned_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);

    // Seed P1-P200 if empty
    $r = $conn->query("SELECT COUNT(*) as c FROM parking_spaces");
    $row = $r ? $r->fetch_assoc() : ['c' => 0];
    if ($row['c'] == 0) {
        for ($i = 1; $i <= 200; $i++) {
            $n = 'P' . $i;
            $conn->query("INSERT IGNORE INTO parking_spaces (parking_number) VALUES ('" . $conn->real_escape_string($n) . "')");
        }
    }
}

/**
 * Assign an available parking space for online bookings. Returns parking_number or null.
 * Uses row-level lock to prevent duplicates.
 */
function db_assign_parking(mysqli $conn, string $bookingId): ?string
{
    db_ensure_parking_table($conn);
    $bookingIdEsc = $conn->real_escape_string($bookingId);
    $conn->query("START TRANSACTION");
    $result = $conn->query("
        SELECT id, parking_number FROM parking_spaces
        WHERE is_available = 1
        ORDER BY id
        LIMIT 1
        FOR UPDATE
    ");
    if (!$result || $result->num_rows === 0) {
        $conn->query("ROLLBACK");
        return null;
    }
    $row = $result->fetch_assoc();
    $parkingNumber = $row['parking_number'];
    $id = (int)$row['id'];
    $conn->query("UPDATE parking_spaces SET is_available = 0, booking_id = '$bookingIdEsc', assigned_at = NOW() WHERE id = $id");
    $conn->query("COMMIT");
    return $parkingNumber;
}

function db_ensure_erd_tables(mysqli $conn): void
{
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Add ERD attributes to native CustomerUser
    $checkPhone = $conn->query("SHOW COLUMNS FROM `CustomerUser` LIKE 'PhoneNo'");
    if (!$checkPhone || $checkPhone->num_rows == 0) {
        $conn->query("ALTER TABLE `CustomerUser` ADD COLUMN `PhoneNo` VARCHAR(50) NULL");
    }

    // Add ERD attributes to native bookings and connect the Foreign Key
    $checkShowtime = $conn->query("SHOW COLUMNS FROM `bookings` LIKE 'ShowtimeID'");
    if (!$checkShowtime || $checkShowtime->num_rows == 0) {
        $conn->query("ALTER TABLE `bookings` ADD COLUMN `ShowtimeID` INT NULL");
    }

    // Forcefully inject missing columns into existing tables if the DB was cached without them earlier
    $erdColumns = [
        'theater' => ['TheaterType' => 'VARCHAR(100) NOT NULL DEFAULT "standard"'],
        'Seat' => ['MovieName' => 'VARCHAR(255) NULL', 'TheaterType' => 'VARCHAR(100) NULL'],
        'payment' => ['RefNumber' => 'VARCHAR(100) NULL']
    ];
    foreach ($erdColumns as $table => $cols) {
        $tableExists = $conn->query("SHOW TABLES LIKE '$table'");
        if ($tableExists && $tableExists->num_rows > 0) {
            foreach ($cols as $col => $def) {
                $checkCol = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if ($checkCol && $checkCol->num_rows == 0) {
                    $conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                }
            }
        }
    }

    // Enforce ERD compliant Integer based primary and foreign keys

    $conn->query("CREATE TABLE IF NOT EXISTS `movie` (
        `MovieID` INT AUTO_INCREMENT PRIMARY KEY,
        `Title` VARCHAR(255) NOT NULL,
        `Duration` INT,
        `Genre` VARCHAR(100),
        `ReleaseDate` DATE,
        `Rating` FLOAT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `theater` (
        `TheaterID` INT AUTO_INCREMENT PRIMARY KEY,
        `TheaterName` VARCHAR(255) NOT NULL,
        `TheaterType` VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `showtime` (
        `ShowtimeID` INT AUTO_INCREMENT PRIMARY KEY,
        `MovieID` INT NOT NULL,
        `TheaterID` INT NOT NULL,
        `StartTime` DATETIME NOT NULL,
        `EndTime` DATETIME NOT NULL,
        FOREIGN KEY (`MovieID`) REFERENCES `movie`(`MovieID`) ON DELETE CASCADE,
        FOREIGN KEY (`TheaterID`) REFERENCES `theater`(`TheaterID`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add the foreign key constraint directly onto the bindings of bookings to connect the ERD securely
    $checkFk = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_bookings_showtime'");
    if (!$checkFk || $checkFk->num_rows == 0) {
        $conn->query("ALTER TABLE `bookings` ADD CONSTRAINT `fk_bookings_showtime` FOREIGN KEY (`ShowtimeID`) REFERENCES `showtime`(`ShowtimeID`) ON DELETE SET NULL");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `payment` (
        `PaymentID` INT AUTO_INCREMENT PRIMARY KEY,
        `BookingID` INT NOT NULL,
        `Amount` FLOAT NOT NULL,
        `PaymentMethod` VARCHAR(100) NOT NULL,
        `RefNumber` VARCHAR(100),
        `PaymentDate` DATE NOT NULL,
        `PaymentStatus` VARCHAR(50) NOT NULL,
        FOREIGN KEY (`BookingID`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
        CONSTRAINT `chk_payment_method` CHECK (`PaymentMethod` IN ('Cash', 'cash', 'E-Wallet', 'EWallet', 'ewallet', 'Ewallet'))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `Seat` (
        `SeatID` INT AUTO_INCREMENT PRIMARY KEY,
        `TheaterID` INT,
        `SeatNumber` VARCHAR(50) NOT NULL,
        `AvailabilityStatus` VARCHAR(50) NOT NULL,
        `MovieName` VARCHAR(255),
        `TheaterType` VARCHAR(100),
        FOREIGN KEY (`TheaterID`) REFERENCES `theater`(`TheaterID`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `ticket` (
        `TicketID` INT AUTO_INCREMENT PRIMARY KEY,
        `BookingID` INT NOT NULL,
        `SeatID` INT NOT NULL,
        `TicketNumber` VARCHAR(100) NOT NULL,
        `Status` VARCHAR(50) NOT NULL,
        FOREIGN KEY (`BookingID`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`SeatID`) REFERENCES `Seat`(`SeatID`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `parking` (
        `ParkingID` INT AUTO_INCREMENT PRIMARY KEY,
        `BookingID` INT NOT NULL,
        `SlotNumber` VARCHAR(50),
        `VehicleType` VARCHAR(50),
        `VehiclePlate` VARCHAR(50),
        `EntryTime` DATETIME,
        `ExitTime` DATETIME,
        `ParkingFee` FLOAT,
        `ParkingStatus` VARCHAR(50),
        FOREIGN KEY (`BookingID`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS `food_orders` (
        `FoodOrderID` INT AUTO_INCREMENT PRIMARY KEY,
        `BookingID` INT NOT NULL,
        `ItemName` VARCHAR(255) NOT NULL,
        `Category` VARCHAR(100),
        `Quantity` INT NOT NULL,
        `UnitPrice` FLOAT NOT NULL,
        `TotalPrice` FLOAT NOT NULL,
        `OrderStatus` VARCHAR(50),
        FOREIGN KEY (`BookingID`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
}

