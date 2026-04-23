<?php
// Custom DB script to bypass localhost IPv6 CLI resolution issues
$servername = "127.0.0.1"; // specifically force IPv4
$username = "root";
$password = "";
$dbname = "cineflix";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$sqlFile = __DIR__ . '/erd_schema.sql';
if (!file_exists($sqlFile)) {
    die("File not found: erd_schema.sql\n");
}

$sql = file_get_contents($sqlFile);
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    if ($conn->errno) {
       echo "Execution failed. Error: " . $conn->error . "\n";
    } else {
       echo "Successfully built tables!\n";
    }
} else {
    echo "Query Error: " . $conn->error . "\n";
}
$conn->close();
?>
