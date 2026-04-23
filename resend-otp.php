<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cineflix";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Include OTP email function
require_once __DIR__ . '/includes/send_otp_email.php';

// Process resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        $conn->close();
        exit();
    }
    
    // Check if user exists
    $sql = "SELECT CustomerID, Name, IsVerified FROM CustomerUser WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        $conn->close();
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if already verified
        if (isset($user['IsVerified']) && $user['IsVerified'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified. You can login now.']);
            $stmt->close();
            $conn->close();
            exit();
        }
        
        // Check if OTP columns exist
        $sql_check_columns = "SHOW COLUMNS FROM CustomerUser LIKE 'OTP'";
        $result_columns = $conn->query($sql_check_columns);
        
        if ($result_columns && $result_columns->num_rows > 0) {
            // Generate new OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update OTP in database
            $sql_update = "UPDATE CustomerUser SET OTP = ?, OTPExpiry = ? WHERE CustomerID = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if ($stmt_update === false) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                $stmt->close();
                $conn->close();
                exit();
            }
            
            $stmt_update->bind_param("ssi", $otp, $otp_expiry, $user['CustomerID']);
            
            if ($stmt_update->execute()) {
                // Send OTP email
                $emailResult = sendOTPEmail($email, $user['Name'], $otp);
                
                if ($emailResult['success']) {
                    echo json_encode(['success' => true, 'message' => 'Verification code resent successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Code generated but failed to send email: ' . $emailResult['message']]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate new code']);
            }
            
            $stmt_update->close();
        } else {
            // OTP columns don't exist - add them
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
            
            // Generate new OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update OTP in database
            $sql_update = "UPDATE CustomerUser SET OTP = ?, OTPExpiry = ? WHERE CustomerID = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if ($stmt_update) {
                $stmt_update->bind_param("ssi", $otp, $otp_expiry, $user['CustomerID']);
                
                if ($stmt_update->execute()) {
                    // Send OTP email
                    $emailResult = sendOTPEmail($email, $user['Name'], $otp);
                    
                    if ($emailResult['success']) {
                        echo json_encode(['success' => true, 'message' => 'Verification code resent successfully!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Code generated but failed to send email: ' . $emailResult['message']]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to generate new code']);
                }
                
                $stmt_update->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found. Please sign up again.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

