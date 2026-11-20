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
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    $error = 'Email atau kata sandi salah!';
}

$pageTitle = 'Login - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card card-custom p-4">
            <div class="text-center mb-3">
                <i class="bi bi-recycle text-success fs-1"></i>
                <h4 class="fw-bold mt-2">Masuk SmartJelantah</h4>
                <p class="text-muted small">Ubah minyak jelantah jadi saldo belanja koperasi</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="warga@smartjelantah.id" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-semibold">Masuk</button>
            </form>
            <div class="text-center mt-3 small">
                Belum punya akun warga? <a href="register.php" class="text-success fw-semibold">Daftar di sini</a>
            </div>
            <hr>
            <div class="bg-light p-2 rounded small text-muted">
                <strong>Akun Demo:</strong><br>
                Warga: <code>warga@smartjelantah.id</code> / <code>warga123</code><br>
                Admin: <code>admin@smartjelantah.id</code> / <code>admin123</code>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
