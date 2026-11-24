<?php
/**
 * Navbar Global Template KasirKu
 */
$currentRole = $_SESSION['role'] ?? 'kasir';
$currentNama = $_SESSION['nama'] ?? 'User';
$flash = get_flash();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center fw-bold text-white" href="<?= base_url(); ?>">
            <i class="bi bi-cart-check-fill text-primary fs-4 me-2"></i>
            <span>KasirKu</span>
            <span class="badge bg-secondary-subtle text-secondary fs-6 fw-normal ms-2 text-uppercase"><?= e($currentRole); ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <?php if ($currentRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/index.php'); ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/kategori/index.php'); ?>">
                            <i class="bi bi-tags me-1"></i> Kategori
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/produk/index.php'); ?>">
                            <i class="bi bi-box-seam me-1"></i> Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/stok/index.php'); ?>">
                            <i class="bi bi-arrow-left-right me-1"></i> Stok
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/transaksi/index.php'); ?>">
                            <i class="bi bi-receipt me-1"></i> Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/laporan/index.php'); ?>">
                            <i class="bi bi-graph-up-arrow me-1"></i> Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/user/index.php'); ?>">
                            <i class="bi bi-people me-1"></i> User
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn btn-primary text-white btn-sm px-3 mt-1 mt-lg-0" href="<?= base_url('kasir/transaksi.php'); ?>">
                            <i class="bi bi-cart-plus me-1"></i> Buka Kasir POS
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('kasir/index.php'); ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white btn-sm px-3 mt-1 mt-lg-0" href="<?= base_url('kasir/transaksi.php'); ?>">
                            <i class="bi bi-cart-plus me-1"></i> Transaksi Baru (POS)
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link" href="<?= base_url('kasir/riwayat.php'); ?>">
                            <i class="bi bi-clock-history me-1"></i> Riwayat Transaksi
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-2 text-primary"></i>
                        <span><?= e($currentNama); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="fw-bold"><?= e($currentNama); ?></div>
                            <small class="text-muted">Role: <?= strtoupper(e($currentRole)); ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= base_url('auth/logout.php'); ?>">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Container flash notification -->
<div class="container-fluid px-3 px-lg-4 mt-3">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> <?= e($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

