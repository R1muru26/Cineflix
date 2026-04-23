SET FOREIGN_KEY_CHECKS = 0;

-- 1. Modify `customeruser` mapping (use backticks for punctuation in column names)
SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customeruser' AND column_name='CustomerName');
SET @stat := if(@exist=0, 'ALTER TABLE `customeruser` ADD COLUMN `CustomerName` VARCHAR(255) GENERATED ALWAYS AS (`Name`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customeruser' AND column_name='PhoneNo.');
SET @stat := if(@exist=0, 'ALTER TABLE `customeruser` ADD COLUMN `PhoneNo.` VARCHAR(50) GENERATED ALWAYS AS (`PhoneNo`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Modify `bookings` mapping
SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='bookings' AND column_name='BookingID');
SET @stat := if(@exist=0, 'ALTER TABLE `bookings` ADD COLUMN `BookingID` INT GENERATED ALWAYS AS (`id`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='bookings' AND column_name='CustomerID_ERD');
SET @stat := if(@exist=0, 'ALTER TABLE `bookings` ADD COLUMN `CustomerID_ERD` INT GENERATED ALWAYS AS (`customer_id`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='bookings' AND column_name='Quantity_ERD');
SET @stat := if(@exist=0, 'ALTER TABLE `bookings` ADD COLUMN `Quantity_ERD` INT GENERATED ALWAYS AS (`quantity`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='bookings' AND column_name='BookingDate');
SET @stat := if(@exist=0, 'ALTER TABLE `bookings` ADD COLUMN `BookingDate` DATE GENERATED ALWAYS AS (DATE(`created_at`)) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT count(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='bookings' AND column_name='BookingStatus');
SET @stat := if(@exist=0, 'ALTER TABLE `bookings` ADD COLUMN `BookingStatus` VARCHAR(50) GENERATED ALWAYS AS (`status`) VIRTUAL', 'SELECT 1');
PREPARE stmt FROM @stat; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Drop incompatible old ERD tables
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `ticket`;
DROP TABLE IF EXISTS `parking`;
DROP TABLE IF EXISTS `food`;
DROP TABLE IF EXISTS `food_orders`;

-- 4. Create new exact ERD tables
CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) NOT NULL,
  `Amount` float NOT NULL,
  `PaymentMethod` varchar(100) NOT NULL,
  `RefNumber` varchar(100) DEFAULT NULL,
  `PaymentDate` date NOT NULL,
  `PaymentStatus` varchar(50) NOT NULL,
  PRIMARY KEY (`PaymentID`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ticket` (
  `TicketID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) NOT NULL,
  `SeatID` int(11) NOT NULL,
  `TicketNumber` varchar(100) NOT NULL,
  `Status` varchar(50) NOT NULL,
  PRIMARY KEY (`TicketID`),
  CONSTRAINT `fk_ticket_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_seat` FOREIGN KEY (`SeatID`) REFERENCES `seat` (`SeatID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `parking` (
  `ParkingID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) NOT NULL,
  `SlotNumber` varchar(50) DEFAULT NULL,
  `VehicleType` varchar(50) DEFAULT NULL,
  `VehiclePlate` varchar(50) DEFAULT NULL,
  `EntryTime` datetime DEFAULT NULL,
  `ExitTime` datetime DEFAULT NULL,
  `ParkingFee` float DEFAULT NULL,
  `ParkingStatus` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ParkingID`),
  CONSTRAINT `fk_parking_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `food_orders` (
  `FoodOrderID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) NOT NULL,
  `ItemName` varchar(255) NOT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitPrice` float NOT NULL,
  `TotalPrice` float NOT NULL,
  `OrderStatus` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`FoodOrderID`),
  CONSTRAINT `fk_food_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
