<?php
session_start();
include '../config/koneksi.php';

// Ambil data barang dari database
$barang = [];
$stmt = oci_parse($conn, "SELECT KODE_BARANG, NAMA_BARANG, HARGA, SATUAN, STOK FROM TBL_BARANG");
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $barang[] = $row;
}
oci_free_statement($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasirApp - Transaksi Barang</title>
    
    <!-- Styles & Fonts -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, #6d28d9 0%, #7c3aed 100%);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 30;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .sidebar-icon {
            width: 20px;
            text-align: center;
            margin-right: 12px;
        }
        .content-wrapper {
            margin-left: 250px;
            transition: all 0.3s;
        }
        .autocomplete-suggestion {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .item-code {
            font-weight: 600;
            color: #6d28d9;
        }
        .item-name {
            color: #4b5563;
        }
        .item-price {
            color: #059669;
            font-weight: 500;
        }
        .ui-autocomplete {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .ui-menu-item {
            border-bottom: 1px solid #f3f4f6;
        }
        .ui-menu-item:last-child {
            border-bottom: none;
        }
        .card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .cart-animation {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); }
            100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
        }
        .pulse-animation {
            animation: pulse 1.5s infinite;
        }
        .header-section {
            background: linear-gradient(135deg, #6d28d9 0%, #4f46e5 100%);
            color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Receipt Modal Styles */
        .receipt-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .receipt-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .receipt {
            font-family: 'Courier New', monospace;
            line-height: 1.4;
            font-size: 12px;
            text-align: center;
            color: #000;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
        }

        .receipt-header h2 {
            font-size: 18px !important;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .receipt-header p {
            font-size: 11px !important;
            margin: 2px 0;
        }

        .receipt-item {
            margin-bottom: 8px;
            text-align: left;
        }

        .receipt-total {
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 8px;
        }

        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt, .receipt * {
                visibility: visible;
            }
            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                font-size: 10px;
            }
            .receipt-modal {
                display: none !important;
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 100%;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .receipt-content {
                margin: 2% auto;
                width: 95%;
                max-height: 95vh;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen"> 
        <!-- Sidebar -->
        <aside class="sidebar text-white p-6">
            <div class="flex items-center space-x-3 mb-10">
                <div class="bg-white p-2 rounded-lg">
                <i class="fas fa-cash-register text-purple-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">KasirApp</h2>
            </div>
            
            <nav class="space-y-2">
                <a href="KasirDashboard.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
                <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
                </a>
                <a href="DataBarang.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
                <span class="sidebar-icon"><i class="fas fa-boxes-stacked"></i></span> Data Barang
                </a>
                <a href="Transaksi.php" class="flex items-center p-3 rounded-xl bg-white/20 text-white font-medium">
                <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Transaksi
                </a>
                <a href="Riwayat.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
                <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat
                </a>
            </nav>

            <!-- Receipt Actions Section - Moved to Sidebar -->
            <div id="receipt-actions" class="mt-8 pt-6 border-t border-white/20" style="display: none;">
                <h4 class="text-sm font-medium text-white/90 mb-3 flex items-center">
                    <i class="fas fa-receipt mr-2"></i> Actions
                </h4>
                <div class="space-y-2">
                    <button type="button" id="btn-print-preview" onclick="showReceiptPreview()" disabled
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg transition-all flex items-center justify-center opacity-50 cursor-not-allowed text-sm">
                        <i class="fas fa-eye mr-2"></i> Preview Struk
                    </button>
                    <button type="button" onclick="resetTransaction()"
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-2.5 px-4 rounded-lg transition-all flex items-center justify-center text-sm">
                        <i class="fas fa-redo-alt mr-2"></i> Reset Transaksi
                    </button>
                </div>
            </div>
        </aside>

        <!-- Toggle Button for Mobile -->
        <button id="sidebarToggle" class="md:hidden fixed top-4 right-4 z-40 bg-purple-600 text-white p-3 rounded-full shadow-lg">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main class="content-wrapper flex-1 p-4 md:p-6 lg:p-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-cash-register text-purple-600 mr-3"></i> Transaksi Barang
                        </h1>
                        <p class="text-gray-500 mt-1">Kelola transaksi penjualan dengan mudah</p>
                    </div>
                </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
            <div id="success-alert" class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-xl flex items-center fade-out shadow-md">
                <div class="bg-green-200 p-2 rounded-full mr-3">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium">Transaksi berhasil!</p>
                    <p><?= $_SESSION['success_msg'] ?></p>
                </div>
                <button class="ml-auto text-green-500 hover:text-green-700" onclick="document.getElementById('success-alert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>

            <?php if (empty($barang)): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-6 rounded-xl mb-8 shadow-md">
                <div class="flex items-center">
                    <div class="bg-yellow-200 p-4 rounded-full mr-5 text-2xl">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium mb-2">Tidak ada barang tersedia</h3>
                        <p class="mb-4">Silahkan tambahkan data barang terlebih dahulu sebelum melakukan transaksi.</p>
                        <a href="DataBarang.php" class="inline-flex bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg items-center transition-all">
                            <i class="fas fa-plus mr-2"></i> Tambah Data Barang
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Form Input -->
                <div class="lg:col-span-5">
                    <div class="card p-6 bg-white">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-box-open text-purple-600 mr-2"></i> 
                            Informasi Barang
                        </h3>
                        
                        <form id="barangForm" class="space-y-4">
                            <div class="mb-4">
                                <label for="kode_barang" class="block text-sm font-medium text-gray-700 mb-1">
                                    Kode Barang / Nama Barang:
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-barcode text-gray-400"></i>
                                    </div>
                                    <input type="text" id="kode_barang" 
                                           class="w-full p-2.5 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                           placeholder="Masukkan kode atau nama barang...">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="nama_barang" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Barang:
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-tag text-gray-400"></i>
                                    </div>
                                    <input type="text" id="nama_barang" 
                                           class="w-full p-2.5 pl-10 bg-gray-50 border border-gray-300 rounded-lg" 
                                           readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="harga" class="block text-sm font-medium text-gray-700 mb-1">
                                        Harga:
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-money-bill-wave text-gray-400"></i>
                                        </div>
                                        <input type="text" id="harga" 
                                               class="w-full p-2.5 pl-10 bg-gray-50 border border-gray-300 rounded-lg" 
                                               readonly>
                                    </div>
                                </div>

                                <div>
                                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">
                                        Jumlah:
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-cubes text-gray-400"></i>
                                        </div>
                                        <input type="number" id="jumlah" min="1" value="1" 
                                               class="w-full p-2.5 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2">
                                <div id="stok-info" class="text-sm flex items-center">
                                    <i class="fas fa-cubes text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">Stok: </span>
                                    <span id="stok-display" class="ml-1 font-medium">-</span>
                                </div>
                                
                                <button type="button" onclick="tambahBarang()" 
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-all flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Tambah ke Keranjang
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tombol Pembayaran untuk Mobile -->
                    <div id="mobilePayment" class="card p-4 mt-4 lg:hidden">
                        <button type="button" onclick="scrollToPayment()" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg flex items-center justify-center transition-all font-medium">
                            <i class="fas fa-money-bill-wave mr-2"></i> Lanjut ke Pembayaran
                        </button>
                    </div>
                </div>

                <!-- Keranjang & Pembayaran -->
                <div class="lg:col-span-7">
                    <div class="card">
                        <!-- Header Keranjang -->
                        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-t-xl px-6 py-4 flex items-center justify-between">
                            <h3 class="font-semibold flex items-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Keranjang Belanja
                            </h3>
                            <div class="flex items-center">
                                <span id="cart-counter" class="bg-white text-purple-700 text-xs px-2 py-1 rounded-full font-medium">
                                    0 item
                                </span>
                            </div>
                        </div>
                        
                        <!-- Empty Cart State -->
                        <div id="empty-cart" class="flex flex-col items-center justify-center py-12">
                            <div class="bg-purple-100 p-4 rounded-full text-purple-500 text-3xl mb-4">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 mb-1">Keranjang Kosong</h3>
                            <p class="text-gray-500 text-center max-w-sm mb-4">
                                Tambahkan barang untuk memulai transaksi penjualan
                            </p>
                            <button onclick="document.getElementById('kode_barang').focus()" 
                                    class="text-purple-600 hover:text-purple-800 flex items-center text-sm font-medium">
                                <i class="fas fa-search mr-1"></i> Cari Barang
                            </button>
                        </div>
                        
                        <!-- Cart Items -->
                        <div id="cart-container" class="hidden">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Produk</th>
                                            <th class="px-4 py-3 text-center">Qty</th>
                                            <th class="px-4 py-3 text-right">Harga</th>
                                            <th class="px-4 py-3 text-right">Subtotal</th>
                                            <th class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="keranjang_body">
                                        <!-- Cart items will be added here -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Cart Summary -->
                            <div id="cart-summary" class="border-t border-gray-200 px-6 py-4">
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-600">Total Item:</span>
                                    <span id="total-items" class="font-medium">0 item</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-600">Total Belanja:</span>
                                    <span id="total_display" class="font-medium text-lg text-purple-700">Rp 0</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Form -->
                        <div id="payment-section" class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-xl">
                            <h3 class="font-medium text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i> Pembayaran
                            </h3>
                            
                            <form method="POST" action="../process/proses_transaksi.php" id="transaksiForm" onsubmit="return handleSubmit()">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="uang_bayar" class="block text-sm font-medium text-gray-700 mb-1">
                                            Uang Bayar:
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-money-bill-wave text-gray-400"></i>
                                            </div>
                                            <input type="number" id="uang_bayar" name="uang_bayar"
                                                   class="w-full p-2.5 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                                   oninput="hitungKembalian()" placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="kembalian" class="block text-sm font-medium text-gray-700 mb-1">
                                            Kembalian:
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-hand-holding-dollar text-gray-400"></i>
                                            </div>
                                            <input type="text" id="kembalian" name="kembalian" 
                                                   class="w-full p-2.5 pl-10 bg-gray-50 border border-gray-300 rounded-lg" 
                                                   readonly placeholder="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Payment Buttons -->
                                <div class="mt-4 grid grid-cols-4 gap-2">
                                    <button type="button" onclick="quickPayment(50000)" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-2 py-1 rounded border border-blue-200 text-sm">
                                        Rp 50.000
                                    </button>
                                    <button type="button" onclick="quickPayment(100000)" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-2 py-1 rounded border border-blue-200 text-sm">
                                        Rp 100.000
                                    </button>
                                    <button type="button" onclick="payExact()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-2 py-1 rounded border border-blue-200 text-sm">
                                        Uang Pas
                                    </button>
                                    <button type="button" onclick="clearPayment()" class="bg-red-50 hover:bg-red-100 text-red-700 px-2 py-1 rounded border border-red-200 text-sm">
                                        Reset
                                    </button>
                                </div>

                                <input type="hidden" name="keranjang" id="keranjang_input">
                                <input type="hidden" name="total_bayar" id="total_bayar">
                                <input type="hidden" name="total" id="total_input">

                                <div class="mt-6">
                                    <button type="submit" id="btn-submit" disabled 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg transition-all flex items-center justify-center opacity-50 cursor-not-allowed">
                                        <i class="fas fa-check-circle mr-2"></i> Proses Transaksi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <footer class="text-center text-gray-500 text-sm mt-12 mb-4">
                <p>&copy; <?= date('Y') ?> KasirApp - Sistem Informasi Kasir</p>
            </footer>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal">
        <div class="receipt-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Preview Struk</h3>
                <button onclick="closeReceiptModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="receiptPreview" class="receipt border p-4 bg-white">
                <!-- Receipt content will be generated here -->
            </div>
            
            <div class="flex gap-3 mt-4">
                <button onclick="printReceipt()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg">
                    <i class="fas fa-print mr-2"></i> Cetak Struk
                </button>
                <button onclick="closeReceiptModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg">
                    <i class="fas fa-times mr-2"></i> Tutup
                </button>
            </div>
        </div>
    </div>

    // JavaScript for functionality
<script>
    // Inisialisasi data
    let keranjang = JSON.parse(localStorage.getItem('keranjang')) || [];
    const barang = <?php echo json_encode($barang); ?>;
    let selectedBarang = null;
    let lastTransactionData = null;

    // Format number to currency (Rp)
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(angka).replace('IDR', 'Rp');
    }

    // Format date and time
    function formatDateTime(date = new Date()) {
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        };
        return date.toLocaleString('id-ID', options);
    }

    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });

    // Autocomplete kode barang
    $("#kode_barang").autocomplete({
        delay: 200,
        minLength: 1,
        source: function(request, response) {
            const results = barang.filter(b =>
                b.KODE_BARANG.toUpperCase().includes(request.term.toUpperCase()) ||
                b.NAMA_BARANG.toLowerCase().includes(request.term.toLowerCase())
            );
            
            response(results.map(b => ({
                label: `${b.KODE_BARANG} - ${b.NAMA_BARANG}`,
                value: b.KODE_BARANG,
                item: b
            })));
        },
        select: function(event, ui) {
            selectedBarang = ui.item.item;
            $('#kode_barang').val(ui.item.value);
            $('#nama_barang').val(selectedBarang.NAMA_BARANG);
            $('#harga').val(formatRupiah(selectedBarang.HARGA));
            
            const stokDisp = $('#stok-display');
            stokDisp.text(selectedBarang.STOK + ' ' + selectedBarang.SATUAN);
            
            if (parseInt(selectedBarang.STOK) <= 5) {
                stokDisp.removeClass('text-green-600 text-blue-600').addClass('text-orange-500 font-medium');
            } else if (parseInt(selectedBarang.STOK) > 20) {
                stokDisp.removeClass('text-orange-500 text-blue-600').addClass('text-green-600 font-medium');
            } else {
                stokDisp.removeClass('text-orange-500 text-green-600').addClass('text-blue-600 font-medium');
            }
            
            setTimeout(function() {
                $("#jumlah").focus().select();
            }, 100);
            return false;
        },
        response: function(event, ui) {
            if (ui.content.length === 0) {
                $('#stok-display').text('-');
                $('#stok-display').removeClass('text-orange-500 text-green-600 text-blue-600');
                selectedBarang = null;
            }
        }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
        const stokValue = parseInt(item.item.STOK);
        let stokClass = 'text-blue-600';
        
        if (stokValue <= 5) {
            stokClass = 'text-orange-500 font-medium';
        } else if (stokValue > 20) {
            stokClass = 'text-green-600';
        }
        
        return $("<li>")
            .append(`<div class="p-3 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="item-code">${item.item.KODE_BARANG}</span> - 
                        <span class="item-name">${item.item.NAMA_BARANG}</span>
                    </div>
                    <span class="item-price">${formatRupiah(item.item.HARGA)}</span>
                </div>
                <div class="text-xs mt-1">
                    <span class="${stokClass}">Stok: ${item.item.STOK} ${item.item.SATUAN}</span>
                </div>
            </div>`)
            .appendTo(ul);
    };

    // Initialize autocomplete
    $(document).ready(function() {
        // Load cart from localStorage
        updateKeranjangDisplay();

        // Auto-hide success alert
        setTimeout(function() {
            const alert = document.getElementById('success-alert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);

        // Mobile sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    });

    // Fill barang data when selected
    function fillBarangData(barang) {
        document.getElementById('nama_barang').value = barang.NAMA_BARANG;
        document.getElementById('harga').value = formatRupiah(barang.HARGA);
        document.getElementById('stok-display').textContent = barang.STOK + ' ' + barang.SATUAN;
        document.getElementById('jumlah').max = barang.STOK;
        document.getElementById('jumlah').value = 1;
        document.getElementById('jumlah').focus();
    }

    // Add item to cart
    function tambahBarang() {
        if (!selectedBarang) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Barang',
                text: 'Silahkan pilih barang terlebih dahulu!',
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        const jumlah = parseInt(document.getElementById('jumlah').value);
        if (jumlah <= 0 || jumlah > selectedBarang.STOK) {
            Swal.fire({
                icon: 'error',
                title: 'Jumlah Tidak Valid',
                text: `Jumlah harus antara 1 sampai ${selectedBarang.STOK}!`,
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        // Check if item already exists in cart
        const existingIndex = keranjang.findIndex(item => item.kode === selectedBarang.KODE_BARANG);
        
        if (existingIndex !== -1) {
            const newQty = keranjang[existingIndex].jumlah + jumlah;
            if (newQty > selectedBarang.STOK) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Tidak Cukup',
                    text: `Total quantity akan melebihi stok yang tersedia (${selectedBarang.STOK})!`,
                    confirmButtonColor: '#7c3aed'
                });
                return;
            }
            keranjang[existingIndex].jumlah = newQty;
            keranjang[existingIndex].subtotal = newQty * selectedBarang.HARGA;
        } else {
            keranjang.push({
                kode: selectedBarang.KODE_BARANG,
                nama: selectedBarang.NAMA_BARANG,
                harga: selectedBarang.HARGA,
                jumlah: jumlah,
                satuan: selectedBarang.SATUAN,
                subtotal: jumlah * selectedBarang.HARGA
            });
        }

        // Save to localStorage
        localStorage.setItem('keranjang', JSON.stringify(keranjang));
        
        // Update display
        updateKeranjangDisplay();
        resetForm();
        
        // Show success animation
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Barang ditambahkan ke keranjang',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // Update cart display
    function updateKeranjangDisplay() {
        const emptyCart = document.getElementById('empty-cart');
        const cartContainer = document.getElementById('cart-container');
        const cartCounter = document.getElementById('cart-counter');
        const totalItems = document.getElementById('total-items');
        const totalDisplay = document.getElementById('total_display');
        const keranjangBody = document.getElementById('keranjang_body');
        const receiptActions = document.getElementById('receipt-actions');
        const btnSubmit = document.getElementById('btn-submit');
        const btnPrintPreview = document.getElementById('btn-print-preview');

        if (keranjang.length === 0) {
            emptyCart.style.display = 'flex';
            cartContainer.classList.add('hidden');
            receiptActions.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            btnPrintPreview.disabled = true;
            btnPrintPreview.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            emptyCart.style.display = 'none';
            cartContainer.classList.remove('hidden');
            receiptActions.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            btnPrintPreview.disabled = false;
            btnPrintPreview.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        // Update counter
        const totalItemCount = keranjang.reduce((sum, item) => sum + item.jumlah, 0);
        cartCounter.textContent = `${totalItemCount} item`;
        totalItems.textContent = `${totalItemCount} item`;

        // Calculate total
        const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
        totalDisplay.textContent = formatRupiah(total);

        // Update cart body
        keranjangBody.innerHTML = '';
        keranjang.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100 cart-animation';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <div>
                        <div class="font-medium text-gray-800 text-sm">${item.nama}</div>
                        <div class="text-xs text-gray-500">${item.kode}</div>
                        <div class="text-xs text-gray-500">${formatRupiah(item.harga)}/${item.satuan}</div>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center space-x-1">
                        <button onclick="updateJumlah(${index}, -1)" class="text-red-600 hover:text-red-800 p-1">
                            <i class="fas fa-minus-circle"></i>
                        </button>
                        <span class="mx-2 font-medium">${item.jumlah}</span>
                        <button onclick="updateJumlah(${index}, 1)" class="text-green-600 hover:text-green-800 p-1">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right text-sm">${formatRupiah(item.harga)}</td>
                <td class="px-4 py-3 text-right font-medium">${formatRupiah(item.subtotal)}</td>
                <td class="px-4 py-3 text-center">
                    <button onclick="hapusBarang(${index})" class="text-red-600 hover:text-red-800 p-1">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            keranjangBody.appendChild(row);
        });

        // Update hidden inputs
        document.getElementById('keranjang_input').value = JSON.stringify(keranjang);
        document.getElementById('total_input').value = total;
        document.getElementById('total_bayar').value = total;

        // Reset payment calculation
        hitungKembalian();
    }

    // Update quantity
    function updateJumlah(index, change) {
        const newJumlah = keranjang[index].jumlah + change;
        
        if (newJumlah <= 0) {
            hapusBarang(index);
            return;
        }

        // Find original item to check stock
        const originalItem = barang.find(item => item.KODE_BARANG === keranjang[index].kode);
        if (newJumlah > originalItem.STOK) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Tidak Cukup',
                text: `Stok maksimal: ${originalItem.STOK}`,
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        keranjang[index].jumlah = newJumlah;
        keranjang[index].subtotal = newJumlah * keranjang[index].harga;
        
        localStorage.setItem('keranjang', JSON.stringify(keranjang));
        updateKeranjangDisplay();
    }

    // Remove item from cart
    function hapusBarang(index) {
        Swal.fire({
            title: 'Hapus Barang?',
            text: 'Apakah Anda yakin ingin menghapus barang ini dari keranjang?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                keranjang.splice(index, 1);
                localStorage.setItem('keranjang', JSON.stringify(keranjang));
                updateKeranjangDisplay();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: 'Barang telah dihapus dari keranjang.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    }

    // Reset form
    function resetForm() {
        document.getElementById('barangForm').reset();
        document.getElementById('nama_barang').value = '';
        document.getElementById('harga').value = '';
        document.getElementById('stok-display').textContent = '-';
        selectedBarang = null;
        document.getElementById('kode_barang').focus();
    }

    // Reset transaction
    function resetTransaction() {
        if (keranjang.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Keranjang Kosong',
                text: 'Tidak ada transaksi untuk direset!',
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        Swal.fire({
            title: 'Reset Transaksi?',
            text: 'Semua item dalam keranjang akan dihapus!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                keranjang = [];
                localStorage.removeItem('keranjang');
                updateKeranjangDisplay();
                resetForm();
                clearPayment();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Transaksi Direset!',
                    text: 'Keranjang telah dikosongkan.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    }

    // Payment calculations
    function hitungKembalian() {
        const uangBayar = parseFloat(document.getElementById('uang_bayar').value) || 0;
        const total = parseFloat(document.getElementById('total_bayar').value) || 0;
        const kembalian = uangBayar - total;

        const kembalianInput = document.getElementById('kembalian');
        const btnSubmit = document.getElementById('btn-submit');

        if (uangBayar >= total && keranjang.length > 0) {
            kembalianInput.value = formatRupiah(kembalian);
            kembalianInput.classList.remove('border-red-300', 'bg-red-50');
            kembalianInput.classList.add('border-gray-300');
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            kembalianInput.value = formatRupiah(0);
            kembalianInput.classList.add('border-red-300', 'bg-red-50');
            kembalianInput.classList.remove('border-gray-300');
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Quick payment functions
    function quickPayment(amount) {
        document.getElementById('uang_bayar').value = amount;
        hitungKembalian();
    }

    function payExact() {
        const total = parseFloat(document.getElementById('total_bayar').value) || 0;
        document.getElementById('uang_bayar').value = total;
        hitungKembalian();
    }

    function clearPayment() {
        document.getElementById('uang_bayar').value = '';
        document.getElementById('kembalian').value = '';
        hitungKembalian();
    }

    // Mobile scroll to payment
    function scrollToPayment() {
        document.getElementById('payment-section').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }

    // Handle form submission - FIXED VERSION
    function handleSubmit(event) {
        // Prevent default form submission
        if (event) {
            event.preventDefault();
        }

        // Validation
        if (keranjang.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Keranjang Kosong',
                text: 'Tidak ada barang dalam keranjang!',
                confirmButtonColor: '#7c3aed'
            });
            return false;
        }

        const uangBayar = parseFloat(document.getElementById('uang_bayar').value) || 0;
        const total = parseFloat(document.getElementById('total_bayar').value) || 0;
        
        if (uangBayar < total) {
            Swal.fire({
                icon: 'error',
                title: 'Uang Tidak Cukup',
                text: 'Uang bayar kurang dari total belanja!',
                confirmButtonColor: '#7c3aed'
            });
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Memproses Transaksi...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Prepare transaction data
        const transactionData = {
            keranjang: JSON.stringify(keranjang),
            total: total,
            uang_bayar: uangBayar,
            kembalian: uangBayar - total
        };

        // Send to server using fetch API
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(transactionData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            // Check if response contains success indicator
            if (data.includes('success') || data.includes('berhasil')) {
                // Store transaction data for receipt
                lastTransactionData = {
                    items: [...keranjang],
                    total: total,
                    uangBayar: uangBayar,
                    kembalian: uangBayar - total,
                    tanggal: new Date()
                };

                onTransactionSuccess();
            } else {
                throw new Error('Transaction failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memproses transaksi',
                confirmButtonColor: '#7c3aed'
            });
        });

        return false;
    }

    // Alternative form submission using traditional method
    function submitTransactionForm() {
        // Update hidden inputs with current data
        document.getElementById('keranjang_input').value = JSON.stringify(keranjang);
        document.getElementById('total_input').value = parseFloat(document.getElementById('total_bayar').value) || 0;
        document.getElementById('uang_bayar_input').value = parseFloat(document.getElementById('uang_bayar').value) || 0;
        document.getElementById('kembalian_input').value = (parseFloat(document.getElementById('uang_bayar').value) || 0) - (parseFloat(document.getElementById('total_bayar').value) || 0);

        // Store transaction data for receipt
        lastTransactionData = {
            items: [...keranjang],
            total: parseFloat(document.getElementById('total_bayar').value) || 0,
            uangBayar: parseFloat(document.getElementById('uang_bayar').value) || 0,
            kembalian: (parseFloat(document.getElementById('uang_bayar').value) || 0) - (parseFloat(document.getElementById('total_bayar').value) || 0),
            tanggal: new Date()
        };

        // Submit the form
        document.getElementById('transactionForm').submit();
    }

    // Receipt functions
    function showReceiptPreview() {
        if (!lastTransactionData && keranjang.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Data',
                text: 'Tidak ada data transaksi untuk ditampilkan!',
                confirmButtonColor: '#7c3aed'
            });
            return;
        }

        // Use current cart data if no transaction data exists
        const data = lastTransactionData || {
            items: [...keranjang],
            total: parseFloat(document.getElementById('total_bayar').value) || 0,
            uangBayar: parseFloat(document.getElementById('uang_bayar').value) || 0,
            kembalian: (parseFloat(document.getElementById('uang_bayar').value) || 0) - (parseFloat(document.getElementById('total_bayar').value) || 0),
            tanggal: new Date()
        };
        
        generateReceiptPreview(data);
        document.getElementById('receiptModal').style.display = 'block';
    }

    function generateReceiptPreview(data) {
        const receiptPreview = document.getElementById('receiptPreview');
        const currentDate = formatDateTime(data.tanggal);
        
        let receiptHTML = `
            <div class="receipt-header text-center mb-4">
                <h2 class="text-lg font-bold">KASIR APP</h2>
                <p class="text-xs">Sistem Informasi Kasir</p>
                <p class="text-xs">Jl. Contoh No. 123, Kota</p>
                <p class="text-xs">Telp: (021) 12345678</p>
                <div class="border-t border-dashed border-gray-400 my-2"></div>
                <p class="text-xs">${currentDate}</p>
                <div class="border-t border-dashed border-gray-400 my-2"></div>
            </div>
            
            <div class="receipt-items text-left text-xs mb-4">
        `;
        
        data.items.forEach(item => {
            const itemTotal = item.jumlah * item.harga;
            receiptHTML += `
                <div class="receipt-item">
                    <div class="flex justify-between">
                        <span class="font-medium">${item.nama}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>${item.jumlah} x ${formatRupiah(item.harga)}</span>
                        <span>${formatRupiah(itemTotal)}</span>
                    </div>
                </div>
            `;
        });
        
        receiptHTML += `
            </div>
            
            <div class="receipt-total text-xs">
                <div class="border-t border-dashed border-gray-400 pt-2">
                    <div class="flex justify-between font-bold">
                        <span>TOTAL:</span>
                        <span>${formatRupiah(data.total)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Bayar:</span>
                        <span>${formatRupiah(data.uangBayar)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kembali:</span>
                        <span>${formatRupiah(data.kembalian)}</span>
                    </div>
                </div>
                <div class="border-t border-dashed border-gray-400 mt-2 pt-2 text-center">
                    <p>Terima kasih atas kunjungan Anda!</p>
                    <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
                </div>
            </div>
        `;
        
        receiptPreview.innerHTML = receiptHTML;
    }

    function printReceipt() {
        window.print();
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('receiptModal');
        if (event.target === modal) {
            closeReceiptModal();
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateKeranjangDisplay();
        
        // Check if there's success message and show receipt preview
        <?php if (isset($_SESSION['success_msg'])): ?>
        setTimeout(() => {
            if (lastTransactionData) {
                showReceiptPreview();
            }
        }, 2000);
        <?php endif; ?>
    });

    // Success callback after transaction
    function onTransactionSuccess() {
        // Clear cart
        keranjang = [];
        localStorage.removeItem('keranjang');
        updateKeranjangDisplay();
        resetForm();
        clearPayment();
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Transaksi Berhasil!',
            text: 'Transaksi telah berhasil diproses.',
            confirmButtonColor: '#7c3aed'
        }).then(() => {
            // Show receipt preview after success message
            if (lastTransactionData) {
                showReceiptPreview();
            }
        });
    }

    // Event listeners untuk form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission with button click
        const submitBtn = document.getElementById('btn-submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (handleSubmit(e) !== false) {
                    submitTransactionForm();
                }
            });
        }

        // Handle form submission with enter key
        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (handleSubmit(e) !== false) {
                    submitTransactionForm();
                }
            });
        }

        // Handle payment input events
        const uangBayarInput = document.getElementById('uang_bayar');
        if (uangBayarInput) {
            uangBayarInput.addEventListener('input', hitungKembalian);
            uangBayarInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    const submitBtn = document.getElementById('btn-submit');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.click();
                    }
                }
            });
        }
    });
</script>
</body>
</html>