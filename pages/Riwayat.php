<?php
include '../config/koneksi.php';


// Set locale and timezone
setlocale(LC_TIME, 'id_ID');
date_default_timezone_set('Asia/Jakarta');

// Ambil riwayat transaksi
$query_transaksi = oci_parse($conn, "SELECT ID_TRANSAKSI, 
       TO_CHAR(TANGGAL, 'DD-MM-YYYY HH24:MI:SS') as TANGGAL, 
       TOTAL, 
       TOTAL_BAYAR, 
       KASIR 
FROM TBL_TRANSAKSI 
ORDER BY TANGGAL DESC");

oci_execute($query_transaksi);

// Ambil detail transaksi per transaksi
$riwayat = [];
while ($transaksi = oci_fetch_assoc($query_transaksi)) {
    $id_transaksi = $transaksi['ID_TRANSAKSI'];

    // Ambil detail transaksi lengkap (pakai join untuk dapat nama barang)
    $query_detail = oci_parse($conn, "
        SELECT d.KODE_BARANG, b.NAMA_BARANG, d.JUMLAH, b.SATUAN, d.SUBTOTAL
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

    $transaksi['DETAIL'] = $detail_transaksi;
    $riwayat[] = $transaksi;

    oci_free_statement($query_detail);
}

oci_free_statement($query_transaksi);

// Hitung statistik untuk dashboard cards
$total_transaksi = count($riwayat);
$total_pendapatan = array_sum(array_column($riwayat, 'TOTAL'));
$rata_rata_transaksi = $total_transaksi > 0 ? $total_pendapatan / $total_transaksi : 0;

// Hitung transaksi hari ini
$transaksi_hari_ini = count(array_filter($riwayat, function($t) { 
    return date('Y-m-d', strtotime($t['TANGGAL'])) == date('Y-m-d'); 
}));

oci_close($conn);

// Fungsi format rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - KasirApp</title>
    
    <!-- Styles & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CDN - Placed in head for earlier loading -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Styles -->
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f8fafc;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, #6d28d9 0%, #7c3aed 100%);
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 10;
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
        .table-container {
            overflow-x: auto;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .detail-row {
            animation: fadeIn 0.3s ease-out;
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.3);
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
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar text-white p-6" id="sidebar">
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
                <a href="Transaksi.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
                    <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Transaksi
                </a>
                <a href="Riwayat.php" class="flex items-center p-3 rounded-xl bg-white/20 text-white font-medium">
                    <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat
                </a>
            </nav>
        </aside>

        <!-- Toggle Button for Mobile -->
        <button id="sidebarToggle" class="md:hidden fixed top-4 right-4 z-20 bg-purple-600 text-white p-3 rounded-full shadow-md">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main class="content-wrapper flex-1 p-6 md:p-8 mt-12 md:mt-0">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center mb-2">
                            <i class="fas fa-history text-purple-600 mr-3"></i> 
                            Riwayat Transaksi
                        </h1>
                        <p class="text-gray-500">Kelola dan pantau semua transaksi yang telah dilakukan</p>
                    </div>
                    <!-- <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3">
                        <button onclick="exportData()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-all">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                        <button onclick="printReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-all">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                    </div> -->
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <!-- Total Transaksi -->
                <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-purple-500 stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Transaksi</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?= $total_transaksi ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-receipt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total Pendapatan -->
                <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-green-500 stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Pendapatan</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?= format_rupiah($total_pendapatan) ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Rata-rata Transaksi -->
                <!-- <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-blue-500 stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Rata-rata Transaksi</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?= format_rupiah($rata_rata_transaksi) ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div> -->
                
                <!-- Transaksi Hari Ini -->
                <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-amber-500 stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Transaksi Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?= $transaksi_hari_ini ?></p>
                        </div>
                        <div class="bg-amber-100 p-3 rounded-lg">
                            <i class="fas fa-calendar-day text-amber-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Filter Section -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-6 mb-8 shadow-md">
                <div class="flex items-center mb-4">
                    <i class="fas fa-filter text-white text-xl mr-3"></i>
                    <h3 class="text-lg font-bold text-white">Filter & Pencarian</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search-input" class="block text-sm font-medium text-white mb-2">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-purple-300"></i>
                            </div>
                            <input type="text" id="search-input" class="search-input w-full bg-white bg-opacity-90 border-0 rounded-lg py-2 pl-10 pr-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-white" placeholder="ID Transaksi, Kasir...">
                        </div>
                    </div>
                    <div>
                        <label for="date-from" class="block text-sm font-medium text-white mb-2">Dari Tanggal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-purple-300"></i>
                            </div>
                            <input type="date" id="date-from" class="search-input w-full bg-white bg-opacity-90 border-0 rounded-lg py-2 pl-10 pr-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-white">
                        </div>
                    </div>
                    <div>
                        <label for="date-to" class="block text-sm font-medium text-white mb-2">Sampai Tanggal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-purple-300"></i>
                            </div>
                            <input type="date" id="date-to" class="search-input w-full bg-white bg-opacity-90 border-0 rounded-lg py-2 pl-10 pr-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-list mr-3"></i>
                            Daftar Transaksi
                        </h3>
                        <div id="transaction-count" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                            <?= count($riwayat) ?> Transaksi
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="w-full" id="transaction-table">
                        <thead class="bg-gray-50">
                            <tr class="text-xs md:text-sm text-left text-gray-700">
                                <!-- <th class="px-4 py-3 font-semibold">ID</th> -->
                                <th class="px-4 py-3 font-semibold">Tanggal & Waktu</th>
                                <th class="px-4 py-3 font-semibold">Kasir</th>
                                <th class="px-4 py-3 font-semibold">Total</th>
                                <th class="px-4 py-3 font-semibold">Bayar</th>
                                <th class="px-4 py-3 font-semibold">Kembalian</th>
                                <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($riwayat) > 0): ?>
                                <?php foreach ($riwayat as $transaksi): ?>
                                    <tr class="hover:bg-gray-50 transition-colors" 
                                        data-transaction-id="<?= $transaksi['ID_TRANSAKSI'] ?>"
                                        data-date="<?= date('Y-m-d', strtotime($transaksi['TANGGAL'])) ?>" 
                                        data-kasir="<?= $transaksi['KASIR'] ?>">
                                        <!-- <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="bg-purple-100 p-1.5 rounded-lg mr-2">
                                                    <i class="fas fa-receipt text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?= $transaksi['ID_TRANSAKSI'] ?></span>
                                            </div>
                                        </td> -->
                                        <td class="px-4 py-3">
                                            <div class="text-xs md:text-sm">

                                                <?php 
                                                    $timestamp = strtotime($transaksi['TANGGAL']);
                                                    setlocale(LC_TIME, 'id_ID');
                                                ?>
                                                <div class="font-medium text-gray-900">
                                                    <?= strftime("%d %B %Y", $timestamp) ?>
                                                </div>
                                                <div class="text-gray-500">
                                                    <?= date("H:i:s", $timestamp) ?>
                                                </div>

                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="bg-blue-100 p-1.5 rounded-full mr-2">
                                                    <i class="fas fa-user text-blue-600 text-xs"></i>
                                                </div>
                                                <span class="text-sm text-gray-900"><?= $transaksi['KASIR'] ?></span>
                                            </div>
                                        </td>
                                        
                                        <?php
                                            $total_bayar = $transaksi['TOTAL_BAYAR'] ?? 0;
                                            $total = $transaksi['TOTAL'] ?? 0;
                                            $kembalian = ($total_bayar >= $total) ? $total_bayar - $total : 0;
                                        ?>
                                        
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-bold text-green-600"><?= format_rupiah($total) ?></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-bold text-blue-600"><?= format_rupiah($total_bayar) ?></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-bold text-purple-600"><?= format_rupiah($kembalian) ?></span>
                                        </td>
                                        <td class="px-4 py-3">

                                            <div class="flex items-center justify-center space-x-1">

                                                <button onclick="toggleDetail('<?= $transaksi['ID_TRANSAKSI'] ?>')" 
                                                        class="bg-purple-100 hover:bg-purple-200 text-purple-700 hover:text-purple-800 px-2 py-1 rounded text-xs transition-colors flex items-center">
                                                    <i class="fas fa-eye mr-1"></i> Detail
                                                </button>

                                                
                                                <button onclick="printStruk('<?= $transaksi['ID_TRANSAKSI'] ?>')" 
                                                        class="bg-green-100 hover:bg-green-200 text-green-700 hover:text-green-800 px-2 py-1 rounded text-xs transition-colors flex items-center">
                                                    <i class="fas fa-print mr-1"></i> Struk
                                                </button>
                                                

                                                <button onclick="confirmDelete('<?= $transaksi['ID_TRANSAKSI'] ?>')" 
                                                        class="bg-red-100 hover:bg-red-200 text-red-700 hover:text-red-800 px-2 py-1 rounded text-xs transition-colors flex items-center">
                                                    <i class="fas fa-trash mr-1"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Detail Transaksi -->
                                    <tr id="detail_<?= $transaksi['ID_TRANSAKSI'] ?>" class="hidden bg-gray-50 detail-row">
                                        <td colspan="7" class="px-4 py-3">
                                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                                <h4 class="text-md font-bold text-gray-800 mb-3 flex items-center">
                                                    <i class="fas fa-list-ul text-purple-600 mr-2"></i>
                                                    Detail Transaksi #<?= $transaksi['ID_TRANSAKSI'] ?>
                                                </h4>
                                                <?php if (count($transaksi['DETAIL']) > 0): ?>
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full bg-white rounded-lg overflow-hidden text-sm">
                                                            <thead class="bg-gray-100">
                                                                <tr class="text-xs text-gray-700">
                                                                    <th class="px-3 py-2 text-left font-medium">Kode Barang</th>
                                                                    <th class="px-3 py-2 text-left font-medium">Nama Barang</th>
                                                                    <th class="px-3 py-2 text-center font-medium">Jumlah</th>
                                                                    <th class="px-3 py-2 text-center font-medium">Satuan</th>
                                                                    <th class="px-3 py-2 text-right font-medium">Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-200">
                                                                <?php foreach ($transaksi['DETAIL'] as $detail): ?>
                                                                    <tr class="hover:bg-gray-50">
                                                                        <td class="px-3 py-2">
                                                                            <span class="bg-gray-100 px-2 py-1 rounded text-xs font-mono"><?= $detail['KODE_BARANG'] ?></span>
                                                                        </td>
                                                                        <td class="px-3 py-2 font-medium"><?= $detail['NAMA_BARANG'] ?></td>
                                                                        <td class="px-3 py-2 text-center">
                                                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                                                                <?= $detail['JUMLAH'] ?>
                                                                            </span>
                                                                        </td>
                                                                        <td class="px-3 py-2 text-center text-gray-600"><?= $detail['SATUAN'] ?></td>
                                                                        <td class="px-3 py-2 text-right font-bold text-green-600"><?= format_rupiah($detail['SUBTOTAL']) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot class="bg-gray-50">
                                                                <tr>
                                                                    <td colspan="4" class="px-3 py-2 text-right font-medium">Total:</td>
                                                                    <td class="px-3 py-2 text-right font-bold text-green-600">
                                                                        <?= format_rupiah($transaksi['TOTAL']) ?>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-8 text-gray-500">
                                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                                        <p>Tidak ada detail untuk transaksi ini.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="bg-gray-100 p-6 rounded-full mb-4">
                                                <i class="fas fa-history text-5xl text-gray-300"></i>
                                            </div>
                                            <h3 class="text-xl font-medium text-gray-800 mb-2">Belum Ada Transaksi</h3>
                                            <p class="text-gray-500 mb-4">Belum ada riwayat transaksi yang tercatat</p>
                                            <a href="Transaksi.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg flex items-center transition-all">
                                                <i class="fas fa-plus mr-2"></i> Buat Transaksi Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- No Data After Filter -->
                <div id="no-results" class="hidden p-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-gray-100 p-6 rounded-full mb-4">
                            <i class="fas fa-search text-5xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-800 mb-2">Tidak Ada Hasil</h3>
                        <p class="text-gray-500">Tidak ditemukan transaksi yang sesuai dengan filter</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-gray-500 text-sm mt-8 mb-4">
                <p>&copy; <?= date('Y') ?> KasirApp - Sistem Informasi Kasir</p>
            </footer>
        </main>
    </div>

    <!-- Hidden Form for Delete -->
    <form id="deleteForm" action="../process/hapus_transaksi.php" method="POST" style="display: none;">
        <input type="hidden" name="id_transaksi" id="deleteTransactionId">
    </form>

    <!-- JavaScript -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Toggle detail function
        function toggleDetail(id_transaksi) {
            const detailRow = document.getElementById('detail_' + id_transaksi);
            const button = event.target.closest('button');
            
            if (detailRow.classList.contains('hidden')) {
                // Close any open details first
                document.querySelectorAll('.detail-row:not(.hidden)').forEach(row => {
                    row.classList.add('hidden');
                    // Reset all detail buttons
                    document.querySelectorAll('button[onclick^="toggleDetail"]').forEach(btn => {
                        if (btn.innerHTML.includes('Tutup')) {
                            btn.innerHTML = '<i class="fas fa-eye mr-1"></i> Detail';
                        }
                    });
                });
                
                // Open this detail
                detailRow.classList.remove('hidden');
                button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i> Tutup';
                
                // Scroll to make sure the detail is visible
                setTimeout(() => {
                    detailRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            } else {
                detailRow.classList.add('hidden');
                button.innerHTML = '<i class="fas fa-eye mr-1"></i> Detail';
            }
        }

        // Confirm delete with SweetAlert
        function confirmDelete(id_transaksi) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                html: `Apakah Anda yakin ingin menghapus transaksi <b>#${id_transaksi}</b>?<br>Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    document.getElementById('deleteTransactionId').value = id_transaksi;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        // Show loading state for long operations
        function showLoading() {
            Swal.fire({
                title: 'Memproses...',
                html: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }


        // Function to print receipt
        function printStruk(id_transaksi) {
            const strukWindow = window.open(`struk.php?id=${id_transaksi}`, 'strukWindow', 'width=400,height=600,scrollbars=yes,resizable=yes');
            
            if (strukWindow) {
                strukWindow.focus();
                // Auto print setelah window terbuka
                strukWindow.onload = function() {
                    setTimeout(function() {
                        strukWindow.print();
                    }, 500);
                };
            } else {
                Swal.fire({
                    title: 'Popup Diblokir',
                    text: 'Mohon izinkan popup untuk mencetak struk',
                    icon: 'warning'
                });
            }
        }


        // Advanced search with multiple criteria
        function advancedSearch() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const rows = document.querySelectorAll('#transaction-table tbody tr[data-transaction-id]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const transactionId = row.getAttribute('data-transaction-id').toLowerCase();
                const kasir = row.getAttribute('data-kasir').toLowerCase();
                const transactionDate = row.getAttribute('data-date');
                
                let showRow = true;
                
                // Text search
                if (searchTerm && !transactionId.includes(searchTerm) && !kasir.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Date range filter
                if (dateFrom && transactionDate < dateFrom) {
                    showRow = false;
                }
                if (dateTo && transactionDate > dateTo) {
                    showRow = false;
                }
                
                if (showRow) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                    
                    // Hide detail row too if it's expanded
                    const detailId = 'detail_' + transactionId;
                    const detailRow = document.getElementById(detailId);
                    if (detailRow) {
                        detailRow.classList.add('hidden');
                    }
                }
            });
            
            // Update visible count
            document.getElementById('transaction-count').textContent = `${visibleCount} Transaksi`;
            
            // Show/hide no results message
            const noResults = document.getElementById('no-results');
            if (visibleCount === 0 && (searchTerm || dateFrom || dateTo)) {
                noResults.classList.remove('hidden');
                document.querySelector('.table-container').classList.add('hidden');
            } else {
                noResults.classList.add('hidden');
                document.querySelector('.table-container').classList.remove('hidden');
            }
        }


        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            document.getElementById('search-input').addEventListener('input', advancedSearch);
            document.getElementById('date-from').addEventListener('change', advancedSearch);
            document.getElementById('date-to').addEventListener('change', advancedSearch);
            
            
            // Check for URL parameters for success/error messages
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') && urlParams.get('success') === '1') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: urlParams.get('message') || 'Transaksi berhasil dihapus.',
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true
                });
                
                // Remove params from URL to prevent showing message on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.has('error') && urlParams.get('error') === '1') {
                Swal.fire({
                    title: 'Gagal!',
                    text: urlParams.get('message') || 'Terjadi kesalahan.',
                    icon: 'error'
                });
                
                // Remove params from URL to prevent showing message on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>