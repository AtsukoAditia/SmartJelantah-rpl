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
    alamat_lokasi TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    kapasitas_maks_kg FLOAT NOT NULL DEFAULT 30.0,
    berat_terisi_kg FLOAT NOT NULL DEFAULT 0.0,
    harga_per_kg DECIMAL(10, 2) NOT NULL DEFAULT 6000.00,
    status ENUM('aktif', 'maintenance', 'offline') NOT NULL DEFAULT 'aktif',
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

-- Insert multiple stations
INSERT IGNORE INTO stasiun_iot (id_device, nama_lokasi, alamat_lokasi, latitude, longitude, kapasitas_maks_kg, berat_terisi_kg, harga_per_kg, status)
VALUES 
('JHUB-01', 'Pos RW Lingkungan Nahara', 'Jl. Raya Nahara No. 45, RT 03/RW 07, Kel. Nahara, Kec. Cilodong, Depok', -6.3982, 106.8275, 30.0, 4.5, 6000.00, 'aktif'),
('JHUB-02', 'Koperasi Warga Citayam', 'Jl. Citayam Raya No. 12, RT 01/RW 04, Kel. Citayam, Kec. Sawangan, Depok', -6.4125, 106.7892, 30.0, 12.3, 6000.00, 'aktif'),
('JHUB-03', 'Dropbox RW 05 Bojongsari', 'Jl. Bojongsari Baru No. 88, RT 05/RW 05, Kel. Bojongsari, Kec. Bojongsari, Depok', -6.4358, 106.7654, 30.0, 22.7, 6000.00, 'aktif'),
('JHUB-04', 'Pos Jaga RW 12 Cimanggis', 'Jl. Cimanggis Raya Km 5, RT 12/RW 12, Kel. Cimanggis, Kec. Cimanggis, Depok', -6.3891, 106.8567, 30.0, 0.0, 6000.00, 'aktif'),
('JHUB-05', 'Koperasi Lestari Tapos', 'Jl. Tapos Raya No. 200, RT 02/RW 08, Kel. Tapos, Kec. Tapos, Depok', -6.4567, 106.8345, 30.0, 8.9, 6000.00, 'aktif');