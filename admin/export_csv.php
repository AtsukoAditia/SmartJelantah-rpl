<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_smartjelantah_' . date('Ymd_His') . '.csv');

// Filter logic (same as index.php)
$where = [];
$params = [];
$types = '';

if (!empty($_GET['tanggal_mulai'])) {
    $where[] = "DATE(s.waktu_setor) >= ?";
    $params[] = $_GET['tanggal_mulai'];
    $types .= 's';
}
if (!empty($_GET['tanggal_selesai'])) {
    $where[] = "DATE(s.waktu_setor) <= ?";
    $params[] = $_GET['tanggal_selesai'];
    $types .= 's';
}
if (!empty($_GET['kode_warga'])) {
    $where[] = "u.kode_warga LIKE ?";
    $params[] = '%' . $_GET['kode_warga'] . '%';
    $types .= 's';
}
if (!empty($_GET['id_device'])) {
    $where[] = "s.id_device = ?";
    $params[] = $_GET['id_device'];
    $types .= 's';
}

$sql = "SELECT s.*, u.nama, u.kode_warga FROM setoran_jelantah s JOIN users u ON s.id_user = u.id_user";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY s.waktu_setor DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$query = $stmt->get_result();

$output = fopen('php://output', 'w');
fputcsv($output, ['Event ID', 'Waktu Setor', 'ID Device', 'Kode Warga', 'Nama Warga', 'Berat (Kg)', 'Harga/Kg (Rp)', 'Nominal Saldo (Rp)']);

while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, [
        $row['event_id'],
        $row['waktu_setor'],
        $row['id_device'],
        $row['kode_warga'],
        $row['nama'],
        $row['berat_kg'],
        $row['harga_per_kg'],
        $row['nominal_saldo']
    ]);
}
fclose($output);
exit;
?>