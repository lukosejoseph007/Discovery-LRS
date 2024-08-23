-- Open phpMyAdmin: Log in to your phpMyAdmin interface.
-- Select the Database: Choose the database where you want to create the new table.
-- Click on the "SQL" tab at the top of the page.
-- Copy and paste the SQL script below into the SQL query box.
-- Click on the "Go" button to execute the SQL commands and create the new table.



-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2024 at 02:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `lrs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `new_statements`
--

CREATE TABLE `statements` (
  `id` int(11) NOT NULL,
  `statement_id` varchar(255) NOT NULL,
  `actor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actor`)),
  `verb` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`verb`)),
  `object` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`object`)),
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result`)),
  `timestamp` datetime NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `context` text DEFAULT NULL,
  `courseName` varchar(255) DEFAULT NULL,
  `courseId` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `new_statements`
--
ALTER TABLE `statements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `statement_id_UNIQUE` (`statement_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `new_statements`
--
