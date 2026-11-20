# SmartJelantah: Platform Otomatisasi Penampungan Jelantah dan Integrasi Saldo Belanja Koperasi Berbasis IoT

Tugas Proyek Berkelanjutan Mata Kuliah **Rekayasa Perangkat Lunak (RPL)** — Universitas Indraprasta PGRI (UNINDRA).

Aplikasi web *mobile-responsive* untuk mengelola penyetoran minyak jelantah rumah tangga melalui stasiun penampung pintar (*Smart Dropbox* berbasis IoT) yang terkonversi otomatis menjadi saldo belanja koperasi lingkungan.

---

## Anggota Kelompok & Pembagian Branch (Tugas 11)

| Nama Lengkap | NPM | Email | Branch GitHub | Tanggung Jawab Modul |
| :--- | :--- | :--- | :--- | :--- |
| **Aditia Nugraha** | 202343501284 | `atsuko.aditia@gmail.com` | `feature/core-auth` | Setup Repository, Database, Autentikasi RBAC (`login.php`, `register.php`), & Deployment |
| **Sri Wahyuni** | 202343501188 | `yunni.studymail@gmail.com` | `feature/dashboard-warga` | Antarmuka Mobile Warga (`user/index.php`), Kartu Saldo, QR Code Identitas, & Riwayat Setoran |
| **Adit Tio Dharma** | 202343501179 | `Aditiodarma8@gmail.com` | `feature/admin-panel` | Dashboard Admin (`admin/index.php`), Monitoring Kapasitas Tangki, Reset Kuras, & Export CSV |
| **Muhamad Hilman Maulana** | 202343501245 | `muhamadhilmanmaulana325@gmail.com` | `feature/iot-simulator` | REST API Endpoint IoT (`api/setor_iot.php`), Logika Konversi Saldo, & Simulator Mesin IoT |

---

## Tech Stack
* **Backend:** PHP 8.x Native (MySQLi Prepared Statements)
* **Frontend:** HTML5, Bootstrap 5.3 (Mobile-First Responsive), Bootstrap Icons
* **Database:** MySQL / MariaDB (XAMPP)
* **IoT Interface:** REST API JSON (siap dihubungkan dengan NodeMCU ESP32 + Load Cell HX711 + Ultrasonik JSN-SR04T)

---

## Cara Menjalankan di Lokal (XAMPP)

1. **Clone Repository ke Folder `htdocs` XAMPP**
   ```bash
   cd C:\xampp\htdocs
   git clone [https://github.com/AtsukoAditia/SmartJelantah-rpl.git](https://github.com/AtsukoAditia/SmartJelantah-rpl.git)
   cd SmartJelantah-rpl
