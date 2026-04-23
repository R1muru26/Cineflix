<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cineflix";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include OTP email function
require_once __DIR__ . '/includes/send_otp_email.php';

// Process the form when submitted
if (isset($_POST['signup'])) {
    $redirectWithError = function(string $code) {
        header('Location: signup.html?error=' . urlencode($code));
        exit();
    };

    $surname = trim($_POST['surname'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_initial = trim($_POST['middle_initial'] ?? '');
    $username_input = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $password_confirm_raw = $_POST['password_confirm'] ?? '';

    // Validate name fields
    if ($surname === '' || $first_name === '' || $username_input === '' || $email === '' || trim($password_raw) === '') {
        $redirectWithError('required');
    }

    // Validate surname (letters, spaces, hyphens, apostrophes only)
    if (!preg_match("/^[A-Za-z\s'-]{1,50}$/", $surname)) {
        $redirectWithError('invalid_surname');
    }

    // Validate first name (letters, spaces, hyphens, apostrophes only)
    if (!preg_match("/^[A-Za-z\s'-]{1,50}$/", $first_name)) {
        $redirectWithError('invalid_firstname');
    }

    // Validate middle initial (single letter, optional)
    if ($middle_initial !== '' && !preg_match("/^[A-Za-z]$/", $middle_initial)) {
        $redirectWithError('invalid_middle');
    }

    // Combine name fields
    $name = trim($first_name . ' ' . ($middle_initial !== '' ? strtoupper($middle_initial) . '. ' : '') . $surname);

    if (strlen($password_raw) < 8) {
        $redirectWithError('password_length');
    }

    if ($password_raw !== $password_confirm_raw) {
        $redirectWithError('password_mismatch');
    }

    $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

    $sql_check = "SELECT 1 FROM CustomerUser WHERE Email = ? OR Username = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check === false) {
        $conn->close();
        $redirectWithError('server');
    }
    $stmt_check->bind_param("ss", $email, $username_input);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check && $result_check->num_rows > 0) {
        $stmt_check->close();
        $conn->close();
        $redirectWithError('duplicate');
    }
    $stmt_check->close();

    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes')); // OTP valid for 10 minutes

    // Insert user with OTP (unverified)
    // Check if OTP columns exist, if not use default values
    $sql_check_columns = "SHOW COLUMNS FROM CustomerUser LIKE 'OTP'";
    $result_columns = $conn->query($sql_check_columns);
    
    if ($result_columns && $result_columns->num_rows > 0) {
        // OTP columns exist
        $sql_insert = "INSERT INTO CustomerUser (Name, Username, Email, Password, Phone, OTP, OTPExpiry, IsVerified) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert === false) {
            $conn->close();
            $redirectWithError('server');
        }
        $stmt_insert->bind_param("sssssss", $name, $username_input, $email, $hashed_password, $phone, $otp, $otp_expiry);
    } else {
        // OTP columns don't exist yet - insert without them (will be added via SQL script)
        $sql_insert = "INSERT INTO CustomerUser (Name, Username, Email, Password, Phone) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert === false) {
            $conn->close();
            $redirectWithError('server');
        }
        $stmt_insert->bind_param("sssss", $name, $username_input, $email, $hashed_password, $phone);
    }

    if ($stmt_insert->execute()) {
        $user_id = $conn->insert_id;
        
        // If OTP columns exist, update them
        if ($result_columns && $result_columns->num_rows > 0) {
            // OTP already set in INSERT
        } else {
            // Try to add OTP columns if they don't exist and update
            // Check if columns exist first (for MySQL compatibility)
            $check_otp = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'OTP'");
            if (!$check_otp || $check_otp->num_rows == 0) {
                $conn->query("ALTER TABLE CustomerUser ADD COLUMN OTP VARCHAR(6) NULL");
            }
            $check_expiry = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'OTPExpiry'");
            if (!$check_expiry || $check_expiry->num_rows == 0) {
                $conn->query("ALTER TABLE CustomerUser ADD COLUMN OTPExpiry DATETIME NULL");
            }
            $check_verified = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'IsVerified'");
            if (!$check_verified || $check_verified->num_rows == 0) {
                $conn->query("ALTER TABLE CustomerUser ADD COLUMN IsVerified TINYINT(1) DEFAULT 0");
            }
            
            $sql_update_otp = "UPDATE CustomerUser SET OTP = ?, OTPExpiry = ?, IsVerified = 0 WHERE CustomerID = ?";
            $stmt_update = $conn->prepare($sql_update_otp);
            if ($stmt_update) {
                $stmt_update->bind_param("ssi", $otp, $otp_expiry, $user_id);
                $stmt_update->execute();
                $stmt_update->close();
            }
        }
        
        // Send OTP email
        $emailResult = sendOTPEmail($email, $name, $otp);
        
        $stmt_insert->close();
        $conn->close();
        
        // Store email in session for verification page
        $_SESSION['verification_email'] = $email;
        $_SESSION['verification_name'] = $name;
        
        // Redirect to verification page
        header('Location: verify-email.html?email=' . urlencode($email));
        exit();
    }

    $stmt_insert->close();
    $conn->close();
    $redirectWithError('server');
}

// Close database connection
$conn->close();
?>
