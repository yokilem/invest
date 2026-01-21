-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 28 Ara 2025, 13:29:25
-- Sunucu sürümü: 10.6.24-MariaDB
-- PHP Sürümü: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `u2461468_invest3403-1`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `daily_earnings`
--

CREATE TABLE `daily_earnings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `gpu_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `earning_date` date DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `daily_earnings`
--

INSERT INTO `daily_earnings` (`id`, `user_id`, `gpu_id`, `amount`, `earning_date`, `is_paid`) VALUES
(1, 7, 6, 10.00, '2025-11-03', 0),
(2, 7, 6, 10.00, '2025-11-04', 0),
(3, 7, 18, 25.00, '2025-11-04', 0),
(4, 7, 6, 10.00, '2025-11-05', 0),
(5, 7, 18, 25.00, '2025-11-05', 0),
(6, 7, 6, 10.00, '2025-11-06', 0),
(7, 7, 18, 25.00, '2025-11-06', 0),
(8, 7, 6, 10.00, '2025-11-07', 0),
(9, 7, 18, 25.00, '2025-11-07', 0),
(10, 7, 6, 10.00, '2025-11-08', 0),
(11, 7, 18, 25.00, '2025-11-08', 0),
(12, 7, 6, 10.00, '2025-11-09', 0),
(13, 7, 18, 25.00, '2025-11-09', 0),
(14, 7, 6, 10.00, '2025-11-10', 0),
(15, 7, 18, 25.00, '2025-11-10', 0),
(16, 7, 6, 10.00, '2025-11-11', 0),
(17, 7, 18, 25.00, '2025-11-11', 0),
(18, 7, 6, 10.00, '2025-11-12', 0),
(19, 7, 18, 25.00, '2025-11-12', 0),
(20, 7, 6, 10.00, '2025-11-13', 0),
(21, 7, 18, 25.00, '2025-11-13', 0),
(22, 7, 6, 10.00, '2025-11-14', 0),
(23, 7, 18, 25.00, '2025-11-14', 0),
(24, 7, 6, 10.00, '2025-11-15', 0),
(25, 7, 18, 25.00, '2025-11-15', 0),
(26, 7, 6, 10.00, '2025-11-16', 0),
(27, 7, 18, 25.00, '2025-11-16', 0),
(28, 7, 6, 10.00, '2025-11-17', 0),
(29, 7, 18, 25.00, '2025-11-17', 0),
(30, 7, 6, 10.00, '2025-11-18', 0),
(31, 7, 18, 25.00, '2025-11-18', 0),
(32, 7, 6, 10.00, '2025-11-19', 0),
(33, 7, 18, 25.00, '2025-11-19', 0),
(34, 7, 6, 10.00, '2025-11-20', 0),
(35, 7, 18, 25.00, '2025-11-20', 0),
(36, 7, 6, 10.00, '2025-11-21', 0),
(37, 7, 18, 25.00, '2025-11-21', 0),
(38, 7, 6, 10.00, '2025-11-22', 0),
(39, 7, 18, 25.00, '2025-11-22', 0),
(40, 7, 6, 10.00, '2025-11-23', 0),
(41, 7, 18, 25.00, '2025-11-23', 0),
(42, 7, 6, 10.00, '2025-11-24', 0),
(43, 7, 18, 25.00, '2025-11-24', 0),
(44, 7, 6, 10.00, '2025-11-25', 0),
(45, 7, 18, 25.00, '2025-11-25', 0),
(46, 7, 6, 10.00, '2025-11-26', 0),
(47, 7, 18, 25.00, '2025-11-26', 0),
(48, 7, 6, 10.00, '2025-11-27', 0),
(49, 7, 18, 25.00, '2025-11-27', 0),
(50, 7, 6, 10.00, '2025-11-28', 0),
(51, 7, 18, 25.00, '2025-11-28', 0),
(52, 7, 6, 10.00, '2025-11-29', 0),
(53, 7, 18, 25.00, '2025-11-29', 0),
(54, 7, 6, 10.00, '2025-11-30', 0),
(55, 7, 18, 25.00, '2025-11-30', 0),
(56, 7, 6, 10.00, '2025-12-01', 0),
(57, 7, 18, 25.00, '2025-12-01', 0),
(58, 7, 6, 10.00, '2025-12-02', 0),
(59, 7, 18, 25.00, '2025-12-02', 0),
(60, 7, 6, 10.00, '2025-12-03', 0),
(61, 7, 18, 25.00, '2025-12-03', 0),
(62, 7, 6, 10.00, '2025-12-04', 0),
(63, 7, 18, 25.00, '2025-12-04', 0),
(64, 7, 6, 10.00, '2025-12-05', 0),
(65, 7, 18, 25.00, '2025-12-05', 0),
(66, 7, 6, 10.00, '2025-12-06', 0),
(67, 7, 18, 25.00, '2025-12-06', 0),
(68, 7, 6, 10.00, '2025-12-07', 0),
(69, 7, 18, 25.00, '2025-12-07', 0),
(70, 7, 6, 10.00, '2025-12-08', 0),
(71, 7, 18, 25.00, '2025-12-08', 0),
(72, 7, 6, 10.00, '2025-12-09', 0),
(73, 7, 18, 25.00, '2025-12-09', 0),
(74, 7, 6, 10.00, '2025-12-10', 0),
(75, 7, 18, 25.00, '2025-12-10', 0),
(76, 7, 6, 10.00, '2025-12-11', 0),
(77, 7, 18, 25.00, '2025-12-11', 0),
(78, 7, 6, 10.00, '2025-12-12', 0),
(79, 7, 18, 25.00, '2025-12-12', 0),
(80, 7, 6, 10.00, '2025-12-13', 0),
(81, 7, 18, 25.00, '2025-12-13', 0),
(82, 7, 6, 10.00, '2025-12-14', 0),
(83, 7, 18, 25.00, '2025-12-14', 0),
(84, 7, 6, 10.00, '2025-12-15', 0),
(85, 7, 18, 25.00, '2025-12-15', 0),
(86, 7, 6, 10.00, '2025-12-16', 0),
(87, 7, 18, 25.00, '2025-12-16', 0),
(88, 7, 6, 10.00, '2025-12-17', 0),
(89, 7, 18, 25.00, '2025-12-17', 0),
(90, 7, 6, 10.00, '2025-12-18', 0),
(91, 7, 18, 25.00, '2025-12-18', 0),
(92, 7, 6, 10.00, '2025-12-19', 0),
(93, 7, 18, 25.00, '2025-12-19', 0),
(94, 7, 6, 10.00, '2025-12-20', 0),
(95, 7, 18, 25.00, '2025-12-20', 0),
(96, 7, 6, 10.00, '2025-12-21', 0),
(97, 7, 18, 25.00, '2025-12-21', 0),
(98, 7, 6, 10.00, '2025-12-22', 0),
(99, 7, 18, 25.00, '2025-12-22', 0),
(100, 7, 6, 10.00, '2025-12-23', 0),
(101, 7, 18, 25.00, '2025-12-23', 0),
(102, 7, 6, 10.00, '2025-12-24', 0),
(103, 7, 18, 25.00, '2025-12-24', 0),
(104, 7, 6, 10.00, '2025-12-25', 0),
(105, 7, 18, 25.00, '2025-12-25', 0),
(106, 7, 6, 10.00, '2025-12-26', 0),
(107, 7, 18, 25.00, '2025-12-26', 0),
(108, 7, 6, 10.00, '2025-12-27', 0),
(109, 7, 18, 25.00, '2025-12-27', 0),
(110, 7, 6, 10.00, '2025-12-28', 0),
(111, 7, 18, 25.00, '2025-12-28', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gpus`
--

CREATE TABLE `gpus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `monthly_income` decimal(15,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `gpus`
--

INSERT INTO `gpus` (`id`, `name`, `description`, `price`, `monthly_income`, `commission_rate`, `image_path`, `stock`, `is_active`, `created_at`) VALUES
(5, 'ASUS DUAL GeForce RTX 5060 Ti OC 8GB GDDR7 128 Bit DLSS 4', 'ASUS Dual GeForce RTX™ 5060 Ti combines powerful thermal performance with broad compatibility. Advanced cooling solutions from flagship graphics cards — including two Axial-tech fans for optimizing airflow to the heatsink. Designed in a compact 2.5-slot form factor, delivering more power in less space.', 500.00, 300.00, 60.00, 'uploads/gpu_images/69079fd428b78_1762107348.jpg', 25, 1, '2025-11-02 18:15:48'),
(6, 'ASUS PRIME GeForce RTX 5060 Ti OC 8GB GDDR7 128 Bit DLSS 4', 'Experience Primal performance with the Prime GeForce RTX 5060 Ti, featuring a 2.5-slot design for expansive compatibility, enhanced by a triple-fan setup for supreme airflow design for supreme cooling.', 500.00, 300.00, 60.00, 'uploads/gpu_images/6907a0a5ee014_1762107557.jpg', 26, 1, '2025-11-02 18:19:17'),
(7, 'Experience Primal performance with the Prime GeForce RTX 5060 Ti, featuring a 2.5-slot design for ex', 'VENTUS focuses on the essentials to tackle any challenge. Its efficient thermal solution is encased in a resilient enclosure with a neutral aesthetic, allowing this sleek graphics card to integrate seamlessly into any build.', 500.00, 300.00, 60.00, 'uploads/gpu_images/6907a18482f99_1762107780.jpg', 30, 1, '2025-11-02 18:23:00'),
(8, 'MSI GeForce RTX 5060 Ti 8G SHADOW 2X OC PLUS 8GB GDDR7 128 Bit DLSS 4', 'Fifth-Gen Tensor Cores\r\nMax AI performance with FP4 and DLSS 4\r\n\r\nNew Streaming Multiprocessors\r\nOptimized for neural shaders\r\n\r\nFourth-Gen Ray Tracing Cores\r\nBuilt for Mega Geometry', 500.00, 300.00, 60.00, 'uploads/gpu_images/6907a2088290b_1762107912.jpg', 30, 1, '2025-11-02 18:25:12'),
(9, 'ASUS DUAL GeForce RTX 5060 Ti OC 16GB GDDR7 128 Bit DLSS 4 ', 'ASUS Dual GeForce RTX™ 5060 Ti combines powerful thermal performance with broad compatibility. Advanced cooling solutions from flagship graphics cards — including two Axial-tech fans for optimizing airflow to the heatsink. Designed in a compact 2.5-slot form factor, delivering more power in less space.', 600.00, 360.00, 60.00, 'uploads/gpu_images/6907a3811e63a_1762108289.jpg', 30, 1, '2025-11-02 18:31:29'),
(10, 'MSI GeForce RTX 5060 Ti 16G SHADOW 2X OC PLUS 16GB GDDR7 128 Bit DLSS 4', 'Fifth-Gen Tensor Cores\r\nMax AI performance with FP4 and DLSS 4\r\n\r\nNew Streaming Multiprocessors\r\nOptimized for neural shaders\r\n\r\nFourth-Gen Ray Tracing Cores\r\nBuilt for Mega Geometry', 600.00, 360.00, 60.00, 'uploads/gpu_images/6907a3ea90ddd_1762108394.jpg', 30, 1, '2025-11-02 18:33:14'),
(11, 'ASUS TUF Gaming GeForce RTX 5060 Ti OC 16GB GDDR7 128 Bit DLSS 4', 'NVIDIA Blackwell architecture is elevated by enhanced cooling and power delivery, fortified with rugged reinforcements for exceptional durability. Lock, load and dominate with the TUF Gaming GeForce RTX™ 5060 Ti, designed to withstand the harshest conditions and deliver unparalleled performance.', 750.00, 450.00, 60.00, 'uploads/gpu_images/6907a758a969e_1762109272.jpg', 25, 1, '2025-11-02 18:47:52'),
(12, 'ASUS DUAL GeForce RTX 5070 OC 12GB GDDR7 192 Bit DLSS 4', 'ASUS Dual GeForce RTX™ 5070 combines powerful thermal performance with broad compatibility. Advanced cooling solutions from flagship graphics cards — including two Axial-tech fans for optimizing airflow to the heatsink. Designed with a compact profile, delivering more power in less space. These enhancements make ASUS Dual the perfect choice for gamers who want heavyweight graphics performance in a compact build.', 750.00, 450.00, 60.00, 'uploads/gpu_images/6907a7b466588_1762109364.jpg', 25, 1, '2025-11-02 18:49:24'),
(13, 'MSI GeForce RTX 5070 12G SHADOW 3X OC 12GB GDDR7 192 Bit DLSS 4', 'MSI SHADOW brings a performance-focused design that delivers the gaming experience players want, making it the ideal choice when upgrading or building a gaming rig.', 900.00, 540.00, 60.00, 'uploads/gpu_images/6907a84b2c405_1762109515.jpg', 25, 1, '2025-11-02 18:51:55'),
(14, 'ASUS TUF Gaming GeForce RTX 5070 OC 12GB GDDR7 192 Bit DLSS 4', 'NVIDIA Blackwell architecture is elevated by enhanced cooling and power delivery, fortified with rugged reinforcements for exceptional durability. Lock, load and dominate with the TUF Gaming GeForce RTX™ 5070, designed to withstand the harshest conditions and deliver unparalleled performance.', 900.00, 540.00, 60.00, 'uploads/gpu_images/6907a88b57efd_1762109579.jpg', 25, 1, '2025-11-02 18:52:59'),
(15, 'GALAX GeForce RTX 5070 Ti 1-Click OC White 16GB GDDR7 256 Bit DLSS 4', 'Powered by the NVIDIA Blackwell Architecture and DLSS 4 \r\nDedicated Tensor Cores \r\nDedicated RT Cores \r\nMicrosoft DirectX® 12 Ultimate\r\nGDDR7 Graphics Memory\r\nNVIDIA DLSS 4\r\nNVIDIA® App\r\nNVIDIA G-SYNC®\r\nNVIDIA GPU Boost™\r\nGame Ready and NVIDIA Studio Drivers\r\nDisplayPort 2.1b, HDMI 2.1b\r\nVR Ready\r\nTo 1-Click OC, overclock, control ARGB and monitor GPU info of your graphics cards in-App, please make sure your mobile device and PC are connected with the same WiFi network.\r\nAll photos, specifications, contents are used for reference only and are subject to change without notice. Actual products in different countries varies and are best to consult your local distributor / importer for confirmation.', 1000.00, 600.00, 60.00, 'uploads/gpu_images/6907a9521eeb4_1762109778.jpg', 30, 1, '2025-11-02 18:56:18'),
(16, 'MSI GeForce RTX 5070 12G GAMING TRIO OC WHITE 12GB GDDR7 192 Bit DLSS 4', 'Fifth-Gen Tensor Cores\r\nMax AI performance with FP4 and DLSS 4\r\n\r\nNew Streaming Multiprocessors\r\nOptimized for neural shaders\r\n\r\nFourth-Gen Ray Tracing Cores\r\nBuilt for Mega Geometry', 1000.00, 600.00, 60.00, 'uploads/gpu_images/6907a9c525be1_1762109893.jpg', 30, 1, '2025-11-02 18:58:13'),
(17, 'GALAX GeForce RTX 5070 Ti HOF Gaming Black Edition 1-Click OC 16GB GDDR7 256 Bit DLSS 4', 'GALAX GeForce RTX™ 5070 Ti HOF Gaming Black Edition\r\n16GB GDDR7 256-bit DP2.1b*3/HDMI 2.1b/DLSS 4', 1250.00, 750.00, 60.00, 'uploads/gpu_images/6907aa55e98d0_1762110037.jpg', 30, 1, '2025-11-02 19:00:37'),
(18, 'MSI GeForce RTX 5070 Ti 16G GAMING TRIO OC 16GB GDDR7 256 Bit DLSS 4', 'MSI GeForce RTX 5070 Ti 16G GAMING TRIO OC 16GB GDDR7 256 Bit DLSS 4', 1250.00, 750.00, 60.00, 'uploads/gpu_images/6907aac52f52f_1762110149.jpg', 29, 1, '2025-11-02 19:02:29');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `investments`
--

CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `investment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `investments`
--

INSERT INTO `investments` (`id`, `user_id`, `amount`, `screenshot_path`, `status`, `investment_date`, `payment_method_id`) VALUES
(9, 7, 500.00, 'uploads/screenshots/6908ef8de18d3_1762193293.jpg', 'approved', '2025-11-03 18:08:13', 6),
(10, 7, 800.00, 'uploads/screenshots/6908f16493aa4_1762193764.jpg', 'approved', '2025-11-03 18:16:04', 6);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `coin_name` varchar(50) NOT NULL,
  `wallet_address` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `coin_name`, `wallet_address`, `is_active`) VALUES
(4, 'Bitcoin (BTC)', '161A3rSWmQaoUC2zdNvcUUQqegrs4VF769', 1),
(5, 'Ethereum (ERC20)', '0x3f70fFB6C04f07A755A999E846cfa99BCBB13d98', 1),
(6, 'USDT (TRC20)', 'TSNbRVoT3Em9KLs1iceHeuPFhgrvNRZN6Q', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `gpu_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `gpu_id`, `amount`, `screenshot_path`, `status`, `purchase_date`, `payment_method_id`) VALUES
(77, 7, 6, 500.00, 'uploads/screenshots/6908ccef450f5_1762184431.jpg', 'approved', '2025-11-03 15:40:31', 6),
(78, 7, 6, 500.00, 'uploads/screenshots/6908d4524b79e_1762186322.jpg', 'approved', '2025-11-03 16:12:02', 6),
(79, 7, 18, 1250.00, 'uploads/screenshots/6908d9887fe7e_1762187656.jpg', 'approved', '2025-11-03 16:34:16', 6),
(80, 7, 6, 500.00, NULL, 'approved', '2025-11-03 18:16:30', NULL),
(81, 7, 6, 500.00, NULL, 'approved', '2025-11-04 17:13:35', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'GPU Invest', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(2, 'site_description', 'High-performance GPU investment platform', '2025-11-02 00:11:30', '2025-11-02 19:08:26'),
(3, 'currency', '$', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(4, 'currency_code', 'USD', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(5, 'min_investment', '500', '2025-11-02 00:11:30', '2025-11-02 00:11:48'),
(6, 'min_withdrawal', '75', '2025-11-02 00:11:30', '2025-11-02 00:11:48'),
(7, 'withdrawal_fee', '0.5', '2025-11-02 00:11:30', '2025-11-02 00:11:48'),
(8, 'support_email', 'admin@invesakprimesyrk.com', '2025-11-02 00:11:30', '2025-11-02 19:18:06'),
(9, 'maintenance_mode', '0', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(10, 'user_registration', '1', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(11, 'email_verification', '0', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(12, 'session_timeout', '60', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(13, 'force_ssl', '0', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(14, 'login_attempts', '1', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(15, 'password_strength', 'medium', '2025-11-02 00:11:30', '2025-11-02 00:11:30'),
(31, 'site_logo', 'assets/images/logo.png', '2025-11-02 00:52:22', '2025-11-02 00:52:22'),
(36, 'home_banner', 'assets/images/banners/home_banner.jpg', '2025-11-02 17:38:44', '2025-11-02 17:38:44'),
(37, 'feature_passive_income', 'assets/images/features/passive_income.jpg', '2025-11-02 17:39:10', '2025-11-02 17:39:10'),
(38, 'feature_high_commission', 'assets/images/features/high_commission.jpg', '2025-11-02 17:39:26', '2025-11-02 17:39:26'),
(39, 'feature_secure_investment', 'assets/images/features/secure_investment.png', '2025-11-02 17:50:25', '2025-11-02 17:50:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `theme` enum('light','dark') DEFAULT 'light',
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_earning_date` date DEFAULT NULL,
  `investment_balance` decimal(15,2) DEFAULT 0.00,
  `earnings_balance` decimal(15,2) DEFAULT 0.00,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `balance`, `theme`, `role`, `created_at`, `last_login`, `last_earning_date`, `investment_balance`, `earnings_balance`, `email_verified`, `verification_token`, `verification_token_expires`, `reset_token`, `reset_token_expires`) VALUES
(2, 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1500.00, 'light', 'user', '2025-11-01 14:26:54', NULL, NULL, 0.00, 0.00, 0, NULL, NULL, NULL, NULL),
(6, 'admin', 'admin@gpuinvest.com', '$2y$10$zPQk6vg1x62fTCWxbQSNB.Jx4W3AOMtSqBB2H0rj67fAK9LrnGzSS', 0.00, 'light', 'admin', '2025-11-01 14:43:07', '2025-11-28 18:00:36', NULL, 0.00, 0.00, 0, NULL, NULL, NULL, NULL),
(7, 'yoki', 'demo@demo.com', '$2y$10$zPQk6vg1x62fTCWxbQSNB.Jx4W3AOMtSqBB2H0rj67fAK9LrnGzSS', 2000.00, 'dark', 'user', '2025-11-02 21:24:10', '2025-11-28 17:58:37', '2025-11-03', 300.00, 1315.00, 0, '6257d1693dac1aa10bcf436b37830e313d66d08822e8f1f3b6d3467391b11e37', '2025-11-05 19:45:49', NULL, NULL),
(8, 'lemur', 'hesapicin4415@gmail.com', '$2y$10$mRr2.kcFHmkQ1lFMWs/70ev21oFR8tB4qjP1S4mdTm5Xjgfy8.8Z.', 0.00, 'light', 'user', '2025-11-04 19:19:46', '2025-11-20 10:24:50', NULL, 0.00, 0.00, 0, '25c883cbdac0413bc3160073ffa784bb83698f34a9b44a6aec45411f65f2cc8b', '2025-11-05 23:16:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_gpus`
--

CREATE TABLE `user_gpus` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `gpu_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `activation_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user_gpus`
--

INSERT INTO `user_gpus` (`id`, `user_id`, `gpu_id`, `purchase_id`, `purchase_date`, `activation_time`) VALUES
(12, 7, 6, 78, '2025-11-03 16:12:31', '2025-11-03 17:12:31'),
(13, 7, 18, 79, '2025-11-03 16:34:28', '2025-11-03 17:34:28'),
(14, 7, 6, 80, '2025-11-03 18:16:30', '2025-11-03 21:16:30'),
(15, 7, 6, 81, '2025-11-04 17:13:35', '2025-11-04 20:13:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `coin_type` varchar(10) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `user_id`, `amount`, `wallet_address`, `coin_type`, `status`, `created_at`) VALUES
(1, 7, 105.00, 'TSNbRVoT3Em9KLs1iceHeuPFhgrvNRZN6Q', 'TRX', 'rejected', '2025-11-06 18:19:45'),
(2, 7, 105.00, 'TSNbRVoT3Em9KLs1iceHeuPFhgrvNRZN6Q', 'TRX', 'rejected', '2025-11-06 18:22:01'),
(3, 7, 105.00, 'TSNbRVoT3Em9KLs1iceHeuPFhgrvNRZN6Q', 'USDT', 'approved', '2025-11-06 18:22:30'),
(4, 7, 505.00, 'dışıdvfvıd848t7764frkgkjfhvkdhklvjr48938oy4rt', 'USDT', 'approved', '2025-11-28 17:57:48');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `daily_earnings`
--
ALTER TABLE `daily_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `gpu_id` (`gpu_id`);

--
-- Tablo için indeksler `gpus`
--
ALTER TABLE `gpus`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Tablo için indeksler `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `gpu_id` (`gpu_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Tablo için indeksler `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `user_gpus`
--
ALTER TABLE `user_gpus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `gpu_id` (`gpu_id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Tablo için indeksler `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `daily_earnings`
--
ALTER TABLE `daily_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- Tablo için AUTO_INCREMENT değeri `gpus`
--
ALTER TABLE `gpus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Tablo için AUTO_INCREMENT değeri `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `user_gpus`
--
ALTER TABLE `user_gpus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `daily_earnings`
--
ALTER TABLE `daily_earnings`
  ADD CONSTRAINT `daily_earnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `daily_earnings_ibfk_2` FOREIGN KEY (`gpu_id`) REFERENCES `gpus` (`id`);

--
-- Tablo kısıtlamaları `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `investments_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Tablo kısıtlamaları `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`gpu_id`) REFERENCES `gpus` (`id`),
  ADD CONSTRAINT `purchases_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Tablo kısıtlamaları `user_gpus`
--
ALTER TABLE `user_gpus`
  ADD CONSTRAINT `user_gpus_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_gpus_ibfk_2` FOREIGN KEY (`gpu_id`) REFERENCES `gpus` (`id`),
  ADD CONSTRAINT `user_gpus_ibfk_3` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`);

--
-- Tablo kısıtlamaları `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
