-- CineFlix Enhanced Features Database Schema
-- Run this script to add tables for the new features

-- 1. Dynamic Pricing History
CREATE TABLE IF NOT EXISTS pricing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_title VARCHAR(255) NOT NULL,
    show_date DATE NOT NULL,
    show_time VARCHAR(50) NOT NULL,
    cinema_type VARCHAR(50) NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    final_price DECIMAL(10,2) NOT NULL,
    occupancy_rate DECIMAL(5,2) NOT NULL,
    pricing_factors JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_movie_date (movie_title, show_date)
);

-- 2. Food Orders
CREATE TABLE IF NOT EXISTS food_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL UNIQUE,
    booking_id VARCHAR(50) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    items JSON NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    status ENUM('preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'preparing',
    preparation_time INT DEFAULT 0, -- in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_time TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_id (booking_id),
    INDEX idx_status (status),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- 3. User Notifications
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id VARCHAR(100) NOT NULL,
    type ENUM('alert', 'reminder', 'promotion', 'recommendation', 'feature') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    action_text VARCHAR(100),
    action_url VARCHAR(500),
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, read_at),
    INDEX idx_expires (expires_at)
);

-- 4. Check-in Records
ALTER TABLE bookings 
ADD COLUMN checked_in BOOLEAN DEFAULT FALSE,
ADD COLUMN checkin_time TIMESTAMP NULL,
ADD COLUMN entrance_validated BOOLEAN DEFAULT FALSE,
ADD COLUMN entrance_time TIMESTAMP NULL,
ADD COLUMN gate_number VARCHAR(10);

-- 5. Food Menu Items
CREATE TABLE IF NOT EXISTS food_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    item_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    preparation_time INT DEFAULT 0, -- in minutes
    available BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Promotions
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed', 'buy_one_get_one') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    conditions JSON,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. User Preferences (for recommendations)
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    favorite_genres JSON,
    preferred_cinema_types JSON,
    preferred_show_times JSON,
    price_sensitivity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    notification_preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES CustomerUser(CustomerID)
);

-- 8. Seat Heat Map Data
CREATE TABLE IF NOT EXISTS seat_heat_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_title VARCHAR(255) NOT NULL,
    show_date DATE NOT NULL,
    show_time VARCHAR(50) NOT NULL,
    cinema_type VARCHAR(50) NOT NULL,
    seat_id VARCHAR(10) NOT NULL,
    heat_score INT DEFAULT 0, -- 0-100
    booking_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_show (movie_title, show_date, show_time),
    INDEX idx_seat (seat_id)
);

-- Insert sample food menu items
INSERT IGNORE INTO food_menu (category, item_id, name, description, price, preparation_time) VALUES
('popcorn', 'pop_s', 'Small Popcorn', 'Buttered popcorn, small size', 120, 3),
('popcorn', 'pop_m', 'Medium Popcorn', 'Buttered popcorn, medium size', 150, 3),
('popcorn', 'pop_l', 'Large Popcorn', 'Buttered popcorn, large size', 180, 3),
('drinks', 'coke_s', 'Coke (Small)', 'Coca-Cola, 12oz', 80, 1),
('drinks', 'coke_m', 'Coke (Medium)', 'Coca-Cola, 16oz', 100, 1),
('drinks', 'coke_l', 'Coke (Large)', 'Coca-Cola, 20oz', 120, 1),
('combos', 'combo1', 'Classic Combo', 'Medium Popcorn + Medium Coke', 200, 3),
('combos', 'combo2', 'Deluxe Combo', 'Large Popcorn + Large Coke + Nachos', 280, 5);

-- Insert sample promotions
INSERT IGNORE INTO promotions (title, description, discount_type, discount_value, start_date, end_date) VALUES
('Tuesday Special', 'Get 20% off all tickets every Tuesday!', 'percentage', 20.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59'),
('Early Bird', 'Book 7+ days in advance and save 10%', 'percentage', 10.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59'),
('Matinee Special', 'All shows before 12 PM at discounted prices', 'percentage', 15.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_food_orders_status ON food_orders(status);
CREATE INDEX IF NOT EXISTS idx_food_orders_created ON food_orders(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON user_notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON user_notifications(type);
CREATE INDEX IF NOT EXISTS idx_pricing_movie_date ON pricing_history(movie_title, show_date);
CREATE INDEX IF NOT EXISTS idx_heat_show ON seat_heat_data(movie_title, show_date, show_time);

-- Add triggers for automatic timestamp updates
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_food_orders_timestamp 
BEFORE UPDATE ON food_orders
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER IF NOT EXISTS update_user_preferences_timestamp 
BEFORE UPDATE ON user_preferences
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER IF NOT EXISTS update_seat_heat_timestamp 
BEFORE UPDATE ON seat_heat_data
FOR EACH ROW
BEGIN
    SET NEW.last_updated = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- Add view for active promotions
CREATE OR REPLACE VIEW active_promotions AS
SELECT * FROM promotions 
WHERE is_active = TRUE 
AND start_date <= NOW() 
AND end_date >= NOW();

-- Add view for user booking analytics
CREATE OR REPLACE VIEW user_booking_analytics AS
SELECT 
    b.customer_id,
    COUNT(*) as total_bookings,
    SUM(b.total_amount) as total_spent,
    AVG(b.total_amount) as avg_booking_value,
    COUNT(DISTINCT b.item_name) as unique_movies,
    MAX(b.created_at) as last_booking_date
FROM bookings b
WHERE b.status = 'Paid'
GROUP BY b.customer_id;
