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

// Include email functions
require_once __DIR__ . '/includes/email_config.php';
require_once __DIR__ . '/includes/smtp_mailer.php';

// Process the form when submitted
if (isset($_POST['reset'])) {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo "<script>alert('Please enter your email address.'); window.location.href = 'forgot-password.html';</script>";
        exit();
    }

    // Check if the email exists in the database
    $sql_check = "SELECT CustomerID, Name FROM CustomerUser WHERE Email = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        echo "<script>alert('Database error. Please try again later.'); window.location.href = 'forgot-password.html';</script>";
        exit();
    }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $user = $result_check->fetch_assoc();
        $userName = $user['Name'] ?? 'User';

        // Ensure ResetToken and ResetTokenExpiry columns exist
        $check_token = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetToken'");
        if (!$check_token || $check_token->num_rows == 0) {
            $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetToken VARCHAR(100) NULL");
        }
        $check_expiry = $conn->query("SHOW COLUMNS FROM CustomerUser LIKE 'ResetTokenExpiry'");
        if (!$check_expiry || $check_expiry->num_rows == 0) {
            $conn->query("ALTER TABLE CustomerUser ADD COLUMN ResetTokenExpiry DATETIME NULL");
        }

        // Generate a password reset token
        $token = bin2hex(random_bytes(32));  // Generate a random token

        // Set the token's expiration time (1 hour from now)
        $expiry_time = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Store the token and its expiration time in the database
        $sql_update = "UPDATE CustomerUser SET ResetToken = ?, ResetTokenExpiry = ? WHERE Email = ?";
        $stmt_update = $conn->prepare($sql_update);
        if (!$stmt_update) {
            $stmt_check->close();
            echo "<script>alert('Database error. Please try again later.'); window.location.href = 'forgot-password.html';</script>";
            exit();
        }
        $stmt_update->bind_param("sss", $token, $expiry_time, $email);
        
        if (!$stmt_update->execute()) {
            $stmt_check->close();
            $stmt_update->close();
            echo "<script>alert('Failed to generate reset token. Please try again.'); window.location.href = 'forgot-password.html';</script>";
            exit();
        }

        // Get base URL (adjust for your setup)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname($_SERVER['PHP_SELF']);
        $reset_link = $protocol . '://' . $host . $basePath . '/reset-password.php?token=' . urlencode($token);

        // Send password reset email using SMTP (optional - doesn't block redirect)
        $config = require __DIR__ . '/includes/email_config.php';
        $emailBody = getPasswordResetEmailTemplate($userName, $reset_link);

        try {
            $mailer = new SMTPMailer(
                $config['smtp_host'],
                $config['smtp_port'],
                $config['smtp_username'],
                $config['smtp_password'],
                $config['smtp_encryption']
            );

            // Try to send email (but don't block the flow if it fails)
            $mailer->send(
                $config['smtp_from_email'],
                $config['smtp_from_name'],
                $email,
                $userName,
                'CineFlix Password Reset Request',
                $emailBody,
                true
            );
        } catch (Exception $e) {
            // Email sending failed, but continue with redirect
        }
        
        // Redirect directly to reset password page with token
        $stmt_check->close();
        $stmt_update->close();
        header('Location: reset-password.php?token=' . urlencode($token));
        exit();
    } else {
        // If email doesn't exist, show a generic message (don't reveal if email exists)
        $stmt_check->close();
        echo "<script>alert('If an account exists with that email, a password reset link has been sent.'); window.location.href = 'login.php';</script>";
    }
}

function getPasswordResetEmailTemplate($name, $resetLink) {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Poppins\', Arial, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #e50914 0%, #b20710 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;">CineFlix</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">🔐 Password Reset Request</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 50px 40px; background: #ffffff;">
                            <h2 style="margin: 0 0 20px 0; color: #1a1a2e; font-size: 28px; font-weight: 600; text-align: center;">Reset Your Password</h2>
                            <p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.6; text-align: center;">
                                Hi <strong style="color: #e50914;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                            <p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.6; text-align: center;">
                                We received a request to reset your password. Click the button below to create a new password. This link will expire in 1 hour.
                            </p>
                            <table role="presentation" style="width: 100%; margin: 40px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="' . htmlspecialchars($resetLink) . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #e50914 0%, #b20710 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 30px 0 0 0; color: #888888; font-size: 14px; line-height: 1.6; text-align: center;">
                                If you did not request a password reset, please ignore this email. Your password will remain unchanged.
                            </p>
                            <p style="margin: 20px 0 0 0; color: #888888; font-size: 12px; line-height: 1.6; text-align: center; word-break: break-all;">
                                Or copy and paste this link into your browser:<br>
                                <span style="color: #e50914;">' . htmlspecialchars($resetLink) . '</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                                © ' . date('Y') . ' CineFlix. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    return $html;
}

// Close the database connection
$conn->close();
?>
