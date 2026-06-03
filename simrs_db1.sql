-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 03, 2026 at 12:44 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simrs_db1`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrian`
--

CREATE TABLE `antrian` (
  `id` bigint UNSIGNED NOT NULL,
  `pendaftaran_id` bigint UNSIGNED NOT NULL,
  `unit_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `nomor_antrian` int NOT NULL,
  `kode_antrian` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('menunggu','dipanggil','pemeriksaan_awal','sedang_diperiksa','selesai_pemeriksaan','lunas','obat_diserahkan','tidak_hadir') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `waktu_panggil` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2024_01_01_000001_create_users_table', 1),
(2, '2024_01_01_000002_create_roles_table', 1),
(3, '2024_01_01_000003_create_pasien_table', 1),
(4, '2024_01_01_000004_create_unit_pemeriksaan_table', 1),
(5, '2024_01_01_000005_create_pendaftaran_antrian_table', 1),
(6, '2024_01_01_000007_create_password_reset_tokens_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pasien`
--

CREATE TABLE `pasien` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `nomor_rm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nik` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pasien` enum('umum','bpjs') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `no_bpjs` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` bigint UNSIGNED NOT NULL,
  `nomor_pendaftaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pasien_id` bigint UNSIGNED NOT NULL,
  `unit_id` bigint UNSIGNED NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'Administrator sistem', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(2, 'admin_perawat', 'Pendaftaran & antrian langsung', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(3, 'pasien', 'Daftar online & lihat antrian', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(4, 'perawat', 'Pemanggilan & assessment', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(5, 'dokter', 'Pemeriksaan & E-Resep', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(6, 'kasir', 'Pembayaran & antrian kasir', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(7, 'admin_kasir', 'Kelola harga', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(8, 'apoteker', 'Penyerahan obat', '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(9, 'admin_apotik', 'Kelola stok & harga obat', '2026-06-03 12:44:29', '2026-06-03 12:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `unit_pemeriksaan`
--

CREATE TABLE `unit_pemeriksaan` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_unit` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_pemeriksaan`
--

INSERT INTO `unit_pemeriksaan` (`id`, `kode_unit`, `nama_unit`, `deskripsi`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'A', 'Mata', NULL, 1, '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(2, 'B', 'Gigi', NULL, 1, '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(3, 'C', 'Penyakit Dalam', NULL, 1, '2026-06-03 12:44:29', '2026-06-03 12:44:29'),
(4, 'D', 'Jantung', NULL, 1, '2026-06-03 12:44:29', '2026-06-03 12:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verification_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `otp_expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `is_active`, `email_verification_token`, `email_verified_at`, `otp_expired_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'freejpgtopng1@gmail.com', '$2y$12$S3yRHMHlPwwIguy6V5DKtOpLpCtHMowOW94HcATRZAjGs1mVWSd4.', 1, NULL, '2026-06-03 12:44:30', NULL, '2026-06-03 12:44:30', '2026-06-03 12:44:30', NULL),
(2, 'derinayu@viamedika.id', '$2y$12$.bSlF38cdIUn8ri7P02nlewhytNZrxOnU8S31jEPBx4CKFYsr/M1i', 1, NULL, '2026-06-03 12:44:30', NULL, '2026-06-03 12:44:30', '2026-06-03 12:44:30', NULL),
(3, 'mijie@viamedika.id', '$2y$12$y4BljpxxW2zAFCINeaNAhOdp/A29LvHZeUOSUZd5fyPBQ1cqzAzZC', 1, NULL, '2026-06-03 12:44:30', NULL, '2026-06-03 12:44:30', '2026-06-03 12:44:30', NULL),
(4, 'janny@viamedika.id', '$2y$12$k09/w4a.pDZkuXeRZipo/.nDNQWhrrMXquWIHVW84ji0OG18QAOJ6', 1, NULL, '2026-06-03 12:44:31', NULL, '2026-06-03 12:44:31', '2026-06-03 12:44:31', NULL),
(5, 'kasir01@viamedika.id', '$2y$12$asYv9shG0OdBE0CBbs.XBuY3IWhbKCrzcnK1od.l/bMB/MN3tFJSO', 1, NULL, '2026-06-03 12:44:31', NULL, '2026-06-03 12:44:31', '2026-06-03 12:44:31', NULL),
(6, 'adminkasir@viamedika.id', '$2y$12$6ZJrr8uGtMLV31s7icGNJuhcM.C2BEchdAHoyY97LEqodHpQPgj7S', 1, NULL, '2026-06-03 12:44:31', NULL, '2026-06-03 12:44:31', '2026-06-03 12:44:31', NULL),
(7, 'apoteker01@viamedika.id', '$2y$12$UG.k.wnrmoPaC7fpoTx4DejNCS3uJwuscRDw6pEPsFXUDjqzKqUu2', 1, NULL, '2026-06-03 12:44:32', NULL, '2026-06-03 12:44:32', '2026-06-03 12:44:32', NULL),
(8, 'adminapotik@viamedika.id', '$2y$12$rL5ZDlCDH/vPSp7AYh/ReOD.7wTBJ5A7kKndIMlATJPIm3NLHHGla', 1, NULL, '2026-06-03 12:44:32', NULL, '2026-06-03 12:44:32', '2026-06-03 12:44:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 2, 2, NULL, NULL),
(3, 3, 4, NULL, NULL),
(4, 4, 5, NULL, NULL),
(5, 5, 6, NULL, NULL),
(6, 6, 7, NULL, NULL),
(7, 7, 8, NULL, NULL),
(8, 8, 9, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `antrian_pendaftaran_id_unit_id_unique` (`pendaftaran_id`,`unit_id`),
  ADD UNIQUE KEY `antrian_unit_id_tanggal_nomor_antrian_unique` (`unit_id`,`tanggal`,`nomor_antrian`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pasien_nomor_rm_unique` (`nomor_rm`),
  ADD UNIQUE KEY `pasien_nik_unique` (`nik`),
  ADD KEY `pasien_user_id_foreign` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD KEY `password_reset_tokens_email_index` (`email`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pendaftaran_nomor_pendaftaran_unique` (`nomor_pendaftaran`),
  ADD KEY `pendaftaran_pasien_id_foreign` (`pasien_id`),
  ADD KEY `pendaftaran_unit_id_foreign` (`unit_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_nama_role_unique` (`nama_role`);

--
-- Indexes for table `unit_pemeriksaan`
--
ALTER TABLE `unit_pemeriksaan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_pemeriksaan_kode_unit_unique` (`kode_unit`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  ADD KEY `user_roles_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pasien`
--
ALTER TABLE `pasien`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `unit_pemeriksaan`
--
ALTER TABLE `unit_pemeriksaan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `antrian`
--
ALTER TABLE `antrian`
  ADD CONSTRAINT `antrian_pendaftaran_id_foreign` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `antrian_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `unit_pemeriksaan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pasien`
--
ALTER TABLE `pasien`
  ADD CONSTRAINT `pasien_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `pendaftaran_pasien_id_foreign` FOREIGN KEY (`pasien_id`) REFERENCES `pasien` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pendaftaran_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `unit_pemeriksaan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
