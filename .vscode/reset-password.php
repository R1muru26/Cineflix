<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cineflix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

// Handle password update
if (isset($_POST['update_password'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';

    if (trim($token) === '' || trim($new_password) === '') {
        $error = 'Invalid request.';
    } else {
        $sql = "SELECT CustomerID FROM CustomerUser WHERE ResetToken = ? AND ResetTokenExpiry > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE CustomerUser SET Password = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE ResetToken = ?");
            $upd->bind_param("ss", $hash, $token);
            if ($upd->execute()) {
                $success = 'Password updated successfully. You can now log in.';
            } else {
                $error = 'Failed to update password.';
            }
            $upd->close();
        } else {
            $error = 'Invalid or expired token.';
        }
        $stmt->close();
    }
}

// Read token for display form
$token_from_get = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | CineFlix</title>
    <link rel="stylesheet" href="login.css">
</head>
<body class="has-background">
    <div class="background-blur"></div>
    <header class="site-header">
        <a class="logo" href="homepage.php">
            <img src="logo/newlogo1.png" alt="CineFlix Logo">
        </a>
        <nav class="top-nav">
            <ul>
                <li><a class="nav-btn" href="status.html">Status</a></li>
                <li><a class="nav-btn" href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="login-container">
            <h2>Set a New Password</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form action="reset-password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_from_get); ?>">
                <input type="password" name="password" class="login-input" placeholder="New Password" required>
                <button type="submit" name="update_password" class="login-btn">Update Password</button>
                <a href="login.php" class="signup-link">Back to Login</a>
            </form>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>


