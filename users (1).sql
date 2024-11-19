-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 20, 2024 at 05:53 PM
-- Server version: 8.0.36-cll-lve
-- PHP Version: 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `w2jobs_w2jobsc2c`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credits` double NOT NULL DEFAULT '0',
  `category_id` int DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` int NOT NULL DEFAULT '1',
  `employment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `provider` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `plan_id` int DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `is_star` tinyint(1) NOT NULL DEFAULT '0',
  `will_expire` date DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `avatar`, `role`, `email`, `phone`, `address`, `credits`, `category_id`, `meta`, `status`, `employment`, `provider`, `provider_id`, `plan`, `plan_id`, `email_verified_at`, `kyc_verified_at`, `is_star`, `will_expire`, `password`, `deleted_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Alex', 'admin', 'https://w2jobs.us/uploads/1/24/06/1718407538.png', 'admin', 'support@w2jobs.net', '2057943520', '8606 Adelphi road, Washington DC, Maryland, 20740', 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '$2y$10$lUb2llvAPzwrXvrgT.VsruspXcj5sNfxzDXNL6.LrC4/52utRf9YO', NULL, NULL, '2024-03-14 02:25:08', '2024-06-14 12:55:38'),
(3, 'American Jobs', 'americanjobs1wr', NULL, 'employer', 'admin@w2jobs.net', NULL, NULL, 0, 23, '{\"company\":{\"name\":\"American Jobs\",\"size\":\"1-15\",\"year_of_establishment\":\"2020-01-02\",\"address\":null,\"intro\":\"American Jobs\",\"teams\":[\"https:\\/\\/w2j.us\\/uploads\\/24\\/03\\/1710497215RyCqLmvDh4jinwHMip5h.jpg\"]},\"contact\":{\"name\":\"Alex\",\"designation\":\"CEO\",\"email\":\"admin@w2jobs.net\",\"mobile\":\"Alex\"},\"business\":{\"description\":\"test\",\"license_no\":\"1\",\"rl_no\":\"1\",\"site_url\":\"https:\\/\\/w2j.us\\/\"},\"social\":{\"linkedin\":null,\"twitter\":null,\"facebook\":null,\"instagram\":null}}', 1, NULL, NULL, NULL, '{\"job_limit\":10,\"featured_jobs\":5,\"live_job_for_days\":60,\"ai_credits\":1000}', 1, '2024-03-14 23:32:20', '2024-03-14 23:32:20', 1, '2024-04-14', '$2y$10$xULfPdmvORy1TpHEQpvCPOPILcdNtU6v5tjJ5zunbA8x/nAwOLJPK', NULL, NULL, '2024-03-14 23:29:33', '2024-03-14 23:51:33'),
(4, 'Trade Jobs', 'tradejobs', NULL, 'employer', 'info@w2jobs.net', NULL, NULL, 0, 93, '{\"company\":{\"name\":\"Trade Jobs\",\"size\":\"51-120\",\"year_of_establishment\":\"2017-01-25\",\"address\":\"Trade Jobs\",\"intro\":\"Trade Jobs\",\"teams\":[\"https:\\/\\/w2j.us\\/uploads\\/24\\/04\\/1713352260Jkz271wyFcyQOudDQ0mE.jpg\"]},\"contact\":{\"name\":\"Alex\",\"designation\":\"Job Manager\",\"email\":\"info@w2jobs.net\",\"mobile\":null},\"business\":{\"description\":\"Our company provides Trade & non tech jobs.\",\"license_no\":null,\"rl_no\":null,\"site_url\":null},\"social\":{\"linkedin\":null,\"twitter\":null,\"facebook\":null,\"instagram\":null}}', 1, NULL, NULL, NULL, '{\"job_limit\":20,\"featured_jobs\":10,\"live_job_for_days\":90,\"ai_credits\":1000}', 2, '2024-04-17 00:50:17', '2024-04-17 00:50:17', 1, '2024-05-24', '$2y$10$waNRfuFMLmRRVnCoTGNLjuxVi3IMsQ3MmyJ5cMsL6vPOR5Sw6dnwu', NULL, NULL, '2024-04-16 23:35:03', '2024-04-25 20:09:23'),
(6, 'William Andrew', 'williamandrew', NULL, 'user', 'wandrew@yopmail.com', NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2024-05-01 00:45:47', NULL, 1, NULL, '$2y$10$6K6/VjoNdGQOxgW6eXv/ouZA7qz1kPz.CwsImi874/q070At5x6X2', NULL, NULL, '2024-04-24 02:55:39', '2024-05-01 00:45:47'),
(7, 'C2C Jobs', 'c2cjobs', 'https://w2jobs.us/uploads/24/06/JglNljVCbysu28SABnBh.png', 'employer', 'c2cjobs@yopmail.com', NULL, NULL, 0, 175, '{\"company\":{\"name\":\"C2C Jobs\",\"size\":\"16-30\",\"year_of_establishment\":\"2010-01-01\",\"address\":\"C2C Jobs\",\"intro\":\"C2C Jobs\",\"teams\":[\"https:\\/\\/w2jobs.us\\/uploads\\/24\\/06\\/1718621913kFNXz5uewflHiPamPLVK.jpg\",\"https:\\/\\/w2jobs.us\\/uploads\\/24\\/06\\/1718621913mS0TfrpLr9sHiJvsKFuK.jpg\",\"https:\\/\\/w2jobs.us\\/uploads\\/24\\/06\\/1718621913b77E3fYe7KOxALXz0xw5.jpg\"],\"video_id\":null,\"video_intro\":null},\"contact\":{\"name\":\"C2C Jobs\",\"designation\":\"W2 Jobs\",\"email\":\"c2cjobs@yopmail.com\",\"mobile\":\"205-794-3520\"},\"business\":{\"description\":\"C2C Jobs\",\"license_no\":null,\"rl_no\":null,\"site_url\":null},\"social\":{\"linkedin\":null,\"twitter\":null,\"facebook\":null,\"instagram\":null}}', 1, NULL, NULL, NULL, '{\"job_limit\":200,\"featured_jobs\":100,\"live_job_for_days\":800,\"ai_credits\":1000}', 2, '2024-06-18 02:14:00', '2024-06-18 02:14:00', 0, '2025-06-17', '$2y$10$vSA1RRcPYxtZuJnTgAvkxenWc9tT8WvbeURH3AVP7CJaeuHxUgtl.', NULL, NULL, '2024-06-17 00:21:54', '2024-06-18 02:14:00'),
(8, 'Sekar Bachala', 'sekarbachala', NULL, 'employer', 'sekar.b@dataviolet.com', NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2024-07-02 15:53:32', NULL, 0, NULL, '$2y$10$D6A7fx.SBWDiu6jmlx64se93bmb9Spoi1XukrV5fytjVT4GzvD1Im', NULL, NULL, '2024-07-02 15:47:40', '2024-07-02 15:53:32'),
(9, 'Sandhya Mandapaka', 'sandhyamandapaka', NULL, 'user', 'sandhyacpj54@gmail.com', '571-758-5729', 'Ashburn, VA', 0, 173, '{\"overview\":\"With around 9 years of experience as a .Net Developer, I have expertise in designing, developing, and maintaining efficient applications using C#, ASP.NET, and AngularJS. I have worked across all phases of the SDLC in both Waterfall and Agile methodologies. My skills include developing web and desktop applications, proficiency in OOP concepts, design patterns, and advanced JavaScript\\/UI frameworks like Angular 13 and Bootstrap. I have strong experience with tools like Visual Studio and SQL Profiler, and I excel in developing REST services, integrating them with NodeJS and Angular, and working with ASP.NET Core and Entity Framework Core to create scalable, maintainable applications.\",\"gender\":\"female\",\"date_of_birth\":\"1991-05-04\",\"step_completed\":\"5\",\"country_id\":8,\"state_id\":65,\"address\":\"Ashburn, VA\",\"expert_level\":1,\"expected_salary\":65,\"currency\":\"USD\",\"work_experiences\":[],\"service_id\":173,\"category_id\":213,\"skills\":[254,261,256,255,257,258,262],\"resume\":\"https:\\/\\/w2jobs.us\\/uploads\\/24\\/07\\/HuN3iC82h7U2tbKxVuDK.docx\",\"social\":{\"facebook\":null,\"linkedin\":null,\"twitter\":null,\"instagram\":null}}', 1, NULL, NULL, NULL, NULL, NULL, '2024-07-24 07:39:31', NULL, 0, NULL, '$2y$10$/g7C6H26Ec7/6HVaAwIytewkSIxq80qtXfcwrd8P24dHesR67SHH2', NULL, NULL, '2024-07-24 07:38:11', '2024-07-24 07:42:41'),
(10, 'John Ke', 'johnke', NULL, 'user', 'johnke@yopmail.com', '1234567890', 'al', 0, 171, '{\"overview\":\"test\",\"gender\":\"male\",\"date_of_birth\":\"1999-04-18\",\"step_completed\":\"4\",\"country_id\":8,\"state_id\":14,\"address\":\"al\",\"expert_level\":1,\"expected_salary\":null,\"currency\":\"USD\",\"work_experiences\":[],\"service_id\":171,\"category_id\":211,\"skills\":[225,231,226,227,228,229,230]}', 0, NULL, NULL, NULL, NULL, NULL, '2024-08-19 19:35:51', NULL, 0, NULL, '$2y$10$OcNy6apjXUG9zWn1FNoA5uA4qR20lJK4GDy4iErLGyL5JIzDekd3u', NULL, NULL, '2024-08-19 19:35:20', '2024-08-19 19:43:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
