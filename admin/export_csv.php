<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_smartjelantah_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Event ID', 'Waktu Setor', 'ID Device', 'Kode Warga', 'Nama Warga', 'Berat (Kg)', 'Harga/Kg (Rp)', 'Nominal Saldo (Rp)']);

$query = mysqli_query($conn, "SELECT s.*, u.nama, u.kode_warga FROM setoran_jelantah s JOIN users u ON s.id_user = u.id_user ORDER BY s.waktu_setor DESC");
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
