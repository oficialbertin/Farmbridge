-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 10:11 AM
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
-- Database: `farmbridge`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `province_id`, `district_id`, `sector_id`, `details`) VALUES
(1, 15, 90, 1234, ''),
(2, 11, 62, 855, ''),
(3, 11, 62, 855, ''),
(4, 15, 90, 1248, ''),
(5, 15, 90, 1234, ''),
(6, 15, 90, 1234, ''),
(7, 11, 62, 856, '');

-- --------------------------------------------------------

--
-- Table structure for table `ai_model_performance`
--

CREATE TABLE `ai_model_performance` (
  `id` int(11) NOT NULL,
  `model_name` varchar(100) DEFAULT NULL,
  `accuracy` float DEFAULT NULL,
  `training_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_points` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_model_performance`
--

INSERT INTO `ai_model_performance` (`id`, `model_name`, `accuracy`, `training_date`, `data_points`) VALUES
(1, 'intent_classifier', 0.5, '2025-07-30 12:47:41', 10);

-- --------------------------------------------------------

--
-- Table structure for table `ai_training_data`
--

CREATE TABLE `ai_training_data` (
  `id` int(11) NOT NULL,
  `input_text` text NOT NULL,
  `expected_output` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `confidence_score` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

CREATE TABLE `chatbot_conversations` (
  `id` int(11) NOT NULL,
  `user_message` text NOT NULL,
  `ai_response` text NOT NULL,
  `intent_category` varchar(50) DEFAULT NULL,
  `user_satisfaction` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crops`
--

CREATE TABLE `crops` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','sold','pending') DEFAULT 'available',
  `harvest_type` enum('in_stock','future') DEFAULT 'in_stock',
  `estimated_harvest_date` date DEFAULT NULL,
  `listed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crops`
--

INSERT INTO `crops` (`id`, `farmer_id`, `name`, `description`, `quantity`, `unit`, `price`, `image`, `status`, `harvest_type`, `estimated_harvest_date`, `listed_at`) VALUES
(1, 3, 'tomatoes', 'well maintained tomatoes', 62, 'kg', 500.00, 'uploads/68874c5582ade_tomatoes.jpg', 'available', 'in_stock', NULL, '2025-07-28 10:09:25'),
(3, 3, 'apples', 'good apples', 55, 'kg', 5000.00, 'uploads/688b814979f66_apples.jpg', 'available', 'in_stock', NULL, '2025-07-31 14:44:25'),
(4, 3, 'irish potato', 'good potatoes', 77, 'kg', 390.00, 'uploads/689c9145e0da2_onoins.jpg', 'available', 'in_stock', NULL, '2025-08-13 13:20:03'),
(5, 3, 'pineapple', 'good product', 50, 'kg', 400.00, NULL, 'available', 'in_stock', NULL, '2025-09-11 09:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `crop_sales`
--

CREATE TABLE `crop_sales` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop_sales`
--

INSERT INTO `crop_sales` (`id`, `crop_id`, `farmer_id`, `buyer_id`, `quantity`, `location`, `sale_date`, `price`, `created_at`) VALUES
(1, 1, 3, 5, 10, NULL, '2025-07-28', 5000.00, '2025-07-28 15:01:56'),
(2, 1, 3, 5, 10, NULL, '2025-07-29', 5000.00, '2025-07-29 08:15:26');

-- --------------------------------------------------------

--
-- Table structure for table `demand_forecast`
--

CREATE TABLE `demand_forecast` (
  `id` int(11) NOT NULL,
  `crop_name` varchar(100) DEFAULT NULL,
  `forecast_value` int(11) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `date_generated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `raised_by` int(11) NOT NULL,
  `raised_by_role` enum('buyer','farmer') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('open','under_review','resolved','closed') DEFAULT 'open',
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disputes`
--

INSERT INTO `disputes` (`id`, `order_id`, `raised_by`, `raised_by_role`, `reason`, `status`, `resolution`, `resolved_by`, `resolved_at`, `created_at`) VALUES
(1, 5, 5, 'buyer', 'my order is still pending i don\'t know the problem', 'open', NULL, NULL, NULL, '2025-09-12 07:38:14'),
(2, 4, 5, 'buyer', 'Type: Wrong quality | Details: least quality', 'resolved', 'thank you for you dispute collect those product and we will give you good ones as its replacement', 1, '2025-09-12 07:51:39', '2025-09-12 07:45:40');

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_rw` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `province_id`, `name`, `name_en`, `name_rw`) VALUES
(61, 11, 'Nyarugenge', 'Nyarugenge', 'Nyarugenge'),
(62, 11, 'Gasabo', 'Gasabo', 'Gasabo'),
(63, 11, 'Kicukiro', 'Kicukiro', 'Kicukiro'),
(64, 12, 'Nyanza', 'Nyanza', 'Nyanza'),
(65, 12, 'Gisagara', 'Gisagara', 'Gisagara'),
(66, 12, 'Nyaruguru', 'Nyaruguru', 'Nyaruguru'),
(67, 12, 'Huye', 'Huye', 'Huye'),
(68, 12, 'Nyamagabe', 'Nyamagabe', 'Nyamagabe'),
(69, 12, 'Ruhango', 'Ruhango', 'Ruhango'),
(70, 12, 'Muhanga', 'Muhanga', 'Muhanga'),
(71, 12, 'Kamonyi', 'Kamonyi', 'Kamonyi'),
(72, 13, 'Karongi', 'Karongi', 'Karongi'),
(73, 13, 'Rutsiro', 'Rutsiro', 'Rutsiro'),
(74, 13, 'Rubavu', 'Rubavu', 'Rubavu'),
(75, 13, 'Nyabihu', 'Nyabihu', 'Nyabihu'),
(76, 13, 'Ngororero', 'Ngororero', 'Ngororero'),
(77, 13, 'Rusizi', 'Rusizi', 'Rusizi'),
(78, 13, 'Nyamasheke', 'Nyamasheke', 'Nyamasheke'),
(79, 14, 'Rulindo', 'Rulindo', 'Rulindo'),
(80, 14, 'Gakenke', 'Gakenke', 'Gakenke'),
(81, 14, 'Musanze', 'Musanze', 'Musanze'),
(82, 14, 'Burera', 'Burera', 'Burera'),
(83, 14, 'Gicumbi', 'Gicumbi', 'Gicumbi'),
(84, 15, 'Rwamagana', 'Rwamagana', 'Rwamagana'),
(85, 15, 'Nyagatare', 'Nyagatare', 'Nyagatare'),
(86, 15, 'Gatsibo', 'Gatsibo', 'Gatsibo'),
(87, 15, 'Kayonza', 'Kayonza', 'Kayonza'),
(88, 15, 'Kirehe', 'Kirehe', 'Kirehe'),
(89, 15, 'Ngoma', 'Ngoma', 'Ngoma'),
(90, 15, 'Bugesera', 'Bugesera', 'Bugesera');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `token` varchar(32) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `email`, `token`, `expires_at`, `verified_at`, `created_at`) VALUES
(3, 'bertinnniga@gmail.com', '7baab0c3178df0a97aa3bece00ec5e38', '2025-10-12 13:34:31', NULL, '2025-10-11 13:34:31'),
(7, 'bertinniga@gmail.com', '7824061dec7b429c37b2c29422e421ed', '2025-10-12 13:48:27', NULL, '2025-10-11 13:48:27'),
(8, 'bertinniga@gmail.com', '5a04214cd26a5b2d80139fdfb32a3074', '2025-10-12 13:55:45', NULL, '2025-10-11 13:55:45');

-- --------------------------------------------------------

--
-- Table structure for table `farming_tips`
--

CREATE TABLE `farming_tips` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farming_tips`
--

INSERT INTO `farming_tips` (`id`, `title`, `content`, `category`, `created_at`, `updated_at`) VALUES
(1, 'Soil Preparation', 'Prepare soil with organic matter; test pH for optimal growth.', 'SOIL_MANAGEMENT', '2025-10-14 08:00:27', '2025-10-14 08:00:27');

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

CREATE TABLE `market_prices` (
  `id` int(11) NOT NULL,
  `commodity` varchar(100) DEFAULT NULL,
  `market` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_prices`
