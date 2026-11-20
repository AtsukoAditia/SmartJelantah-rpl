<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id_device = trim($_GET['id_device'] ?? '');

if ($id_device === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Parameter id_device wajib diisi',
        'code' => 'MISSING_PARAMETER'
    ]);
    exit;
}

// Validate device ID format
if (!preg_match('/^JHUB-\d{2}$/', $id_device)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Format ID perangkat tidak valid',
        'code' => 'INVALID_DEVICE_ID'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_device, nama_lokasi, alamat_lokasi, latitude, longitude, 
           kapasitas_maks_kg, berat_terisi_kg, harga_per_kg, status, updated_at
    FROM stasiun_iot 
    WHERE id_device = ?
");
$stmt->bind_param("s", $id_device);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'Stasiun IoT tidak ditemukan',
        'code' => 'STATION_NOT_FOUND'
    ]);
    exit;
}

$station = $result->fetch_assoc();
$persen_terisi = round(($station['berat_terisi_kg'] / $station['kapasitas_maks_kg']) * 100, 1);
$sisa_kapasitas = max(0, $station['kapasitas_maks_kg'] - $station['berat_terisi_kg']);

echo json_encode([
    'status' => true,
    'code' => 'SUCCESS',
    'data' => [
        'id_device' => $station['id_device'],
        'nama_lokasi' => $station['nama_lokasi'],
        'alamat_lokasi' => $station['alamat_lokasi'],
        'latitude' => (float)$station['latitude'],
        'longitude' => (float)$station['longitude'],
        'kapasitas_maks_kg' => (float)$station['kapasitas_maks_kg'],
        'berat_terisi_kg' => (float)$station['berat_terisi_kg'],
        'sisa_kapasitas_kg' => (float)$sisa_kapasitas,
        'persen_terisi' => (float)$persen_terisi,
        'harga_per_kg' => (float)$station['harga_per_kg'],
        'status' => $station['status'],
        'updated_at' => $station['updated_at'],
        'can_receive' => $station['status'] === 'aktif' && $persen_terisi < 90,
        'status_label' => $station['status'] === 'aktif' ? ($persen_terisi >= 90 ? 'Penuh' : 'Siap') : ucfirst($station['status'])
    ]
]);
?>