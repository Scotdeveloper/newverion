-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 20, 2026 at 07:17 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `admin_panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','editor') NOT NULL DEFAULT 'editor',
  `failed_attempts` int DEFAULT '0',
  `last_failed_attempt` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `violations` int DEFAULT '0',
  `violation_reason` text,
  `violation_timestamp` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `session_id` varchar(255) DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `session_created_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `last_login_device` text,
  `last_login_request_at` timestamp NULL DEFAULT NULL,
  `login_requests_count` int DEFAULT '0',
  `login_count` int DEFAULT NULL,
  `last_session_id` varchar(255) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`, `failed_attempts`, `last_failed_attempt`, `locked_until`, `violations`, `violation_reason`, `violation_timestamp`, `created_at`, `updated_at`, `session_id`, `session_token`, `session_created_at`, `last_login_ip`, `last_login_device`, `last_login_request_at`, `login_requests_count`, `login_count`, `last_session_id`, `last_login_at`) VALUES
(10, 'EastGate', 'eastgateassembly@gmail.com', '$2y$10$AtODDAXp5Kph8WThaFnxcuE/WbQTPC2DklRzet5ut6SXnvpwzLsxC', 'super_admin', 0, NULL, NULL, 0, NULL, NULL, '2026-08-20 18:21:14', '2026-10-20 19:04:33', NULL, '41056eab1418c1b613aa6df2aa87ccbc6e148068c5fea841a905601f9d112530', '2026-10-20 19:04:33', '::1', 'Windows PC (Chrome)', NULL, 0, NULL, NULL, NULL),
(11, 'admin', 'johnmachariamwangi123@gmail.com', '$2y$10$I13JghCwVgelE3qOHYfg9O.i/WG7LykRrYfXd8uzXqwZtHam5dmqO', 'editor', 5, '2026-10-20 18:20:14', '2026-10-20 19:20:14', 0, NULL, NULL, '2026-08-20 18:26:23', '2026-10-20 18:20:14', NULL, NULL, '2026-10-20 18:11:17', '::1', 'Windows PC (Edge)', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_permission` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`admin_id`,`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `admin_id` int NOT NULL,
  `role_id` int NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int DEFAULT NULL,
  PRIMARY KEY (`admin_id`,`role_id`),
  KEY `fk_ar_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`admin_id`, `role_id`, `assigned_at`, `assigned_by`) VALUES
