<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-deteksi BASE_URL agar tetap berjalan baik di subfolder htdocs maupun root server
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = '/SmartJelantah-rpl';
if (preg_match('#^(/[^/]+)#', $scriptName, $matches)) {
    $firstSegment = $matches[1];
    $knownTopFiles = ['/index.php', '/login.php', '/register.php', '/logout.php', '/simulator_iot.php'];
    $knownSubDirs = ['/admin', '/user', '/api'];
    if (!in_array($firstSegment, $knownTopFiles) && !in_array($firstSegment, $knownSubDirs)) {
        $baseUrl = $firstSegment;
    } else {
        $baseUrl = '';
    }
}
define('BASE_URL', $baseUrl);

$db_host = 'localhost';
$db_user = 'smartjelantah';
$db_pass = 'SmartJelantah2026!';
$db_name = 'smartjelantah_db';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Auto-seed akun demo jika tabel users masih kosong
$checkUser = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($checkUser) {
    $row = mysqli_fetch_assoc($checkUser);
    if ((int)$row['total'] === 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $wargaPass = password_hash('warga123', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, kode_warga, saldo_koperasi, total_kg) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $nama1 = 'Admin Koperasi';
        $email1 = 'admin@smartjelantah.id';
        $role1 = 'admin';
        $kode1 = 'ADM-001';
        $saldo1 = 0;
        $kg1 = 0;
        $stmt->bind_param("sssssdd", $nama1, $email1, $adminPass, $role1, $kode1, $saldo1, $kg1);
        $stmt->execute();

        $nama2 = 'Ibu Sari (Warga)';
        $email2 = 'warga@smartjelantah.id';
        $role2 = 'warga';
        $kode2 = 'WRG-001';
        $saldo2 = 27000;
        $kg2 = 4.5;
        $stmt->bind_param("sssssdd", $nama2, $email2, $wargaPass, $role2, $kode2, $saldo2, $kg2);
        $stmt->execute();
        $stmt->close();
    }
}
?>