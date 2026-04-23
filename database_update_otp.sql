-- SQL script to add OTP verification columns to CustomerUser table
-- Run this script in your MySQL database to enable OTP verification

USE cineflix;

-- Add OTP columns (check manually if they exist first to avoid errors)
-- If you get "Duplicate column" errors, the columns already exist and you can ignore them

ALTER TABLE CustomerUser 
ADD COLUMN OTP VARCHAR(6) NULL;

ALTER TABLE CustomerUser 
ADD COLUMN OTPExpiry DATETIME NULL;

ALTER TABLE CustomerUser 
ADD COLUMN IsVerified TINYINT(1) DEFAULT 0;

-- Update existing users to be verified (optional - remove if you want to verify all existing users)
-- UPDATE CustomerUser SET IsVerified = 1 WHERE IsVerified IS NULL OR IsVerified = 0;

-- Verify the columns were added
DESCRIBE CustomerUser;

