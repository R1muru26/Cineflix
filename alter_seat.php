<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "cineflix";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$alterSql = "ALTER TABLE `Seat` 
             ADD COLUMN `MovieName` VARCHAR(255) NULL AFTER `AvailabilityStatus`,
             ADD COLUMN `TheaterType` VARCHAR(100) NULL AFTER `MovieName`";

if ($conn->query($alterSql) === TRUE) {
    echo "Columns added to Seat table successfully.\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}

$conn->close();
?>
