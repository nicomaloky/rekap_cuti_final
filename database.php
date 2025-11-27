<?php
// database.php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'disdik_bogor_cuti';

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// Set karakter set ke utf8
$conn->set_charset("utf8");

/*
--- STRUKTUR DATABASE (Jalankan di phpMyAdmin) ---

-- 1. Tabel Admin
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal untuk Login
INSERT INTO `admin` (`username`, `password`, `role`) VALUES
('admin', 'admin', 'admin'),
('viewer', 'viewer', 'view');

-- 2. Tabel Pegawai
CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `unit_kerja` varchar(255) NOT NULL,
  `tmt_pensiun` date DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Master Jenis Cuti
CREATE TABLE `jenis_cuti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_cuti` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Master Jenis Cuti
INSERT INTO `jenis_cuti` (`id`, `nama_cuti`) VALUES
(1, 'Cuti Tahunan'),
(2, 'Cuti Besar'),
(3, 'Cuti Sakit'),
(4, 'Cuti Melahirkan'),
(5, 'Cuti Karena Alasan Penting'),
(6, 'Cuti di Luar Tanggungan Negara');

-- 4. Tabel Cuti
CREATE TABLE `cuti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL,
  `jenis_cuti_id` int(11) DEFAULT NULL,
  `alasan_cuti` text NOT NULL,
  `lama_cuti` int(3) NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `alamat_cuti` varchar(255) NOT NULL,
  `telp` varchar(20) NOT NULL,
  `pertimbangan_atasan` varchar(50) DEFAULT NULL,
  `tgl_pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `pegawai_id` (`pegawai_id`),
  KEY `fk_jenis_cuti` (`jenis_cuti_id`),
  CONSTRAINT `cuti_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jenis_cuti` FOREIGN KEY (`jenis_cuti_id`) REFERENCES `jenis_cuti` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

*/
?>
