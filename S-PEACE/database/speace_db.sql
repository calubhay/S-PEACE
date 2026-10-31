-- ====================================================================
-- S-PEACE: Web-Based Student Complaint and Concern Management System
-- Polytechnic College of Botolan (PCB)
-- Database Name: speace_db
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `speace_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `speace_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if re-importing
DROP TABLE IF EXISTS `complaint_upvotes`;
DROP TABLE IF EXISTS `complaint_logs`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin', 'staff') NOT NULL DEFAULT 'student',
  `student_id_number` VARCHAR(50) NULL,
  `department` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL,
  `display_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff
-- --------------------------------------------------------
CREATE TABLE `staff` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `specialization` VARCHAR(100) NOT NULL,
  `contact_number` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `status` ENUM('available', 'on_task', 'on_leave') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: complaints
-- --------------------------------------------------------
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tracking_code` VARCHAR(30) NOT NULL UNIQUE,
  `student_id` INT NULL,
  `student_name` VARCHAR(100) NULL,
  `student_email` VARCHAR(150) NULL,
  `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  `category_id` INT NOT NULL,
  `problem` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `photo_url` VARCHAR(255) NULL,
  `status` ENUM('Submitted', 'In progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Submitted',
  `urgency` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
  `upvotes` INT NOT NULL DEFAULT 0,
  `assigned_staff_id` INT NULL,
  `admin_notes` TEXT NULL,
  `resolution_notes` TEXT NULL,
  `resolution_photo_url` VARCHAR(255) NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `in_progress_at` DATETIME NULL,
  `resolved_at` DATETIME NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaints_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_complaints_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_complaints_user` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: complaint_logs
-- --------------------------------------------------------
CREATE TABLE `complaint_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `performed_by` VARCHAR(100) NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: complaint_upvotes
-- --------------------------------------------------------
CREATE TABLE `complaint_upvotes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `client_identifier` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_complaint_client` (`complaint_id`, `client_identifier`),
  CONSTRAINT `fk_upvotes_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- 1. Default Categories matching Frame 2 wireframe
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `display_order`) VALUES
(1, 'Comfort rooms', 'comfort-rooms', 'droplet', 'Restrooms, water faucets, flush toilets, and sanitation fixtures', 1),
(2, 'Facilities', 'facilities', 'building', 'Campus buildings, roofs, walkways, staircases, doors and security gates', 2),
(3, 'Classrooms', 'classrooms', 'book-open', 'Student armchairs, teacher tables, whiteboards, lighting, and windows', 3),
(4, 'Equipment', 'equipment', 'monitor', 'Ceiling fans, air conditioning units, LCD projectors, and lab computers', 4),
(5, 'Cleanliness', 'cleanliness', 'trash-2', 'Waste bins, garbage segregation, corridor tidiness, and campus grounds', 5),
(6, 'Safety', 'safety', 'shield-alert', 'Electrical outlets, exposed wirings, slippery ramps, and fire safety', 6),
(7, 'Other', 'other', 'more-horizontal', 'General campus maintenance issues requiring administration review', 7);

-- 2. Default Campus Maintenance Staff
INSERT INTO `staff` (`id`, `full_name`, `specialization`, `contact_number`, `email`, `status`) VALUES
(1, 'Juan Dela Cruz', 'Plumbing & Water Systems', '0917-123-4567', 'jdelacruz@pcb.edu.ph', 'available'),
(2, 'Mark Angelo Santos', 'Electrical & Power Fixtures', '0918-234-5678', 'msantos@pcb.edu.ph', 'on_task'),
(3, 'Pedro Reyes', 'Carpentry & Structural Repairs', '0920-345-6789', 'preyes@pcb.edu.ph', 'available'),
(4, 'Maria Bautista', 'Campus Sanitation & Janitorial Lead', '0922-456-7890', 'mbautista@pcb.edu.ph', 'available'),
(5, 'Robert Garcia', 'Cooling, HVAC & Technical Equipment', '0925-567-8901', 'rgarcia@pcb.edu.ph', 'available');

-- 3. Default Users (Admin & Students)
-- Password for admin: admin123 (hashed with password_hash BCRYPT)
-- Password for student: student123
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `student_id_number`, `department`) VALUES
(1, 'PCB Admin', 'admin@pcb.edu.ph', '$2y$10$wT0E82P5aXU6E7w9E/FjmeL52WkRrhh0Z36.qV17s00pZp33F8zti', 'admin', 'EMP-001', 'Facilities and General Services'),
(2, 'Juan Student', 'student@pcb.edu.ph', '$2y$10$eE7nF3v2Aekv0B02e9E1lOWqWq153E0P2uVz0c/K4.3Ww6uY4Yjwe', 'student', '2024-00142', 'Institute of Computing Studies'),
(3, 'Maria Santos', 'maria.s@pcb.edu.ph', '$2y$10$eE7nF3v2Aekv0B02e9E1lOWqWq153E0P2uVz0c/K4.3Ww6uY4Yjwe', 'student', '2024-00189', 'College of Teacher Education');

