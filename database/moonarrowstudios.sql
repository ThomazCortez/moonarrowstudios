-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 07-Fev-2025 às 11:08
-- Versão do servidor: 8.3.0
-- versão do PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `moonarrowstudios`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categories`
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
-- Estrutura da tabela `comments`
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
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`, `parent_id`, `upvotes`, `downvotes`) VALUES
(1, 48, 26, '<p>Test Comment</p>', '2025-01-19 15:42:46', NULL, 0, 0),
(2, 48, 26, 'Test Comment', '2025-01-19 15:50:52', NULL, 0, 0),
(3, 48, 26, 'Test Heading 1Test Heading2Test Normal', '2025-01-19 15:51:28', NULL, 0, 0),
(4, 48, 26, '<h1>Test Heading</h1><p><br></p>', '2025-01-19 15:53:11', NULL, 0, 0),
(5, 48, 26, '<p>Test</p>', '2025-01-19 15:55:18', NULL, 0, 0),
(6, 48, 26, '<p>Test</p>', '2025-01-19 15:55:39', NULL, 0, 0),
(7, 48, 26, '<p>test3</p>', '2025-01-19 15:55:42', NULL, 0, 0),
(8, 48, 26, '<p>a</p>', '2025-01-19 15:56:01', NULL, 0, 0),
(9, 48, 26, '<p>aaa</p>', '2025-01-19 16:00:05', NULL, 0, 0),
(10, 48, 26, '<p>aa</p>', '2025-01-19 16:01:03', NULL, 0, 0),
(11, 48, 26, '<h1>HEADING1</h1><h2>HEADING2</h2><h3>HEADING3</h3>', '2025-01-19 16:15:47', NULL, 0, 0),
(12, 48, 26, '<h3>Test</h3><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">skibidi\ndop dop dop\nyes\n  yes\n    yes\n</pre><p><br></p>', '2025-01-19 16:23:55', NULL, 0, 0),
(13, 48, 26, '<p>hello</p>', '2025-01-19 20:51:59', NULL, 0, 0),
(14, 49, 26, '<p>hi</p>', '2025-01-19 20:55:38', NULL, 0, 0),
(15, 49, 26, '<p>This comment is super duper cool!</p>', '2025-01-19 20:56:51', NULL, 0, 0),
(16, 49, 26, '<h1>AAAAAAAAA</h1>', '2025-01-19 20:56:56', NULL, 0, 0),
(17, 40, 26, '<p>Test</p>', '2025-01-19 21:09:03', NULL, 0, 0),
(18, 40, 26, '<p>Test 2</p>', '2025-01-19 21:11:07', NULL, 0, 0),
(19, 40, 26, '<h1>HEADING</h1>', '2025-01-19 21:13:03', NULL, 0, 0),
(20, 40, 26, '<p>aaaa</p>', '2025-01-19 21:17:16', NULL, 0, 0),
(21, 40, 26, '<p>aaaa</p>', '2025-01-19 21:27:27', NULL, 0, 0),
(22, 40, 26, '<p>asdasd</p>', '2025-01-19 21:27:30', NULL, 0, 0),
(23, 40, 26, '<p>test</p>', '2025-01-19 21:33:35', 19, 0, 0),
(24, 40, 26, '<p>TEST1</p>', '2025-01-19 21:39:51', NULL, 0, 0),
(25, 40, 26, '<pre class=\"ql-syntax\" spellcheck=\"false\">TEST2\n</pre>', '2025-01-19 21:40:01', 24, 0, 0),
(26, 40, 26, '<p>TEST3</p>', '2025-01-19 21:40:34', 24, 0, 0),
(27, 49, 26, '<h1>This is a comment</h1><p>Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;</p><pre class=\"ql-syntax\" spellcheck=\"false\">Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\n</pre><p>Thanks!</p>', '2025-01-19 22:17:13', NULL, 0, 0),
(28, 49, 26, '<p>Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Lorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\nLorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\nLorem ipsum dolor sit amet. Ut placeat quibusdam sit omnis autem id praesentium dignissimos sit repellat consequatur nam ratione doloribus. Et deserunt reiciendis non possimus dolorum 33 voluptates voluptatibus aut nihil asperiores!&nbsp;\n</pre><p><br></p>', '2025-01-19 22:17:37', 27, 0, 0),
(29, 50, 26, '<p>Hello!</p>', '2025-01-21 10:29:11', NULL, 1, 0),
(30, 50, 26, '<p>Hiii!</p>', '2025-01-21 10:29:27', 29, 0, 0),
(31, 50, 26, '<p>TEST</p>', '2025-01-21 10:51:50', NULL, 0, 0),
(32, 50, 26, '<p>woah</p>', '2025-01-21 10:56:12', NULL, 0, 0),
(33, 50, 26, '<p>aaa</p>', '2025-01-21 17:31:30', NULL, 0, 0),
(34, 50, 26, '<p>test</p>', '2025-01-21 17:31:34', 32, 0, 0),
(35, 50, 26, '<p>a</p>', '2025-01-21 17:45:32', NULL, 0, 0),
(36, 50, 26, '<p>abc</p>', '2025-01-21 17:49:30', NULL, 0, 0),
(37, 50, 26, '<p>yest</p>', '2025-01-21 17:57:37', NULL, 0, 0),
(38, 50, 26, '<p>test</p>', '2025-01-21 17:57:44', 37, 0, 0),
(39, 50, 26, '<p>test</p>', '2025-01-21 17:58:02', 36, 0, 0),
(40, 50, 26, '<p>a</p>', '2025-01-21 18:00:48', 37, 0, 0),
(41, 50, 26, '<p>test</p>', '2025-01-21 19:07:37', 35, 0, 0),
(42, 50, 26, '<p>test</p>', '2025-01-21 19:09:50', 37, 0, 0),
(43, 50, 26, '<p>test</p>', '2025-01-21 19:12:44', 37, 0, 0),
(44, 50, 26, '<p>hi</p>', '2025-01-21 19:19:34', NULL, 0, 0),
(45, 50, 26, '<p>woah!</p>', '2025-01-21 19:19:41', 44, 0, 0),
(46, 50, 26, '<p>asd</p>', '2025-01-21 19:23:06', 31, 0, 0),
(47, 50, 26, '<p>a</p>', '2025-01-21 19:25:46', 44, 0, 0),
(48, 50, 26, '<p>TESTTT</p>', '2025-01-21 19:25:54', 31, 0, 0),
(49, 50, 26, '<p>woah</p>', '2025-01-21 20:17:30', NULL, 0, 0),
(50, 50, 26, '<p>cool!</p>', '2025-01-21 20:17:35', 49, 0, 1),
(51, 50, 26, '<p>works!?</p>', '2025-01-22 00:25:45', 49, 0, 0),
(52, 50, 26, '<p>DOES</p>', '2025-01-22 00:25:56', 49, 0, 0),
(53, 50, 26, '<p>cool</p>', '2025-01-22 00:28:09', NULL, 0, 0),
(54, 51, 26, '<p>test</p>', '2025-01-23 20:52:57', NULL, 1, 0),
(55, 51, 26, '<p>nice</p>', '2025-01-23 20:53:05', 54, 0, 0),
(56, 52, 30, '<p>hii</p>', '2025-01-25 14:20:13', NULL, 0, 0),
(57, 53, 30, '<p>test</p>', '2025-01-27 19:51:02', NULL, 0, 0),
(58, 53, 30, '<p>Reply!</p>', '2025-01-27 19:51:15', 57, 0, 0),
(59, 53, 30, '<p>Another Reply!</p>', '2025-01-27 19:51:28', 57, 0, 0),
(60, 53, 19, '<p>cool!</p><pre class=\"ql-syntax\" spellcheck=\"false\">cool!\n</pre><p><br></p>', '2025-01-28 18:44:16', NULL, 1, 0),
(61, 54, 19, '<p>Test</p>', '2025-01-28 18:58:12', NULL, 0, 0),
(62, 54, 19, '<p>test</p>', '2025-01-28 18:58:19', 61, 0, 0),
(63, 53, 19, '<p>One more reply!</p>', '2025-01-28 23:05:26', 57, 0, 0),
(64, 60, 24, '<h1>This is my post!</h1><h1><br></h1><pre class=\"ql-syntax\" spellcheck=\"false\">:)\n</pre>', '2025-01-29 13:37:53', NULL, 0, 0),
(65, 60, 19, '<h1>This is a comment!</h1><p><br></p><p>Hi!</p>', '2025-01-29 13:38:54', NULL, 2, 0),
(66, 60, 19, '<p>Another one!</p>', '2025-01-29 13:39:04', NULL, 0, 0),
(67, 60, 19, '<p>This is a reply to this comment!</p>', '2025-01-29 13:39:23', 65, 0, 0),
(68, 60, 17, '<p><strong>Nice post!</strong></p>', '2025-01-29 13:40:00', 65, 0, 0),
(69, 61, 33, '<p>This is a comment!</p>', '2025-01-29 15:50:52', NULL, 1, 0),
(70, 61, 33, '<p>Hello!</p>', '2025-01-29 15:51:00', NULL, 0, 0),
(71, 61, 33, '<p>Reply!</p>', '2025-01-29 15:51:19', 69, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `comment_votes`
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
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `comment_votes`
--

INSERT INTO `comment_votes` (`id`, `user_id`, `comment_id`, `vote_type`, `created_at`) VALUES
(3, 26, 29, 'upvote', '2025-01-21 10:29:18'),
(17, 26, 50, 'downvote', '2025-01-21 20:17:40'),
(18, 26, 54, 'upvote', '2025-01-23 20:52:59'),
(23, 19, 60, 'upvote', '2025-01-28 18:44:33'),
(28, 19, 65, 'upvote', '2025-01-29 13:39:30'),
(29, 17, 65, 'upvote', '2025-01-29 13:39:48'),
(30, 33, 69, 'upvote', '2025-01-29 15:50:54');

-- --------------------------------------------------------

--
-- Estrutura da tabela `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`,`following_id`),
  KEY `following_id` (`following_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `follows`
--

INSERT INTO `follows` (`follower_id`, `following_id`, `created_at`) VALUES
(19, 30, '2025-01-28 20:40:30'),
(33, 24, '2025-01-29 15:48:08');

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`(250)),
  KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `password_resets`
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
-- Estrutura da tabela `posts`
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
  PRIMARY KEY (`id`),
  KEY `fk_posts_categories` (`category_id`),
  KEY `fk_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `posts`
--

INSERT INTO `posts` (`id`, `title`, `content`, `category_id`, `hashtags`, `images`, `created_at`, `videos`, `user_id`, `upvotes`, `downvotes`) VALUES
(58, 'Lorem Ipsum 1', '<h3>Lorem</h3><p>Lorem ipsum dolor sit amet. Ut voluptas pariatur et velit dicta aut totam nostrum et odio minima et voluptatum inventore ut dignissimos repellendus. In voluptatem voluptates qui porro mollitia sit molestiae quidem? Aut molestiae iusto eum error nihil in internos corporis eum adipisci consequuntur ad sapiente omnis in neque quibusdam et impedit fugit.</p><p><strong>Lorem: abcdefghijklmnopqrstuvwxyz</strong></p><pre class=\"ql-syntax\" spellcheck=\"false\">using System;\r\n\r\n\r\nclass Program\r\n{\r\n&nbsp; &nbsp; static void Main()\r\n&nbsp; &nbsp; {\r\n&nbsp; &nbsp; &nbsp; &nbsp; Random random = new Random();\r\n&nbsp; &nbsp; &nbsp; &nbsp; int randomNumber = random.Next(1, 101);\r\n&nbsp; &nbsp; &nbsp; &nbsp; Console.WriteLine(\"Random number: \" + randomNumber);\r\n&nbsp; &nbsp; }\r\n}\r\n</pre><p><br></p>', 1, '#C #Code #Random', '[]', '2025-01-29 13:29:10', '[]', 17, 0, 0),
(59, 'Lorem Ipsum 2', '<p>Lorem ipsum dolor sit amet. Ut voluptas accusantium eos nisi enim est repellendus itaque qui odio sint ut saepe internos eum modi maiores. </p><pre class=\"ql-syntax\" spellcheck=\"false\">print(\"Hello World!\")\r\n</pre><p>Non facilis doloribus et repudiandae alias est asperiores animi. Hic quasi quam et illum deserunt et fugit provident ut corrupti dolorem qui explicabo perferendis vel sapiente soluta sit sequi impedit. </p><ul><li>Aut totam expedita qui omnis minima et nemo fugit sed reprehenderit dolores id perferendis assumenda et saepe beatae.</li><li>Aut totam expedita qui omnis minima et nemo fugit sed reprehenderit dolores id perferendis assumenda et saepe beatae.</li><li>Aut totam expedita qui omnis minima et nemo fugit sed reprehenderit dolores id perferendis assumenda et saepe beatae.</li></ul><blockquote>Lorem Ipsum!</blockquote><p><br></p>', 6, '#python #programming #print', '[\"uploads\\/images\\/0ff4118d-8411-4586-97f2-93b794217025.png\"]', '2025-01-29 13:32:39', '[]', 19, 2, 0),
(60, 'Lorem Ipsum 3', '<h4>Lorem</h4><h3>Ipsum</h3><p>Lorem Ipsum</p><p><strong>Lorem ipsum dolor sit amet.</strong></p><p><em>Lorem ipsum dolor sit amet.</em></p><p><u>Lorem ipsum dolor sit amet.</u></p><blockquote>Lorem ipsum dolor sit amet.</blockquote><pre class=\"ql-syntax\" spellcheck=\"false\">Lorem ipsum dolor sit amet.\r\n</pre><ol><li>Lorem ipsum dolor sit amet.</li><li>Lorem ipsum dolor sit amet.</li></ol><ul><li>Lorem ipsum dolor sit amet.</li><li>Lorem ipsum dolor sit amet.</li></ul><p><a href=\"https://www.aemtg.pt/\" rel=\"noopener noreferrer\" target=\"_blank\">https://www.aemtg.pt/</a></p>', 6, '#example #loremipsum #test', '[\"uploads\\/images\\/\\u2014Pngtree\\u2014arrow shape red simple curved_8186744.png\",\"uploads\\/images\\/banner.png\"]', '2025-01-29 13:36:47', '[\"uploads\\/videos\\/12707805_3840_2160_25fps.mp4\"]', 24, 0, 0),
(61, 'My Post Title', '<h3>Heading</h3><p>Content</p><pre class=\"ql-syntax\" spellcheck=\"false\">weweew\r\n  wewewe\r\n      wrwrwr\r\n</pre><p><br></p>', 16, '#test', '[\"uploads\\/images\\/0ff4118d-8411-4586-97f2-93b794217025.png\"]', '2025-01-29 15:50:10', '[\"uploads\\/videos\\/12707805_3840_2160_25fps.mp4\"]', 33, 1, 0),
(62, 'Test', '<p>test</p>', 1, '#wewe #wow', '[]', '2025-01-31 10:55:02', '[]', 33, 0, 0),
(63, 'Test 2', '<p>aaaa</p>', 9, '#wow #cool #python', '[]', '2025-01-31 10:56:17', '[]', 33, 0, 0),
(64, 'tags testtt', '<p>a</p>', 14, '', '[]', '2025-01-31 11:12:57', '[]', 33, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `post_votes`
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
) ENGINE=MyISAM AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `post_votes`
--

INSERT INTO `post_votes` (`id`, `user_id`, `post_id`, `vote_type`) VALUES
(99, 26, 51, 'upvote'),
(44, 19, 40, 'downvote'),
(42, 26, 40, 'downvote'),
(45, 26, 39, 'upvote'),
(74, 26, 45, 'upvote'),
(102, 19, 54, 'upvote'),
(103, 30, 52, 'upvote'),
(106, 24, 59, 'upvote'),
(107, 19, 59, 'upvote'),
(108, 33, 61, 'upvote');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
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
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `created_at`, `profile_picture`, `banner`, `description`) VALUES
(17, 'aaa', 'aaa@gmail.com', '$2y$10$tDYT3J4Clw10/WYJbjuSDuSt2FUVHvfwOIErfxxmcGtSYKHwRecuS', 'user', '2024-12-20 11:26:03', NULL, NULL, NULL),
(19, 'a30743', 'a30743@aemtg.pt', '$2y$10$z1w0K0JJjBOmbKCM66GgL.L8L8ZBwzEzNhHwJi7BV.zGuipDa5xe2', 'user', '2024-12-30 18:01:09', NULL, NULL, 'HI!'),
(24, 'teste123', 'exemple123123@gmail.com', '$2y$10$UhnjQ/wurSX0ImzToLpZ2eZRpsWGaVmNVcdsAwZpEjfb9B0U9pkDa', 'user', '2024-12-30 22:58:12', NULL, NULL, NULL),
(33, 'Thomaz123', 'thomazbarrago@gmail.com', '$2y$10$tOUGkhk4dsJ0rryDlVMDFOfQRw8gKQsEKX1rU94XrBs5wduqANE92', 'user', '2025-01-29 15:43:52', '\\moonarrowstudios\\uploads\\profile_pictures\\profile_33_1738572439.png', '../uploads/banners/banner_33.png', 'This is my description! hhhh');

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
