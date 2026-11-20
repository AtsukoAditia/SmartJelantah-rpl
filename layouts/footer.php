</div>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga'): ?>
<nav class="navbar fixed-bottom navbar-light bg-white border-top d-md-none shadow-sm">
    <div class="container-fluid justify-content-around">
        <a href="<?= BASE_URL ?>/user/index.php" class="text-decoration-none text-success text-center small">
            <i class="bi bi-house-door-fill fs-5 d-block"></i>Beranda
        </a>
        <a href="<?= BASE_URL ?>/simulator_iot.php" class="text-decoration-none text-dark text-center small">
            <i class="bi bi-qr-code-scan fs-5 d-block"></i>Setor IoT
        </a>
        <a href="<?= BASE_URL ?>/user/index.php#riwayat" class="text-decoration-none text-dark text-center small">
            <i class="bi bi-clock-history fs-5 d-block"></i>Riwayat
        </a>
    </div>
</nav>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
