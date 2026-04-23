<?php
session_start();

echo "<h2>Session Debug - Homepage Check</h2>";

echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>Session Contents:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>User ID Check:</h3>";
echo "isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? 'TRUE' : 'FALSE') . "<br>";
echo "empty(\$_SESSION['user_id']): " . (empty($_SESSION['user_id']) ? 'TRUE' : 'FALSE') . "<br>";

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    echo "<strong style='color: green;'>✓ User should see logged-in menu</strong><br>";
} else {
    echo "<strong style='color: red;'>✗ User will see Login button</strong><br>";
}

echo "<h3>Test Session Setting:</h3>";
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_name'] = 'Test User';
$_SESSION['username'] = 'testuser';

echo "Session set. Now checking again:<br>";
echo "isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? 'TRUE' : 'FALSE') . "<br>";

echo "<br><a href='homepage.php'>Go to Homepage (should show user menu now)</a><br>";
echo "<a href='clear_session.php'>Clear Session</a><br>";
?>
