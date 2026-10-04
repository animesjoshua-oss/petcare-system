-- ============================================================
-- PETCARE+ CLINIC HUB - COMPLETE DATABASE SETUP
-- ============================================================
-- This is a comprehensive merged SQL file containing:
-- 1. Full database schema (tables, indexes, constraints)
-- 2. Initial data (roles, demo clinics)
-- 3. Services seed data
-- 4. Veterinarians seed data
-- 5. Demo user data
-- 6. Medical record types update
-- 7. Clinic images update
--
-- USAGE:
-- For Production (yzz.me): Database already exists, just import this file
-- For Local Development:
--   1. Create database: CREATE DATABASE petcare_clinic_hub;
--   2. Import this file in phpMyAdmin or run: mysql -u root -p petcare_clinic_hub < COMPLETE_DATABASE_SETUP.sql
--   3. Update config.php with your database credentials
--
-- Last Updated: October 2026
-- ============================================================

-- ============================================================
-- DATABASE SELECTION
-- ============================================================
-- PRODUCTION: This will use the yzzme_43078279 database
USE `yzzme_43078279`;

-- LOCAL DEVELOPMENT: Comment the line above and uncomment these:
-- CREATE DATABASE IF NOT EXISTS `petcare_clinic_hub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `petcare_clinic_hub`;

-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- SECTION 1: DROP EXISTING TABLES (CLEAN START)
-- ============================================================

DROP TABLE IF EXISTS `ai_recommendations`;
DROP TABLE IF EXISTS `ai_summaries`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `appointment_slots`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `clinics`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `favorite_clinics`;
DROP TABLE IF EXISTS `groomers`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `pets`;
DROP TABLE IF EXISTS `pet_health_records`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `veterinarians`;

-- ============================================================
-- SECTION 2: CREATE TABLES
-- ============================================================

-- Roles Table
CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `id_verified` tinyint(1) NOT NULL DEFAULT 0,
  `id_document_path` varchar(255) DEFAULT NULL,
  `id_document_status` enum('pending','approved','rejected') DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `email_verification_token` (`email_verification_token`),
  KEY `password_reset_token` (`password_reset_token`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clinics Table
CREATE TABLE `clinics` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `city` (`city`),
  CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Veterinarians Table
CREATE TABLE `veterinarians` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `years_experience` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `clinic_id` (`clinic_id`),
  CONSTRAINT `veterinarians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veterinarians_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groomers Table
