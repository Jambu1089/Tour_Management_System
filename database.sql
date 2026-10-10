CREATE DATABASE IF NOT EXISTS `tour_management_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `tour_management_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `GUIDELINES`;
DROP TABLE IF EXISTS `REVIEW`;
DROP TABLE IF EXISTS `GUIDE_ASSIGNMENT`;
DROP TABLE IF EXISTS `BOOKING`;
DROP TABLE IF EXISTS `TOUR_PACKAGE`;
DROP TABLE IF EXISTS `DESTINATION`;
DROP TABLE IF EXISTS `CATEGORY`;
DROP TABLE IF EXISTS `USER`;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE `USER` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) NOT NULL UNIQUE,
  `Password` VARCHAR(255) NOT NULL,
  `Phone` VARCHAR(30) DEFAULT '+880 1712 345678',
  `Role` ENUM('Tourist', 'Tour Guide', 'Agency Manager', 'Admin') NOT NULL,
  `Status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `CATEGORY` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(100) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `DESTINATION` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Category_ID` INT(11) NOT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `Location` VARCHAR(150) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Image` TEXT DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_dest_category` (`Category_ID`),
  CONSTRAINT `fk_destination_category` FOREIGN KEY (`Category_ID`) REFERENCES `CATEGORY` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `TOUR_PACKAGE` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Destination_ID` INT(11) NOT NULL,
  `Package_Name` VARCHAR(150) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Duration_Days` INT(11) NOT NULL DEFAULT 1,
  `Capacity` INT(11) NOT NULL DEFAULT 20,
  `Inclusions` TEXT DEFAULT NULL,
  `Image` TEXT DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_pkg_destination` (`Destination_ID`),
  CONSTRAINT `fk_package_destination` FOREIGN KEY (`Destination_ID`) REFERENCES `DESTINATION` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `BOOKING` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `User_ID` INT(11) NOT NULL,
  `Tour_Package_ID` INT(11) NOT NULL,
  `Booking_Date` DATE NOT NULL,
  `Travel_Date` DATE NOT NULL,
  `Persons` INT(11) NOT NULL DEFAULT 1,
  `Total_Price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Status` ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`ID`),
  KEY `idx_booking_user` (`User_ID`),
  KEY `idx_booking_package` (`Tour_Package_ID`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`User_ID`) REFERENCES `USER` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_package` FOREIGN KEY (`Tour_Package_ID`) REFERENCES `TOUR_PACKAGE` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `GUIDE_ASSIGNMENT` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Booking_ID` INT(11) NOT NULL,
  `Guide_ID` INT(11) NOT NULL,
  `Assignment_Date` DATE NOT NULL,
  `Status` ENUM('Scheduled', 'In Progress', 'Completed', 'Delayed') NOT NULL DEFAULT 'Scheduled',
  PRIMARY KEY (`ID`),
  KEY `idx_ga_booking` (`Booking_ID`),
  KEY `idx_ga_guide` (`Guide_ID`),
  CONSTRAINT `fk_assignment_booking` FOREIGN KEY (`Booking_ID`) REFERENCES `BOOKING` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_guide` FOREIGN KEY (`Guide_ID`) REFERENCES `USER` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `REVIEW` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Tour_Package_ID` INT(11) NOT NULL,
  `User_ID` INT(11) NOT NULL,
  `Rating` INT(1) NOT NULL CHECK (`Rating` BETWEEN 1 AND 5),
  `Comment` TEXT DEFAULT NULL,
  `Review_Date` DATE NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_review_package` (`Tour_Package_ID`),
  KEY `idx_review_user` (`User_ID`),
  CONSTRAINT `fk_review_package` FOREIGN KEY (`Tour_Package_ID`) REFERENCES `TOUR_PACKAGE` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`User_ID`) REFERENCES `USER` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `GUIDELINES` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Guide_ID` INT(11) NOT NULL,
  `Note` TEXT NOT NULL,
  `Notice_Date` DATE NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_guidelines_guide` (`Guide_ID`),
  CONSTRAINT `fk_guidelines_guide` FOREIGN KEY (`Guide_ID`) REFERENCES `USER` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `USER` (`ID`, `Name`, `Email`, `Password`, `Phone`, `Role`, `Status`) VALUES
(1, 'Admin User', 'admin@tour.com', 'admin123', '+880 1712 000001', 'Admin', 'Active'),
(2, 'Agency Manager', 'manager@tour.com', 'manager123', '+880 1712 000002', 'Agency Manager', 'Active'),
(3, 'Tour Guide Fahim', 'guide@tour.com', 'guide123', '+880 1712 000003', 'Tour Guide', 'Active'),
(4, 'Sayedur Leon', 'tourist@tour.com', 'tourist123', '+880 1712 345678', 'Tourist', 'Active'),
(5, 'Fahim Rahman', 'fahim@tour.com', 'tourist123', '+880 1712 345679', 'Tourist', 'Active'),
(6, 'Chandrima Purba', 'chandrima@tour.com', 'tourist123', '+880 1712 345680', 'Tourist', 'Active'),
(7, 'Nafis Bondhon', 'nafis@tour.com', 'tourist123', '+880 1712 345681', 'Tourist', 'Active');


