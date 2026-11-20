<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';
requireRole('warga');

$pageTitle = 'Scan QR Stasiun - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <!-- Scanner Card -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-qr-code-scan"></i> Scan QR Code Stasiun</h6>
                <span class="badge bg-success" id="scannerStatus"><i class="bi bi-camera-video"></i> Kamera Siap</span>
            </div>
            <div class="card-body p-0">
                <div id="scannerContainer" style="position: relative; background: #000; border-radius: 0 0 16px 16px; overflow: hidden;">
                    <video id="preview" playsinline style="width: 100%; min-height: 300px; display: none;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="scannerOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark bg-opacity-50" style="display: none;">
                        <div class="text-white text-center p-4">
                            <div class="scanner-frame mb-3" style="width: 200px; height: 200px; border: 3px solid #198754; border-radius: 12px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.7); position: relative;">
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="border-radius: 9px; border: 2px solid transparent; background: linear-gradient(45deg, #198754, transparent) border-box; -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0) border-box; -webkit-mask-composite: xor; mask-composite: exclude; animation: scanLine 2s linear infinite;"></div>
                            </div>
                            <p class="small mb-0">Arahkan QR code ke dalam frame</p>
                            <p class="text-muted small">QR berisi ID stasiun (contoh: JHUB-01)</p>
                        </div>
                    </div>
                    <div id="permissionPrompt" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark text-white p-4" style="display: none;">
                        <i class="bi bi-camera-video-off fs-1 mb-3 text-warning"></i>
                        <h5>Akses Kamera Diperlukan</h5>
                        <p class="text-center small mb-3">Izinkan akses kamera untuk memindai QR code stasiun</p>
                        <button class="btn btn-warning" onclick="requestCamera()">
                            <i class="bi bi-camera me-1"></i> Buka Kamera
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="toggleTorch()">
                        <i class="bi bi-flash" id="torchIcon"></i> <span id="torchText">Nyalakan Flash</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="switchCamera()">
                        <i class="bi bi-camera-rotate"></i> Ganti Kamera
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm flex-fill" onclick="stopScanner()">
                        <i class="bi bi-x-circle"></i> Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Card (initially hidden) -->
        <div class="card card-custom d-none" id="resultCard">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-check-circle"></i> Stasiun Ditemukan</h6>
                <button type="button" class="btn btn-sm btn-light" onclick="resetScanner()">
                    <i class="bi bi-arrow-counterclockwise"></i> Scan Lagi
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">ID Mesin</small>
                            <strong id="resId" class="fs-5 text-primary"></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Status</small>
                            <span id="resStatus" class="badge fs-6"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Kapasitas Maks</small>
                            <strong id="resMax" class="fs-5">30 kg</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Terisi</small>
                            <strong id="resFilled" class="fs-5 text-primary"></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Sisa Kapasitas</small>
                            <strong id="resRemaining" class="fs-5 text-success"></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-custom bg-light p-3 h-100 text-center">
                            <small class="text-muted d-block">Persentase</small>
                            <strong id="resPercent" class="fs-5"></strong>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Kapasitas Tangki</span>
                        <span class="fw-bold" id="resPercentLabel">0%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar" id="resProgress" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="mt-3 p-3 rounded bg-light">
                    <h6 class="small mb-2"><i class="bi bi-geo-alt"></i> Lokasi</h6>
                    <p class="small mb-1" id="resLocation"></p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openMaps()">
                        <i class="bi bi-map me-1"></i> Buka di Maps
                    </button>
                </div>
                
                <div class="mt-3 d-grid gap-2">
                    <a href="/simulator_iot.php" class="btn btn-success fw-semibold">
                        <i class="bi bi-droplet-half me-1"></i> Langsung Setor Minyak
                    </a>
                    <a href="/user/index.php" class="btn btn-outline-primary">
                        <i class="bi bi-house-door me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Error Card -->
        <div class="card card-custom border-danger d-none" id="errorCard">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Gagal Memuat Data</h6>
            </div>
            <div class="card-body text-center">
                <p class="small mb-3" id="errorMessage"></p>
                <button type="button" class="btn btn-outline-danger" onclick="resetScanner()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Coba Lagi
                </button>
            </div>
        </div>

        <!-- Manual Input Fallback -->
        <div class="card card-custom mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-keyboard"></i> Atau Masukkan ID Manual</h6>
            </div>
            <div class="card-body">
                <form id="manualForm" class="d-flex gap-2">
                    <input type="text" name="id_device" class="form-control form-control-sm" placeholder="JHUB-01" pattern="JHUB-\d{2}" title="Format: JHUB-XX" required maxlength="7">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Cek Stasiun</button>
                </form>
                <p class="form-text small mb-0">Format: JHUB-01 sampai JHUB-05</p>
            </div>
        </div>
    </div>
</div>

