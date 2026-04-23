-- CINEFLIX Feature Migrations
-- Run this script to add tables/columns for new features

-- 1. OAuth provider columns in CustomerUser (Google/Microsoft Sign-In)
-- Run only if columns don't exist
ALTER TABLE CustomerUser ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(32) NULL;
ALTER TABLE CustomerUser ADD COLUMN IF NOT EXISTS oauth_id VARCHAR(255) NULL;
-- For MySQL 5.7 compatibility (no IF NOT EXISTS for ADD COLUMN), use separate migration checks

-- 2. Parking spaces table for online bookings
CREATE TABLE IF NOT EXISTS parking_spaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parking_number VARCHAR(20) NOT NULL UNIQUE,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  booking_id VARCHAR(32) NULL,
  assigned_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed parking slots (run api/init_parking.php to populate P1-P200)

-- 3. Add parking_number column to bookings
ALTER TABLE bookings ADD COLUMN parking_number VARCHAR(20) NULL;

-- 4. Food orders (for chatbot orders and tracking)
CREATE TABLE IF NOT EXISTS food_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(32) NOT NULL UNIQUE,
  booking_id VARCHAR(32) NULL,
  customer_id INT NULL,
  customer_name VARCHAR(120) NULL,
  customer_email VARCHAR(120) NULL,
  seat_number VARCHAR(20) NULL,
  items LONGTEXT NOT NULL COMMENT 'JSON array of {id, name, price, qty}',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  final_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('received','preparing','ready','delivering','delivered') NOT NULL DEFAULT 'received',
  estimated_minutes INT NOT NULL DEFAULT 15,
  delivered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add discount columns if table already existed
-- ALTER TABLE food_orders ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0;
-- ALTER TABLE food_orders ADD COLUMN final_total DECIMAL(10,2) NOT NULL DEFAULT 0;

-- 5. OAuth columns for CustomerUser (MySQL compatible - run manually if needed)
-- Check if columns exist first, then run:
-- ALTER TABLE CustomerUser ADD COLUMN oauth_provider VARCHAR(32) NULL;
-- ALTER TABLE CustomerUser ADD COLUMN oauth_id VARCHAR(255) NULL;
