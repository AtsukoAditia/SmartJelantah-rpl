<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('admin');

$notif = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_harga'])) {
        $id_device = $_POST['id_device'] ?? 'JHUB-01';
        $hargaBaru = (float)$_POST['harga_per_kg'];
        if ($hargaBaru > 0) {
            $stmt = $conn->prepare("UPDATE stasiun_iot SET harga_per_kg = ? WHERE id_device = ?");
            $stmt->bind_param("ds", $hargaBaru, $id_device);
            $stmt->execute();
            $notif = 'Harga beli jelantah untuk ' . $id_device . ' berhasil diperbarui!';
        }
    } elseif (isset($_POST['kuras_tangki'])) {
        $id_device = $_POST['id_device'] ?? 'JHUB-01';
        mysqli_query($conn, "UPDATE stasiun_iot SET berat_terisi_kg = 0 WHERE id_device = ?", "s", [$id_device]);
        $notif = 'Pengurasan tangki ' . $id_device . ' dicatat. Kapasitas di-reset ke 0 kg!';
    } elseif (isset($_POST['update_status'])) {
        $id_device = $_POST['id_device'] ?? '';
        $status = $_POST['status'] ?? 'aktif';
        if ($id_device) {
            $stmt = $conn->prepare("UPDATE stasiun_iot SET status = ? WHERE id_device = ?");
            $stmt->bind_param("ss", $status, $id_device);
            $stmt->execute();
            $notif = 'Status ' . $id_device . ' diubah menjadi ' . ucfirst($status) . '!';
        }
    }
}

// Get all stations
$stations = mysqli_query($conn, "SELECT * FROM stasiun_iot ORDER BY id_device");
$stationsArray = [];
while ($s = mysqli_fetch_assoc($stations)) {
    $stationsArray[] = $s;
}

// Overall stats
$stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(berat_kg),0) AS total_berat, COALESCE(SUM(nominal_saldo),0) AS total_saldo FROM setoran_jelantah"));

// Count alerts
$alertCount = 0;
$fullCount = 0;
$warningCount = 0;
foreach ($stationsArray as $s) {
    $persen = ($s['kapasitas_maks_kg'] > 0) ? round(($s['berat_terisi_kg'] / $s['kapasitas_maks_kg']) * 100, 1) : 0;
    if ($persen >= 90) {
        $fullCount++;
        $alertCount++;
    } elseif ($persen >= 70) {
        $warningCount++;
        $alertCount++;
    }
}

// Filter logic for transaction history
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
$transaksi = $stmt->get_result();

$pageTitle = 'Dashboard Admin - SmartJelantah';
require_once __DIR__ . '/../layouts/header.php';
?>
<?php if ($notif): ?>
    <div class="alert alert-success alert-dismissible fade show small"><?= htmlspecialchars($notif) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Alert Summary -->
<?php if ($alertCount > 0): ?>
<div class="alert alert-<?= $fullCount > 0 ? 'danger' : 'warning' ?> alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="bi bi-<?= $fullCount > 0 ? 'exclamation-triangle' : 'info-circle' ?> fs-4 me-3"></i>
        <div>
            <strong><?= $fullCount > 0 ? 'PERINGATAN KRITIS' : 'PERHATIAN' ?></strong><br>
            <small>
                <?= $fullCount ?> stasiun penuh (>=90%), <?= $warningCount ?> stasiun hampir penuh (>=70%).
                <a href="#stationsTable" class="text-decoration-underline">Lihat detail</a>
            </small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <span class="text-muted small">Total Jelantah Terkumpul</span>
            <h3 class="fw-bold text-success mb-0"><?= number_format($stat['total_berat'], 2) ?> kg</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <span class="text-muted small">Total Saldo Warga Diterbitkan</span>
            <h3 class="fw-bold text-primary mb-0">Rp <?= number_format($stat['total_saldo'], 0, ',', '.') ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <span class="text-muted small">Total Stasiun Aktif</span>
            <h3 class="fw-bold text-info mb-0"><?= count(array_filter($stationsArray, fn($s) => $s['status'] === 'aktif')) ?> / <?= count($stationsArray) ?></h3>
        </div>
    </div>
</div>