--

INSERT INTO `market_prices` (`id`, `commodity`, `market`, `price`, `date`, `source`) VALUES
(1, 'maize', 'Kigali', 450.00, '2025-08-10', 'Local Market'),
(2, 'maize', 'Musanze', 420.00, '2025-08-09', 'Local Market'),
(3, 'maize', 'Huye', 480.00, '2025-08-08', 'Local Market'),
(4, 'tomato', 'Kigali', 1200.00, '2025-08-10', 'Local Market'),
(5, 'tomato', 'Musanze', 1100.00, '2025-08-09', 'Local Market'),
(6, 'potato', 'Kigali', 800.00, '2025-08-10', 'Local Market'),
(7, 'potato', 'Musanze', 750.00, '2025-08-09', 'Local Market'),
(8, 'banana', 'Kigali', 600.00, '2025-08-10', 'Local Market'),
(9, 'banana', 'Huye', 550.00, '2025-08-09', 'Local Market'),
(10, 'rice', 'Kigali', 1800.00, '2025-08-10', 'Local Market'),
(11, 'rice', 'Nyagatare', 1700.00, '2025-08-09', 'Local Market'),
(12, 'bean', 'Kigali', 2200.00, '2025-08-10', 'Local Market'),
(13, 'bean', 'Musanze', 2100.00, '2025-08-09', 'Local Market'),
(14, 'cassava', 'Kigali', 300.00, '2025-08-10', 'Local Market'),
(15, 'cassava', 'Huye', 280.00, '2025-08-09', 'Local Market'),
(16, 'onion', 'Kigali', 950.00, '2025-08-13', 'WFP'),
(17, 'onion', 'Musanze', 900.00, '2025-08-13', 'WFP'),
(18, 'onion', 'Huye', 980.00, '2025-08-13', 'WFP'),
(19, 'carrot', 'Kigali', 750.00, '2025-08-13', 'Local Market'),
(20, 'cabbage', 'Kigali', 600.00, '2025-08-13', 'Local Market'),
(21, 'spinach', 'Kigali', 400.00, '2025-08-13', 'Local Market'),
(22, 'lettuce', 'Kigali', 500.00, '2025-08-13', 'Local Market'),
(23, 'eggplant', 'Kigali', 650.00, '2025-08-13', 'Local Market'),
(24, 'cucumber', 'Kigali', 550.00, '2025-08-13', 'Local Market'),
(25, 'bell pepper', 'Kigali', 850.00, '2025-08-13', 'Local Market'),
(26, 'green beans', 'Kigali', 700.00, '2025-08-13', 'Local Market'),
(27, 'cauliflower', 'Kigali', 900.00, '2025-08-13', 'Local Market'),
(28, 'broccoli', 'Kigali', 1100.00, '2025-08-13', 'Local Market'),
(29, 'garlic', 'Kigali', 1500.00, '2025-08-13', 'Local Market'),
(30, 'ginger', 'Kigali', 1800.00, '2025-08-13', 'Local Market'),
(31, 'apple', 'Kigali', 1200.00, '2025-08-13', 'Local Market'),
(32, 'orange', 'Kigali', 800.00, '2025-08-13', 'Local Market'),
(33, 'mango', 'Kigali', 700.00, '2025-08-13', 'Local Market'),
(34, 'pineapple', 'Kigali', 900.00, '2025-08-13', 'Local Market'),
(35, 'avocado', 'Kigali', 500.00, '2025-08-13', 'Local Market'),
(36, 'papaya', 'Kigali', 400.00, '2025-08-13', 'Local Market'),
(37, 'watermelon', 'Kigali', 300.00, '2025-08-13', 'Local Market'),
(38, 'passion fruit', 'Kigali', 1000.00, '2025-08-13', 'Local Market'),
(39, 'guava', 'Kigali', 450.00, '2025-08-13', 'Local Market'),
(40, 'lemon', 'Kigali', 350.00, '2025-08-13', 'Local Market'),
(41, 'lime', 'Kigali', 400.00, '2025-08-13', 'Local Market'),
(42, 'pea', 'Kigali', 1800.00, '2025-08-13', 'Local Market'),
(43, 'lentil', 'Kigali', 2500.00, '2025-08-13', 'Local Market'),
(44, 'chickpea', 'Kigali', 2000.00, '2025-08-13', 'Local Market'),
(45, 'soybean', 'Kigali', 1600.00, '2025-08-13', 'Local Market'),
(46, 'sweet potato', 'Kigali', 400.00, '2025-08-13', 'Local Market'),
(47, 'yam', 'Kigali', 500.00, '2025-08-13', 'Local Market'),
(48, 'taro', 'Kigali', 450.00, '2025-08-13', 'Local Market'),
(49, 'coffee', 'Kigali', 3500.00, '2025-08-13', 'FAOSTAT'),
(50, 'tea', 'Kigali', 2800.00, '2025-08-13', 'FAOSTAT'),
(51, 'sugarcane', 'Kigali', 200.00, '2025-08-13', 'Local Market'),
(52, 'tobacco', 'Kigali', 4000.00, '2025-08-13', 'FAOSTAT'),
(53, 'wheat', 'Kigali', 2200.00, '2025-08-13', 'FAOSTAT'),
(54, 'sorghum', 'Kigali', 380.00, '2025-08-13', 'Local Market'),
(55, 'maize', 'Kigali', 450.00, '2025-10-07', 'Local Market'),
(56, 'maize', 'Musanze', 420.00, '2025-10-07', 'Local Market'),
(57, 'tomato', 'Kigali', 1200.00, '2025-10-07', 'Local Market'),
(58, 'tomato', 'Musanze', 1100.00, '2025-10-07', 'Local Market'),
(59, 'potato', 'Kigali', 800.00, '2025-10-07', 'Local Market'),
(60, 'potato', 'Musanze', 750.00, '2025-10-07', 'Local Market'),
(61, 'banana', 'Kigali', 600.00, '2025-10-07', 'Local Market'),
(62, 'rice', 'Kigali', 1800.00, '2025-10-07', 'Local Market'),
(63, 'bean', 'Kigali', 2200.00, '2025-10-07', 'Local Market'),
(64, 'cassava', 'Huye', 300.00, '2025-10-07', 'Local Market');

-- --------------------------------------------------------

--
-- Stand-in structure for view `market_prices_ussd_view`
-- (See below for the actual view)
--
CREATE TABLE `market_prices_ussd_view` (
`id` int(11)
,`crop_name` varchar(100)
,`price` decimal(10,2)
,`location` varchar(100)
,`unit` varchar(2)
,`updated_at` date
);

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_metrics`
--

CREATE TABLE `monitoring_metrics` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `active_sessions` int(11) DEFAULT 0,
  `expired_sessions` int(11) DEFAULT 0,
  `total_users` int(11) DEFAULT 0,
  `farmers` int(11) DEFAULT 0,
  `buyers` int(11) DEFAULT 0,
  `new_users_24h` int(11) DEFAULT 0,
  `total_products` int(11) DEFAULT 0,
  `total_quantity` decimal(15,2) DEFAULT 0.00,
  `available_products` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `completed_orders` int(11) DEFAULT 0,
  `orders_24h` int(11) DEFAULT 0,
  `total_price_updates` int(11) DEFAULT 0,
  `crops_tracked` int(11) DEFAULT 0,
  `updates_24h` int(11) DEFAULT 0,
  `total_tips` int(11) DEFAULT 0,
  `categories` int(11) DEFAULT 0,
  `tips_24h` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `delivery_option` enum('buyer','farmer') DEFAULT 'buyer',
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `delivery_status` enum('pending','farmer_confirmed','out_for_delivery','delivered','completed') DEFAULT 'pending',
  `escrow_status` enum('pending','released','disputed') DEFAULT 'pending',
  `harvest_status` enum('not_harvested','harvesting','harvested') DEFAULT 'not_harvested',
  `estimated_delivery_date` date DEFAULT NULL,
  `confirmation_buyer` tinyint(1) DEFAULT 0,
  `confirmation_farmer` tinyint(1) DEFAULT 0,
  `dispute_flag` tinyint(1) DEFAULT 0,
  `buyer_notes` text DEFAULT NULL,
  `farmer_notes` text DEFAULT NULL,
  `status` enum('pending','paid','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `crop_id`, `quantity`, `total`, `delivery_option`, `delivery_fee`, `delivery_status`, `escrow_status`, `harvest_status`, `estimated_delivery_date`, `confirmation_buyer`, `confirmation_farmer`, `dispute_flag`, `buyer_notes`, `farmer_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 10, 5000.00, 'buyer', 0.00, 'pending', 'pending', 'not_harvested', NULL, 0, 0, 0, NULL, NULL, 'pending', '2025-07-28 15:00:16', '2025-07-31 14:17:11'),
