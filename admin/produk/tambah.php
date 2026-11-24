<?php
/**
 * Tambah Produk - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

// Ambil kategori untuk pilihan select
try {
    $stmtKategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategoriList = $stmtKategori->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat kategori: " . htmlspecialchars($e->getMessage()));
}

$error = '';
$kode_produk   = '';
$nama_produk   = '';
$id_kategori   = '';
$harga_beli    = '';
$harga_jual    = '';
$stok          = '0';
$stok_minimum  = '5';
$satuan        = 'Pcs';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi form.';
    } else {
        $kode_produk   = strtoupper(trim($_POST['kode_produk'] ?? ''));
        $nama_produk   = trim($_POST['nama_produk'] ?? '');
        $id_kategori   = (int)($_POST['id_kategori'] ?? 0);
        $harga_beli    = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_beli'] ?? 0);
        $harga_jual    = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_jual'] ?? 0);
        $stok          = (int)($_POST['stok'] ?? 0);
        $stok_minimum  = (int)($_POST['stok_minimum'] ?? 5);
        $satuan        = trim($_POST['satuan'] ?? 'Pcs');

        // Validasi input
        if (empty($kode_produk)) {
            $error = 'Kode produk wajib diisi!';
        } elseif (empty($nama_produk)) {
            $error = 'Nama produk wajib diisi!';
        } elseif ($id_kategori <= 0) {
            $error = 'Pilih kategori yang valid!';
        } elseif ($harga_beli < 0) {
            $error = 'Harga beli tidak boleh negatif!';
        } elseif ($harga_jual < 0) {
            $error = 'Harga jual tidak boleh negatif!';
        } elseif ($stok < 0) {
            $error = 'Stok tidak boleh bernilai negatif!';
        } elseif ($stok_minimum < 0) {
            $error = 'Stok minimum tidak boleh negatif!';
        } else {
            try {
                // 1. Cek keunikan kode_produk
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = :kode");
                $stmtCheck->execute(['kode' => $kode_produk]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = 'Kode produk "' . e($kode_produk) . '" sudah digunakan! Masukkan kode yang berbeda.';
                } else {
                    // 2. Upload Gambar jika ada
                    $namaGambar = null;
                    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                        $fileTmp   = $_FILES['gambar']['tmp_name'];
                        $fileName  = $_FILES['gambar']['name'];
                        $fileSize  = $_FILES['gambar']['size'];
                        $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

                        if (!in_array($fileExt, $allowedExt)) {
                            $error = 'Format gambar tidak diizinkan! Hanya JPG, JPEG, PNG, dan WEBP.';
                        } elseif ($fileSize > 2 * 1024 * 1024) {
                            $error = 'Ukuran gambar maksimal adalah 2 MB!';
                        } else {
                            $namaGambar = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                            $targetPath = __DIR__ . '/../../uploads/produk/' . $namaGambar;

                            if (!move_uploaded_file($fileTmp, $targetPath)) {
                                $error = 'Gagal menyimpan file gambar ke server!';
                                $namaGambar = null;
                            }
                        }
                    }

                    // Jika tidak ada error dalam upload gambar
                    if (empty($error)) {
                        $pdo->beginTransaction();

                        $stmtInsert = $pdo->prepare("
                            INSERT INTO produk 
                            (kode_produk, nama_produk, id_kategori, harga_beli, harga_jual, stok, stok_minimum, satuan, gambar) 
                            VALUES 
                            (:kode, :nama, :kategori, :beli, :jual, :stok, :min, :satuan, :gambar)
                        ");
                        $stmtInsert->execute([
                            'kode'     => $kode_produk,
                            'nama'     => $nama_produk,
                            'kategori' => $id_kategori,
                            'beli'     => $harga_beli,
                            'jual'     => $harga_jual,
                            'stok'     => $stok,
                            'min'      => $stok_minimum,
                            'satuan'   => $satuan,
                            'gambar'   => $namaGambar
                        ]);

                        $newProdukId = (int)$pdo->lastInsertId();

                        // Jika ada stok awal (> 0), catat ke stock_movements
                        if ($stok > 0) {
                            $stmtMovement = $pdo->prepare("
                                INSERT INTO stock_movements 
                                (id_produk, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) 
                                VALUES 
                                (:id_produk, 'masuk', :jumlah, 0, :stok_sesudah, 'Stok Awal Produk Baru', :id_user)
                            ");
                            $stmtMovement->execute([
                                'id_produk'    => $newProdukId,
                                'jumlah'       => $stok,
                                'stok_sesudah' => $stok,
                                'id_user'      => $_SESSION['user_id']
                            ]);
                        }

                        $pdo->commit();

                        set_flash('success', 'Produk "' . e($nama_produk) . '" berhasil ditambahkan.');
                        header('Location: ' . base_url('admin/produk/index.php'));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan database: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Tambah Produk - KasirKu';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/produk/index.php'); ?>">Produk</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tambah Produk</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-box-seam text-primary me-2"></i> Tambah Master Produk Baru
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">
                        <?= csrf_field(); ?>

                        <div class="row g-3">
                            <!-- Kode Produk -->
                            <div class="col-md-4">
                                <label for="kode_produk" class="form-label fw-semibold text-secondary">
                                    Kode Produk / Barcode <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase" id="kode_produk" name="kode_produk" value="<?= e($kode_produk); ?>" placeholder="Contoh: PRD011" required autofocus>
                                <div class="form-text small">Kode harus unik untuk setiap produk.</div>
                            </div>

                            <!-- Nama Produk -->
                            <div class="col-md-8">
                                <label for="nama_produk" class="form-label fw-semibold text-secondary">
                                    Nama Produk <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?= e($nama_produk); ?>" placeholder="Masukkan nama lengkap produk" required>
                            </div>

                            <!-- Kategori -->
                            <div class="col-md-6">
                                <label for="id_kategori" class="form-label fw-semibold text-secondary">
                                    Kategori Produk <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="id_kategori" name="id_kategori" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $k['id_kategori']; ?>" <?= $id_kategori == $k['id_kategori'] ? 'selected' : ''; ?>>
                                            <?= e($k['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Satuan -->
                            <div class="col-md-6">
                                <label for="satuan" class="form-label fw-semibold text-secondary">
                                    Satuan Barang <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="satuan" name="satuan" value="<?= e($satuan); ?>" placeholder="Contoh: Pcs, Kg, Botol, Bungkus" required>
                            </div>

                            <!-- Harga Beli (Modal) -->
                            <div class="col-md-6">
                                <label for="harga_beli" class="form-label fw-semibold text-secondary">
                                    Harga Beli (Modal) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control" id="harga_beli" name="harga_beli" value="<?= e($harga_beli); ?>" placeholder="0" required>
                                </div>
                            </div>

                            <!-- Harga Jual -->
                            <div class="col-md-6">
                                <label for="harga_jual" class="form-label fw-semibold text-secondary">
                                    Harga Jual Konsumen <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control" id="harga_jual" name="harga_jual" value="<?= e($harga_jual); ?>" placeholder="0" required>
                                </div>
                            </div>

                            <!-- Stok Awal -->
                            <div class="col-md-6">
                                <label for="stok" class="form-label fw-semibold text-secondary">
                                    Stok Awal <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="0" class="form-control" id="stok" name="stok" value="<?= e($stok); ?>" required>
                                <div class="form-text small">Stok fisik saat produk didaftarkan.</div>
                            </div>

                            <!-- Stok Minimum Alert -->
                            <div class="col-md-6">
                                <label for="stok_minimum" class="form-label fw-semibold text-secondary">
                                    Batas Stok Minimum <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="0" class="form-control" id="stok_minimum" name="stok_minimum" value="<?= e($stok_minimum); ?>" required>
                                <div class="form-text small">Sistem akan memberi tanda peringatan jika stok mencapai nilai ini.</div>
                            </div>

                            <!-- Upload Gambar -->
                            <div class="col-12">
                                <label for="gambar" class="form-label fw-semibold text-secondary">
                                    Foto / Gambar Produk (Opsional)
                                </label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text small">Maksimal 2 MB. Format: JPG, JPEG, PNG, WEBP.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/produk/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Simpan Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

