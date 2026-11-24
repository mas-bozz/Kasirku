<?php
/**
 * Root Index - Pengarah Rute Awal Aplikasi
 */
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . base_url('admin/index.php'));
        exit;
    } else {
        header('Location: ' . base_url('kasir/index.php'));
        exit;
    }
} else {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

