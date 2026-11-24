<?php
/**
 * Auth Middleware Global
 * Memastikan user sudah login dan akun aktif
 */
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses sistem.');
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

// Cek apakah status user masih aktif di database
try {
    $stmtUserCheck = $pdo->prepare("SELECT status FROM users WHERE id_user = :id");
    $stmtUserCheck->execute(['id' => $_SESSION['user_id']]);
    $currentUserStatus = $stmtUserCheck->fetchColumn();

    if ($currentUserStatus !== 'aktif') {
        session_unset();
        session_destroy();
        session_start();
        set_flash('danger', 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
} catch (PDOException $e) {
    // Abaikan jika koneksi sementara bermasalah
}

