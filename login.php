<?php
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['id_user'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['kode_warga'] = $user['kode_warga'];
            
            // Remember me
            if (isset($_POST['remember'])) {
                setcookie('remember_email', $email, time() + (86400 * 30), '/', '', true, true); // 30 days, secure, httponly
            } else {
                setcookie('remember_email', '', time() - 3600, '/', '', true, true);
            }
            
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    $error = 'Email atau kata sandi salah!';
}

$rememberedEmail = $_COOKIE['remember_email'] ?? '';

$pageTitle = 'Login - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card card-custom p-4">
            <div class="text-center mb-4">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-recycle fs-1"></i>
                </div>
                <h4 class="fw-bold mb-1">Masuk SmartJelantah</h4>
                <p class="text-muted small mb-0">Ubah minyak jelantah jadi saldo belanja koperasi</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="warga@smartjelantah.id" value="<?= htmlspecialchars($rememberedEmail) ?>" required autocomplete="email">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input" <?= $rememberedEmail ? 'checked' : '' ?>>
                        <label class="form-check-label small text-muted" for="remember">Ingat saya</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>
            
            <div class="text-center mt-3 small">
                Belum punya akun warga? <a href="register.php" class="text-success fw-semibold text-decoration-none">Daftar di sini</a>
            </div>
            
            <hr class="my-3">
            
            <div class="bg-light p-3 rounded small">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1"></i> Akun Demo</h6>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="bg-white p-2 rounded border">
                            <div class="fw-semibold small">Warga</div>
                            <code class="d-block text-success small">warga@smartjelantah.id</code>
                            <code class="d-block small">warga123</code>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white p-2 rounded border">
                            <div class="fw-semibold small">Admin</div>
                            <code class="d-block text-primary small">admin@smartjelantah.id</code>
                            <code class="d-block small">admin123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
