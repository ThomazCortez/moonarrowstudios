-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 04, 2025 at 03:12 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `moonarrowstudios`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_deletions`
--

DROP TABLE IF EXISTS `account_deletions`;
CREATE TABLE IF NOT EXISTS `account_deletions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime GENERATED ALWAYS AS ((`created_at` + interval 24 hour)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_deletions`
--

INSERT INTO `account_deletions` (`id`, `user_id`, `email`, `token`, `created_at`) VALUES
(2, 30, 'thomazbarrago@gmail.com', '3c254cd43be88b4c59813e3fc28bfa32259f2e13333ef8cbab6705022897b114', '2025-06-01 04:18:50');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
CREATE TABLE IF NOT EXISTS `assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `hashtags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `videos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int NOT NULL,
  `upvotes` int DEFAULT '0',
  `downvotes` int DEFAULT '0',
  `preview_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('published','draft','hidden') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'published',
  `views` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reported_count` int DEFAULT '0',
  `asset_type` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_posts_categories` (`category_id`),
  KEY `fk_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `title`, `content`, `category_id`, `hashtags`, `images`, `created_at`, `videos`, `user_id`, `upvotes`, `downvotes`, `preview_image`, `asset_file`, `status`, `views`, `updated_at`, `reported_count`, `asset_type`) VALUES
(130, 'Rain VFX', '<p><br></p>', 9, '#rain #vfx', '[]', '2025-06-04 00:46:01', '[]', 36, 0, 0, '', 'uploads/assets/Rain Overlay Transparent Video.mp4', 'published', 21, '2025-06-04 01:42:08', 0, ''),
(131, 'Samba Dance', '<p>It likes to dance.</p>', 8, '#samba #dance #animation', '[]', '2025-06-04 00:55:25', '[]', 36, 0, 0, '', 'uploads/assets/Samba Dancing.fbx', 'published', 9, '2025-06-04 01:30:28', 0, ''),
(132, 'Jazzy Chase Theme', '<p><br></p>', 11, '#jazz #chase #theme', '[]', '2025-06-04 01:02:59', '[]', 38, 0, 0, '', 'uploads/assets/untitled  - caffeine tutorial.mp3', 'published', 2, '2025-06-04 01:30:34', 0, ''),
(133, 'Wooden Floor Texture', '<p><br></p>', 6, '#wood #floor #texture', '[]', '2025-06-04 01:07:15', '[]', 38, 0, 0, 'uploads/previews/WoodFloor054_1K-JPG_Color.jpg', 'uploads/assets/WoodFloor054_1K-JPG_Color.jpg', 'published', 3, '2025-06-04 03:07:25', 0, ''),
(134, 'MP7 Gun', '<p><br></p>', 5, '#mp7 #gun #smg #model', '[]', '2025-06-04 01:09:02', '[]', 38, 1, 0, '', 'uploads/assets/low-poly_hk_mp7_a1.glb', 'published', 8, '2025-06-04 03:07:29', 0, ''),
(135, 'Ultrakill Font', '<p>Mankind is <strong>DEAD.</strong></p><p>Blood is <strong>FUEL.</strong></p><p>Hell is <strong>FULL.</strong></p>', 17, '#ultrakill #font', '[]', '2025-06-04 01:33:04', '[]', 30, 1, 0, '', 'uploads/assets/VCR_OSD_MONO_1.001.ttf', 'published', 7, '2025-06-04 02:06:57', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
CREATE TABLE IF NOT EXISTS `asset_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `name`) VALUES
(1, '2D Sprites'),
(5, '3D Models'),
(6, 'Textures & Materials'),
(7, 'UI & Icons'),
(8, 'Animations & Rigs'),
(9, 'VFX (Visual Effects)'),
(10, 'Sound Effects (SFX)'),
(11, 'Background Music'),
(12, 'Voiceovers'),
(15, 'Particle Effects'),
(17, 'Game-Specific Fonts'),
(18, 'HUD Elements');

-- --------------------------------------------------------

--
-- Table structure for table `asset_votes`
--

DROP TABLE IF EXISTS `asset_votes`;
CREATE TABLE IF NOT EXISTS `asset_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `asset_id` int NOT NULL,
  `vote_type` enum('upvote','downvote') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`asset_id`),
  KEY `asset_id` (`asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_votes`
--

INSERT INTO `asset_votes` (`id`, `user_id`, `asset_id`, `vote_type`) VALUES
(130, 30, 80, 'downvote'),
(136, 30, 119, 'downvote'),
(137, 30, 134, 'upvote'),
(139, 30, 135, 'upvote');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'General'),
(5, 'Game Design'),
(6, 'Programming'),
(7, 'Art and Animation'),
(8, 'Sound and Music'),
(9, 'Game Engines'),
(10, 'Scripting and Tools'),
(11, 'Game Marketing'),
(12, 'Game Monetization'),
(13, 'Indie Game Development'),
(14, 'VR/AR Development'),
(15, 'Game Testing and QA'),
(16, 'Game Modding'),
(17, 'Game Localization'),
(18, 'Job and Team Building'),
(19, 'Game Development Resources'),
(20, 'Game Development Events'),
(21, 'Game Projects'),
(22, 'Off-Topic'),
(23, 'Support and Troubleshooting');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `parent_id` int DEFAULT NULL,
  `upvotes` int NOT NULL DEFAULT '0',
  `downvotes` int NOT NULL DEFAULT '0',
  `status` enum('published','draft','hidden') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'published',
  `reported_count` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `edited_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`, `parent_id`, `upvotes`, `downvotes`, `status`, `reported_count`, `updated_at`, `edited_at`) VALUES
