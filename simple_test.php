<?php
echo "PHP is working!";
echo "<br>";
echo "Current time: " . date('Y-m-d H:i:s');
echo "<br>";
echo "Server host: " . ($_SERVER['HTTP_HOST'] ?? 'not set');
echo "<br>";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set');
echo "<br>";
echo "Script name: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set');
?>