<style>
.scanner-frame::before {
    content: '';
    position: absolute;
    top: -3px; left: -3px; right: -3px; bottom: -3px;
    border: 3px solid #198754;
    border-radius: 12px;
    opacity: 0.3;
}
@keyframes scanLine {
    0% { transform: translateY(-100%); }
    100% { transform: translateY(100%); }
}
#preview { transform: rotateY(0deg); }
#preview.mirror { transform: rotateY(180deg); }
</style>

<script>
let video = document.getElementById('preview');
let canvas = document.getElementById('canvas');
let ctx = canvas.getContext('2d', { willReadFrequently: true });
let stream = null;
let facingMode = 'environment'; // back camera first
let torchEnabled = false;
let scanning = false;
let lastScanTime = 0;
const SCAN_COOLDOWN = 3000; // 3 seconds between scans

const scannerContainer = document.getElementById('scannerContainer');
const scannerOverlay = document.getElementById('scannerOverlay');
const permissionPrompt = document.getElementById('permissionPrompt');
const scannerStatus = document.getElementById('scannerStatus');
const resultCard = document.getElementById('resultCard');
const errorCard = document.getElementById('errorCard');
const torchIcon = document.getElementById('torchIcon');
const torchText = document.getElementById('torchText');

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    checkCameraPermission();
    setupManualForm();
});

async function checkCameraPermission() {
    try {
        const permission = await navigator.permissions.query({ name: 'camera' });
        if (permission.state === 'granted') {
            startScanner();
        } else if (permission.state === 'prompt') {
            showPermissionPrompt();
        } else {
            showPermissionPrompt();
        }
    } catch (e) {
        // Permissions API not supported, try directly
        startScanner();
    }
}

function showPermissionPrompt() {
    scannerOverlay.style.display = 'none';
    permissionPrompt.style.display = 'flex';
    video.style.display = 'none';
    scannerStatus.innerHTML = '<i class="bi bi-camera-video-off"></i> Kamera Tertutup';
    scannerStatus.className = 'badge bg-warning';
}

async function requestCamera() {
    await startScanner();
}

async function startScanner() {
    if (stream) return;
    
    permissionPrompt.style.display = 'none';
    scannerOverlay.style.display = 'flex';
    video.style.display = 'block';
    
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: facingMode,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });
        
        video.srcObject = stream;
        await video.play();
        
        scannerStatus.innerHTML = '<i class="bi bi-camera-video"></i> Memindai...';
        scannerStatus.className = 'badge bg-success';
        
        scanning = true;
        requestAnimationFrame(scanFrame);
        
    } catch (err) {
        console.error('Camera error:', err);
        scannerOverlay.style.display = 'none';
        permissionPrompt.style.display = 'flex';
        permissionPrompt.innerHTML = `
            <i class="bi bi-camera-video-off fs-1 mb-3 text-danger"></i>
            <h5>Tidak Dapat Mengakses Kamera</h5>
            <p class="text-center small mb-3">${err.message}</p>
            <p class="text-center small mb-3">Gunakan input manual di bawah</p>
        `;
        scannerStatus.innerHTML = '<i class="bi bi-camera-video-off"></i> Error';
        scannerStatus.className = 'badge bg-danger';
    }
}

function stopScanner() {
    scanning = false;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    video.style.display = 'none';
    scannerOverlay.style.display = 'none';
    scannerStatus.innerHTML = '<i class="bi bi-camera-video-off"></i> Kamera Dimatikan';
    scannerStatus.className = 'badge bg-secondary';
}

function switchCamera() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    if (stream) {
        stopScanner();
        setTimeout(startScanner, 100);
    }
}

async function toggleTorch() {
    if (!stream) return;
    
    const track = stream.getVideoTracks()[0];
    const capabilities = track.getCapabilities();
    
    if (!capabilities.torch) {
        showToast('Flash tidak tersedia di kamera ini', 'error');
        return;
    }
    
    try {
        torchEnabled = !torchEnabled;
        await track.applyConstraints({ advanced: [{ torch: torchEnabled }] });
        torchIcon.className = torchEnabled ? 'bi bi-flashlight' : 'bi bi-flash';
        torchText.textContent = torchEnabled ? 'Matikan Flash' : 'Nyalakan Flash';
    } catch (err) {
        console.error('Torch error:', err);
        showToast('Gagal mengubah flash', 'error');
    }
}

function scanFrame() {
    if (!scanning || video.paused || video.ended) {
        if (scanning) requestAnimationFrame(scanFrame);
        return;
    }
    
    // Only scan every 100ms to save CPU
    const now = Date.now();
    if (now - lastScanTime < 100) {
        requestAnimationFrame(scanFrame);
        return;
    }
    lastScanTime = now;
    
    // Draw video frame to canvas
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    
    // Try to decode QR code
    try {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });
        
        if (code && code.data) {
            const now = Date.now();
            if (now - lastScanTime > SCAN_COOLDOWN) {
                lastScanTime = now;
                handleQRCode(code.data.trim());
            }
        }
    } catch (e) {
        // jsQR not loaded or error
    }
    
    requestAnimationFrame(scanFrame);
}

