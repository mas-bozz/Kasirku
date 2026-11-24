<?php
/**
 * Konfigurasi Database & Helper Global KasirKu
 * PHP Native 8.x + PDO MySQL/MariaDB
 */

// Mulai session jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'kasirku');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Deteksi Base URL Otomatis
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Mengambil root folder aplikasi secara konsisten (misal: /KASIR)
$project_folder = '';
$dirs = explode('/', trim($script_name, '/'));
if (!empty($dirs[0])) {
    // Ambil segment pertama path sebagai root aplikasi jika di subfolder localhost
    $project_folder = '/' . $dirs[0];
}
define('BASE_URL', rtrim($protocol . $host . $project_folder, '/') . '/');

// Inisialisasi Koneksi PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("<h3>Koneksi Database Gagal!</h3><p>Pastikan MySQL di Laragon/XAMPP sudah aktif dan database <strong>" . DB_NAME . "</strong> sudah diimport.</p><p>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// ========================================================
// HELPER FUNCTIONS SEDERHANA & BERGUNA
// ========================================================

/**
 * Format angka ke format mata uang Rupiah
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Sanitasi string output untuk mencegah XSS
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper Generate URL
 */
function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Helper Flash Message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Helper CSRF Token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

