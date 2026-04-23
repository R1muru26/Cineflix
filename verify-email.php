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

// Process verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        $conn->close();
        exit();
    }
    
    // Check if OTP columns exist
    $sql_check_columns = "SHOW COLUMNS FROM CustomerUser LIKE 'OTP'";
    $result_columns = $conn->query($sql_check_columns);
    
    if ($result_columns && $result_columns->num_rows > 0) {
        // OTP columns exist - verify OTP
        $sql = "SELECT CustomerID, Name, OTP, OTPExpiry, IsVerified FROM CustomerUser WHERE Email = ?";
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
            if ($user['IsVerified'] == 1) {
                echo json_encode(['success' => false, 'message' => 'Email is already verified. You can login now.']);
                $stmt->close();
                $conn->close();
                exit();
            }
            
            // Check if OTP matches
            if ($user['OTP'] === $otp) {
                // Check if OTP is not expired
                $otp_expiry = strtotime($user['OTPExpiry']);
                $current_time = time();
                
                if ($current_time > $otp_expiry) {
                    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
                    $stmt->close();
                    $conn->close();
                    exit();
                }
                
                // Verify the user
                $sql_update = "UPDATE CustomerUser SET IsVerified = 1, OTP = NULL, OTPExpiry = NULL WHERE CustomerID = ?";
                $stmt_update = $conn->prepare($sql_update);
                
                if ($stmt_update === false) {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                    $stmt->close();
                    $conn->close();
                    exit();
                }
                
                $stmt_update->bind_param("i", $user['CustomerID']);
                
                if ($stmt_update->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
                    $stmt_update->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
                    $stmt_update->close();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found. Please sign up again.']);
        }
        
        $stmt->close();
    } else {
        // OTP columns don't exist - add them and mark as verified
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
        
        // For existing users without OTP system, just mark as verified
        $sql_update = "UPDATE CustomerUser SET IsVerified = 1 WHERE Email = ?";
        $stmt = $conn->prepare($sql_update);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

