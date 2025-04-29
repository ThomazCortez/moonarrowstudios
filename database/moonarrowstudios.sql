-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 29, 2025 at 10:34 AM
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
  PRIMARY KEY (`id`),
  KEY `fk_posts_categories` (`category_id`),
  KEY `fk_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `title`, `content`, `category_id`, `hashtags`, `images`, `created_at`, `videos`, `user_id`, `upvotes`, `downvotes`, `preview_image`, `asset_file`, `status`, `views`, `updated_at`, `reported_count`) VALUES
(74, 'abc', '<p>abc</p>', 1, '#music', NULL, '2025-02-23 19:24:18', NULL, 30, 0, 0, NULL, NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(75, 'lol', '<p>lol</p>', 11, '#a', '[]', '2025-02-24 19:35:00', '[]', 30, 0, 0, 'uploads/previews/a0084845941_65.jpg', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(76, 'a', '<p>abc</p>', 7, '#ksd', '[]', '2025-02-24 19:48:25', '[]', 30, 0, 0, 'uploads/previews/gfoyUJWR_400x400.jpg', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(77, 'audio1', '<p>audio</p>', 10, '#audio', '[]', '2025-02-24 20:16:06', '[]', 30, 0, 0, '', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(79, 'a', '<p>a</p>', 11, '#zz', '[]', '2025-02-25 20:37:17', '[]', 30, 0, 0, '', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(80, 'comment testing', '<p>yes</p>', 14, '#shader', '[]', '2025-02-26 12:32:49', '[]', 30, 0, 1, '', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(82, 'a2', '<p>a2</p>', 11, '#a2', 'null', '2025-03-01 13:23:53', 'null', 30, 0, 0, NULL, 'uploads/assets/MC Bin Laden - Ta Tranquilo Ta Favorável (Clipe Oficial).mp3', 'published', 0, '2025-03-04 14:10:18', 0),
(83, 'a3', '<p>a3</p>', 1, '#a3', 'null', '2025-03-01 13:24:31', 'null', 30, 0, 0, NULL, '', 'published', 0, '2025-03-04 14:10:18', 0),
(84, 'a4', '<p>a4</p>', 1, '#a4', '[]', '2025-03-01 13:25:39', '[]', 30, 0, 0, '', NULL, 'published', 0, '2025-03-04 14:10:18', 0),
(85, 'favoravel', '<p>favoravel</p>', 11, '#favoravel', '[]', '2025-03-01 13:40:54', '[]', 30, 0, 0, 'uploads/previews/e6c51864a3d91796e797e764126bb6c8.500x500x1.jpg', 'uploads/assets/MC Bin Laden - Ta Tranquilo Ta Favorável (Clipe Oficial).mp3', 'published', 2, '2025-04-28 13:43:35', 0),
(88, 'c', '<p>c</p>', 12, '#c', '[\"uploads\\/images\\/sun.png\"]', '2025-03-01 14:10:38', '[]', 30, 0, 0, 'uploads/previews/a2572949485_65.jpg', 'uploads/assets/ProjetoPSILourenço12F.rar', 'published', 0, '2025-03-04 14:10:18', 0),
(90, 'VIRUS', '<p>VIRUS</p>', 6, '#virus', '[]', '2025-03-01 15:43:09', '[]', 30, 0, 0, '', 'uploads/assets/eicar_com.zip', 'published', 0, '2025-04-07 20:21:36', 1),
(92, 'ZPAX1', '<p>YES1</p>', 5, '', '[\"uploads\\/images\\/IMG_20250128_190627.jpg\",\"uploads\\/images\\/a0084845941_65.jpg\"]', '2025-03-02 11:34:46', '[]', 19, 0, 0, 'uploads/previews/gfoyUJWR_400x400.jpg', '', 'published', 0, '2025-04-21 16:25:02', 3),
(94, 'video', '<p>video</p>', 1, NULL, '[\"uploads\\/images\\/Captura de ecr\\u00e3 2024-11-30 163813.png\"]', '2025-03-04 18:30:30', '[\"uploads\\/videos\\/Roblox VR 2025.03.02 - 23.32.14.02.mp4\"]', 31, 0, 0, 'uploads/previews/Captura de ecrã 2024-11-30 163813.png', NULL, 'published', 1, '2025-04-28 21:46:24', 0),
(95, 'abc', '<p>abc</p>', 1, '#abc', '[\"uploads\\/images\\/Captura de ecr\\u00e3 2024-12-03 191655.png\"]', '2025-03-04 18:37:36', '[\"uploads\\/videos\\/Roblox VR 2025.03.02 - 23.32.17.03.DVR.mp4\"]', 31, 0, 0, 'uploads/previews/Captura de ecrã 2024-12-03 191655.png', 'uploads/assets/Captura de ecrã 2024-12-03 191655.png', 'published', 3, '2025-04-28 13:43:18', 1),
(96, 'report', '<p>report me.</p>', 12, '#report', '[]', '2025-04-08 21:45:09', '[]', 19, 0, 0, '', 'uploads/assets/mine_obradearte.png', 'published', 13, '2025-04-28 22:00:55', 2);

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
(14, 'Custom Shaders'),
(15, 'Particle Effects'),
(16, 'Post-Processing Effects'),
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
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_votes`
--