(10, 1, '2026-10-19 18:26:38', NULL),
(11, 2, '2026-10-19 18:26:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `status` enum('approve','rejected','cancel') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` tinyint(1) DEFAULT '0',
  `termination_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_session_id` (`session_token`(250)),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` bigint NOT NULL,
  `admin_id` int DEFAULT NULL,
  `admin_name_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `outcome` enum('success','failure','denied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `after_json` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

DROP TABLE IF EXISTS `children`;
CREATE TABLE IF NOT EXISTS `children` (
  `id` int NOT NULL,
  `event_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guardian_id` int NOT NULL,
  `child_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('unread','read','followed_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_child_uuid` (`child_uuid`),
  UNIQUE KEY `uniq_child_per_guardian` (`event_id`,`guardian_id`,`normalized_name`,`date_of_birth`),
  KEY `idx_guardian` (`guardian_id`),
  KEY `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_batch_requests`
--

DROP TABLE IF EXISTS `child_batch_requests`;
CREATE TABLE IF NOT EXISTS `child_batch_requests` (
  `idempotency_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guardian_id` int NOT NULL,
  `event_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_json` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idempotency_key`),
  KEY `idx_guardian` (`guardian_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `child_batch_requests`
--

INSERT INTO `child_batch_requests` (`idempotency_key`, `guardian_id`, `event_id`, `response_json`, `created_at`) VALUES
('113af03b0be94080d55a338983038e9434f82e22d071fd2ea8002602a918d44d', 8, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":15,\"child_uuid\":\"3b88fb16-c812-4491-919e-f308920dec08\",\"full_name\":\"Ytgg\",\"already_registered\":false}],\"guardian\":{\"id\":8,\"full_name\":\"Joy kim\",\"phone\":\"0745258369\",\"location\":\"Kingongo\"}}', '2026-09-23 19:56:06'),
('1c2a0b3c96c45d6024692e000554cf25187e00749a08a976b37d25eb870c8a85', 4, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":16,\"child_uuid\":\"85f630c6-132f-4004-9b8d-32970cfa3a61\",\"full_name\":\"felly\",\"already_registered\":false}],\"guardian\":{\"id\":4,\"full_name\":\"Mwangi John Macharia\",\"phone\":\"0757226870\",\"location\":\"meru\"}}', '2026-09-29 12:54:10'),
('219ed384173c464a20d9223e4864d2ef863c102687a67f9c81beab31c5581135', 4, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":3,\"child_uuid\":\"7be59064-4676-430a-9c3c-69e99dad5aa1\",\"full_name\":\"mwangi scott\",\"already_registered\":false},{\"id\":4,\"child_uuid\":\"143b550b-b017-4df7-b687-434acdeafb71\",\"full_name\":\"betty scott\",\"already_registered\":false},{\"id\":5,\"child_uuid\":\"1578d2b3-aa1f-4291-8229-f02051cca699\",\"full_name\":\"margaret mwangi\",\"already_registered\":false},{\"id\":6,\"child_uuid\":\"381c290d-a08c-4fbc-9687-13d8ec1fb1ee\",\"full_name\":\"joseph mwangi\",\"already_registered\":false},{\"id\":7,\"child_uuid\":\"d67cadac-326c-45e0-a437-6f111e9f620a\",\"full_name\":\"jerry mwangi\",\"already_registered\":false}],\"guardian\":{\"id\":4,\"full_name\":\"Mwangi John Macharia\",\"phone\":\"0757226870\",\"location\":\"meru\"}}', '2026-09-20 20:28:31'),
('4f8bcdf481ffc642290564a74917e437a8a0e73defdf7e4a7d0a20f87922c007', 4, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":1,\"child_uuid\":\"f62162c3-56c4-47dc-8a91-879e0f793cc1\",\"full_name\":\"flora\",\"already_registered\":false}],\"guardian\":{\"id\":4,\"full_name\":\"Mwangi John Macharia\",\"phone\":\"0757226870\",\"location\":\"meru\"}}', '2026-09-18 20:49:34'),
('7312a83be4e5400278e54b6f6842d27c267fe650e4de3d66c1aab07e4364b8d6', 6, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":8,\"child_uuid\":\"019fbbf5-98b0-49e8-bd3c-19aaf5580b0c\",\"full_name\":\"mwangi scott\",\"already_registered\":false},{\"id\":9,\"child_uuid\":\"004ac26f-e06a-421b-ba81-5c0b04c1a251\",\"full_name\":\"jeff karanja\",\"already_registered\":false},{\"id\":10,\"child_uuid\":\"306fc77e-f3fa-40f5-b46c-7c687bb12fff\",\"full_name\":\"betty mwangi\",\"already_registered\":false},{\"id\":11,\"child_uuid\":\"97598c72-e464-484f-b960-8f77af3cb5e2\",\"full_name\":\"daniel karanja\",\"already_registered\":false},{\"id\":12,\"child_uuid\":\"ea33b6ea-a280-47b8-a11e-241ef739bb80\",\"full_name\":\"steven mwangi\",\"already_registered\":false},{\"id\":13,\"child_uuid\":\"5910d30e-eeed-4f69-af81-f65c26d371f4\",\"full_name\":\"beatrice wangeci\",\"already_registered\":false}],\"guardian\":{\"id\":6,\"full_name\":\"joseph mwangi\",\"phone\":\"0112345678\",\"location\":\"Kingongo\"}}', '2026-09-20 21:19:14'),
('747001d04749225b7ad6fbac637430447cd3bc0f92f1987476ca7ba8a4cf5e34', 7, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":14,\"child_uuid\":\"3261e35e-a77c-4071-b6e4-65f8a61abb8a\",\"full_name\":\"Scooter\",\"already_registered\":false}],\"guardian\":{\"id\":7,\"full_name\":\"John kanja\",\"phone\":\"0712345678\",\"location\":\"Ihururu\"}}', '2026-09-23 18:23:13'),
('7ae4f4643ed4aa86d48fe397059c8ea9dd3e7a9d697f37cc4f017ac1fc1b0f87', 5, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":2,\"child_uuid\":\"72028de6-c742-4411-a67d-040dd57c2737\",\"full_name\":\"flora\",\"already_registered\":false}],\"guardian\":{\"id\":5,\"full_name\":\"scott mwangi\",\"phone\":\"0703940389\",\"location\":\"kandara\"}}', '2026-09-18 20:51:49'),
('93671ae6f1b5f06b63c1dff9511be8be66810940088deb2baf3f4f7393eebef1', 14, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":18,\"child_uuid\":\"6d4ca4bc-e1cf-4364-9d5f-5d5da6741470\",\"full_name\":\"john mwangi\",\"already_registered\":false},{\"id\":19,\"child_uuid\":\"9a19b33d-fa85-4450-bfae-546cf624b2ef\",\"full_name\":\"scott mwanfi\",\"already_registered\":false},{\"id\":20,\"child_uuid\":\"07cb264c-8787-4399-92dd-c2ebcc0b2165\",\"full_name\":\"judy mwangi\",\"already_registered\":false},{\"id\":21,\"child_uuid\":\"0672370c-64eb-4f0c-833d-2495ae6a6796\",\"full_name\":\"betty mwangi\",\"already_registered\":false},{\"id\":22,\"child_uuid\":\"38104661-7d48-4ab3-aee2-8b63325af6c2\",\"full_name\":\"joseph mwangi\",\"already_registered\":false},{\"id\":23,\"child_uuid\":\"3a61ec7e-87e7-4555-9671-70c71796685a\",\"full_name\":\"steven mwangi\",\"already_registered\":false}],\"guardian\":{\"id\":14,\"full_name\":\"Mwangi John Macharia\",\"phone\":\"0703940389\",\"location\":\"kandara\"}}', '2026-10-08 21:38:13'),
('b369460ab5f2419b3e5cd3563d3ebb32ffb1e782104f15626cb6168eb862a253', 9, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":17,\"child_uuid\":\"e09fa053-816e-4133-b994-9eaebf91a4d8\",\"full_name\":\"scot mwangi\",\"already_registered\":false}],\"guardian\":{\"id\":9,\"full_name\":\"kim mwangi\",\"phone\":\"012345678\",\"location\":\"majengo\"}}', '2026-09-29 16:37:09'),
('e8ce526c7e0e2c9c6c0008334a1b329b9a02854a3d3059139656ceb82e88ee20', 15, 'heroes_dinner_2026', '{\"success\":true,\"message\":\"Children registered successfully.\",\"children\":[{\"id\":24,\"child_uuid\":\"e08dbd67-a851-4765-aeec-65262636f2bd\",\"full_name\":\"Scott mwangi\",\"already_registered\":false}],\"guardian\":{\"id\":15,\"full_name\":\"Scott mwangi\",\"phone\":\"0758412435\",\"location\":\"Kangemi\"}}', '2026-10-11 10:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `connect_requests`
--

DROP TABLE IF EXISTS `connect_requests`;
CREATE TABLE IF NOT EXISTS `connect_requests` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `message` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `form_fingerprint` varchar(64) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `connect_requests`
--

INSERT INTO `connect_requests` (`id`, `name`, `email`, `phone`, `location`, `message`, `ip_address`, `user_agent`, `form_fingerprint`, `status`, `is_read`, `submitted_at`) VALUES
(9, 'john mwangi', 'john@gmail.com', '+254757226870', 'kenya', 'I would like to join a connect group. Please contact me with more information about groups in my area.', '102.210.40.230', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'b0b51dc6587ed5c10252d4829d5af19f3f0553d6ee3a1584409a13db135447f0', 'new', 1, '2026-03-24 15:47:26'),
(5, 'Mwangi chumari', 'mwangichumari@gmail.com', '+254795520608', 'Asian quarters', 'I would like to join a connect group.', '154.156.204.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '6c6d7ea6c87c78bfb2ed9235f9fe09a881d470db7939a3d783f471118761a778', 'new', 1, '2026-03-04 01:57:43'),
(12, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '0157226870', 'Nyeri', 'I would like to join a connect group. Please contact me with more information about groups in my area.', '102.210.43.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'c5580719d8e2f81b44dce5480f38179e3d882b4bc87bfae1449534f6b5d84ab6', 'new', 1, '2026-07-15 23:18:17'),
(13, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '254757226870', 'kandara', 'I would like to join a connect group. Please contact me with more information about groups in my area.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'b967832a160b07bd6d63669639ce262843b270ad50421d11ca6431f0fa0e7865', 'new', 1, '2026-09-29 08:33:45'),
(14, 'Mwangi Macharia', 'johnmachariamwangi13@gmail.com', '0703940389', 'meru', 'I would like to join a connect group. Please contact me with more information about groups in my area.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '7a39a284246e1e60faa0a945e7018ddb7b2b738e2fd7890ced08165ced381525', 'new', 1, '2026-09-29 09:12:44'),
(15, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '0703940389', 'kandara', 'I would like to join a connect group. Please contact me with more information about groups in my area.', '102.0.33.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '5e9b0661f0145d5d447b2d4d1bf37a6cbb8d806590ca83ca806f1f887dd69fce', 'new', 1, '2026-10-08 21:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `contact_requests`
--

DROP TABLE IF EXISTS `contact_requests`;
CREATE TABLE IF NOT EXISTS `contact_requests` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `form_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('new','read','replied','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_fingerprint` (`form_fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_requests`
--

INSERT INTO `contact_requests` (`id`, `name`, `email`, `phone`, `subject`, `message`, `ip_address`, `user_agent`, `is_read`, `form_fingerprint`, `status`, `submitted_at`) VALUES
(14, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '254757226870', 'pray for me', 'bbbbbbbbbbbbbbbbbbbbbb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 1, 'dae9969c53048beb7482ec3101117c51e5f46762a427cc717305a553ecfe1992', 'new', '2026-09-29 09:10:55'),
(15, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '254757226870', 'pray for me', 'nnnnnnnnnnnnnnnnnnnnnnnnnnnnnn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 1, 'f9436fbd55794d7d5b113cca359e09a3e13a1b8068e568307bbdb2c4e1a33713', 'new', '2026-09-29 09:12:01'),
(16, 'Mwangi John Macharia', 'johnmachariamwangi123@gmail.com', '0703940389', 'pray for me', 'dddddddddddddddddddddddddddddddddd', '102.0.33.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 1, 'aab653bba2b772ee583bcd8a185d5781d8e070b2a376eb7e3730dc2bd8d2e9f3', 'new', '2026-10-08 21:30:10'),
(17, 'Hubda', '', '0798456123', 'We are in danger', 'Ggggdhdhdhdhhr', '102.0.33.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', 1, '9356671eef97373140d78d90145a11da357bee6812c98c158ed6d72ff272d601', 'new', '2026-10-08 22:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `emergencies`
--

DROP TABLE IF EXISTS `emergencies`;
CREATE TABLE IF NOT EXISTS `emergencies` (
  `id` int NOT NULL,
  `content` text NOT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','published','cancelled','archived') DEFAULT 'published',
  `event_date` date DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `image_path`, `status`, `event_date`, `location`, `event_time`, `created_at`, `updated_at`) VALUES
(10, 'Heros\' Dinner 2026 Edition', 'A Night to celebrate Our Heros\' of Faith', 'uploads/6ac7d782c4d04_b73b14524c11b336.jpg', 'published', '2026-10-16', 'EastGate assembly', '18:00:00', '2026-10-08 17:48:51', '2026-10-08 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `heroes_dinner_registrations`
--

DROP TABLE IF EXISTS `heroes_dinner_registrations`;
CREATE TABLE IF NOT EXISTS `heroes_dinner_registrations` (
  `id` int NOT NULL,
  `event_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'heroes_dinner_2026',
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marital_status` enum('Teens','Youth/Single','Married','Widowed','Separated/Divorced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Youth/Single',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_session_token` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('unread','read','followed_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `idx_event_phone` (`event_id`,`normalized_phone`),
  KEY `idx_event_name` (`event_id`,`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `heroes_dinner_registrations`
--

INSERT INTO `heroes_dinner_registrations` (`id`, `event_id`, `full_name`, `gender`, `marital_status`, `phone`, `normalized_phone`, `location`, `token`, `idempotency_key`, `guardian_session_token`, `ip_address`, `user_agent`, `registered_at`, `updated_at`, `status`) VALUES
(24, 'heroes_dinner_2026', 'Mwaniki Brian Wambugu', 'Male', 'Youth/Single', '0702328596', '254702328596', 'Meeting point', '8c2091d67db4e54b406b84a2e91a88385248633ce92c0aea1662249b15646d21', 'eec27ea5b32ec24a0198f40e5d4825741d82f70caafa65fc9764dbb5d5de29da', '2fbb684fe2cbd60de42b132c22b2c8b918b52927bbba252fb69dcd8e83aa9a3d', '154.159.238.185', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', '2026-10-09 10:57:45', '2026-10-11 11:24:54', 'read'),
(25, 'heroes_dinner_2026', 'Jesse Kihara', 'Male', 'Youth/Single', '+254796471986', '254796471986', 'Skuta Kagayu', '4aa1bd9fc8a881dc549036c194651ec391cc66ab42d85654e5839537f2767cd2', '71c250dbc04dec3be1ca3cec1b754710154bfa51dcc28f66cfd846cb5dc75c63', '981c5befdcc533db0b21a92625acac5d323373c72d5b18d86ce9e268edca17e5', '154.76.222.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', '2026-10-09 18:05:25', '2026-10-11 11:25:25', 'read');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `success` tinyint(1) DEFAULT '0',
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `success`, `attempt_time`) VALUES
(125, '102.210.43.14', 'scott', 0, '2026-07-06 20:24:38'),
(124, '41.89.254.2', 'Scott', 1, '2026-07-02 08:58:34'),
(107, '102.210.40.230', 'Eastgate', 1, '2026-05-13 19:29:41'),
(109, '102.210.40.230', 'Eastgate', 1, '2026-05-15 18:20:53'),
(110, '41.89.254.2', 'Eastgate', 1, '2026-05-19 09:34:26'),
(111, '102.210.40.230', 'Eastgate', 1, '2026-05-28 07:54:44'),
(112, '41.89.254.2', 'Eastgate', 1, '2026-06-15 11:50:04'),
(113, '41.89.254.2', 'Eastgate', 1, '2026-06-16 12:22:07'),
(114, '41.89.254.2', 'Scott', 1, '2026-06-16 12:25:48'),
(115, '41.89.254.2', 'Eastgate', 1, '2026-06-16 12:26:33'),
(116, '41.89.254.2', 'Scott', 1, '2026-06-16 12:27:55'),
(117, '41.89.254.2', 'Scott', 1, '2026-06-16 13:25:07'),
(118, '41.89.254.2', 'Scott', 1, '2026-06-16 15:11:55'),
(119, '41.89.254.2', 'Scott', 1, '2026-06-17 08:01:56'),
(120, '102.210.40.230', 'Scott', 1, '2026-06-18 15:19:08'),
(121, '41.89.254.2', 'Scott', 1, '2026-06-23 08:23:38'),
(122, '41.89.254.2', 'scott', 1, '2026-06-23 09:07:43'),
(123, '41.89.254.2', 'scott', 1, '2026-06-23 09:15:03'),
(108, '102.210.40.230', 'Eastgate', 1, '2026-05-14 07:00:36'),
(126, '102.210.43.14', 'scott', 0, '2026-07-06 20:25:08'),
(127, '102.210.43.14', 'scott', 1, '2026-07-06 20:26:28'),
(128, '41.89.254.2', 'scott', 1, '2026-07-08 08:23:22'),
(129, '41.90.238.74', 'scott', 0, '2026-07-10 13:50:47'),
(130, '41.90.238.74', 'Scott', 1, '2026-07-10 13:51:08'),
(131, '102.210.43.14', 'scott', 1, '2026-07-15 15:32:02'),
(132, '102.210.43.14', 'Scott', 1, '2026-07-15 15:32:22'),
(133, '102.210.43.14', 'Scott', 1, '2026-07-15 16:33:44'),
(134, '102.210.43.14', 'Scott', 1, '2026-07-15 18:30:12'),
(135, '102.210.43.14', 'Scott', 1, '2026-07-15 18:58:46'),
(136, '102.210.43.14', 'scott', 0, '2026-07-15 19:21:58'),
(137, '102.210.43.14', 'scott', 1, '2026-07-15 19:22:12'),
(138, '102.210.43.14', 'Scott', 1, '2026-07-15 22:08:52'),
(139, '102.210.43.14', 'scott', 0, '2026-07-15 22:17:11'),
(140, '102.210.43.14', 'scott', 1, '2026-07-15 22:17:27'),
(141, '102.210.43.14', 'EastGate', 1, '2026-07-15 22:53:54'),
(142, '102.210.43.14', 'Scott', 1, '2026-07-15 23:14:53'),
(143, '102.210.43.14', 'Scott', 1, '2026-07-16 07:05:13'),
(144, '102.210.43.14', 'Scott', 1, '2026-07-16 16:15:08'),
(145, '102.210.43.14', 'Eastgate', 1, '2026-07-16 17:29:22'),
(146, '102.210.43.14', 'Scott', 1, '2026-07-16 18:43:09'),
(147, '41.90.238.74', 'Scott', 1, '2026-07-17 09:17:14'),
(148, '41.90.238.74', 'Eastgate', 1, '2026-07-18 08:47:25'),
(149, '41.90.238.74', 'Scott', 1, '2026-07-18 10:57:12'),
(150, '41.90.238.74', 'Eastgate', 1, '2026-07-18 14:29:22'),
(151, '41.90.238.74', 'Eastgate', 1, '2026-07-20 08:22:29'),
(152, '102.210.43.14', 'Eastgate', 1, '2026-07-22 17:20:38'),
(153, '102.210.43.14', 'Eastgate', 1, '2026-07-23 19:05:53'),
(154, '102.210.43.14', 'Scott', 0, '2026-07-25 19:22:27'),
(155, '102.210.43.14', 'Eastgate', 1, '2026-07-25 19:23:01'),
(156, '102.210.43.14', 'Scott', 0, '2026-07-25 19:35:48'),
(157, '102.210.43.14', 'Scott', 1, '2026-07-25 19:38:34'),
(158, '102.210.43.14', 'Scott', 1, '2026-07-26 18:24:46'),
(159, '154.159.252.6', 'Eastgate', 1, '2026-07-26 18:26:06'),
(160, '102.210.43.14', 'Eastgate', 1, '2026-07-26 18:39:56'),
(161, '154.159.238.237', 'Eastgate', 1, '2026-07-26 19:57:06'),
(162, '102.210.43.14', 'Eastgate', 1, '2026-07-26 22:24:39'),
(163, '102.210.43.14', 'Eastgate', 1, '2026-07-27 15:11:35'),
(164, '102.210.43.14', 'Eastgate', 1, '2026-07-27 15:17:28'),
(165, '102.210.43.14', 'Eastgate', 1, '2026-07-27 15:49:00'),
(166, '102.210.43.14', 'Eastgate', 0, '2026-08-17 15:08:11'),
(167, '102.210.43.14', 'Scott', 0, '2026-08-17 15:08:25'),
(168, '102.210.43.14', 'Scott', 1, '2026-08-17 15:08:35'),
(169, '::1', 'EastGate', 0, '2026-08-17 15:16:18'),
(170, '::1', 'scott', 1, '2026-08-17 15:18:11'),
(171, '::1', 'scott', 1, '2026-08-17 15:19:34'),
(172, '::1', 'scott', 1, '2026-08-17 15:31:49'),
(173, '::1', 'scott', 1, '2026-08-17 15:34:10'),
(174, '::1', 'scott', 1, '2026-08-17 15:46:15'),
(175, '::1', 'scott', 1, '2026-08-17 15:49:03'),
(176, '::1', 'scott', 1, '2026-08-17 15:57:52'),
(177, '::1', 'scott', 1, '2026-08-17 18:04:23'),
(178, '::1', 'scott', 1, '2026-08-17 18:07:47'),
(179, '::1', 'scott', 1, '2026-08-18 18:46:59'),
(180, '::1', 'EastGate', 0, '2026-08-20 08:44:04'),
(181, '::1', 'EastGate', 1, '2026-08-20 08:44:16'),
(182, '::1', 'EastGate', 1, '2026-08-20 15:18:04'),
(183, '::1', 'EastGate', 1, '2026-08-20 15:18:28'),
(184, '::1', 'EastGate', 1, '2026-08-20 15:21:23'),
(185, '::1', 'admin', 1, '2026-08-20 15:26:31'),
(186, '::1', 'admin', 1, '2026-09-23 18:57:58'),
(187, '::1', 'admin', 1, '2026-09-23 19:25:24'),
(188, '::1', 'admin', 1, '2026-09-23 20:06:21'),
(189, '::1', 'admin', 1, '2026-09-25 19:22:05'),
(190, '::1', 'admin', 1, '2026-09-26 09:38:33'),
(191, '::1', 'admin', 1, '2026-09-29 16:39:37'),
(192, '::1', 'admin', 1, '2026-10-01 16:47:10'),
(193, '::1', 'admin', 1, '2026-10-01 19:02:07'),
(194, '102.0.33.226', 'Eastgate', 1, '2026-10-07 15:28:24'),
(195, '105.160.71.36', 'Eastgate', 1, '2026-10-08 14:38:57'),
(196, '102.0.33.226', 'Eastgate', 1, '2026-10-08 17:40:52'),
(197, '102.0.33.226', 'Eastgate', 1, '2026-10-08 19:01:58'),
(198, '102.0.33.226', 'Eastgate', 1, '2026-10-08 22:16:23'),
(199, '102.0.33.226', 'Eastgate', 1, '2026-10-09 08:23:57'),
(200, '102.0.33.226', 'Eastgate', 1, '2026-10-10 09:07:10'),
(201, '105.161.238.88', 'Eastgate', 1, '2026-10-11 11:24:33'),
(202, '102.0.33.226', 'Eastgate', 1, '2026-10-15 15:49:02'),
(203, '102.0.33.226', 'Eastgate', 1, '2026-10-18 21:18:28'),
(204, '102.0.33.226', 'Eastgate', 1, '2026-10-18 23:15:54'),
(205, '102.0.33.226', 'Eastgate', 1, '2026-10-19 18:39:31'),
(0, '::1', 'admin', 1, '2026-10-20 18:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `login_sessions`
--

DROP TABLE IF EXISTS `login_sessions`;
CREATE TABLE IF NOT EXISTS `login_sessions` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `terminated_at` timestamp NULL DEFAULT NULL,
  `terminated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`(250)),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_sessions`
--

INSERT INTO `login_sessions` (`id`, `admin_id`, `session_id`, `session_token`, `ip_address`, `user_agent`, `device_info`, `last_activity`, `created_at`, `is_active`, `terminated_at`, `terminated_by`) VALUES
(1, 10, 'et2c7a1jb80gqrqmrenq81q6on', '41056eab1418c1b613aa6df2aa87ccbc6e148068c5fea841a905601f9d112530', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'Windows PC (Chrome)', '2026-10-20 19:04:33', '2026-10-20 19:04:33', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
CREATE TABLE IF NOT EXISTS `members` (
  `id` int NOT NULL,
  `full_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `registered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  `gender` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone` (`phone`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_registered_at` (`registered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `full_name`, `age`, `email`, `phone`, `location`, `ip_address`, `user_agent`, `status`, `registered_at`, `is_read`, `gender`) VALUES
(6, 'JAMES KINGS', 12, 'scot00@gmail.com', '0789004535', 'nairobi', '41.89.254.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'active', '2026-07-02 03:49:35', 1, 'Male'),
(7, 'Mwangi John Macharia', 26, 'johnmachariamwangi123@gmail.com', '0142364488', 'Nairobi', '102.210.43.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'active', '2026-07-15 16:11:49', 1, 'Male'),
(8, 'Mwangi Macharia', 22, 'johnmachariamwangi1@gmail.com', '254757226879', 'kandara', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', 'active', '2026-09-29 13:46:07', 1, 'Female'),
(9, 'mirogi sbg', 35, 'mirogi@gmail.com', '0125427655', 'kandara', '102.0.33.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 'active', '2026-10-08 10:39:10', 1, 'Male'),
(10, 'sabato skott', 35, 'sabatosk@gmail.com', '0125427789', 'kandara', '102.0.33.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 'active', '2026-10-08 14:29:39', 1, 'Male');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_transactions`
--

DROP TABLE IF EXISTS `mpesa_transactions`;
CREATE TABLE IF NOT EXISTS `mpesa_transactions` (
  `id` int NOT NULL,
  `merchant_request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout_request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_code` int DEFAULT '0',
  `result_desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `donor_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `donor_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'offering',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'mpesa',
  `mpesa_receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `result_callback_data` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_request_id` (`checkout_request_id`),
  KEY `idx_checkout_request` (`checkout_request_id`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offerings`
--

DROP TABLE IF EXISTS `offerings`;
CREATE TABLE IF NOT EXISTS `offerings` (
  `id` int NOT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_phone` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(50) DEFAULT 'offering',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `message` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `hashed_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_token` (`email`,`hashed_token`(64)),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_used` (`used`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `hashed_token`, `expires_at`, `used`, `ip_address`, `user_agent`, `created_at`) VALUES
(14, 'johnmachariamwangi123@gmail.com', '$2y$10$XAA1TNhMlWbdrAAgL2vmHORCJda/6hA7ylojAWXyVvJC24hhomanq', '2026-08-16 14:50:37', 0, '102.210.43.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-08-16 11:35:37');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL,
  `perm_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perm_key` (`perm_key`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `perm_key`, `module`, `action`, `description`, `created_at`) VALUES
(1, 'events.view', 'events', 'view', 'View events', '2026-10-19 18:09:59'),
(2, 'events.create', 'events', 'create', 'Create events', '2026-10-19 18:09:59'),
(3, 'events.update', 'events', 'update', 'Edit events', '2026-10-19 18:09:59'),
(4, 'events.delete', 'events', 'delete', 'Delete events', '2026-10-19 18:09:59'),
(5, 'notifications.view', 'notifications', 'view', 'View notifications (emergencies)', '2026-10-19 18:09:59'),
(6, 'notifications.create', 'notifications', 'create', 'Create notifications', '2026-10-19 18:09:59'),
(7, 'notifications.update', 'notifications', 'update', 'Edit/activate notifications', '2026-10-19 18:09:59'),
(8, 'notifications.delete', 'notifications', 'delete', 'Delete notifications', '2026-10-19 18:09:59'),
(9, 'connect.view', 'connect', 'view', 'View connect group requests', '2026-10-19 18:09:59'),
(10, 'connect.create', 'connect', 'create', 'Create connect group entries', '2026-10-19 18:09:59'),
(11, 'connect.update', 'connect', 'update', 'Edit/mark read', '2026-10-19 18:09:59'),
(12, 'connect.follow_up', 'connect', 'follow_up', 'Mark follow-up', '2026-10-19 18:09:59'),
(13, 'connect.delete', 'connect', 'delete', 'Delete connect requests', '2026-10-19 18:09:59'),
(14, 'members.view', 'members', 'view', 'View members', '2026-10-19 18:09:59'),
(15, 'members.create', 'members', 'create', 'Create members', '2026-10-19 18:09:59'),
(16, 'members.update', 'members', 'update', 'Edit members', '2026-10-19 18:09:59'),
(17, 'members.follow_up', 'members', 'follow_up', 'Follow-up members', '2026-10-19 18:09:59'),
(18, 'members.delete', 'members', 'delete', 'Delete members', '2026-10-19 18:09:59'),
(19, 'dinner.view', 'dinner', 'view', 'View dinner registrations', '2026-10-19 18:09:59'),
(20, 'dinner.create', 'dinner', 'create', 'Create dinner registrations', '2026-10-19 18:09:59'),
(21, 'dinner.update', 'dinner', 'update', 'Edit dinner registrations', '2026-10-19 18:09:59'),
(22, 'dinner.follow_up', 'dinner', 'follow_up', 'Follow-up dinner registrations', '2026-10-19 18:09:59'),
(23, 'dinner.delete', 'dinner', 'delete', 'Delete dinner registrations', '2026-10-19 18:09:59'),
(24, 'contact.view', 'contact', 'view', 'View contact messages', '2026-10-19 18:09:59'),
(25, 'contact.update', 'contact', 'update', 'Update message status/notes', '2026-10-19 18:09:59'),
(26, 'contact.follow_up', 'contact', 'follow_up', 'Follow-up contact messages', '2026-10-19 18:09:59'),
(27, 'contact.delete', 'contact', 'delete', 'Delete contact messages', '2026-10-19 18:09:59'),
(28, 'offerings.view', 'offerings', 'view', 'View offerings/M-Pesa', '2026-10-19 18:09:59'),
(29, 'offerings.export', 'offerings', 'export', 'Export offerings', '2026-10-19 18:09:59'),
(30, 'offerings.delete', 'offerings', 'delete', 'Delete offerings', '2026-10-19 18:09:59'),
(31, 'admins.manage', 'admins', 'manage', 'Manage admin accounts', '2026-10-19 18:09:59'),
(32, 'roles.manage', 'roles', 'manage', 'Manage roles & permissions', '2026-10-19 18:09:59'),
(33, 'history.view', 'history', 'view', 'View audit history', '2026-10-19 18:09:59'),
(34, 'settings.theme', 'settings', 'theme', 'Change theme settings', '2026-10-19 18:09:59'),
(35, 'profile.self_edit', 'profile', 'self_edit', 'Edit own profile', '2026-10-19 18:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `reset_requests`
--

DROP TABLE IF EXISTS `reset_requests`;
CREATE TABLE IF NOT EXISTS `reset_requests` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_date` (`email`,`requested_at`),
  KEY `idx_ip_date` (`ip_address`,`requested_at`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reset_requests`
--

INSERT INTO `reset_requests` (`id`, `email`, `ip_address`, `requested_at`) VALUES
(8, 'mwangiscott09@gmail.com', '102.210.43.14', '2026-07-15 18:31:10'),
(7, 'mwangiscott09@gmail.com', '102.210.43.14', '2026-07-15 18:25:54'),
(6, 'mwangiscott09@gmail.com', '102.210.43.14', '2026-07-15 18:21:34'),
(5, 'scotdeveloper70@gmail.com', '102.210.40.230', '2026-03-24 13:57:48'),
(9, 'mwangiscott09@gmail.com', '102.210.43.14', '2026-07-15 19:06:59'),
(10, 'johnmachariamwangi123@gmail.com', '102.210.43.14', '2026-07-25 19:36:57'),
(11, 'johnmachariamwangi123@gmail.com', '102.210.43.14', '2026-07-27 15:16:01'),
(12, 'mwangimwangiscott09@gmail.com', '102.210.43.14', '2026-07-27 15:45:04'),
(13, 'mwangiscott09@gmail.com', '102.210.43.14', '2026-07-27 15:49:34'),
(14, 'johnmachariamwangi123@gmail.com', '102.210.43.14', '2026-08-16 11:35:37');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_system`, `created_at`) VALUES
(1, 'super_admin', 'Full access to all modules, admins, roles, and history.', 1, '2026-10-19 18:24:18'),
(2, 'admin', 'Manages content; cannot delete sensitive records or manage admins.', 1, '2026-10-19 18:24:18'),
(3, 'editor', 'Content editor; no deletes, no admin management.', 1, '2026-10-19 18:24:18');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_perm` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(1, 2),
(2, 2),
(1, 3),
(2, 3),
(1, 4),
(2, 4),
(1, 5),
(2, 5),
(3, 5),
(1, 6),
(2, 6),
(1, 7),
(2, 7),
(1, 8),
(2, 8),
(1, 9),
(2, 9),
(3, 9),
(1, 10),
(2, 10),
(3, 10),
(1, 11),
(2, 11),
(3, 11),
(1, 12),
(2, 12),
(3, 12),
(1, 13),
(2, 13),
(1, 14),
(2, 14),
(3, 14),
(1, 15),
(2, 15),
(3, 15),
(1, 16),
(2, 16),
(3, 16),
(1, 17),
(2, 17),
(3, 17),
(1, 18),
(1, 19),
(2, 19),
(3, 19),
(1, 20),
(2, 20),
(3, 20),
(1, 21),
(2, 21),
(3, 21),
(1, 22),
(2, 22),
(3, 22),
(1, 23),
(1, 24),
(2, 24),
(3, 24),
(1, 25),
(2, 25),
(3, 25),
(1, 26),
(2, 26),
(3, 26),
(1, 27),
(1, 28),
(2, 28),
(3, 28),
(1, 29),
(2, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(2, 34),
(3, 34),
(1, 35),
(2, 35),
(3, 35);

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

DROP TABLE IF EXISTS `security_events`;
CREATE TABLE IF NOT EXISTS `security_events` (
  `id` bigint NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `admin_id` int DEFAULT NULL,
  `username_attempted` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `detail` text COLLATE utf8mb4_general_ci,
  `created_at_utc` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`event_type`),
  KEY `idx_created` (`created_at_utc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL,
  `phone` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `offering_type` varchar(50) NOT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `mpesa_receipt` varchar(100) DEFAULT NULL,
  `transaction_date` varchar(20) DEFAULT NULL,
  `result_code` int DEFAULT NULL,
  `result_desc` text,
  `status` enum('Pending','Processing','Completed','Failed','Timeout','Cancelled') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_message` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `raw_callback` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `phone`, `amount`, `offering_type`, `merchant_request_id`, `checkout_request_id`, `mpesa_receipt`, `transaction_date`, `result_code`, `result_desc`, `status`, `submitted_at`, `customer_message`, `updated_at`, `raw_callback`) VALUES
(36, '254703940389', 1.00, 'Thanksgiving', 'd58a-4a7a-996c-b61167cc3f719672', 'ws_CO_17072026131823930703940389', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', '', '2026-07-17 10:18:23', 'Success. Request accepted for processing', '2026-07-17 14:17:12', NULL),
(37, '254703940389', 1.00, 'Offering', 'eb56-4ae5-baaa-f1873e4e734a290', 'ws_CO_17072026171713493703940389', NULL, NULL, NULL, NULL, '', '2026-07-17 14:17:13', 'Success. Request accepted for processing', NULL, NULL),
(38, '254703940389', 1.00, 'Offering', 'eb56-4ae5-baaa-f1873e4e734a296', 'ws_CO_17072026171725883703940389', NULL, NULL, NULL, NULL, '', '2026-07-17 14:17:25', 'Success. Request accepted for processing', NULL, NULL),
(39, '254757226870', 1.00, 'Thanksgiving', 'eb56-4ae5-baaa-f1873e4e734a603', 'ws_CO_17072026172105630757226870', NULL, NULL, 1037, 'No response from user.', '', '2026-07-17 14:21:05', 'Success. Request accepted for processing', '2026-07-17 14:21:32', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"eb56-4ae5-baaa-f1873e4e734a603\",\"CheckoutRequestID\":\"ws_CO_17072026172105630757226870\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(40, '254757226870', 1.00, 'Offering', 'eb56-4ae5-baaa-f1873e4e734a797', 'ws_CO_17072026172322927757226870', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-17 14:23:22', 'Success. Request accepted for processing', '2026-07-17 14:23:41', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"eb56-4ae5-baaa-f1873e4e734a797\",\"CheckoutRequestID\":\"ws_CO_17072026172322927757226870\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(41, '254703940389', 1.00, 'Thanksgiving', 'eb56-4ae5-baaa-f1873e4e734a1125', 'ws_CO_17072026172708707703940389', NULL, NULL, 1037, 'No response from user.', '', '2026-07-17 14:27:08', 'Success. Request accepted for processing', '2026-07-17 14:27:35', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"eb56-4ae5-baaa-f1873e4e734a1125\",\"CheckoutRequestID\":\"ws_CO_17072026172708707703940389\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(42, '254757226870', 1.00, 'Mission', 'd58a-4a7a-996c-b61167cc3f7155971', 'ws_CO_17072026173823177757226870', NULL, NULL, 1037, 'No response from user.', '', '2026-07-17 14:38:22', 'Success. Request accepted for processing', '2026-07-17 14:38:49', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7155971\",\"CheckoutRequestID\":\"ws_CO_17072026173823177757226870\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(43, '254757226870', 1.00, 'Projects', 'd58a-4a7a-996c-b61167cc3f7156223', 'ws_CO_17072026174139392757226870', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-17 14:41:39', 'Success. Request accepted for processing', '2026-07-17 14:41:58', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7156223\",\"CheckoutRequestID\":\"ws_CO_17072026174139392757226870\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(44, '254757226870', 1.00, 'Projects', 'd58a-4a7a-996c-b61167cc3f7156516', 'ws_CO_17072026174644973757226870', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-17 14:46:44', 'Success. Request accepted for processing', '2026-07-17 14:47:01', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7156516\",\"CheckoutRequestID\":\"ws_CO_17072026174644973757226870\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(45, '254703940389', 1.00, 'Offering', 'd58a-4a7a-996c-b61167cc3f7158706', 'ws_CO_17072026183554290703940389', NULL, NULL, 1037, 'No response from user.', '', '2026-07-17 15:35:54', 'Success. Request accepted for processing', '2026-07-17 15:36:20', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7158706\",\"CheckoutRequestID\":\"ws_CO_17072026183554290703940389\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(46, '254703940389', 1.00, 'Offering', 'eb56-4ae5-baaa-f1873e4e734a5109', 'ws_CO_17072026183917752703940389', NULL, NULL, 1037, 'DS timeout user cannot be reached.', '', '2026-07-17 15:39:17', 'Success. Request accepted for processing', '2026-07-17 15:39:19', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"eb56-4ae5-baaa-f1873e4e734a5109\",\"CheckoutRequestID\":\"ws_CO_17072026183917752703940389\",\"ResultCode\":1037,\"ResultDesc\":\"DS timeout user cannot be reached.\"}}}'),
(47, '254703940389', 1.00, 'Offering', 'd58a-4a7a-996c-b61167cc3f7159095', 'ws_CO_17072026184326677703940389', NULL, NULL, 1037, 'No response from user.', '', '2026-07-17 15:43:26', 'Success. Request accepted for processing', '2026-07-17 15:43:53', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7159095\",\"CheckoutRequestID\":\"ws_CO_17072026184326677703940389\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(48, '254757226870', 1.00, 'FirstFruit', 'd58a-4a7a-996c-b61167cc3f7159105', 'ws_CO_17072026184347181757226870', NULL, NULL, 1037, 'DS timeout user cannot be reached.', '', '2026-07-17 15:43:47', 'Success. Request accepted for processing', '2026-07-17 15:44:03', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7159105\",\"CheckoutRequestID\":\"ws_CO_17072026184347181757226870\",\"ResultCode\":1037,\"ResultDesc\":\"DS timeout user cannot be reached.\"}}}'),
(49, '254703940389', 1.00, 'Offering', 'd58a-4a7a-996c-b61167cc3f7159371', 'ws_CO_17072026184901384703940389', NULL, NULL, NULL, NULL, '', '2026-07-17 15:49:01', 'Success. Request accepted for processing', NULL, NULL),
(50, '254703940389', 1.00, 'Offering', 'eb56-4ae5-baaa-f1873e4e734a5641', 'ws_CO_17072026184915623703940389', NULL, NULL, NULL, NULL, '', '2026-07-17 15:49:15', 'Success. Request accepted for processing', NULL, NULL),
(51, '254757226870', 1.00, 'Offering', 'd58a-4a7a-996c-b61167cc3f7159565', 'ws_CO_17072026185231846757226870', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-17 15:52:31', 'Success. Request accepted for processing', '2026-07-17 15:52:44', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"d58a-4a7a-996c-b61167cc3f7159565\",\"CheckoutRequestID\":\"ws_CO_17072026185231846757226870\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(52, '254757226870', 1.00, 'Offering', '1294-459c-9072-18e604f936d580122', 'ws_CO_18072026113005300757226870', NULL, NULL, NULL, NULL, '', '2026-07-18 08:30:04', 'Success. Request accepted for processing', NULL, NULL),
(53, '254703940389', 1.00, 'Mission', 'baa9-4152-bfff-5b6c7760b4658217', 'ws_CO_18072026114952673703940389', NULL, NULL, NULL, NULL, '', '2026-07-18 08:49:52', 'Success. Request accepted for processing', NULL, NULL),
(54, '254703940389', 1.00, 'Mission', 'baa9-4152-bfff-5b6c7760b4658352', 'ws_CO_18072026115424190703940389', NULL, NULL, NULL, NULL, '', '2026-07-18 08:54:23', 'Success. Request accepted for processing', NULL, NULL),
(55, '254703940389', 1.00, 'Mission', '00cb-4960-9494-1e5e5ce1fd0e547', 'ws_CO_18072026115848336703940389', 'UGIH4BT4KI', '2026-07-18 11:58:58', 0, 'The service request is processed successfully.', '', '2026-07-18 08:58:47', 'Success. Request accepted for processing', '2026-07-18 08:58:59', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"00cb-4960-9494-1e5e5ce1fd0e547\",\"CheckoutRequestID\":\"ws_CO_18072026115848336703940389\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\",\"'),
(56, '254757226870', 1.00, 'FirstFruit', 'baa9-4152-bfff-5b6c7760b4658865', 'ws_CO_18072026121120955757226870', 'UGIH1BMBV2', '2026-07-18 12:11:35', 0, 'The service request is processed successfully.', '', '2026-07-18 09:11:20', 'Success. Request accepted for processing', '2026-07-18 09:11:36', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"baa9-4152-bfff-5b6c7760b4658865\",\"CheckoutRequestID\":\"ws_CO_18072026121120955757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\",'),
(57, '254757226870', 1.00, 'FirstFruit', '1294-459c-9072-18e604f936d582167', 'ws_CO_18072026121438539757226870', 'UGIH1BMIFJ', '2026-07-18 12:14:50', 0, 'The service request is processed successfully.', '', '2026-07-18 09:14:37', 'Success. Request accepted for processing', '2026-07-18 09:14:51', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d582167\",\"CheckoutRequestID\":\"ws_CO_18072026121438539757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(58, '254757226870', 1.00, 'FirstFruit', '1294-459c-9072-18e604f936d582564', 'ws_CO_18072026122346183757226870', 'UGIH1BMG9F', '2026-07-18 12:24:00', 0, 'The service request is processed successfully.', '', '2026-07-18 09:23:45', 'Success. Request accepted for processing', '2026-07-18 09:24:00', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d582564\",\"CheckoutRequestID\":\"ws_CO_18072026122346183757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(59, '254757226870', 1.00, 'Thanksgiving', 'baa9-4152-bfff-5b6c7760b46510078', 'ws_CO_18072026130107220757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', '', '2026-07-18 10:01:06', 'Success. Request accepted for processing', '2026-07-18 10:11:50', NULL),
(60, '254757226870', 1.00, 'Offering', 'baa9-4152-bfff-5b6c7760b46510130', 'ws_CO_18072026130341098757226870', 'UGIH1BMKSL', '2026-07-18 13:03:54', 0, 'The service request is processed successfully.', '', '2026-07-18 10:03:39', 'Success. Request accepted for processing', '2026-07-18 10:03:55', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"baa9-4152-bfff-5b6c7760b46510130\",\"CheckoutRequestID\":\"ws_CO_18072026130341098757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(61, '254757226870', 1.00, 'Tithe', 'baa9-4152-bfff-5b6c7760b46510195', 'ws_CO_18072026130541772757226870', 'UGIH1BMHVZ', '2026-07-18 13:06:00', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 10:05:40', 'Success. Request accepted for processing', '2026-07-18 10:06:00', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"baa9-4152-bfff-5b6c7760b46510195\",\"CheckoutRequestID\":\"ws_CO_18072026130541772757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(62, '254703940389', 1.00, 'Offering', 'baa9-4152-bfff-5b6c7760b46510272', 'ws_CO_18072026130754211703940389', 'UGIH4BTF0I', '2026-07-18 13:08:08', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 10:07:52', 'Success. Request accepted for processing', '2026-07-18 10:08:08', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"baa9-4152-bfff-5b6c7760b46510272\",\"CheckoutRequestID\":\"ws_CO_18072026130754211703940389\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(63, '254757226870', 1.00, 'Offering', '6706-4013-9c32-ede0dd8cb8093462', 'ws_CO_18072026131153197757226870', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-18 10:11:51', 'Success. Request accepted for processing', '2026-07-18 10:12:01', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"6706-4013-9c32-ede0dd8cb8093462\",\"CheckoutRequestID\":\"ws_CO_18072026131153197757226870\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(64, '254757226870', 1.00, 'Offering', '1294-459c-9072-18e604f936d584826', 'ws_CO_18072026131730626757226870', NULL, NULL, 2001, 'The initiator information is invalid.', 'Failed', '2026-07-18 10:17:29', 'Success. Request accepted for processing', '2026-07-18 10:17:44', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d584826\",\"CheckoutRequestID\":\"ws_CO_18072026131730626757226870\",\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\"}}}'),
(65, '254757226870', 1.00, 'Offering', '1294-459c-9072-18e604f936d584894', 'ws_CO_18072026131921880757226870', NULL, NULL, 1037, 'No response from user.', '', '2026-07-18 10:19:20', 'Success. Request accepted for processing', '2026-07-18 10:19:46', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d584894\",\"CheckoutRequestID\":\"ws_CO_18072026131921880757226870\",\"ResultCode\":1037,\"ResultDesc\":\"No response from user.\"}}}'),
(66, '254703940389', 1.00, 'Thanksgiving', '6706-4013-9c32-ede0dd8cb8094871', 'ws_CO_18072026134312946703940389', 'UGIH4BTEDM', '2026-07-18 13:43:38', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 10:43:11', 'Success. Request accepted for processing', '2026-07-18 10:43:38', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"6706-4013-9c32-ede0dd8cb8094871\",\"CheckoutRequestID\":\"ws_CO_18072026134312946703940389\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\",'),
(67, '254703940389', 20.00, 'FirstFruit', '6706-4013-9c32-ede0dd8cb8098856', 'ws_CO_18072026151647087703940389', NULL, NULL, 1032, 'Request Cancelled by user.', '', '2026-07-18 12:16:45', 'Success. Request accepted for processing', '2026-07-18 12:17:08', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"6706-4013-9c32-ede0dd8cb8098856\",\"CheckoutRequestID\":\"ws_CO_18072026151647087703940389\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(68, '254703940389', 20.00, 'FirstFruit', '1294-459c-9072-18e604f936d590166', 'ws_CO_18072026152026592703940389', NULL, NULL, 1032, 'Request Cancelled by user.', 'Cancelled', '2026-07-18 12:20:25', 'Success. Request accepted for processing', '2026-07-18 12:20:42', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d590166\",\"CheckoutRequestID\":\"ws_CO_18072026152026592703940389\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(69, '254703940389', 1.00, 'Thanksgiving', '00cb-4960-9494-1e5e5ce1fd0e5809', 'ws_CO_18072026152101882703940389', NULL, NULL, 1032, 'Request Cancelled by user.', 'Cancelled', '2026-07-18 12:21:00', 'Success. Request accepted for processing', '2026-07-18 12:21:07', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"00cb-4960-9494-1e5e5ce1fd0e5809\",\"CheckoutRequestID\":\"ws_CO_18072026152101882703940389\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(70, '254703940389', 15.00, 'Thanksgiving', '6706-4013-9c32-ede0dd8cb8099030', 'ws_CO_18072026152126962703940389', NULL, NULL, 1032, 'Request Cancelled by user.', 'Cancelled', '2026-07-18 12:21:25', 'Success. Request accepted for processing', '2026-07-18 12:21:47', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"6706-4013-9c32-ede0dd8cb8099030\",\"CheckoutRequestID\":\"ws_CO_18072026152126962703940389\",\"ResultCode\":1032,\"ResultDesc\":\"Request Cancelled by user.\"}}}'),
(71, '254703940389', 15.00, 'Thanksgiving', '00cb-4960-9494-1e5e5ce1fd0e5830', 'ws_CO_18072026152214319703940389', NULL, NULL, 1, 'The balance is insufficient for the transaction.', 'Failed', '2026-07-18 12:22:13', 'Success. Request accepted for processing', '2026-07-18 12:22:27', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"00cb-4960-9494-1e5e5ce1fd0e5830\",\"CheckoutRequestID\":\"ws_CO_18072026152214319703940389\",\"ResultCode\":1,\"ResultDesc\":\"The balance is insufficient for the transaction.\"}}}'),
(72, '254757226870', 1.00, 'Offering', '1294-459c-9072-18e604f936d593304', 'ws_CO_18072026164323815757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-18 13:43:22', 'Success. Request accepted for processing', '2026-07-18 14:04:18', NULL),
(73, '254757226870', 1.00, 'Mission', '1294-459c-9072-18e604f936d593440', 'ws_CO_18072026164641140757226870', 'UGIH1BND5G', '2026-07-18 16:46:58', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 13:46:39', 'Success. Request accepted for processing', '2026-07-18 13:46:58', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"1294-459c-9072-18e604f936d593440\",\"CheckoutRequestID\":\"ws_CO_18072026164641140757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(74, '254757226870', 1.00, 'FirstFruit', 'baa9-4152-bfff-5b6c7760b46516363', 'ws_CO_18072026170612267757226870', 'UGIH1BNIPF', '2026-07-18 17:06:24', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 14:06:11', 'Success. Request accepted for processing', '2026-07-18 14:06:24', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"baa9-4152-bfff-5b6c7760b46516363\",\"CheckoutRequestID\":\"ws_CO_18072026170612267757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\"'),
(75, '254757226870', 1.00, 'FirstFruit', '00cb-4960-9494-1e5e5ce1fd0e8902', 'ws_CO_18072026171705564757226870', 'UGIH1BNK9R', '2026-07-18 17:17:26', 0, 'The service request is processed successfully.', 'Completed', '2026-07-18 14:17:04', 'Success. Request accepted for processing', '2026-07-18 14:17:26', '{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"00cb-4960-9494-1e5e5ce1fd0e8902\",\"CheckoutRequestID\":\"ws_CO_18072026171705564757226870\",\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\",'),
(76, '254757226870', 1.00, 'Offering', '00cb-4960-9494-1e5e5ce1fd0e9754', 'ws_CO_18072026174351069757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-18 14:43:51', 'Success. Request accepted for processing', '2026-07-18 14:54:32', NULL),
(77, '254757226870', 1.00, 'FirstFruit', '1294-459c-9072-18e604f936d597805', 'ws_CO_18072026174644197757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-18 14:46:44', 'Success. Request accepted for processing', '2026-07-18 14:54:32', NULL),
(78, '254757226870', 1.00, 'Thanksgiving', 'baa9-4152-bfff-5b6c7760b46517906', 'ws_CO_18072026174813546757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-18 14:48:13', 'Success. Request accepted for processing', '2026-07-18 14:54:32', NULL),
(79, '254757226870', 1.00, 'FirstFruit', '1294-459c-9072-18e604f936d598243', 'ws_CO_18072026175435385757226870', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-18 14:54:35', 'Success. Request accepted for processing', '2026-10-06 20:39:59', NULL),
(80, '254711959651', 10.00, 'Tithe', '7cd0-4a28-8d04-cb27fe4732c647180', 'ws_CO_19072026174032451711959651', NULL, NULL, NULL, NULL, 'Processing', '2026-07-19 14:40:32', 'Success. Request accepted for processing', NULL, NULL),
(81, '254703940389', 1.00, 'Mission', '6839-428d-8589-2bcb00296f3756983', 'ws_CO_19072026201534840703940389', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-19 17:15:35', 'Success. Request accepted for processing', '2026-07-19 19:53:44', NULL),
(82, '254703940389', 1.00, 'Tithe', '6839-428d-8589-2bcb00296f3767386', 'ws_CO_19072026225346915703940389', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-19 19:53:46', 'Success. Request accepted for processing', '2026-07-27 18:20:01', NULL),
(83, '254789665544', 37489.00, 'Thanksgiving', 'b824-4eab-b51c-485633e35ec2128052', 'ws_CO_22072026202530142789665544', NULL, NULL, NULL, NULL, 'Processing', '2026-07-22 17:25:30', 'Success. Request accepted for processing', NULL, NULL),
(84, '254703940389', 1.00, 'Mission', '77aa-47e7-8a39-5426eb3fbe8137654', 'ws_CO_27072026212003803703940389', NULL, NULL, 1037, 'STK Push request expired after 5 minutes.', 'Timeout', '2026-07-27 18:20:03', 'Success. Request accepted for processing', '2026-10-11 10:21:36', NULL),
(85, '254706852926', 1.00, 'Tithe', 'ab71-4ea3-bc42-60115f59166519964', 'ws_CO_110820261917534706852926', NULL, NULL, NULL, NULL, 'Processing', '2026-08-11 16:17:52', 'Success. Request accepted for processing', NULL, NULL),
(86, '254757226870', 100000.00, 'Mission', '07e0-40a2-8aa6-a85c104d97cc696322', 'ws_CO_061020262340026757226870', NULL, NULL, NULL, NULL, 'Processing', '2026-10-06 20:40:02', 'Success. Request accepted for processing', NULL, NULL),
(87, '254730940389', 1.00, 'Tithe', '4e7d-425c-aa3e-9b62f1dcf8a21481300', 'ws_CO_111020261320564730940389', NULL, NULL, NULL, NULL, 'Processing', '2026-10-11 10:20:56', 'Success. Request accepted for processing', NULL, NULL),
(88, '254703940389', 1.00, 'Tithe', 'f57c-4d7a-b000-6a48cacb5dd795688', 'ws_CO_111020261321402703940389', NULL, NULL, NULL, NULL, 'Processing', '2026-10-11 10:21:45', 'Success. Request accepted for processing', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `violation_logs`
--

DROP TABLE IF EXISTS `violation_logs`;
CREATE TABLE IF NOT EXISTS `violation_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `violation_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `violation_logs`
--

INSERT INTO `violation_logs` (`id`, `admin_id`, `violation_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 11, 'unauthorized_attempt', 'Attempted to access: view_dashboard - You do not have permission to perform this action.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-08-20 15:38:29'),
(2, 11, 'unauthorized_attempt', 'Attempted to access: view_dashboard - You do not have permission to perform this action.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-08-20 15:44:16'),
(3, 11, 'unauthorized_attempt', 'Attempted to access: view_dashboard - You do not have permission to perform this action.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-23 19:11:06'),
(4, 11, 'unauthorized_attempt', 'Attempted to access: view_dashboard - You do not have permission to perform this action.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-23 19:11:31');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
