-- Sample Movie Schedules Data Generator
-- This script generates sample schedules for existing movies
-- Run this after creating the movie_schedules table

-- Get existing movies from Movie table
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    '2d' as theatre_type,
    CASE 
        WHEN MovieID % 4 = 1 THEN 'Cinema 1'
        WHEN MovieID % 4 = 2 THEN 'Cinema 2'
        WHEN MovieID % 4 = 3 THEN 'Cinema 3'
        ELSE 'Cinema 4'
    END as cinema_hall,
    CURDATE() as show_date,
    CASE 
        WHEN MovieID % 4 = 1 THEN '10:00 AM'
        WHEN MovieID % 4 = 2 THEN '1:00 PM'
        WHEN MovieID % 4 = 3 THEN '4:00 PM'
        ELSE '7:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 4 = 1 THEN '12:00 PM'
        WHEN MovieID % 4 = 2 THEN '3:00 PM'
        WHEN MovieID % 4 = 3 THEN '6:00 PM'
        ELSE '9:00 PM'
    END as end_time,
    350.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

-- Generate Standard theatre schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'standard' as theatre_type,
    CASE 
        WHEN MovieID % 4 = 1 THEN 'Cinema 5'
        WHEN MovieID % 4 = 2 THEN 'Cinema 6'
        WHEN MovieID % 4 = 3 THEN 'Cinema 7'
        ELSE 'Cinema 8'
    END as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) as show_date,
    CASE 
        WHEN MovieID % 4 = 1 THEN '11:00 AM'
        WHEN MovieID % 4 = 2 THEN '2:00 PM'
        WHEN MovieID % 4 = 3 THEN '5:00 PM'
        ELSE '8:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 4 = 1 THEN '1:00 PM'
        WHEN MovieID % 4 = 2 THEN '4:00 PM'
        WHEN MovieID % 4 = 3 THEN '7:00 PM'
        ELSE '10:00 PM'
    END as end_time,
    350.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

-- Generate 3D theatre schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    '3d' as theatre_type,
    CASE 
        WHEN MovieID % 4 = 1 THEN 'Cinema 9'
        WHEN MovieID % 4 = 2 THEN 'Cinema 10'
        WHEN MovieID % 4 = 3 THEN 'Cinema 11'
        ELSE 'Cinema 12'
    END as cinema_hall,
    CURDATE() as show_date,
    CASE 
        WHEN MovieID % 4 = 1 THEN '10:30 AM'
        WHEN MovieID % 4 = 2 THEN '1:30 PM'
        WHEN MovieID % 4 = 3 THEN '4:30 PM'
        ELSE '7:30 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 4 = 1 THEN '12:30 PM'
        WHEN MovieID % 4 = 2 THEN '3:30 PM'
        WHEN MovieID % 4 = 3 THEN '6:30 PM'
        ELSE '9:30 PM'
    END as end_time,
    410.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

-- Generate IMAX theatre schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'imax' as theatre_type,
    CASE 
        WHEN MovieID % 2 = 0 THEN 'IMAX Hall 1'
        ELSE 'IMAX Hall 2'
    END as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) as show_date,
    CASE 
        WHEN MovieID % 2 = 0 THEN '11:00 AM'
        WHEN MovieID % 4 = 1 THEN '2:00 PM'
        WHEN MovieID % 4 = 2 THEN '5:00 PM'
        ELSE '8:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 2 = 0 THEN '1:30 PM'
        WHEN MovieID % 4 = 1 THEN '4:30 PM'
        WHEN MovieID % 4 = 2 THEN '7:30 PM'
        ELSE '10:30 PM'
    END as end_time,
    480.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

-- Generate Directors Club theatre schedules
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'directors' as theatre_type,
    'Directors Club' as cinema_hall,
    CURDATE() as show_date,
    CASE 
        WHEN MovieID % 3 = 1 THEN '12:00 PM'
        WHEN MovieID % 3 = 2 THEN '3:00 PM'
        ELSE '6:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 3 = 1 THEN '2:30 PM'
        WHEN MovieID % 3 = 2 THEN '5:30 PM'
        ELSE '8:30 PM'
    END as end_time,
    600.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 8;

-- Add more schedules for each theatre type to ensure 4+ per movie
INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    '2d' as theatre_type,
    CONCAT('Cinema ', (MovieID % 6) + 1) as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as show_date,
    CASE 
        WHEN MovieID % 3 = 0 THEN '9:00 AM'
        WHEN MovieID % 3 = 1 THEN '12:00 PM'
        WHEN MovieID % 3 = 2 THEN '3:00 PM'
        ELSE '6:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 3 = 0 THEN '11:00 AM'
        WHEN MovieID % 3 = 1 THEN '2:00 PM'
        WHEN MovieID % 3 = 2 THEN '5:00 PM'
        ELSE '8:00 PM'
    END as end_time,
    350.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'standard' as theatre_type,
    CONCAT('Cinema ', (MovieID % 6) + 7) as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as show_date,
    CASE 
        WHEN MovieID % 3 = 0 THEN '10:00 AM'
        WHEN MovieID % 3 = 1 THEN '1:00 PM'
        WHEN MovieID % 3 = 2 THEN '4:00 PM'
        ELSE '7:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 3 = 0 THEN '12:00 PM'
        WHEN MovieID % 3 = 1 THEN '3:00 PM'
        WHEN MovieID % 3 = 2 THEN '6:00 PM'
        ELSE '9:00 PM'
    END as end_time,
    350.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    '3d' as theatre_type,
    CONCAT('Cinema ', (MovieID % 6) + 13) as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as show_date,
    CASE 
        WHEN MovieID % 3 = 0 THEN '10:30 AM'
        WHEN MovieID % 3 = 1 THEN '1:30 PM'
        WHEN MovieID % 3 = 2 THEN '4:30 PM'
        ELSE '7:30 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 3 = 0 THEN '12:30 PM'
        WHEN MovieID % 3 = 1 THEN '3:30 PM'
        WHEN MovieID % 3 = 2 THEN '6:30 PM'
        ELSE '9:30 PM'
    END as end_time,
    410.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'imax' as theatre_type,
    CASE 
        WHEN MovieID % 2 = 0 THEN 'IMAX Hall 1'
        ELSE 'IMAX Hall 2'
    END as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as show_date,
    CASE 
        WHEN MovieID % 2 = 0 THEN '11:00 AM'
        WHEN MovieID % 4 = 1 THEN '2:00 PM'
        WHEN MovieID % 4 = 2 THEN '5:00 PM'
        ELSE '8:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 2 = 0 THEN '1:30 PM'
        WHEN MovieID % 4 = 1 THEN '4:30 PM'
        WHEN MovieID % 4 = 2 THEN '7:30 PM'
        ELSE '10:30 PM'
    END as end_time,
    480.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 10;

INSERT IGNORE INTO movie_schedules (movie_id, movie_title, theatre_type, cinema_hall, show_date, show_time, end_time, price) 
SELECT 
    MovieID,
    Title,
    'directors' as theatre_type,
    'Directors Club' as cinema_hall,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as show_date,
    CASE 
        WHEN MovieID % 3 = 1 THEN '12:00 PM'
        WHEN MovieID % 3 = 2 THEN '3:00 PM'
        ELSE '6:00 PM'
    END as show_time,
    CASE 
        WHEN MovieID % 3 = 1 THEN '2:30 PM'
        WHEN MovieID % 3 = 2 THEN '5:30 PM'
        ELSE '8:30 PM'
    END as end_time,
    600.00 as price
FROM Movie 
WHERE MovieID IS NOT NULL
LIMIT 8;
