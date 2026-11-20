<?php
require_once __DIR__ . '/config/database.php';
$defaultKode = $_SESSION['kode_warga'] ?? 'WRG-001';
$pageTitle = 'Simulator Stasiun IoT - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-custom bg-dark text-white p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-success"><i class="bi bi-wifi"></i> ESP32 ONLINE (JHUB-01)</span>
                <span class="small text-secondary">Virtual Smart Dropbox</span>
            </div>

            <div class="bg-black border border-success rounded p-3 mb-3 font-monospace text-success" id="lcdScreen">
                > LCD 16x2 READY<br>
                > SILAKAN SCAN KODE WARGA & TUANG MINYAK
            </div>

            <div id="alertBox"></div>

            <form id="simForm">
                <div class="mb-3">
                    <label class="form-label small">1. Scan / Masukkan Kode Anggota Warga</label>
                    <input type="text" id="kode_warga" class="form-control" value="<?= htmlspecialchars($defaultKode) ?>" placeholder="WRG-001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">2. Simulasi Sensor Load Cell HX711 (Berat dalam Kg)</label>
                    <input type="number" id="berat_kg" class="form-control" value="1.5" step="0.1" min="0.1" max="30" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">
                    <i class="bi bi-droplet-half"></i> Konfirmasi Selesai Tuang & Kirim Telemetry
                </button>
            </form>

            <?php if (isset($_SESSION['id_user'])): ?>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-light btn-sm w-100 mt-3">Kembali ke Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('simForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const lcd = document.getElementById('lcdScreen');
    const alertBox = document.getElementById('alertBox');
    const kode_warga = document.getElementById('kode_warga').value;
    const berat_kg = document.getElementById('berat_kg').value;
    const event_id = 'EVT-' + Date.now();

    lcd.innerHTML = '> SOLENOID TERBUKA...<br>> AUTO-TARE & MENIMBANG ' + berat_kg + ' KG...';

    const res = await fetch('<?= BASE_URL ?>/api/setor_iot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ kode_warga, id_device: 'JHUB-01', berat_kg, event_id })
    });
    const data = await res.json();

    if (data.status) {
        lcd.innerHTML = '> SETORAN BERHASIL!<br>> SALDO BARU: RP ' + data.data.saldo_baru.toLocaleString('id-ID');
        alertBox.innerHTML = `<div class="alert alert-success py-2 small">${data.message}</div>`;
    } else {
        lcd.innerHTML = '> TRANSAKSI DITOLAK!<br>> CEK PESAN ERROR DI BAWAH';
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small">${data.message}</div>`;
    }
});
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
