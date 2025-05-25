<?php
include '../config/koneksi.php';

// Cek apakah ada parameter id_transaksi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID Transaksi tidak ditemukan!'); window.close();</script>";
    exit;
}

$id_transaksi = $_GET['id'];

// Ambil data transaksi
$query_transaksi = oci_parse($conn, "
    SELECT t.ID_TRANSAKSI, 
           TO_CHAR(t.TANGGAL, 'DD/MM/YYYY HH24:MI:SS') as TANGGAL, 
           t.TOTAL, 
           t.TOTAL_BAYAR, 
           t.KASIR, 
           t.KEMBALIAN
    FROM TBL_TRANSAKSI t 
    WHERE t.ID_TRANSAKSI = :id_transaksi
");
oci_bind_by_name($query_transaksi, ':id_transaksi', $id_transaksi);
oci_execute($query_transaksi);
$transaksi = oci_fetch_assoc($query_transaksi);

if (!$transaksi) {
    echo "<script>alert('Transaksi tidak ditemukan!'); window.close();</script>";
    exit;
}

// Ambil detail transaksi
$query_detail = oci_parse($conn, "
    SELECT d.KODE_BARANG, b.NAMA_BARANG, d.JUMLAH, b.SATUAN, b.HARGA, d.SUBTOTAL
    FROM TBL_DETAIL_TRANSAKSI d
    JOIN TBL_BARANG b ON d.KODE_BARANG = b.KODE_BARANG
    WHERE d.ID_TRANSAKSI = :id_transaksi
");
oci_bind_by_name($query_detail, ':id_transaksi', $id_transaksi);
oci_execute($query_detail);

$detail_transaksi = [];
while ($detail = oci_fetch_assoc($query_detail)) {
    $detail_transaksi[] = $detail;
}

oci_free_statement($query_transaksi);
oci_free_statement($query_detail);
oci_close($conn);

// Fungsi format rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Informasi toko (bisa diubah sesuai kebutuhan)
$nama_toko = "KasirApp Store";
$alamat_toko = "Jl. Mendalan No. 123, Lamongan";
$telp_toko = "Telp: (021) 12345678";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #<?= $transaksi['ID_TRANSAKSI'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .struk-container {
            width: 300px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #333;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 12px;
            margin: 2px 0;
        }

        .transaction-info {
            margin-bottom: 15px;
            font-size: 12px;
        }

        .transaction-info div {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .separator {
            border-bottom: 1px dashed #333;
            margin: 10px 0;
        }

        .items {
            margin-bottom: 15px;
        }

        .item {
            margin: 8px 0;
            font-size: 12px;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .item-detail {
            display: flex;
            justify-content: space-between;
            margin-left: 10px;
        }

        .totals {
            margin-top: 10px;
            font-size: 12px;
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .total-final {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #333;
            font-size: 11px;
        }

        .no-print {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            background-color: #6d28d9;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #5b21b6;
        }

        .btn-secondary {
            background-color: #6b7280;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .struk-container {
                width: auto;
                max-width: 300px;
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 10px;
            }

            .no-print {
                display: none;
            }
        }

        @page {
            size: 80mm auto;
            margin: 5mm;
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <!-- Header Toko -->
        <div class="header">
            <h1><?= $nama_toko ?></h1>
            <p><?= $alamat_toko ?></p>
            <p><?= $telp_toko ?></p>
        </div>

        <!-- Info Transaksi -->
        <div class="transaction-info">
            <div>
                <span>No. Transaksi</span>
                <span><?= $transaksi['ID_TRANSAKSI'] ?></span>
            </div>
            <div>
                <span>Tanggal</span>
                <span><?= $transaksi['TANGGAL'] ?></span>  <!-- Hapus date() dan strtotime() -->
            </div>
            <div>
                <span>Kasir</span>
                <span><?= $transaksi['KASIR'] ?></span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Detail Barang -->
        <div class="items">
            <?php foreach ($detail_transaksi as $item): ?>
                <div class="item">
                    <div class="item-name"><?= $item['NAMA_BARANG'] ?></div>
                    <div class="item-detail">
                        <span><?= $item['JUMLAH'] ?> <?= $item['SATUAN'] ?> x <?= format_rupiah($item['HARGA']) ?></span>
                        <span><?= format_rupiah($item['SUBTOTAL']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="separator"></div>

        <!-- Total -->
        <div class="totals">
            <div>
                <span>Total Item</span>
                <span><?= count($detail_transaksi) ?> item</span>
            </div>
            <div class="total-final">
                <span>TOTAL</span>
                <span><?= format_rupiah($transaksi['TOTAL']) ?></span>
            </div>
            <div>
                <span>Bayar</span>
                <span><?= format_rupiah($transaksi['TOTAL_BAYAR']) ?></span>
            </div>
            <div>
                <span>Kembalian</span>
                <span><?= format_rupiah($transaksi['KEMBALIAN']) ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>*** TERIMA KASIH ***</p>
            <p>Barang yang sudah dibeli</p>
            <p>tidak dapat dikembalikan</p>
            <p>Simpan struk ini sebagai</p>
            <p>bukti pembelian yang sah</p>
        </div>
    </div>

    <!-- Tombol Aksi (tidak ikut tercetak) -->
    <div class="no-print">
        <button class="btn" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Struk
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> Tutup
        </button>
        <button class="btn btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
    </div>

    <script>
        // Auto print jika parameter print=1
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }

        // Event listener untuk Ctrl+P
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Event listener untuk ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>