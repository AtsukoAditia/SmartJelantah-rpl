</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga'): ?>
<nav class="navbar fixed-bottom navbar-light bg-white border-top d-md-none shadow-sm" role="navigation" aria-label="Navigasi mobile warga" style="border-top-color: #e9ecef !important;">
    <div class="container-fluid justify-content-around py-2">
        <a href="<?= BASE_URL ?>/user/index.php" class="text-decoration-none text-success text-center small d-flex flex-column align-items-center gap-1" aria-label="Beranda">
            <i class="bi bi-house-door-fill fs-4" aria-hidden="true"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= BASE_URL ?>/simulator_iot.php" class="text-decoration-none text-dark text-center small d-flex flex-column align-items-center gap-1" aria-label="Setor IoT">
            <i class="bi bi-qr-code-scan fs-4" aria-hidden="true"></i>
            <span>Setor IoT</span>
        </a>
        <a href="<?= BASE_URL ?>/user/index.php#riwayat" class="text-decoration-none text-dark text-center small d-flex flex-column align-items-center gap-1" aria-label="Riwayat Setoran">
            <i class="bi bi-clock-history fs-4" aria-hidden="true"></i>
            <span>Riwayat</span>
        </a>
    </div>
</nav>
<?php endif; ?>

<!-- Toast container for notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3 d-md-none" style="z-index: 1080;" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Toast helper function
function showToast(message, type = 'info') {
    var container = document.getElementById('toastContainer');
    if (!container) return;
    
    var bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-primary');
    var icon = type === 'success' ? 'bi-check-circle' : (type === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle');
    
    var toast = document.createElement('div');
    toast.className = 'toast ' + bgClass + ' text-white';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = '<div class="toast-header ' + bgClass + ' text-white border-0">' +
        '<i class="bi ' + icon + ' me-2"></i>' +
        '<strong class="me-auto">SmartJelantah</strong>' +
        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>' +
        '<div class="toast-body">' + message + '</div>';
    
    container.appendChild(toast);
    var bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
</script>
</body>
</html>