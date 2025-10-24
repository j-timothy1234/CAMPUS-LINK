-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 09:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campuslink`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(50) DEFAULT NULL,
  `agent_id` varchar(50) DEFAULT NULL,
  `service` varchar(20) DEFAULT NULL,
  `mode` varchar(20) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `pickup` text DEFAULT NULL,
  `destination` text DEFAULT NULL,
  `estimate` varchar(50) DEFAULT NULL,
  `pickup_lat` double DEFAULT NULL,
  `pickup_lng` double DEFAULT NULL,
  `dest_lat` double DEFAULT NULL,
  `dest_lng` double DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `client_id`, `agent_id`, `service`, `mode`, `datetime`, `pickup`, `destination`, `estimate`, `pickup_lat`, `pickup_lng`, `dest_lat`, `dest_lng`, `status`, `created_at`) VALUES
(1, 'CL_0001', '', 'book', 'bike', '0000-00-00 00:00:00', '', '', '', -0.5957865, 30.599128, -14.060241, 32.04272, 'pending', '2025-10-22 14:23:37'),
(2, 'CL_0001', '', 'book', 'bike', '0000-00-00 00:00:00', '', '', '', 0.321, 32.5713, 0.5751567, 33.2805299, 'pending', '2025-10-23 19:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Client_ID` varchar(10) DEFAULT NULL,
  `Username` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone_Number` varchar(15) DEFAULT NULL,
  `Gender` enum('MALE','FEMALE') NOT NULL,
  `Password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Client_ID` (`Client_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `Client_ID`, `Username`, `Email`, `Phone_Number`, `Gender`, `Password`) VALUES
