<?php
session_start();

// Simulate successful login
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_name'] = 'Test User';
$_SESSION['username'] = 'testuser';

echo "Session set. <a href='homepage.php'>Test Homepage</a>";
?>
