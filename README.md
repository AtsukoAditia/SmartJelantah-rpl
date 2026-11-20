# SmartJelantah (JELANTAHUB)

**SmartJelantah: Rancang Bangun Platform Otomatisasi Penampungan Jelantah dan Integrasi Saldo Belanja Koperasi Berbasis IoT**

Aplikasi web *mobile-responsive* untuk mengelola penyetoran minyak jelantah rumah tangga melalui stasiun penampung pintar (*Smart Dropbox* berbasis IoT) yang terkonversi otomatis menjadi saldo belanja koperasi lingkungan.

---

## Anggota Tim & Pembagian Branch (Tugas 11)

| Anggota | Branch GitHub | Modul Tanggung Jawab |
| :--- | :--- | :--- |
| **Aditia** (`@AtsukoAditia`) | `feature/auth-user` | Setup Repo, Database, Autentikasi RBAC, & Dashboard Mobile Warga |
| **Sony** | `feature/admin-panel` | Dashboard Admin, Monitoring Kapasitas Tangki, Reset Kuras, & Export CSV |
| **eL Dimazki** | `feature/iot-simulator` | REST API Endpoint IoT (`api/setor_iot.php`), Logika Konversi Saldo, & Simulator Mesin IoT |

---

## Tech Stack
* **Backend:** PHP Native (PHP 8.x + MySQLi Prepared Statements)
* **Frontend:** HTML5, Bootstrap 5.3 (Mobile-First Responsive), Bootstrap Icons
* **Database:** MySQL / MariaDB (XAMPP)
* **IoT Interface:** REST API JSON (siap dihubungkan dengan NodeMCU ESP32 + Load Cell HX711 + Ultrasonik JSN-SR04T)

---

## Cara Menjalankan di Lokal (XAMPP)

1. **Letakkan Folder Proyek ke `htdocs` XAMPP**
   * Ekstrak atau clone repositori ini ke `C:\xampp\htdocs\SmartJelantah-rpl` (Windows) atau `/Applications/XAMPP/htdocs/SmartJelantah-rpl` (macOS).
2. **Jalankan Apache & MySQL**
   * Buka **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
3. **Import Database**
   * Buka browser dan akses `http://localhost/phpmyadmin`.
   * Buat database baru bernama **`smartjelantah_db`**.
   * Pilih tab **Import**, pilih file `database.sql` dari folder proyek, lalu klik **Go**.
4. **Akses Aplikasi di Browser**
   * Buka `http://localhost/SmartJelantah-rpl/` (gunakan mode *Inspect Element / Device Toolbar* di browser untuk melihat tampilan mobile warga).
   * Halaman Simulator Mesin IoT dapat diakses langsung melalui menu navigasi atau `http://localhost/SmartJelantah-rpl/simulator_iot.php`.

---

## Akun Demo Default

| Role | Email | Password | Kode Anggota |
| :--- | :--- | :--- | :--- |
| **Admin Koperasi** | `admin@smartjelantah.id` | `admin123` | `ADM-001` |
| **Warga (Penyetor)** | `warga@smartjelantah.id` | `warga123` | `WRG-001` |

---

## Roadmap Pengembangan Lanjutan (Maintenance Plan)
* **v1.0 (Current - MVP):** Autentikasi Warga & Admin, Dashboard Mobile Warga, Monitoring Kapasitas Tangki & Reset Pengurasan, Pengaturan Harga Beli/Kg, Export Laporan CSV, dan Virtual IoT Simulator.
* **v1.1 (Corrective Maintenance):** Penambahan validasi anti-spam request dan penanganan *offline queue sync* dari perangkat keras ESP32 saat sinyal Wi-Fi terputus.
* **v2.0 (Perfective Maintenance):** Integrasi modul **Kasir Koperasi (POS Potong Saldo Sembako)**, pemindaian QR Code kamera langsung, serta notifikasi WhatsApp otomatis saat tangki mencapai kapasitas kritis (>= 90%).
