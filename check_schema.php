<?php
require 'includes/db.php';
$conn = db_get_connection();
if ($res = $conn->query("SHOW CREATE TABLE `theater`")) {
    $row = $res->fetch_row();
    echo $row[1] . "\n\n";
}
?>
