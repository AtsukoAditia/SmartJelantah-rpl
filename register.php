<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';

$error = '';
$success = '';
$formData = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $formData = ['nama' => $nama, 'email' => $email];

    // Validation
    $errors = [];
    
    if ($nama === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi';
    } elseif (strlen($nama) < 2) {
        $errors['nama'] = 'Nama minimal 2 karakter';
    } elseif (strlen($nama) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter';
    }
    
    if ($email === '') {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    }
    
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter';
    } elseif (strlen($password) > 50) {
        $errors['password'] = 'Password maksimal 50 karakter';
    }
    
    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Konfirmasi password wajib diisi';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
    }
    
    // Check email uniqueness
    if (empty($errors['email'])) {
        $cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $errors['email'] = 'Email sudah terdaftar!';
        }
    }
    
    if (empty($errors)) {
        $kode_warga = generateKodeWarga($conn);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'warga';

        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, kode_warga) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $email, $hash, $role, $kode_warga);
        if ($stmt->execute()) {
            $success = 'Akun berhasil dibuat! Kode warga Anda: ' . $kode_warga;
            $formData = ['nama' => '', 'email' => ''];
        } else {
            $error = 'Gagal mendaftar akun. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Daftar Warga - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-custom p-4">
            <div class="text-center mb-4">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-person-plus fs-1"></i>
                </div>
                <h4 class="fw-bold mb-1">Daftar Warga Baru</h4>
                <p class="text-muted small mb-0">Bergabung dengan SmartJelantah & mulai menabung jelantah</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="nama" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" name="nama" id="nama" class="form-control border-start-0 <?= isset($errors['nama']) ? 'is-invalid' : '' ?>" 
                               placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($formData['nama']) ?>" 
                               required autocomplete="name" maxlength="100">
                    </div>
                    <?php if (isset($errors['nama'])): ?>
                        <div class="invalid-feedback d-block small"><?= htmlspecialchars($errors['nama']) ?></div>
                    <?php else: ?>
                        <div class="form-text small">Nama akan ditampilkan di dashboard dan QR code</div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               placeholder="email@contoh.com" value="<?= htmlspecialchars($formData['email']) ?>" 
                               required autocomplete="email" maxlength="100">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block small"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php else: ?>
                        <div class="form-text small">Digunakan untuk login & notifikasi</div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control border-start-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               placeholder="Minimal 6 karakter" required autocomplete="new-password" maxlength="50">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block small"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php else: ?>
                        <div class="form-text small">Minimal 6 karakter, kombinasikan huruf & angka</div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0 <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                               placeholder="Ulangi password" required autocomplete="new-password" maxlength="50">
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback d-block small"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
                    <i class="bi bi-person-plus me-1"></i> Daftar Sekarang
                </button>
            </form>
            
            <div class="text-center mt-3 small">
                Sudah punya akun? <a href="login.php" class="text-success fw-semibold text-decoration-none">Masuk di sini</a>
            </div>
            
            <hr class="my-3">
            
            <div class="bg-light p-3 rounded small">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1"></i> Apa yang Anda Dapatkan?</h6>
                <ul class="mb-0 ps-3 text-muted small">
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> QR Code identitas warga unik (WRG-XXX)</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Saldo koperasi otomatis dari setoran jelantah</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Riwayat setoran lengkap & terpercaya</li>
                    <li class="mb-0"><i class="bi bi-check text-success me-1"></i> Akses simulator IoT untuk demo</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
