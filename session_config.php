<?php
session_start();

echo "<h2>Session Configuration Check</h2>";

echo "<h3>Current Session Info</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>Session Cookie Parameters</h3>";
$cookieParams = session_get_cookie_params();
echo "Cookie Lifetime: " . $cookieParams['lifetime'] . "<br>";
echo "Cookie Path: " . $cookieParams['path'] . "<br>";
echo "Cookie Domain: " . ($cookieParams['domain'] ?? 'default') . "<br>";
echo "Cookie Secure: " . ($cookieParams['secure'] ? 'Yes' : 'No') . "<br>";
echo "Cookie HttpOnly: " . ($cookieParams['httponly'] ? 'Yes' : 'No') . "<br>";
echo "Cookie SameSite: " . ($cookieParams['samesite'] ?? 'default') . "<br>";

echo "<h3>Current Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Test Session Operations</h3>";
$_SESSION['test_timestamp'] = date('Y-m-d H:i:s');
echo "Set test_timestamp: " . $_SESSION['test_timestamp'] . "<br>";

echo "<h3>Links to Test</h3>";
echo "<a href='set_test_session.php'>Set Test Session</a><br>";
echo "<a href='test_session.php'>Test Session Persistence</a><br>";
echo "<a href='homepage.php'>Homepage</a><br>";
echo "<a href='clear_session.php'>Clear Session</a><br>";

echo "<h3>Manual Login Test</h3>";
echo "<form method='post' action='login.php'>
    Email: <input type='email' name='email' required><br>
    Password: <input type='password' name='password' required><br>
    <input type='checkbox' name='g-recaptcha-response' value='test' checked style='display:none;'>
    <input type='submit' value='Login'>
</form>";
?>
