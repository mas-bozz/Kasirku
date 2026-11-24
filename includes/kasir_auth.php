<?php
/**
 * Auth Middleware Khusus Menu Kasir / Penjualan
 */
require_once __DIR__ . '/auth.php';

// Kasir & Admin boleh mengakses transaksi kasir
if (!in_array($_SESSION['role'], ['admin', 'kasir'])) {
    set_flash('danger', 'Akses tidak sah.');
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