INSERT INTO `asset_votes` (`id`, `user_id`, `asset_id`, `vote_type`) VALUES
(130, 30, 80, 'downvote');

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
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`, `parent_id`, `upvotes`, `downvotes`, `status`, `reported_count`, `updated_at`) VALUES
(1, 48, 26, '<p>Test Comment</p>', '2025-01-19 15:42:46', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(2, 48, 26, 'Test Comment', '2025-01-19 15:50:52', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(3, 48, 26, 'Test Heading 1Test Heading2Test Normal', '2025-01-19 15:51:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(4, 48, 26, '<h1>Test Heading</h1><p><br></p>', '2025-01-19 15:53:11', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(5, 48, 26, '<p>Test</p>', '2025-01-19 15:55:18', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(6, 48, 26, '<p>Test</p>', '2025-01-19 15:55:39', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(7, 48, 26, '<p>test3</p>', '2025-01-19 15:55:42', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(8, 48, 26, '<p>a</p>', '2025-01-19 15:56:01', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(9, 48, 26, '<p>aaa</p>', '2025-01-19 16:00:05', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(10, 48, 26, '<p>aa</p>', '2025-01-19 16:01:03', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(11, 48, 26, '<h1>HEADING1</h1><h2>HEADING2</h2><h3>HEADING3</h3>', '2025-01-19 16:15:47', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(12, 48, 26, '<h3>Test</h3><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">skibidi\ndop dop dop\nyes\n  yes\n    yes\n</pre><p><br></p>', '2025-01-19 16:23:55', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(13, 48, 26, '<p>hello</p>', '2025-01-19 20:51:59', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(14, 49, 26, '<p>hi</p>', '2025-01-19 20:55:38', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(15, 49, 26, '<p>This comment is super duper cool!</p>', '2025-01-19 20:56:51', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(16, 49, 26, '<h1>AAAAAAAAA</h1>', '2025-01-19 20:56:56', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(17, 40, 26, '<p>Test</p>', '2025-01-19 21:09:03', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(18, 40, 26, '<p>Test 2</p>', '2025-01-19 21:11:07', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(19, 40, 26, '<h1>HEADING</h1>', '2025-01-19 21:13:03', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(20, 40, 26, '<p>aaaa</p>', '2025-01-19 21:17:16', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(21, 40, 26, '<p>aaaa</p>', '2025-01-19 21:27:27', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(22, 40, 26, '<p>asdasd</p>', '2025-01-19 21:27:30', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(23, 40, 26, '<p>test</p>', '2025-01-19 21:33:35', 19, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(24, 40, 26, '<p>TEST1</p>', '2025-01-19 21:39:51', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(25, 40, 26, '<pre class=\"ql-syntax\" spellcheck=\"false\">TEST2\n</pre>', '2025-01-19 21:40:01', 24, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(26, 40, 26, '<p>TEST3</p>', '2025-01-19 21:40:34', 24, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(27, 49, 26, '<h1>This is a comment</h1><p>Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;</p><pre class=\"ql-syntax\" spellcheck=\"false\">Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\n</pre><p>Thanks!</p>', '2025-01-19 22:17:13', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(28, 49, 26, '<p>Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\nLorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\nLorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\n</pre><p><br></p>', '2025-01-19 22:17:37', 27, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(29, 50, 26, '<p>Hello!</p>', '2025-01-21 10:29:11', NULL, 1, 0, 'published', 0, '2025-03-10 23:27:25'),
(30, 50, 26, '<p>Hiii!</p>', '2025-01-21 10:29:27', 29, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(31, 50, 26, '<p>TEST</p>', '2025-01-21 10:51:50', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(32, 50, 26, '<p>woah</p>', '2025-01-21 10:56:12', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(33, 50, 26, '<p>aaa</p>', '2025-01-21 17:31:30', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(34, 50, 26, '<p>test</p>', '2025-01-21 17:31:34', 32, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(35, 50, 26, '<p>a</p>', '2025-01-21 17:45:32', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(36, 50, 26, '<p>abc</p>', '2025-01-21 17:49:30', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(37, 50, 26, '<p>yest</p>', '2025-01-21 17:57:37', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(38, 50, 26, '<p>test</p>', '2025-01-21 17:57:44', 37, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(39, 50, 26, '<p>test</p>', '2025-01-21 17:58:02', 36, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(40, 50, 26, '<p>a</p>', '2025-01-21 18:00:48', 37, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(41, 50, 26, '<p>test</p>', '2025-01-21 19:07:37', 35, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(42, 50, 26, '<p>test</p>', '2025-01-21 19:09:50', 37, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(43, 50, 26, '<p>test</p>', '2025-01-21 19:12:44', 37, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(44, 50, 26, '<p>hi</p>', '2025-01-21 19:19:34', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(45, 50, 26, '<p>woah!</p>', '2025-01-21 19:19:41', 44, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(46, 50, 26, '<p>asd</p>', '2025-01-21 19:23:06', 31, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(47, 50, 26, '<p>a</p>', '2025-01-21 19:25:46', 44, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(48, 50, 26, '<p>TESTTT</p>', '2025-01-21 19:25:54', 31, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(49, 50, 26, '<p>woah</p>', '2025-01-21 20:17:30', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(50, 50, 26, '<p>cool!</p>', '2025-01-21 20:17:35', 49, 0, 1, 'published', 0, '2025-03-10 23:27:25'),
(51, 50, 26, '<p>works!?</p>', '2025-01-22 00:25:45', 49, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(52, 50, 26, '<p>DOES</p>', '2025-01-22 00:25:56', 49, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(53, 50, 26, '<p>cool</p>', '2025-01-22 00:28:09', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(54, 51, 26, '<p>test</p>', '2025-01-23 20:52:57', NULL, 1, 0, 'published', 0, '2025-03-10 23:27:25'),
(55, 51, 26, '<p>nice</p>', '2025-01-23 20:53:05', 54, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(64, 69, 29, '<p>aa</p>', '2025-02-25 20:31:42', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:25'),
(69, 79, 31, '<p>comment</p>', '2025-04-07 15:47:13', NULL, 0, 0, 'published', 2, '2025-04-07 20:53:38'),
(70, 79, 31, '<p>This is a reply!</p>', '2025-04-08 22:07:15', 69, 0, 0, 'published', 0, '2025-04-09 08:57:50'),
(71, 79, 31, '<p>😎😎😎😎😎😎✔✔✔✔✔👀👀👀👀👀✨✨✨✨😆😆😆😆😆😆</p>', '2025-04-10 09:30:19', NULL, 0, 0, 'published', 3, '2025-04-21 16:20:25');

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
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments_asset`
--

