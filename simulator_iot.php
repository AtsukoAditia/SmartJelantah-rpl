<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';

// Simulator can be accessed without login for demo, but if logged in use their kode_warga
$defaultKode = $_SESSION['kode_warga'] ?? 'WRG-001';
$pageTitle = 'Simulator Stasiun IoT - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';

// Get current tank status for display
$iotRes = mysqli_query($conn, "SELECT * FROM stasiun_iot WHERE id_device = 'JHUB-01'");
$iot = mysqli_fetch_assoc($iotRes);
$persenTangki = round(($iot['berat_terisi_kg'] / $iot['kapasitas_maks_kg']) * 100, 1);
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <!-- Machine Status Card -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-cpu"></i> Stasiun IoT: <?= htmlspecialchars($iot['nama_lokasi']) ?> (<?= htmlspecialchars($iot['id_device']) ?>)</h6>
                <span class="badge bg-success"><i class="bi bi-wifi"></i> ONLINE</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Kapasitas Maks</small>
                            <strong class="fs-5"><?= $iot['kapasitas_maks_kg'] ?> kg</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Terisi Saat Ini</small>
                            <strong class="fs-5 text-primary"><?= $iot['berat_terisi_kg'] ?> kg</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Sisa Kapasitas</small>
                            <strong class="fs-5 text-success"><?= max(0, $iot['kapasitas_maks_kg'] - $iot['berat_terisi_kg']) ?> kg</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Harga/Kg</small>
                            <strong class="fs-5">Rp <?= number_format($iot['harga_per_kg'], 0, ',', '.') ?></strong>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Kapasitas Tangki</span>
                        <span class="fw-bold"><?= $persenTangki ?>%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar <?= $persenTangki >= 90 ? 'bg-danger' : ($persenTangki >= 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= min($persenTangki, 100) ?>%;"></div>
                    </div>
                    <?php if ($persenTangki >= 90): ?>
                        <div class="alert alert-danger py-1 small mt-2 mb-0"><i class="bi bi-lock-fill"></i> Tangki Penuh (>=90%) - Inlet terkunci otomatis!</div>
                    <?php elseif ($persenTangki >= 70): ?>
                        <div class="alert alert-warning py-1 small mt-2 mb-0"><i class="bi bi-exclamation-triangle"></i> Kapasitas mendekati batas (>=70%)</div>
                    <?php else: ?>
                        <div class="alert alert-success py-1 small mt-2 mb-0"><i class="bi bi-check-circle"></i> Mesin siap menerima setoran</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Simulator Card -->
        <div class="card card-custom bg-dark text-white p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-warning text-dark"><i class="bi bi-tv"></i> LCD 16x2 Virtual Smart Dropbox</span>
                <span class="small text-secondary">Simulator Mode</span>
            </div>

            <div class="bg-black border border-success rounded p-3 mb-3 font-monospace text-success" id="lcdScreen" style="min-height: 60px;">
                > LCD 16x2 READY<br>
                > SILAKAN SCAN KODE WARGA & TUANG MINYAK
            </div>

            <div id="alertBox"></div>

            <form id="simForm">
                <div class="mb-3">
                    <label class="form-label small">1. Scan / Masukkan Kode Anggota Warga</label>
                    <input type="text" id="kode_warga" class="form-control" value="<?= htmlspecialchars($defaultKode) ?>" placeholder="WRG-001" required>
                    <div class="form-text small">Format: WRG-XXX (contoh: WRG-001)</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">2. Simulasi Sensor Load Cell HX711 (Berat dalam Kg)</label>
                    <div class="input-group">
                        <input type="number" id="berat_kg" class="form-control" value="1.5" step="0.1" min="0.1" max="30" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="form-text small">Range: 0.1 - 30 kg (maks kapasitas tangki: <?= $iot['kapasitas_maks_kg'] ?> kg)</div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" id="btnSubmit" class="btn btn-warning fw-bold">
                        <i class="bi bi-droplet-half"></i> Konfirmasi Selesai Tuang & Kirim Telemetry
                    </button>
                    <?php if (isset($_SESSION['id_user'])): ?>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-light btn-sm">Kembali ke Dashboard</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-sm">Login Warga</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Quick Test Buttons -->
            <div class="mt-4 p-3 bg-secondary-subtle rounded">
                <h6 class="small text-white mb-2"><i class="bi bi-lightning"></i> Quick Test Scenarios</h6>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success quick-test" data-kode="WRG-001" data-berat="1.0" data-desc="Setoran normal 1kg">Normal 1kg</button>
                    <button type="button" class="btn btn-sm btn-outline-info quick-test" data-kode="WRG-001" data-berat="5.0" data-desc="Setoran besar 5kg">Besar 5kg</button>
                    <button type="button" class="btn btn-sm btn-outline-warning quick-test" data-kode="WRG-999" data-berat="1.0" data-desc="Kode warga tidak valid">Kode Invalid</button>
                    <button type="button" class="btn btn-sm btn-outline-danger quick-test" data-kode="WRG-001" data-berat="30" data-desc="Melebihi kapasitas tangki">Over Capacity</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('simForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitSetoran();
});

// Quick test buttons
document.querySelectorAll('.quick-test').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('kode_warga').value = this.dataset.kode;
        document.getElementById('berat_kg').value = this.dataset.berat;
        submitSetoran();
    });
});

async function submitSetoran() {
    const lcd = document.getElementById('lcdScreen');
    const alertBox = document.getElementById('alertBox');
    const btnSubmit = document.getElementById('btnSubmit');
    const kode_warga = document.getElementById('kode_warga').value;
    const berat_kg = document.getElementById('berat_kg').value;
    const event_id = 'EVT-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);

    // Disable button during submission
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';

    // LCD Animation Sequence
    lcd.innerHTML = '> SOLENOID TERBUKA...<br>> AUTO-TARE & MENIMBANG ' + berat_kg + ' KG...';
    await sleep(800);
    lcd.innerHTML = '> MENGUKUR MASSA...<br>> STABILISASI SENSOR...';
    await sleep(600);
    lcd.innerHTML = '> MASSA STABIL: ' + berat_kg + ' KG<br>> MENGIRIM TELEMETRI...';
    await sleep(500);

    try {
        const res = await fetch('<?= BASE_URL ?>/api/setor_iot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ kode_warga, id_device: 'JHUB-01', berat_kg, event_id })
        });
        const data = await res.json();

        if (data.status) {
            lcd.innerHTML = '> SETORAN BERHASIL!<br>> SALDO BARU: RP ' + data.data.saldo_baru.toLocaleString('id-ID');
            alertBox.innerHTML = `<div class="alert alert-success py-2 small">${data.message}</div>`;
            
            // Update tank status display
            setTimeout(() => location.reload(), 2000);
        } else {
            lcd.innerHTML = '> TRANSAKSI DITOLAK!<br>> CEK PESAN ERROR DI BAWAH';
            alertBox.innerHTML = `<div class="alert alert-danger py-2 small">${data.message}</div>`;
        }
    } catch (err) {
        lcd.innerHTML = '> ERROR KONEKSI!<br>> CEK JARINGAN/SERVER';
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small">Gagal terhubung ke server: ${err.message}</div>`;
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-droplet-half"></i> Konfirmasi Selesai Tuang & Kirim Telemetry';
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>