(106, 102, 30, '<p>Thank you for sharing!</p>', '2025-06-04 04:11:48', NULL, 0, 0, 'published', 0, '2025-06-04 03:11:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments_asset`
--

DROP TABLE IF EXISTS `comments_asset`;
CREATE TABLE IF NOT EXISTS `comments_asset` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `parent_id` int DEFAULT NULL,
  `upvotes` int NOT NULL DEFAULT '0',
  `downvotes` int NOT NULL DEFAULT '0',
  `status` enum('published','draft','hidden') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'published',
  `reported_count` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `edited_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments_asset`
--

INSERT INTO `comments_asset` (`id`, `asset_id`, `user_id`, `content`, `created_at`, `parent_id`, `upvotes`, `downvotes`, `status`, `reported_count`, `updated_at`, `edited_at`) VALUES
(87, 134, 30, '<p>Nice 3D model, thanks!</p>', '2025-06-04 02:14:14', NULL, 1, 0, 'published', 0, '2025-06-04 01:18:28', NULL),
(88, 134, 38, '<p>Thanks :D</p>', '2025-06-04 02:18:56', 87, 0, 0, 'published', 0, '2025-06-04 01:18:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comment_asset_votes`
--

DROP TABLE IF EXISTS `comment_asset_votes`;
CREATE TABLE IF NOT EXISTS `comment_asset_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `comment_id` int NOT NULL,
  `vote_type` enum('upvote','downvote') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`user_id`,`comment_id`),
  KEY `comment_id` (`comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_asset_votes`
--

INSERT INTO `comment_asset_votes` (`id`, `user_id`, `comment_id`, `vote_type`, `created_at`) VALUES
(29, 30, 72, 'upvote', '2025-02-27 08:56:56'),
(31, 30, 87, 'upvote', '2025-06-04 01:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `comment_votes`
--

DROP TABLE IF EXISTS `comment_votes`;
CREATE TABLE IF NOT EXISTS `comment_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `comment_id` int NOT NULL,
  `vote_type` enum('upvote','downvote') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`user_id`,`comment_id`),
  KEY `comment_id` (`comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_votes`
--

INSERT INTO `comment_votes` (`id`, `user_id`, `comment_id`, `vote_type`, `created_at`) VALUES
(3, 26, 29, 'upvote', '2025-01-21 10:29:18'),
(17, 26, 50, 'downvote', '2025-01-21 20:17:40'),
(18, 26, 54, 'upvote', '2025-01-23 20:52:59'),
(23, 19, 60, 'upvote', '2025-01-28 18:44:33'),
(32, 30, 66, 'downvote', '2025-02-27 08:52:21'),
(33, 30, 67, 'upvote', '2025-02-27 08:52:22'),
(34, 30, 68, 'downvote', '2025-02-27 08:52:23'),
(39, 30, 65, 'downvote', '2025-02-27 08:52:41'),
(40, 19, 75, 'upvote', '2025-05-17 23:49:15'),
(42, 19, 80, 'upvote', '2025-05-22 10:51:00'),
(43, 30, 88, 'upvote', '2025-05-31 13:02:03');

-- --------------------------------------------------------

--
-- Table structure for table `email_changes`
--

DROP TABLE IF EXISTS `email_changes`;
CREATE TABLE IF NOT EXISTS `email_changes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `new_email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_email_changes_users` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_changes`
--

INSERT INTO `email_changes` (`id`, `user_id`, `new_email`, `token`, `created_at`) VALUES
(1, 33, 'colortest12345@gmail.com', '15dc907398954e58c5715af2c2275c1caf6b9c8bbb2ccb2434143a732914b7a0', '2025-05-28 18:10:13'),
(2, 30, 'thomazcfb@gmail.com', '5c2ecbe1979ddb1c0c81632b477dab430c51a34dec9c32ac87d6a418e8ca26ab', '2025-05-28 18:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`,`following_id`),
  KEY `fk_follows_following` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`follower_id`, `following_id`, `created_at`) VALUES
(19, 19, '2025-05-18 13:17:54'),
(19, 30, '2025-05-27 09:19:30'),
(30, 19, '2025-05-06 00:09:09'),
(30, 31, '2025-06-01 22:45:54'),
(30, 32, '2025-05-31 15:23:34'),
(31, 19, '2025-04-29 09:25:47'),
(31, 30, '2025-04-28 23:11:56'),
(32, 30, '2025-05-29 11:17:43'),
(33, 30, '2025-05-29 11:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `purpose` enum('reset','change') COLLATE utf8mb4_general_ci DEFAULT 'reset',
  `new_password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`(250)),
  KEY `token` (`token`),
  KEY `fk_password_resets_users` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `purpose`, `new_password`, `created_at`) VALUES
(10, 'thomazbarrago@gmail.com', '0af739ae0fe3ec98bfe742fc8b4525258b8779205aa4a86b06552478c05e573e', 'reset', NULL, '2024-12-01 23:01:27'),
(11, 'thomazbarrago@gmail.com', 'e145eac2b2de24d005f233c4686ee448456d009165acfd9c9228f05408af3505', 'reset', NULL, '2024-12-01 23:04:21'),
(15, 'thomazbarrago@gmail.com', '7b61562f9131790435631e01dd39aca37a6eb28c326424ca3767980f1416d5af', 'reset', NULL, '2024-12-01 23:28:37'),
(16, 'thomazbarrago@gmail.com', '0a0654ef25cae8794567f82d67ca5a55e4536a971cbe1213352cf7c1a379d976', 'reset', NULL, '2024-12-01 23:29:06'),
(17, 'thomazbarrago@gmail.com', '2d8a1b4f61fa1f24905575b4ec1c8c41fe29981a5fbc5300d9b77ee1419a6e1b', 'reset', NULL, '2024-12-01 23:29:54'),
(18, 'thomazbarrago@gmail.com', 'd9c1675b966781192c31ee253e0ba1adc720715cdb3414e57c2ba0580a40d1c8', 'reset', NULL, '2024-12-01 23:30:19'),
(22, 'thomazbarrago@gmail.com', 'c6a32518530b2ac0006b7a554f21cab8202087aa84e2e2cb5a61376ecbcd3a7b', 'reset', NULL, '2024-12-28 22:59:48'),
(23, 'thomazbarrago@gmail.com', '468a14e250b4aa11e3d0bb2114e7f62d780c987d3f36109066932320e3db5995', 'reset', NULL, '2024-12-28 23:04:41'),
(24, 'thomazbarrago@gmail.com', 'ff3145bafa98deab10ddf6e74de674f83b2cbbf91221275548fb29b54805e5d4', 'reset', NULL, '2024-12-28 23:05:39'),
(28, 'thomazbarrago@gmail.com', '87030be9dadf773218f8bd259a900391b059cae417480ce50dce0810ad02c5be', 'reset', NULL, '2024-12-30 22:28:34'),
(29, 'thomazbarrago@gmail.com', '4f3b22a80b42e7aeeb10b8e54b5fb0fdb73fcb196f8aaa20699d4a1431ed09dd', 'reset', NULL, '2024-12-30 23:10:27'),
(36, 'thomazbarrago@gmail.com', 'ace925ed753623095ff4776ee8c02cd703b1ac60ed3a4250316b04a41d937a14', 'reset', NULL, '2025-01-21 22:36:54'),
(38, 'thomazbarrago@gmail.com', '056f30712a7bde03b0edcec9a1234a0109547e97d2273e7f561185bbd0b93bfd', 'reset', NULL, '2025-01-23 22:56:37'),
(40, 'thomazbarrago@gmail.com', '0974ea1a407dbdf8957ea77bdbf053266fbde0315eb18b4a924aa7cb98eb1451', 'reset', NULL, '2025-01-26 23:33:43'),
(41, 'thomazbarrago@gmail.com', '99cae380671e56b4b9743b9bd3baa42c9bb5a2db4c67f99dea91a407cda125f8', 'reset', NULL, '2025-01-26 23:36:13'),
(46, 'thomazbarrago@gmail.com', 'f1fb8e14e3e8d7ec9d0893c1e74f6b28d7674e16b0309f0604fe060e4683c3df', 'reset', NULL, '2025-05-28 17:32:45'),
(47, 'thomazbarrago@gmail.com', 'cff16cc6bf89a46733f23fe3666f7680d8bf083b2716a64093e10f53cba4793e', 'reset', NULL, '2025-05-28 17:37:32'),
(48, 'thomazbarrago@gmail.com', '42403e23bbb19c0657542b750d6889128f0c32b2e810781c0b366fcc6b7ec9de', 'change', '$2y$10$lbLens6qGD/camFI4R4nO.TBgHSBEQV8XC9pewsyHjHB/xJDniCmG', '2025-05-28 17:58:25'),
(49, 'thomazbarrago@gmail.com', '24290543d3f056ac603fcfa10c142d44a6d42320f54a80c44a754109c80ae392', 'change', '$2y$10$SsHcanf4ITHH0ioARk2TaumqnlL.Snr6bpdS9Ut.yRNbiNB53n0/.', '2025-05-28 17:58:36'),
(50, 'thomazbarrago@gmail.com', '9af79d174af9303578761fe152b2ae58dd13ec919eb5534783794beaffdcacc1', 'change', '$2y$10$R1.PQck8PE9V2sbDxCNfx.z3YuivJfijfjO27rTrufff2w5yEP2Gu', '2025-05-28 17:58:53'),
(51, 'thomazbarrago@gmail.com', '4ab3a3bb18f2af00606bb9f1900b87b3d6d98c9319721bc9f9d4848b532c376f', 'change', '$2y$10$b2qaP97HpjVCB793VPEvXOFcYunFaOavQpgL9voxVg0EGNEmLqEVm', '2025-05-28 17:59:05'),
(52, 'thomazbarrago@gmail.com', '2ee384d917caa027a826834af744cf61586b102e439b63b0fa23331ef53463a9', 'change', '$2y$10$VtJyt.RRnx.EgT0XoquQ6OlYtq74LKJxGVsKMylse7TshudRROxs.', '2025-05-28 18:04:19'),
(54, 'sirkazzio@gmail.com', '8f9fa63d3e623b27ff113fd17091bab276e35e337d9840fa10b88ee8f7a46489', 'change', '$2y$10$JrOthQKl1BK3rBeXwz5xQu4qd61mgu6f7erjb212iFpTRmjKEZ0W2', '2025-06-01 04:24:28'),
(55, 'thomazbarrago@gmail.com', '5b240e884bf4e7e7c2ae5695d747d2663292e565146e572e1d9ad00e0ed05b9a', 'reset', NULL, '2025-06-04 01:35:02');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `hashtags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `videos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int NOT NULL,
  `upvotes` int DEFAULT '0',
  `downvotes` int DEFAULT '0',
  `status` enum('published','draft','hidden') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'published',
  `views` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reported_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_posts_categories` (`category_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `content`, `category_id`, `hashtags`, `images`, `created_at`, `videos`, `user_id`, `upvotes`, `downvotes`, `status`, `views`, `updated_at`, `reported_count`) VALUES
