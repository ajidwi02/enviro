-- ==============================================================================
-- Migration SQL: Pembuatan Tabel weather_aoi5
-- Project: Enviro Monitoring System - AOI 5
-- Database: enviro
-- Tanggal: 26 Oktober 2026
-- Deskripsi: Menyimpan rekaman snapshot kondisi cuaca luar pabrik AOI 5 per jam
-- ==============================================================================

USE `enviro`;

-- 1. Buat Tabel weather_aoi5
CREATE TABLE IF NOT EXISTS `weather_aoi5` (
    `id_transaction` VARCHAR(50) NOT NULL COMMENT 'Format ID Unik per jam: AOI5_WTR_YYYYMMDD_HH0000',
    `factory` VARCHAR(20) DEFAULT 'AOI 5' COMMENT 'Nama Factory',
    `weather_status` ENUM('Cerah', 'Mendung', 'Hujan') NOT NULL COMMENT 'Status Cuaca Standar Pabrik',
    `temp_outdoor` DECIMAL(4,1) DEFAULT NULL COMMENT 'Suhu Udara Luar Ruangan (°C)',
    `humidity_outdoor` DECIMAL(4,1) DEFAULT NULL COMMENT 'Kelembapan Udara Luar Ruangan (%)',
    `rainfall` DECIMAL(5,1) DEFAULT 0.0 COMMENT 'Curah Hujan (mm)',
    `wind_speed` DECIMAL(5,1) DEFAULT 0.0 COMMENT 'Kecepatan Angin (km/h)',
    `cloud_cover` INT DEFAULT 0 COMMENT 'Tutupan Awan (%)',
    `weather_code` INT DEFAULT 0 COMMENT 'Kode Cuaca WMO Internasional',
    `record_time` DATETIME NOT NULL COMMENT 'Waktu Pencatatan Data Cuaca',
    PRIMARY KEY (`id_transaction`),
    INDEX `idx_record_time` (`record_time`),
    INDEX `idx_weather_status` (`weather_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel Data Riwayat Cuaca Luar AOI 5';

-- ==============================================================================
-- End of Migration
-- ==============================================================================
