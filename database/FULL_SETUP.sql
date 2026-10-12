-- ============================================================
-- PETCARE+ CLINIC HUB — COMPLETE DATABASE SETUP
-- ============================================================
-- This file combines:
--   1. embedded base schema and seed data
--   2. later schema migrations
--
-- !! DESTRUCTIVE: the base dump contains DROP TABLE IF EXISTS. !!
-- Import only into a newly created, empty database. Importing this file
-- into a database with data will drop and recreate the base tables.
-- Do not use this as an incremental production migration.
--
-- For existing databases, review and apply only the necessary migration
-- statements after taking a verified backup. Do not import this full setup.
--
-- HOW TO USE (Local XAMPP):
-- Import into a newly created, empty petcare_clinic_hub database.
-- Use phpMyAdmin Import rather than pasting the full file into SQL.
--
-- Generated: September 21, 2026
-- ============================================================

-- ============================================================
-- PART 1: MAIN SCHEMA & SEED DATA
-- (embedded base schema)
-- ============================================================
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 06:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `petcare_clinic_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

DROP TABLE IF EXISTS `ai_recommendations`;
CREATE TABLE `ai_recommendations` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `recommendation_type` enum('veterinary','grooming','general') NOT NULL DEFAULT 'general',
  `prompt_used` text DEFAULT NULL,
  `recommendation` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_summaries`
--

DROP TABLE IF EXISTS `ai_summaries`;
CREATE TABLE `ai_summaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `summary` text NOT NULL,
  `prompt_used` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED DEFAULT NULL,
  `vet_id` int(10) UNSIGNED DEFAULT NULL,
  `groomer_id` int(10) UNSIGNED DEFAULT NULL,
  `slot_id` int(10) UNSIGNED DEFAULT NULL,
  `appointment_type` enum('veterinary','grooming') NOT NULL DEFAULT 'veterinary',
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `status` enum('pending','approved','rejected','completed','cancelled','rescheduled') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reschedule_date` date DEFAULT NULL,
  `reschedule_time` time DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_slots`
--

DROP TABLE IF EXISTS `appointment_slots`;
CREATE TABLE `appointment_slots` (
  `id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(10) UNSIGNED DEFAULT NULL,
  `provider_type` enum('veterinarian','groomer') DEFAULT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

DROP TABLE IF EXISTS `clinics`;
CREATE TABLE `clinics` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT 'Pangasinan',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `operating_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_hours`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_ratings` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`id`, `admin_id`, `name`, `description`, `address`, `city`, `province`, `phone`, `email`, `website`, `logo`, `cover_photo`, `latitude`, `longitude`, `operating_hours`, `is_active`, `average_rating`, `total_ratings`, `created_at`, `updated_at`) VALUES
(26, 2, 'PetAlly Veterinary Clinic', 'Full-service veterinary clinic located along Perez Boulevard, Dagupan City.', 'Perez Blvd, Dagupan City', 'Dagupan', 'Pangasinan', '+63 981 246 7042', NULL, NULL, NULL, NULL, 16.04362000, 120.33412000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(27, 2, 'Animal Life Clinic and Supply', 'Veterinary clinic and pet supply store serving Dagupan City and nearby areas.', 'Amado St, Tapuac, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 523-1327', NULL, NULL, NULL, NULL, 16.04280000, 120.33180000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(28, 2, 'Pet Protect Veterinary Clinic', 'Dedicated veterinary care along MacArthur Highway, Tapuac District.', 'McArthur Hwy, Tapuac, Dagupan City', 'Dagupan', 'Pangasinan', '+63 991 444 4677', NULL, NULL, NULL, NULL, 16.04150000, 120.33050000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(29, 2, 'St. Francis Veterinary Clinic', 'Trusted veterinary clinic on Burgos Street in the heart of Dagupan City.', 'Burgos St, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 522-0219', NULL, NULL, NULL, NULL, 16.04420000, 120.33530000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(30, 2, 'Saint Francis Veterinary Services', 'Veterinary services and consultations for all pet types in Dagupan.', 'Amado St, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 515-6675', NULL, NULL, NULL, NULL, 16.04310000, 120.33220000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(31, 2, 'MJM Veterinary Clinic', 'Veterinary clinic and pet supply store in Lucao District, Dagupan.', 'Lucao, Dagupan City', 'Dagupan', 'Pangasinan', '+63 969 197 1305', NULL, NULL, NULL, NULL, 16.05100000, 120.34200000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(32, 2, 'PetDoctors Animal Clinic - Dagupan', 'Modern animal clinic in Bonuan Gueset, offering comprehensive vet services.', 'Bonuan Gueset, Dagupan City', 'Dagupan', 'Pangasinan', NULL, NULL, NULL, NULL, NULL, 16.02900000, 120.33800000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(33, 2, 'St. Martin Animal Clinic', 'Reliable animal clinic along Arellano Street, Dagupan City.', 'Arellano St, Dagupan City', 'Dagupan', 'Pangasinan', NULL, NULL, NULL, NULL, NULL, 16.04500000, 120.33600000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(34, 2, 'Paws & Purrs Pet Station', 'Premium pet grooming and boarding facility in Tapuac District.', 'Tapuac District, Dagupan City', 'Dagupan', 'Pangasinan', '+63 961 887 0903', NULL, NULL, NULL, 'http://localhost/WEB%20petcare/uploads/clinics/paws_cover.jpg', 16.04200000, 120.33100000, '{\"monday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"9:00 AM\",\"close\":\"5:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-09-16 03:07:47'),
(35, 2, 'RH Elegance Pet Grooming', 'Professional pet grooming services in Caranglaan, Dagupan City.', 'Caranglaan, Dagupan City', 'Dagupan', 'Pangasinan', '+63 991 341 5497', NULL, NULL, NULL, NULL, 16.03700000, 120.32900000, '{\"monday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"9:00 AM\",\"close\":\"5:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-06-20 16:26:34'),
(37, 2, 'Nose To Tail Veterinary Clinic and Grooming Center', 'Complete vet and grooming services for all pets in Calasiao.', 'Calasiao, Pangasinan', 'Calasiao', 'Pangasinan', NULL, NULL, NULL, NULL, 'http://localhost/WEB%20petcare/uploads/clinics/NTTVC_cover.jpg', 16.01000000, 120.36600000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1, 0.00, 0, '2026-06-20 16:26:34', '2026-09-16 03:07:47'),
(46, 2, 'Every Paw Veterinary Clinic', 'Open Daily except Wednesdays: 9AM - 6PM. Located along Caranglaan Dagupan-Calasiao Rd, Brgy. San Miguel, Calasiao, Pangasinan (Beside Datuin Motorworks).', 'Along Caranglaan Dagupan-Calasiao Rd, Brgy. San Miguel, Calasiao, Pangasinan (Beside Datuin Motorworks)', 'Calasiao', 'Pangasinan', NULL, NULL, NULL, NULL, 'http://localhost/WEB%20petcare/uploads/clinics/evey_cover.jpg', 16.00810000, 120.41670000, NULL, 1, 0.00, 0, '2026-09-03 09:13:48', '2026-09-16 03:07:47'),
(47, 2, 'D&R Dog and Cat Clinic', 'Veterinary and Grooming clinic. Located meters away from Panaderia Antonio just beside Bumanlag.', 'De Venecia Highway, Banaoang, Calasiao, Pangasinan', 'Calasiao', 'Pangasinan', '09226576611', NULL, NULL, NULL, 'http://localhost/WEB%20petcare/uploads/clinics/DR_cover.jpg', 16.00810000, 120.41670000, NULL, 1, 0.00, 0, '2026-09-03 10:00:21', '2026-09-16 03:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `favorite_clinics`
--

DROP TABLE IF EXISTS `favorite_clinics`;
CREATE TABLE `favorite_clinics` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groomers`
--

DROP TABLE IF EXISTS `groomers`;
CREATE TABLE `groomers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `years_experience` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groomers`
--

