-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 05, 2025 at 03:30 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cloude_drive`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` enum('file','folder') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `resource_type`, `resource_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', 'folder', 6, '{\"folder_name\":\"Company Projects\"}', NULL, NULL, '2025-08-29 12:55:00'),
(2, 1, 'create', 'folder', 7, '{\"folder_name\":\"Marketing Materials\"}', NULL, NULL, '2025-08-30 12:55:00'),
(3, 3, 'upload', 'file', 4, '{\"filename\":\"brochure_draft.pdf\"}', NULL, NULL, '2025-08-31 12:55:00'),
(4, 4, 'upload', 'file', 6, '{\"filename\":\"source_code_v2.zip\"}', NULL, NULL, '2025-09-01 12:55:00'),
(5, 2, 'download', 'file', 1, '{\"filename\":\"company_policy.pdf\"}', NULL, NULL, '2025-09-02 12:55:00'),
(6, 3, 'share', 'folder', 7, '{\"shared_with\":\"john_doe\",\"permissions\":\"read,write\"}', NULL, NULL, '2025-09-03 12:55:00'),
(7, 5, 'upload', 'file', 18, '{\"filename\":\"old_documents.zip\"}', NULL, NULL, '2025-09-04 12:55:00'),
(8, 4, 'delete', 'file', 0, '{\"filename\":\"temp_file.txt\"}', NULL, NULL, '2025-09-05 12:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `original_name`, `file_name`, `file_path`, `file_size`, `file_type`, `mime_type`, `folder_id`, `uploaded_by`, `upload_date`, `is_deleted`) VALUES
(1, 'company_policy.pdf', 'file_admin_001.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/admin/file_admin_001.pdf', 256000, 'pdf', 'application/pdf', 1, 1, '2025-09-05 12:55:00', 0),
(2, 'employee_handbook.docx', 'file_admin_002.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/admin/file_admin_002.docx', 512000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 1, '2025-09-05 12:55:00', 0),
(3, 'logo_v1.png', 'file_marketing_001.png', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_001.png', 128000, 'png', 'image/png', 7, 1, '2025-09-05 12:55:00', 0),
(4, 'brochure_draft.pdf', 'file_marketing_002.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_002.pdf', 1024000, 'pdf', 'application/pdf', 7, 3, '2025-09-05 12:55:00', 0),
(5, 'social_media_assets.zip', 'file_marketing_003.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Marketing Materials/file_marketing_003.zip', 2048000, 'zip', 'application/zip', 7, 3, '2025-09-05 12:55:00', 0),
(6, 'source_code_v2.zip', 'file_dev_001.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_001.zip', 5120000, 'zip', 'application/zip', 8, 4, '2025-09-05 12:55:00', 0),
(7, 'database_schema.sql', 'file_dev_002.sql', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_002.sql', 64000, 'sql', 'text/sql', 8, 4, '2025-09-05 12:55:00', 0),
(8, 'api_documentation.md', 'file_dev_003.md', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/Development/file_dev_003.md', 32000, 'md', 'text/markdown', 8, 2, '2025-09-05 12:55:00', 0),
(9, 'job_descriptions.docx', 'file_hr_001.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/HR Documents/file_hr_001.docx', 256000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 9, 1, '2025-09-05 12:55:00', 0),
(10, 'salary_structure.xlsx', 'file_hr_002.xlsx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Company Projects/HR Documents/file_hr_002.xlsx', 128000, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 9, 1, '2025-09-05 12:55:00', 0),
(11, 'training_video.mp4', 'file_public_001.mp4', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Public Resources/file_public_001.mp4', 10485760, 'mp4', 'video/mp4', 10, 1, '2025-09-05 12:55:00', 0),
(12, 'company_presentation.pptx', 'file_public_002.pptx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/Public Resources/file_public_002.pptx', 2048000, 'pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 10, 2, '2025-09-05 12:55:00', 0),
(13, 'resume.pdf', 'file_john_001.pdf', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Documents/file_john_001.pdf', 200000, 'pdf', 'application/pdf', 11, 2, '2025-09-05 12:55:00', 0),
(14, 'project_notes.txt', 'file_john_002.txt', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Documents/file_john_002.txt', 8000, 'txt', 'text/plain', 11, 2, '2025-09-05 12:55:00', 0),
(15, 'vacation_photo.jpg', 'file_john_003.jpg', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/john_doe/Photos/file_john_003.jpg', 1024000, 'jpg', 'image/jpeg', 12, 2, '2025-09-05 12:55:00', 0),
(16, 'client_proposal.docx', 'file_sarah_001.docx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/sarah_wilson/Work Files/file_sarah_001.docx', 512000, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 13, 3, '2025-09-05 12:55:00', 0),
(17, 'budget_analysis.xlsx', 'file_sarah_002.xlsx', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/sarah_wilson/Work Files/file_sarah_002.xlsx', 256000, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 13, 3, '2025-09-05 12:55:00', 0),
(18, 'code_snippets.txt', 'file_mike_001.txt', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/mike_chen/Personal/file_mike_001.txt', 16000, 'txt', 'text/plain', 14, 4, '2025-09-05 12:55:00', 0),
(19, 'family_photo.png', 'file_mike_002.png', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/mike_chen/Personal/file_mike_002.png', 2048000, 'png', 'image/png', 14, 4, '2025-09-05 12:55:00', 0),
(20, 'old_documents.zip', 'file_emma_001.zip', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/emma_brown/Archive/file_emma_001.zip', 1024000, 'zip', 'application/zip', 15, 5, '2025-09-05 12:55:00', 0),
(21, 'backup_data.tar.gz', 'file_emma_002.tar.gz', '/Applications/XAMPP/xamppfiles/htdocs/cloudeDrive/uploads/emma_brown/Archive/file_emma_002.tar.gz', 5120000, 'tar.gz', 'application/gzip', 15, 5, '2025-09-05 12:55:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `file_permissions`
--

CREATE TABLE `file_permissions` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_download` tinyint(1) DEFAULT 1,
  `can_delete` tinyint(1) DEFAULT 0,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `is_shared` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `name`, `parent_id`, `owner_id`, `is_shared`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(2, 'john_doe', NULL, 2, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(3, 'sarah_wilson', NULL, 3, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(4, 'mike_chen', NULL, 4, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(5, 'emma_brown', NULL, 5, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(6, 'Company Projects', NULL, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(7, 'Marketing Materials', 6, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(8, 'Development', 6, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(9, 'HR Documents', 6, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(10, 'Public Resources', NULL, 1, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(11, 'Documents', 2, 2, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(12, 'Photos', 2, 2, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(13, 'Work Files', 3, 3, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(14, 'Personal', 4, 4, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(15, 'Archive', 5, 5, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `folder_permissions`
--

CREATE TABLE `folder_permissions` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_download` tinyint(1) DEFAULT 1,
  `can_upload` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folder_permissions`
--

INSERT INTO `folder_permissions` (`id`, `folder_id`, `user_id`, `can_view`, `can_download`, `can_upload`, `can_delete`, `granted_by`, `granted_at`) VALUES
(1, 7, 2, 1, 1, 1, 0, 1, '2025-09-05 12:55:00'),
(2, 7, 3, 1, 1, 1, 1, 1, '2025-09-05 12:55:00'),
(3, 7, 4, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(4, 7, 5, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(5, 8, 2, 1, 1, 1, 1, 1, '2025-09-05 12:55:00'),
(6, 8, 4, 1, 1, 1, 1, 1, '2025-09-05 12:55:00'),
(7, 8, 3, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(8, 8, 5, 0, 0, 0, 0, 1, '2025-09-05 12:55:00'),
(9, 9, 3, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(10, 9, 2, 0, 0, 0, 0, 1, '2025-09-05 12:55:00'),
(11, 9, 4, 0, 0, 0, 0, 1, '2025-09-05 12:55:00'),
(12, 9, 5, 0, 0, 0, 0, 1, '2025-09-05 12:55:00'),
(13, 10, 2, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(14, 10, 3, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(15, 10, 4, 1, 1, 0, 0, 1, '2025-09-05 12:55:00'),
(16, 10, 5, 1, 1, 0, 0, 1, '2025-09-05 12:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `upload_sessions`
--

CREATE TABLE `upload_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `total_size` bigint(20) NOT NULL,
  `chunk_size` int(11) NOT NULL,
  `total_chunks` int(11) NOT NULL,
  `uploaded_chunks` text DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 24 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `storage_limit` bigint(20) DEFAULT 10737418240,
  `used_storage` bigint(20) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `storage_limit`, `used_storage`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@clouddrive.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'admin', 10737418240, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(2, 'john_doe', 'john@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(3, 'sarah_wilson', 'sarah@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(4, 'mike_chen', 'mike@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00'),
(5, 'emma_brown', 'emma@example.com', '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6', 'user', 1073741824, 0, '2025-09-05 12:55:00', '2025-09-05 12:55:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_files_folder_id` (`folder_id`),
  ADD KEY `idx_files_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_file_user` (`file_id`,`user_id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_file_permissions_file_id` (`file_id`),
  ADD KEY `idx_file_permissions_user_id` (`user_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `folder_permissions`
--
ALTER TABLE `folder_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_folder_user` (`folder_id`,`user_id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_folder_permissions_folder_id` (`folder_id`),
  ADD KEY `idx_folder_permissions_user_id` (`user_id`);

--
-- Indexes for table `upload_sessions`
--
ALTER TABLE `upload_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `file_permissions`
--
ALTER TABLE `file_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `folder_permissions`
--
ALTER TABLE `folder_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD CONSTRAINT `file_permissions_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folder_permissions`
--
ALTER TABLE `folder_permissions`
  ADD CONSTRAINT `folder_permissions_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folder_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folder_permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `upload_sessions`
--
ALTER TABLE `upload_sessions`
  ADD CONSTRAINT `upload_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `upload_sessions_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