(1, 'CL_0001', 'J Timothy', 'jobingetimothyosubert@gmail.com', '0752915250', 'MALE', '$2y$10$dZxe7OgMbdy5qjNhw7nopequ8XecBd1iwB.SP7tvJISnPmvQi9anC'),
(2, 'CL_0002', 'Ninsiima Caroline', 'ninsiimacarol732@gmail.com', '0766191676', 'FEMALE', '$2y$10$eGiGZgrNSrc.pJScPhp4Zu1nMckzZ5tVW94mxKsdqxCMFWlByNXJa'),
(3, 'CL_0003', 'ALIRWA PHIONA', 'phionaalirwa67@gmail.com', '0756694769', 'FEMALE', '$2y$10$rwy/47FlJohqBsmOHeDxu.dw4CK.04YaHm2Yg19jvahPwy/k1p9iy'),
(4, 'CL_0004', 'BALUKU', 'alinethuanoldbaluku@gmail.com', '0791248600', 'MALE', '$2y$10$bnE8H3fBufby/PPQfNz38uPKCG5OSYWq.U6hzym3NpTfebSeMMYYe'),
(5, 'CL_0005', 'BALUKU', 'alinethuanoldbaluku12@gmail.com', '0791248601', 'MALE', '$2y$10$WR7Wi5KwKGkMhm.9WD28uOS6kffjrpw7nz8MtKYgOLly9MUDdSV1G');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Driver_ID` varchar(8) NOT NULL,
  `Username` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone_Number` bigint(20) NOT NULL,
  `Gender` enum('MALE','FEMALE') NOT NULL,
  `Profile_Photo` varchar(255) NOT NULL,
  `Car_Plate_Number` varchar(20) NOT NULL,
  `Residence` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `Driver_ID` (`Driver_ID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone_Number` (`Phone_Number`),
  UNIQUE KEY `Car_Plate_Number` (`Car_Plate_Number`),
  UNIQUE KEY `unique_driver_username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `Driver_ID`, `Username`, `Email`, `Phone_Number`, `Gender`, `Profile_Photo`, `Car_Plate_Number`, `Residence`, `Password`, `Created_At`) VALUES
(1, 'D000001', 'J Timothy', 'jobingetimothyosubert@gmail.com', 752915250, 'MALE', '../uploads_driver/driver_68f58e5b56b0b_pol.jpg', 'UBB 783L', 'KATETE', '$2y$10$wWX1hXLHIbE7zMs.A1CvpecXTYRwRT8eFkZKNK1mhg/KY1DW.wwNe', '2025-10-20 01:20:29'),
(2, 'D000002', 'Timothy Osubert', 'timothyosubertjobinge@gmail.com', 754829281, 'MALE', '../uploads_driver/driver_68f63b9605a8a_pol.jpg', 'UBQ 687K', 'KATETE', '$2y$10$.oHydDB1WqfQNh83BI7qbuMcroJaXTEqjmmmXEtrr6yL13nvDlRnC', '2025-10-20 13:39:34'),
(3, 'D000003', 'shawn manson', 'marzavendator@gmail.com', 709047981, 'MALE', '../uploads_driver/driver_68f6427c17747_WhatsApp Image 2025-02-15 at 14.07.33_1ea8b129.jpg', 'UBB 343Y', 'ruharo', '$2y$10$dIPuSYz0TNUgwz5/8UMlxObc3pjpdwPGwH5mExZFAlAlu0FOJKWHe', '2025-10-20 14:09:00'),
(4, 'D000004', 'Daniel', 'bwararedaniel@gmail.com', 783386003, 'MALE', '../uploads_driver/driver_68f76c238b036_many-black-african-hands-reaching-260nw-2582996033.jpg', 'UDL 481Q', 'Kakiika', '$2y$10$QKjoC8WFYUvBzePTYkrG3OLzNWumw0A/YK9jdaSCif6ORbQzklxw.', '2025-10-21 11:19:02'),
(5, 'D000005', 'JOBINGE JUDE', 'jobingejude@gmail.com', 753044472, 'MALE', '../uploads_driver/driver_68fa7c5b331e6_earth.jpg', 'UAC 231D', 'IGANGA', '$2y$10$E4mCWaCUS6UNEuz91X6Sbuyww4NZOtXtRNcuKSeLd1sFisf7ksChy', '2025-10-23 19:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `agent_id` varchar(50) DEFAULT NULL,
  `client_id` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `booking_id`, `agent_id`, `client_id`, `status`, `created_at`) VALUES
(1, 2, 'broadcast:rider', 'CL_0001', 'pending', '2025-10-23 19:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_table` varchar(50) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_table`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 'drivers', 'D000001', 'be6886896e4fe72346a9e28af48345a22ca92c876d49843f', '2025-10-22 16:26:38', '2025-10-22 13:26:38');

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

DROP TABLE IF EXISTS `riders`;
CREATE TABLE IF NOT EXISTS `riders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Rider_ID` varchar(8) NOT NULL,
  `Username` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone_Number` bigint(20) NOT NULL,
  `Gender` enum('Male','Female') NOT NULL,
  `Profile_Photo` varchar(255) NOT NULL,
  `Residence` varchar(50) NOT NULL,
  `Motorcycle_Plate_Number` varchar(20) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `Rider_ID` (`Rider_ID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone_Number` (`Phone_Number`),
  UNIQUE KEY `Motorcycle_Plate_Number` (`Motorcycle_Plate_Number`),
  UNIQUE KEY `unique_rider_username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riders`
--

INSERT INTO `riders` (`id`, `Rider_ID`, `Username`, `Email`, `Phone_Number`, `Gender`, `Profile_Photo`, `Residence`, `Motorcycle_Plate_Number`, `Password`, `Created_At`) VALUES
(1, 'R000001', 'PRINCE SAM', 'princesam@gmail.com', 784010420, 'Male', '../upload_rider/PRINCE_SAM_profile.jpg', 'KATETE', 'UBB 783L', '$2y$10$HUUswwASnxhYycgC78puMeRNZ0v1wRnw8PmxYZHiUTCF9s.QMDBPO', '2025-10-20 12:50:08'),
(2, 'R000002', 'J Timothy Osuberrt', 'osubert@gmail.com', 753242298, 'Male', '../upload_rider/J_Timothy_Osuberrt_profile.jpg', 'JINJA', 'UFT 738M', '$2y$10$DSst65iBG/spLy2VFgZD1OG1EYae.FQ3Ty8rI9furtBglr2K601MS', '2025-10-20 14:05:38'),
(3, 'R000003', 'J Timothy Osuberrt_1', 'osubert3@gmail.com', 768936273, 'Male', '../upload_rider/J_Timothy_Osuberrt_profile.png', 'WANTONI', 'UPD 347K', '$2y$10$EW6OBXqH18VqAyxdCVY9UuH9ndzJz/vDIaS4wTBRHy8Ep9CUhI9WK', '2025-10-20 15:01:57'),
(4, 'R000004', 'FELIX BRO', 'felixbro@gmail.com', 740194512, 'Male', '../upload_rider/FELIX_BRO_profile.jpg', 'BANDA', 'UBE 822K', '$2y$10$ke//jfTV5ukaJPWg7rajQersNm1LeNoEBHMubXntWoxmG61.5hh2O', '2025-10-20 18:39:05'),
(5, 'R000005', 'Armstrong', 'mansonshawnariho@gmail.com', 771249589, 'Male', '../upload_rider/Armstrong_profile.jpg', 'Ruharo', 'UAA 363H', '$2y$10$jcqoIZL/ZBiKzCyUqwqqG.kgRB136NSpAo./aOeEA74DNwkeo4Hcu', '2025-10-21 11:40:03'),
(6, 'R000006', 'Kose Joy', 'kosejoy626@gmail.com', 763243453, 'Female', '../upload_rider/Kose_Joy_profile.jpeg', 'Nyamitanga', 'UBG 363G', '$2y$10$VCtLYWDRh8qpcU7.ah.fxuUxnHfCOg6SGzWXxWyASZKKu9CLeVj6q', '2025-10-22 13:11:54'),
(7, 'R000007', 'Pascals', 'pascals@gmail.com', 752915250, 'Male', '../upload_rider/Pascals_profile.jpeg', 'Katete', 'UGH 637H', '$2y$10$/TUp08IRrWVSH4SeeVNyb.CUxaEFN.nZtsZaJauIFonQvx2Weaov6', '2025-10-22 13:39:35'),
(8, 'R000008', 'Phiona', 'alirwaphiona@gmail.com', 752915500, 'Female', '../upload_rider/Phiona_profile.jpg', 'NYAMITANGA', 'UBE 382L', '$2y$10$H9RYINHpN/IKLcWH2pvX5e/JDi/Q4nnZT7SwQnr1p43bPMNtma/PG', '2025-10-22 14:00:05');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
