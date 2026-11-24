<?php
/**
 * Terminal POS / Transaksi Kasir - KasirKu
 */
require_once __DIR__ . '/../includes/kasir_auth.php';

try {
    // Ambil semua kategori untuk tombol filter
    $stmtKat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategoriList = $stmtKat->fetchAll();

    // Ambil seluruh produk yang siap dijual (diurutkan berdasarkan nama)
    $stmtProduk = $pdo->query("
        SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
        ORDER BY p.nama_produk ASC
    ");
    $produkList = $stmtProduk->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan memuat data POS: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Terminal Kasir POS - KasirKu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="row g-3">
        
        <!-- KOLOM KIRI: KATALOG PRODUK -->
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                
                <!-- Header Katalog & Kotak Pencarian -->
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="posSearch" class="form-control border-start-0 ps-0" placeholder="Ketik nama produk atau scan barcode..." autofocus>
                                <button type="button" class="btn btn-outline-secondary" id="btnResetSearch" title="Reset Pencarian">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end text-muted small">
                            <i class="bi bi-person-badge me-1"></i> Kasir: <strong><?= e($_SESSION['nama']); ?></strong>
                        </div>
                    </div>

                    <!-- Filter Kategori Tombol Pill -->
                    <div class="d-flex gap-2 overflow-auto pt-3 pb-1" id="categoryFilterContainer">
                        <button type="button" class="btn btn-sm btn-primary category-btn active" data-kategori="all">
                            Semua (<?= count($produkList); ?>)
                        </button>
                        <?php foreach ($kategoriList as $k): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary category-btn" data-kategori="<?= $k['id_kategori']; ?>">
                                <?= e($k['nama_kategori']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Daftar Grid Produk -->
                <div class="card-body p-3 overflow-y-auto" style="max-height: calc(100vh - 240px);">
                    <div class="row g-3" id="productGrid">
                        <?php foreach ($produkList as $item): ?>
                            <div class="col-6 col-sm-4 col-md-4 col-xl-3 product-item" 
                                 data-id="<?= $item['id_produk']; ?>"
                                 data-kode="<?= strtolower(e($item['kode_produk'])); ?>"
                                 data-nama="<?= strtolower(e($item['nama_produk'])); ?>"
                                 data-kategori="<?= $item['id_kategori']; ?>"
                                 data-harga="<?= (float)$item['harga_jual']; ?>"
                                 data-stok="<?= (int)$item['stok']; ?>"
                                 data-satuan="<?= e($item['satuan']); ?>">
                                
                                <div class="card h-100 pos-product-card rounded-3 p-2 text-start position-relative <?= $item['stok'] <= 0 ? 'opacity-50' : ''; ?>"
                                     onclick="addToCart(<?= $item['id_produk']; ?>, '<?= addslashes(e($item['nama_produk'])); ?>', <?= (float)$item['harga_jual']; ?>, <?= (int)$item['stok']; ?>, '<?= addslashes(e($item['satuan'])); ?>')">
                                    
                                    <!-- Badge Stok -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <?php if ($item['stok'] <= 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php elseif ($item['stok'] <= $item['stok_minimum']): ?>
                                            <span class="badge bg-warning text-dark"><?= $item['stok']; ?> <?= e($item['satuan']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $item['stok']; ?> <?= e($item['satuan']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Foto Produk -->
                                    <div class="text-center my-2" style="height: 75px;">
                                        <?php if (!empty($item['gambar']) && file_exists(__DIR__ . '/../uploads/produk/' . $item['gambar'])): ?>
                                            <img src="<?= base_url('uploads/produk/' . $item['gambar']); ?>" alt="<?= e($item['nama_produk']); ?>" class="img-fluid rounded object-fit-contain h-100">
                                        <?php else: ?>
                                            <div class="bg-light rounded h-100 d-flex align-items-center justify-content-center text-muted">
                                                <i class="bi bi-box-seam fs-2"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Informasi Produk -->
                                    <div class="small text-muted fw-bold"><?= e($item['kode_produk']); ?></div>
                                    <div class="fw-semibold text-dark text-truncate mb-1" title="<?= e($item['nama_produk']); ?>">
                                        <?= e($item['nama_produk']); ?>
                                    </div>
                                    <div class="fw-bold text-primary fs-6 mt-auto">
                                        <?= format_rupiah($item['harga_jual']); ?>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pesan saat produk tidak ditemukan -->
                    <div id="noProductAlert" class="text-center py-5 d-none text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                        <h5>Produk tidak ditemukan</h5>
                        <p class="small">Coba kata kunci pencarian yang lain.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- KOLOM KANAN: KERANJANG BELANJA & PEMBAYARAN -->
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark fs-5">
                        <i class="bi bi-cart3 text-primary me-2"></i> Keranjang
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()" title="Kosongkan Keranjang">
                        <i class="bi bi-trash me-1"></i> Kosongkan
                    </button>
                </div>

                <!-- Form Transaksi -->
                <form id="posForm" method="POST" action="<?= base_url('kasir/proses_transaksi.php'); ?>" class="d-flex flex-column flex-grow-1">
                    <?= csrf_field(); ?>
                    
                    <!-- Hidden input data items transaksi -->
                    <input type="hidden" name="cart_items" id="cartItemsInput" value="[]">

                    <!-- Daftar Item dalam Keranjang -->
                    <div class="card-body p-3 overflow-y-auto flex-grow-1" id="cartContainer" style="max-height: 320px; min-height: 200px;">
                        <div id="emptyCartMessage" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                            <div class="fw-semibold">Keranjang masih kosong</div>
                            <div class="small">Pilih atau klik produk di sebelah kiri untuk menambahkan.</div>
                        </div>

                        <div id="cartItemList" class="d-flex flex-column gap-2"></div>
                    </div>

                    <!-- Rincian Total & Pembayaran -->
                    <div class="card-footer bg-light p-3 border-top mt-auto">
                        
                        <!-- Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small">Subtotal:</span>
                            <span class="fw-semibold text-dark" id="displaySubtotal">Rp 0</span>
                            <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                        </div>

                        <!-- Diskon -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small">Diskon (Rp):</span>
                            <div style="width: 140px;">
                                <input type="number" min="0" step="any" class="form-control form-control-sm text-end" id="inputDiskon" name="diskon" value="0" oninput="calculateTotal()">
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-white border mb-3">
                            <span class="fw-bold text-dark fs-6">TOTAL BAYAR:</span>
                            <span class="fw-bold text-primary fs-4" id="displayTotal">Rp 0</span>
                            <input type="hidden" name="total" id="inputTotal" value="0">
                        </div>

                        <!-- Pilihan Metode Pembayaran -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Metode Pembayaran</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="metode_tunai" value="Tunai" checked onchange="onMetodeChange()">
                                    <label class="btn btn-outline-secondary btn-sm w-100 fw-semibold" for="metode_tunai">
                                        <i class="bi bi-cash me-1"></i> Tunai
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="metode_transfer" value="Transfer" onchange="onMetodeChange()">
                                    <label class="btn btn-outline-secondary btn-sm w-100 fw-semibold" for="metode_transfer">
                                        <i class="bi bi-bank me-1"></i> Transfer
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="metode_qris" value="QRIS" onchange="onMetodeChange()">
                                    <label class="btn btn-outline-secondary btn-sm w-100 fw-semibold" for="metode_qris">
                                        <i class="bi bi-qr-code-scan me-1"></i> QRIS
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Input Uang Diterima -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="inputBayar" class="form-label small fw-semibold text-secondary mb-0">Uang Diterima (Bayar):</label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold" onclick="setUangPas()">
                                    Uang Pas
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" min="0" step="any" class="form-control text-end fw-bold fs-5" id="inputBayar" name="pembayaran" value="0" required oninput="calculateKembalian()">
                            </div>
                        </div>

                        <!-- Shortcut Nominal Cepat -->
                        <div class="d-flex flex-wrap gap-1 mb-3" id="quickMoneyButtons">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addMoney(10000)">+10rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addMoney(20000)">+20rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addMoney(50000)">+50rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="addMoney(100000)">+100rb</button>
                        </div>

                        <!-- Kembalian -->
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-white border mb-3">
                            <span class="small fw-semibold text-secondary">Kembalian:</span>
                            <span class="fw-bold text-success fs-5" id="displayKembalian">Rp 0</span>
                            <input type="hidden" name="kembalian" id="inputKembalian" value="0">
                        </div>

                        <!-- Pesan Peringatan Pembayaran Kurang -->
                        <div id="paymentAlert" class="alert alert-danger py-1 px-2 small mb-3 d-none text-center">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Pembayaran tidak mencukupi!
                        </div>

                        <!-- Tombol Proses Transaksi -->
                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow-sm" id="btnSubmitTrx">
                            <i class="bi bi-printer-fill me-2"></i> BAYAR & CETAK STRUK
                        </button>

                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

<!-- JAVASCRIPT LOGIKA KASIR POS -->
<script>
let cart = [];

// Format angka ke format Rupiah
function formatRupiah(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
}

// Tambah produk ke keranjang
function addToCart(id, nama, harga, stok, satuan) {
    if (stok <= 0) {
        alert('Maaf, stok barang "' + nama + '" sudah habis!');
        return;
    }

    const existingIndex = cart.findIndex(item => item.id === id);

    if (existingIndex > -1) {
        if (cart[existingIndex].qty + 1 > stok) {
            alert('Jumlah beli melebihi stok yang tersedia (' + stok + ' ' + satuan + ')!');
            return;
        }
        cart[existingIndex].qty += 1;
    } else {
        cart.push({
            id: id,
            nama: nama,
            harga: harga,
            stok: stok,
            satuan: satuan,
            qty: 1
        });
    }

    renderCart();
}

// Ubah quantity item
function changeQty(index, delta) {
    const item = cart[index];
    const newQty = item.qty + delta;

    if (newQty <= 0) {
        removeFromCart(index);
    } else if (newQty > item.stok) {
        alert('Jumlah beli melebihi sisa stok yang ada (' + item.stok + ' ' + item.satuan + ')!');
    } else {
        item.qty = newQty;
        renderCart();
    }
}

// Ubah quantity langsung dari input
function setItemQty(index, value) {
    let newQty = parseInt(value) || 0;
    const item = cart[index];

    if (newQty <= 0) {
        removeFromCart(index);
    } else if (newQty > item.stok) {
        alert('Maksimal stok tersedia adalah ' + item.stok + ' ' + item.satuan + '!');
        item.qty = item.stok;
        renderCart();
    } else {
        item.qty = newQty;
        renderCart();
    }
}

// Hapus item dari keranjang
function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

// Kosongkan keranjang
function clearCart() {
    if (cart.length > 0 && confirm('Kosongkan semua barang dari keranjang belanja?')) {
        cart = [];
        renderCart();
    }
}

// Render tampilan keranjang belanja
function renderCart() {
    const emptyMsg = document.getElementById('emptyCartMessage');
    const itemList = document.getElementById('cartItemList');
    const cartInput = document.getElementById('cartItemsInput');

    itemList.innerHTML = '';

    if (cart.length === 0) {
        emptyMsg.classList.remove('d-none');
        cartInput.value = '[]';
    } else {
        emptyMsg.classList.add('d-none');
        cartInput.value = JSON.stringify(cart);

        cart.forEach((item, index) => {
            const subtotalItem = item.harga * item.qty;
            const itemElement = document.createElement('div');
            itemElement.className = 'p-2 rounded border bg-white shadow-sm';
            itemElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;" title="${item.nama}">
                        ${item.nama}
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="removeFromCart(${index})" title="Hapus">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="input-group input-group-sm" style="width: 110px;">
                        <button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(${index}, -1)">-</button>
                        <input type="number" min="1" max="${item.stok}" class="form-control text-center p-0" value="${item.qty}" onchange="setItemQty(${index}, this.value)">
                        <button type="button" class="btn btn-outline-secondary px-2" onclick="changeQty(${index}, 1)">+</button>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">${formatRupiah(item.harga)} x ${item.qty}</small>
                        <span class="fw-bold text-dark">${formatRupiah(subtotalItem)}</span>
                    </div>
                </div>
            `;
            itemList.appendChild(itemElement);
        });
    }

    calculateTotal();
}

// Hitung Subtotal, Diskon, dan Grand Total
function calculateTotal() {
    const subtotal = cart.reduce((acc, item) => acc + (item.harga * item.qty), 0);
    const diskonInput = parseFloat(document.getElementById('inputDiskon').value) || 0;
    
    // Total setelah diskon (tidak boleh kurang dari 0)
    const grandTotal = Math.max(0, subtotal - diskonInput);

    document.getElementById('displaySubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('inputSubtotal').value = subtotal;

    document.getElementById('displayTotal').textContent = formatRupiah(grandTotal);
    document.getElementById('inputTotal').value = grandTotal;

    // Jika metode Transfer / QRIS dipilih, sinkronkan nominal bayar otomatis
    const selectedMetode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
    if (selectedMetode !== 'Tunai') {
        document.getElementById('inputBayar').value = grandTotal;
    }

    calculateKembalian();
}

// Hitung Kembalian
function calculateKembalian() {
    const total = parseFloat(document.getElementById('inputTotal').value) || 0;
    const bayar = parseFloat(document.getElementById('inputBayar').value) || 0;
    const alertElem = document.getElementById('paymentAlert');
    const submitBtn = document.getElementById('btnSubmitTrx');

    const kembalian = bayar - total;

    if (total > 0 && bayar < total) {
        alertElem.classList.remove('d-none');
        document.getElementById('displayKembalian').textContent = 'Rp 0';
        document.getElementById('inputKembalian').value = 0;
        submitBtn.disabled = true;
    } else {
        alertElem.classList.add('d-none');
        document.getElementById('displayKembalian').textContent = formatRupiah(Math.max(0, kembalian));
        document.getElementById('inputKembalian').value = Math.max(0, kembalian);
        submitBtn.disabled = (cart.length === 0);
    }
}

// Set Nominal Uang Pas
function setUangPas() {
    const total = parseFloat(document.getElementById('inputTotal').value) || 0;
    document.getElementById('inputBayar').value = total;
    calculateKembalian();
}

// Tombol Tambah Nominal Cepat
function addMoney(nominal) {
    let current = parseFloat(document.getElementById('inputBayar').value) || 0;
    document.getElementById('inputBayar').value = current + nominal;
    calculateKembalian();
}

// Perubahan Metode Pembayaran
function onMetodeChange() {
    const selectedMetode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
    const quickButtons = document.getElementById('quickMoneyButtons');
    const inputBayar = document.getElementById('inputBayar');
    const total = parseFloat(document.getElementById('inputTotal').value) || 0;

    if (selectedMetode === 'Tunai') {
        quickButtons.classList.remove('d-none');
        inputBayar.readOnly = false;
    } else {
        quickButtons.classList.add('d-none');
        inputBayar.value = total;
        inputBayar.readOnly = true;
    }
    calculateKembalian();
}

// Form validation sebelum submit
document.getElementById('posForm').addEventListener('submit', function(e) {
    if (cart.length === 0) {
        e.preventDefault();
        alert('Keranjang belanja masih kosong! Silakan pilih produk terlebih dahulu.');
        return false;
    }

    const total = parseFloat(document.getElementById('inputTotal').value) || 0;
    const bayar = parseFloat(document.getElementById('inputBayar').value) || 0;

    if (bayar < total) {
        e.preventDefault();
        alert('Pembayaran tidak mencukupi! Masukkan uang pembayaran minimal sebesar ' + formatRupiah(total));
        return false;
    }
});

// Fitur Pencarian Realtime Produk & Filter Kategori
document.getElementById('posSearch').addEventListener('input', function() {
    filterProducts();
});

document.getElementById('btnResetSearch').addEventListener('click', function() {
    document.getElementById('posSearch').value = '';
    filterProducts();
});

document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary', 'active');
        filterProducts();
    });
});

function filterProducts() {
    const query = document.getElementById('posSearch').value.toLowerCase().trim();
    const activeCategoryBtn = document.querySelector('.category-btn.active');
    const selectedCat = activeCategoryBtn ? activeCategoryBtn.getAttribute('data-kategori') : 'all';

    let visibleCount = 0;
    const products = document.querySelectorAll('.product-item');

    products.forEach(p => {
        const kode = p.getAttribute('data-kode');
        const nama = p.getAttribute('data-nama');
        const kat = p.getAttribute('data-kategori');

        const matchSearch = kode.includes(query) || nama.includes(query);
        const matchCategory = (selectedCat === 'all' || kat === selectedCat);

        if (matchSearch && matchCategory) {
            p.classList.remove('d-none');
            visibleCount++;
        } else {
            p.classList.add('d-none');
        }
    });

    const alertNoProduct = document.getElementById('noProductAlert');
    if (visibleCount === 0) {
        alertNoProduct.classList.remove('d-none');
    } else {
        alertNoProduct.classList.add('d-none');
    }
}

// Inisialisasi awal saat halaman dibuka
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

