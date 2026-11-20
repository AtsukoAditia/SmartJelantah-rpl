<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('admin');

$notif = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_harga'])) {
        $hargaBaru = (float)$_POST['harga_per_kg'];
        if ($hargaBaru > 0) {
            $stmt = $conn->prepare("UPDATE stasiun_iot SET harga_per_kg = ? WHERE id_device = 'JHUB-01'");
            $stmt->bind_param("d", $hargaBaru);
            $stmt->execute();
            $notif = 'Harga beli jelantah berhasil diperbarui!';
        }
    } elseif (isset($_POST['kuras_tangki'])) {
        mysqli_query($conn, "UPDATE stasiun_iot SET berat_terisi_kg = 0 WHERE id_device = 'JHUB-01'");
        $notif = 'Pengurasan tangki dicatat. Kapasitas stasiun IoT di-reset ke 0 kg!';
    }
}

$iot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM stasiun_iot WHERE id_device = 'JHUB-01'"));
$persenTangki = round(($iot['berat_terisi_kg'] / $iot['kapasitas_maks_kg']) * 100, 1);

$stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(berat_kg),0) AS total_berat, COALESCE(SUM(nominal_saldo),0) AS total_saldo FROM setoran_jelantah"));

// Filter logic
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
            <span class="text-muted small">Harga Beli Aktif</span>
            <h3 class="fw-bold text-dark mb-0">Rp <?= number_format($iot['harga_per_kg'], 0, ',', '.') ?> / kg</h3>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold mb-2">Monitoring Tangki IoT (<?= htmlspecialchars($iot['id_device']) ?>)</h6>
            <p class="small text-muted mb-2">Terisi: <strong><?= $iot['berat_terisi_kg'] ?> kg</strong> dari <?= $iot['kapasitas_maks_kg'] ?> kg (<?= $persenTangki ?>%)</p>
            <div class="progress mb-3" style="height: 20px;">
                <div class="progress-bar <?= $persenTangki >= 90 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= min($persenTangki, 100) ?>%;"><?= $persenTangki ?>%</div>
            </div>
            <form method="POST" onsubmit="return confirm('Konfirmasi tangki sudah dikuras oleh mitra pengepul?');">
                <button type="submit" name="kuras_tangki" class="btn btn-warning btn-sm fw-semibold">
                    <i class="bi bi-arrow-repeat"></i> Catat Kuras Tangki (Reset 0%)
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold mb-2">Pengaturan Tarif Beli Jelantah</h6>
            <p class="small text-muted mb-3">Perubahan harga berlaku untuk transaksi setoran berikutnya.</p>
            <form method="POST" class="d-flex gap-2">
                <input type="number" name="harga_per_kg" class="form-control" value="<?= (int)$iot['harga_per_kg'] ?>" min="1000" step="500" required>
                <button type="submit" name="update_harga" class="btn btn-success fw-semibold">Simpan</button>
            </form>
        </div>
    </div>
</div>

<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Rekapitulasi Transaksi Setoran Warga</h6>
        <div class="d-flex gap-2">
            <a href="export_csv.php" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </div>
    
    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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
                                <option value="JHUB-01" <?= ($_GET['id_device'] ?? '') === 'JHUB-01' ? 'selected' : '' ?>>JHUB-01</option>
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
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Event ID</th>
                    <th>Waktu</th>
                    <th>Kode</th>
                    <th>Nama Warga</th>
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
                        <td><?= number_format($t['berat_kg'], 2) ?> kg</td>
                        <td class="fw-bold text-success">Rp <?= number_format($t['nominal_saldo'], 0, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>