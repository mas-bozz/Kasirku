<?php
/**
 * Proses Logout Pengguna
 */
require_once __DIR__ . '/../config/database.php';

// Hapus semua data session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Mulai session baru hanya untuk menyampaikan flash message
session_start();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');

header('Location: ' . base_url('auth/login.php'));
exit;

