DROP DATABASE IF EXISTS `tour_management_db`;
CREATE DATABASE `tour_management_db`;
USE `tour_management_db`;

-- 1. USER TABLE
CREATE TABLE IF NOT EXISTS `USER` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) NOT NULL UNIQUE,
  `Password` VARCHAR(255) NOT NULL,
  `Phone` VARCHAR(20) DEFAULT '+880 1712 345678',
  `Role` ENUM('Tourist', 'Tour Guide', 'Agency Manager', 'Admin') NOT NULL,
  `Status` ENUM('Active', 'Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CATEGORY TABLE
CREATE TABLE IF NOT EXISTS `CATEGORY` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL,
  `Description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. DESTINATION TABLE
CREATE TABLE IF NOT EXISTS `DESTINATION` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Category_ID` INT(11) NOT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `Location` VARCHAR(150) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Image` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400',
  FOREIGN KEY (`Category_ID`) REFERENCES `CATEGORY`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TOUR_PACKAGE TABLE
CREATE TABLE IF NOT EXISTS `TOUR_PACKAGE` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Destination_ID` INT(11) NOT NULL,
  `Package_Name` VARCHAR(150) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Price` DECIMAL(10,2) NOT NULL,
  `Duration_Days` INT(11) NOT NULL,
  `Capacity` INT(11) NOT NULL DEFAULT 20,
  `Inclusions` TEXT DEFAULT NULL,
  `Image` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400',
  FOREIGN KEY (`Destination_ID`) REFERENCES `DESTINATION`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. BOOKING TABLE
CREATE TABLE IF NOT EXISTS `BOOKING` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `User_ID` INT(11) NOT NULL,
  `Tour_Package_ID` INT(11) NOT NULL,
  `Booking_Date` DATE NOT NULL,
  `Travel_Date` DATE NOT NULL,
  `Persons` INT(11) NOT NULL DEFAULT 1,
  `Total_Price` DECIMAL(10,2) NOT NULL,
  `Status` ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
  FOREIGN KEY (`User_ID`) REFERENCES `USER`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`Tour_Package_ID`) REFERENCES `TOUR_PACKAGE`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. GUIDE_ASSIGNMENT TABLE
CREATE TABLE IF NOT EXISTS `GUIDE_ASSIGNMENT` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Booking_ID` INT(11) NOT NULL,
  `Guide_ID` INT(11) NOT NULL,
  `Assignment_Date` DATE NOT NULL,
  `Status` ENUM('Scheduled', 'In Progress', 'Completed', 'Delayed') DEFAULT 'Scheduled',
  FOREIGN KEY (`Booking_ID`) REFERENCES `BOOKING`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`Guide_ID`) REFERENCES `USER`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. REVIEW TABLE
