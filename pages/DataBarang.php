<?php include '../config/koneksi.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KasirApp - Data Barang</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body { 
      font-family: 'Poppins', sans-serif; 
      background-color: #f8fafc;
    }
    .sidebar {
      background: linear-gradient(180deg, #6d28d9 0%, #7c3aed 100%);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
    .table-container {
      max-height: calc(100vh - 200px);
      overflow-y: auto;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .table-container::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .table-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    .table-container::-webkit-scrollbar-thumb {
      background: #d1d5db;
      border-radius: 10px;
    }
    .table-container::-webkit-scrollbar-thumb:hover {
      background: #7c3aed;
    }
    table tbody tr {
      transition: all 0.2s ease;
    }
    table tbody tr:hover {
      background-color: rgba(124, 58, 237, 0.05);
      transform: translateY(-1px);
    }
    .btn-primary {
      transition: all 0.3s ease;
      box-shadow: 0 1px 3px rgba(124, 58, 237, 0.2);
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }
    .btn-primary:active {
      transform: translateY(0);
    }
    .stok-warning {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.7; }
      100% { opacity: 1; }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .alert {
      animation: fadeIn 0.3s ease-out;
    }
    .modal {
      backdrop-filter: blur(3px);
    }
    .modal-content {
      transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .modal.hidden .modal-content {
      transform: scale(0.95);
      opacity: 0;
    }
    .modal:not(.hidden) .modal-content {
      transform: scale(1);
      opacity: 1;
    }
    .badge {
      transition: all 0.2s ease;
    }
    .badge:hover {
      transform: scale(1.05);
    }
    .action-button {
      transition: all 0.2s ease;
    }
    .action-button:hover {
      transform: translateY(-1px);
    }
    .shadow-card {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .search-input:focus {
      box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        overflow: hidden;
      }
      .content-wrapper {
        margin-left: 0;
        width: 100%;
      }
      .mobile-menu-open .sidebar {
        width: 250px;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="sidebar text-white p-6 hidden md:block">
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
        <a href="DataBarang.php" class="flex items-center p-3 rounded-xl bg-white/20 text-white font-medium">
          <span class="sidebar-icon"><i class="fas fa-boxes-stacked"></i></span> Data Barang
        </a>
        <a href="Transaksi.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
          <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Transaksi
        </a>
        <a href="Riwayat.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
          <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat
        </a>
      </nav>
    </aside>

    <!-- Mobile Navbar -->
    <div class="md:hidden bg-purple-600 text-white p-4 flex justify-between items-center w-full fixed top-0 left-0 z-10">
      <div class="flex items-center space-x-2">
        <i class="fas fa-cash-register text-xl"></i>
        <h2 class="text-xl font-bold">KasirApp</h2>
      </div>
      <button id="menuBtn" class="p-2 rounded-lg hover:bg-purple-700 transition-colors" aria-label="Toggle menu">
        <i class="fas fa-bars"></i>
      </button>
    </div>

    <!-- Main Content -->
    <main class="flex-1 md:ml-64 p-6 md:p-8 mt-14 md:mt-0">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-boxes-stacked text-purple-600 mr-3"></i>Data Barang
          </h1>
          <p class="text-gray-500 mt-1">Kelola data barang di KasirApp</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <div class="relative">
            <input type="text" id="searchInput" placeholder="Cari barang..." class="search-input pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:border-purple-500 w-full md:w-64 transition-all duration-200">
            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
              <i class="fas fa-search"></i>
            </div>
          </div>
          
          <button onclick="toggleModal()" class="btn-primary bg-purple-600 text-white px-4 py-2 rounded-xl hover:bg-purple-700 flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> Tambah Barang
          </button>
        </div>
      </div>
      
      <!-- Pesan sukses/error -->
      <?php if (isset($_GET['status'])): ?>
        <?php 
        $icon = '';
        $bgColor = '';
        $borderColor = '';
        $textColor = '';
        $title = '';
        $message = '';
        
        switch($_GET['status']) {
          case 'deleted':
            $icon = 'fa-check-circle';
            $bgColor = 'bg-green-100';
            $borderColor = 'border-green-500';
            $textColor = 'text-green-800';
            $iconBg = 'bg-green-200';
            $iconColor = 'text-green-600';
            $title = 'Berhasil!';
            $message = 'Barang berhasil dihapus.';
            break;
          case 'errordeleting':
            $icon = 'fa-exclamation-circle';
            $bgColor = 'bg-red-100';
            $borderColor = 'border-red-500';
            $textColor = 'text-red-800';
            $iconBg = 'bg-red-200';
            $iconColor = 'text-red-600';
            $title = 'Gagal!';
            $message = 'Terjadi kesalahan saat menghapus barang.';
            break;
          case 'added':
            $icon = 'fa-check-circle';
            $bgColor = 'bg-green-100';
            $borderColor = 'border-green-500';
            $textColor = 'text-green-800';
            $iconBg = 'bg-green-200';
            $iconColor = 'text-green-600';
            $title = 'Berhasil!';
            $message = 'Barang baru berhasil ditambahkan.';
            break;
          case 'erroradding':
            $icon = 'fa-exclamation-circle';
            $bgColor = 'bg-red-100';
            $borderColor = 'border-red-500';
            $textColor = 'text-red-800';
            $iconBg = 'bg-red-200';
            $iconColor = 'text-red-600';
            $title = 'Gagal!';
            $message = 'Terjadi kesalahan saat menambahkan barang.';
            break;
          case 'edited':
            $icon = 'fa-check-circle';
            $bgColor = 'bg-green-100';
            $borderColor = 'border-green-500';
            $textColor = 'text-green-800';
            $iconBg = 'bg-green-200';
            $iconColor = 'text-green-600';
            $title = 'Berhasil!';
            $message = 'Data barang berhasil diperbarui.';
            break;
        }
        ?>
        <div id="alert-msg" class="alert <?= $bgColor ?> border-l-4 <?= $borderColor ?> <?= $textColor ?> p-4 mb-6 rounded-xl shadow-sm flex items-center">
          <div class="<?= $iconBg ?> p-2 rounded-lg mr-3">
            <i class="fas <?= $icon ?> <?= $iconColor ?>"></i>
          </div>
          <div>
            <h4 class="font-medium"><?= $title ?></h4>
            <p><?= $message ?></p>
          </div>
        </div>
      <?php endif; ?>
      
      <!-- Statistik -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <?php
          // Get statistics
          $queryTotalBarang = "SELECT COUNT(*) as total FROM TBL_BARANG";
          $resultTotalBarang = oci_parse($conn, $queryTotalBarang);
          oci_execute($resultTotalBarang);
          $rowTotalBarang = oci_fetch_assoc($resultTotalBarang);
          $totalBarang = $rowTotalBarang['TOTAL'];
          
          $queryLowStock = "SELECT COUNT(*) as low_stock FROM TBL_BARANG WHERE STOK <= 5";
          $resultLowStock = oci_parse($conn, $queryLowStock);
          oci_execute($resultLowStock);
          $rowLowStock = oci_fetch_assoc($resultLowStock);
          $lowStock = $rowLowStock['LOW_STOCK'];
          
          $queryCategories = "SELECT COUNT(DISTINCT KATEGORI) as categories FROM TBL_BARANG";
          $resultCategories = oci_parse($conn, $queryCategories);
          oci_execute($resultCategories);
          $rowCategories = oci_fetch_assoc($resultCategories);
          $totalCategories = $rowCategories['CATEGORIES'];
        ?>
        
        <!-- Total Barang -->
        <div class="shadow-card bg-white rounded-xl p-6 flex items-center">
          <div class="bg-purple-100 text-purple-600 p-3 rounded-xl mr-4">
            <i class="fas fa-boxes-stacked text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Barang</p>
            <h3 class="text-xl font-bold"><?= $totalBarang ?></h3>
          </div>
        </div>
        
        <!-- Stok Menipis -->
        <div class="shadow-card bg-white rounded-xl p-6 flex items-center">
          <div class="bg-orange-100 text-orange-600 p-3 rounded-xl mr-4">
            <i class="fas fa-exclamation-triangle text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Stok Menipis</p>
            <h3 class="text-xl font-bold"><?= $lowStock ?></h3>
            <?php if ($lowStock > 0): ?>
              <button onclick="showLowStockModal()" class="mt-2 px-3 py-1 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600 transition">Lihat</button>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Total Kategori -->
        <div class="shadow-card bg-white rounded-xl p-6 flex items-center">
          <div class="bg-blue-100 text-blue-600 p-3 rounded-xl mr-4">
            <i class="fas fa-tags text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Kategori</p>
            <h3 class="text-xl font-bold"><?= $totalCategories ?></h3>
          </div>
        </div>
        
        <!-- Update Terakhir -->
        <div class="shadow-card bg-white rounded-xl p-6 flex items-center">
          <div class="bg-green-100 text-green-600 p-3 rounded-xl mr-4">
            <i class="fas fa-clock text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Update Terakhir</p>
            <h3 class="text-xl font-bold"><?= date('d M Y'); ?></h3>
          </div>
        </div>
      </div>

      <!-- Tabel Data Barang -->
      <div class="bg-white shadow-card rounded-xl p-6 mb-6">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center">
            <div class="bg-purple-100 text-purple-800 rounded-xl px-4 py-2 inline-flex items-center">
              <i class="fas fa-database mr-2"></i>
              <span class="font-medium">Daftar Barang</span>
            </div>
          </div>
          
          <div class="text-gray-500 text-sm">
            <i class="fas fa-info-circle mr-1"></i> Stok berwarna oranye menandakan stok menipis.
          </div>
        </div>
        
        <div class="table-container overflow-x-auto">
          <table id="barangTable" class="min-w-full text-sm text-left">
            <thead class="text-gray-600 bg-gray-50 sticky top-0">
              <tr>
                <th class="py-3 px-4 rounded-tl-lg">Kode</th>
                <th class="py-3 px-4">Nama Barang</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4">Stok</th>
                <th class="py-3 px-4">Satuan</th>
                <th class="py-3 px-4">Harga</th>
                <th class="py-3 px-4 text-center rounded-tr-lg">Aksi</th>
              </tr>
            </thead>
            <tbody class="text-gray-700 divide-y divide-gray-100">
              <?php
                $query = "SELECT * FROM TBL_BARANG ORDER BY KODE_BARANG";
                $result = oci_parse($conn, $query);
                oci_execute($result);
                $hasData = false;

                while ($row = oci_fetch_assoc($result)) {
                  $hasData = true;
                  echo "<tr class='border-b hover:bg-gray-50 transition-colors'>";
                  echo "<td class='py-3 px-4 font-medium'><span class='bg-purple-50 text-purple-700 px-2 py-1 rounded-md'>{$row['KODE_BARANG']}</span></td>";
                  echo "<td class='py-3 px-4'>{$row['NAMA_BARANG']}</td>";
                  echo "<td class='py-3 px-4'><span class='badge bg-blue-50 text-blue-700 rounded-full px-3 py-1 text-xs font-medium'>{$row['KATEGORI']}</span></td>";
                  
                  if ($row['STOK'] <= 5) {
                    echo "<td class='py-3 px-4'><span class='stok-warning bg-orange-50 text-orange-600 px-3 py-1 rounded-full font-medium'>{$row['STOK']}</span></td>";
                  } else {
                    echo "<td class='py-3 px-4'><span class='bg-green-50 text-green-700 px-3 py-1 rounded-full font-medium'>{$row['STOK']}</span></td>";
                  }
                  
                  echo "<td class='py-3 px-4'>{$row['SATUAN']}</td>";
                  echo "<td class='py-3 px-4 font-medium'>Rp " . number_format($row['HARGA'], 0, ',', '.') . "</td>";
                  echo "<td class='py-3 px-4 text-center'>
                        <div class='flex justify-center space-x-2'>
                          <a href='../process/edit_barang.php?kode={$row['KODE_BARANG']}' class='action-button bg-blue-50 text-blue-500 hover:bg-blue-100 p-2 rounded-lg' aria-label='Edit barang'>
                            <i class='fas fa-edit'></i>
                          </a>
                          <button onclick=\"confirmDelete('{$row['KODE_BARANG']}', '{$row['NAMA_BARANG']}')\" class='action-button bg-red-50 text-red-500 hover:bg-red-100 p-2 rounded-lg' aria-label='Hapus barang'>
                            <i class='fas fa-trash-alt'></i>
                          </button>
                        </div>
                      </td>";
                  echo "</tr>";
                }

                // If no data exists
                if (!$hasData) {
                  echo "<tr><td colspan='7' class='py-8 text-center text-gray-500'>Belum ada data barang tersedia.</td></tr>";
                }
                
                oci_free_statement($result);
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah Barang -->
      <div id="modal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 hidden z-50">
        <div class="modal-content bg-white p-6 rounded-xl shadow-xl w-full max-w-md mx-4">
          <div class="flex justify-between items-center mb-6 pb-3 border-b">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
              <i class="fas fa-plus-circle text-purple-600 mr-2"></i>Tambah Barang
            </h2>
            <button onclick="toggleModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100" aria-label="Tutup">
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>
          
          <form action="../process/proses_tambah_barang.php" method="POST">
            <div class="mb-4">
              <label for="kode" class="block font-medium text-gray-700 mb-1">Kode Barang</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-barcode text-gray-400"></i>
                </div>
                <input type="text" name="kode_barang" id="kode" required autocomplete="off"
                  class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
              </div>
            </div>

            <div class="mb-4">
              <label for="nama" class="block font-medium text-gray-700 mb-1">Nama Barang</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-box text-gray-400"></i>
                </div>
                <input type="text" name="nama_barang" id="nama" required 
                  class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
              </div>
            </div>

            <div class="mb-4">
              <label for="kategori" class="block font-medium text-gray-700 mb-1">Kategori</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-tags text-gray-400"></i>
                </div>
                <select name="kategori" id="kategori" required 
                  class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent appearance-none">
                  <option value="">-- Pilih Kategori --</option>
                  <option value="Alat Tulis Kantor">Alat Tulis Kantor</option>
                  <option value="Bahan Makanan">Bahan Makanan</option>
                  <option value="Makanan">Makanan</option>
                  <option value="Minuman">Minuman</option>
                  <option value="Perlengkapan Mandi">Perlengkapan Mandi</option>
                  <option value="Snack">Snack</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="mb-4">
                <label for="stok" class="block font-medium text-gray-700 mb-1">Stok</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-cubes text-gray-400"></i>
                  </div>
                  <input type="number" name="stok" id="stok" min="0" required 
                    class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                </div>
              </div>

              <div class="mb-4">
                <label for="satuan" class="block font-medium text-gray-700 mb-1">Satuan</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-ruler text-gray-400"></i>
                  </div>
                  <select name="satuan" id="satuan" required 
                    class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent appearance-none">
                    <option value="">-- Pilih --</option>
                    <option value="botol">botol</option>
                    <option value="dus">dus</option>
                    <option value="kg">kg</option>
                    <option value="liter">liter</option>
                    <option value="pak">pak</option>
                    <option value="pcs">pcs</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-gray-400"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <label for="harga" class="block font-medium text-gray-700 mb-1">Harga (Rp)</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-tag text-gray-400"></i>
                </div>
                <input type="number" name="harga" id="harga" min="0" required 
                  class="w-full border border-gray-300 pl-10 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-sm text-gray-600 font-medium" id="harga-display">
                  Rp 0
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-3">
              <button type="button" onclick="toggleModal()" 
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
                <i class="fas fa-times mr-2"></i> Batal
              </button>
              <button type="submit" 
                class="btn-primary bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                <i class="fas fa-save mr-2"></i> Simpan
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Stok Menipis -->
      <div id="lowStockModal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 hidden z-50">
        <div class="modal-content bg-white p-6 rounded-xl shadow-xl w-full max-w-lg mx-4">
          <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h2 class="text-lg font-bold text-gray-800 flex items-center">
              <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i> Daftar Barang Stok Menipis
            </h2>
            <button onclick="closeLowStockModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100" aria-label="Tutup">
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
              <thead class="text-gray-600 bg-gray-50">
                <tr>
                  <th class="py-2 px-4">Kode Barang</th>
                  <th class="py-2 px-4">Nama Barang</th>
                  <th class="py-2 px-4">Stok</th>
                </tr>
              </thead>
              <tbody class="text-gray-700">
                <?php
                  $query = "SELECT KODE_BARANG, NAMA_BARANG, STOK FROM TBL_BARANG WHERE STOK <= 5 ORDER BY STOK ASC";
                  $result = oci_parse($conn, $query);
                  oci_execute($result);
                  $ada = false;
                  while ($row = oci_fetch_assoc($result)) {
                    $ada = true;
                    echo "<tr>";
                    echo "<td class='py-2 px-4'>{$row['KODE_BARANG']}</td>";
                    echo "<td class='py-2 px-4'>{$row['NAMA_BARANG']}</td>";
                    echo "<td class='py-2 px-4 text-orange-600 font-bold'>{$row['STOK']}</td>";
                    echo "</tr>";
                  }
                  if (!$ada) {
                    echo "<tr><td colspan='3' class='py-4 px-4 text-center text-gray-400'>Tidak ada barang dengan stok menipis.</td></tr>";
                  }
                  oci_free_statement($result);
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      
      <!-- Confirmation Delete Modal -->
      <div id="confirmDeleteModal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 hidden z-50">
        <div class="modal-content bg-white p-6 rounded-xl shadow-xl w-full max-w-sm mx-4">
          <div class="text-center mb-6">
            <div class="bg-red-100 mx-auto mb-4 p-3 rounded-full inline-block">
              <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Konfirmasi Hapus</h3>
            <p class="text-gray-600 mt-2">Apakah Anda yakin ingin menghapus barang "<span id="deleteItemName" class="font-medium"></span>"?</p>
          </div>
          
          <form id="deleteForm" method="POST" class="flex justify-center space-x-3">
            <input type="hidden" id="deleteKodeBarang" name="kode_barang" value="">
            <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
              <i class="fas fa-times mr-2"></i> Batal
            </button>
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors flex items-center">
              <i class="fas fa-trash-alt mr-2"></i> Hapus
            </button>
          </form>
        </div>
      </div>
      
      <!-- Footer -->
      <footer class="text-center py-6 text-gray-500 text-sm">
        <p>&copy; <?php echo date('Y'); ?> KasirApp - Sistem Informasi Kasir</p>
      </footer>
    </main>
  </div>
  
  <script>
    // Toggle modal
    function toggleModal() {
      const modal = document.getElementById("modal");
      modal.classList.toggle("hidden");
      if (!modal.classList.contains("hidden")) {
        document.getElementById("kode").focus();
      }
    }
    
    // Confirm delete
    function confirmDelete(kodeBarang, namaBarang) {
      const confirmBox = document.getElementById('confirmDeleteModal');
      document.getElementById('deleteItemName').textContent = namaBarang;
      document.getElementById('deleteForm').action = '../process/hapus_barang.php';
      document.getElementById('deleteKodeBarang').value = kodeBarang;
      confirmBox.classList.remove('hidden');
    }
    
    function closeConfirmModal() {
      document.getElementById('confirmDeleteModal').classList.add('hidden');
    }
    
    // Low stock modal
    function showLowStockModal() {
      document.getElementById('lowStockModal').classList.remove('hidden');
    }
    
    function closeLowStockModal() {
      document.getElementById('lowStockModal').classList.add('hidden');
    }
    
    // Mobile menu toggle
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
      menuBtn.addEventListener('click', function() {
        const sidebar = document.querySelector('aside');
        sidebar.classList.toggle('hidden');
        
        if (!sidebar.classList.contains('hidden')) {
          sidebar.style.width = '250px';
          sidebar.style.position = 'fixed';
          sidebar.style.top = '0';
          sidebar.style.left = '0';
          sidebar.style.zIndex = '50';
          
          // Add close button
          const closeBtn = document.createElement('button');
          closeBtn.innerHTML = '<i class="fas fa-times"></i>';
          closeBtn.className = 'absolute top-6 right-6 text-white text-xl p-2 rounded-lg hover:bg-white/10';
          closeBtn.id = 'closeSidebarBtn';
          closeBtn.setAttribute('aria-label', 'Close menu');
          closeBtn.addEventListener('click', function() {
            sidebar.classList.add('hidden');
            sidebar.style.width = '';
            this.remove();
          });
          
          // Only add if it doesn't exist already
          if (!document.getElementById('closeSidebarBtn')) {
            sidebar.appendChild(closeBtn);
          }
        }
      });
    }
    
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alert = document.getElementById('alert-msg');
      if (alert) {
        setTimeout(function() {
          alert.style.opacity = '0';
          alert.style.transition = 'opacity 0.8s ease';
          setTimeout(function() {
            alert.style.display = 'none';
          }, 800);
        }, 5000);
      }
      
      // Initialize search functionality
      document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.getElementById('barangTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
          // Skip if this is the "no data" message row
          if (rows[i].cells.length === 1 && rows[i].cells[0].colSpan) {
            continue;
          }
          
          const kode = rows[i].getElementsByTagName('td')[0]?.textContent.toLowerCase() || '';
          const nama = rows[i].getElementsByTagName('td')[1]?.textContent.toLowerCase() || '';
          const kategori = rows[i].getElementsByTagName('td')[2]?.textContent.toLowerCase() || '';
          
          if (kode.includes(searchValue) || nama.includes(searchValue) || kategori.includes(searchValue)) {
            rows[i].style.display = '';
          } else {
            rows[i].style.display = 'none';
          }
        }
      });
      
      // Format currency input
      const hargaInput = document.getElementById('harga');
      if (hargaInput) {
        hargaInput.addEventListener('input', function() {
          // Remove non-digit characters
          let value = this.value.replace(/\D/g, '');
          
          // Add thousand separators
          if (value) {
            document.getElementById('harga-display').textContent = new Intl.NumberFormat('id-ID', { 
              style: 'currency', 
              currency: 'IDR',
              minimumFractionDigits: 0,
              maximumFractionDigits: 0
            }).format(value);
          } else {
            document.getElementById('harga-display').textContent = 'Rp 0';
          }
        });
      }
    });
  </script>
  <?php oci_close($conn); ?>
</body>
</html>