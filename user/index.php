<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('warga');

$id_user = $_SESSION['id_user'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$warga = $stmt->get_result()->fetch_assoc();

// Get all stations
$stations = mysqli_query($conn, "SELECT * FROM stasiun_iot WHERE status = 'aktif' ORDER BY id_device");
$stationsArray = [];
$stationCount = 0;
while ($s = mysqli_fetch_assoc($stations)) {
    $stationsArray[] = $s;
    $stationCount++;
}

// Get single station for tank status card (first active station)
$iot = $stationsArray[0] ?? null;
$persenTangki = ($iot && $iot['kapasitas_maks_kg'] > 0) ? round(($iot['berat_terisi_kg'] / $iot['kapasitas_maks_kg']) * 100, 1) : 0;

// Filter logic for history
$where = [];
$params = [$id_user];
$types = 'i';

if (!empty($_GET['tanggal_mulai'])) {
    $where[] = "DATE(waktu_setor) >= ?";
    $params[] = $_GET['tanggal_mulai'];
    $types .= 's';
}
if (!empty($_GET['tanggal_selesai'])) {
    $where[] = "DATE(waktu_setor) <= ?";
    $params[] = $_GET['tanggal_selesai'];
    $types .= 's';
}

$sql = "SELECT * FROM setoran_jelantah WHERE id_user = ?";
if ($where) {
    $sql .= " AND " . implode(' AND ', $where);
}
$sql .= " ORDER BY waktu_setor DESC";

$stmtHist = $conn->prepare($sql);
$stmtHist->bind_param($types, ...$params);
$stmtHist->execute();
$riwayat = $stmtHist->get_result();

// Stats for summary cards
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(berat_kg), 0) as total_berat,
        COALESCE(SUM(nominal_saldo), 0) as total_saldo,
        COUNT(*) as total_transaksi
    FROM setoran_jelantah WHERE id_user = $id_user
"));

