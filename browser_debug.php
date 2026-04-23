<?php
session_start();

echo "<h2>Browser/Device Specific Debug</h2>";

echo "<h3>Cookie Check</h3>";
echo "Cookies received: <pre>" . print_r($_COOKIE, true) . "</pre>";

echo "<h3>Session Info</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Cookie exists: " . (isset($_COOKIE[session_name()]) ? 'YES' : 'NO') . "<br>";

if (isset($_COOKIE[session_name()])) {
    echo "Session Cookie Value: " . $_COOKIE[session_name()] . "<br>";
    echo "Matches current session: " . ($_COOKIE[session_name()] === session_id() ? 'YES' : 'NO') . "<br>";
}

echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Set Test Session</h3>";
$_SESSION['browser_test'] = 'working_' . date('H:i:s');
echo "Test session set: " . $_SESSION['browser_test'] . "<br>";

echo "<h3>Manual Test Links</h3>";
echo "<a href='homepage.php' target='_blank'>Homepage (new tab)</a><br>";
echo "<a href='check_session.php' target='_blank'>Check Session (new tab)</a><br>";

echo "<h3>Force New Session</h3>";
echo "<a href='force_new_session.php'>Force New Session ID</a><br>";
?>
