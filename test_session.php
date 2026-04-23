<?php
session_start();

echo "<h2>Session Persistence Test</h2>";

// Check if we're coming from a login redirect
if (isset($_GET['from_login']) && $_GET['from_login'] == '1') {
    echo "<h3>After Login Redirect</h3>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    if (!empty($_SESSION['user_id'])) {
        echo "<strong style='color: green;'>✓ User is logged in!</strong><br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
        echo "User Email: " . ($_SESSION['user_email'] ?? 'Not set') . "<br>";
    } else {
        echo "<strong style='color: red;'>✗ User session data is missing!</strong><br>";
        echo "This indicates the session was lost during redirect.<br>";
    }
    
    echo "<br><a href='homepage.php'>Continue to Homepage</a><br>";
    echo "<a href='debug_login.php'>Back to Debug Test</a><br>";
} else {
    echo "<h3>Manual Session Check</h3>";
    echo "Current Session ID: " . session_id() . "<br>";
    echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    if (!empty($_SESSION['user_id'])) {
        echo "<strong style='color: green;'>✓ User appears to be logged in</strong><br>";
    } else {
        echo "<strong style='color: orange;'>No user session data found</strong><br>";
    }
    
    echo "<br><a href='homepage.php'>Go to Homepage</a><br>";
    echo "<a href='debug_login.php'>Back to Debug Test</a><br>";
}

// Test setting a session variable
$_SESSION['test_var'] = 'test_value_' . time();
echo "<br>Test variable set: " . $_SESSION['test_var'] . "<br>";
?>
