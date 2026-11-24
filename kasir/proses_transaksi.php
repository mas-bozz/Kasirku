<?php
/**
 * Proses Penyimpanan Transaksi Penjualan Kasir - KasirKu
 * Menggunakan PDO Database Transaction untuk Menjaga Konsistensi Stok
 */
require_once __DIR__ . '/../includes/kasir_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('kasir/transaksi.php'));
    exit;
}

// 1. Verifikasi CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Token keamanan sesi kedaluwarsa. Silakan ulangi transaksi.');
    header('Location: ' . base_url('kasir/transaksi.php'));
    exit;
}

// 2. Ambil dan validasi data keranjang
$rawCart = $_POST['cart_items'] ?? '[]';
$cart = json_decode($rawCart, true);

if (!is_array($cart) || empty($cart)) {
    set_flash('danger', 'Keranjang belanja masih kosong!');
    header('Location: ' . base_url('kasir/transaksi.php'));
    exit;
}

$diskonInput       = max(0, (float)($_POST['diskon'] ?? 0));
$pembayaranInput   = (float)($_POST['pembayaran'] ?? 0);
$metodePembayaran  = trim($_POST['metode_pembayaran'] ?? 'Tunai');
$userId            = (int)$_SESSION['user_id'];

if (!in_array($metodePembayaran, ['Tunai', 'Transfer', 'QRIS'])) {
    $metodePembayaran = 'Tunai';
}

try {
    // Mulai Database Transaction PDO
    $pdo->beginTransaction();

    $verifiedSubtotal = 0;
    $itemsToProcess = [];

    // 3. Validasi harga & ketersediaan stok riil dari database (mencegah manipulasi client-side)
    foreach ($cart as $item) {
        $idProduk = (int)($item['id'] ?? 0);
        $qtyBeli  = (int)($item['qty'] ?? 0);

        if ($idProduk <= 0 || $qtyBeli <= 0) {
            throw new Exception("Data barang tidak valid atau jumlah beli bernilai 0.");
        }

        // Kunci baris produk dengan FOR UPDATE agar stok tidak balapan (race condition)
        $stmtProd = $pdo->prepare("SELECT id_produk, nama_produk, harga_jual, stok, satuan FROM produk WHERE id_produk = :id FOR UPDATE");
        $stmtProd->execute(['id' => $idProduk]);
        $dbProd = $stmtProd->fetch();

        if (!$dbProd) {
            throw new Exception("Produk dengan ID #$idProduk tidak ditemukan di database.");
        }

        if ($qtyBeli > $dbProd['stok']) {
            throw new Exception("Stok untuk produk \"" . $dbProd['nama_produk'] . "\" tidak mencukupi! Sisa stok saat ini: " . $dbProd['stok'] . " " . $dbProd['satuan'] . ".");
        }

        $hargaJual      = (float)$dbProd['harga_jual'];
        $subtotalBarang = $hargaJual * $qtyBeli;
        $verifiedSubtotal += $subtotalBarang;

        $itemsToProcess[] = [
            'id_produk'    => $idProduk,
            'nama_produk'  => $dbProd['nama_produk'],
            'harga'        => $hargaJual,
            'jumlah'       => $qtyBeli,
            'subtotal'     => $subtotalBarang,
            'stok_sebelum' => (int)$dbProd['stok'],
            'stok_sesudah' => (int)$dbProd['stok'] - $qtyBeli,
        ];
    }

    // 4. Kalkulasi Total & Kembalian
    $grandTotal = max(0, $verifiedSubtotal - $diskonInput);

    if ($pembayaranInput < $grandTotal) {
        throw new Exception("Pembayaran tidak mencukupi! Total yang harus dibayar adalah " . format_rupiah($grandTotal) . ", uang yang diterima: " . format_rupiah($pembayaranInput) . ".");
    }

    $kembalian = $pembayaranInput - $grandTotal;

    // 5. Generate Nomor Transaksi Unik Otomatis (Format: TRX-YYYYMMDD-0001)
    $today = date('Ymd');
    $prefix = "TRX-$today-";

    $stmtLast = $pdo->prepare("SELECT nomor_transaksi FROM penjualans WHERE nomor_transaksi LIKE :prefix ORDER BY id_penjualan DESC LIMIT 1 FOR UPDATE");
    $stmtLast->execute(['prefix' => "$prefix%"]);
    $lastTrx = $stmtLast->fetchColumn();

    if ($lastTrx) {
        $lastNumber = (int)substr($lastTrx, -4);
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '0001';
    }
    $nomorTransaksi = $prefix . $newNumber;

    // 6. Simpan Header Transaksi ke tabel penjualans
    $stmtPenjualan = $pdo->prepare("
        INSERT INTO penjualans 
        (nomor_transaksi, id_user, tanggal, subtotal, diskon, total, pembayaran, kembalian, metode_pembayaran) 
        VALUES 
        (:no_trx, :uid, NOW(), :subtotal, :diskon, :total, :bayar, :kembali, :metode)
    ");
    $stmtPenjualan->execute([
        'no_trx'   => $nomorTransaksi,
        'uid'      => $userId,
        'subtotal' => $verifiedSubtotal,
        'diskon'   => $diskonInput,
        'total'    => $grandTotal,
        'bayar'    => $pembayaranInput,
        'kembali'  => $kembalian,
        'metode'   => $metodePembayaran
    ]);

    $idPenjualan = (int)$pdo->lastInsertId();

    // 7. Simpan Detail Transaksi, Kurangi Stok, dan Catat Kartu Stok (stock_movements)
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_penjualan 
        (id_penjualan, id_produk, harga, jumlah, subtotal) 
        VALUES 
        (:id_penjualan, :id_produk, :harga, :jumlah, :subtotal)
    ");

    $stmtUpdateStok = $pdo->prepare("
        UPDATE produk SET stok = :stok_baru WHERE id_produk = :id_produk
    ");

    $stmtMovement = $pdo->prepare("
        INSERT INTO stock_movements 
        (id_produk, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan, id_user) 
        VALUES 
        (:id_produk, 'keluar', :jumlah, :stok_sebelum, :stok_sesudah, :ket, :id_user)
    ");

    foreach ($itemsToProcess as $item) {
        // Simpan ke detail_penjualan
        $stmtDetail->execute([
            'id_penjualan' => $idPenjualan,
            'id_produk'    => $item['id_produk'],
            'harga'        => $item['harga'],
            'jumlah'       => $item['jumlah'],
            'subtotal'     => $item['subtotal']
        ]);

        // Kurangi stok di tabel produk
        $stmtUpdateStok->execute([
            'stok_baru' => $item['stok_sesudah'],
            'id_produk' => $item['id_produk']
        ]);

        // Catat ke kartu stok (stock_movements)
        $stmtMovement->execute([
            'id_produk'    => $item['id_produk'],
            'jumlah'       => $item['jumlah'],
            'stok_sebelum' => $item['stok_sebelum'],
            'stok_sesudah' => $item['stok_sesudah'],
            'ket'          => "Penjualan Transaksi " . $nomorTransaksi,
            'id_user'      => $userId
        ]);
    }

    // Komit transaksi database jika semua tahap berhasil tanpa kendala
    $pdo->commit();

    set_flash('success', "Transaksi $nomorTransaksi berhasil diproses!");
    header('Location: ' . base_url('kasir/struk.php?id=' . $idPenjualan));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Gagal memproses transaksi: ' . $e->getMessage());
    header('Location: ' . base_url('kasir/transaksi.php'));
    exit;
}

