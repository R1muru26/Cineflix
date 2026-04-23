<?php
session_start();

// Destroy current session and create a new one
session_destroy();
session_start();
session_regenerate_id(true);

echo "New session created. Session ID: " . session_id() . "<br>";
echo "<a href='browser_debug.php'>Test New Session</a><br>";
echo "<a href='homepage.php'>Go to Homepage</a><br>";
?>
