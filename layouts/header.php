<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'SmartJelantah - Platform IoT & Koperasi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; padding-bottom: 70px; }
        .brand-gradient { background: linear-gradient(135deg, #198754 0%, #0f5132 100%); }
        .card-custom { border: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        @media (min-width: 768px) { body { padding-bottom: 20px; } }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark brand-gradient shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-droplet-fill text-warning me-1"></i> SmartJelantah
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= BASE_URL ?>/simulator_iot.php" class="btn btn-sm btn-warning fw-semibold">
                <i class="bi bi-cpu"></i> <span class="d-none d-sm-inline">Simulator IoT</span>
            </a>
            <?php if (isset($_SESSION['id_user'])): ?>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container">
