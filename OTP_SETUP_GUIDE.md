# CineFlix OTP Verification System - Setup Guide

## Overview
This OTP (One-Time Password) verification system sends a 6-digit code to users' email addresses when they sign up, ensuring account security and email verification.

## Features
- ✅ Automatic OTP generation on signup
- ✅ Beautiful cinema-themed email design
- ✅ 10-minute OTP expiration
- ✅ Resend OTP functionality
- ✅ No signup limits - all accounts can sign up
- ✅ Email verification required before login

## Setup Instructions

### Step 1: Configure Gmail SMTP

1. Open `includes/email_config.php`
2. Replace the following values with your Gmail credentials:
   ```php
   'smtp_username' => 'your-email@gmail.com',  // Your Gmail address
   'smtp_password' => 'your-app-password',     // Gmail App Password (see below)
   'smtp_from_email' => 'your-email@gmail.com', // Your Gmail address
   ```

### Step 2: Generate Gmail App Password

1. Go to your Google Account: https://myaccount.google.com/
2. Enable **2-Step Verification** (if not already enabled)
3. Go to **App Passwords**: https://myaccount.google.com/apppasswords
4. Select "Mail" and "Other (Custom name)" - enter "CineFlix"
5. Click "Generate"
6. Copy the 16-character password and paste it in `email_config.php` as `smtp_password`

### Step 3: Update Database Schema

Run the SQL script to add OTP columns to your database:

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select the `cineflix` database
3. Go to the SQL tab
4. Copy and paste the contents of `database_update_otp.sql`
5. Click "Go"

**Option B: Using MySQL Command Line**
```bash
mysql -u root -p cineflix < database_update_otp.sql
```

**Option C: Manual SQL (if above doesn't work)**
```sql
USE cineflix;

ALTER TABLE CustomerUser 
ADD COLUMN OTP VARCHAR(6) NULL,
ADD COLUMN OTPExpiry DATETIME NULL,
ADD COLUMN IsVerified TINYINT(1) DEFAULT 0;
```

### Step 4: Test the System

1. Go to your signup page
2. Create a new account
3. Check your email for the OTP code
4. Enter the code on the verification page
5. Try logging in

## How It Works

1. **Signup Process:**
   - User fills out signup form
   - System generates a 6-digit OTP
   - OTP is stored in database with 10-minute expiration
   - Beautiful email is sent to user's email address
   - User is redirected to verification page

2. **Verification Process:**
   - User enters 6-digit OTP code
   - System validates OTP and expiration
   - If valid, account is marked as verified
   - User can now login

3. **Resend OTP:**
   - User can request a new OTP code
   - 60-second cooldown between requests
   - New OTP is generated and sent

## Files Created/Modified

### New Files:
- `includes/email_config.php` - Email configuration
- `includes/smtp_mailer.php` - SMTP email sender
- `includes/send_otp_email.php` - OTP email function and template
- `verify-email.html` - Email verification page
- `verify-email.php` - Verification backend
- `resend-otp.php` - Resend OTP functionality
- `database_update_otp.sql` - Database schema update

### Modified Files:
- `signup.php` - Now generates and sends OTP
- `login.php` - Checks if email is verified before login

## Troubleshooting

### Email Not Sending?

1. **Check Gmail App Password:**
   - Make sure you're using an App Password, not your regular Gmail password
   - App Passwords are 16 characters with spaces (remove spaces in config)

2. **Check PHP mail() Configuration:**
   - The system falls back to PHP's mail() function if SMTP fails
   - Ensure your server can send emails (check php.ini)

3. **Check Error Logs:**
   - Check PHP error logs for SMTP connection errors
   - Check if port 587 is open on your server

4. **Test Email Configuration:**
   - Try sending a test email directly from PHP
   - Check if your hosting provider allows SMTP connections

### OTP Not Working?

1. **Check Database:**
   - Ensure OTP columns were added successfully
   - Verify the columns exist: `OTP`, `OTPExpiry`, `IsVerified`

2. **Check Session:**
   - Ensure PHP sessions are working
   - Check session storage permissions

### Database Errors?

1. **MySQL Version:**
   - If "ADD COLUMN IF NOT EXISTS" doesn't work, use the manual SQL in Step 3
   - Some older MySQL versions don't support this syntax

2. **Column Already Exists:**
   - If columns already exist, the system will handle it automatically
   - No need to run the SQL script again

## Email Template Customization

The email template is in `includes/send_otp_email.php` in the `getOTPEmailTemplate()` function. You can customize:
- Colors (currently uses CineFlix red #e50914)
- Layout and design
- Text content
- Logo/branding

## Security Notes

- OTPs expire after 10 minutes
- OTPs are cleared after successful verification
- Users cannot login until email is verified
- Resend has a 60-second cooldown to prevent abuse
- OTPs are stored securely in the database

## Support

If you encounter any issues:
1. Check the error logs
2. Verify all configuration steps
3. Test email sending independently
4. Check database connectivity

---

**Note:** This system works with any email provider that supports SMTP. Gmail is recommended for ease of setup, but you can use any SMTP server by modifying `email_config.php`.