$pageTitle = 'Dashboard Warga - SmartJelantah';
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="row g-3">
    <!-- Stats Summary Cards -->
    <div class="col-12">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <div class="card card-custom p-3 text-center h-100">
                    <div class="text-muted small mb-1">Total Setoran</div>
                    <div class="fw-bold text-primary fs-5"><?= number_format($stats['total_transaksi'], 0) ?> kali</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-custom p-3 text-center h-100">
                    <div class="text-muted small mb-1">Total Berat</div>
                    <div class="fw-bold text-success fs-5"><?= number_format($stats['total_berat'], 2) ?> kg</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-custom p-3 text-center h-100">
                    <div class="text-muted small mb-1">Total Saldo</div>
                    <div class="fw-bold text-warning fs-5">Rp <?= number_format($stats['total_saldo'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-custom p-3 text-center h-100">
                    <div class="text-muted small mb-1">Saldo Sekarang</div>
                    <div class="fw-bold text-dark fs-5">Rp <?= number_format($warga['saldo_koperasi'], 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile & QR Card -->
    <div class="col-md-6 col-lg-5">
        <div class="card card-custom brand-gradient text-white p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-warning text-dark mb-2">ID: <?= htmlspecialchars($warga['kode_warga']) ?></span>
                    <h5 class="mb-1"><?= htmlspecialchars($warga['nama']) ?></h5>
                    <p class="small opacity-75 mb-0">Anggota sejak <?= date('M Y', strtotime($warga['created_at'])) ?></p>
                </div>
                <div class="bg-white p-2 rounded">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($warga['kode_warga']) ?>&margin=2" alt="QR Warga" class="d-block">
                    <div class="text-dark fw-bold small mt-1 text-center"><?= htmlspecialchars($warga['kode_warga']) ?></div>
                </div>
            </div>
            
            <hr class="border-light opacity-25">
            
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="bg-white bg-opacity-10 rounded p-3">
                        <div class="fw-bold fs-4"><?= number_format($warga['saldo_koperasi'], 0, ',', '.') ?></div>
                        <div class="small opacity-75">Saldo Koperasi</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-white bg-opacity-10 rounded p-3">
                        <div class="fw-bold fs-4"><?= number_format($warga['total_kg'], 2) ?></div>
                        <div class="small opacity-75">Total kg Disetor</div>
                    </div>
                </div>
            </div>
            
            <hr class="border-light opacity-25">
            
            <div class="d-flex justify-content-between small">
                <span>Tarif Aktif: <strong>Rp <?= number_format($iot['harga_per_kg'] ?? 6000, 0, ',', '.') ?>/kg</strong></span>
                <span>Total Transaksi: <strong><?= $stats['total_transaksi'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Stations Overview Card -->
    <div class="col-md-6 col-lg-7">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-hdd-network text-success me-1"></i> Semua Stasiun IoT (<?= $stationCount ?>)</h6>
                <a href="/scan_qr.php" class="btn btn-sm btn-success fw-semibold">
                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR Stasiun
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Lokasi</th>
                                <th>Kapasitas</th>
                                <th>Terisi</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stationsArray as $s): 
                                $persen = round(($s['berat_terisi_kg'] / $s['kapasitas_maks_kg']) * 100, 1);
                                $sisa = max(0, $s['kapasitas_maks_kg'] - $s['berat_terisi_kg']);
                                $canReceive = $persen < 90;
                                $statusClass = $persen >= 90 ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success');
                                $statusLabel = $persen >= 90 ? 'Penuh' : ($persen >= 70 ? 'Hampir Penuh' : 'Siap');
                            ?>
                                <tr>
                                    <td><code class="small"><?= htmlspecialchars($s['id_device']) ?></code></td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($s['nama_lokasi']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($s['alamat_lokasi'] ?? '-') ?></small>
                                    </td>
                                    <td><?= $s['kapasitas_maks_kg'] ?> kg</td>
                                    <td class="fw-semibold"><?= $s['berat_terisi_kg'] ?> kg</td>
                                    <td class="text-success fw-semibold"><?= $sisa ?> kg</td>
                                    <td>
                                        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?> (<?= $persen ?>%)</span>
                                    </td>
                                    <td>
                                        <?php if ($canReceive): ?>
                                            <a href="/simulator_iot.php?device=<?= urlencode($s['id_device']) ?>" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-droplet-half"></i> Setor
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="bi bi-lock"></i> Tutup
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tank Status Card -->
    <div class="col-md-6 col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold mb-3 d-flex align-items-center">
                <i class="bi bi-hdd-rack text-success me-2"></i> Status Stasiun IoT
            </h6>
            <p class="text-muted small mb-2"><?= htmlspecialchars($iot['nama_lokasi'] ?? '-') ?> (<?= htmlspecialchars($iot['id_device'] ?? '-') ?>)</p>
            
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Kapasitas</span>
                    <span class="fw-bold"><?= $persenTangki ?>%</span>
                </div>
                <div class="progress" style="height: 14px;">
                    <div class="progress-bar <?= $persenTangki >= 90 ? 'bg-danger' : ($persenTangki >= 70 ? 'bg-warning' : 'bg-success') ?>" 
                         style="width: <?= min($persenTangki, 100) ?>%;" 
                         role="progressbar" 
                         aria-valuenow="<?= $persenTangki ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= $persenTangki ?>%
                    </div>
                </div>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="card card-custom bg-light p-2 text-center">
                        <small class="text-muted d-block">Terisi</small>
                        <strong><?= $iot['berat_terisi_kg'] ?? 0 ?> kg</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-custom bg-light p-2 text-center">
                        <small class="text-muted d-block">Sisa</small>
                        <strong class="text-success"><?= max(0, ($iot['kapasitas_maks_kg'] ?? 30) - ($iot['berat_terisi_kg'] ?? 0)) ?> kg</strong>
                    </div>
                </div>
            </div>
            
            <?php if ($persenTangki >= 90): ?>
                <div class="alert alert-danger py-2 small mb-2 d-flex align-items-center">
                    <i class="bi bi-lock-fill me-2"></i> Tangki Penuh (>=90%) - Inlet terkunci otomatis!
                </div>
            <?php elseif ($persenTangki >= 70): ?>
                <div class="alert alert-warning py-2 small mb-2 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i> Kapasitas mendekati batas (>=70%)
                </div>
            <?php else: ?>
                <div class="alert alert-success py-2 small mb-2 d-flex align-items-center">
                    <i class="bi bi-check-circle me-2"></i> Mesin Siap Menerima Setoran
                </div>
            <?php endif; ?>
            
            <?php if ($persenTangki < 90): ?>
                <a href="<?= BASE_URL ?>/simulator_iot.php" class="btn btn-success btn-sm w-100 fw-semibold">
                    <i class="bi bi-droplet-half me-1"></i> Setor Minyak Sekarang
                </a>
            <?php else: ?>
                <button class="btn btn-secondary btn-sm w-100" disabled>
                    <i class="bi bi-lock me-1"></i> Mesin Terkunci (Tangki Penuh)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Info Card -->
    <div class="col-md-6 col-lg-3">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Cara Setor Minyak</h6>
            <ol class="small text-muted mb-0 ps-3">
                <li class="mb-2">Bawa minyak jelantah ke stasiun</li>
                <li class="mb-2">Scan QR kode warga di mesin</li>
                <li class="mb-2">Tuang minyak ke inlet</li>
                <li class="mb-2">Tunggu konfirmasi di LCD</li>
                <li class="mb-0">Saldo otomatis masuk koperasi</li>
            </ol>
            <hr class="my-3">
            <h6 class="fw-bold mb-2 small"><i class="bi bi-qr-code me-1"></i> Scan QR Stasiun</h6>
            <p class="small text-muted mb-2">Scan QR di mesin untuk lihat detail kapasitas real-time</p>
            <a href="/scan_qr.php" class="btn btn-primary btn-sm w-100 fw-semibold">
                <i class="bi bi-camera-video me-1"></i> Buka Scanner
            </a>
        </div>
    </div>

    <!-- History with Filter -->
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-1"></i> Riwayat Setoran Jelantah</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="filterModalLabel"><i class="bi bi-funnel"></i> Filter Riwayat</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="GET" class="modal-body">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Dari Tanggal</label>
                                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['tanggal_mulai'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Sampai Tanggal</label>
                                    <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['tanggal_selesai'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i> Terapkan</button>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Mesin</th>
                                <th>Berat (kg)</th>
                                <th>Tarif/kg</th>
                                <th>Saldo Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($riwayat->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                        Belum ada riwayat setoran.
                                        <?php if ($where): ?>
                                            <br><a href="index.php" class="text-decoration-none small">Tampilkan semua</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($r = $riwayat->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d M Y H:i', strtotime($r['waktu_setor'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['id_device']) ?></span></td>
                                        <td class="fw-semibold"><?= number_format($r['berat_kg'], 2) ?> kg</td>
                                        <td>Rp <?= number_format($r['harga_per_kg'], 0, ',', '.') ?></td>
                                        <td class="text-success fw-bold">+ Rp <?= number_format($r['nominal_saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>