CREATE TABLE `groomers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `years_experience` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `clinic_id` (`clinic_id`),
  CONSTRAINT `groomers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `groomers_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pets Table
CREATE TABLE `pets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services Table
CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `service_type` enum('veterinary','grooming','both') NOT NULL DEFAULT 'veterinary',
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `service_type` (`service_type`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments Table
CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `pet_id` (`pet_id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `service_id` (`service_id`),
  KEY `vet_id` (`vet_id`),
  KEY `groomer_id` (`groomer_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `status` (`status`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_ibfk_5` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_ibfk_6` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pet Health Records Table
CREATE TABLE `pet_health_records` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `vet_id` (`vet_id`),
  CONSTRAINT `pet_health_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pet_health_records_ibfk_2` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ratings Table
CREATE TABLE `ratings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  KEY `owner_id` (`owner_id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `vet_id` (`vet_id`),
  KEY `groomer_id` (`groomer_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_4` FOREIGN KEY (`vet_id`) REFERENCES `veterinarians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ratings_ibfk_5` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorite Clinics Table
CREATE TABLE `favorite_clinics` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_clinic_unique` (`user_id`,`clinic_id`),
  KEY `clinic_id` (`clinic_id`),
  CONSTRAINT `favorite_clinics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorite_clinics_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Recommendations Table
CREATE TABLE `ai_recommendations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `recommendation_type` enum('veterinary','grooming','general') NOT NULL DEFAULT 'general',
  `prompt_used` text DEFAULT NULL,
  `recommendation` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Summaries Table
CREATE TABLE `ai_summaries` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `summary` text NOT NULL,
  `prompt_used` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `ai_summaries_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_summaries_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointment Slots Table
CREATE TABLE `appointment_slots` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clinic_id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(10) UNSIGNED DEFAULT NULL,
  `provider_type` enum('veterinarian','groomer') DEFAULT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `clinic_id` (`clinic_id`),
  KEY `slot_date` (`slot_date`),
  CONSTRAINT `appointment_slots_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache Tables
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs Tables
CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrations Table
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION 3: INSERT INITIAL DATA
-- ============================================================

-- Insert Roles
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'pet_owner', 'Registered pet owners'),
(2, 'veterinarian', 'Licensed veterinarians'),
(3, 'groomer', 'Professional pet groomers'),
(4, 'admin', 'Clinic administrators'),
(5, 'superadmin', 'System super-administrator');

-- Insert Migrations
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_09_12_133304_create_sessions_table', 2);

-- Insert Demo Admin User (admin@petcareplus.com / petcare123)
INSERT INTO `users` (`id`, `role_id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `is_active`, `is_email_verified`, `id_verified`) VALUES
(2, 4, 'PetCare', 'Admin', 'admin@petcareplus.com', '+63-900-000-0001', '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', 1, 1, 1);

-- Insert Demo Clinics in Pangasinan
INSERT INTO `clinics` (`id`, `admin_id`, `name`, `description`, `address`, `city`, `province`, `phone`, `latitude`, `longitude`, `operating_hours`, `is_active`) VALUES
(26, 2, 'PetAlly Veterinary Clinic', 'Full-service veterinary clinic located along Perez Boulevard, Dagupan City.', 'Perez Blvd, Dagupan City', 'Dagupan', 'Pangasinan', '+63 981 246 7042', 16.04362000, 120.33412000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1),
(27, 2, 'Animal Life Clinic and Supply', 'Veterinary clinic and pet supply store serving Dagupan City and nearby areas.', 'Amado St, Tapuac, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 523-1327', 16.04280000, 120.33180000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(28, 2, 'Pet Protect Veterinary Clinic', 'Dedicated veterinary care along MacArthur Highway, Tapuac District.', 'McArthur Hwy, Tapuac, Dagupan City', 'Dagupan', 'Pangasinan', '+63 991 444 4677', 16.04150000, 120.33050000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(29, 2, 'St. Francis Veterinary Clinic', 'Trusted veterinary clinic on Burgos Street in the heart of Dagupan City.', 'Burgos St, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 522-0219', 16.04420000, 120.33530000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1),
(30, 2, 'Saint Francis Veterinary Services', 'Veterinary services and consultations for all pet types in Dagupan.', 'Amado St, Dagupan City', 'Dagupan', 'Pangasinan', '(075) 515-6675', 16.04310000, 120.33220000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(31, 2, 'MJM Veterinary Clinic', 'Veterinary clinic and pet supply store in Lucao District, Dagupan.', 'Lucao, Dagupan City', 'Dagupan', 'Pangasinan', '+63 969 197 1305', 16.05100000, 120.34200000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(32, 2, 'PetDoctors Animal Clinic - Dagupan', 'Modern animal clinic in Bonuan Gueset, offering comprehensive vet services.', 'Bonuan Gueset, Dagupan City', 'Dagupan', 'Pangasinan', NULL, 16.02900000, 120.33800000, '{\"monday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"7:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"4:00 PM\"},\"sunday\":{\"open\":\"9:00 AM\",\"close\":\"1:00 PM\"}}', 1),
(33, 2, 'St. Martin Animal Clinic', 'Reliable animal clinic along Arellano Street, Dagupan City.', 'Arellano St, Dagupan City', 'Dagupan', 'Pangasinan', NULL, 16.04500000, 120.33600000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(34, 2, 'Paws & Purrs Pet Station', 'Premium pet grooming and boarding facility in Tapuac District.', 'Tapuac District, Dagupan City', 'Dagupan', 'Pangasinan', '+63 961 887 0903', 16.04200000, 120.33100000, '{\"monday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"9:00 AM\",\"close\":\"5:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(35, 2, 'RH Elegance Pet Grooming', 'Professional pet grooming services in Caranglaan, Dagupan City.', 'Caranglaan, Dagupan City', 'Dagupan', 'Pangasinan', '+63 991 341 5497', 16.03700000, 120.32900000, '{\"monday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"tuesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"wednesday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"thursday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"friday\":{\"open\":\"9:00 AM\",\"close\":\"6:00 PM\"},\"saturday\":{\"open\":\"9:00 AM\",\"close\":\"5:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(37, 2, 'Nose To Tail Veterinary Clinic and Grooming Center', 'Complete vet and grooming services for all pets in Calasiao.', 'Calasiao, Pangasinan', 'Calasiao', 'Pangasinan', NULL, 16.01000000, 120.36600000, '{\"monday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"tuesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"wednesday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"thursday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"friday\":{\"open\":\"8:00 AM\",\"close\":\"5:00 PM\"},\"saturday\":{\"open\":\"8:00 AM\",\"close\":\"3:00 PM\"},\"sunday\":{\"closed\":true}}', 1),
(46, 2, 'Every Paw Veterinary Clinic', 'Open Daily except Wednesdays: 9AM - 6PM. Located along Caranglaan Dagupan-Calasiao Rd, Brgy. San Miguel, Calasiao, Pangasinan (Beside Datuin Motorworks).', 'Along Caranglaan Dagupan-Calasiao Rd, Brgy. San Miguel, Calasiao, Pangasinan (Beside Datuin Motorworks)', 'Calasiao', 'Pangasinan', NULL, 16.00810000, 120.41670000, NULL, 1),
(47, 2, 'D&R Dog and Cat Clinic', 'Veterinary and Grooming clinic. Located meters away from Panaderia Antonio just beside Bumanlag.', 'De Venecia Highway, Banaoang, Calasiao, Pangasinan', 'Calasiao', 'Pangasinan', '09226576611', 16.00810000, 120.41670000, NULL, 1);

-- ============================================================
-- SECTION 4: SEED DEMO DATA (Demo Owner & Pet)
-- ============================================================

-- Insert Demo Owner (owner.demo@petcareplus.com / petcare123)
INSERT INTO `users` (`role_id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `is_active`, `is_email_verified`)
SELECT r.id, 'Jamie', 'Santos', 'owner.demo@petcareplus.com', '+63-917-555-0142',
       '$2y$10$IzwZn1MRDP1.Aeonm09zLOEHgtgFSp/fY6DM8THkLmZye26VyBsF.', 1, 1
FROM roles r
WHERE r.name = 'pet_owner'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'owner.demo@petcareplus.com');

-- Insert Demo Pet
INSERT INTO `pets` (`owner_id`, `name`, `species`, `breed`, `age_years`, `age_months`, `weight_kg`, `gender`, `color`, `notes`, `is_active`)
SELECT u.id, 'Milo', 'dog', 'Shih Tzu', 3, 0, 6.20, 'male', 'White and brown',
       'Demo pet profile for appointment and health record walkthrough.', 1
FROM users u
WHERE u.email = 'owner.demo@petcareplus.com'
  AND NOT EXISTS (SELECT 1 FROM pets p WHERE p.owner_id = u.id AND p.name = 'Milo');

-- ============================================================
-- DATABASE SETUP COMPLETE!
-- ============================================================
-- Next Steps:
-- 1. Run seed_services.sql for services (optional)
-- 2. Run seed_vets.sql for veterinarian accounts (optional)
-- 3. Update clinic images with update_clinic_images.sql (optional)
-- 4. Configure your includes/config.php with database credentials
-- 5. Login with demo accounts:
--    - Admin: admin@petcareplus.com / petcare123
--    - Pet Owner: owner.demo@petcareplus.com / petcare123
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
