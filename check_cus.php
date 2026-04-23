<?php
require 'includes/db.php';
$conn = db_get_connection();
foreach (['CustomerUser', 'customeruser_erd'] as $t) {
    if ($res = $conn->query("SHOW CREATE TABLE `$t`")) {
        echo "Table: $t EXISTS\n";
    }
}
?>
