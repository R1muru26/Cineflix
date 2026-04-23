-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cineflix
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `booking`
--

DROP TABLE IF EXISTS `booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `ShowtimeID` int(11) DEFAULT NULL,
  `BookingDate` datetime DEFAULT NULL,
  `Quantity` int(11) NOT NULL,
  `BookingType` varchar(50) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`BookingID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `ShowtimeID` (`ShowtimeID`),
  CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customeruser` (`CustomerID`),
  CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`ShowtimeID`) REFERENCES `showtime` (`ShowtimeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` varchar(32) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(120) DEFAULT NULL,
  `customer_email` varchar(120) DEFAULT NULL,
  `item_type` enum('movie','event') NOT NULL DEFAULT 'movie',
  `item_name` varchar(255) NOT NULL,
  `event_option` varchar(120) DEFAULT NULL,
  `show_date` date DEFAULT NULL,
  `show_time` varchar(64) DEFAULT NULL,
  `venue` varchar(120) DEFAULT NULL,
  `seats` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `addons` longtext DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Paid',
  `refund_reason` text DEFAULT NULL,
  `refund_status` varchar(60) DEFAULT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_type` varchar(20) DEFAULT NULL,
  `discount_original_total` decimal(10,2) DEFAULT NULL,
  `discounted_total` decimal(10,2) DEFAULT NULL,
  `discount_status` varchar(20) DEFAULT NULL,
  `discount_id_number` varchar(64) DEFAULT NULL,
  `discount_id_path` varchar(255) DEFAULT NULL,
  `parking_number` varchar(20) DEFAULT NULL,
  `ShowtimeID` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_id` (`booking_id`),
  KEY `fk_bookings_showtime` (`ShowtimeID`),
  CONSTRAINT `fk_bookings_showtime` FOREIGN KEY (`ShowtimeID`) REFERENCES `showtime` (`ShowtimeID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customeruser`
--

DROP TABLE IF EXISTS `customeruser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customeruser` (
  `CustomerID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `ResetToken` varchar(255) DEFAULT NULL,
  `ResetTokenExpiry` datetime DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `OTP` varchar(6) DEFAULT NULL,
  `OTPExpiry` datetime DEFAULT NULL,
  `IsVerified` tinyint(1) DEFAULT 0,
  `profile_picture` varchar(500) DEFAULT NULL,
  `PhoneNo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `food`
--

DROP TABLE IF EXISTS `food`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food` (
  `FoodOrderID` varchar(64) NOT NULL,
  `ItemID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` varchar(64) NOT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `ItemName` varchar(255) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitPrice` float NOT NULL,
  `TotalPrice` float NOT NULL,
  `OrderStatus` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ItemID`),
  KEY `BookingID` (`BookingID`),
  CONSTRAINT `food_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings_erd` (`BookingID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movie`
--

DROP TABLE IF EXISTS `movie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movie` (
  `MovieID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `Duration` int(11) NOT NULL,
  `Genre` varchar(100) DEFAULT NULL,
  `ReleaseDate` date DEFAULT NULL,
  `Rating` float DEFAULT NULL,
  `TrailerURL` varchar(500) DEFAULT NULL,
  `section` varchar(20) NOT NULL DEFAULT 'more_movies',
  PRIMARY KEY (`MovieID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movie_schedules`
--

DROP TABLE IF EXISTS `movie_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movie_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `movie_title` varchar(255) NOT NULL,
  `theatre_type` enum('Standard','IMAX','3D','Directors Club') NOT NULL DEFAULT 'Standard',
  `cinema_hall` varchar(50) NOT NULL,
  `show_date` date NOT NULL,
  `show_time` varchar(50) NOT NULL,
  `end_time` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_seats` int(11) NOT NULL DEFAULT 80,
  `total_seats` int(11) NOT NULL DEFAULT 80,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_movie_theatre` (`movie_id`,`theatre_type`),
  KEY `idx_date_time` (`show_date`,`show_time`),
  KEY `idx_theatre_hall` (`theatre_type`,`cinema_hall`),
  CONSTRAINT `movie_schedules_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movie` (`MovieID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parking`
--

DROP TABLE IF EXISTS `parking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parking` (
  `ParkingID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` varchar(64) NOT NULL,
  `SlotNumber` varchar(50) DEFAULT NULL,
  `VehicleType` varchar(50) DEFAULT NULL,
  `VehiclePlate` varchar(50) DEFAULT NULL,
  `EntryTime` datetime DEFAULT NULL,
  `ExitTime` datetime DEFAULT NULL,
  `ParkingFee` float DEFAULT NULL,
  `ParkingStatus` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ParkingID`),
  KEY `BookingID` (`BookingID`),
  CONSTRAINT `parking_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings_erd` (`BookingID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parking_spaces`
--

DROP TABLE IF EXISTS `parking_spaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parking_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parking_number` varchar(20) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `booking_id` varchar(32) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_number` (`parking_number`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` varchar(32) NOT NULL,
  `Amount` float NOT NULL,
  `PaymentMethod` varchar(100) NOT NULL,
  `RefNumber` varchar(100) DEFAULT NULL,
  `PaymentDate` datetime NOT NULL,
  `PaymentStatus` varchar(50) NOT NULL,
  PRIMARY KEY (`PaymentID`),
  KEY `BookingID` (`BookingID`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `seat`
--

DROP TABLE IF EXISTS `seat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seat` (
  `SeatID` int(11) NOT NULL AUTO_INCREMENT,
  `TheaterID` int(11) DEFAULT NULL,
  `SeatNumber` varchar(10) DEFAULT NULL,
  `AvailabilityStatus` tinyint(1) DEFAULT 1,
  `MovieName` varchar(255) DEFAULT NULL,
  `TheaterType` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`SeatID`),
  KEY `TheaterID` (`TheaterID`),
  CONSTRAINT `seat_ibfk_1` FOREIGN KEY (`TheaterID`) REFERENCES `theater` (`TheaterID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `showtime`
--

DROP TABLE IF EXISTS `showtime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `showtime` (
  `ShowtimeID` int(11) NOT NULL AUTO_INCREMENT,
  `MovieID` int(11) DEFAULT NULL,
  `TheaterID` int(11) DEFAULT NULL,
  `StartTime` datetime NOT NULL,
  `EndTime` datetime NOT NULL,
  PRIMARY KEY (`ShowtimeID`),
  KEY `MovieID` (`MovieID`),
  KEY `TheaterID` (`TheaterID`),
  CONSTRAINT `showtime_ibfk_1` FOREIGN KEY (`MovieID`) REFERENCES `movie` (`MovieID`),
  CONSTRAINT `showtime_ibfk_2` FOREIGN KEY (`TheaterID`) REFERENCES `theater` (`TheaterID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `theater`
--

DROP TABLE IF EXISTS `theater`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theater` (
  `TheaterID` int(11) NOT NULL AUTO_INCREMENT,
  `TheaterName` varchar(255) NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `TheaterType` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`TheaterID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket` (
  `TicketID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` varchar(32) NOT NULL,
  `SeatID` int(11) NOT NULL,
  `TicketNumber` varchar(100) NOT NULL,
  `Status` varchar(50) NOT NULL,
  PRIMARY KEY (`TicketID`),
  KEY `BookingID` (`BookingID`),
  KEY `SeatID` (`SeatID`),
  CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`SeatID`) REFERENCES `seat` (`SeatID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'info',
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `action_text` varchar(50) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_id` (`notification_id`),
  KEY `idx_user_read` (`user_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-13  8:51:41
