-- Movie Schedules Database Schema for CineFlix
-- This script creates the movie schedules table with theatre type support

-- Create movie_schedules table
CREATE TABLE IF NOT EXISTS movie_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    movie_title VARCHAR(255) NOT NULL,
    theatre_type ENUM('2d', 'standard', '3d', 'imax', 'directors') NOT NULL DEFAULT '2d',
    cinema_hall VARCHAR(50) NOT NULL, -- e.g., 'Cinema 1', 'Cinema 2', 'IMAX Hall'
    show_date DATE NOT NULL,
    show_time VARCHAR(50) NOT NULL, -- e.g., '10:00 AM', '1:30 PM', '7:00 PM'
    end_time VARCHAR(50) NOT NULL, -- e.g., '12:00 PM', '3:30 PM', '9:30 PM'
    price DECIMAL(10,2) NOT NULL,
    available_seats INT NOT NULL DEFAULT 80,
    total_seats INT NOT NULL DEFAULT 80,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_movie_theatre (movie_id, theatre_type),
    INDEX idx_date_time (show_date, show_time),
    INDEX idx_theatre_hall (theatre_type, cinema_hall),
    FOREIGN KEY (movie_id) REFERENCES Movie(MovieID) ON DELETE CASCADE
);

-- Insert sample schedules for each theatre type (4+ schedules each)
-- Note: Replace movie_id values with actual MovieID from your Movie table

-- 2D Theatre Schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES
(1, 'Sample Movie 1', '2d', 'Cinema 1', CURDATE(), '10:00 AM', '12:00 PM', 350.00),
(1, 'Sample Movie 1', '2d', 'Cinema 1', CURDATE(), '1:30 PM', '3:30 PM', 350.00),
(1, 'Sample Movie 1', '2d', 'Cinema 1', CURDATE(), '4:00 PM', '6:00 PM', 350.00),
(1, 'Sample Movie 1', '2d', 'Cinema 1', CURDATE(), '7:00 PM', '9:00 PM', 350.00),
(1, 'Sample Movie 1', '2d', 'Cinema 1', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00 AM', '12:00 PM', 350.00);

-- Standard Theatre Schedules  
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES
(2, 'Sample Movie 2', 'standard', 'Cinema 2', CURDATE(), '11:00 AM', '1:00 PM', 350.00),
(2, 'Sample Movie 2', 'standard', 'Cinema 2', CURDATE(), '2:00 PM', '4:00 PM', 350.00),
(2, 'Sample Movie 2', 'standard', 'Cinema 2', CURDATE(), '5:00 PM', '7:00 PM', 350.00),
(2, 'Sample Movie 2', 'standard', 'Cinema 2', CURDATE(), '8:00 PM', '10:00 PM', 350.00),
(2, 'Sample Movie 2', 'standard', 'Cinema 2', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00 AM', '1:00 PM', 350.00);

-- 3D Theatre Schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES
(3, 'Sample Movie 3', '3d', 'Cinema 3', CURDATE(), '10:30 AM', '12:30 PM', 410.00),
(3, 'Sample Movie 3', '3d', 'Cinema 3', CURDATE(), '1:00 PM', '3:00 PM', 410.00),
(3, 'Sample Movie 3', '3d', 'Cinema 3', CURDATE(), '4:30 PM', '6:30 PM', 410.00),
(3, 'Sample Movie 3', '3d', 'Cinema 3', CURDATE(), '7:30 PM', '9:30 PM', 410.00),
(3, 'Sample Movie 3', '3d', 'Cinema 3', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30 AM', '12:30 PM', 410.00);

-- IMAX Theatre Schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES
(4, 'Sample Movie 4', 'imax', 'IMAX Hall', CURDATE(), '11:00 AM', '1:30 PM', 480.00),
(4, 'Sample Movie 4', 'imax', 'IMAX Hall', CURDATE(), '2:00 PM', '4:30 PM', 480.00),
(4, 'Sample Movie 4', 'imax', 'IMAX Hall', CURDATE(), '5:00 PM', '7:30 PM', 480.00),
(4, 'Sample Movie 4', 'imax', 'IMAX Hall', CURDATE(), '8:00 PM', '10:30 PM', 480.00),
(4, 'Sample Movie 4', 'imax', 'IMAX Hall', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00 AM', '1:30 PM', 480.00);

-- Directors Club Theatre Schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) VALUES
(5, 'Sample Movie 5', 'directors', 'Directors Club', CURDATE(), '12:00 PM', '2:30 PM', 600.00),
(5, 'Sample Movie 5', 'directors', 'Directors Club', CURDATE(), '3:00 PM', '5:30 PM', 600.00),
(5, 'Sample Movie 5', 'directors', 'Directors Club', CURDATE(), '6:00 PM', '8:30 PM', 600.00),
(5, 'Sample Movie 5', 'directors', 'Directors Club', CURDATE(), '9:00 PM', '11:30 PM', 600.00),
(5, 'Sample Movie 5', 'directors', 'Directors Club', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00 PM', '2:30 PM', 600.00);

-- Create view for active schedules
CREATE OR REPLACE VIEW active_movie_schedules AS
SELECT 
    ms.*,
    m.Title as movie_title,
    m.PosterPath as movie_poster,
    m.Genre as movie_genre,
    m.Rating as movie_rating,
    m.Duration as movie_duration
FROM movie_schedules ms
LEFT JOIN Movie m ON ms.movie_id = m.MovieID
WHERE ms.is_active = TRUE 
AND ms.show_date >= CURDATE()
ORDER BY ms.show_date, ms.show_time, ms.theatre_type;
