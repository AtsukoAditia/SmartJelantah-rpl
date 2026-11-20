<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/saldo_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method harus POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$kode_warga = trim($input['kode_warga'] ?? '');
$id_device  = trim($input['id_device'] ?? 'JHUB-01');
$berat_kg   = (float)($input['berat_kg'] ?? 0);
$event_id   = trim($input['event_id'] ?? ('EVT-' . time() . '-' . rand(100, 999)));

$hasil = prosesSetoranIoT($conn, $kode_warga, $id_device, $berat_kg, $event_id);
echo json_encode($hasil);
?>