(100, 'How Do I Optimize My Unity Game for Mobile?', '<p>Hey everyone, I\'m working on a 2D platformer in Unity for Android/iOS and it\'s struggling on lower-end phones. </p><p><br></p><p>I\'ve already reduced texture sizes and disabled real-time lights, but the performance is still not where I want it.</p><p><br></p><p>Here’s a quick look at how I’m currently limiting frame rate and resolution:</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">void Start() {\r\n&nbsp; &nbsp; Application.targetFrameRate = 30;\r\n&nbsp; &nbsp; Screen.SetResolution(960, 540, true);\r\n}\r\n</pre><p><br></p><p>Is this a good approach? What else should I tweak to improve performance, especially for older devices?</p>', 9, '#godot #mobile #optimization', '[]', '2025-06-04 00:09:36', '[]', 38, 0, 0, 'published', 3, '2025-06-04 01:30:17', 0),
(101, 'Feedback Needed on My Idle Animation Loop – Too Stiff?', '<h3>Hey artists and animators! </h3><p>I’ve been working on a pixel art idle animation for the main character in my indie action game (a monkey, lol). </p><p>I’m aiming for a subtle idle loop, but it’s feeling kind of stiff or unnatural.</p><p><strong>Here\'s the current frame sequence I’m using (8 frames total).</strong></p>', 7, '#monkey #idle #animation #2d', '[\"uploads\\/images\\/MonkeIdle.gif\",\"php\\/uploads\\/images\\/683f927a3f7b8_1748996730.png\"]', '2025-06-04 00:14:45', '[]', 38, 0, 0, 'published', 9, '2025-06-04 01:30:10', 0),
(102, 'Super Quick Character Animation Setup in Unity', '<p>Just found this really helpful video: <strong><em>Unity Tutorial: Animate your character in less than 3 min (idle, walk, jump)</em></strong></p><p>It covers setting up idle, walk, and jump animations using Animator and transitions — perfect if you\'re just getting started with character animation in Unity.</p><p>Anyone know other quick tutorials like this? Drop links! 👇</p>', 7, '#unity #animation #sprite #tutorial', '[]', '2025-06-04 00:32:46', '[\"uploads\\/videos\\/Unity Tutorial Animate your character in less than 3 min (idle, walk, jump).mp4\"]', 36, 0, 0, 'published', 9, '2025-06-04 03:11:49', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_votes`
--

DROP TABLE IF EXISTS `post_votes`;
CREATE TABLE IF NOT EXISTS `post_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `vote_type` enum('upvote','downvote') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`post_id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_votes`
--

INSERT INTO `post_votes` (`id`, `user_id`, `post_id`, `vote_type`) VALUES
(42, 26, 40, 'downvote'),
(44, 19, 40, 'downvote'),
(45, 26, 39, 'upvote'),
(74, 26, 45, 'upvote'),
(99, 26, 51, 'upvote'),
(102, 19, 54, 'upvote'),
(103, 30, 52, 'upvote'),
(105, 30, 66, 'upvote'),
(108, 31, 74, 'upvote'),
(121, 30, 85, 'upvote'),
(122, 30, 82, 'upvote'),
(123, 30, 79, 'upvote');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_id` int NOT NULL,
  `content_type` enum('post','asset','comment','reply') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content_id` int NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reported_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `content_type`, `content_id`, `reason`, `details`, `reported_at`, `resolved`) VALUES
