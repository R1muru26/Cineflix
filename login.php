<?php
session_start();

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "cineflix";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once __DIR__ . '/includes/auth.php';

// Define error variable
$error_message = "";

// Handle form submission from both login.html and this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);
    // CAPTCHA validation (simple server-side check that the user ticked the box)
    // Note: For development / localhost we only check that a response exists,
    // and we do NOT call the Google verify API to avoid false negatives.
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (empty($recaptchaResponse)) {
        $conn->close();
        header("Location: login.html?error=" . urlencode('Please complete the CAPTCHA.'));
        exit();
    }

    // Admin override
    if ($emailInput === 'admin' && $passwordInput === '1') {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_name'] = 'Administrator';
        $conn->close();
        header("Location: admin-dashboard.php");
        exit();
    }

    // Staff override (for walk-in bookings management)
    if ($emailInput === 'staff' && $passwordInput === '1') {
        $_SESSION['is_staff'] = true;
        $_SESSION['staff_name'] = 'Staff';
        $conn->close();
        header("Location: staff-walkin.php");
        exit();
    }

    // Query to check if the email exists in the CustomerUser table
    // Check if IsVerified column exists
    $sql_check_column = "SHOW COLUMNS FROM CustomerUser LIKE 'IsVerified'";
    $result_check = $conn->query($sql_check_column);
    $has_verified_column = ($result_check && $result_check->num_rows > 0);
    
    if ($has_verified_column) {
        $sql = "SELECT CustomerID, Name, Username, Email, Password, IsVerified FROM CustomerUser WHERE Email = ?";
    } else {
        $sql = "SELECT CustomerID, Name, Username, Email, Password FROM CustomerUser WHERE Email = ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Query failed: " . $conn->error);
    }
    $stmt->bind_param("s", $emailInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($passwordInput, $user['Password'])) {
            // Check if email is verified (if verification system is enabled)
            if ($has_verified_column && isset($user['IsVerified']) && $user['IsVerified'] == 0) {
                // Email not verified
                if (!headers_sent()) {
                    header("Location: verify-email.html?email=" . urlencode($emailInput) . "&error=" . urlencode('Please verify your email address before logging in.'));
                    exit();
                }
                $error_message = "Please verify your email address before logging in.";
            } else {
                // Login successful
                $_SESSION['user_id'] = $user['CustomerID'];
                $_SESSION['user_email'] = $user['Email'];
                $_SESSION['user_name'] = $user['Name'];
                $_SESSION['username'] = $user['Username'];

                // Issue or clear remember-me cookie for normal customers only
                if ($rememberMe) {
                    cineflix_issue_remember_cookie((int)$user['CustomerID'], (string)$user['Email']);
                } else {
                    cineflix_clear_remember_cookie();
                }

                // Ensure session is written before redirect
                session_write_close();
                
                header("Location: homepage.php");
                exit();
            }
        } else {
            // Wrong password
            if (!headers_sent()) {
                header("Location: login.html?error=" . urlencode('Incorrect password or username.'));
                exit();
            }
            $error_message = "Incorrect password or username.";
        }
    } else {
        // Account does not exist
        if (!headers_sent()) {
            header("Location: login.html?error=" . urlencode("Account doesn't exist. Please sign up."));
            exit();
        }
        $error_message = "Account doesn't exist. Please sign up.";
    }
    if (isset($stmt)) { $stmt->close(); }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | CineFlix</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