INSERT INTO `comments_asset` (`id`, `asset_id`, `user_id`, `content`, `created_at`, `parent_id`, `upvotes`, `downvotes`, `status`, `reported_count`, `updated_at`) VALUES
(65, 80, 30, '<p>hello</p>', '2025-02-26 12:33:32', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(66, 80, 30, '<p>test</p>', '2025-02-26 12:40:53', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(67, 80, 30, '<p>ye</p>', '2025-02-27 08:48:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(68, 80, 30, '<p>ye</p>', '2025-02-27 08:48:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(69, 80, 30, '<p>ye</p>', '2025-02-27 08:48:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(70, 80, 30, '<p>ye</p>', '2025-02-27 08:48:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(71, 80, 30, '<p>ye</p>', '2025-02-27 08:48:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(72, 80, 30, '<p>oh god</p>', '2025-02-27 08:48:39', NULL, 1, 0, 'published', 0, '2025-03-10 23:27:44'),
(73, 80, 30, '<p>sup!</p>', '2025-02-27 08:50:39', 65, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(74, 95, 31, '<p>a</p>', '2025-03-10 22:13:28', NULL, 0, 0, 'published', 0, '2025-03-10 23:27:44'),
(75, 95, 31, '<p>b</p>', '2025-04-08 22:10:12', 74, 0, 0, 'published', 0, '2025-04-08 21:10:12'),
(76, 96, 19, '<p>asset report?</p><p><br></p>', '2025-04-08 22:45:32', NULL, 0, 0, 'published', 2, '2025-04-11 20:35:43'),
(77, 96, 19, '<p>reply report!</p>', '2025-04-08 22:51:08', 76, 0, 0, 'published', 1, '2025-04-08 21:51:14');

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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_asset_votes`
--

INSERT INTO `comment_asset_votes` (`id`, `user_id`, `comment_id`, `vote_type`, `created_at`) VALUES
(29, 30, 72, 'upvote', '2025-02-27 08:56:56');

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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(39, 30, 65, 'downvote', '2025-02-27 08:52:41');

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
(19, 30, '2025-01-30 22:46:19'),
(31, 19, '2025-04-29 09:25:47'),
(31, 30, '2025-04-28 23:11:56');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`(250)),
  KEY `token` (`token`),
  KEY `fk_password_resets_users` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`) VALUES
(10, 'thomazbarrago@gmail.com', '0af739ae0fe3ec98bfe742fc8b4525258b8779205aa4a86b06552478c05e573e', '2024-12-01 23:01:27'),
(11, 'thomazbarrago@gmail.com', 'e145eac2b2de24d005f233c4686ee448456d009165acfd9c9228f05408af3505', '2024-12-01 23:04:21'),
(15, 'thomazbarrago@gmail.com', '7b61562f9131790435631e01dd39aca37a6eb28c326424ca3767980f1416d5af', '2024-12-01 23:28:37'),
(16, 'thomazbarrago@gmail.com', '0a0654ef25cae8794567f82d67ca5a55e4536a971cbe1213352cf7c1a379d976', '2024-12-01 23:29:06'),
(17, 'thomazbarrago@gmail.com', '2d8a1b4f61fa1f24905575b4ec1c8c41fe29981a5fbc5300d9b77ee1419a6e1b', '2024-12-01 23:29:54'),
(18, 'thomazbarrago@gmail.com', 'd9c1675b966781192c31ee253e0ba1adc720715cdb3414e57c2ba0580a40d1c8', '2024-12-01 23:30:19'),
(19, 'a30743@aemtg.pt', '605ca4715ff8dbf80b6fdac4544ee8fbce088b930089908200bfe03ebe3c5654', '2024-12-01 23:33:16'),
(22, 'thomazbarrago@gmail.com', 'c6a32518530b2ac0006b7a554f21cab8202087aa84e2e2cb5a61376ecbcd3a7b', '2024-12-28 22:59:48'),
(23, 'thomazbarrago@gmail.com', '468a14e250b4aa11e3d0bb2114e7f62d780c987d3f36109066932320e3db5995', '2024-12-28 23:04:41'),
(24, 'thomazbarrago@gmail.com', 'ff3145bafa98deab10ddf6e74de674f83b2cbbf91221275548fb29b54805e5d4', '2024-12-28 23:05:39'),
(27, 'a30743@aemtg.pt', 'f184962eb5a8322a557962877e8baf9d3b64a059c69f1815274e332bcfbd729c', '2024-12-30 19:27:44'),
(28, 'thomazbarrago@gmail.com', '87030be9dadf773218f8bd259a900391b059cae417480ce50dce0810ad02c5be', '2024-12-30 22:28:34'),
(29, 'thomazbarrago@gmail.com', '4f3b22a80b42e7aeeb10b8e54b5fb0fdb73fcb196f8aaa20699d4a1431ed09dd', '2024-12-30 23:10:27'),
(36, 'thomazbarrago@gmail.com', 'ace925ed753623095ff4776ee8c02cd703b1ac60ed3a4250316b04a41d937a14', '2025-01-21 22:36:54'),
(38, 'thomazbarrago@gmail.com', '056f30712a7bde03b0edcec9a1234a0109547e97d2273e7f561185bbd0b93bfd', '2025-01-23 22:56:37'),
(40, 'thomazbarrago@gmail.com', '0974ea1a407dbdf8957ea77bdbf053266fbde0315eb18b4a924aa7cb98eb1451', '2025-01-26 23:33:43'),
(41, 'thomazbarrago@gmail.com', '99cae380671e56b4b9743b9bd3baa42c9bb5a2db4c67f99dea91a407cda125f8', '2025-01-26 23:36:13');

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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `content`, `category_id`, `hashtags`, `images`, `created_at`, `videos`, `user_id`, `upvotes`, `downvotes`, `status`, `views`, `updated_at`, `reported_count`) VALUES
(58, 'test hastag 2', '<p>aasdasdd</p>', 17, '', '[]', '2025-01-31 22:41:55', '[]', 30, 0, 0, 'published', 3, '2025-04-10 10:03:34', 0),
(59, 'test hastag 3', '<p>aasddas</p>', 1, '', '[]', '2025-01-31 22:47:49', '[]', 30, 0, 0, 'published', 4, '2025-03-04 15:13:51', 0),
(60, 'a', '<p>a</p>', 21, '', '[]', '2025-01-31 22:51:30', '[]', 30, 0, 0, 'published', 8, '2025-03-03 17:41:45', 0),
(61, 'test hash', '<p>a</p>', 22, '', '[]', '2025-01-31 22:55:50', '[]', 30, 0, 0, 'published', 4, '2025-04-09 09:01:18', 0),
(74, 'Hello, and Wecome to MoonArrow Studios!', '<p>Here</p>', 1, '', '[]', '2025-03-03 20:16:47', '[]', 31, 1, 0, 'published', 3, '2025-04-10 10:03:46', 0),
(75, 'a', '<p>a</p>', 9, '#a', '[]', '2025-03-03 20:19:26', '[]', 31, 0, 0, 'published', 1, '2025-03-03 20:20:03', 0),
(76, 'bla', '<p>blabla</p>', 21, '#blablabla', '[]', '2025-03-03 20:20:27', '[]', 31, 0, 0, 'published', 1, '2025-03-04 15:13:53', 0),
(77, 'a', '<p>a</p>', 14, '#a', '[\"uploads\\/images\\/vfx.png\"]', '2025-03-04 15:14:24', '[]', 31, 0, 0, 'published', 1, '2025-03-04 15:14:26', 0),
(78, 'image', '<p>image</p>', 5, '#image', '[\"uploads\\/images\\/coding-background-9izlympnd0ovmpli.jpg\"]', '2025-03-04 18:04:38', '[]', 31, 0, 0, 'published', 4, '2025-04-07 15:29:25', 1),
(79, 'video', '<p>video</p>', 20, '#bideo', '[]', '2025-03-04 18:10:34', '[\"uploads\\/videos\\/GABRIEL.mp4\"]', 31, 0, 0, 'published', 108, '2025-04-21 22:37:38', 9),
(80, 'post1', '<p>tes</p>', 16, '#abc', '[]', '2025-04-21 23:18:32', '[]', 19, 0, 0, 'published', 17, '2025-04-29 09:25:55', 0);

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
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(108, 31, 74, 'upvote');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_id` int NOT NULL,
  `content_type` enum('post','asset','comment','reply') COLLATE utf8mb4_general_ci NOT NULL,
  `content_id` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `reported_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(43, 31, '', 19, 'Impersonation', 'n es o kazzio bro', '2025-04-29 10:09:01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `status_changes`
--

DROP TABLE IF EXISTS `status_changes`;
CREATE TABLE IF NOT EXISTS `status_changes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `changed_by` int NOT NULL,
  `old_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `new_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
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
  `youtube` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkedin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `twitter` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `github` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `portfolio` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reported_count` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `created_at`, `profile_picture`, `banner`, `description`, `status`, `youtube`, `linkedin`, `twitter`, `instagram`, `github`, `portfolio`, `reported_count`) VALUES
(19, 'a30743', 'a30743@aemtg.pt', '$2y$10$749e3jGeEI1/WaUcokVT3OmPEogIU6evldHMSOUdDK16Y9Su9CeDa', 'user', '2024-12-30 18:01:09', '\\moonarrowstudios\\uploads\\profile_pictures\\profile_19_1745193530.png', '\\moonarrowstudios\\uploads\\banners\\banner_19.png', 'HI!', 'active', 'https://www.youtube.com/user/SirKazzio', '', 'https://x.com/sirkazzio', '', '', '', 1),
(24, 'teste123', 'exemple123123@gmail.com', '$2y$10$UhnjQ/wurSX0ImzToLpZ2eZRpsWGaVmNVcdsAwZpEjfb9B0U9pkDa', 'user', '2024-12-30 22:58:12', NULL, NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(27, 'usertest123', 'usertest@gmail.com', '$2y$10$Y3uiX24QwkjOkJfYc8xM1emMGCB1FFs6bzEUttd/aEajAXxm2.fwS', 'user', '2025-01-21 22:27:42', NULL, NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(30, 'Thomaz123', 'thomazbarrago@gmail.com', '$2y$10$KLSzn36tJl1/AE2Om9XHGegc9CgJFu125VFK6EhSr2BLZ5IwhsJq2', 'admin', '2025-01-23 20:59:54', '\\moonarrowstudios\\uploads\\profile_pictures\\profile_30_1740833430.png', '\\moonarrowstudios\\uploads\\banners\\banner_30_1740833430.png', 'Welcome to my profile!', 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(31, 'moonarrowstudios', 'moonarrowstudios@gmail.com', '$2y$10$IABFpbOSzkTphuW2qAYd2OhgM/G.YgQuzEU0E80bs3L8cNC.kPC4y', 'admin', '2025-03-02 16:53:11', NULL, NULL, 'Hello and welcome to MoonArrow Studios!', 'active', '', '', '', '', 'https://github.com/ThomazCortez', 'http://localhost/moonarrowstudios', 3),
(32, 'SirKazzio', 'sirkazzio@gmail.com', '$2y$10$/hRiWV6MlvEb1SF4niQS5.BUmOaEIXvXEaAhDCphMDG1zKfVzeGUe', 'user', '2025-04-25 13:07:38', NULL, NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_categories` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_assets_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments_asset`
--
ALTER TABLE `comments_asset`
  ADD CONSTRAINT `comments_asset_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_asset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `fk_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_follows_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reporter_id` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
