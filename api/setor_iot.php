<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/saldo_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method harus POST', 'code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Input validation
$errors = [];
$kode_warga = trim($input['kode_warga'] ?? '');
$id_device  = trim($input['id_device'] ?? 'JHUB-01');
$berat_kg   = isset($input['berat_kg']) ? (float)$input['berat_kg'] : 0;
$event_id   = trim($input['event_id'] ?? ('EVT-' . time() . '-' . rand(100, 999)));

if ($kode_warga === '') {
    $errors[] = 'Kode warga wajib diisi';
} elseif (!preg_match('/^WRG-\d{3}$/', $kode_warga)) {
    $errors[] = 'Format kode warga tidak valid (harus WRG-XXX)';
}

if ($berat_kg <= 0) {
    $errors[] = 'Berat minyak harus lebih dari 0 kg';
}

if ($berat_kg > 100) {
    $errors[] = 'Berat melebihi batas maksimal sensor (100 kg)';
}

if ($event_id === '') {
    $errors[] = 'Event ID wajib diisi';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Validasi gagal: ' . implode('; ', $errors),
        'code' => 'VALIDATION_ERROR',
        'errors' => $errors
    ]);
    exit;
}

$hasil = prosesSetoranIoT($conn, $kode_warga, $id_device, $berat_kg, $event_id);

// Add HTTP status codes based on result
if (!$hasil['status']) {
    if (strpos($hasil['message'], 'duplikat') !== false) {
        http_response_code(409); // Conflict
        $hasil['code'] = 'DUPLICATE_EVENT';
    } elseif (strpos($hasil['message'], 'tidak ditemukan') !== false) {
        http_response_code(404);
        $hasil['code'] = 'USER_NOT_FOUND';
    } elseif (strpos($hasil['message'], 'PENUH') !== false) {
        http_response_code(503); // Service Unavailable
        $hasil['code'] = 'TANK_FULL';
    } elseif (strpos($hasil['message'], 'tidak terdaftar') !== false) {
        http_response_code(404);
        $hasil['code'] = 'DEVICE_NOT_FOUND';
    } else {
        http_response_code(500);
        $hasil['code'] = 'SERVER_ERROR';
    }
} else {
    http_response_code(201); // Created
    $hasil['code'] = 'SUCCESS';
}

echo json_encode($hasil);
?>