INSERT INTO `groomers` (`id`, `user_id`, `clinic_id`, `specialization`, `bio`, `years_experience`, `photo`, `is_available`, `created_at`, `updated_at`) VALUES
(7, 5, 34, 'All Breeds & Cats', NULL, 5, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(8, 25, 35, 'All Breeds', NULL, 4, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(9, 26, 37, 'Small Breeds', NULL, 3, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(11, 28, 34, 'Large Breeds', NULL, 4, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(12, 29, 35, 'Cats & Small Dogs', NULL, 5, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(13, 30, 37, 'All Breeds', NULL, 2, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(1, 100, 'Account Approved!', 'Your account has been verified and approved. You can now log in to PetCare+.', 'success', 0, 'http://127.0.0.1:8000/auth/login.php', '2026-09-12 12:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
CREATE TABLE `pets` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `species` varchar(100) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age_years` int(11) DEFAULT NULL,
  `age_months` int(11) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `gender` enum('male','female','unknown') NOT NULL DEFAULT 'unknown',
  `color` varchar(100) DEFAULT NULL,
  `microchip_number` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `owner_id`, `name`, `species`, `breed`, `age_years`, `age_months`, `weight_kg`, `gender`, `color`, `microchip_number`, `photo`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 7, 'Luna', 'Cat', 'Half persian', NULL, 6, 4.40, 'male', 'GRAY', NULL, 'uploads/pets/253d49c7254e59e22f9585460763fa23.png', '', 1, '2026-06-20 13:28:17', '2026-06-20 13:28:17');

-- --------------------------------------------------------

--
-- Table structure for table `pet_health_records`
--

DROP TABLE IF EXISTS `pet_health_records`;
CREATE TABLE `pet_health_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `vet_id` int(10) UNSIGNED DEFAULT NULL,
  `record_type` varchar(100) NOT NULL DEFAULT 'checkup',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `next_appointment` date DEFAULT NULL,
  `record_date` date NOT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `appointment_id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `vet_id` int(10) UNSIGNED DEFAULT NULL,
  `groomer_id` int(10) UNSIGNED DEFAULT NULL,
  `clinic_rating` tinyint(4) DEFAULT NULL,
  `vet_rating` tinyint(4) DEFAULT NULL,
  `groomer_rating` tinyint(4) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'pet_owner', 'Registered pet owners', '2026-06-20 12:03:43'),
(2, 'veterinarian', 'Licensed veterinarians', '2026-06-20 12:03:43'),
(3, 'groomer', 'Professional pet groomers', '2026-06-20 12:03:43'),
(4, 'admin', 'Clinic administrators', '2026-06-20 12:03:43'),
(5, 'superadmin', 'System super-administrator', '2026-06-20 12:03:43');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `service_type` enum('veterinary','grooming','both') NOT NULL DEFAULT 'veterinary',
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `clinic_id`, `name`, `description`, `service_type`, `duration_minutes`, `price`, `is_active`, `created_at`) VALUES
(1, 26, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(2, 26, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(3, 26, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(4, 26, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(5, 26, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 2500.00, 1, '2026-09-03 03:20:22'),
(6, 26, 'Dental Cleaning', 'Professional teeth cleaning under sedation', 'veterinary', 60, 1200.00, 1, '2026-09-03 03:20:22'),
(7, 26, 'X-Ray / Radiograph', 'Diagnostic imaging for bones and organs', 'veterinary', 30, 800.00, 1, '2026-09-03 03:20:22'),
(8, 26, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(9, 26, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(10, 26, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(11, 27, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(12, 27, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(13, 27, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(14, 27, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(15, 27, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 2500.00, 1, '2026-09-03 03:20:22'),
(16, 27, 'Dental Cleaning', 'Professional teeth cleaning under sedation', 'veterinary', 60, 1200.00, 1, '2026-09-03 03:20:22'),
(17, 27, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(18, 27, 'Wound Care & Dressing', 'Cleaning and bandaging of wounds', 'veterinary', 30, 350.00, 1, '2026-09-03 03:20:22'),
(19, 27, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(20, 27, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(21, 28, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(22, 28, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(23, 28, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(24, 28, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(25, 28, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 2500.00, 1, '2026-09-03 03:20:22'),
(26, 28, 'X-Ray / Radiograph', 'Diagnostic imaging for bones and organs', 'veterinary', 30, 800.00, 1, '2026-09-03 03:20:22'),
(27, 28, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(28, 28, 'Urinalysis', 'Urine examination for kidney and bladder health', 'veterinary', 30, 400.00, 1, '2026-09-03 03:20:22'),
(29, 28, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(30, 28, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(31, 29, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(32, 29, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(33, 29, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(34, 29, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(35, 29, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 2500.00, 1, '2026-09-03 03:20:22'),
(36, 29, 'Dental Cleaning', 'Professional teeth cleaning under sedation', 'veterinary', 60, 1200.00, 1, '2026-09-03 03:20:22'),
(37, 29, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(38, 29, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(39, 29, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(40, 29, 'Senior Pet Wellness Exam', 'Comprehensive exam for aging pets', 'veterinary', 45, 450.00, 1, '2026-09-03 03:20:22'),
(41, 30, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(42, 30, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(43, 30, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(44, 30, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(45, 30, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(46, 30, 'Urinalysis', 'Urine examination for kidney and bladder health', 'veterinary', 30, 400.00, 1, '2026-09-03 03:20:22'),
(47, 30, 'Wound Care & Dressing', 'Cleaning and bandaging of wounds', 'veterinary', 30, 350.00, 1, '2026-09-03 03:20:22'),
(48, 30, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(49, 30, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(50, 31, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(51, 31, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(52, 31, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(53, 31, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(54, 31, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 2500.00, 1, '2026-09-03 03:20:22'),
(55, 31, 'Dental Cleaning', 'Professional teeth cleaning under sedation', 'veterinary', 60, 1200.00, 1, '2026-09-03 03:20:22'),
(56, 31, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(57, 31, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(58, 31, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(59, 32, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 350.00, 1, '2026-09-03 03:20:22'),
(60, 32, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 275.00, 1, '2026-09-03 03:20:22'),
(61, 32, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(62, 32, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(63, 32, 'Spay / Neuter', 'Surgical sterilization procedure', 'veterinary', 120, 3000.00, 1, '2026-09-03 03:20:22'),
(64, 32, 'Dental Cleaning', 'Professional teeth cleaning under sedation', 'veterinary', 60, 1500.00, 1, '2026-09-03 03:20:22'),
(65, 32, 'X-Ray / Radiograph', 'Diagnostic imaging for bones and organs', 'veterinary', 30, 900.00, 1, '2026-09-03 03:20:22'),
(66, 32, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 700.00, 1, '2026-09-03 03:20:22'),
(67, 32, 'Ultrasound', 'Abdominal and cardiac ultrasound imaging', 'veterinary', 45, 1200.00, 1, '2026-09-03 03:20:22'),
(68, 32, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(69, 32, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 600.00, 1, '2026-09-03 03:20:22'),
(70, 33, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(71, 33, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(72, 33, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(73, 33, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(74, 33, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(75, 33, 'Wound Care & Dressing', 'Cleaning and bandaging of wounds', 'veterinary', 30, 350.00, 1, '2026-09-03 03:20:22'),
(76, 33, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(77, 33, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(151, 37, 'General Consultation', 'Routine check-up and health assessment', 'veterinary', 30, 300.00, 1, '2026-09-03 03:20:22'),
(152, 37, 'Vaccination', 'Core and non-core vaccines for dogs and cats', 'veterinary', 20, 250.00, 1, '2026-09-03 03:20:22'),
(153, 37, 'Deworming', 'Oral deworming treatment for pets', 'veterinary', 15, 150.00, 1, '2026-09-03 03:20:22'),
(154, 37, 'Flea & Tick Treatment', 'Topical or oral flea and tick prevention', 'veterinary', 20, 200.00, 1, '2026-09-03 03:20:22'),
(155, 37, 'Blood Test / CBC', 'Complete blood count and chemistry panel', 'veterinary', 30, 600.00, 1, '2026-09-03 03:20:22'),
(156, 37, 'Microchipping', 'Permanent pet identification implant', 'veterinary', 15, 500.00, 1, '2026-09-03 03:20:22'),
(157, 37, 'Emergency Consultation', 'Urgent care for sick or injured pets', 'veterinary', 45, 500.00, 1, '2026-09-03 03:20:22'),
(158, 37, 'Bath & Blow Dry', 'Shampoo, conditioner, blow dry and brush out', 'grooming', 60, 300.00, 1, '2026-09-03 03:20:22'),
(159, 37, 'Puppy Cut', 'Short all-over trim, neat and easy to maintain', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(160, 37, 'Teddy Bear Cut', 'Rounded fluffy cut giving a stuffed-toy appearance', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(161, 37, 'Kennel Cut', 'Practical short clip for easy maintenance', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(162, 37, 'Summer Cut', 'Very short trim ideal for hot weather', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(163, 37, 'Winter Cut', 'Slightly longer warm coat trim for cold months', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(164, 37, 'Lamb Cut', 'Fluffy rounded body with clean legs', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(165, 37, 'Lion Cut', 'Mane left on head, short body clip', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(166, 37, 'Asian Fusion Cut', 'Creative styled cut popular for small breeds', 'grooming', 120, 650.00, 1, '2026-09-03 03:20:22'),
(167, 37, 'Sanitary Trim', 'Trimming around private areas for hygiene', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(168, 37, 'Hygiene Trim', 'Trimming around sanitary areas', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(169, 37, 'Belly Trim', 'Trimming of belly fur for hygiene', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(170, 37, 'Tail Trim', 'Shaping and trimming of the tail', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(171, 37, 'Ear Trim', 'Shaping and tidying the ear fur', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(172, 37, 'Face Trim', 'General facial fur tidying', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(173, 37, 'Paw Pad Trim', 'Trimming fur between paw pads', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(174, 37, 'Nail Cutting', 'Safe trimming of pet nails', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(175, 37, 'Nail Grinding', 'Smooth filing with rotary tool', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(176, 37, 'Ear Cleaning', 'Gentle removal of debris and wax from ears', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(177, 37, 'Ear Hair Plucking', 'Removal of hair inside the ear canal', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(178, 37, 'Eye Cleaning', 'Gentle cleaning around the eyes', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(179, 37, 'Teeth Brushing', 'Brushing teeth to prevent tartar build-up', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(180, 37, 'Anal Gland Expression', 'Gentle manual expression of anal glands', 'grooming', 15, 150.00, 1, '2026-09-03 03:20:22'),
(181, 37, 'De-shedding Treatment', 'Deep treatment to reduce heavy shedding', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(182, 37, 'Undercoat Removal', 'Thorough removal of excess undercoat', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(183, 37, 'Dematting', 'Careful removal of mats and tangles', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(184, 37, 'Coat Conditioning', 'Deep moisturizing treatment for coat health', 'grooming', 45, 300.00, 1, '2026-09-03 03:20:22'),
(185, 37, 'Oatmeal Bath', 'Soothing bath for itchy or sensitive skin', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(186, 37, 'Flea & Tick Bath', 'Medicated bath targeting fleas and ticks', 'grooming', 60, 350.00, 1, '2026-09-03 03:20:22'),
(187, 37, 'Tear Stain Removal', 'Gentle cleaning of tear stains around eyes', 'grooming', 20, 130.00, 1, '2026-09-03 03:20:22'),
(188, 37, 'Paw Balm Treatment', 'Moisturizing treatment for dry cracked pads', 'grooming', 20, 130.00, 1, '2026-09-03 03:20:22'),
(189, 37, 'Perfume Application', 'Pet-safe fragrance spray after grooming', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(190, 37, 'Luxury Bath', 'Premium shampoo and conditioning bath', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(191, 37, 'Whitening Shampoo Bath', 'Brightening bath for white-coated pets', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(192, 37, 'Senior Dog Grooming', 'Gentle grooming adapted for older dogs', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(193, 37, 'Puppy Grooming', 'Introductory gentle groom for young puppies', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(194, 37, 'Large Breed Grooming', 'Full grooming service for large breed dogs', 'grooming', 120, 650.00, 1, '2026-09-03 03:20:22'),
(195, 37, 'Small Breed Grooming', 'Full grooming service for small breed dogs', 'grooming', 90, 480.00, 1, '2026-09-03 03:20:22'),
(196, 37, 'Express Grooming', 'Priority fast-track grooming service', 'grooming', 90, 650.00, 1, '2026-09-03 03:20:22'),
(197, 37, 'Full Grooming Package', 'Bath, haircut, nails, ears, and teeth', 'grooming', 120, 800.00, 1, '2026-09-03 03:20:22'),
(198, 37, 'Premium Grooming Package', 'Full groom + coat treatment + spa', 'grooming', 150, 950.00, 1, '2026-09-03 03:20:22'),
(245, 34, 'Bath & Blow Dry', 'Shampoo, conditioner, blow dry and brush out', 'grooming', 60, 300.00, 1, '2026-09-03 03:20:22'),
(246, 34, 'Shampoo & Conditioner', 'Basic shampoo and conditioner wash', 'grooming', 45, 250.00, 1, '2026-09-03 03:20:22'),
(247, 34, 'Luxury Bath', 'Premium shampoo and conditioning bath', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(248, 34, 'Medicated Bath', 'Vet-prescribed medicated shampoo bath', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(249, 34, 'Oatmeal Bath', 'Soothing bath for itchy or sensitive skin', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(250, 34, 'Whitening Shampoo Bath', 'Brightening bath for white-coated pets', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(251, 34, 'Hypoallergenic Bath', 'Gentle bath using allergy-safe products', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(252, 34, 'Flea Bath', 'Medicated flea-killing shampoo bath', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(253, 34, 'Flea & Tick Treatment', 'Medicated bath targeting fleas and ticks', 'grooming', 60, 350.00, 1, '2026-09-03 03:20:22'),
(254, 34, 'Aromatherapy Bath', 'Calming essential oil infused bath', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(255, 34, 'Puppy Cut', 'Short all-over trim, neat and easy to maintain', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(256, 34, 'Teddy Bear Cut', 'Rounded fluffy cut giving a stuffed-toy appearance', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(257, 34, 'Kennel Cut', 'Practical short clip for easy maintenance', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(258, 34, 'Summer Cut', 'Very short trim ideal for hot weather', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(259, 34, 'Winter Cut', 'Slightly longer warm coat trim for cold months', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(260, 34, 'Lamb Cut', 'Fluffy rounded body with clean legs', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(261, 34, 'Lion Cut', 'Mane left on head, short body clip', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(262, 34, 'Asian Fusion Cut', 'Creative styled cut popular for small breeds', 'grooming', 120, 700.00, 1, '2026-09-03 03:20:22'),
(263, 34, 'Asian Teddy Cut', 'Soft rounded Asian-style teddy bear cut', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(264, 34, 'Show Cut', 'Breed-standard precision cut for show dogs', 'grooming', 150, 1200.00, 1, '2026-09-03 03:20:22'),
(265, 34, 'Breed Standard Cut', 'Grooming per official breed standard', 'grooming', 120, 900.00, 1, '2026-09-03 03:20:22'),
(266, 34, 'Breed Styling', 'Breed-specific styling and grooming', 'grooming', 120, 800.00, 1, '2026-09-03 03:20:22'),
(267, 34, 'Plush Cut', 'Extra fluffy soft finish styling', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(268, 34, 'Natural Trim', 'Minimal trim following natural coat shape', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(269, 34, 'Custom Haircut', 'Tailored cut per owner request', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(270, 34, 'Creative Grooming', 'Custom artistic styling', 'grooming', 180, 1500.00, 1, '2026-09-03 03:20:22'),
(271, 34, 'Pattern Clip', 'Specific pattern clipping for the coat', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(272, 34, 'Hand Scissoring', 'Full scissor finish for precise styling', 'grooming', 120, 800.00, 1, '2026-09-03 03:20:22'),
(273, 34, 'Hand Stripping', 'Removal of dead coat by hand', 'grooming', 120, 900.00, 1, '2026-09-03 03:20:22'),
(274, 34, 'Scissor Finish', 'Neat scissor work to finish any style', 'grooming', 60, 350.00, 1, '2026-09-03 03:20:22'),
(275, 34, 'Full Body Trim', 'All-over body trim and tidy', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(276, 34, 'Full Body Clip', 'Full short clip all over', 'grooming', 90, 480.00, 1, '2026-09-03 03:20:22'),
(277, 34, 'Short Clip', 'Very short all-over clip', 'grooming', 75, 420.00, 1, '2026-09-03 03:20:22'),
(278, 34, 'Medium Clip', 'Medium length all-over clip', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(279, 34, 'Long Coat Trim', 'Trimming and thinning of long coats', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(280, 34, 'Maintenance Trim', 'Light trim to maintain current style', 'grooming', 45, 300.00, 1, '2026-09-03 03:20:22'),
(281, 34, 'Round Face Cut', 'Perfectly rounded face trim', 'grooming', 45, 200.00, 1, '2026-09-03 03:20:22'),
(282, 34, 'Clean Face Cut', 'Close trim around the face for a neat look', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(283, 34, 'Clean Feet Cut', 'Neat trim around paws and feet', 'grooming', 30, 120.00, 1, '2026-09-03 03:20:22'),
(284, 34, 'Sanitary Trim', 'Trimming around private areas for hygiene', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(285, 34, 'Hygiene Trim', 'Trimming around sanitary areas', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(286, 34, 'Feather Trim', 'Light trimming of feathered coat on legs and tail', 'grooming', 45, 200.00, 1, '2026-09-03 03:20:22'),
(287, 34, 'Belly Trim', 'Trimming of belly fur for hygiene', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(288, 34, 'Tail Trim', 'Shaping and trimming of the tail', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(289, 34, 'Ear Trim', 'Shaping and tidying the ear fur', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(290, 34, 'Face Trim', 'General facial fur tidying', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(291, 34, 'Paw Pad Trim', 'Trimming fur between paw pads', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(292, 34, 'Rounded Legs Cut', 'Rounded styling of the leg fur', 'grooming', 45, 250.00, 1, '2026-09-03 03:20:22'),
(293, 34, 'Fluffy Finish', 'Extra fluffing and finishing spray', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(294, 34, 'Nail Cutting', 'Safe trimming of pet nails', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(295, 34, 'Nail Trimming', 'Trimming nails to safe length', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(296, 34, 'Nail Grinding', 'Smooth filing of nails using rotary tool', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(297, 34, 'Nail Filing', 'Smooth filing of nails after cutting', 'grooming', 15, 80.00, 1, '2026-09-03 03:20:22'),
(298, 34, 'Paw Pad Shaving', 'Shaving excess fur around paw pads', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(299, 34, 'Paw Moisturizing', 'Moisturizer applied to paws and pads', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(300, 34, 'Paw Balm Treatment', 'Moisturizing treatment for dry cracked pads', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(301, 34, 'Paw Cleaning', 'Thorough cleaning of all four paws', 'grooming', 15, 80.00, 1, '2026-09-03 03:20:22'),
(302, 34, 'Paw Fur Trim', 'Trim fur growing between toes', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(303, 34, 'Paw Massage', 'Relaxing massage of paw muscles', 'grooming', 15, 120.00, 1, '2026-09-03 03:20:22'),
(304, 34, 'Ear Cleaning', 'Gentle removal of debris and wax from ears', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(305, 34, 'Ear Hair Plucking', 'Removal of hair inside the ear canal', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(306, 34, 'Ear Inspection', 'Visual check of ears for issues', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(307, 34, 'Eye Cleaning', 'Gentle cleaning around the eyes', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(308, 34, 'Eye Trim', 'Trimming fur around eyes for visibility', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(309, 34, 'Tear Stain Removal', 'Gentle cleaning of tear stains', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(310, 34, 'Teeth Brushing', 'Brushing teeth to prevent tartar build-up', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(311, 34, 'Dental Freshening', 'Dental gel or spray for fresh breath', 'grooming', 10, 100.00, 1, '2026-09-03 03:20:22'),
(312, 34, 'Breath Freshening', 'Oral spray to freshen pet breath', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(313, 34, 'Oral Care', 'Full oral hygiene routine', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(314, 34, 'Anal Gland Expression', 'Gentle manual expression of anal glands', 'grooming', 15, 150.00, 1, '2026-09-03 03:20:22'),
(315, 34, 'De-shedding Treatment', 'Deep treatment to reduce heavy shedding', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(316, 34, 'Undercoat Removal', 'Thorough removal of excess undercoat', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(317, 34, 'Coat Brushing', 'Thorough brushing of the full coat', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(318, 34, 'Brush Out', 'Thorough brushing and detangling', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(319, 34, 'Dematting', 'Careful removal of mats and tangles', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(320, 34, 'Coat Conditioning', 'Deep moisturizing treatment for coat health', 'grooming', 45, 300.00, 1, '2026-09-03 03:20:22'),
(321, 34, 'Coat Moisturizing', 'Moisturizing spray and treatment', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(322, 34, 'Coat Rejuvenation', 'Intensive repair treatment for damaged coat', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(323, 34, 'Coat Shine Treatment', 'Finishing treatment for glossy coat', 'grooming', 30, 250.00, 1, '2026-09-03 03:20:22'),
(324, 34, 'Coat Polish', 'Finishing polish for coat shine', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(325, 34, 'Coat Fluffing', 'Fluffing and volumizing finish', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(326, 34, 'Blow Dry', 'Standard blow dry after bath', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(327, 34, 'High Velocity Dry', 'Professional high-powered drying', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(328, 34, 'Perfume Application', 'Light pet-safe fragrance spray after grooming', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(329, 34, 'Cologne Spray', 'Pet-safe cologne application', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(330, 34, 'Hot Oil Treatment', 'Deep nourishing oil treatment for dry coat', 'grooming', 45, 400.00, 1, '2026-09-03 03:20:22'),
(331, 34, 'Skin Moisturizing', 'Moisturizing lotion for dry skin', 'grooming', 20, 200.00, 1, '2026-09-03 03:20:22'),
(332, 34, 'Sensitive Skin Care', 'Gentle grooming for reactive skin', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(333, 34, 'Anti-Itch Treatment', 'Medicated relief for itchy skin', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(334, 34, 'Anti-Dandruff Treatment', 'Specialized dandruff control bath', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(335, 34, 'Dry Skin Treatment', 'Hydrating treatment for dry skin', 'grooming', 45, 380.00, 1, '2026-09-03 03:20:22'),
(336, 34, 'Allergy-Friendly Grooming', 'All products hypoallergenic', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(337, 34, 'Skin Inspection', 'Visual skin health check during grooming', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(338, 34, 'Blueberry Facial', 'Whitening and brightening facial scrub', 'grooming', 30, 250.00, 1, '2026-09-03 03:20:22'),
(339, 34, 'Mud Treatment', 'Detoxifying mud mask for coat and skin', 'grooming', 60, 600.00, 1, '2026-09-03 03:20:22'),
(340, 34, 'Deodorizing Treatment', 'Odor-neutralizing bath and spray', 'grooming', 45, 350.00, 1, '2026-09-03 03:20:22'),
(341, 34, 'Finishing Touch Styling', 'Final styling touches after grooming', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(342, 34, 'Puppy Grooming', 'Introductory gentle groom for young puppies', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(343, 34, 'Senior Dog Grooming', 'Gentle grooming adapted for older dogs', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(344, 34, 'Large Breed Grooming', 'Full grooming service for large breed dogs', 'grooming', 120, 700.00, 1, '2026-09-03 03:20:22'),
(345, 34, 'Small Breed Grooming', 'Full grooming service for small breed dogs', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(346, 34, 'Giant Breed Grooming', 'Grooming for giant breed dogs', 'grooming', 150, 900.00, 1, '2026-09-03 03:20:22'),
(347, 34, 'Nervous Dog Grooming', 'Patient gentle grooming for anxious dogs', 'grooming', 120, 700.00, 1, '2026-09-03 03:20:22'),
(348, 34, 'Aggressive Dog Grooming', 'Safe grooming for difficult dogs', 'grooming', 120, 700.00, 1, '2026-09-03 03:20:22'),
(349, 34, 'First-Time Grooming', 'Introductory grooming session', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(350, 34, 'Express Grooming', 'Priority fast-track grooming', 'grooming', 90, 700.00, 1, '2026-09-03 03:20:22'),
(351, 34, 'Spa Grooming', 'Full spa treatment grooming package', 'grooming', 180, 1200.00, 1, '2026-09-03 03:20:22'),
(352, 34, 'Basic Grooming Package', 'Bath, nails, ears only', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(353, 34, 'Full Grooming Package', 'Bath, haircut, nails, ears, and teeth', 'grooming', 120, 800.00, 1, '2026-09-03 03:20:22'),
(354, 34, 'Premium Grooming Package', 'Full groom + coat treatment + teeth + nails', 'grooming', 150, 1000.00, 1, '2026-09-03 03:20:22'),
(355, 35, 'Bath & Blow Dry', 'Shampoo, conditioner, blow dry and brush out', 'grooming', 60, 300.00, 1, '2026-09-03 03:20:22'),
(356, 35, 'Shampoo & Conditioner', 'Basic shampoo and conditioner wash', 'grooming', 45, 250.00, 1, '2026-09-03 03:20:22'),
(357, 35, 'Luxury Bath', 'Premium shampoo and conditioning bath', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(358, 35, 'Medicated Bath', 'Vet-prescribed medicated shampoo bath', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(359, 35, 'Oatmeal Bath', 'Soothing bath for itchy or sensitive skin', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(360, 35, 'Whitening Shampoo Bath', 'Brightening bath for white-coated pets', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(361, 35, 'Hypoallergenic Bath', 'Gentle bath using allergy-safe products', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(362, 35, 'Flea Bath', 'Medicated flea-killing shampoo bath', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(363, 35, 'Flea & Tick Treatment', 'Medicated bath targeting fleas and ticks', 'grooming', 60, 350.00, 1, '2026-09-03 03:20:22'),
(364, 35, 'Aromatherapy Bath', 'Calming essential oil infused bath', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(365, 35, 'Puppy Cut', 'Short all-over trim, neat and easy to maintain', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(366, 35, 'Teddy Bear Cut', 'Rounded fluffy cut giving a stuffed-toy appearance', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(367, 35, 'Kennel Cut', 'Practical short clip for easy maintenance', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(368, 35, 'Summer Cut', 'Very short trim ideal for hot weather', 'grooming', 75, 450.00, 1, '2026-09-03 03:20:22'),
(369, 35, 'Winter Cut', 'Slightly longer warm coat trim for cold months', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(370, 35, 'Lamb Cut', 'Fluffy rounded body with clean legs', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(371, 35, 'Lion Cut', 'Mane left on head, short body clip', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(372, 35, 'Lion Cut (Cat)', 'Shaved body with mane and pom-pom tail for cats', 'grooming', 90, 700.00, 1, '2026-09-03 03:20:22'),
(373, 35, 'Asian Fusion Cut', 'Creative styled cut popular for small breeds', 'grooming', 120, 700.00, 1, '2026-09-03 03:20:22'),
(374, 35, 'Asian Teddy Cut', 'Soft rounded Asian-style teddy bear cut', 'grooming', 90, 650.00, 1, '2026-09-03 03:20:22'),
(375, 35, 'Show Cut', 'Breed-standard precision cut for show dogs', 'grooming', 150, 1200.00, 1, '2026-09-03 03:20:22'),
(376, 35, 'Breed Standard Cut', 'Grooming per official breed standard', 'grooming', 120, 900.00, 1, '2026-09-03 03:20:22'),
(377, 35, 'Breed Styling', 'Breed-specific styling and grooming', 'grooming', 120, 850.00, 1, '2026-09-03 03:20:22'),
(378, 35, 'Plush Cut', 'Extra fluffy soft finish styling', 'grooming', 90, 600.00, 1, '2026-09-03 03:20:22'),
(379, 35, 'Natural Trim', 'Minimal trim following natural coat shape', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(380, 35, 'Custom Haircut', 'Tailored cut per owner request', 'grooming', 90, 650.00, 1, '2026-09-03 03:20:22'),
(381, 35, 'Creative Grooming', 'Custom artistic coloring and patterns', 'grooming', 180, 1500.00, 1, '2026-09-03 03:20:22'),
(382, 35, 'Pattern Clip', 'Specific pattern clipping for the coat', 'grooming', 90, 650.00, 1, '2026-09-03 03:20:22'),
(383, 35, 'Hand Scissoring', 'Full scissor finish for precise styling', 'grooming', 120, 800.00, 1, '2026-09-03 03:20:22'),
(384, 35, 'Hand Stripping', 'Removal of dead coat by hand', 'grooming', 120, 900.00, 1, '2026-09-03 03:20:22'),
(385, 35, 'Scissor Finish', 'Neat scissor work to finish any style', 'grooming', 60, 380.00, 1, '2026-09-03 03:20:22'),
(386, 35, 'Full Body Trim', 'All-over body trim and tidy', 'grooming', 90, 520.00, 1, '2026-09-03 03:20:22'),
(387, 35, 'Full Body Clip', 'Full short clip all over', 'grooming', 90, 500.00, 1, '2026-09-03 03:20:22'),
(388, 35, 'Short Clip', 'Very short all-over clip', 'grooming', 75, 440.00, 1, '2026-09-03 03:20:22'),
(389, 35, 'Medium Clip', 'Medium length all-over clip', 'grooming', 75, 470.00, 1, '2026-09-03 03:20:22'),
(390, 35, 'Long Coat Trim', 'Trimming and thinning of long coats', 'grooming', 90, 520.00, 1, '2026-09-03 03:20:22'),
(391, 35, 'Maintenance Trim', 'Light trim to maintain current style', 'grooming', 45, 320.00, 1, '2026-09-03 03:20:22'),
(392, 35, 'Round Face Cut', 'Perfectly rounded face trim', 'grooming', 45, 200.00, 1, '2026-09-03 03:20:22'),
(393, 35, 'Clean Face Cut', 'Close trim around the face for a neat look', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(394, 35, 'Clean Feet Cut', 'Neat trim around paws and feet', 'grooming', 30, 120.00, 1, '2026-09-03 03:20:22'),
(395, 35, 'Sanitary Trim', 'Trimming around private areas for hygiene', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(396, 35, 'Hygiene Trim', 'Trimming around sanitary areas', 'grooming', 30, 150.00, 1, '2026-09-03 03:20:22'),
(397, 35, 'Feather Trim', 'Light trimming of feathered coat on legs and tail', 'grooming', 45, 200.00, 1, '2026-09-03 03:20:22'),
(398, 35, 'Belly Trim', 'Trimming of belly fur for hygiene', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(399, 35, 'Tail Trim', 'Shaping and trimming of the tail', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(400, 35, 'Ear Trim', 'Shaping and tidying the ear fur', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(401, 35, 'Face Trim', 'General facial fur tidying', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(402, 35, 'Paw Pad Trim', 'Trimming fur between paw pads', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(403, 35, 'Rounded Legs Cut', 'Rounded styling of the leg fur', 'grooming', 45, 250.00, 1, '2026-09-03 03:20:22'),
(404, 35, 'Fluffy Finish', 'Extra fluffing and volumizing finish', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(405, 35, 'Nail Cutting', 'Safe trimming of pet nails', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(406, 35, 'Nail Trimming', 'Trimming nails to safe length', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(407, 35, 'Nail Grinding', 'Smooth filing of nails using rotary tool', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(408, 35, 'Nail Filing', 'Smooth filing of nails after cutting', 'grooming', 15, 80.00, 1, '2026-09-03 03:20:22'),
(409, 35, 'Paw Pad Shaving', 'Shaving excess fur around paw pads', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(410, 35, 'Paw Moisturizing', 'Moisturizer applied to paws and pads', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(411, 35, 'Paw Balm Treatment', 'Moisturizing treatment for dry cracked pads', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(412, 35, 'Paw Cleaning', 'Thorough cleaning of all four paws', 'grooming', 15, 80.00, 1, '2026-09-03 03:20:22'),
(413, 35, 'Paw Fur Trim', 'Trim fur growing between toes', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(414, 35, 'Paw Massage', 'Relaxing massage of paw muscles', 'grooming', 15, 120.00, 1, '2026-09-03 03:20:22'),
(415, 35, 'Ear Cleaning', 'Gentle removal of debris and wax from ears', 'grooming', 20, 100.00, 1, '2026-09-03 03:20:22'),
(416, 35, 'Ear Hair Plucking', 'Removal of hair inside the ear canal', 'grooming', 20, 120.00, 1, '2026-09-03 03:20:22'),
(417, 35, 'Ear Inspection', 'Visual check of ears for issues', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(418, 35, 'Eye Cleaning', 'Gentle cleaning around the eyes', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(419, 35, 'Eye Trim', 'Trimming fur around eyes for visibility', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(420, 35, 'Tear Stain Removal', 'Gentle cleaning of tear stains', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(421, 35, 'Teeth Brushing', 'Brushing teeth to prevent tartar build-up', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(422, 35, 'Dental Freshening', 'Dental gel or spray for fresh breath', 'grooming', 10, 100.00, 1, '2026-09-03 03:20:22'),
(423, 35, 'Breath Freshening', 'Oral spray to freshen pet breath', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(424, 35, 'Oral Care', 'Full oral hygiene routine', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(425, 35, 'Anal Gland Expression', 'Gentle manual expression of anal glands', 'grooming', 15, 150.00, 1, '2026-09-03 03:20:22'),
(426, 35, 'De-shedding Treatment', 'Deep treatment to reduce heavy shedding', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(427, 35, 'Undercoat Removal', 'Thorough removal of excess undercoat', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(428, 35, 'Coat Brushing', 'Thorough brushing of the full coat', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(429, 35, 'Brush Out', 'Thorough brushing and detangling', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(430, 35, 'Dematting', 'Careful removal of mats and tangles', 'grooming', 60, 500.00, 1, '2026-09-03 03:20:22'),
(431, 35, 'Coat Conditioning', 'Deep moisturizing treatment for coat health', 'grooming', 45, 300.00, 1, '2026-09-03 03:20:22'),
(432, 35, 'Coat Moisturizing', 'Moisturizing spray and treatment', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(433, 35, 'Coat Rejuvenation', 'Intensive repair treatment for damaged coat', 'grooming', 60, 520.00, 1, '2026-09-03 03:20:22'),
(434, 35, 'Coat Shine Treatment', 'Finishing treatment for glossy coat', 'grooming', 30, 260.00, 1, '2026-09-03 03:20:22'),
(435, 35, 'Coat Polish', 'Finishing polish for coat shine', 'grooming', 20, 160.00, 1, '2026-09-03 03:20:22'),
(436, 35, 'Coat Fluffing', 'Fluffing and volumizing finish', 'grooming', 20, 160.00, 1, '2026-09-03 03:20:22'),
(437, 35, 'Blow Dry', 'Standard blow dry after bath', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(438, 35, 'High Velocity Dry', 'Professional high-powered drying', 'grooming', 30, 200.00, 1, '2026-09-03 03:20:22'),
(439, 35, 'Perfume Application', 'Light pet-safe fragrance spray after grooming', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(440, 35, 'Cologne Spray', 'Pet-safe cologne application', 'grooming', 10, 80.00, 1, '2026-09-03 03:20:22'),
(441, 35, 'Hot Oil Treatment', 'Deep nourishing oil treatment for dry coat', 'grooming', 45, 400.00, 1, '2026-09-03 03:20:22'),
(442, 35, 'Skin Moisturizing', 'Moisturizing lotion for dry skin', 'grooming', 20, 200.00, 1, '2026-09-03 03:20:22'),
(443, 35, 'Sensitive Skin Care', 'Hypoallergenic products for reactive skin', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(444, 35, 'Anti-Itch Treatment', 'Medicated bath for itchy skin relief', 'grooming', 60, 450.00, 1, '2026-09-03 03:20:22'),
(445, 35, 'Anti-Dandruff Treatment', 'Specialized dandruff control bath', 'grooming', 60, 420.00, 1, '2026-09-03 03:20:22'),
(446, 35, 'Dry Skin Treatment', 'Hydrating treatment for dry skin', 'grooming', 45, 400.00, 1, '2026-09-03 03:20:22'),
(447, 35, 'Allergy-Friendly Grooming', 'All products hypoallergenic', 'grooming', 60, 470.00, 1, '2026-09-03 03:20:22'),
(448, 35, 'Skin Inspection', 'Visual skin health check during grooming', 'grooming', 15, 100.00, 1, '2026-09-03 03:20:22'),
(449, 35, 'Blueberry Facial', 'Whitening and brightening facial scrub', 'grooming', 30, 250.00, 1, '2026-09-03 03:20:22'),
(450, 35, 'Mud Treatment', 'Detoxifying mud mask for coat and skin', 'grooming', 60, 600.00, 1, '2026-09-03 03:20:22'),
(451, 35, 'Deodorizing Treatment', 'Odor-neutralizing bath and spray', 'grooming', 45, 350.00, 1, '2026-09-03 03:20:22'),
(452, 35, 'Finishing Touch Styling', 'Final styling touches after grooming', 'grooming', 20, 150.00, 1, '2026-09-03 03:20:22'),
(453, 35, 'Puppy Grooming', 'Introductory gentle groom for young puppies', 'grooming', 60, 400.00, 1, '2026-09-03 03:20:22'),
(454, 35, 'Senior Dog Grooming', 'Gentle grooming adapted for older dogs', 'grooming', 90, 550.00, 1, '2026-09-03 03:20:22'),
(455, 35, 'Large Breed Grooming', 'Full grooming service for large breed dogs', 'grooming', 120, 720.00, 1, '2026-09-03 03:20:22'),
(456, 35, 'Small Breed Grooming', 'Full grooming service for small breed dogs', 'grooming', 90, 520.00, 1, '2026-09-03 03:20:22'),
(457, 35, 'Giant Breed Grooming', 'Grooming for giant breed dogs', 'grooming', 150, 950.00, 1, '2026-09-03 03:20:22'),
(458, 35, 'Nervous Dog Grooming', 'Patient gentle grooming for anxious dogs', 'grooming', 120, 720.00, 1, '2026-09-03 03:20:22'),
(459, 35, 'Aggressive Dog Grooming', 'Safe grooming for difficult dogs', 'grooming', 120, 720.00, 1, '2026-09-03 03:20:22'),
(460, 35, 'First-Time Grooming', 'Introductory grooming session', 'grooming', 60, 420.00, 1, '2026-09-03 03:20:22'),
(461, 35, 'Express Grooming', 'Priority fast-track grooming service', 'grooming', 90, 700.00, 1, '2026-09-03 03:20:22'),
(462, 35, 'Spa Grooming', 'Full spa treatment grooming package', 'grooming', 180, 1300.00, 1, '2026-09-03 03:20:22'),
(463, 35, 'Basic Grooming Package', 'Bath, nails, ears only', 'grooming', 60, 420.00, 1, '2026-09-03 03:20:22'),
(464, 35, 'Full Grooming Package', 'Bath, haircut, nails, ears, and teeth', 'grooming', 120, 850.00, 1, '2026-09-03 03:20:22'),
(465, 35, 'Premium Grooming Package', 'Full groom + coat treatment + teeth + nails', 'grooming', 150, 1050.00, 1, '2026-09-03 03:20:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT 'Pangasinan',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `id_verified` tinyint(1) NOT NULL DEFAULT 0,
  `id_document_type` varchar(50) DEFAULT NULL,
  `id_document_number` varchar(100) DEFAULT NULL,
  `id_document_path` varchar(255) DEFAULT NULL,
  `id_document_path_back` varchar(255) DEFAULT NULL,
  `id_verified_at` datetime DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `profile_photo`, `address`, `city`, `province`, `is_active`, `is_email_verified`, `id_verified`, `id_document_type`, `id_document_number`, `id_document_path`, `id_document_path_back`, `id_verified_at`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `failed_login_attempts`, `locked_until`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 5, 'Super', 'Admin', 'superadmin@petcareplus.com', '+63-900-000-0000', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, NULL, 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-09-12 21:44:31', '2026-06-20 12:03:43', '2026-09-12 13:44:31'),
(2, 4, 'Clinic', 'Admin', 'clinicadmin@petcareplus.com', '+63-900-000-0001', '$2y$10$UEsIJ0bD8Io8.zu95wWtReKM7BYDlTtl3Uxk07eaN/SuROgenmS4q', NULL, NULL, NULL, 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-09-12 20:25:44', '2026-06-20 12:03:43', '2026-09-12 12:25:44'),
(4, 2, 'Jose', 'Reyes', 'vet@petcareplus.com', '+63-900-000-0003', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, NULL, 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(5, 3, 'Ana', 'Cruz', 'groomer@petcareplus.com', '+63-900-000-0004', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, NULL, 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(10, 2, 'Miguel', 'Aguilar', 'vet2@petcareplus.com', '+63-900-000-0010', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(11, 2, 'Maria', 'Dela Cruz', 'vet3@petcareplus.com', '+63-900-000-0011', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(12, 2, 'Carlos', 'Santos', 'vet4@petcareplus.com', '+63-900-000-0012', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(13, 2, 'Liza', 'Mendoza', 'vet5@petcareplus.com', '+63-900-000-0013', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(14, 2, 'Ramon', 'Garcia', 'vet6@petcareplus.com', '+63-900-000-0014', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(15, 2, 'Elena', 'Bautista', 'vet7@petcareplus.com', '+63-900-000-0015', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(16, 2, 'Mario', 'Torres', 'vet8@petcareplus.com', '+63-900-000-0016', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Calasiao', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(17, 2, 'Rosa', 'Villanueva', 'vet9@petcareplus.com', '+63-900-000-0017', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Calasiao', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(18, 2, 'Pedro', 'Aquino', 'vet10@petcareplus.com', '+63-900-000-0018', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Binmaley', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(19, 2, 'Nora', 'Castillo', 'vet11@petcareplus.com', '+63-900-000-0019', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Mangaldan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(20, 2, 'Felix', 'Ramos', 'vet12@petcareplus.com', '+63-900-000-0020', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Mangaldan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(21, 2, 'Gloria', 'Navarro', 'vet13@petcareplus.com', '+63-900-000-0021', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'San Carlos', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(22, 2, 'Andres', 'Pascual', 'vet14@petcareplus.com', '+63-900-000-0022', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'San Carlos', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(23, 2, 'Celine', 'Dizon', 'vet15@petcareplus.com', '+63-900-000-0023', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'San Carlos', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(24, 2, 'Marco', 'Ignacio', 'vet16@petcareplus.com', '+63-900-000-0024', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Lingayen', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(25, 3, 'Bianca', 'Flores', 'groomer2@petcareplus.com', '+63-900-000-0025', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(26, 3, 'Jasmine', 'Ortega', 'groomer3@petcareplus.com', '+63-900-000-0026', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(27, 3, 'Kevin', 'Lim', 'groomer4@petcareplus.com', '+63-900-000-0027', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Mangaldan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(28, 3, 'Trisha', 'Rebollos', 'groomer5@petcareplus.com', '+63-900-000-0028', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Calasiao', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(29, 3, 'Dante', 'Soriano', 'groomer6@petcareplus.com', '+63-900-000-0029', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'San Carlos', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(30, 3, 'Renz', 'Reyes', 'groomer7@petcareplus.com', '+63-900-000-0030', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', NULL, NULL, 'Dagupan', 'Pangasinan', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-06-20 12:03:43', '2026-06-20 12:03:43'),
(100, 1, 'Mj', 'Pastoral', 'kumirchi1@gmail.com', '09684180346', '$2y$12$8ItqSLnVHJYqbJE8v5PXQeqVElR4Jw2GilE5S2iSvqei7o8uJhOm6', NULL, NULL, NULL, 'Pangasinan', 1, 1, 1, 'passport', '4859-2719-3207-6945', 'http://127.0.0.1:8000/uploads/id_docs/id_front_1f3ea0e24d8fd7b0.jpg', 'http://127.0.0.1:8000/uploads/id_docs/id_back_1f3ea0e24d8fd7b0.jpg', NULL, NULL, NULL, NULL, 0, NULL, '2026-09-12 20:26:37', '2026-09-12 12:24:46', '2026-09-12 12:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `veterinarians`
--

DROP TABLE IF EXISTS `veterinarians`;
CREATE TABLE `veterinarians` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `years_experience` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `veterinarians`
--

INSERT INTO `veterinarians` (`id`, `user_id`, `clinic_id`, `license_number`, `specialization`, `bio`, `years_experience`, `photo`, `is_available`, `created_at`, `updated_at`) VALUES
(19, 4, 26, 'PRC-VET-2019-0026', 'General Practice', NULL, 7, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(20, 10, 27, 'PRC-VET-2020-0027', 'Internal Medicine', NULL, 6, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(21, 11, 28, 'PRC-VET-2018-0028', 'Surgery', NULL, 8, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(22, 12, 29, 'PRC-VET-2021-0029', 'Dermatology', NULL, 5, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(23, 13, 30, 'PRC-VET-2022-0030', 'General Practice', NULL, 4, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(24, 14, 31, 'PRC-VET-2020-0031', 'General Practice', NULL, 6, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(25, 15, 32, 'PRC-VET-2017-0032', 'Radiology & Imaging', NULL, 9, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(26, 16, 33, 'PRC-VET-2023-0033', 'General Practice', NULL, 3, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23'),
(28, 18, 37, 'PRC-VET-2022-0037', 'General Practice', NULL, 4, NULL, 1, '2026-09-03 03:20:23', '2026-09-03 03:20:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_airec_pet` (`pet_id`),
  ADD KEY `fk_airec_user` (`generated_by`);

--
-- Indexes for table `ai_summaries`
--
ALTER TABLE `ai_summaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aisum_pet` (`pet_id`),
  ADD KEY `fk_aisum_user` (`generated_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appt_owner` (`owner_id`),
  ADD KEY `idx_appt_pet` (`pet_id`),
  ADD KEY `idx_appt_clinic` (`clinic_id`),
  ADD KEY `idx_appt_date` (`appointment_date`),
  ADD KEY `idx_appt_status` (`status`),
  ADD KEY `fk_appt_service` (`service_id`),
  ADD KEY `fk_appt_vet` (`vet_id`),
  ADD KEY `fk_appt_groomer` (`groomer_id`);

--
-- Indexes for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slots_clinic` (`clinic_id`);



--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinics_admin` (`admin_id`),
  ADD KEY `idx_clinics_city` (`city`);


--
-- Indexes for table `favorite_clinics`
--
ALTER TABLE `favorite_clinics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fav` (`user_id`,`clinic_id`),
  ADD KEY `fk_fav_clinic` (`clinic_id`);

--
-- Indexes for table `groomers`
--
ALTER TABLE `groomers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_groomer_user` (`user_id`),
  ADD KEY `idx_groomer_clinic` (`clinic_id`);




--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pets_owner` (`owner_id`);

--
-- Indexes for table `pet_health_records`
--
ALTER TABLE `pet_health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phr_pet` (`pet_id`),
  ADD KEY `idx_phr_vet` (`vet_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rating_appt` (`appointment_id`),
  ADD KEY `idx_rat_owner` (`owner_id`),
  ADD KEY `idx_rat_clinic` (`clinic_id`),
  ADD KEY `fk_rat_vet` (`vet_id`),
  ADD KEY `fk_rat_groomer` (`groomer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_name` (`name`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_clinic` (`clinic_id`);


--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`);

--
-- Indexes for table `veterinarians`
--
ALTER TABLE `veterinarians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vet_user` (`user_id`),
  ADD KEY `idx_vet_clinic` (`clinic_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_summaries`
--
ALTER TABLE `ai_summaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
--

--
-- AUTO_INCREMENT for table `favorite_clinics`
--
ALTER TABLE `favorite_clinics`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groomers`
--
ALTER TABLE `groomers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
--

--
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pet_health_records`
--
ALTER TABLE `pet_health_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=466;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `veterinarians`
--
ALTER TABLE `veterinarians`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `fk_airec_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_airec_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ai_summaries`
--
ALTER TABLE `ai_summaries`
  ADD CONSTRAINT `fk_aisum_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aisum_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appt_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`),
  ADD CONSTRAINT `fk_appt_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appt_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_appt_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`),
  ADD CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appt_vet` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD CONSTRAINT `fk_slots_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `fk_clinics_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `favorite_clinics`
--
ALTER TABLE `favorite_clinics`
  ADD CONSTRAINT `fk_fav_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groomers`
--
ALTER TABLE `groomers`
  ADD CONSTRAINT `fk_groomer_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_groomer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `fk_pets_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pet_health_records`
--
ALTER TABLE `pet_health_records`
  ADD CONSTRAINT `fk_phr_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_phr_vet` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_rat_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rat_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`),
  ADD CONSTRAINT `fk_rat_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rat_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_rat_vet` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `veterinarians`
--
ALTER TABLE `veterinarians`
  ADD CONSTRAINT `fk_vet_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- PART 2: SCHEMA MIGRATIONS
-- Apply these statements only to a fresh database as part of this setup.
-- Production URL conversion is kept in restore_production_urls.sql.
--


-- ============================================================
-- TERMS & PRIVACY POLICY MIGRATION
-- Added: September 21, 2026
-- Description: Adds terms_accepted and terms_accepted_at columns
--              to the users table to record when each user agreed
--              to the Terms of Service and Privacy Policy at
--              registration time.
-- ============================================================

-- Add terms_accepted (1 = agreed, 0 = not yet agreed)
-- Default 0 so existing rows are not affected
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS terms_accepted    TINYINT(1)  NOT NULL DEFAULT 0
                                               COMMENT '1 = user agreed to ToS & Privacy Policy at registration',
    ADD COLUMN IF NOT EXISTS terms_accepted_at DATETIME    NULL     DEFAULT NULL
                                               COMMENT 'UTC timestamp when the user accepted the terms';

-- Do not back-fill acceptance from email verification or account creation.
-- Neither is evidence that a user accepted the terms. Existing users remain
-- terms_accepted = 0 until the application records their actual acceptance.

-- Verify the columns were added
SELECT 'VERIFICATION - terms columns:' AS status;
DESCRIBE users;

-- Verify the terms fields
SELECT 'VERIFICATION - users with terms accepted:' AS status;
SELECT
    COUNT(*)                                       AS total_users,
    SUM(terms_accepted = 1)                        AS accepted,
    SUM(terms_accepted = 0)                        AS pending,
    MIN(terms_accepted_at)                         AS earliest_acceptance,
    MAX(terms_accepted_at)                         AS latest_acceptance
FROM users;

SELECT 'DONE! terms_accepted and terms_accepted_at columns ready.' AS status;


-- ============================================================
-- INACTIVE USER + NO-SHOW SYSTEM MIGRATION
-- Added: September 21, 2026
-- Description:
--   1. Adds inactivity tracking columns to users table
--   2. Adds no-show tracking columns to users table
--   3. Adds 'no_show' to appointments status enum
--   4. Adds no_show_count column to users for per-user tracking
-- ============================================================

-- â”€â”€ 1. Users table: inactivity tracking â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS inactivity_warned_at      DATETIME NULL DEFAULT NULL
        COMMENT 'When the 30-day inactivity warning email was last sent',
    ADD COLUMN IF NOT EXISTS inactivity_deactivated_at DATETIME NULL DEFAULT NULL
        COMMENT 'When the account was auto-deactivated for inactivity (90 days)',
    ADD COLUMN IF NOT EXISTS no_show_count             TINYINT(3) UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Total number of no-show appointments for this user',
    ADD COLUMN IF NOT EXISTS is_booking_restricted     TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = user is temporarily restricted from making new bookings due to no-shows';

-- â”€â”€ 2. Appointments table: add no_show status â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
-- Extend the existing status enum to include no_show
ALTER TABLE appointments
    MODIFY COLUMN status ENUM(
        'pending','approved','rejected','completed',
        'cancelled','rescheduled','no_show'
    ) NOT NULL DEFAULT 'pending';

-- â”€â”€ 3. Indexes for cron job performance â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
-- Speed up inactivity queries (scan by last_login + is_active)
CREATE INDEX IF NOT EXISTS idx_users_last_login_active
    ON users (last_login, is_active);

-- Speed up no-show appointment queries
CREATE INDEX IF NOT EXISTS idx_appt_status_date
    ON appointments (status, appointment_date);

-- â”€â”€ Verification â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
SELECT 'VERIFICATION - users new columns:' AS status;
SELECT
    COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'users'
  AND COLUMN_NAME IN ('inactivity_warned_at','inactivity_deactivated_at','no_show_count','is_booking_restricted');

SELECT 'VERIFICATION - appointments status enum:' AS status;
SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'appointments'
  AND COLUMN_NAME  = 'status';

SELECT 'DONE! Inactive user + no-show migration complete.' AS status;


-- ============================================================
-- CONTACT MESSAGES SYSTEM MIGRATION
-- Added: September 21, 2026
-- Description: Creates contact_messages and contact_replies
--              tables for the web-to-web messaging system.
-- ============================================================

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'NULL if sent by guest (not logged in)',
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(191) NOT NULL,
  `phone`      VARCHAR(30)  DEFAULT NULL,
  `subject`    VARCHAR(255) NOT NULL,
  `message`    TEXT NOT NULL,
  `status`     ENUM('unread','read','replied','closed') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email`   (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_replies` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT(10) UNSIGNED NOT NULL COMMENT 'FK to contact_messages.id',
  `admin_id`   INT(10) UNSIGNED NOT NULL COMMENT 'FK to users.id (admin who replied)',
  `reply`      TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'DONE! contact_messages and contact_replies tables created.' AS status;



-- ============================================================
-- SYMPTOM PHOTO UPLOAD MIGRATION
-- Added: September 21, 2026
-- Description: Adds symptom_photo column to appointments table
-- ============================================================

ALTER TABLE appointments
    ADD COLUMN IF NOT EXISTS symptom_photo VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Path to uploaded symptom/condition photo by pet owner';

SELECT 'DONE! symptom_photo column added to appointments.' AS status;
