-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql302.infinityfree.com
-- Generation Time: Nov 15, 2026 at 10:18 AM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43415006_db_pengarsipan`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id_dokumen` int(11) NOT NULL,
  `id_folder` int(11) NOT NULL,
  `informasi_arsip` text NOT NULL,
  `jumlah_arsip` int(11) NOT NULL,
  `tanggal_upload` date NOT NULL,
  `kondisi_arsip` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL,
  `lokasi_file` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id_dokumen`, `id_folder`, `informasi_arsip`, `jumlah_arsip`, `tanggal_upload`, `kondisi_arsip`, `lokasi_file`) VALUES
(1, 4, 'COBA GIH', 1, '2026-11-15', 'Baik', 'ARSIP_20261115_145455_Picture1.png'),
(3, 5, 'wawa', 1, '2026-11-15', 'Baik', 'uploads/4f978f1fbd8b9641f01fca69fcf977fc.png'),
(4, 4, 'pdf', 1, '2026-11-15', 'Baik', 'uploads/ff34a28067c676a4303ec19324826301.pdf'),
(5, 4, 'KADALUARSA', 1, '2021-01-15', 'Baik', 'ARSIP_20261115_151955_Laporan_Keuangan_PT_Campina_20.xlsx');

-- --------------------------------------------------------

--
-- Table structure for table `folder`
--

CREATE TABLE `folder` (
  `id_folder` int(11) NOT NULL,
  `nama_folder` varchar(100) NOT NULL,
  `tanggal_dibuat` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folder`
--

INSERT INTO `folder` (`id_folder`, `nama_folder`, `tanggal_dibuat`) VALUES
(4, 'BAKEKOK', '2026-11-15'),
(5, 'OI', '2026-11-15');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pemusnahan`
--

CREATE TABLE `riwayat_pemusnahan` (
  `id_riwayat` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `tanggal_upload` date NOT NULL,
  `tanggal_pemusnahan` datetime NOT NULL,
  `nama_folder` varchar(100) NOT NULL,
  `nama_user_pemusnah` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_pemusnahan`
--

INSERT INTO `riwayat_pemusnahan` (`id_riwayat`, `nama_file`, `tanggal_upload`, `tanggal_pemusnahan`, `nama_folder`, `nama_user_pemusnah`) VALUES
(1, 'ARSIP_20261115_145551_SB2_9249.jpg', '2020-03-15', '2026-11-15 20:59:36', 'OI', 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `sop`
--

CREATE TABLE `sop` (
  `id_sop` int(11) NOT NULL,
  `judul_sop` varchar(150) NOT NULL,
  `informasi_sop` text NOT NULL,
  `tanggal_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sop`
--

INSERT INTO `sop` (`id_sop`, `judul_sop`, `informasi_sop`, `tanggal_update`) VALUES
(1, 'COBA', 'OIOIOI', '2026-11-15 20:54:03'),
(2, 'COBA', 'OIOIOI', '2026-11-15 20:54:03');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `jabatan` enum('Kepala Arsiparis','Staff Arsiparis') NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `username`, `nama_user`, `jabatan`, `password`) VALUES
(1, 'admin', 'Administrator', 'Kepala Arsiparis', 'admin123'),
(8, 'STAFF1', 'fau', 'Staff Arsiparis', 'STAFF111');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_folder` (`id_folder`);

--
-- Indexes for table `folder`
--
ALTER TABLE `folder`
  ADD PRIMARY KEY (`id_folder`);

--
-- Indexes for table `riwayat_pemusnahan`
--
ALTER TABLE `riwayat_pemusnahan`
  ADD PRIMARY KEY (`id_riwayat`);

--
-- Indexes for table `sop`
--
ALTER TABLE `sop`
  ADD PRIMARY KEY (`id_sop`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `folder`
--
ALTER TABLE `folder`
  MODIFY `id_folder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `riwayat_pemusnahan`
--
ALTER TABLE `riwayat_pemusnahan`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sop`
--
ALTER TABLE `sop`
  MODIFY `id_sop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_folder`) REFERENCES `folder` (`id_folder`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
