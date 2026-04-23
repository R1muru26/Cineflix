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
    $token = trim($_POST['token'] ?? '');
    $new_password = trim($_POST['password'] ?? '');

    if (empty($token) || empty($new_password)) {
        $error = 'Invalid request. Please fill in all fields.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Ensure ResetToken columns exist
        $check_token = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetToken'");
        if (!$check_token || $check_token->num_rows == 0) {
            $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetToken VARCHAR(100) NULL");
        }
        $check_expiry = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetTokenExpiry'");
        if (!$check_expiry || $check_expiry->num_rows == 0) {
            $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetTokenExpiry DATETIME NULL");
        }
        
        // Clean and validate token (remove any whitespace, ensure it's a valid hex string)
        $token = trim($token);
        if (empty($token) || !ctype_xdigit($token)) {
            $error = 'Invalid token format. Please use the link from your email.';
        } else {
            // Check if token exists and is valid (check expiry properly)
            $sql = "SELECT CustomerID, ResetTokenExpiry FROM CustomerUser WHERE ResetToken = ? AND ResetToken IS NOT NULL";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = 'Database error. Please try again.';
            } else {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res && $res->num_rows === 1) {
                    $user_data = $res->fetch_assoc();
                    $expiry = $user_data['ResetTokenExpiry'];
                    
                    // Check if token has expired
                    if ($expiry !== null) {
                        $expiry_timestamp = strtotime($expiry);
                        $current_timestamp = time();
                        if ($expiry_timestamp <= $current_timestamp) {
                            $error = 'This reset link has expired. Please request a new password reset.';
                            $stmt->close();
                        } else {
                            // Token is valid and not expired, update password
                            $hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $upd = $conn->prepare("UPDATE CustomerUser SET Password = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE ResetToken = ?");
                            if (!$upd) {
                                $error = 'Database error. Please try again.';
                                $stmt->close();
                            } else {
                                $upd->bind_param("ss", $hash, $token);
                                if ($upd->execute()) {
                                    $success = 'Password updated successfully! Redirecting to login...';
                                    // Redirect to login after 2 seconds
                                    echo '<script>setTimeout(function(){ window.location.href = "login.html"; }, 2000);</script>';
                                } else {
                                    $error = 'Failed to update password. Please try again.';
                                }
                                $upd->close();
                                $stmt->close();
                            }
                        }
                    } else {
                        // Token exists but has no expiry (shouldn't happen, but handle it)
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $upd = $conn->prepare("UPDATE CustomerUser SET Password = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE ResetToken = ?");
                        if (!$upd) {
                            $error = 'Database error. Please try again.';
                            $stmt->close();
                        } else {
                            $upd->bind_param("ss", $hash, $token);
                            if ($upd->execute()) {
                                $success = 'Password updated successfully! Redirecting to login...';
                                echo '<script>setTimeout(function(){ window.location.href = "login.html"; }, 2000);</script>';
                            } else {
                                $error = 'Failed to update password. Please try again.';
                            }
                            $upd->close();
                            $stmt->close();
                        }
                    }
                } else {
                    $error = 'Invalid or expired token. Please request a new password reset.';
                    $stmt->close();
                }
            }
        }
    }
}

// Read token for display form - properly decode URL-encoded token
$token_from_get = isset($_GET['token']) ? trim(urldecode($_GET['token'])) : '';

// Validate token on page load (if not submitting form)
if (!isset($_POST['update_password']) && !empty($token_from_get)) {
    // Ensure ResetToken columns exist
    $check_token = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetToken'");
    if (!$check_token || $check_token->num_rows == 0) {
        $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetToken VARCHAR(100) NULL");
    }
    $check_expiry = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetTokenExpiry'");
    if (!$check_expiry || $check_expiry->num_rows == 0) {
        $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetTokenExpiry DATETIME NULL");
    }
    
    // Validate token format
    if (!ctype_xdigit($token_from_get)) {
        $error = 'Invalid token format. Please use the link from your email.';
        $token_from_get = ''; // Clear invalid token
    } else {
        // Check if token exists and is valid
        $sql = "SELECT CustomerID, ResetTokenExpiry FROM CustomerUser WHERE ResetToken = ? AND ResetToken IS NOT NULL";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $token_from_get);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $res->num_rows === 1) {
                $user_data = $res->fetch_assoc();
                $expiry = $user_data['ResetTokenExpiry'];
                
                // Check if token has expired
                if ($expiry !== null) {
                    $expiry_timestamp = strtotime($expiry);
                    $current_timestamp = time();
                    if ($expiry_timestamp <= $current_timestamp) {
                        $error = 'This reset link has expired. Please request a new password reset.';
                        $token_from_get = ''; // Clear expired token
                    }
                }
            } else {
                $error = 'Invalid or expired token. Please request a new password reset.';
                $token_from_get = ''; // Clear invalid token
            }
            $stmt->close();
        }
    }
}

