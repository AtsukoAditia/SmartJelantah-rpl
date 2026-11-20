CREATE DATABASE IF NOT EXISTS smartjelantah_db;
USE smartjelantah_db;

CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('warga', 'admin') NOT NULL DEFAULT 'warga',
    kode_warga VARCHAR(20) NOT NULL UNIQUE,
    saldo_koperasi DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_kg FLOAT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stasiun_iot (
    id_device VARCHAR(30) PRIMARY KEY,
    nama_lokasi VARCHAR(100) NOT NULL,
    kapasitas_maks_kg FLOAT NOT NULL DEFAULT 30.0,
    berat_terisi_kg FLOAT NOT NULL DEFAULT 0.0,
    harga_per_kg DECIMAL(10, 2) NOT NULL DEFAULT 6000.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS setoran_jelantah (
    id_setoran INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(64) NOT NULL UNIQUE,
    id_user INT NOT NULL,
    id_device VARCHAR(30) NOT NULL,
    berat_kg FLOAT NOT NULL,
    harga_per_kg DECIMAL(10, 2) NOT NULL,
    nominal_saldo DECIMAL(12, 2) NOT NULL,
    waktu_setor TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_device) REFERENCES stasiun_iot(id_device) ON DELETE CASCADE
);

INSERT IGNORE INTO stasiun_iot (id_device, nama_lokasi, kapasitas_maks_kg, berat_terisi_kg, harga_per_kg)
VALUES ('JHUB-01', 'Pos RW Lingkungan Nahara', 30.0, 4.5, 6000.00);
