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

        
        /* Success notification with struk option */
        .success-with-struk {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            transform: translateX(400px);
            transition: all 0.3s ease;
        }
        
        .success-with-struk.show {
            transform: translateX(0);

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

            .success-with-struk {
                top: 10px;
                right: 10px;
                left: 10px;
                transform: translateY(-300px);
            }
            .success-with-struk.show {
                transform: translateY(0);

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

                    
                    <!-- <div class="flex space-x-2">
                        <button type="button" id="resetButton" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-redo-alt mr-1"></i> Reset
                        </button>
                        <?php if (!empty($barang)): ?>
                        <button type="button" id="scanButton" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-barcode mr-1"></i> Scan Barcode
                        </button>
                        <?php endif; ?>
                    </div> -->
                </div>

            <!-- Success Alert with Print Option -->
            <?php if (isset($_SESSION['success_msg']) && isset($_SESSION['last_transaction_id'])): ?>
            <div id="success-with-struk-notification" class="success-with-struk">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-white bg-opacity-20 p-2 rounded-full mr-3">
                                <i class="fas fa-check text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Transaksi Berhasil!</h4>
                                <p class="text-sm opacity-90"><?= $_SESSION['success_msg'] ?></p>
                            </div>
                        </div>
                        <button onclick="closeStrukNotification()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-full transition-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="bg-white p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-50 p-3 rounded-lg mr-3">
                            <i class="fas fa-receipt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Cetak Struk Transaksi?</h5>
                            <p class="text-sm text-gray-600">Apakah Anda ingin mencetak struk untuk pelanggan?</p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="printTransactionStruk('<?= $_SESSION['last_transaction_id'] ?>')" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-all">
                            <i class="fas fa-print mr-2"></i> Cetak Struk
                        </button>
                        <button onclick="closeStrukNotification()" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex items-center justify-center transition-all">
                            <i class="fas fa-times mr-2"></i> Tidak, Terima Kasih
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Regular Success Alert -->
            <?php if (isset($_SESSION['success_msg']) && !isset($_SESSION['last_transaction_id'])): ?>

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

                        
                        <!-- Quick Add Popular Items -->
                        <!-- <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Barang Populer:</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <?php
                                // Just show up to 6 items as popular
                                $popular_items = array_slice($barang, 0, 6);
                                foreach ($popular_items as $item): 
                                ?>
                                <button onclick="quickAddItem('<?= $item['KODE_BARANG'] ?>')" 
                                        class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-2 rounded-lg transition-colors text-left truncate">
                                    <?= $item['NAMA_BARANG'] ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div> -->

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


    <!-- JavaScript for functionality -->
    <script>
        // Inisialisasi data
        let keranjang = JSON.parse(localStorage.getItem('keranjang')) || [];
        const barang = <?php echo json_encode($barang); ?>;
        let selectedBarang = null;

        // Format number to currency (Rp)
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(angka).replace('IDR', 'Rp');
        }

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Struk Notification Functions
        function showStrukNotification() {
            const notification = document.getElementById('success-with-struk-notification');
            if (notification) {
                setTimeout(() => {
                    notification.classList.add('show');
                }, 500);
            }
        }

        function closeStrukNotification() {
            const notification = document.getElementById('success-with-struk-notification');
            if (notification) {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }

        // Function to print transaction struk
        function printTransactionStruk(id_transaksi) {
            const strukWindow = window.open(`struk.php?id=${id_transaksi}`, 'strukWindow', 'width=400,height=600,scrollbars=yes,resizable=yes');
            
            if (strukWindow) {
                strukWindow.focus();
                closeStrukNotification();
                
                // Show success toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Struk dibuka di tab baru'
                });
            } else {
                Swal.fire({
                    title: 'Popup Diblokir',
                    text: 'Mohon izinkan popup untuk mencetak struk',
                    icon: 'warning',
                    confirmButtonColor: '#6d28d9'
                });
            }
        }

        // Autocomplete kode barang
        $("#kode_barang").autocomplete({
            delay: 300,
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
        
        // Quick add popular item
        function quickAddItem(kode) {
            const barangData = barang.find(b => b.KODE_BARANG === kode);
            if (barangData) {
                selectedBarang = barangData;
                $('#kode_barang').val(barangData.KODE_BARANG);
                $('#nama_barang').val(barangData.NAMA_BARANG);
                $('#harga').val(formatRupiah(barangData.HARGA));
                $('#stok-display').text(barangData.STOK + ' ' + barangData.SATUAN);
                
                if (parseInt(barangData.STOK) <= 5) {
                    $('#stok-display').removeClass('text-green-600').addClass('text-orange-500 font-medium');
                } else {
                    $('#stok-display').removeClass('text-orange-500 font-medium').addClass('text-green-600');
                }
                
                $("#jumlah").focus().select();
            }
        }
        
        // Add item to cart
        function tambahBarang() {
            const kode = $('#kode_barang').val();
            const nama = $('#nama_barang').val();
            const hargaStr = $('#harga').val().replace(/[^\d]/g, '');
            const harga = parseInt(hargaStr);
            const jumlah = parseInt($('#jumlah').val());

            if (!kode || !nama || isNaN(harga) || isNaN(jumlah) || jumlah <= 0) {
                Swal.fire({
                    title: 'Input Tidak Lengkap',
                    text: 'Silakan pilih barang dan masukkan jumlah yang valid',
                    icon: 'warning',
                    confirmButtonColor: '#6d28d9'
                });
                return;
            }

            const barangData = barang.find(b => b.KODE_BARANG === kode);
            if (!barangData) {
                Swal.fire({
                    title: 'Barang Tidak Ditemukan',
                    text: 'Silakan pilih barang yang valid dari daftar',
                    icon: 'error',
                    confirmButtonColor: '#6d28d9'
                });
                return;
            }

            const satuan = barangData.SATUAN;
            const stok = parseInt(barangData.STOK);
            const sudahDiKeranjang = keranjang.find(item => item.kode_barang === kode);
            const totalJumlah = sudahDiKeranjang ? sudahDiKeranjang.jumlah + jumlah : jumlah;

            if (stok > 0 && totalJumlah > stok) {
                Swal.fire({
                    title: 'Stok Tidak Mencukupi',
                    html: `Stok yang tersedia hanya <strong>${stok} ${satuan}</strong>`,
                    icon: 'warning',
                    confirmButtonColor: '#6d28d9'
                });
                return;
            }

            const subtotal = harga * jumlah;
            
            // Add animation class to indicate new item
            const oldLength = keranjang.length;
            
            if (sudahDiKeranjang) {
                sudahDiKeranjang.jumlah += jumlah;
                sudahDiKeranjang.subtotal += subtotal;
            } else {
                keranjang.push({ 
                    kode_barang: kode, 
                    nama_barang: nama, 
                    harga: harga, 
                    jumlah: jumlah, 
                    subtotal: subtotal, 
                    satuan: satuan 
                });
            }
            renderKeranjang();

            // Reset form fields and focus on kode_barang
            $('#kode_barang, #nama_barang, #harga').val('');
            $('#jumlah').val('1');
            $('#stok-display').text('-');
            $('#stok-display').removeClass('text-orange-500 font-medium text-green-600');
            $('#kode_barang').focus();
            selectedBarang = null;
            
            // Show toast notification
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: 'success',
                title: 'Barang berhasil ditambahkan'
            });
            
            // Animate the new or updated row
            if (oldLength < keranjang.length) {
                $(`#cart-row-${keranjang.length - 1}`).addClass('cart-animation');
            } else {
                // Find the index of the updated item
                const idx = keranjang.findIndex(item => item.kode_barang === kode);
                if (idx >= 0) {
                    $(`#cart-row-${idx}`).addClass('pulse-animation');
                    setTimeout(() => {
                        $(`#cart-row-${idx}`).removeClass('pulse-animation');
                    }, 1000);
                }
            }
        }

        // Render shopping cart
        function renderKeranjang() {
            const tbody = $('#keranjang_body');
            const emptyCart = $('#empty-cart');
            const cartContainer = $('#cart-container');
            const cartCounter = $('#cart-counter');
            const totalItems = $('#total-items');
            const mobilePayment = $('#mobilePayment');
            
            tbody.empty();
            
            if (keranjang.length === 0) {
                emptyCart.show();
                cartContainer.hide();
                mobilePayment.hide();
                cartCounter.text('0 item');
                totalItems.text('0 item');
                updateCheckoutButton();
                return;
            }
            
            emptyCart.hide();
            cartContainer.show();
            mobilePayment.show();
            
            const itemText = keranjang.length === 1 ? ' item' : ' items';
            cartCounter.text(keranjang.length + itemText);
            totalItems.text(keranjang.length + itemText);
            
            let total = 0;
            let totalQty = 0;
            
            keranjang.forEach((item, index) => {
                total += item.subtotal;
                totalQty += item.jumlah;
                
                const row = $(`
                    <tr id="cart-row-${index}" class="border-b hover:bg-gray-50 transition-all">
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-800">${item.nama_barang}</span>
                                <span class="text-xs text-gray-500">${item.kode_barang}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <button type="button" onclick="updateQuantity(${index}, -1)" class="text-purple-600 hover:text-purple-800 p-1">
                                    <i class="fas fa-minus-circle"></i>
                                </button>
                                <span class="mx-2 bg-gray-100 px-2 py-1 rounded min-w-[30px] text-center">
                                    ${item.jumlah}
                                </span>
                                <button type="button" onclick="updateQuantity(${index}, 1)" class="text-purple-600 hover:text-purple-800 p-1">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">${item.satuan}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            ${formatRupiah(item.harga)}
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                            ${formatRupiah(item.subtotal)}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="hapusItem(${index})" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `);
                tbody.append(row);
            });

            $('#total_display').text(formatRupiah(total));
            $('#total_input').val(total);
            hitungKembalian();
            localStorage.setItem('keranjang', JSON.stringify(keranjang));
            updateCheckoutButton();
        }
        
        // Update item quantity
        function updateQuantity(index, change) {
            const item = keranjang[index];
            const barangData = barang.find(b => b.KODE_BARANG === item.kode_barang);
            const newQty = item.jumlah + change;
            
            if (newQty <= 0) {
                hapusItem(index);
                return;
            }
            
            if (barangData && newQty > parseInt(barangData.STOK)) {
                Swal.fire({
                    title: 'Stok Tidak Mencukupi',
                    html: `Stok yang tersedia hanya <strong>${barangData.STOK} ${barangData.SATUAN}</strong>`,
                    icon: 'warning',
                    confirmButtonColor: '#6d28d9'
                });
                return;
            }
            
            item.jumlah = newQty;
            item.subtotal = item.harga * newQty;
            renderKeranjang();
        }
        
        // Remove item from cart
        function hapusItem(index) {
            Swal.fire({
                title: 'Hapus Barang',
                text: `Apakah Anda yakin ingin menghapus ${keranjang[index].nama_barang} dari keranjang?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    keranjang.splice(index, 1);
                    renderKeranjang();
                }
            });
        }
        
        // Calculate change
        function hitungKembalian() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            const uangBayar = parseInt($('#uang_bayar').val()) || 0;
            
            if (uangBayar >= total && total > 0) {
                const kembalian = uangBayar - total;
                $('#kembalian').val(formatRupiah(kembalian));
                $('#btn-submit').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            } else {
                $('#kembalian').val(formatRupiah(0));
                $('#btn-submit').addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
            }
        }
        
        // Quick payment options
        function quickPayment(amount) {
            $('#uang_bayar').val(amount);
            hitungKembalian();
        }
        
        function payExact() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            $('#uang_bayar').val(total);
            hitungKembalian();
        }
        
        function clearPayment() {
            $('#uang_bayar').val('');
            $('#kembalian').val('');
            hitungKembalian();
        }
        
        // Scroll to payment section (for mobile)
        function scrollToPayment() {
            document.getElementById('payment-section').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Handle form submission
        function handleSubmit() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            const uangBayar = parseInt($('#uang_bayar').val()) || 0;

            if (keranjang.length === 0) {
                Swal.fire({
                    title: 'Keranjang Kosong',
                    text: 'Silakan tambahkan barang ke keranjang terlebih dahulu',
                    icon: 'warning',
                    confirmButtonColor: '#6d28d9'
                });
                return false;
            }

            if (isNaN(uangBayar) || uangBayar < total) {
                Swal.fire({
                    title: 'Pembayaran Kurang',
                    text: 'Jumlah uang yang dibayarkan kurang dari total belanja',
                    icon: 'error',
                    confirmButtonColor: '#6d28d9'
                });
                return false;
            }

            // Save data to hidden inputs
            $('#total_bayar').val(uangBayar); 
            $('#keranjang_input').val(JSON.stringify(keranjang)); 

            // Show loading state
            Swal.fire({
                title: 'Memproses Transaksi',
                html: 'Mohon tunggu sebentar...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Clear cart from localStorage after transaction is processed
            localStorage.removeItem('keranjang');
            
            return true;
        }
        
        // Reset form button
        $('#resetButton').on('click', function() {
            if (keranjang.length === 0) return;
            
            Swal.fire({
                title: 'Reset Transaksi',
                text: 'Apakah Anda yakin ingin membatalkan transaksi ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    keranjang = [];
                    localStorage.removeItem('keranjang');
                    renderKeranjang();
                    
                    $('#kode_barang, #nama_barang, #harga, #uang_bayar, #kembalian').val('');
                    $('#jumlah').val('1');
                    $('#stok-display').text('-');
                    $('#stok-display').removeClass('text-orange-500 font-medium text-green-600');
                    
                    Swal.fire(
                        'Transaksi Direset',
                        'Transaksi telah dibatalkan',
                        'success'
                    );
                }
            });
        });
        
        // Barcode scanner simulation
        $('#scanButton').on('click', function() {
            $('#kode_barang').focus();
            const Toast = Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            Toast.fire({
                icon: 'info',
                title: 'Siap untuk memindai barcode'
            });
        });
        
        // Update checkout button state
        function updateCheckoutButton() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            const uangBayar = parseInt($('#uang_bayar').val()) || 0;
            
            if (keranjang.length > 0 && uangBayar >= total) {
                $('#btn-submit').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            } else {
                $('#btn-submit').addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // F2 - Focus on search
            if (e.key === 'F2') {
                e.preventDefault();
                $('#kode_barang').focus();
            }
            
            // F4 - Reset transaction
            if (e.key === 'F4') {
                e.preventDefault();
                $('#resetButton').click();
            }
            
            // F8 - Focus on payment
            if (e.key === 'F8') {
                e.preventDefault();
                $('#uang_bayar').focus().select();
            }
            
            // Escape - Clear current input or close modal
            if (e.key === 'Escape') {
                if ($('#kode_barang').is(':focus')) {
                    $('#kode_barang').val('');
                }
                if ($('#jumlah').is(':focus')) {
                    $('#jumlah').val('1');
                }
                if ($('#uang_bayar').is(':focus')) {
                    $('#uang_bayar').val('');
                    hitungKembalian();
                }
            }
        });
        
        // Auto-hide success message & Initialize
        $(document).ready(function() {
            // Initialize cart
            renderKeranjang();
            
            // Show struk notification if exists
            <?php if (isset($_SESSION['success_msg']) && isset($_SESSION['last_transaction_id'])): ?>
            showStrukNotification();
            <?php endif; ?>
            
            // Auto-hide regular success alert
            setTimeout(function() {
                $('#success-alert').fadeOut('slow');
            }, 5000);
        });
    </script>

    <!-- Print Receipt Handler -->
    <?php if (isset($_SESSION['success_msg']) && isset($_SESSION['last_transaction_id'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success message with print option
        Swal.fire({
            title: 'Transaksi Berhasil!',
            html: `
                <div class="text-left">
                    <p class="mb-4"><?= $_SESSION['success_msg'] ?></p>
                    <div class="bg-blue-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Apakah Anda ingin mencetak struk transaksi?
                        </p>
                    </div>
                </div>
            `,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print mr-2"></i> Cetak Struk',
            cancelButtonText: 'Tidak, Terima Kasih',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Print receipt
                printStruk('<?= $_SESSION['last_transaction_id'] ?>');
            }
        });
    });

    // Function to print receipt
    function printStruk(id_transaksi) {
        const strukWindow = window.open(`struk.php?id=${id_transaksi}`, 'strukWindow', 'width=400,height=600,scrollbars=yes,resizable=yes');
        
        if (strukWindow) {
            strukWindow.focus();
        } else {
            Swal.fire({
                title: 'Popup Diblokir',
                text: 'Mohon izinkan popup untuk mencetak struk',
                icon: 'warning'
            });
        }
    }
    </script>
    <?php 
        // Clear the session variables after use
        unset($_SESSION['last_transaction_id']); 
    endif; 
    ?>

    <script>
    // Function to print last transaction struk
    function printLastStruk() {
        <?php if (isset($_SESSION['last_transaction_id'])): ?>
            printStruk('<?= $_SESSION['last_transaction_id'] ?>');
        <?php else: ?>
            Swal.fire({
                title: 'Tidak Ada Transaksi',
                text: 'Tidak ada transaksi yang bisa dicetak',
                icon: 'info'
            });
        <?php endif; ?>
    }
    </script>

    <?php 
    // Clear session variables after use
    if (isset($_SESSION['success_msg'])) {
        unset($_SESSION['success_msg']);
    }
    if (isset($_SESSION['last_transaction_id'])) {
        unset($_SESSION['last_transaction_id']); 
    }
    ?>

</body>
</html>