INSERT INTO `CATEGORY` (`ID`, `Name`, `Description`) VALUES
(1, 'Adventure', 'Outdoor activities, hiking, hill tracks, and mountain expeditions'),
(2, 'City Tour', 'Urban sightseeing, architectural highlights, and food walks'),
(3, 'Beach', 'Coastal scenery, sandy shores, and marine recreation'),
(4, 'Eco-Tour', 'Forests, wildlife conservation reserves, and tea estates');

INSERT INTO `DESTINATION` (`ID`, `Category_ID`, `Name`, `Location`, `Description`, `Image`) VALUES
(1, 3, 'Kuwakata Sea Beach', 'Kuakata, Bangladesh', 'Panoramic sea beach known for unobstructed sunrise and sunset views over the Bay of Bengal.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400'),
(2, 4, 'Sylhet Tea Garden', 'Sylhet, Bangladesh', 'Lush rolling green tea hills, serene streams, and fresh rainforest trails.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=400'),
(3, 1, 'Sajek Valley', 'Bandarban, Bangladesh', 'Valley nestled high among the clouds with breathtaking mountain viewpoints.', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=400'),
(4, 3, 'Cox\'s Bazar Sea Beach', 'Chittagong, Bangladesh', 'The longest natural unbroken sea beach in the world with sunset views.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=400');


INSERT INTO `TOUR_PACKAGE` (`ID`, `Destination_ID`, `Package_Name`, `Description`, `Price`, `Duration_Days`, `Capacity`, `Inclusions`, `Image`) VALUES
(1, 1, 'Kuwakata Sea Beach', 'Experience sunrise and sunset at Kuwakata sea beach with resort stay, meals, and local guide.', 200.00, 2, 20, 'Resort Stay, Meals, Transport, Guide', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400'),
(2, 2, 'Sylhet Tea Garden', 'Walk through organic tea estates, visit Ratargul Swamp Forest, and cruise the Lalakhal river.', 300.00, 3, 20, 'Eco-Cottage, Breakfast, River Boat, Guide', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=400'),
(3, 3, 'Sajek Valley', 'Cloud camp adventure in Sajek Valley with 4x4 Chander Gari transport and Helipad sunset tour.', 250.00, 1, 25, 'Hill Resort, 4x4 Chander Gari, Guide', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=400'),
(4, 4, 'Cox\'s Bazar Sea Beach', 'Relax along the world\'s longest beach with hotel accommodations, seafood dinners, and marine drive tour.', 500.00, 2, 30, 'Beach Resort, Meals, Marine Drive Transit', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=400');

INSERT INTO `BOOKING` (`ID`, `User_ID`, `Tour_Package_ID`, `Booking_Date`, `Travel_Date`, `Persons`, `Total_Price`, `Status`) VALUES
(1, 5, 1, '2026-09-01', '2026-09-20', 12, 2400.00, 'Pending'),
(2, 5, 4, '2026-09-02', '2026-09-20', 1, 500.00, 'Pending'),
(3, 5, 3, '2026-08-25', '2026-09-20', 17, 4250.00, 'Approved'),
(4, 5, 1, '2026-08-28', '2026-09-20', 14, 2800.00, 'Approved'),
(5, 6, 2, '2026-08-20', '2026-09-25', 2, 600.00, 'Approved');

INSERT INTO `GUIDE_ASSIGNMENT` (`ID`, `Booking_ID`, `Guide_ID`, `Assignment_Date`, `Status`) VALUES
(1, 4, 3, '2026-09-15', 'In Progress'),
(2, 3, 3, '2026-09-12', 'Scheduled'),
(3, 5, 4, '2026-09-18', 'Scheduled');

INSERT INTO `REVIEW` (`ID`, `Tour_Package_ID`, `User_ID`, `Rating`, `Comment`, `Review_Date`) VALUES
(1, 3, 5, 5, 'Sajek Valley was breathtaking! The cloud-watching experience from the cottage was unforgettable.', '2026-09-10');

INSERT INTO `GUIDELINES` (`ID`, `Guide_ID`, `Note`, `Notice_Date`) VALUES
(1, 3, 'Kuwakata Sea Beach - Meeting at 8:00 AM at Hotel Lobby', '2026-09-18'),
(2, 3, 'Sajek Valley - Assemble at Dighinala Army Checkpost by 10:00 AM for the military escort.', '2026-09-15'),
(3, 3, 'Thailand Explorer - Bring original passports and visa docs.', '2026-06-22'),
(4, 3, 'Switzerland Trip - Carry thermal jackets and warm boots.', '2026-06-10');