CREATE TABLE IF NOT EXISTS `REVIEW` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Tour_Package_ID` INT(11) NOT NULL,
  `User_ID` INT(11) NOT NULL,
  `Rating` INT(1) NOT NULL CHECK (`Rating` BETWEEN 1 AND 5),
  `Comment` TEXT DEFAULT NULL,
  `Review_Date` DATE NOT NULL,
  FOREIGN KEY (`Tour_Package_ID`) REFERENCES `TOUR_PACKAGE`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`User_ID`) REFERENCES `USER`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. GUIDELINES TABLE
CREATE TABLE IF NOT EXISTS `GUIDELINES` (
  `ID` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `Guide_ID` INT(11) NOT NULL,
  `Note` TEXT NOT NULL,
  `Notice_Date` DATE NOT NULL,
  FOREIGN KEY (`Guide_ID`) REFERENCES `USER`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================= SEED DATA =================

-- 1. Users (Admin, Agency Manager, Tour Guides, Tourists)
INSERT INTO `USER` (`ID`, `Name`, `Email`, `Password`, `Phone`, `Role`, `Status`) VALUES
(1, 'Admin User', 'admin@tour.com', 'admin123', '+880 1712 000001', 'Admin', 'Active'),
(2, 'Agency Manager', 'manager@tour.com', 'manager123', '+880 1712 000002', 'Agency Manager', 'Active'),
(3, 'Tour Guide Fahim', 'guide@tour.com', 'guide123', '+880 1712 000003', 'Tour Guide', 'Active'),
(4, 'David Johnson', 'david@tour.com', 'guide123', '+880 1712 000004', 'Tour Guide', 'Active'),
(5, 'Sayedur Leon', 'tourist@tour.com', 'tourist123', '+880 1712 345678', 'Tourist', 'Active'),
(6, 'Emma Watson', 'emma@tour.com', 'tourist123', '+880 1712 345679', 'Tourist', 'Active'),
(7, 'Michael Lee', 'michael@tour.com', 'tourist123', '+880 1712 345680', 'Tourist', 'Active'),
(8, 'Olivia Brown', 'olivia@tour.com', 'tourist123', '+880 1712 345681', 'Tourist', 'Active');

-- 2. Categories
INSERT INTO `CATEGORY` (`ID`, `Name`, `Description`) VALUES
(1, 'Adventure', 'Action packed outdoor activities and mountain climbs'),
(2, 'City Tour', 'Urban discovery, historical architecture and landmarks'),
(3, 'Beach', 'Tropical islands and water recreation excursions'),
(4, 'Eco-Tour', 'Nature and wildlife preservation trips');

-- 3. Destinations
INSERT INTO `DESTINATION` (`ID`, `Category_ID`, `Name`, `Location`, `Description`, `Image`) VALUES
(1, 1, 'Bali Adventure', 'Bali, Indonesia', 'Tropical paradise with temples, active volcano trails, and surf spots.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400'),
(2, 1, 'Swiss Alps Escape', 'Switzerland', 'Alpine mountain ranges, panoramic scenic railways, and skiing chalets.', 'https://images.unsplash.com/photo-1530122037265-a5f1f91d3b99?w=400'),
(3, 2, 'Thailand Explorer', 'Bangkok & Phuket, Thailand', 'Vibrant street food markets, historical temples, and coastal islands.', 'https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=400'),
(4, 3, 'Maldives Paradise', 'Maldives', 'Crystal clear waters and private luxury overwater bungalows.', 'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?w=400');

-- 4. Tour Packages
INSERT INTO `TOUR_PACKAGE` (`ID`, `Destination_ID`, `Package_Name`, `Description`, `Price`, `Duration_Days`, `Capacity`, `Inclusions`, `Image`) VALUES
(1, 1, 'Bali Adventure', 'Enjoy 5 days and 4 nights in Bali with hotel, breakfast, transport, and a private tour guide.', 499.00, 5, 20, 'Hotel, Breakfast, Guide, Transport', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400'),
(2, 2, 'Swiss Alps Escape', 'Snowboarding and panoramic scenic train rides through the Swiss Alps over 7 days.', 899.00, 7, 15, 'Resort, Ski Pass, Transit', 'https://images.unsplash.com/photo-1530122037265-a5f1f91d3b99?w=400'),
(3, 3, 'Thailand Explorer', 'Island hopping, night markets, and temple exploration across Bangkok and Phuket.', 399.00, 4, 25, 'Boutique Stay, Ferry, Guide', 'https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=400'),
(4, 4, 'Maldives Paradise', 'Overwater resort stay featuring private snorkeling excursions and sunset cruises.', 699.00, 6, 18, 'Overwater Bungalow, Meals, Boat', 'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?w=400');

-- 5. Bookings
INSERT INTO `BOOKING` (`ID`, `User_ID`, `Tour_Package_ID`, `Booking_Date`, `Travel_Date`, `Persons`, `Total_Price`, `Status`) VALUES
(1, 5, 1, '2026-05-01', '2026-05-12', 1, 499.00, 'Approved'),
(2, 5, 2, '2026-05-15', '2026-06-10', 1, 899.00, 'Pending'),
(3, 5, 3, '2026-05-20', '2026-06-22', 1, 399.00, 'Pending'),
(4, 6, 2, '2026-05-02', '2026-05-15', 2, 1798.00, 'Pending'),
(5, 7, 3, '2026-05-05', '2026-05-18', 1, 399.00, 'Approved'),
(6, 8, 4, '2026-05-10', '2026-05-20', 1, 699.00, 'Approved');

-- 6. Guide Assignments
INSERT INTO `GUIDE_ASSIGNMENT` (`ID`, `Booking_ID`, `Guide_ID`, `Assignment_Date`, `Status`) VALUES
(1, 1, 3, '2026-05-02', 'In Progress'),
(2, 5, 3, '2026-05-06', 'Scheduled'),
(3, 6, 4, '2026-05-11', 'Scheduled');

-- 7. Reviews
INSERT INTO `REVIEW` (`ID`, `Tour_Package_ID`, `User_ID`, `Rating`, `Comment`, `Review_Date`) VALUES
(1, 1, 5, 5, 'Exceptional experience! The guide was very helpful throughout Bali.', '2026-05-14');

-- 8. Guidelines
INSERT INTO `GUIDELINES` (`ID`, `Guide_ID`, `Note`, `Notice_Date`) VALUES
(1, 3, 'Bali Adventure - Meeting at 8:00 AM at Hotel Lobby', '2026-05-12'),
(2, 3, 'Switzerland Trip - Carry thermal jackets and warm boots.', '2026-06-10'),
(3, 3, 'Thailand Explorer - Bring original passports and visa docs.', '2026-06-22');