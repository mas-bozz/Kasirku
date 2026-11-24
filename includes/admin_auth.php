<?php
/**
 * Auth Middleware Khusus Role Admin
 * Mencegah Kasir mengakses menu administrasi
 */
require_once __DIR__ . '/auth.php';

if ($_SESSION['role'] !== 'admin') {
    set_flash('danger', 'Akses ditolak! Halaman tersebut hanya dapat diakses oleh Administrator.');
    header('Location: ' . base_url('kasir/index.php'));
    exit;
}