<!-- Stations Monitoring Table -->
<div class="card card-custom mb-4" id="stationsTable">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-hdd-network text-success me-1"></i> Monitoring Semua Stasiun IoT</h6>
        <span class="badge bg-primary"><?= count($stationsArray) ?> Stasiun</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lokasi</th>
                        <th>Alamat</th>
                        <th>Kapasitas Maks</th>
                        <th>Terisi</th>
                        <th>Sisa</th>
                        <th>Persen</th>
                        <th>Status</th>
                        <th>Harga/kg</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stationsArray as $s): 
                        $persen = ($s['kapasitas_maks_kg'] > 0) ? round(($s['berat_terisi_kg'] / $s['kapasitas_maks_kg']) * 100, 1) : 0;
                        $sisa = max(0, $s['kapasitas_maks_kg'] - $s['berat_terisi_kg']);
                        $canReceive = $persen < 90 && $s['status'] === 'aktif';
                        $progressClass = $persen >= 90 ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success');
                        $statusClass = $s['status'] === 'aktif' ? ($persen >= 90 ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success')) : 'bg-secondary';
                        $statusLabel = $s['status'] === 'aktif' ? ($persen >= 90 ? 'PENUH' : ($persen >= 70 ? 'HAMPIR PENUH' : 'SIAP')) : ucfirst($s['status']);
                    ?>
                        <tr class="<?= $persen >= 90 ? 'table-danger' : ($persen >= 70 ? 'table-warning' : '') ?>">
                            <td><code class="small"><?= htmlspecialchars($s['id_device']) ?></code></td>
                            <td class="fw-medium"><?= htmlspecialchars($s['nama_lokasi']) ?></td>
                            <td class="small text-muted" style="max-width: 200px;"><?= htmlspecialchars($s['alamat_lokasi'] ?? '-') ?></td>
                            <td><?= $s['kapasitas_maks_kg'] ?> kg</td>
                            <td class="fw-semibold"><?= $s['berat_terisi_kg'] ?> kg</td>
                            <td class="text-success fw-semibold"><?= $sisa ?> kg</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-fill" style="height: 8px; max-width: 100px;">
                                        <div class="progress-bar <?= $progressClass ?>" style="width: <?= min($persen, 100) ?>%;"></div>
                                    </div>
                                    <small class="fw-bold"><?= $persen ?>%</small>
                                </div>
                            </td>
                            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>Rp <?= number_format($s['harga_per_kg'], 0, ',', '.') ?></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id_device'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($s['status'] === 'aktif' && $persen > 0): ?>
                                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#kurasModal<?= $s['id_device'] ?>">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-<?= $s['status'] === 'aktif' ? 'secondary' : 'success' ?>" data-bs-toggle="modal" data-bs-target="#statusModal<?= $s['id_device'] ?>">
                                        <i class="bi bi-<?= $s['status'] === 'aktif' ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Edit Harga Modal -->
                        <div class="modal fade" id="editModal<?= $s['id_device'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" class="modal-body p-3">
                                        <input type="hidden" name="id_device" value="<?= $s['id_device'] ?>">
                                        <h6 class="mb-3"><i class="bi bi-pencil"></i> Ubah Harga - <?= $s['id_device'] ?></h6>
                                        <div class="mb-3">
                                            <label class="form-label small">Harga per kg (Rp)</label>
                                            <input type="number" name="harga_per_kg" class="form-control form-control-sm" value="<?= (int)$s['harga_per_kg'] ?>" min="1000" step="500" required>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="submit" name="update_harga" class="btn btn-sm btn-success">Simpan</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kuras Tangki Modal -->
                        <div class="modal fade" id="kurasModal<?= $s['id_device'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" class="modal-body p-3">
                                        <input type="hidden" name="id_device" value="<?= $s['id_device'] ?>">
                                        <h6 class="mb-3"><i class="bi bi-arrow-repeat"></i> Reset Tangki - <?= $s['id_device'] ?></h6>
                                        <p class="small text-muted">Tangki terisi <?= $s['berat_terisi_kg'] ?> kg. Konfirmasi sudah dikuras?</p>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="submit" name="kuras_tangki" class="btn btn-sm btn-warning">Ya, Reset ke 0</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Modal -->
                        <div class="modal fade" id="statusModal<?= $s['id_device'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" class="modal-body p-3">
                                        <input type="hidden" name="id_device" value="<?= $s['id_device'] ?>">
                                        <h6 class="mb-3"><i class="bi bi-toggle"></i> Ubah Status - <?= $s['id_device'] ?></h6>
                                        <div class="mb-3">
                                            <label class="form-label small">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="aktif" <?= $s['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                <option value="maintenance" <?= $s['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                <option value="offline" <?= $s['status'] === 'offline' ? 'selected' : '' ?>>Offline</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Ubah</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transaction History with Filter -->
<div class="card card-custom">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-1"></i> Rekapitulasi Transaksi Setoran Warga</h6>
        <div class="d-flex gap-2">
            <a href="export_csv.php" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </div>
    
    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="filterModalLabel"><i class="bi bi-funnel"></i> Filter Transaksi</h6>
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
                        <div class="col-md-6">
                            <label class="form-label small">Kode Warga</label>
                            <input type="text" name="kode_warga" class="form-control form-control-sm" placeholder="WRG-001" value="<?= htmlspecialchars($_GET['kode_warga'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Mesin</label>
                            <select name="id_device" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <?php foreach ($stationsArray as $s): ?>
                                    <option value="<?= $s['id_device'] ?>" <?= ($_GET['id_device'] ?? '') === $s['id_device'] ? 'selected' : '' ?>><?= $s['id_device'] ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <th>Event ID</th>
                        <th>Waktu</th>
                        <th>Kode</th>
                        <th>Nama Warga</th>
                        <th>Mesin</th>
                        <th>Berat</th>
                        <th>Nominal Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($t = mysqli_fetch_assoc($transaksi)): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($t['event_id']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($t['waktu_setor'])) ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($t['kode_warga']) ?></span></td>
                            <td><?= htmlspecialchars($t['nama']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($t['id_device']) ?></span></td>
                            <td><?= number_format($t['berat_kg'], 2) ?> kg</td>
                            <td class="fw-bold text-success">Rp <?= number_format($t['nominal_saldo'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
