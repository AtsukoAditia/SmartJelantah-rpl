<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
requireRole('warga');

$id_user = $_SESSION['id_user'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$warga = $stmt->get_result()->fetch_assoc();

$iotRes = mysqli_query($conn, "SELECT * FROM stasiun_iot LIMIT 1");
$iot = mysqli_fetch_assoc($iotRes);
$persenTangki = round(($iot['berat_terisi_kg'] / $iot['kapasitas_maks_kg']) * 100, 1);

$stmtHist = $conn->prepare("SELECT * FROM setoran_jelantah WHERE id_user = ? ORDER BY waktu_setor DESC");
$stmtHist->bind_param("i", $id_user);
$stmtHist->execute();
$riwayat = $stmtHist->get_result();

$pageTitle = 'Dashboard Warga - SmartJelantah';
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card card-custom brand-gradient text-white p-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge bg-warning text-dark mb-2">ID: <?= htmlspecialchars($warga['kode_warga']) ?></span>
                    <h5 class="mb-1"><?= htmlspecialchars($warga['nama']) ?></h5>
                    <p class="small opacity-75 mb-3">Saldo Belanja Koperasi Aktif</p>
                    <h2 class="fw-bold mb-0">Rp <?= number_format($warga['saldo_koperasi'], 0, ',', '.') ?></h2>
                </div>
                <div class="bg-white p-2 rounded text-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=<?= urlencode($warga['kode_warga']) ?>" alt="QR Warga">
                    <div class="text-dark fw-bold" style="font-size: 10px;"><?= htmlspecialchars($warga['kode_warga']) ?></div>
                </div>
            </div>
            <hr class="border-light opacity-25">
            <div class="d-flex justify-content-between small">
                <span>Total Jelantah Disetor: <strong><?= number_format($warga['total_kg'], 2) ?> kg</strong></span>
                <span>Tarif: <strong>Rp <?= number_format($iot['harga_per_kg'], 0, ',', '.') ?>/kg</strong></span>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-hdd-rack text-success"></i> Status Stasiun IoT: <?= htmlspecialchars($iot['nama_lokasi']) ?></h6>
            <p class="text-muted small mb-2">Kapasitas Tangki: <?= $iot['berat_terisi_kg'] ?> / <?= $iot['kapasitas_maks_kg'] ?> kg (<?= $persenTangki ?>%)</p>
            <div class="progress mb-3" style="height: 18px;">
                <div class="progress-bar <?= $persenTangki >= 90 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= min($persenTangki, 100) ?>%;">
                    <?= $persenTangki ?>%
                </div>
            </div>
            <?php if ($persenTangki >= 90): ?>
                <div class="alert alert-danger py-2 small mb-0"><i class="bi bi-lock-fill"></i> Tangki Penuh! Mesin terkunci sementara menunggu pengurasan.</div>
            <?php else: ?>
                <div class="alert alert-success py-2 small mb-2"><i class="bi bi-unlock-fill"></i> Mesin Siap Menerima Setoran Jelantah.</div>
                <a href="<?= BASE_URL ?>/simulator_iot.php" class="btn btn-outline-success btn-sm w-100">Buka Simulator Setor Minyak</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12" id="riwayat">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-success"></i> Riwayat Setoran Jelantah</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle small">
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
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat setoran.</td></tr>
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
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
