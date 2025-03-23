-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 01:09 PM
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
-- Database: `gymbuddy`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `trainer_id`, `date`, `time`, `status`, `payment_status`) VALUES
(44, 14, 14, '2025-03-20', '16:00:00', 'confirmed', 'paid'),
(45, 14, 15, '2025-03-10', '09:00:00', 'pending', 'unpaid'),
(46, 14, 14, '2025-03-27', '13:00:00', 'confirmed', 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `name`, `description`, `contact`, `image_url`, `specialization`) VALUES
(14, 'Paulo Lustestica', NULL, '09785563421', '67cb1820933cc.jpg', 'Lifting'),
(15, 'Exequiel Dela Cruz', NULL, '09785534212', '67b7ec960d7a0.jpg', 'Body Building'),
(16, 'Kenett Villalon', NULL, '09052718705', '67c3ecd9d9002.jpg', 'Cardio');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_availability`
--

CREATE TABLE `trainer_availability` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainer_availability`
--

INSERT INTO `trainer_availability` (`id`, `trainer_id`, `day_of_week`, `start_time`, `end_time`, `is_available`) VALUES
(50, 14, 4, '09:00:00', '17:00:00', 1),
(51, 15, 1, '09:00:00', '17:00:00', 1),
(52, 15, 3, '09:00:00', '17:00:00', 1),
(53, 15, 4, '09:00:00', '17:00:00', 1),
(54, 15, 5, '09:00:00', '19:00:00', 1),
(55, 16, 1, '09:00:00', '22:00:00', 1),
(56, 16, 2, '09:00:00', '19:00:00', 1),
(57, 16, 3, '09:00:00', '23:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','trainer') DEFAULT 'user',
  `trainer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `trainer_id`) VALUES
(9, 'admin', 'admin@gmail.com', '$2y$10$aDsqCq2FiMCZHq8HSOmkiOaTysAVkL8.PVOYNARd.a47S9c3gS3hu', 'admin', NULL),
(10, 'Paulo Lustestica', 'PauloLustestica@gmail.com', '$2y$10$BJSkWu10tC71IcgwuR5TF.fQO8xdHygqotKAZxommgXaKvm9wSrFW', 'trainer', 14),
(11, 'user', 'user@gmail.com', '$2y$10$pH.PmvaF2mZ6I89hVtJE8.227pV.3/1W.gd5BWpZ8uCSvoQXAVOK6', 'user', NULL),
(12, 'Exequiel Dela Cruz', 'ExequielDelaCruz@gmail.com', '$2y$10$RR4MOyifaKyueeYy6xRx3.tjqwsfFN7TgFlnoV1Jxtl9nZW.S270y', 'trainer', 15),
(13, 'Kenett Villalon', 'KenettVillalon@gmail.com', '$2y$10$TEFkU5SyATG12x7WMe8RZuSOpraKk5OQrQ12UvmWc8S1qahyqRrIu', 'trainer', 16),
(14, 'lance', 'lance@gmail.com', '$2y$10$TQ60H7aoP5Zeq7mQp9cP6.VN3TwfcNdY52Fv60b16rpttmfK1fFvG', 'user', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainer_availability`
--
ALTER TABLE `trainer_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_availability` (`trainer_id`,`day_of_week`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_trainer` (`trainer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `trainer_availability`
--
ALTER TABLE `trainer_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`);

--
-- Constraints for table `trainer_availability`
--
ALTER TABLE `trainer_availability`
  ADD CONSTRAINT `trainer_availability_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
