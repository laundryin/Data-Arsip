CREATE DATABASE IF NOT EXISTS pengarsipan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pengarsipan;

CREATE TABLE IF NOT EXISTS `user` (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  nama_user VARCHAR(100) NOT NULL,
  jabatan ENUM('Kepala Arsiparis', 'Staff Arsiparis') NOT NULL,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS folder (
  id_folder INT AUTO_INCREMENT PRIMARY KEY,
  nama_folder VARCHAR(100) NOT NULL,
  tanggal_dibuat DATE NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS sop (
  id_sop INT AUTO_INCREMENT PRIMARY KEY,
  judul_sop VARCHAR(150) NOT NULL,
  informasi_sop TEXT NOT NULL,
  tanggal_update DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS dokumen (
  id_dokumen INT AUTO_INCREMENT PRIMARY KEY,
  id_folder INT NOT NULL,
  informasi_arsip TEXT NOT NULL,
  jumlah_arsip INT NOT NULL,
  tanggal_upload DATE NOT NULL,
  kondisi_arsip ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL,
  lokasi_file VARCHAR(255) NOT NULL,
  INDEX idx_dokumen_upload (tanggal_upload),
  CONSTRAINT fk_dokumen_folder FOREIGN KEY (id_folder) REFERENCES folder(id_folder) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS riwayat_pemusnahan (
  id_riwayat INT AUTO_INCREMENT PRIMARY KEY,
  nama_file VARCHAR(255) NOT NULL,
  tanggal_upload DATE NOT NULL,
  tanggal_pemusnahan DATETIME NOT NULL,
  nama_folder VARCHAR(100) NOT NULL,
  nama_user_pemusnah VARCHAR(100) NOT NULL
) ENGINE=InnoDB;
