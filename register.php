<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || strlen($password) < 6) {
        $error = 'Lengkapi data dengan benar (password minimal 6 karakter).';
    } else {
        $cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $kode_warga = generateKodeWarga($conn);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'warga';

            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, kode_warga) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $email, $hash, $role, $kode_warga);
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
            $error = 'Gagal mendaftar akun.';
        }
    }
}

$pageTitle = 'Daftar Warga - SmartJelantah';
require_once __DIR__ . '/layouts/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card card-custom p-4">
            <h4 class="fw-bold text-center mb-3">Registrasi Warga</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-semibold">Daftar Akun</button>
            </form>
            <div class="text-center mt-3 small">
                Sudah punya akun? <a href="login.php" class="text-success fw-semibold">Login</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
