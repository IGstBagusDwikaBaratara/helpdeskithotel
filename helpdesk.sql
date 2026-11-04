-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2026 at 12:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `helpdesk`
--

-- --------------------------------------------------------

--
-- Table structure for table `aset`
--

CREATE TABLE `aset` (
  `id` int(11) NOT NULL,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(80) NOT NULL,
  `jenis` varchar(40) NOT NULL,
  `lokasi` varchar(60) DEFAULT NULL,
  `keterangan` varchar(200) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tiket`
--

CREATE TABLE `tiket` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(60) NOT NULL,
  `tanggal` date NOT NULL,
  `divisi` varchar(40) NOT NULL,
  `kategori1` varchar(40) NOT NULL,
  `kategori2` varchar(40) NOT NULL,
  `judul` varchar(90) NOT NULL,
  `lokasi` varchar(60) DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `tindakan` text DEFAULT NULL,
  `penanggung_id` int(11) DEFAULT NULL,
  `diambil_pada` datetime DEFAULT NULL,
  `status` enum('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `aset_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(40) NOT NULL,
  `nama` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `peran` enum('it','pelapor') NOT NULL DEFAULT 'pelapor',
  `pertanyaan` varchar(120) DEFAULT NULL,
  `jawaban_hash` varchar(255) DEFAULT NULL,
  `gagal_reset` int(11) NOT NULL DEFAULT 0,
  `terkunci_sampai` datetime DEFAULT NULL,
  `akses_it` enum('tidak','diminta','ditolak') NOT NULL DEFAULT 'tidak',
  `diminta_pada` datetime DEFAULT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `nama`, `password_hash`, `peran`, `pertanyaan`, `jawaban_hash`, `gagal_reset`, `terkunci_sampai`, `akses_it`, `diminta_pada`, `dibuat`) VALUES
(1, 'ONANASANTOS', 'EMYU TSUNAMI TROPHY', '$2y$10$bGWt0fmGebtvY1Crd.oqpuiFTWyA7Y7UgPXhwRBGW97JeOjwRer36', 'it', NULL, NULL, 0, NULL, 'tidak', NULL, '2026-10-23 01:38:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aset`
--
ALTER TABLE `aset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `tiket`
--
ALTER TABLE `tiket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tiket_user` (`user_id`),
  ADD KEY `fk_tiket_pj` (`penanggung_id`),
  ADD KEY `fk_tiket_aset` (`aset_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aset`
--
ALTER TABLE `aset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tiket`
--
ALTER TABLE `tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tiket`
--
ALTER TABLE `tiket`
  ADD CONSTRAINT `fk_tiket_aset` FOREIGN KEY (`aset_id`) REFERENCES `aset` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tiket_pj` FOREIGN KEY (`penanggung_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tiket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
