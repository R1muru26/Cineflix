-- Add missing columns to CustomerUser table
-- Run this in phpMyAdmin SQL tab

USE CINEFLIX;

-- Add Username column
ALTER TABLE CustomerUser 
ADD COLUMN Username VARCHAR(100) NOT NULL UNIQUE AFTER Name;

-- Add Password column
ALTER TABLE CustomerUser 
ADD COLUMN Password VARCHAR(255) NOT NULL AFTER Email;

-- Add password reset columns (optional)
ALTER TABLE CustomerUser 
ADD COLUMN ResetToken VARCHAR(255) NULL AFTER Password,
ADD COLUMN ResetTokenExpiry DATETIME NULL AFTER ResetToken;