(3, 5, 1, 10, 5000.00, 'buyer', 0.00, 'pending', 'pending', 'not_harvested', NULL, 0, 0, 0, NULL, NULL, 'pending', '2025-07-29 08:15:26', '2025-07-31 14:17:11'),
(4, 5, 3, 21, 105000.00, 'buyer', 0.00, 'pending', 'released', 'harvested', '2025-08-07', 0, 0, 1, '', NULL, 'pending', '2025-08-04 12:12:00', '2025-09-12 07:51:39'),
(5, 5, 4, 1, 390.00, 'buyer', 0.00, 'pending', 'disputed', 'harvested', '2025-08-16', 0, 0, 1, '', NULL, 'pending', '2025-08-13 13:48:01', '2025-09-12 07:38:14'),
(6, 5, 1, 1, 4840.00, 'buyer', 4300.00, 'pending', 'pending', 'harvested', '2025-09-21', 0, 0, 0, '', NULL, 'pending', '2025-09-18 12:50:47', '2025-09-18 12:50:47'),
(7, 5, 1, 1, 4840.00, 'buyer', 4300.00, 'pending', 'pending', 'harvested', '2025-10-10', 0, 0, 0, '', NULL, 'pending', '2025-10-07 10:43:17', '2025-10-07 10:43:17'),
(8, 5, 1, 2, 5680.00, 'buyer', 4600.00, 'pending', 'pending', 'harvested', '2025-10-10', 0, 0, 0, '', NULL, 'pending', '2025-10-07 10:48:44', '2025-10-07 10:48:44'),
(9, 5, 1, 1, 4840.00, 'buyer', 4300.00, 'pending', 'pending', 'harvested', '2025-10-10', 0, 0, 0, '', NULL, 'pending', '2025-10-07 11:08:34', '2025-10-07 11:08:34'),
(10, 5, 1, 1, 540.00, 'buyer', 0.00, 'pending', 'pending', 'harvested', '2025-10-10', 0, 0, 0, '', NULL, 'pending', '2025-10-07 12:14:32', '2025-10-07 12:14:32'),
(11, 5, 1, 1, 540.00, 'buyer', 0.00, 'pending', 'pending', 'harvested', '2025-10-12', 0, 0, 0, '', NULL, 'pending', '2025-10-09 11:18:34', '2025-10-09 11:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `changed_by`, `changed_at`) VALUES
(1, 4, 'pending', 'Order created', 5, '2025-08-04 12:12:00'),
(4, 5, 'pending', 'Order created', 5, '2025-08-13 13:48:01'),
(5, 5, 'dispute_raised', 'Dispute raised by buyer: my order is still pending i don\'t know the problem', 5, '2025-09-12 07:38:14'),
(6, 4, 'dispute_raised', 'Dispute raised by buyer: Type: Wrong quality | Details: least quality', 5, '2025-09-12 07:45:40'),
(7, 4, 'dispute_resolved', 'Dispute resolved; escrow released to farmer', 1, '2025-09-12 07:51:39'),
(8, 6, 'pending', 'Order created', 5, '2025-09-18 12:50:47'),
(9, 7, 'pending', 'Order created', 5, '2025-10-07 10:43:17'),
(10, 8, 'pending', 'Order created', 5, '2025-10-07 10:48:44'),
(11, 9, 'pending', 'Order created', 5, '2025-10-07 11:08:34'),
(12, 10, 'pending', 'Order created', 5, '2025-10-07 12:14:32'),
(13, 11, 'pending', 'Order created', 5, '2025-10-09 11:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `momo_ref` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('escrow','release','refund') DEFAULT 'escrow',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `momo_ref`, `amount`, `payment_type`, `status`, `paid_at`, `released_at`, `released_by`) VALUES
(1, 4, 'MOMO_1754309520_4', 105000.00, 'escrow', 'pending', NULL, NULL, NULL),
(2, 5, 'MOMO_1755092881_5', 390.00, 'escrow', 'pending', NULL, NULL, NULL),
(3, 6, 'MOMO_1758199847_6', 4840.00, 'escrow', 'pending', NULL, NULL, NULL),
(4, 7, 'MOMO_1759833797_7', 4840.00, 'escrow', 'pending', NULL, NULL, NULL),
(5, 8, 'MOMO_1759834124_8', 5680.00, 'escrow', 'pending', NULL, NULL, NULL),
(6, 9, 'MOMO_1759835314_9', 4840.00, 'escrow', 'pending', NULL, NULL, NULL),
(7, 10, 'MOMO_1759839272_10', 540.00, 'escrow', 'pending', NULL, NULL, NULL),
(8, 11, 'MOMO_1760008714_11', 540.00, 'escrow', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `price_alerts`
--

CREATE TABLE `price_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `target_price` decimal(10,2) NOT NULL,
  `condition` enum('above','below') DEFAULT 'above',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_rw` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`id`, `name`, `name_en`, `name_rw`) VALUES
(11, 'Kigali City', 'Kigali City', 'Umujyi wa Kigali'),
(12, 'Southern Province', 'Southern Province', 'Amajyepfo'),
(13, 'Western Province', 'Western Province', 'Iburengerazuba'),
(14, 'Northern Province', 'Northern Province', 'Amajyaruguru'),
(15, 'Eastern Province', 'Eastern Province', 'Iburasirazuba');

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_rw` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `district_id`, `name`, `name_en`, `name_rw`) VALUES
(833, 61, 'Gitega', 'Gitega', 'Gitega'),
(834, 61, 'Kanyinya', 'Kanyinya', 'Kanyinya'),
(835, 61, 'Kigali', 'Kigali', 'Kigali'),
(836, 61, 'Kimisagara', 'Kimisagara', 'Kimisagara'),
(837, 61, 'Mageragere', 'Mageragere', 'Mageragere'),
(838, 61, 'Muhima', 'Muhima', 'Muhima'),
(839, 61, 'Nyakabanda', 'Nyakabanda', 'Nyakabanda'),
(840, 61, 'Nyamirambo', 'Nyamirambo', 'Nyamirambo'),
(841, 61, 'Nyarugenge', 'Nyarugenge', 'Nyarugenge'),
(842, 61, 'Rwezamenyo', 'Rwezamenyo', 'Rwezamenyo'),
(843, 62, 'Bumbogo', 'Bumbogo', 'Bumbogo'),
(844, 62, 'Gatsata', 'Gatsata', 'Gatsata'),
(845, 62, 'Gikomero', 'Gikomero', 'Gikomero'),
(846, 62, 'Gisozi', 'Gisozi', 'Gisozi'),
(847, 62, 'Jabana', 'Jabana', 'Jabana'),
(848, 62, 'Jali', 'Jali', 'Jali'),
(849, 62, 'Kacyiru', 'Kacyiru', 'Kacyiru'),
(850, 62, 'Kimihurura', 'Kimihurura', 'Kimihurura'),
(851, 62, 'Kimironko', 'Kimironko', 'Kimironko'),
(852, 62, 'Kinyinya', 'Kinyinya', 'Kinyinya'),
(853, 62, 'Ndera', 'Ndera', 'Ndera'),
(854, 62, 'Nduba', 'Nduba', 'Nduba'),
(855, 62, 'Remera', 'Remera', 'Remera'),
(856, 62, 'Rusororo', 'Rusororo', 'Rusororo'),
(857, 62, 'Rutunga', 'Rutunga', 'Rutunga'),
(858, 63, 'Gahanga', 'Gahanga', 'Gahanga'),
(859, 63, 'Gatenga', 'Gatenga', 'Gatenga'),
(860, 63, 'Gikondo', 'Gikondo', 'Gikondo'),
(861, 63, 'Kagarama', 'Kagarama', 'Kagarama'),
(862, 63, 'Kanombe', 'Kanombe', 'Kanombe'),
(863, 63, 'Kicukiro', 'Kicukiro', 'Kicukiro'),
(864, 63, 'Kigarama', 'Kigarama', 'Kigarama'),
(865, 63, 'Masaka', 'Masaka', 'Masaka'),
(866, 63, 'Niboye', 'Niboye', 'Niboye'),
(867, 63, 'Nyarugunga', 'Nyarugunga', 'Nyarugunga'),
(868, 64, 'Busasamana', 'Busasamana', 'Busasamana'),
(869, 64, 'Busoro', 'Busoro', 'Busoro'),
(870, 64, 'Cyabakamyi', 'Cyabakamyi', 'Cyabakamyi'),
(871, 64, 'Kibilizi', 'Kibilizi', 'Kibilizi'),
(872, 64, 'Kigoma', 'Kigoma', 'Kigoma'),
(873, 64, 'Mukingo', 'Mukingo', 'Mukingo'),
(874, 64, 'Muyira', 'Muyira', 'Muyira'),
(875, 64, 'Ntyazo', 'Ntyazo', 'Ntyazo'),
(876, 64, 'Nyagisozi', 'Nyagisozi', 'Nyagisozi'),
(877, 64, 'Rwabicuma', 'Rwabicuma', 'Rwabicuma'),
(878, 65, 'Gikonko', 'Gikonko', 'Gikonko'),
(879, 65, 'Gishubi', 'Gishubi', 'Gishubi'),
(880, 65, 'Kansi', 'Kansi', 'Kansi'),
(881, 65, 'Kibirizi', 'Kibirizi', 'Kibirizi'),
(882, 65, 'Kigembe', 'Kigembe', 'Kigembe'),
(883, 65, 'Mamba', 'Mamba', 'Mamba'),
(884, 65, 'Muganza', 'Muganza', 'Muganza'),
(885, 65, 'Mugombwa', 'Mugombwa', 'Mugombwa'),
(886, 65, 'Mukindo', 'Mukindo', 'Mukindo'),
(887, 65, 'Musha', 'Musha', 'Musha'),
(888, 65, 'Ndora', 'Ndora', 'Ndora'),
(889, 65, 'Nyanza', 'Nyanza', 'Nyanza'),
(890, 65, 'Save', 'Save', 'Save'),
(891, 66, 'Busanze', 'Busanze', 'Busanze'),
(892, 66, 'Cyahinda', 'Cyahinda', 'Cyahinda'),
(893, 66, 'Kibeho', 'Kibeho', 'Kibeho'),
(894, 66, 'Kivu', 'Kivu', 'Kivu'),
(895, 66, 'Mata', 'Mata', 'Mata'),
(896, 66, 'Muganza', 'Muganza', 'Muganza'),
(897, 66, 'Munini', 'Munini', 'Munini'),
(898, 66, 'Ngera', 'Ngera', 'Ngera'),
(899, 66, 'Ngoma', 'Ngoma', 'Ngoma'),
(900, 66, 'Nyabimata', 'Nyabimata', 'Nyabimata'),
(901, 66, 'Nyagisozi', 'Nyagisozi', 'Nyagisozi'),
(902, 66, 'Ruheru', 'Ruheru', 'Ruheru'),
(903, 66, 'Ruramba', 'Ruramba', 'Ruramba'),
(904, 66, 'Rusenge', 'Rusenge', 'Rusenge'),
(905, 67, 'Gishamvu', 'Gishamvu', 'Gishamvu'),
(906, 67, 'Huye', 'Huye', 'Huye'),
(907, 67, 'Karama', 'Karama', 'Karama'),
(908, 67, 'Kigoma', 'Kigoma', 'Kigoma'),
(909, 67, 'Kinazi', 'Kinazi', 'Kinazi'),
(910, 67, 'Maraba', 'Maraba', 'Maraba'),
(911, 67, 'Mbazi', 'Mbazi', 'Mbazi'),
(912, 67, 'Mukura', 'Mukura', 'Mukura'),
(913, 67, 'Ngoma', 'Ngoma', 'Ngoma'),
(914, 67, 'Ruhashya', 'Ruhashya', 'Ruhashya'),
(915, 67, 'Rusatira', 'Rusatira', 'Rusatira'),
(916, 67, 'Rwaniro', 'Rwaniro', 'Rwaniro'),
(917, 67, 'Simbi', 'Simbi', 'Simbi'),
(918, 67, 'Tumba', 'Tumba', 'Tumba'),
(919, 68, 'Buruhukiro', 'Buruhukiro', 'Buruhukiro'),
(920, 68, 'Cyanika', 'Cyanika', 'Cyanika'),
(921, 68, 'Gasaka', 'Gasaka', 'Gasaka'),
(922, 68, 'Gatare', 'Gatare', 'Gatare'),
(923, 68, 'Kaduha', 'Kaduha', 'Kaduha'),
(924, 68, 'Kamegeri', 'Kamegeri', 'Kamegeri'),
(925, 68, 'Kibirizi', 'Kibirizi', 'Kibirizi'),
(926, 68, 'Kibumbwe', 'Kibumbwe', 'Kibumbwe'),
(927, 68, 'Kitabi', 'Kitabi', 'Kitabi'),
(928, 68, 'Mbazi', 'Mbazi', 'Mbazi'),
(929, 68, 'Mugano', 'Mugano', 'Mugano'),
(930, 68, 'Musange', 'Musange', 'Musange'),
(931, 68, 'Musebeya', 'Musebeya', 'Musebeya'),
(932, 68, 'Mushubi', 'Mushubi', 'Mushubi'),
(933, 68, 'Nkomane', 'Nkomane', 'Nkomane'),
(934, 68, 'Tare', 'Tare', 'Tare'),
(935, 68, 'Uwinkingi', 'Uwinkingi', 'Uwinkingi'),
(936, 69, 'Bweramana', 'Bweramana', 'Bweramana'),
(937, 69, 'Byimana', 'Byimana', 'Byimana'),
(938, 69, 'Kabagali', 'Kabagali', 'Kabagali'),
(939, 69, 'Kinazi', 'Kinazi', 'Kinazi'),
(940, 69, 'Kinihira', 'Kinihira', 'Kinihira'),
(941, 69, 'Mbuye', 'Mbuye', 'Mbuye'),
(942, 69, 'Mwendo', 'Mwendo', 'Mwendo'),
(943, 69, 'Ntongwe', 'Ntongwe', 'Ntongwe'),
(944, 69, 'Ruhango', 'Ruhango', 'Ruhango'),
(945, 70, 'Cyeza', 'Cyeza', 'Cyeza'),
(946, 70, 'Kabacuzi', 'Kabacuzi', 'Kabacuzi'),
(947, 70, 'Kibangu', 'Kibangu', 'Kibangu'),
(948, 70, 'Kiyumba', 'Kiyumba', 'Kiyumba'),
(949, 70, 'Muhanga', 'Muhanga', 'Muhanga'),
(950, 70, 'Mushishiro', 'Mushishiro', 'Mushishiro'),
(951, 70, 'Nyabinoni', 'Nyabinoni', 'Nyabinoni'),
(952, 70, 'Nyamabuye', 'Nyamabuye', 'Nyamabuye'),
(953, 70, 'Nyarusange', 'Nyarusange', 'Nyarusange'),
(954, 70, 'Rongi', 'Rongi', 'Rongi'),
(955, 70, 'Rugendabari', 'Rugendabari', 'Rugendabari'),
(956, 70, 'Shyogwe', 'Shyogwe', 'Shyogwe'),
(957, 71, 'Gacurabwenge', 'Gacurabwenge', 'Gacurabwenge'),
(958, 71, 'Karama', 'Karama', 'Karama'),
(959, 71, 'Kayenzi', 'Kayenzi', 'Kayenzi'),
(960, 71, 'Kayumbu', 'Kayumbu', 'Kayumbu'),
(961, 71, 'Mugina', 'Mugina', 'Mugina'),
(962, 71, 'Musambira', 'Musambira', 'Musambira'),
(963, 71, 'Ngamba', 'Ngamba', 'Ngamba'),
(964, 71, 'Nyamiyaga', 'Nyamiyaga', 'Nyamiyaga'),
(965, 71, 'Nyarubaka', 'Nyarubaka', 'Nyarubaka'),
(966, 71, 'Rugarika', 'Rugarika', 'Rugarika'),
(967, 71, 'Rukoma', 'Rukoma', 'Rukoma'),
(968, 71, 'Runda', 'Runda', 'Runda'),
(969, 72, 'Bwishyura', 'Bwishyura', 'Bwishyura'),
(970, 72, 'Gashari', 'Gashari', 'Gashari'),
(971, 72, 'Gishyita', 'Gishyita', 'Gishyita'),
(972, 72, 'Gitesi', 'Gitesi', 'Gitesi'),
(973, 72, 'Mubuga', 'Mubuga', 'Mubuga'),
(974, 72, 'Murambi', 'Murambi', 'Murambi'),
(975, 72, 'Murundi', 'Murundi', 'Murundi'),
(976, 72, 'Mutuntu', 'Mutuntu', 'Mutuntu'),
(977, 72, 'Rubengera', 'Rubengera', 'Rubengera'),
(978, 72, 'Rugabano', 'Rugabano', 'Rugabano'),
(979, 72, 'Ruganda', 'Ruganda', 'Ruganda'),
(980, 72, 'Rwankuba', 'Rwankuba', 'Rwankuba'),
(981, 72, 'Twumba', 'Twumba', 'Twumba'),
(982, 73, 'Boneza', 'Boneza', 'Boneza'),
(983, 73, 'Gihango', 'Gihango', 'Gihango'),
(984, 73, 'Kigeyo', 'Kigeyo', 'Kigeyo'),
(985, 73, 'Kivumu', 'Kivumu', 'Kivumu'),
(986, 73, 'Manihira', 'Manihira', 'Manihira'),
(987, 73, 'Mukura', 'Mukura', 'Mukura'),
(988, 73, 'Murunda', 'Murunda', 'Murunda'),
(989, 73, 'Musasa', 'Musasa', 'Musasa'),
(990, 73, 'Mushonyi', 'Mushonyi', 'Mushonyi'),
(991, 73, 'Mushubati', 'Mushubati', 'Mushubati'),
(992, 73, 'Nyabirasi', 'Nyabirasi', 'Nyabirasi'),
(993, 73, 'Ruhango', 'Ruhango', 'Ruhango'),
(994, 73, 'Rusebeya', 'Rusebeya', 'Rusebeya'),
(995, 74, 'Bugeshi', 'Bugeshi', 'Bugeshi'),
(996, 74, 'Busasamana', 'Busasamana', 'Busasamana'),
(997, 74, 'Cyanzarwe', 'Cyanzarwe', 'Cyanzarwe'),
(998, 74, 'Gisenyi', 'Gisenyi', 'Gisenyi'),
(999, 74, 'Kanama', 'Kanama', 'Kanama'),
(1000, 74, 'Kanzenze', 'Kanzenze', 'Kanzenze'),
(1001, 74, 'Mudende', 'Mudende', 'Mudende'),
(1002, 74, 'Nyakiriba', 'Nyakiriba', 'Nyakiriba'),
(1003, 74, 'Nyamyumba', 'Nyamyumba', 'Nyamyumba'),
(1004, 74, 'Nyundo', 'Nyundo', 'Nyundo'),
(1005, 74, 'Rubavu', 'Rubavu', 'Rubavu'),
(1006, 74, 'Rugerero', 'Rugerero', 'Rugerero'),
(1007, 75, 'Bigogwe', 'Bigogwe', 'Bigogwe'),
(1008, 75, 'Jenda', 'Jenda', 'Jenda'),
(1009, 75, 'Jomba', 'Jomba', 'Jomba'),
(1010, 75, 'Kabatwa', 'Kabatwa', 'Kabatwa'),
(1011, 75, 'Karago', 'Karago', 'Karago'),
(1012, 75, 'Kintobo', 'Kintobo', 'Kintobo'),
(1013, 75, 'Mukamira', 'Mukamira', 'Mukamira'),
(1014, 75, 'Muringa', 'Muringa', 'Muringa'),
(1015, 75, 'Rambura', 'Rambura', 'Rambura'),
(1016, 75, 'Rugera', 'Rugera', 'Rugera'),
(1017, 75, 'Rurembo', 'Rurembo', 'Rurembo'),
(1018, 75, 'Shyira', 'Shyira', 'Shyira'),
(1019, 76, 'BWIRA', 'BWIRA', 'BWIRA'),
(1020, 76, 'GATUMBA', 'GATUMBA', 'GATUMBA'),
(1021, 76, 'HINDIRO', 'HINDIRO', 'HINDIRO'),
(1022, 76, 'KABAYA', 'KABAYA', 'KABAYA'),
(1023, 76, 'KAGEYO', 'KAGEYO', 'KAGEYO'),
(1024, 76, 'KAVUMU', 'KAVUMU', 'KAVUMU'),
(1025, 76, 'MATYAZO', 'MATYAZO', 'MATYAZO'),
(1026, 76, 'MUHANDA', 'MUHANDA', 'MUHANDA'),
(1027, 76, 'MUHORORO', 'MUHORORO', 'MUHORORO'),
(1028, 76, 'NDARO', 'NDARO', 'NDARO'),
(1029, 76, 'NGORORERO', 'NGORORERO', 'NGORORERO'),
(1030, 76, 'NYANGE', 'NYANGE', 'NYANGE'),
(1031, 76, 'SOVU', 'SOVU', 'SOVU'),
(1032, 77, 'Bugarama', 'Bugarama', 'Bugarama'),
(1033, 77, 'Butare', 'Butare', 'Butare'),
(1034, 77, 'Bweyeye', 'Bweyeye', 'Bweyeye'),
(1035, 77, 'Gashonga', 'Gashonga', 'Gashonga'),
(1036, 77, 'Giheke', 'Giheke', 'Giheke'),
(1037, 77, 'Gihundwe', 'Gihundwe', 'Gihundwe'),
(1038, 77, 'Gikundamvura', 'Gikundamvura', 'Gikundamvura'),
(1039, 77, 'Gitambi', 'Gitambi', 'Gitambi'),
(1040, 77, 'Kamembe', 'Kamembe', 'Kamembe'),
(1041, 77, 'Muganza', 'Muganza', 'Muganza'),
(1042, 77, 'Mururu', 'Mururu', 'Mururu'),
(1043, 77, 'Nkanka', 'Nkanka', 'Nkanka'),
(1044, 77, 'Nkombo', 'Nkombo', 'Nkombo'),
(1045, 77, 'Nkungu', 'Nkungu', 'Nkungu'),
(1046, 77, 'Nyakabuye', 'Nyakabuye', 'Nyakabuye'),
(1047, 77, 'Nyakarenzo', 'Nyakarenzo', 'Nyakarenzo'),
(1048, 77, 'Nzahaha', 'Nzahaha', 'Nzahaha'),
(1049, 77, 'Rwimbogo', 'Rwimbogo', 'Rwimbogo'),
(1050, 78, 'Bushekeri', 'Bushekeri', 'Bushekeri'),
(1051, 78, 'Bushenge', 'Bushenge', 'Bushenge'),
(1052, 78, 'Cyato', 'Cyato', 'Cyato'),
(1053, 78, 'Gihombo', 'Gihombo', 'Gihombo'),
(1054, 78, 'Kagano', 'Kagano', 'Kagano'),
(1055, 78, 'Kanjongo', 'Kanjongo', 'Kanjongo'),
(1056, 78, 'Karambi', 'Karambi', 'Karambi'),
(1057, 78, 'Karengera', 'Karengera', 'Karengera'),
(1058, 78, 'Kirimbi', 'Kirimbi', 'Kirimbi'),
(1059, 78, 'Macuba', 'Macuba', 'Macuba'),
(1060, 78, 'Mahembe', 'Mahembe', 'Mahembe'),
(1061, 78, 'Nyabitekeri', 'Nyabitekeri', 'Nyabitekeri'),
(1062, 78, 'Rangiro', 'Rangiro', 'Rangiro'),
(1063, 78, 'Ruharambuga', 'Ruharambuga', 'Ruharambuga'),
(1064, 78, 'Shangi', 'Shangi', 'Shangi'),
(1065, 79, 'BASE', 'BASE', 'BASE'),
(1066, 79, 'BUREGA', 'BUREGA', 'BUREGA'),
(1067, 79, 'BUSHOKI', 'BUSHOKI', 'BUSHOKI'),
(1068, 79, 'BUYOGA', 'BUYOGA', 'BUYOGA'),
(1069, 79, 'CYINZUZI', 'CYINZUZI', 'CYINZUZI'),
(1070, 79, 'CYUNGO', 'CYUNGO', 'CYUNGO'),
(1071, 79, 'KINIHIRA', 'KINIHIRA', 'KINIHIRA'),
(1072, 79, 'KISARO', 'KISARO', 'KISARO'),
(1073, 79, 'MASORO', 'MASORO', 'MASORO'),
(1074, 79, 'MBOGO', 'MBOGO', 'MBOGO'),
(1075, 79, 'MURAMBI', 'MURAMBI', 'MURAMBI'),
(1076, 79, 'NGOMA', 'NGOMA', 'NGOMA'),
(1077, 79, 'NTARABANA', 'NTARABANA', 'NTARABANA'),
(1078, 79, 'RUKOZO', 'RUKOZO', 'RUKOZO'),
(1079, 79, 'RUSIGA', 'RUSIGA', 'RUSIGA'),
(1080, 79, 'SHYORONGI', 'SHYORONGI', 'SHYORONGI'),
(1081, 79, 'TUMBA', 'TUMBA', 'TUMBA'),
(1082, 80, 'Busengo', 'Busengo', 'Busengo'),
(1083, 80, 'Coko', 'Coko', 'Coko'),
(1084, 80, 'Cyabingo', 'Cyabingo', 'Cyabingo'),
(1085, 80, 'Gakenke', 'Gakenke', 'Gakenke'),
(1086, 80, 'Gashenyi', 'Gashenyi', 'Gashenyi'),
(1087, 80, 'Janja', 'Janja', 'Janja'),
(1088, 80, 'Kamubuga', 'Kamubuga', 'Kamubuga'),
(1089, 80, 'Karambo', 'Karambo', 'Karambo'),
(1090, 80, 'Kivuruga', 'Kivuruga', 'Kivuruga'),
(1091, 80, 'Mataba', 'Mataba', 'Mataba'),
(1092, 80, 'Minazi', 'Minazi', 'Minazi'),
(1093, 80, 'Mugunga', 'Mugunga', 'Mugunga'),
(1094, 80, 'Muhondo', 'Muhondo', 'Muhondo'),
(1095, 80, 'Muyongwe', 'Muyongwe', 'Muyongwe'),
(1096, 80, 'Muzo', 'Muzo', 'Muzo'),
(1097, 80, 'Nemba', 'Nemba', 'Nemba'),
(1098, 80, 'Ruli', 'Ruli', 'Ruli'),
(1099, 80, 'Rusasa', 'Rusasa', 'Rusasa'),
(1100, 80, 'Rushashi', 'Rushashi', 'Rushashi'),
(1101, 81, 'Busogo', 'Busogo', 'Busogo'),
(1102, 81, 'Cyuve', 'Cyuve', 'Cyuve'),
(1103, 81, 'Gacaca', 'Gacaca', 'Gacaca'),
(1104, 81, 'Gashaki', 'Gashaki', 'Gashaki'),
(1105, 81, 'Gataraga', 'Gataraga', 'Gataraga'),
(1106, 81, 'Kimonyi', 'Kimonyi', 'Kimonyi'),
(1107, 81, 'Kinigi', 'Kinigi', 'Kinigi'),
(1108, 81, 'Muhoza', 'Muhoza', 'Muhoza'),
(1109, 81, 'Muko', 'Muko', 'Muko'),
(1110, 81, 'Musanze', 'Musanze', 'Musanze'),
(1111, 81, 'Nkotsi', 'Nkotsi', 'Nkotsi'),
(1112, 81, 'Nyange', 'Nyange', 'Nyange'),
(1113, 81, 'Remera', 'Remera', 'Remera'),
(1114, 81, 'Rwaza', 'Rwaza', 'Rwaza'),
(1115, 81, 'Shingiro', 'Shingiro', 'Shingiro'),
(1116, 82, 'Bungwe', 'Bungwe', 'Bungwe'),
(1117, 82, 'Butaro', 'Butaro', 'Butaro'),
(1118, 82, 'Cyanika', 'Cyanika', 'Cyanika'),
(1119, 82, 'Cyeru', 'Cyeru', 'Cyeru'),
(1120, 82, 'Gahunga', 'Gahunga', 'Gahunga'),
(1121, 82, 'Gatebe', 'Gatebe', 'Gatebe'),
(1122, 82, 'Gitovu', 'Gitovu', 'Gitovu'),
(1123, 82, 'Kagogo', 'Kagogo', 'Kagogo'),
(1124, 82, 'Kinoni', 'Kinoni', 'Kinoni'),
(1125, 82, 'Kinyababa', 'Kinyababa', 'Kinyababa'),
(1126, 82, 'Kivuye', 'Kivuye', 'Kivuye'),
(1127, 82, 'Nemba', 'Nemba', 'Nemba'),
(1128, 82, 'Rugarama', 'Rugarama', 'Rugarama'),
(1129, 82, 'Rugendabari', 'Rugendabari', 'Rugendabari'),
(1130, 82, 'Ruhunde', 'Ruhunde', 'Ruhunde'),
(1131, 82, 'Rusarabuye', 'Rusarabuye', 'Rusarabuye'),
(1132, 82, 'Rwerere', 'Rwerere', 'Rwerere'),
(1133, 83, 'Bukure', 'Bukure', 'Bukure'),
(1134, 83, 'Bwisige', 'Bwisige', 'Bwisige'),
(1135, 83, 'Byumba', 'Byumba', 'Byumba'),
(1136, 83, 'Cyumba', 'Cyumba', 'Cyumba'),
(1137, 83, 'Giti', 'Giti', 'Giti'),
(1138, 83, 'Kageyo', 'Kageyo', 'Kageyo'),
(1139, 83, 'Kaniga', 'Kaniga', 'Kaniga'),
(1140, 83, 'Manyagiro', 'Manyagiro', 'Manyagiro'),
(1141, 83, 'Miyove', 'Miyove', 'Miyove'),
(1142, 83, 'Mukarange', 'Mukarange', 'Mukarange'),
(1143, 83, 'Muko', 'Muko', 'Muko'),
(1144, 83, 'Mutete', 'Mutete', 'Mutete'),
(1145, 83, 'Nyamiyaga', 'Nyamiyaga', 'Nyamiyaga'),
(1146, 83, 'Nyankenke', 'Nyankenke', 'Nyankenke'),
(1147, 83, 'Rubaya', 'Rubaya', 'Rubaya'),
(1148, 83, 'Rukomo', 'Rukomo', 'Rukomo'),
(1149, 83, 'Rushaki', 'Rushaki', 'Rushaki'),
(1150, 83, 'Rutare', 'Rutare', 'Rutare'),
(1151, 83, 'Ruvune', 'Ruvune', 'Ruvune'),
(1152, 83, 'Rwamiko', 'Rwamiko', 'Rwamiko'),
(1153, 83, 'Shangasha', 'Shangasha', 'Shangasha'),
(1154, 84, 'Fumbwe', 'Fumbwe', 'Fumbwe'),
(1155, 84, 'Gahengeri', 'Gahengeri', 'Gahengeri'),
(1156, 84, 'Gishali', 'Gishali', 'Gishali'),
(1157, 84, 'Karenge', 'Karenge', 'Karenge'),
(1158, 84, 'Kigabiro', 'Kigabiro', 'Kigabiro'),
(1159, 84, 'Muhazi', 'Muhazi', 'Muhazi'),
(1160, 84, 'Munyaga', 'Munyaga', 'Munyaga'),
(1161, 84, 'Munyiginya', 'Munyiginya', 'Munyiginya'),
(1162, 84, 'Musha', 'Musha', 'Musha'),
(1163, 84, 'Muyumbu', 'Muyumbu', 'Muyumbu'),
(1164, 84, 'Mwulire', 'Mwulire', 'Mwulire'),
(1165, 84, 'Nyakaliro', 'Nyakaliro', 'Nyakaliro'),
(1166, 84, 'Nzige', 'Nzige', 'Nzige'),
(1167, 84, 'Rubona', 'Rubona', 'Rubona'),
(1168, 85, 'GATUNDA', 'GATUNDA', 'GATUNDA'),
(1169, 85, 'KARAMA', 'KARAMA', 'KARAMA'),
(1170, 85, 'KARANGAZI', 'KARANGAZI', 'KARANGAZI'),
(1171, 85, 'KATABAGEMU', 'KATABAGEMU', 'KATABAGEMU'),
(1172, 85, 'KIYOMBE', 'KIYOMBE', 'KIYOMBE'),
(1173, 85, 'MATIMBA', 'MATIMBA', 'MATIMBA'),
(1174, 85, 'MIMURI', 'MIMURI', 'MIMURI'),
(1175, 85, 'MUKAMA', 'MUKAMA', 'MUKAMA'),
(1176, 85, 'MUSHERI', 'MUSHERI', 'MUSHERI'),
(1177, 85, 'NYAGATARE', 'NYAGATARE', 'NYAGATARE'),
(1178, 85, 'RUKOMO', 'RUKOMO', 'RUKOMO'),
(1179, 85, 'RWEMPASHA', 'RWEMPASHA', 'RWEMPASHA'),
(1180, 85, 'RWIMIYAGA', 'RWIMIYAGA', 'RWIMIYAGA'),
(1181, 85, 'TABAGWE', 'TABAGWE', 'TABAGWE'),
(1182, 86, 'Gasange', 'Gasange', 'Gasange'),
(1183, 86, 'Gatsibo', 'Gatsibo', 'Gatsibo'),
(1184, 86, 'Gitoki', 'Gitoki', 'Gitoki'),
(1185, 86, 'Kabarore', 'Kabarore', 'Kabarore'),
(1186, 86, 'Kageyo', 'Kageyo', 'Kageyo'),
(1187, 86, 'Kiramuruzi', 'Kiramuruzi', 'Kiramuruzi'),
(1188, 86, 'Kiziguro', 'Kiziguro', 'Kiziguro'),
(1189, 86, 'Muhura', 'Muhura', 'Muhura'),
(1190, 86, 'Murambi', 'Murambi', 'Murambi'),
(1191, 86, 'Ngarama', 'Ngarama', 'Ngarama'),
(1192, 86, 'Nyagihanga', 'Nyagihanga', 'Nyagihanga'),
(1193, 86, 'Remera', 'Remera', 'Remera'),
(1194, 86, 'Rugarama', 'Rugarama', 'Rugarama'),
(1195, 86, 'Rwimbogo', 'Rwimbogo', 'Rwimbogo'),
(1196, 87, 'Gahini', 'Gahini', 'Gahini'),
(1197, 87, 'Kabare', 'Kabare', 'Kabare'),
(1198, 87, 'Kabarondo', 'Kabarondo', 'Kabarondo'),
(1199, 87, 'Mukarange', 'Mukarange', 'Mukarange'),
(1200, 87, 'Murama', 'Murama', 'Murama'),
(1201, 87, 'Murundi', 'Murundi', 'Murundi'),
(1202, 87, 'Mwiri', 'Mwiri', 'Mwiri'),
(1203, 87, 'Ndego', 'Ndego', 'Ndego'),
(1204, 87, 'Nyamirama', 'Nyamirama', 'Nyamirama'),
(1205, 87, 'Rukara', 'Rukara', 'Rukara'),
(1206, 87, 'Ruramira', 'Ruramira', 'Ruramira'),
(1207, 87, 'Rwinkwavu', 'Rwinkwavu', 'Rwinkwavu'),
(1208, 88, 'Gahara', 'Gahara', 'Gahara'),
(1209, 88, 'Gatore', 'Gatore', 'Gatore'),
(1210, 88, 'Kigarama', 'Kigarama', 'Kigarama'),
(1211, 88, 'Kigina', 'Kigina', 'Kigina'),
(1212, 88, 'Kirehe', 'Kirehe', 'Kirehe'),
(1213, 88, 'Mahama', 'Mahama', 'Mahama'),
(1214, 88, 'Mpanga', 'Mpanga', 'Mpanga'),
(1215, 88, 'Musaza', 'Musaza', 'Musaza'),
(1216, 88, 'Mushikiri', 'Mushikiri', 'Mushikiri'),
(1217, 88, 'Nasho', 'Nasho', 'Nasho'),
(1218, 88, 'Nyamugari', 'Nyamugari', 'Nyamugari'),
(1219, 88, 'Nyarubuye', 'Nyarubuye', 'Nyarubuye'),
(1220, 89, 'Gashanda', 'Gashanda', 'Gashanda'),
(1221, 89, 'Jarama', 'Jarama', 'Jarama'),
(1222, 89, 'Karembo', 'Karembo', 'Karembo'),
(1223, 89, 'Kazo', 'Kazo', 'Kazo'),
(1224, 89, 'Kibungo', 'Kibungo', 'Kibungo'),
(1225, 89, 'Mugesera', 'Mugesera', 'Mugesera'),
(1226, 89, 'Murama', 'Murama', 'Murama'),
(1227, 89, 'Mutenderi', 'Mutenderi', 'Mutenderi'),
(1228, 89, 'Remera', 'Remera', 'Remera'),
(1229, 89, 'Rukira', 'Rukira', 'Rukira'),
(1230, 89, 'Rukumberi', 'Rukumberi', 'Rukumberi'),
(1231, 89, 'Rurenge', 'Rurenge', 'Rurenge'),
(1232, 89, 'Sake', 'Sake', 'Sake'),
(1233, 89, 'Zaza', 'Zaza', 'Zaza'),
(1234, 90, 'Gashora', 'Gashora', 'Gashora'),
(1235, 90, 'Juru', 'Juru', 'Juru'),
(1236, 90, 'Kamabuye', 'Kamabuye', 'Kamabuye'),
(1237, 90, 'Mareba', 'Mareba', 'Mareba'),
(1238, 90, 'Mayange', 'Mayange', 'Mayange'),
(1239, 90, 'Musenyi', 'Musenyi', 'Musenyi'),
(1240, 90, 'Mwogo', 'Mwogo', 'Mwogo'),
(1241, 90, 'Ngeruka', 'Ngeruka', 'Ngeruka'),
(1242, 90, 'Ntarama', 'Ntarama', 'Ntarama'),
(1243, 90, 'Nyamata', 'Nyamata', 'Nyamata'),
(1244, 90, 'Nyarugenge', 'Nyarugenge', 'Nyarugenge'),
(1245, 90, 'Ririma', 'Ririma', 'Ririma'),
(1246, 90, 'Ruhuha', 'Ruhuha', 'Ruhuha'),
(1247, 90, 'Rweru', 'Rweru', 'Rweru'),
(1248, 90, 'Shyara', 'Shyara', 'Shyara');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(191) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('delivery_base', '4000', '2025-10-09 10:44:44'),
('delivery_enabled', 'false', '2025-10-09 10:44:44'),
('delivery_mode', 'auto', '2025-10-09 10:44:44'),
('delivery_per_unit', '300', '2025-10-09 10:44:44'),
('min_quantity', '1', '2025-10-09 10:44:44'),
('platform_markup_pct', '0.08', '2025-10-09 10:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `provider_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('farmer','buyer','admin') NOT NULL DEFAULT 'farmer',
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `profile_pic`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '0781065112', '$2y$10$XobKiqCpXo16UnVDCvPIx.o0Y2ybzw.RpNBdxBHz9vHWY1hTgDbpG', 'admin', 'uploads/6887490892cf5_profile43.jpg', '2025-07-28 09:55:20'),
(3, 'Bertin HAKIZAYEZU', 'oficialbertin@gmail.com', '0781065113', '$2y$10$NFoOoc2HcFwisYS3imepLuz1U8fBtu4mkjaSGcxIHEux7jCAqerlu', 'farmer', 'uploads/68874977bba2f_profile.jpg', '2025-07-28 09:57:11'),
(5, 'muhire', 'muhire@gmail.com', '0781065212', '$2y$10$vr5h32/hEafhjNvKEhg4Pu0Zi0Qn7QjKov25XhX9iw65WCxIJxcyK', 'buyer', 'uploads/688791881b7a6_rp_logo.png', '2025-07-28 14:59:43'),
(7, 'Bertin niga', 'bertinniga@gmail.com', '0781065119', '$2y$10$vBgpm68.t4Hav7aLHwaOnu9rdMbvjOPHjfmEtQ55/O1LXaE.b9pRu', 'farmer', NULL, '2025-10-11 13:48:36'),
(8, 'muhizi', NULL, '250785535316', '$2y$10$Ngc8gVvJOwwDQU.bh0jfZuAGO8G1Cu7vOX1RL0CJGAK/pxG3vzeea', 'farmer', NULL, '2025-10-14 17:46:55');

-- --------------------------------------------------------

--
-- Table structure for table `ussd_logs`
--

CREATE TABLE `ussd_logs` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `input_text` text DEFAULT NULL,
  `response_text` text DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ussd_sessions`
--

CREATE TABLE `ussd_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ussd_sessions`
--

INSERT INTO `ussd_sessions` (`id`, `session_id`, `phone_number`, `data`, `created_at`, `updated_at`) VALUES
(4, 'ATUid_070872b471f34307e91c896815a9f42a', '250785535316', '{\"level\":\"role_selection\",\"language\":\"rw\",\"registration_role\":\"buyer\",\"registration_name\":\"bertin\",\"registration_email\":\"muhire121@gmail.com\",\"registration_province\":\"KIGALI\"}', '2025-10-14 16:38:38', '2025-10-14 16:39:23'),
(5, 'ATUid_3cafde6209a26a7e7e29b2b035f84dcf', '250785535316', '{\"level\":\"role_selection\",\"language\":\"en\",\"registration_role\":\"farmer\",\"registration_name\":\"muhizi\",\"registration_email\":\"muhizi@gmail.com\",\"registration_province\":\"KIGALI\"}', '2025-10-14 16:44:10', '2025-10-14 16:44:56'),
(6, 'ATUid_5a8aaf0262caaff8ad1c968033cb31b8', '250785535316', '{\"level\":\"role_selection\",\"language\":\"rw\"}', '2025-10-14 17:04:50', '2025-10-14 17:04:55'),
(7, 'ATUid_34a6e6a285d45dd3a8ff294869a792c3', '250785535316', '{\"level\":\"language_selection\"}', '2025-10-14 17:44:19', '2025-10-14 17:44:19'),
(8, 'ATUid_8d1ade40cabad58a359782bb5a43c6d2', '250785535316', '{\"level\":\"language_selection\"}', '2025-10-14 17:44:29', '2025-10-14 17:44:29'),
(9, 'ATUid_0cac1d486ba8d129ef15ee933eb7265b', '250785535316', '{\"level\":\"language_selection\"}', '2025-10-14 17:45:47', '2025-10-14 17:45:47'),
(10, 'ATUid_80e4f14e429622731190f9627a313e9d', '250785535316', '{\"level\":\"registration_province\",\"language\":\"rw\",\"registration_role\":\"buyer\",\"registration_name\":\"muhizi\"}', '2025-10-14 17:45:56', '2025-10-14 17:46:16'),
(11, 'ATUid_fe4a1ae6d4935d07ae1d428f1ca2a700', '250785535316', '{\"language\":\"en\"}', '2025-10-14 17:46:27', '2025-10-14 17:46:57'),
(12, 'ATUid_b9896324afd14079d81ca4b89962a270', '250785535316', '{\"level\":\"main_menu\",\"language\":\"en\"}', '2025-10-14 17:47:02', '2025-10-14 17:47:55'),
(13, 'ATUid_3036b0d8d2f1b716e3d1135c1c45eaf6', '250785535316', '{\"level\":\"main_menu\",\"language\":\"en\"}', '2025-10-14 17:52:47', '2025-10-14 17:53:47');

-- --------------------------------------------------------

--
-- Structure for view `market_prices_ussd_view`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_addresses_province` (`province_id`),
  ADD KEY `idx_addresses_district` (`district_id`),
  ADD KEY `idx_addresses_sector` (`sector_id`);

--
-- Indexes for table `ai_model_performance`
--
ALTER TABLE `ai_model_performance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_training_data`
--
ALTER TABLE `ai_training_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crops`
--
ALTER TABLE `crops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crops_farmer_id` (`farmer_id`),
  ADD KEY `idx_crops_quantity` (`quantity`);

--
-- Indexes for table `crop_sales`
--
ALTER TABLE `crop_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `demand_forecast`
--
ALTER TABLE `demand_forecast`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `raised_by` (`raised_by`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `province_id` (`province_id`,`name`),
  ADD KEY `idx_districts_province_id` (`province_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_token` (`email`,`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `farming_tips`
--
ALTER TABLE `farming_tips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farming_tips_category` (`category`),
  ADD KEY `idx_farming_tips_created` (`created_at`);

--
-- Indexes for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitoring_metrics`
--
ALTER TABLE `monitoring_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_monitoring_metrics_ts` (`timestamp`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_buyer_id` (`buyer_id`),
  ADD KEY `idx_orders_crop_id` (`crop_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `released_by` (`released_by`);

--
-- Indexes for table `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_price_alerts_user` (`user_id`),
  ADD KEY `idx_price_alerts_crop` (`crop_name`),
  ADD KEY `idx_price_alerts_active` (`is_active`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `district_id` (`district_id`,`name`),
  ADD KEY `idx_sectors_district_id` (`district_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sms_logs_phone` (`phone_number`),
  ADD KEY `idx_sms_logs_status` (`status`),
  ADD KEY `idx_sms_logs_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_phone` (`phone`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `ussd_logs`
--
ALTER TABLE `ussd_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ussd_logs_session` (`session_id`),
  ADD KEY `idx_ussd_logs_phone` (`phone_number`),
  ADD KEY `idx_ussd_logs_created` (`created_at`);

--
-- Indexes for table `ussd_sessions`
--
ALTER TABLE `ussd_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_ussd_sessions_session_id` (`session_id`),
  ADD KEY `idx_ussd_sessions_phone` (`phone_number`),
  ADD KEY `idx_ussd_sessions_updated` (`updated_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ai_model_performance`
--
ALTER TABLE `ai_model_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_training_data`
--
ALTER TABLE `ai_training_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crops`
--
ALTER TABLE `crops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `crop_sales`
--
ALTER TABLE `crop_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `demand_forecast`
--
ALTER TABLE `demand_forecast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `farming_tips`
--
ALTER TABLE `farming_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `monitoring_metrics`
--
ALTER TABLE `monitoring_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `price_alerts`
--
ALTER TABLE `price_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1249;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ussd_logs`
--
ALTER TABLE `ussd_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ussd_sessions`
--
ALTER TABLE `ussd_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`),
  ADD CONSTRAINT `addresses_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  ADD CONSTRAINT `addresses_ibfk_3` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`);

--
-- Constraints for table `crops`
--
ALTER TABLE `crops`
  ADD CONSTRAINT `crops_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `crop_sales`
--
ALTER TABLE `crop_sales`
  ADD CONSTRAINT `crop_sales_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  ADD CONSTRAINT `crop_sales_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `crop_sales_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`raised_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `disputes_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD CONSTRAINT `fk_price_alerts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sectors`
--
ALTER TABLE `sectors`
  ADD CONSTRAINT `sectors_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
