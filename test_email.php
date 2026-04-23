<?php
/**
 * Email Test Script
 * Run this to test if your email configuration works
 */

require_once __DIR__ . '/includes/send_otp_email.php';

// Test email sending
$testEmail = 'your-test-email@gmail.com'; // Replace with your test email
$testName = 'Test User';
$testOTP = '123456';

echo "Testing email configuration...\n";
echo "Sending test email to: $testEmail\n\n";

$result = sendOTPEmail($testEmail, $testName, $testOTP);

if ($result['success']) {
    echo "✅ Email sent successfully!\n";
    echo "Message: " . $result['message'] . "\n";
} else {
    echo "❌ Email failed to send!\n";
    echo "Error: " . $result['message'] . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check if Gmail App Password is correct\n";
    echo "2. Ensure 2-Step Verification is enabled on Gmail account\n";
    echo "3. Verify 'Less secure app access' is allowed if needed\n";
    echo "4. Check if PHP mail() function is enabled on your server\n";
}
?>
