-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th4 29, 2026 lúc 07:20 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `english_learning_app`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(80) NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(80) NOT NULL DEFAULT 'bi-award',
  `condition_type` varchar(80) NOT NULL,
  `condition_value` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `achievements`
--

INSERT INTO `achievements` (`id`, `code`, `name`, `description`, `icon`, `condition_type`, `condition_value`, `created_at`) VALUES
(1, 'first_set', 'First Set', 'Tạo bộ từ đầu tiên.', 'bi-collection', 'sets_created', 1, '2026-04-29 00:41:32'),
(2, 'first_test', 'First Test', 'Hoàn thành bài test đầu tiên.', 'bi-ui-checks-grid', 'tests_completed', 1, '2026-04-29 00:41:32'),
(3, 'cards_100', '100 Cards Learned', 'Học 100 flashcards.', 'bi-lightning-charge', 'cards_learned', 100, '2026-04-29 00:41:32'),
(4, 'perfect_score', 'Perfect Score', 'Đạt 100% trong bài test.', 'bi-trophy', 'perfect_score', 100, '2026-04-29 00:41:32'),
(5, 'streak_7', '7 Day Streak', 'Học liên tục 7 ngày.', 'bi-calendar-check', 'streak_days', 7, '2026-04-29 00:41:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `target_role` enum('all','user','teacher','admin') NOT NULL DEFAULT 'all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `type`, `target_role`, `is_active`, `start_at`, `end_at`, `created_by`, `created_at`) VALUES
(1, 'Chào mừng tất cả mọi người đã tham gia', 'Chào mừng tất cả mọi người đã đến với website của chúng tôi.', 'info', 'all', 1, NULL, NULL, 3, '2026-04-29 03:12:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `test_result_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','late') NOT NULL DEFAULT 'not_started',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `target_type` varchar(80) NOT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'settings_update', 'settings', NULL, NULL, '{\"csrf_token\":\"af1778a94e06686684d2e2e163ccc7f7da61de4197414f968787ea7615c39eb8\",\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":\"on\",\"maintenance_mode\":\"on\",\"default_test_questions\":\"20\",\"blast_default_seconds\":\"60\",\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":\"on\",\"feedback_enabled\":\"on\",\"public_library_enabled\":\"on\"}', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-04-29 02:19:33'),
(2, 3, 'settings_update', 'settings', NULL, '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":true,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":true,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8115', '2026-04-29 03:02:22'),
(3, 3, 'settings_update', 'settings', NULL, '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":false,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":false,\"default_test_questions\":7,\"blast_default_seconds\":45,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8115', '2026-04-29 03:06:24'),
(4, 3, 'settings_update', 'settings', NULL, '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":false,\"default_test_questions\":7,\"blast_default_seconds\":45,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":false,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8115', '2026-04-29 03:06:25'),
(5, 3, 'settings_update', 'settings', NULL, '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":false,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '{\"site_name\":\"English Learning App\",\"site_tagline\":\"Học tiếng Anh chủ động mỗi ngày\",\"registration_enabled\":true,\"public_set_moderation\":false,\"maintenance_mode\":true,\"default_test_questions\":20,\"blast_default_seconds\":60,\"contact_email\":\"support@example.com\",\"footer_text\":\"English Learning App\",\"leaderboard_enabled\":true,\"feedback_enabled\":true,\"public_library_enabled\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-29 03:08:10'),
(6, 3, 'announcement_create', 'announcement', 1, NULL, '{\"csrf_token\":\"af1778a94e06686684d2e2e163ccc7f7da61de4197414f968787ea7615c39eb8\",\"title\":\"Chào mừng tất cả mọi người đã tham gia \",\"type\":\"info\",\"target_role\":\"all\",\"is_active\":\"on\",\"start_at\":\"\",\"end_at\":\"\",\"content\":\"Chào mừng tất cả mọi người đã đến với website của chúng tôi.\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-29 03:12:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'General English', 'general-english', 'Từ vựng tiếng Anh thông dụng', '2026-04-29 00:41:32'),
(2, 'Business', 'business', 'Từ vựng công việc và kinh doanh', '2026-04-29 00:41:32'),
(3, 'Travel', 'travel', 'Từ vựng du lịch', '2026-04-29 00:41:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `class_members`
--

CREATE TABLE `class_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('owner','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `class_members`
--

INSERT INTO `class_members` (`id`, `class_id`, `user_id`, `role`, `created_at`) VALUES
(1, 1, 1, 'owner', '2026-04-29 00:25:24'),
(2, 1, 2, 'member', '2026-04-29 00:25:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feedback`
--

CREATE TABLE `feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('bug','content_error','suggestion','other') NOT NULL DEFAULT 'other',
  `target_type` enum('set','flashcard','class','system') NOT NULL DEFAULT 'system',
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('open','reviewing','resolved','rejected') NOT NULL DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flashcards`
--

CREATE TABLE `flashcards` (
  `id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `term` varchar(190) NOT NULL,
  `definition` varchar(255) NOT NULL,
  `example_sentence` text DEFAULT NULL,
  `pronunciation` varchar(120) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `flashcards`
--

INSERT INTO `flashcards` (`id`, `set_id`, `term`, `definition`, `example_sentence`, `pronunciation`, `image_url`, `created_at`) VALUES
(1, 1, 'apple', 'quả táo', 'I eat an apple every morning.', '/ˈæp.əl/', '', '2026-04-29 00:25:24'),
(2, 1, 'book', 'quyển sách', 'This book is very interesting.', '/bʊk/', '', '2026-04-29 00:25:24'),
(3, 1, 'water', 'nước', 'Please drink more water.', '/ˈwɔː.tər/', '', '2026-04-29 00:25:24'),
(4, 1, 'school', 'trường học', 'My brother goes to school by bus.', '/skuːl/', '', '2026-04-29 00:25:24'),
(5, 1, 'teacher', 'giáo viên', 'The teacher explains the lesson clearly.', '/ˈtiː.tʃər/', '', '2026-04-29 00:25:24'),
(6, 1, 'improve', 'cải thiện', 'You can improve your English by practicing every day.', '/ɪmˈpruːv/', '', '2026-04-29 00:25:24'),
(7, 1, 'family', 'gia đình', 'My family has dinner together every night.', '/ˈfæm.əl.i/', '', '2026-04-29 00:25:24'),
(8, 1, 'friend', 'bạn bè', 'She is my best friend.', '/frend/', '', '2026-04-29 00:25:24'),
(9, 1, 'listen', 'lắng nghe', 'Listen carefully to the pronunciation.', '/ˈlɪs.ən/', '', '2026-04-29 00:25:24'),
(10, 1, 'write', 'viết', 'Write your answer in the blank.', '/raɪt/', '', '2026-04-29 00:25:24'),
(11, 2, 'meeting', 'cuộc họp', 'We have a meeting at ten o clock.', '/ˈmiː.tɪŋ/', '', '2026-04-29 00:25:24'),
(12, 2, 'customer', 'khách hàng', 'The customer asked for more information.', '/ˈkʌs.tə.mər/', '', '2026-04-29 00:25:24'),
(13, 2, 'business', 'kinh doanh', 'She studies business at university.', '/ˈbɪz.nɪs/', '', '2026-04-29 00:25:24'),
(14, 2, 'schedule', 'lịch trình', 'Please check the project schedule.', '/ˈskedʒ.uːl/', '', '2026-04-29 00:25:24'),
(15, 2, 'invoice', 'hóa đơn', 'The invoice was sent by email.', '/ˈɪn.vɔɪs/', '', '2026-04-29 00:25:24'),
(16, 2, 'budget', 'ngân sách', 'We need to reduce the marketing budget.', '/ˈbʌdʒ.ɪt/', '', '2026-04-29 00:25:24'),
(17, 2, 'deadline', 'hạn chót', 'The deadline is next Friday.', '/ˈded.laɪn/', '', '2026-04-29 00:25:24'),
(18, 2, 'contract', 'hợp đồng', 'They signed the contract yesterday.', '/ˈkɑːn.trækt/', '', '2026-04-29 00:25:24'),
(19, 2, 'manager', 'quản lý', 'The manager approved the plan.', '/ˈmæn.ə.dʒər/', '', '2026-04-29 00:25:24'),
(20, 2, 'presentation', 'bài thuyết trình', 'Her presentation was clear and professional.', '/ˌprez.ənˈteɪ.ʃən/', '', '2026-04-29 00:25:24'),
(21, 3, 'airport', 'sân bay', 'We arrived at the airport early.', '/ˈer.pɔːrt/', '', '2026-04-29 00:25:24'),
(22, 3, 'hotel', 'khách sạn', 'The hotel is near the beach.', '/hoʊˈtel/', '', '2026-04-29 00:25:24'),
(23, 3, 'ticket', 'vé', 'I bought a train ticket online.', '/ˈtɪk.ɪt/', '', '2026-04-29 00:25:24'),
(24, 3, 'passport', 'hộ chiếu', 'Do not forget your passport.', '/ˈpæs.pɔːrt/', '', '2026-04-29 00:25:24'),
(25, 3, 'luggage', 'hành lý', 'My luggage is heavy.', '/ˈlʌɡ.ɪdʒ/', '', '2026-04-29 00:25:24'),
(26, 3, 'reservation', 'sự đặt chỗ', 'I made a reservation for two nights.', '/ˌrez.ərˈveɪ.ʃən/', '', '2026-04-29 00:25:24'),
(27, 3, 'map', 'bản đồ', 'Can you show me the map?', '/mæp/', '', '2026-04-29 00:25:24'),
(28, 3, 'beach', 'bãi biển', 'We spent the afternoon on the beach.', '/biːtʃ/', '', '2026-04-29 00:25:24'),
(29, 3, 'station', 'nhà ga', 'The bus station is across the street.', '/ˈsteɪ.ʃən/', '', '2026-04-29 00:25:24'),
(30, 3, 'tourist', 'khách du lịch', 'Many tourists visit the old city.', '/ˈtʊr.ɪst/', '', '2026-04-29 00:25:24'),
(31, 4, 'legal consulting firm', 'Công ty tư vấn pháp lý', '', '/ˈliː.ɡəl/ /kənˈsʌltɪŋ/ /fɜːm/', '', '2026-04-29 01:16:55'),
(32, 4, 'architecture firm', 'công ty kiến trúc', '', '/ˈɑː.kɪ.ˌtɛk.tʃə/ /fɜːm/', '', '2026-04-29 01:16:55'),
(33, 4, 'book publishing company', 'Công ty xuất bản sách', '', '/bʊk/ /ˈpʌblɪʃɪŋ/ /ˈkʌmpəni/', '', '2026-04-29 01:16:55'),
(34, 4, 'be concerned (adj)', 'quan tâm, lo lắng', '', '/biː/ /kənˈsɜːnd/', '', '2026-04-29 01:16:55'),
(35, 4, 'length (n)', 'độ dài', '', '/lɛn(t)θ/', '', '2026-04-29 01:16:55'),
(36, 4, 'submit (v)', 'nộp', '', '/səbˈmɪt/', '', '2026-04-29 01:16:55'),
(37, 4, 'measure (v)', 'đo lường', '', '/ˈmɛʒə/', '', '2026-04-29 01:16:55'),
(38, 4, 'encourage (sbd to V)', 'khuyến khích ai làm gì', '', '/ɪnˈkʌrɪdʒ/', '', '2026-04-29 01:16:55'),
(39, 4, 'journalist (n)', 'nhà báo', '', '/ˈdʒɜːnəlɪst/', '', '2026-04-29 01:16:55'),
(40, 4, 'renovation', 'sự cải tạo', '', '/rɛnəˈveɪʃən/', '', '2026-04-29 01:16:55'),
(41, 4, 'painting (n)', 'bức họa', '', '/ˈpeɪn.tɪŋ/', '', '2026-04-29 01:16:55'),
(42, 4, 'temporary (adj)', 'tạm thời, nhất thời', '', '/ˈtɛmpəˌrɛri/', '', '2026-04-29 01:16:55'),
(43, 4, 'electronics (n)', 'đồ điện tử', '', '/ɪlɛˈktrɑnɪks/', '', '2026-04-29 01:16:55'),
(44, 4, 'automobile (n)', 'xe ô tô (=car)', '', '/ˈɔː.tə.məˌbiːl/', '', '2026-04-29 01:16:55'),
(45, 4, 'intranet (n)', 'mạng nội bộ', '', '/ˈɪntrəˌnɛt/', '', '2026-04-29 01:16:55'),
(46, 5, 'colleague (n)', 'đồng nghiệp', '', '/ˈkɒliːɡ/', '', '2026-04-29 01:16:55'),
(47, 5, 'administrative assistant (n)', 'trợ lý hành chính', '', '/ədˈmɪ.nəsˌtreɪ.ɾɪv/ /əˈsɪstənt/', '', '2026-04-29 01:16:55'),
(48, 5, 'director (n)', 'giám đốc', '', '/daɪˈrɛktɚ/', '', '2026-04-29 01:16:55'),
(49, 5, 'reserve (v)', 'đặt trước', '', '/rɪˈzɜːv/', '', '2026-04-29 01:16:55'),
(50, 5, 'responsible for (something) (adj)', 'chịu trách nhiệm cho cái gì', '', '/rɪˈspɒnsəbl̩/ /fɔːr/', '', '2026-04-29 01:16:55'),
(51, 5, 'review (v)', 'xem lại', '', '/rɪˈvjuː/', '', '2026-04-29 01:16:55'),
(52, 5, 'application (n)', 'đơn xin', '', '/ˌæpləˈkeɪʃən/', '', '2026-04-29 01:16:55'),
(53, 5, 'newsletter (n)', 'bản tin', '', '/ˈnjuːzˌlɛtə/', '', '2026-04-29 01:16:55'),
(54, 5, 'employee handbook (n)', 'sổ tay nhân viên', '', '/ɛˈmplɔɪiː/ /ˈhændbʊk/', '', '2026-04-29 01:16:55'),
(55, 5, 'trade show (n)', 'Triển lãm thương mại', '', '/treɪd/ /ʃəʊ/', '', '2026-04-29 01:16:55'),
(56, 5, 'product catalog (n)', '(n) danh mục sản phẩm', '', '/ˈprɒd.əkt/ /ˈkætəlɔːɡ/', '', '2026-04-29 01:16:55'),
(57, 5, 'press release (n)', 'thông cáo báo chí', '', '/ˈprɛs/ /riːˈliːs/', '', '2026-04-29 01:16:55'),
(58, 5, 'additional detail (n)', 'chi tiết bổ sung', '', '/əˈdɪʃənəl/ /dɪˈteɪl/', '', '2026-04-29 01:16:55'),
(59, 5, 'business card (n)', 'danh thiếp', '', '/ˈbɪz.nɪs/ /ˈkɑrd/', '', '2026-04-29 01:16:55'),
(60, 5, 'engineer (n)', 'kỹ sư', '', '/ˈɛndʒəˈnɪr/', '', '2026-04-29 01:16:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flashcard_tags`
--

CREATE TABLE `flashcard_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `flashcard_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `learning_classes`
--

CREATE TABLE `learning_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `invite_code` varchar(24) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `learning_classes`
--

INSERT INTO `learning_classes` (`id`, `owner_id`, `name`, `description`, `invite_code`, `created_at`) VALUES
(1, 1, 'English A1 Class', 'Lớp học mẫu dành cho người mới bắt đầu.', 'KHOA-A1', '2026-04-29 00:25:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `saved_sets`
--

CREATE TABLE `saved_sets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `set_tags`
--

CREATE TABLE `set_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'site_name', 'English Learning App', 'text', '2026-04-29 03:08:09'),
(2, 'site_tagline', 'Học tiếng Anh chủ động mỗi ngày', 'text', '2026-04-29 03:08:10'),
(3, 'registration_enabled', '1', 'boolean', '2026-04-29 03:08:10'),
(4, 'public_set_moderation', '0', 'boolean', '2026-04-29 03:08:10'),
(5, 'maintenance_mode', '1', 'boolean', '2026-04-29 03:25:58'),
(6, 'default_test_questions', '20', 'number', '2026-04-29 03:08:10'),
(7, 'blast_default_seconds', '60', 'number', '2026-04-29 03:08:10'),
(8, 'contact_email', 'support@example.com', 'text', '2026-04-29 03:08:10'),
(9, 'footer_text', 'English Learning App', 'text', '2026-04-29 03:08:10'),
(10, 'leaderboard_enabled', '1', 'boolean', '2026-04-29 03:08:10'),
(11, 'feedback_enabled', '1', 'boolean', '2026-04-29 03:08:10'),
(12, 'public_library_enabled', '1', 'boolean', '2026-04-29 03:08:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `study_sessions`
--

CREATE TABLE `study_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mode` varchar(50) NOT NULL,
  `set_id` int(10) UNSIGNED DEFAULT NULL,
  `cards_studied` int(11) NOT NULL DEFAULT 0,
  `duration_seconds` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `study_sessions`
--

INSERT INTO `study_sessions` (`id`, `user_id`, `mode`, `set_id`, `cards_studied`, `duration_seconds`, `created_at`) VALUES
(1, 3, 'match', 1, 1, 0, '2026-04-29 03:14:39'),
(2, 3, 'match', 1, 1, 0, '2026-04-29 03:14:41'),
(3, 3, 'match', 1, 1, 0, '2026-04-29 03:14:43'),
(4, 3, 'match', 1, 1, 0, '2026-04-29 03:14:46'),
(5, 3, 'match', 1, 1, 0, '2026-04-29 03:14:48'),
(6, 3, 'match', 1, 1, 0, '2026-04-29 03:14:53'),
(7, 3, 'match', 1, 1, 0, '2026-04-29 03:14:56'),
(8, 3, 'match', 1, 1, 0, '2026-04-29 03:14:58'),
(9, 3, 'match', 1, 1, 0, '2026-04-29 03:14:59'),
(10, 3, 'match', 1, 1, 0, '2026-04-29 03:15:00'),
(11, 3, 'test', 1, 10, 0, '2026-04-29 03:17:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'daily', 'daily', '2026-04-29 00:41:32'),
(2, 'work', 'work', '2026-04-29 00:41:32'),
(3, 'travel', 'travel', '2026-04-29 00:41:32'),
(4, 'beginner', 'beginner', '2026-04-29 00:41:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `test_results`
--

CREATE TABLE `test_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_questions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `mode` varchar(50) NOT NULL DEFAULT 'test',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `test_results`
--

INSERT INTO `test_results` (`id`, `user_id`, `set_id`, `score`, `total_questions`, `mode`, `created_at`) VALUES
(1, 1, 1, 8, 10, 'test', '2026-04-29 00:25:24'),
(2, 3, 1, 9, 10, 'test', '2026-04-29 03:17:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','teacher','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `last_login_at`, `avatar`, `bio`, `created_at`) VALUES
(1, 'Demo User', 'demo@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'user', 'active', '2026-04-29 10:33:55', NULL, NULL, '2026-04-29 00:25:24'),
(2, 'Student User', 'student@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'user', 'active', '2026-04-29 09:19:56', NULL, NULL, '2026-04-29 00:25:24'),
(3, 'Admin User', 'admin@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'admin', 'active', '2026-04-29 10:33:55', NULL, NULL, '2026-04-29 00:41:31'),
(4, 'Teacher User', 'teacher@example.com', '$2y$10$jCOLverY4Dtps5JfQYCFOuy55otyBfvD1f235EmPK4Q1rFlTICxjC', 'teacher', 'active', '2026-04-29 10:07:48', NULL, NULL, '2026-04-29 00:41:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(1, 3, 2, '2026-04-29 03:17:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_goals`
--

CREATE TABLE `user_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `daily_cards_goal` int(11) NOT NULL DEFAULT 20,
  `daily_minutes_goal` int(11) NOT NULL DEFAULT 10,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `card_id` int(10) UNSIGNED NOT NULL,
  `mode` varchar(50) NOT NULL,
  `correct_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wrong_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_answer` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `set_id`, `card_id`, `mode`, `correct_count`, `wrong_count`, `last_answer`, `updated_at`) VALUES
(1, 3, 1, 2, 'match', 1, 0, 'matched', '2026-04-29 03:14:39'),
(2, 3, 1, 10, 'match', 1, 0, 'matched', '2026-04-29 03:14:41'),
(3, 3, 1, 3, 'match', 1, 0, 'matched', '2026-04-29 03:14:43'),
(4, 3, 1, 1, 'match', 1, 0, 'matched', '2026-04-29 03:14:46'),
(5, 3, 1, 5, 'match', 1, 0, 'matched', '2026-04-29 03:14:48'),
(6, 3, 1, 6, 'match', 1, 0, 'matched', '2026-04-29 03:14:53'),
(7, 3, 1, 9, 'match', 1, 0, 'matched', '2026-04-29 03:14:56'),
(8, 3, 1, 8, 'match', 1, 0, 'matched', '2026-04-29 03:14:58'),
(9, 3, 1, 4, 'match', 1, 0, 'matched', '2026-04-29 03:14:59'),
(10, 3, 1, 7, 'match', 1, 0, 'matched', '2026-04-29 03:15:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vocabulary_sets`
--

CREATE TABLE `vocabulary_sets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` enum('private','public','class') NOT NULL DEFAULT 'private',
  `status` enum('active','pending','hidden','deleted') NOT NULL DEFAULT 'active',
  `deleted_at` datetime DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `level` enum('beginner','elementary','intermediate','upper_intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vocabulary_sets`
--

INSERT INTO `vocabulary_sets` (`id`, `user_id`, `class_id`, `title`, `description`, `is_public`, `visibility`, `status`, `deleted_at`, `approved_by`, `approved_at`, `category_id`, `level`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Basic English Vocabulary', 'Những từ vựng tiếng Anh cơ bản dùng hằng ngày.', 1, 'public', 'active', NULL, NULL, '2026-04-29 07:41:31', NULL, 'beginner', '2026-04-29 00:25:24', '2026-04-29 00:41:31'),
(2, 1, NULL, 'Business English', 'Từ vựng thường gặp trong công việc, họp hành và giao tiếp với khách hàng.', 0, 'private', 'active', NULL, NULL, NULL, NULL, 'beginner', '2026-04-29 00:25:24', '2026-04-29 00:25:24'),
(3, 1, 1, 'Travel English', 'Từ vựng cần thiết khi đi du lịch, đặt phòng và di chuyển.', 0, 'class', 'active', NULL, NULL, NULL, NULL, 'beginner', '2026-04-29 00:25:24', '2026-04-29 00:25:24'),
(4, 1, NULL, 'part 3 - 22T01 (Q 41 - 55)', 'Imported from vocab.json.', 0, 'private', 'active', NULL, NULL, NULL, NULL, 'beginner', '2026-04-29 01:16:55', '2026-04-29 01:16:55'),
(5, 1, NULL, 'part 3 - 22T01 (Q 32 - 40)', 'Imported from vocab.json.', 0, 'private', 'active', NULL, NULL, NULL, NULL, 'beginner', '2026-04-29 01:16:55', '2026-04-29 01:16:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vocabulary_set_permissions`
--

CREATE TABLE `vocabulary_set_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `set_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('viewer','editor','admin') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_announcements_user` (`created_by`);

--
-- Chỉ mục cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_assignments_class` (`class_id`),
  ADD KEY `fk_assignments_set` (`set_id`),
  ADD KEY `fk_assignments_user` (`created_by`);

--
-- Chỉ mục cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assignment_submission` (`assignment_id`,`user_id`),
  ADD KEY `fk_assignment_submissions_user` (`user_id`),
  ADD KEY `fk_assignment_submissions_test` (`test_result_id`);

--
-- Chỉ mục cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `class_members`
--
ALTER TABLE `class_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_class_member` (`class_id`,`user_id`),
  ADD KEY `fk_class_members_user` (`user_id`);

--
-- Chỉ mục cho bảng `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_user` (`user_id`);

--
-- Chỉ mục cho bảng `flashcards`
--
ALTER TABLE `flashcards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_flashcards_set` (`set_id`);

--
-- Chỉ mục cho bảng `flashcard_tags`
--
ALTER TABLE `flashcard_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_flashcard_tag` (`flashcard_id`,`tag_id`),
  ADD KEY `fk_flashcard_tags_tag` (`tag_id`);

--
-- Chỉ mục cho bảng `learning_classes`
--
ALTER TABLE `learning_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `fk_learning_classes_owner` (`owner_id`);

--
-- Chỉ mục cho bảng `saved_sets`
--
ALTER TABLE `saved_sets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_saved_set` (`user_id`,`set_id`),
  ADD KEY `fk_saved_sets_set` (`set_id`);

--
-- Chỉ mục cho bảng `set_tags`
--
ALTER TABLE `set_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_set_tag` (`set_id`,`tag_id`),
  ADD KEY `fk_set_tags_tag` (`tag_id`);

--
-- Chỉ mục cho bảng `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Chỉ mục cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_study_sessions_user` (`user_id`),
  ADD KEY `fk_study_sessions_set` (`set_id`);

--
-- Chỉ mục cho bảng `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `test_results`
--
ALTER TABLE `test_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_test_results_user` (`user_id`),
  ADD KEY `fk_test_results_set` (`set_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `fk_user_achievements_achievement` (`achievement_id`);

--
-- Chỉ mục cho bảng `user_goals`
--
ALTER TABLE `user_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_progress` (`user_id`,`set_id`,`card_id`,`mode`),
  ADD KEY `fk_user_progress_set` (`set_id`),
  ADD KEY `fk_user_progress_card` (`card_id`);

--
-- Chỉ mục cho bảng `vocabulary_sets`
--
ALTER TABLE `vocabulary_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vocabulary_sets_user` (`user_id`),
  ADD KEY `fk_vocabulary_sets_class` (`class_id`);

--
-- Chỉ mục cho bảng `vocabulary_set_permissions`
--
ALTER TABLE `vocabulary_set_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_set_permission` (`set_id`,`user_id`),
  ADD KEY `fk_set_permissions_user` (`user_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `class_members`
--
ALTER TABLE `class_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `flashcards`
--
ALTER TABLE `flashcards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT cho bảng `flashcard_tags`
--
ALTER TABLE `flashcard_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `learning_classes`
--
ALTER TABLE `learning_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `saved_sets`
--
ALTER TABLE `saved_sets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `set_tags`
--
ALTER TABLE `set_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `test_results`
--
ALTER TABLE `test_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `user_goals`
--
ALTER TABLE `user_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `vocabulary_sets`
--
ALTER TABLE `vocabulary_sets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `vocabulary_set_permissions`
--
ALTER TABLE `vocabulary_set_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assignments_class` FOREIGN KEY (`class_id`) REFERENCES `learning_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `fk_assignment_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment_submissions_test` FOREIGN KEY (`test_result_id`) REFERENCES `test_results` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assignment_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `class_members`
--
ALTER TABLE `class_members`
  ADD CONSTRAINT `fk_class_members_class` FOREIGN KEY (`class_id`) REFERENCES `learning_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `flashcards`
--
ALTER TABLE `flashcards`
  ADD CONSTRAINT `fk_flashcards_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `flashcard_tags`
--
ALTER TABLE `flashcard_tags`
  ADD CONSTRAINT `fk_flashcard_tags_flashcard` FOREIGN KEY (`flashcard_id`) REFERENCES `flashcards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_flashcard_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `learning_classes`
--
ALTER TABLE `learning_classes`
  ADD CONSTRAINT `fk_learning_classes_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `saved_sets`
--
ALTER TABLE `saved_sets`
  ADD CONSTRAINT `fk_saved_sets_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_saved_sets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `set_tags`
--
ALTER TABLE `set_tags`
  ADD CONSTRAINT `fk_set_tags_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_set_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD CONSTRAINT `fk_study_sessions_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_study_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `test_results`
--
ALTER TABLE `test_results`
  ADD CONSTRAINT `fk_test_results_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_test_results_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `fk_user_achievements_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_achievements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_goals`
--
ALTER TABLE `user_goals`
  ADD CONSTRAINT `fk_user_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `fk_user_progress_card` FOREIGN KEY (`card_id`) REFERENCES `flashcards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_progress_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `vocabulary_sets`
--
ALTER TABLE `vocabulary_sets`
  ADD CONSTRAINT `fk_vocabulary_sets_class` FOREIGN KEY (`class_id`) REFERENCES `learning_classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vocabulary_sets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `vocabulary_set_permissions`
--
ALTER TABLE `vocabulary_set_permissions`
  ADD CONSTRAINT `fk_set_permissions_set` FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_set_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
