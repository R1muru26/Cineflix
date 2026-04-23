<?php
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
<<<<<<< HEAD

// Clear ticket history from localStorage via JavaScript before redirect
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        // Clear ticket history from localStorage
        localStorage.removeItem('cineflix_bookings');
        // Redirect to homepage
        window.location.href = 'homepage.php';
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>
<?php exit(); ?>
=======
header('Location: homepage.php');
exit();
?>
>>>>>>> 39461e1 (bago)