// If no token in URL and no form submission, show error
if (empty($token_from_get) && !isset($_POST['update_password']) && empty($error)) {
    $error = 'No reset token provided. Please use the link from your email or request a new password reset.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reset Password | CineFlix</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="login.css">
    <style>
        body.has-background { overflow-y: auto; overflow-x: hidden; min-height: 100vh; }
        main {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 2rem 1rem;
            padding-bottom: 2rem;
        }
        .login-container { 
            transform: translateY(-56px); 
            max-width: 400px; 
            width: 90%; 
            box-sizing: border-box;
        }
        .login-input { 
            width: 100%; 
            box-sizing: border-box; 
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 0.9rem;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid rgba(34, 197, 94, 0.3);
            font-size: 0.9rem;
        }
        
        /* Desktop - Large (1920px+) */
        @media (min-width: 1920px) {
            .login-container { max-width: 450px; padding: 2.5rem; }
            .login-input { padding: 1.2rem; font-size: 1.05rem; }
            .login-btn { padding: 1.2rem; font-size: 1.05rem; }
        }
        
        /* Desktop - Standard (1400px-1919px) */
        @media (min-width: 1400px) and (max-width: 1919px) {
            .login-container { max-width: 420px; padding: 2.25rem; }
        }
        
        /* Laptop (1024px-1399px) */
        @media (min-width: 1024px) and (max-width: 1399px) {
            .login-container { max-width: 400px; padding: 2rem; }
        }
        
        /* Tablet (768px-1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .login-container { max-width: 380px; padding: 1.75rem; }
            main { min-height: calc(100vh - 72px); }
        }
        
        /* Mobile (below 768px) */
        @media (max-width: 767px) {
            main { min-height: calc(100vh - 72px); padding: 1rem; }
            .login-container { 
                max-width: 100%; 
                width: 95%; 
                padding: 1.5rem; 
                transform: translateY(-20px);
            }
            .login-input { font-size: 16px; }
        }
    </style>
</head>
<body class="has-background">
    <div class="background-blur"></div>
    <header class="site-header">
        <a class="logo" href="homepage.php">
            <img src="logo/newlogo1.png" alt="CineFlix Logo">
        </a>
        <nav class="top-nav">
            <ul>
                <li><a class="nav-btn" href="status.php">Status</a></li>
                <li><a class="nav-btn" href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="login-container">
            <img src="logo/C_1_-removebg-preview.png" alt="CineFlix Logo" class="login-logo">
            <h2>Set a New Password</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($token_from_get) || isset($_POST['update_password'])): ?>
            <form action="reset-password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_from_get ?: ($_POST['token'] ?? '')); ?>">
                <input type="password" name="password" class="login-input" placeholder="New Password" required minlength="8" title="Password must be at least 8 characters long">
                <p style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-top: -10px; margin-bottom: 15px; text-align: left;">Password must be at least 8 characters long</p>
                <button type="submit" name="update_password" class="login-btn">Update Password</button>
                <a href="login.html" class="signup-link">Back to Login</a>
            </form>
            <?php else: ?>
            <p style="color: rgba(255,255,255,0.8); text-align: center; margin-bottom: 20px;">Please use the link from your email or request a new password reset.</p>
            <a href="forgot-password.html" class="login-btn" style="display: block; text-align: center; text-decoration: none; margin-bottom: 15px;">Request New Reset Link</a>
            <a href="login.html" class="signup-link">Back to Login</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>