(1, 19, 'post', 78, 'Other', 'Image could be better...', '2025-04-07 15:25:13', 0),
(2, 19, 'post', 92, 'Other', 'BRO THIS IS A VIRUS! DELETE DELETE DELEEEEETE!', '2025-04-07 15:30:17', 0),
(3, 19, 'post', 92, 'Other', 'BRO THIS IS A VIRUS! DELETE DELETE DELEEEEETE! AGAIN V2!', '2025-04-07 15:31:14', 0),
(4, 19, 'post', 92, 'Other', 'BRO THIS IS A VIRUS! DELETE DELETE DELEEEEETE! AGAIN V2! ONE MORE WOOOOOOOOOOo', '2025-04-07 15:32:11', 0),
(5, 19, 'post', 92, 'Other', 'bro please work now', '2025-04-07 15:34:10', 0),
(6, 19, 'asset', 92, 'Other', 'frick you zpax', '2025-04-07 15:35:04', 0),
(7, 19, 'asset', 90, 'Other', 'Zpax virus! ban!', '2025-04-07 20:21:36', 0),
(8, 19, 'comment', 69, 'NSFW', 'bad comment! grr!', '2025-04-07 20:27:13', 0),
(9, 19, 'comment', 69, 'Spam', 'REPORTTTTTTT', '2025-04-07 20:53:38', 0),
(10, 19, 'asset', 92, 'Other', 'BRO THIS IS A VIRUS!', '2025-04-07 21:15:04', 0),
(14, 19, 'asset', 95, 'Swearing', 'a', '2025-04-07 21:24:50', 0),
(15, 31, 'post', 79, 'Other', 'view botting', '2025-04-08 21:09:16', 0),
(16, 19, 'comment', 74, 'Other', 'ur reported', '2025-04-08 21:25:04', 0),
(17, 19, 'comment', 76, 'Swearing', 'report1', '2025-04-08 21:45:49', 0),
(18, 19, 'comment', 76, 'Spam', 'report numero 2', '2025-04-08 21:50:03', 0),
(19, 19, 'reply', 77, 'Swearing', 'ew', '2025-04-08 21:51:14', 0),
(20, 19, 'post', 79, 'Other', 'evil people', '2025-04-08 22:03:45', 0),
(21, 19, 'post', 96, 'Harassment', 'ban this bad boy', '2025-04-08 22:11:54', 0),
(22, 19, 'post', 79, 'Other', 'There&#039;s no video attached?', '2025-04-09 13:17:37', 0),
(23, 19, 'post', 79, 'Other', 'There&#039;s no video attached?', '2025-04-09 13:17:44', 0),
(24, 19, 'post', 79, 'Other', 'There&#039;s no video attached?', '2025-04-09 13:17:48', 0),
(25, 19, 'post', 79, 'Other', 'There&#039;s no video attached?', '2025-04-09 13:17:55', 0),
(26, 30, 'post', 79, 'Swearing', 'nuh uh!', '2025-04-11 20:22:12', 0),
(27, 30, 'post', 96, 'Spam', 'oof!', '2025-04-11 20:22:43', 0),
(28, 30, 'asset', 96, 'Swearing', 'reported!1!11!', '2025-04-11 20:32:39', 0),
(29, 30, 'comment', 71, 'Other', 'emojis are stupid', '2025-04-11 20:34:59', 0),
(30, 30, 'comment', 76, 'Swearing', 'get reported stuoid', '2025-04-11 20:35:43', 0),
(31, 31, 'post', 79, 'Other', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam molestie feugiat justo, in blandit odio accumsan in. Praesent sit amet mauris diam. Nulla facilisi. Aliquam accumsan quam nisi, vitae vestibulum ipsum lacinia vitae. Praesent fringilla, nisl et dignissim mollis, erat ipsum accumsan dolor, eu lobortis lacus tortor vitae leo. Etiam at magna aliquam, accumsan dui tempor, dignissim metus. Suspendisse vitae nunc id ipsum auctor sagittis sit amet hendrerit erat. Aenean gravida mi vitae mi sollicitudin mattis ornare sed nibh. Pellentesque nec nisl eget sem gravida gravida eu et justo. Sed ex libero, auctor et elementum vitae, tristique tristique eros. In varius gravida justo, et laoreet lorem pellentesque a. In lobortis et turpis eu hendrerit. Sed sodales ultricies turpis, interdum facilisis ipsum. Nulla et leo a risus aliquam luctus.\r\n\r\nNulla ullamcorper nibh nec tempor vestibulum. Curabitur accumsan quis nisl ac ullamcorper. Nullam venenatis justo eget tincidunt elementum. Donec dapibus ipsum non felis lacinia, eu vulputate nulla tincidunt. Donec sed odio vel tellus placerat finibus in sit amet erat. Etiam pharetra nec mi sit amet tempor. Nam id lacinia quam.\r\n\r\nAliquam aliquet mi urna, sit amet suscipit diam egestas at. Maecenas sed nibh libero. Integer pulvinar fermentum ornare. Vivamus eget dui placerat, consectetur diam et, ullamcorper ante. Ut non ex erat. Vivamus elit enim, porttitor sed tincidunt vel, dignissim id lectus. Duis urna nisl, convallis sit amet scelerisque imperdiet, rutrum eget elit. Vestibulum nec leo arcu. Vestibulum neque turpis, vestibulum id tellus et, consequat gravida lorem.', '2025-04-11 20:51:15', 0),
(32, 30, 'post', 79, 'Other', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam molestie feugiat justo, in blandit odio accumsan in. Praesent sit amet mauris diam. Nulla facilisi. Aliquam accumsan quam nisi, vitae vestibulum ipsum lacinia vitae. Praesent fringilla, nisl et dignissim mollis, erat ipsum accumsan dolor, eu lobortis lacus tortor vitae leo. Etiam at magna aliquam, accumsan dui tempor, dignissim metus. Suspendisse vitae nunc id ipsum auctor sagittis sit amet hendrerit erat. Aenean gravida mi vitae mi sollicitudin mattis ornare sed nibh. Pellentesque nec nisl eget sem gravida gravida eu et justo. Sed ex libero, auctor et elementum vitae, tristique tristique eros. In varius gravida justo, et laoreet lorem pellentesque a. In lobortis et turpis eu hendrerit. Sed sodales ultricies turpis, interdum facilisis ipsum. Nulla et leo a risus aliquam luctus.\r\n\r\nNulla ullamcorper nibh nec tempor vestibulum. Curabitur accumsan quis nisl ac ullamcorper. Nullam venenatis justo eget tincidunt elementum. Donec dapibus ipsum non felis lacinia, eu vulputate nulla tincidunt. Donec sed odio vel tellus placerat finibus in sit amet erat. Etiam pharetra nec mi sit amet tempor. Nam id lacinia quam.\r\n\r\nAliquam aliquet mi urna, sit amet suscipit diam egestas at. Maecenas sed nibh libero. Integer pulvinar fermentum ornare. Vivamus eget dui placerat, consectetur diam et, ullamcorper ante. Ut non ex erat. Vivamus elit enim, porttitor sed tincidunt vel, dignissim id lectus. Duis urna nisl, convallis sit amet scelerisque imperdiet, rutrum eget elit. Vestibulum nec leo arcu. Vestibulum neque turpis, vestibulum id tellus et, consequat gravida lorem.', '2025-04-11 20:52:47', 0),
(33, 19, 'comment', 71, 'Spam', 'emoji spam', '2025-04-21 15:37:54', 0),
(37, 19, 'comment', 71, 'Spam', 'emoji spam', '2025-04-21 16:20:25', 0),
(38, 19, 'asset', 92, 'Malicious', 'VIRUS BRO!', '2025-04-21 16:25:02', 0),
(39, 31, '', 31, 'Impersonation', 'bro thinks he&#039;s sirkazzio', '2025-04-28 21:40:05', 0),
(40, 31, '', 31, 'Other', 'Reporting Thomaz123 as moonarrowstudios', '2025-04-28 22:00:27', 0),
(41, 31, 'asset', 96, 'Other', 'nnow im just reporting a asset', '2025-04-28 22:00:55', 0),
(42, 31, '', 31, 'Other', 'Okay, so this is a report test. Im logged in as moonarrowstudios, and im reporting the user a30743, at http://localhost/moonarrowstudios/php/profile.php?id=19', '2025-04-29 09:28:53', 0),
(43, 31, '', 19, 'Impersonation', 'n es o kazzio bro', '2025-04-29 10:09:01', 0),
(44, 30, '', 19, 'Other', 'tesst', '2025-05-14 15:59:43', 0),
(45, 30, '', 19, 'Harassment', 'test!', '2025-05-14 17:39:44', 0),
(46, 30, 'post', 95, 'Other', 'report test', '2025-05-21 17:40:59', 0),
(47, 30, 'post', 95, 'Swearing', 'report test', '2025-05-21 17:41:48', 0),
(48, 30, 'post', 84, 'Other', 'report test', '2025-05-21 17:42:21', 0),
(49, 30, 'post', 97, 'Other', 'please fix', '2025-05-21 17:45:29', 0),
(50, 30, 'asset', 97, 'Other', 'a', '2025-05-21 17:51:55', 0),
(51, 19, 'post', 84, 'Other', 'heya', '2025-05-22 10:41:26', 0),
(52, 19, 'comment', 79, 'Swearing', 'cool', '2025-05-22 10:47:49', 0);

-- --------------------------------------------------------

--
-- Table structure for table `status_changes`
--

DROP TABLE IF EXISTS `status_changes`;
CREATE TABLE IF NOT EXISTS `status_changes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `old_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `new_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `change_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `changed_by` (`changed_by`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_changes`
--

INSERT INTO `status_changes` (`id`, `user_id`, `changed_by`, `old_status`, `new_status`, `reason`, `change_date`) VALUES
(1, 30, 31, 'active', 'suspended', 'This is just a test. Your account will be reactivated shortly.', '2025-04-25 00:43:08'),
(2, 30, 31, 'suspended', 'active', 'Account reactivated! Welcome back!', '2025-04-25 00:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('active','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `youtube` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkedin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `twitter` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `github` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `portfolio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reported_count` int NOT NULL DEFAULT '0',
  `follow_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `asset_comment_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `reply_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `notification_frequency` enum('instant','hourly','daily','weekly') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'instant',
  `email_updates` tinyint(1) NOT NULL DEFAULT '1',
  `comment_notifications` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `created_at`, `profile_picture`, `banner`, `description`, `status`, `youtube`, `linkedin`, `twitter`, `instagram`, `github`, `portfolio`, `reported_count`, `follow_notifications`, `asset_comment_notifications`, `reply_notifications`, `notification_frequency`, `email_updates`, `comment_notifications`) VALUES
(30, 'Thomaz123', 'thomazbarrago@gmail.com', '$2y$10$UTOy9vxpjWKVoh8fb8osjewCbbj0QHQ6RUTl0MfTGA73WB47FLt36', 'admin', '2025-01-23 20:59:54', '\\moonarrowstudios\\uploads\\profile_pictures\\profile_30_1748999854.png', '\\moonarrowstudios\\uploads\\banners\\banner_30_1748999854.png', 'Welcome to my profile!!!', 'active', '', '', '', '', 'https://github.com/ThomazCortez', '', 0, 1, 1, 1, 'instant', 1, 1),
(31, 'moonarrowstudios', 'moonarrowstudios@gmail.com', '$2y$10$IABFpbOSzkTphuW2qAYd2OhgM/G.YgQuzEU0E80bs3L8cNC.kPC4y', 'admin', '2025-03-02 16:53:11', NULL, NULL, 'Hello and welcome to MoonArrow Studios!', 'active', '', '', '', '', '', '', 3, 0, 0, 0, 'instant', 0, 0),
(36, 'a30743', 'a30743@aemtg.pt', '$2y$10$uhzWQwwkFfN8g61CKg.IZee2rboWUvWfRhI/EfrncIQr3nwqucAgK', 'user', '2025-06-04 00:56:18', NULL, NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, 1, 'instant', 1, 1),
(38, 'SopaQuente', 'thomazfilipe88@gmail.com', '$2y$10$k6fr8LlOQtmIjVcrM45wWOnGkRrkhug8tbxIJ19NqrBpWm0MQsEWu', 'user', '2025-06-04 00:59:56', '\\moonarrowstudios\\uploads\\profile_pictures\\profile_38_1748999568.png', '\\moonarrowstudios\\uploads\\banners\\banner_38_1748999568.png', 'Eu gosto muito de sopa.', 'active', '', '', '', '', '', '', 0, 1, 1, 1, 'instant', 1, 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_deletions`
--
ALTER TABLE `account_deletions`
  ADD CONSTRAINT `account_deletions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_deletions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_categories` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_assets_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `asset_votes`
--
ALTER TABLE `asset_votes`
  ADD CONSTRAINT `fk_asset_votes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_posts` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments_asset`
--
ALTER TABLE `comments_asset`
  ADD CONSTRAINT `fk_comments_asset_assets` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_asset_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments_asset` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_asset_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment_asset_votes`
--
ALTER TABLE `comment_asset_votes`
  ADD CONSTRAINT `fk_comment_asset_votes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_posts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
