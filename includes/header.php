<?php
/**
 * Header Global Template KasirKu
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
$pageTitle = $pageTitle ?? 'KasirKu - Sistem Informasi Toko Kasir';
$userNama = $_SESSION['nama'] ?? 'Pengguna';
$userRole = $_SESSION['role'] ?? 'kasir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
</head>
<body class="bg-light">

