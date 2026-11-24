<?php
/**
 * Edit Produk - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID produk tidak valid.');
    header('Location: ' . base_url('admin/produk/index.php'));
    exit;
}

// Ambil data produk
try {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $produk = $stmt->fetch();

    if (!$produk) {
        set_flash('danger', 'Data produk tidak ditemukan.');
        header('Location: ' . base_url('admin/produk/index.php'));
        exit;
    }

    // Ambil daftar kategori
    $stmtKategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategoriList = $stmtKategori->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat data: " . htmlspecialchars($e->getMessage()));
}

$error = '';
$kode_produk   = $produk['kode_produk'];
$nama_produk   = $produk['nama_produk'];
$id_kategori   = $produk['id_kategori'];
$harga_beli    = $produk['harga_beli'];
$harga_jual    = $produk['harga_jual'];
$stok          = $produk['stok'];
$stok_minimum  = $produk['stok_minimum'];
$satuan        = $produk['satuan'];
$gambar_lama   = $produk['gambar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi kembali.';
    } else {
        $kode_produk   = strtoupper(trim($_POST['kode_produk'] ?? ''));
        $nama_produk   = trim($_POST['nama_produk'] ?? '');
        $id_kategori   = (int)($_POST['id_kategori'] ?? 0);
        $harga_beli    = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_beli'] ?? 0);
        $harga_jual    = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_jual'] ?? 0);
        $stok_baru     = (int)($_POST['stok'] ?? 0);
        $stok_minimum  = (int)($_POST['stok_minimum'] ?? 5);
        $satuan        = trim($_POST['satuan'] ?? 'Pcs');

        if (empty($kode_produk)) {
            $error = 'Kode produk wajib diisi!';
        } elseif (empty($nama_produk)) {
            $error = 'Nama produk wajib diisi!';
        } elseif ($id_kategori <= 0) {
            $error = 'Pilih kategori yang valid!';
        } elseif ($harga_beli < 0) {
            $error = 'Harga beli tidak boleh bernilai negatif!';
        } elseif ($harga_jual < 0) {
            $error = 'Harga jual tidak boleh bernilai negatif!';
        } elseif ($stok_baru < 0) {
            $error = 'Stok tidak boleh bernilai negatif!';
        } elseif ($stok_minimum < 0) {
            $error = 'Stok minimum tidak boleh negatif!';
        } else {
            try {
                // Cek duplikasi kode_produk selain id ini
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = :kode AND id_produk != :id");
                $stmtCheck->execute(['kode' => $kode_produk, 'id' => $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = 'Kode produk "' . e($kode_produk) . '" sudah digunakan oleh produk lain!';
                } else {
                    $namaGambar = $gambar_lama;

                    // Cek jika ada upload gambar baru
                    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                        $fileTmp   = $_FILES['gambar']['tmp_name'];
                        $fileName  = $_FILES['gambar']['name'];
                        $fileSize  = $_FILES['gambar']['size'];
                        $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

                        if (!in_array($fileExt, $allowedExt)) {
                            $error = 'Format file tidak diizinkan! Hanya JPG, JPEG, PNG, dan WEBP.';
                        } elseif ($fileSize > 2 * 1024 * 1024) {
                            $error = 'Ukuran gambar maksimal adalah 2 MB!';
                        } else {
                            $namaGambar = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                            $targetPath = __DIR__ . '/../../uploads/produk/' . $namaGambar;

                            if (move_uploaded_file($fileTmp, $targetPath)) {
                                // Hapus gambar lama jika ada
                                if (!empty($gambar_lama) && file_exists(__DIR__ . '/../../uploads/produk/' . $gambar_lama)) {
                                    @unlink(__DIR__ . '/../../uploads/produk/' . $gambar_lama);
                                }
                            } else {
                                $error = 'Gagal menyimpan file gambar baru ke server!';
                                $namaGambar = $gambar_lama;
                            }
                        }
                    }

                    if (empty($error)) {
                        $pdo->beginTransaction();

                        $stmtUpdate = $pdo->prepare("
                            UPDATE produk SET 
                                kode_produk = :kode,
                                nama_produk = :nama,
                                id_kategori = :kategori,
                                harga_beli = :beli,
                                harga_jual = :jual,
                                stok = :stok,
                                stok_minimum = :min,
                                satuan = :satuan,
                                gambar = :gambar
                            WHERE id_produk = :id
                        ");
                        $stmtUpdate->execute([
                            'kode'     => $kode_produk,
                            'nama'     => $nama_produk,
                            'kategori' => $id_kategori,
                            'beli'     => $harga_beli,
                            'jual'     => $harga_jual,
                            'stok'     => $stok_baru,
                            'min'      => $stok_minimum,
                            'satuan'   => $satuan,
                            'gambar'   => $namaGambar,
                            'id'       => $id
                        ]);

                        // Jika ada perubahan stok langsung via edit, catat log penyesuaian
                        $selisihStok = $stok_baru - $stok;
                        if ($selisihStok !== 0) {
                            $stmtMovement = $pdo->prepare("
                                INSERT INTO stock_movements 
                                (id_produk, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) 
                                VALUES 
                                (:id_produk, 'penyesuaian', :jumlah, :stok_sebelum, :stok_sesudah, 'Penyesuaian Manual Edit Data Produk', :id_user)
                            ");
                            $stmtMovement->execute([
                                'id_produk'    => $id,
                                'jumlah'       => abs($selisihStok),
                                'stok_sebelum' => $stok,
                                'stok_sesudah' => $stok_baru,
                                'id_user'      => $_SESSION['user_id']
                            ]);
                        }

                        $pdo->commit();

                        set_flash('success', 'Data produk "' . e($nama_produk) . '" berhasil diperbarui.');
                        header('Location: ' . base_url('admin/produk/index.php'));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Produk - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Edit Produk</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Produk
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
                                    Kode Produk <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase" id="kode_produk" name="kode_produk" value="<?= e($kode_produk); ?>" required>
                            </div>

                            <!-- Nama Produk -->
                            <div class="col-md-8">
                                <label for="nama_produk" class="form-label fw-semibold text-secondary">
                                    Nama Produk <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk" value="<?= e($nama_produk); ?>" required>
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
                                <input type="text" class="form-control" id="satuan" name="satuan" value="<?= e($satuan); ?>" required>
                            </div>

                            <!-- Harga Beli -->
                            <div class="col-md-6">
                                <label for="harga_beli" class="form-label fw-semibold text-secondary">
                                    Harga Beli (Modal) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control" id="harga_beli" name="harga_beli" value="<?= (float)$harga_beli; ?>" required>
                                </div>
                            </div>

                            <!-- Harga Jual -->
                            <div class="col-md-6">
                                <label for="harga_jual" class="form-label fw-semibold text-secondary">
                                    Harga Jual Konsumen <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control" id="harga_jual" name="harga_jual" value="<?= (float)$harga_jual; ?>" required>
                                </div>
                            </div>

                            <!-- Stok Fisik -->
                            <div class="col-md-6">
                                <label for="stok" class="form-label fw-semibold text-secondary">
                                    Stok Fisik <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="0" class="form-control" id="stok" name="stok" value="<?= (int)$stok; ?>" required>
                                <div class="form-text small">Jika diubah, perubahan akan dicatat sebagai 'penyesuaian' stok.</div>
                            </div>

                            <!-- Stok Minimum -->
                            <div class="col-md-6">
                                <label for="stok_minimum" class="form-label fw-semibold text-secondary">
                                    Batas Stok Minimum <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="0" class="form-control" id="stok_minimum" name="stok_minimum" value="<?= (int)$stok_minimum; ?>" required>
                            </div>

                            <!-- Upload Gambar Baru -->
                            <div class="col-12">
                                <label for="gambar" class="form-label fw-semibold text-secondary">
                                    Ganti Gambar Produk (Opsional)
                                </label>
                                <?php if (!empty($gambar_lama) && file_exists(__DIR__ . '/../../uploads/produk/' . $gambar_lama)): ?>
                                    <div class="mb-2 d-flex align-items-center gap-3">
                                        <img src="<?= base_url('uploads/produk/' . $gambar_lama); ?>" alt="Gambar Saat Ini" class="rounded border" style="width: 70px; height: 70px; object-fit: cover;">
                                        <span class="small text-muted">Gambar saat ini. Pilih file baru di bawah jika ingin menggantinya.</span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text small">Biarkan kosong jika tidak ingin mengubah foto produk. Maksimal 2 MB.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/produk/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