-- 4. Initial Seed Complaints (including the exact report from the UI wireframe mockup!)
INSERT INTO `complaints` (
  `id`, `tracking_code`, `student_id`, `student_name`, `student_email`, 
  `is_anonymous`, `category_id`, `problem`, `location`, 
  `description`, `photo_url`, `status`, `urgency`, `upvotes`, 
  `assigned_staff_id`, `admin_notes`, `resolution_notes`, `submitted_at`, `in_progress_at`, `resolved_at`
) VALUES
(
  1, 
  'SP-2024-8842', 
  NULL, 
  'Anonymous', 
  NULL, 
  1, 
  1, 
  'No water in comfort room', 
  'ICS building, 2nd floor', 
  'The comfort room has no water, so we can\'t use it, in th past 3 days. Student forced to go to other comfort room which is far from our classroom.', 
  'assets/images/samples/leaking_faucet.svg', 
  'Submitted', 
  'High', 
  5, 
  1, 
  'Assigned to Plumbing staff for immediate inspection of main cistern valves.', 
  NULL, 
  '2024-09-08 09:12:00', 
  NULL, 
  NULL
),
(
  2, 
  'SP-2024-5120', 
  2, 
  'Juan Student', 
  'student@pcb.edu.ph', 
  0, 
  3, 
  'Broken wooden armchairs', 
  'Main Academic Building, Room 204', 
  'Several chairs have detached armrests and loose screws causing students discomfort and torn uniforms during morning lectures.', 
  'assets/images/samples/broken_chair.svg', 
  'In progress', 
  'Medium', 
  2, 
  3, 
  'Carpentry team dispatched to replace 6 defective armchairs.', 
  NULL, 
  '2024-09-09 11:30:00', 
  '2024-09-10 08:15:00', 
  NULL
),
(
  3, 
  'SP-2024-3401', 
  3, 
  'Maria Santos', 
  'maria.s@pcb.edu.ph', 
  0, 
  4, 
  'Ceiling fan vibrating loudly and sparking', 
  'CTE Laboratory Rm 102', 
  'The front ceiling fan makes an alarming buzzing noise and occasionally sparks when set to maximum speed. Potential hazard.', 
  'assets/images/samples/electrical_issue.svg', 
  'Resolved', 
  'Critical', 
  8, 
  2, 
  'Emergency electrical check completed.', 
  'Replaced capacitor and secured loose fan motor mounting bracket. Tested safe for student operation.', 
  '2024-09-05 14:00:00', 
  '2024-09-05 15:10:00', 
  '2024-09-06 10:00:00'
),
(
  4, 
  'SP-2024-9122', 
  NULL, 
  'Anonymous', 
  NULL, 
  1, 
  5, 
  'Overflowing trash bin near cafeteria', 
  'Student Center Hallway', 
  'The waste bin has been overflowing since yesterday afternoon attracting flies and emitting unpleasant odor.', 
  'assets/images/samples/cleanliness.svg', 
  'Submitted', 
  'Medium', 
  3, 
  4, 
  'Janitorial sweep scheduled for 1:00 PM.', 
  NULL, 
  '2024-09-11 08:45:00', 
  NULL, 
  NULL
);

-- 5. Complaint Activity Logs
INSERT INTO `complaint_logs` (`complaint_id`, `action_type`, `performed_by`, `notes`, `created_at`) VALUES
(1, 'Report Submitted', 'Student (Anonymous)', 'Facility report submitted via mobile web portal.', '2024-09-08 09:12:00'),
(2, 'Report Submitted', 'Juan Student', 'Report logged with attached photo.', '2024-09-09 11:30:00'),
(2, 'Status Change', 'PCB Admin', 'Status changed to In progress. Assigned to Pedro Reyes.', '2024-09-10 08:15:00'),
(3, 'Report Submitted', 'Maria Santos', 'Urgent electrical report.', '2024-09-05 14:00:00'),
(3, 'Status Change', 'PCB Admin', 'Marked In progress. Assigned to Mark Angelo Santos.', '2024-09-05 15:10:00'),
(3, 'Resolved', 'PCB Admin', 'Maintenance completed and tested safe.', '2024-09-06 10:00:00');
