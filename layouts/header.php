<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartJelantah - Platform Otomatisasi Penampungan Jelantah & Integrasi Saldo Koperasi Berbasis IoT">
    <meta name="theme-color" content="#198754">
    <title><?= $pageTitle ?? 'SmartJelantah - Platform IoT & Koperasi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #198754;
            --brand-primary-dark: #0f5132;
            --brand-gradient: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
        }
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        body { 
            background-color: #f4f7f6; 
            padding-bottom: 70px; 
            font-size: 14px;
            line-height: 1.5;
        }
        .brand-gradient { background: var(--brand-gradient); }
        .card-custom { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .card-custom:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.06); }
        .btn { border-radius: 10px; font-weight: 500; transition: all 0.15s ease; }
        .btn:active { transform: scale(0.98); }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #dee2e6; padding: 0.625rem 0.875rem; }
        .form-control:focus, .form-select:focus { 
            border-color: var(--brand-primary); 
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15); 
        }
        .input-group-text { background-color: #f8f9fa; border: 1px solid #dee2e6; }
        .input-group .form-control:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .input-group .form-control:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        .input-group-text:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .input-group-text:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        .progress { border-radius: 10px; background-color: #e9ecef; }
        .progress-bar { border-radius: 10px; transition: width 0.6s ease; }
        .badge { font-weight: 500; padding: 0.35em 0.65em; }
        .table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; }
        .table td { vertical-align: middle; }
        .table-hover tbody tr:hover { background-color: rgba(25, 135, 84, 0.03); }
        .alert { border-radius: 10px; border: none; }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .modal-header, .modal-footer { border-color: #e9ecef; }
        @media (min-width: 768px) { body { padding-bottom: 20px; } }
        
        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        
        /* Focus visible for accessibility */
        :focus:not(:focus-visible) { outline: none; }
        :focus-visible { outline: 2px solid var(--brand-primary); outline-offset: 2px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark brand-gradient shadow-sm mb-4" role="navigation" aria-label="Navigasi utama">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= BASE_URL ?>/index.php" aria-label="SmartJelantah - Beranda">
            <i class="bi bi-droplet-fill text-warning me-1" aria-hidden="true" style="font-size: 1.5rem;"></i> 
            <span>SmartJelantah</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="<?= BASE_URL ?>/simulator_iot.php" class="btn btn-sm btn-warning fw-semibold d-flex align-items-center gap-1" aria-label="Simulator IoT">
                    <i class="bi bi-cpu" aria-hidden="true"></i> <span class="d-none d-sm-inline">Simulator IoT</span>
                </a>
                <?php if (isset($_SESSION['id_user'])): ?>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" aria-label="Keluar">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div class="container" role="main">