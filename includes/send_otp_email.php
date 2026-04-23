<?php
/**
 * Send OTP Email Function
 * Sends a beautifully designed cinema-themed OTP email
 */

require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/smtp_mailer.php';

function sendOTPEmail($recipientEmail, $recipientName, $otpCode) {
    $config = require __DIR__ . '/email_config.php';
    
    // Create email template
    $emailBody = getOTPEmailTemplate($recipientName, $otpCode);
    
    try {
        // Initialize SMTP Mailer
        $mailer = new SMTPMailer(
            $config['smtp_host'],
            $config['smtp_port'],
            $config['smtp_username'],
            $config['smtp_password'],
            $config['smtp_encryption']
        );
        
        // Send email
        $result = $mailer->send(
            $config['smtp_from_email'],
            $config['smtp_from_name'],
            $recipientEmail,
            $recipientName,
            'Verify Your CineFlix Account - OTP Code',
            $emailBody,
            true
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'OTP email sent successfully'];
        } else {
            // Fallback to PHP mail() if SMTP fails
            return sendOTPEmailFallback($recipientEmail, $recipientName, $otpCode);
        }
    } catch (Exception $e) {
        // Fallback to PHP mail() if SMTP fails
        return sendOTPEmailFallback($recipientEmail, $recipientName, $otpCode);
    }
}

function sendOTPEmailFallback($recipientEmail, $recipientName, $otpCode) {
    $config = require __DIR__ . '/email_config.php';
    $emailBody = getOTPEmailTemplate($recipientName, $otpCode);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $config['smtp_from_name'] . " <" . $config['smtp_from_email'] . ">\r\n";
    $headers .= "Reply-To: " . $config['smtp_from_email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $subject = 'Verify Your CineFlix Account - OTP Code';
    
    if (@mail($recipientEmail, $subject, $emailBody, $headers)) {
        return ['success' => true, 'message' => 'OTP email sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email. Please check your email configuration.'];
    }
}

function getOTPEmailTemplate($name, $otp) {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your CineFlix Account</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Poppins\', Arial, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <!-- Header with Cinema Theme -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e50914 0%, #b20710 100%); padding: 40px 30px; text-align: center; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url(\'data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\') repeat; opacity: 0.1;"></div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; position: relative; z-index: 1;">CineFlix</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9; position: relative; z-index: 1;">🎬 Your Cinema Experience Awaits</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 50px 40px; background: #ffffff;">
                            <h2 style="margin: 0 0 20px 0; color: #1a1a2e; font-size: 28px; font-weight: 600; text-align: center;">Welcome to CineFlix!</h2>
                            
                            <p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.6; text-align: center;">
                                Hi <strong style="color: #e50914;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.6; text-align: center;">
                                Thank you for joining CineFlix! To complete your registration and start enjoying the best cinema experience, please verify your email address using the OTP code below.
                            </p>
                            
                            <!-- OTP Code Box -->
                            <table role="presentation" style="width: 100%; margin: 40px 0;">
                                <tr>
                                    <td align="center">
                                        <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(26, 26, 46, 0.2);">
                                            <p style="margin: 0 0 15px 0; color: #ffffff; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Your Verification Code</p>
                                            <div style="background: #ffffff; border-radius: 10px; padding: 20px; margin: 15px 0;">
                                                <p style="margin: 0; color: #e50914; font-size: 42px; font-weight: 700; letter-spacing: 8px; text-align: center; font-family: \'Courier New\', monospace;">' . htmlspecialchars($otp) . '</p>
                                            </div>
                                            <p style="margin: 15px 0 0 0; color: #ffffff; font-size: 12px; opacity: 0.7; text-align: center;">This code will expire in 10 minutes</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 0 0; color: #888888; font-size: 14px; line-height: 1.6; text-align: center;">
                                Enter this code on the verification page to activate your account. If you didn\'t create an account with CineFlix, please ignore this email.
                            </p>
                            
                            <!-- Cinema Icon Decoration -->
                            <table role="presentation" style="width: 100%; margin: 40px 0 20px 0;">
                                <tr>
                                    <td align="center">
                                        <div style="font-size: 48px; line-height: 1;">🎭</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8f9fa; padding: 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0 0 10px 0; color: #6c757d; font-size: 14px;">
                                <strong style="color: #1a1a2e;">CineFlix Cinema</strong>
                            </p>
                            <p style="margin: 0; color: #6c757d; font-size: 12px; line-height: 1.6;">
                                Your ultimate destination for movies, events, and entertainment.<br>
                                This is an automated email, please do not reply.
                            </p>
                            <p style="margin: 20px 0 0 0; color: #adb5bd; font-size: 11px;">
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