function handleQRCode(data) {
    // Validate format - should be just the device ID or URL containing it
    let deviceId = data;
    
    // Extract device ID from various formats
    const patterns = [
        /^JHUB-\d{2}$/,
        /id_device[=:](JHUB-\d{2})/i,
        /device[=:](JHUB-\d{2})/i,
        /station[=:](JHUB-\d{2})/i,
        /(JHUB-\d{2})/
    ];
    
    let matched = false;
    for (const pattern of patterns) {
        const match = data.match(pattern);
        if (match) {
            deviceId = match[1] || match[0];
            matched = true;
            break;
        }
    }
    
    if (!matched || !/^JHUB-\d{2}$/.test(deviceId)) {
        showToast('QR code tidak valid. Format: JHUB-XX', 'error');
        return;
    }
    
    // Vibrate feedback
    if (navigator.vibrate) navigator.vibrate(100);
    
    // Show loading
    scannerStatus.innerHTML = '<i class="bi bi-hourglass-split"></i> Memuat data...';
    scannerStatus.className = 'badge bg-info';
    
    // Fetch station info
    fetch(`/api/station_info.php?id_device=${encodeURIComponent(deviceId)}`)
        .then(res => res.json())
        .then(result => {
            if (result.status) {
                showStationResult(result.data);
            } else {
                showError(result.message || 'Stasiun tidak ditemukan');
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            showError('Gagal menghubungi server');
        });
}

function showStationResult(data) {
    // Hide scanner, show result
    stopScanner();
    scannerOverlay.style.display = 'none';
    errorCard.classList.add('d-none');
    resultCard.classList.remove('d-none');
    
    // Fill data
    document.getElementById('resId').textContent = data.id_device;
    document.getElementById('resMax').textContent = data.kapasitas_maks_kg + ' kg';
    document.getElementById('resFilled').textContent = data.berat_terisi_kg + ' kg';
    document.getElementById('resRemaining').textContent = data.sisa_kapasitas_kg + ' kg';
    document.getElementById('resPercent').textContent = data.persen_terisi + '%';
    document.getElementById('resPercentLabel').textContent = data.persen_terisi + '%';
    document.getElementById('resProgress').style.width = Math.min(data.persen_terisi, 100) + '%';
    document.getElementById('resLocation').textContent = data.alamat_lokasi || '-';
    
    // Status badge
    const statusEl = document.getElementById('resStatus');
    const statusClass = data.can_receive ? 'bg-success' : (data.status === 'aktif' ? 'bg-warning' : 'bg-secondary');
    statusEl.className = 'badge ' + statusClass;
    statusEl.textContent = data.status_label;
    
    // Progress bar color
    const progressBar = document.getElementById('resProgress');
    progressBar.className = 'progress-bar';
    if (data.persen_terisi >= 90) progressBar.classList.add('bg-danger');
    else if (data.persen_terisi >= 70) progressBar.classList.add('bg-warning');
    else progressBar.classList.add('bg-success');
    
    // Store for maps
    resultCard.dataset.lat = data.latitude;
    resultCard.dataset.lng = data.longitude;
    resultCard.dataset.name = data.nama_lokasi;
    
    showToast(`Stasiun ${data.nama_lokasi} ditemukan`, 'success');
}

function showError(message) {
    scannerOverlay.style.display = 'none';
    resultCard.classList.add('d-none');
    errorCard.classList.remove('d-none');
    document.getElementById('errorMessage').textContent = message;
    showToast(message, 'error');
}

function resetScanner() {
    resultCard.classList.add('d-none');
    errorCard.classList.add('d-none');
    scannerOverlay.style.display = 'flex';
    startScanner();
}

function openMaps() {
    const lat = resultCard.dataset.lat;
    const lng = resultCard.dataset.lng;
    const name = encodeURIComponent(resultCard.dataset.name);
    
    if (lat && lng) {
        // Try Google Maps first, fallback to OpenStreetMap
        const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}&query_place_id=${name}`;
        window.open(url, '_blank');
    } else {
        showToast('Koordinat tidak tersedia', 'error');
    }
}

// Manual form handler
function setupManualForm() {
    document.getElementById('manualForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const deviceId = formData.get('id_device').trim().toUpperCase();
        
        if (!/^JHUB-\d{2}$/.test(deviceId)) {
            showToast('Format ID: JHUB-XX (contoh: JHUB-01)', 'error');
            return;
        }
        
        const btn = e.target.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mencari...';
        
        try {
            const res = await fetch(`/api/station_info.php?id_device=${encodeURIComponent(deviceId)}`);
            const result = await res.json();
            
            if (result.status) {
                showStationResult(result.data);
            } else {
                showError(result.message || 'Stasiun tidak ditemukan');
            }
        } catch (err) {
            showError('Gagal menghubungi server');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Cek Stasiun';
        }
    });
}

// Toast function (from footer)
function showToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        // Fallback
        alert(message);
    }
}
</script>

<!-- Include jsQR library for QR code scanning -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>