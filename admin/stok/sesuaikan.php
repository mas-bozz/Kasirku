<?php
/**
 * Form Tambah / Kurang / Sesuaikan Stok Barang - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

// Ambil id_produk dari query string jika ada
$idProdukSelected = (int)($_GET['id_produk'] ?? 0);

// Ambil daftar produk untuk pilihan select
try {
    $stmtProduk = $pdo->query("SELECT id_produk, kode_produk, nama_produk, stok, satuan FROM produk ORDER BY nama_produk ASC");
    $daftarProduk = $stmtProduk->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat produk: " . htmlspecialchars($e->getMessage()));
}

$error = '';
$selectedId  = $idProdukSelected;
$jenis       = 'masuk';
$jumlah      = '';
$keterangan  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi form.';
    } else {
        $selectedId = (int)($_POST['id_produk'] ?? 0);
        $jenis      = trim($_POST['jenis'] ?? '');
        $jumlahVal  = (int)($_POST['jumlah'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($selectedId <= 0) {
            $error = 'Pilih produk yang akan disesuaikan stoknya!';
        } elseif (!in_array($jenis, ['masuk', 'keluar', 'penyesuaian'])) {
            $error = 'Pilih jenis mutasi yang valid!';
        } elseif ($jumlahVal <= 0 && $jenis !== 'penyesuaian') {
            $error = 'Jumlah barang harus lebih besar dari 0!';
        } elseif (empty($keterangan)) {
            $error = 'Keterangan / alasan mutasi stok wajib diisi!';
        } else {
            try {
                // Ambil data produk terbaru dengan SELECT FOR UPDATE dalam transaksi
                $pdo->beginTransaction();

                $stmtGet = $pdo->prepare("SELECT id_produk, nama_produk, stok, satuan FROM produk WHERE id_produk = :id FOR UPDATE");
                $stmtGet->execute(['id' => $selectedId]);
                $produk = $stmtGet->fetch();

                if (!$produk) {
                    $pdo->rollBack();
                    $error = 'Data produk tidak ditemukan.';
                } else {
                    $stokSebelum = (int)$produk['stok'];
                    $stokSesudah = $stokSebelum;
                    $jumlahMutasi = $jumlahVal;

                    if ($jenis === 'masuk') {
                        $stokSesudah = $stokSebelum + $jumlahMutasi;
                    } elseif ($jenis === 'keluar') {
                        if ($jumlahMutasi > $stokSebelum) {
                            $error = 'Jumlah stok keluar (' . $jumlahMutasi . ' ' . $produk['satuan'] . ') melebihi stok yang tersedia saat ini (' . $stokSebelum . ' ' . $produk['satuan'] . ')! Stok tidak boleh negatif.';
                        } else {
                            $stokSesudah = $stokSebelum - $jumlahMutasi;
                        }
                    } elseif ($jenis === 'penyesuaian') {
                        // Untuk penyesuaian fisik (stock opname), $jumlahVal diinput sebagai stok fisik aktual
                        $stokFisikAktual = (int)($_POST['jumlah'] ?? 0);
                        if ($stokFisikAktual < 0) {
                            $error = 'Stok fisik hasil opname tidak boleh bernilai negatif!';
                        } else {
                            $stokSesudah = $stokFisikAktual;
                            $jumlahMutasi = abs($stokSesudah - $stokSebelum);
                        }
                    }

                    if (empty($error)) {
                        // 1. Update stok di tabel produk
                        $stmtUpdate = $pdo->prepare("UPDATE produk SET stok = :stok WHERE id_produk = :id");
                        $stmtUpdate->execute([
                            'stok' => $stokSesudah,
                            'id'   => $selectedId
                        ]);

                        // 2. Simpan catatan ke tabel stock_movements
                        $stmtMovement = $pdo->prepare("
                            INSERT INTO stock_movements 
                            (id_produk, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) 
                            VALUES 
                            (:id_produk, :jenis, :jumlah, :sebelum, :sesudah, :ket, :uid)
                        ");
                        $stmtMovement->execute([
                            'id_produk' => $selectedId,
                            'jenis'     => $jenis,
                            'jumlah'    => $jumlahMutasi,
                            'sebelum'   => $stokSebelum,
                            'sesudah'   => $stokSesudah,
                            'ket'       => $keterangan,
                            'uid'       => $_SESSION['user_id']
                        ]);

                        $pdo->commit();

                        set_flash('success', 'Mutasi stok untuk produk "' . e($produk['nama_produk']) . '" berhasil disimpan. Sisa stok baru: ' . $stokSesudah . ' ' . e($produk['satuan']) . '.');
                        header('Location: ' . base_url('admin/stok/index.php?id_produk=' . $selectedId));
                        exit;
                    } else {
                        $pdo->rollBack();
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

$pageTitle = 'Penyesuaian Stok - KasirKu';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/stok/index.php'); ?>">Manajemen Stok</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sesuaikan Stok</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i> Form Penyesuaian / Mutasi Stok
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off" id="formStok">
                        <?= csrf_field(); ?>

                        <!-- Pilih Produk -->
                        <div class="mb-3">
                            <label for="id_produk" class="form-label fw-semibold text-secondary">
                                Pilih Produk <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select-produk" id="id_produk" name="id_produk" required onchange="updateInfoStok()">
                                <option value="">-- Pilih Produk yang akan disesuaikan --</option>
                                <?php foreach ($daftarProduk as $p): ?>
                                    <option value="<?= $p['id_produk']; ?>" 
                                            data-stok="<?= $p['stok']; ?>" 
                                            data-satuan="<?= e($p['satuan']); ?>"
                                            <?= $selectedId == $p['id_produk'] ? 'selected' : ''; ?>>
                                        [<?= e($p['kode_produk']); ?>] <?= e($p['nama_produk']); ?> (Stok Saat Ini: <?= $p['stok']; ?> <?= e($p['satuan']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Card Info Stok Saat Ini -->
                        <div class="p-3 bg-light rounded-3 border mb-3 d-flex justify-content-between align-items-center" id="cardInfoStok">
                            <div>
                                <span class="text-muted small">Stok Tercatat Saat Ini:</span>
                                <div class="fs-4 fw-bold text-dark mt-1" id="labelStokSaatIni">-</div>
                            </div>
                            <div class="text-end text-muted small">
                                Satuan: <span class="fw-semibold text-dark" id="labelSatuan">-</span>
                            </div>
                        </div>

                        <!-- Pilihan Jenis Mutasi -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">
                                Jenis Mutasi <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_masuk" value="masuk" <?= $jenis === 'masuk' ? 'checked' : ''; ?> onchange="onJenisChange()">
                                    <label class="btn btn-outline-success w-100 py-2 d-flex flex-column align-items-center" for="jenis_masuk">
                                        <i class="bi bi-box-arrow-in-down fs-4 mb-1"></i>
                                        <span class="fw-bold">Stok Masuk</span>
                                        <small class="text-muted">Barang masuk / Restock</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_keluar" value="keluar" <?= $jenis === 'keluar' ? 'checked' : ''; ?> onchange="onJenisChange()">
                                    <label class="btn btn-outline-danger w-100 py-2 d-flex flex-column align-items-center" for="jenis_keluar">
                                        <i class="bi bi-box-arrow-up fs-4 mb-1"></i>
                                        <span class="fw-bold">Stok Keluar</span>
                                        <small class="text-muted">Rusak / Retur / Hilang</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_penyesuaian" value="penyesuaian" <?= $jenis === 'penyesuaian' ? 'checked' : ''; ?> onchange="onJenisChange()">
                                    <label class="btn btn-outline-info w-100 py-2 d-flex flex-column align-items-center" for="jenis_penyesuaian">
                                        <i class="bi bi-sliders fs-4 mb-1"></i>
                                        <span class="fw-bold">Penyesuaian</span>
                                        <small class="text-muted">Stock Opname Fisik</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Input Jumlah / Stok Aktual -->
                        <div class="mb-3">
                            <label for="jumlah" class="form-label fw-semibold text-secondary" id="labelJumlahInput">
                                Jumlah Barang Masuk <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" min="0" class="form-control" id="jumlah" name="jumlah" value="<?= e($jumlah); ?>" placeholder="Masukkan jumlah" required>
                                <span class="input-group-text bg-light text-muted" id="satuanAddon">Pcs</span>
                            </div>
                            <div class="form-text small" id="hintJumlahInput">
                                Masukkan jumlah barang yang ditambahkan ke stok toko.
                            </div>
                        </div>

                        <!-- Input Keterangan / Alasan -->
                        <div class="mb-4">
                            <label for="keterangan" class="form-label fw-semibold text-secondary">
                                Keterangan / Alasan Mutasi <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Contoh: Penerimaan faktur no. 2026/XI/01 dari Supplier" required><?= e($keterangan); ?></textarea>
                            <div class="form-text small">Catatan ini akan disimpan secara permanen di kartu riwayat mutasi stok.</div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/stok/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Simpan Mutasi Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateInfoStok() {
    const select = document.getElementById('id_produk');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const stok = selectedOption.getAttribute('data-stok') || 0;
        const satuan = selectedOption.getAttribute('data-satuan') || 'Pcs';
        
        document.getElementById('labelStokSaatIni').textContent = stok + ' ' + satuan;
        document.getElementById('labelSatuan').textContent = satuan;
        document.getElementById('satuanAddon').textContent = satuan;
    } else {
        document.getElementById('labelStokSaatIni').textContent = '-';
        document.getElementById('labelSatuan').textContent = '-';
        document.getElementById('satuanAddon').textContent = 'Pcs';
    }
}

function onJenisChange() {
    const jenis = document.querySelector('input[name="jenis"]:checked').value;
    const label = document.getElementById('labelJumlahInput');
    const hint = document.getElementById('hintJumlahInput');
    const jumlahInput = document.getElementById('jumlah');

    if (jenis === 'masuk') {
        label.innerHTML = 'Jumlah Barang Masuk <span class="text-danger">*</span>';
        hint.textContent = 'Masukkan jumlah barang yang ditambahkan ke stok toko.';
        jumlahInput.placeholder = 'Contoh: 10';
    } else if (jenis === 'keluar') {
        label.innerHTML = 'Jumlah Barang Keluar <span class="text-danger">*</span>';
        hint.textContent = 'Masukkan jumlah barang yang dikeluarkan/dikurangi dari stok toko.';
        jumlahInput.placeholder = 'Contoh: 2';
    } else if (jenis === 'penyesuaian') {
        label.innerHTML = 'Hasil Hitung Stok Fisik Aktual (Opname) <span class="text-danger">*</span>';
        hint.textContent = 'Masukkan jumlah stok riil yang ada di rak/gudang setelah dihitung fisik.';
        jumlahInput.placeholder = 'Contoh: 15';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateInfoStok();
    onJenisChange();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

