<?php
include '../config/koneksi.php';

// Default periode to 'mingguan' if not set in the query string
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mingguan';

// Total Barang
$queryBarang = oci_parse($conn, "SELECT COUNT(*) AS TOTAL FROM TBL_BARANG");
oci_execute($queryBarang);
$rowBarang = oci_fetch_assoc($queryBarang);
$totalBarang = $rowBarang['TOTAL'];

// Total Stok Barang
$queryStok = oci_parse($conn, "SELECT SUM(STOK) AS TOTAL_STOK FROM TBL_BARANG");
oci_execute($queryStok);
$rowStok = oci_fetch_assoc($queryStok);
$totalStok = $rowStok['TOTAL_STOK'] ?? 0;

// Total Transaksi
$queryTransaksi = oci_parse($conn, "SELECT COUNT(*) AS TOTAL FROM TBL_TRANSAKSI");
oci_execute($queryTransaksi);
$rowTransaksi = oci_fetch_assoc($queryTransaksi);
$totalTransaksi = $rowTransaksi['TOTAL'];

// Total Pendapatan
$queryPendapatan = oci_parse($conn, "SELECT SUM(dt.SUBTOTAL) AS TOTAL_PENDAPATAN FROM TBL_DETAIL_TRANSAKSI dt");
oci_execute($queryPendapatan);
$rowPendapatan = oci_fetch_assoc($queryPendapatan);
$totalPendapatan = $rowPendapatan['TOTAL_PENDAPATAN'] ?? 0;

// Penjualan Terlaris
$queryTerlaris = oci_parse($conn, "
    SELECT 
        B.NAMA_BARANG,
        SUM(DT.JUMLAH) AS TOTAL_JUMLAH
    FROM 
        TBL_DETAIL_TRANSAKSI DT
    JOIN 
        TBL_BARANG B ON DT.KODE_BARANG = B.KODE_BARANG
    GROUP BY 
        B.NAMA_BARANG
    ORDER BY 
        TOTAL_JUMLAH DESC
    FETCH FIRST 5 ROWS ONLY
");
oci_execute($queryTerlaris);

$barangTerlaris = [];
while ($row = oci_fetch_assoc($queryTerlaris)) {
    $barangTerlaris[] = $row;
}

// Query untuk mendapatkan 3 transaksi terakhir
$queryTransaksiTerakhir = oci_parse($conn, "
  SELECT t.ID_TRANSAKSI, t.TANGGAL, SUM(dt.SUBTOTAL) AS TOTAL
  FROM TBL_TRANSAKSI t
  JOIN TBL_DETAIL_TRANSAKSI dt ON t.ID_TRANSAKSI = dt.ID_TRANSAKSI
  GROUP BY t.ID_TRANSAKSI, t.TANGGAL
  ORDER BY t.TANGGAL DESC
  FETCH FIRST 3 ROWS ONLY
");
oci_execute($queryTransaksiTerakhir);
$transaksiTerakhir = [];
while ($row = oci_fetch_assoc($queryTransaksiTerakhir)) {
    $transaksiTerakhir[] = $row;
}

// Inisialisasi array penjualan sesuai dengan periode
if ($periode == 'mingguan') {
    $labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $penjualan = array_fill_keys($labels, 0);
} else if ($periode == 'bulanan') {
    $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $penjualan = array_fill(0, 12, 0);
}

// Data penjualan berdasarkan periode
if ($periode == 'mingguan') {
    $query = oci_parse($conn, "
        SELECT TO_CHAR(t.TANGGAL, 'DAY', 'NLS_DATE_LANGUAGE=INDONESIAN') AS NAMA_HARI,
               SUM(dt.SUBTOTAL) AS PENJUALAN
        FROM TBL_TRANSAKSI t
        JOIN TBL_DETAIL_TRANSAKSI dt ON t.ID_TRANSAKSI = dt.ID_TRANSAKSI
        WHERE t.TANGGAL >= TRUNC(SYSDATE) - 6
        GROUP BY TO_CHAR(t.TANGGAL, 'DAY', 'NLS_DATE_LANGUAGE=INDONESIAN')
    ");
    oci_execute($query);
    while ($row = oci_fetch_assoc($query)) {
        $namaHari = ucfirst(strtolower(trim($row['NAMA_HARI'])));
        if (array_key_exists($namaHari, $penjualan)) {
            $penjualan[$namaHari] = (float)$row['PENJUALAN'];
        }
    }
} else if ($periode == 'bulanan') {
    $query = oci_parse($conn, "
        SELECT TO_NUMBER(TO_CHAR(t.TANGGAL, 'MM')) AS BULAN,
               SUM(dt.SUBTOTAL) AS PENJUALAN
        FROM TBL_TRANSAKSI t
        JOIN TBL_DETAIL_TRANSAKSI dt ON t.ID_TRANSAKSI = dt.ID_TRANSAKSI
        WHERE t.TANGGAL >= TRUNC(SYSDATE, 'YYYY')
        GROUP BY TO_CHAR(t.TANGGAL, 'MM')
    ");
    oci_execute($query);
    while ($row = oci_fetch_assoc($query)) {
        $bulanIndex = (int)$row['BULAN'] - 1; // Januari = 0
        if (isset($penjualan[$bulanIndex])) {
            $penjualan[$bulanIndex] = (float)$row['PENJUALAN'];
        }
    }
}

// Siapkan data untuk Chart.JS
$dataPenjualan = [];
if ($periode == 'mingguan') {
    foreach ($labels as $namaHari) {
        $dataPenjualan[] = $penjualan[$namaHari];
    }
} else if ($periode == 'bulanan') {
    foreach (range(0, 11) as $i) {
        $dataPenjualan[] = $penjualan[$i];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Kasir</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body { 
      font-family: 'Poppins', sans-serif; 
      background-color: #f8f9fc;
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
    .sidebar-item {
      padding: 0.75rem 1.25rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      transition: all 0.2s;
    }
    .content-wrapper {
      margin-left: 250px;
      padding: 1.5rem;
      height: 100vh;
      overflow-y: auto;
    }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }
    .card:hover {
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }
    .card-icon {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
    }
    .chart-container {
      height: 300px;
      width: 100%;
    }
    .dropdown {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0.5rem;
      font-size: 0.875rem;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .list-item {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #f1f5f9;
      transition: all 0.2s;
    }
    .list-item:last-child {
      border-bottom: none;
    }
    .list-item:hover {
      background-color: #f9fafb;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        overflow: hidden;
      }
      .content-wrapper {
        margin-left: 0;
      }
      .mobile-menu-open .sidebar {
        width: 250px;
      }
      .mobile-menu-open .content-wrapper {
        margin-left: 250px;
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
        <a href="KasirDashboard.php" class="flex items-center p-3 rounded-xl bg-white/20 text-white font-medium">
          <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
        </a>
        <a href="DataBarang.php" class="flex items-center p-3 rounded-xl hover:bg-white/10 text-white/90 hover:text-white font-medium transition-all duration-200">
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

    <!-- Mobile menu button -->
    <button id="mobile-menu-button" class="md:hidden fixed top-4 right-4 z-20 bg-indigo-600 text-white p-3 rounded-full shadow-lg">
      <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="content-wrapper flex-1">
      <div class="flex flex-col h-full">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-2xl font-semibold text-gray-800">Selamat datang, Kasir 👋</h1>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <!-- Total Barang -->
          <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
            <div class="p-4 bg-indigo-100 text-indigo-600 rounded-full">
              <i class="fas fa-boxes-stacked text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Barang</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalBarang ?></h2>
            </div>
          </div>

          <!-- Total Stok -->
          <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
            <div class="p-4 bg-green-100 text-green-600 rounded-full">
              <i class="fas fa-warehouse text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Stok</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalStok ?></h2>
            </div>
          </div>

          <!-- Total Pendapatan -->
          <div class="bg-white shadow rounded-xl p-6 flex items-center space-x-4">
            <div class="p-4 bg-purple-100 text-purple-600 rounded-full">
              <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Pendapatan</p>
              <h2 class="text-2xl font-bold text-gray-800">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2>
            </div>
          </div>
        </div>

        <!-- Chart and Sidebar in One Row -->
        <div class="flex flex-col lg:flex-row gap-6 mb-6">
          <!-- Chart Section -->
          <div class="bg-white shadow rounded-xl p-6 w-full lg:w-2/3 flex flex-col">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Aktivitas Penjualan <?= ucfirst($periode) ?></h3>
              <select id="periode" onchange="updateChart()" class="border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
              </select>
            </div>
            <div class="relative" style="height: 320px;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>

          <!-- Sidebar (Transaksi & Penjualan Terlaris) -->
          <div class="flex flex-col gap-6 w-full lg:w-1/3">
            <!-- Transaksi Terakhir -->
            <div class="bg-white shadow rounded-xl p-6 flex-1">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history text-indigo-600 mr-2"></i> Transaksi Terakhir
              </h3>
              <div class="space-y-3 max-h-72 overflow-auto pr-1 text-sm">
                <?php foreach ($transaksiTerakhir as $trx): ?>
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="font-medium text-gray-800">#<?= $trx['ID_TRANSAKSI'] ?></span>
                      <div class="text-xs text-gray-500"><?= date("d M Y", strtotime($trx['TANGGAL'])) ?></div>
                    </div>
                    <span class="font-medium text-indigo-600">Rp <?= number_format($trx['TOTAL'], 0, ',', '.') ?></span>
                  </div>
                <?php endforeach; ?>
                <?php if (count($transaksiTerakhir) === 0): ?>
                  <div class="text-center text-gray-500">Belum ada transaksi</div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Penjualan Terlaris -->
            <div class="bg-white shadow rounded-xl p-6 flex-1">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-fire text-orange-500 mr-2"></i> Penjualan Terlaris
              </h3>
              <div class="space-y-3 max-h-72 overflow-auto pr-1 text-sm">
                <?php foreach ($barangTerlaris as $item): ?>
                  <div class="flex justify-between items-center">
                    <span class="truncate text-gray-800"><?= htmlspecialchars($item['NAMA_BARANG']) ?></span>
                    <span class="font-medium text-orange-500"><?= $item['TOTAL_JUMLAH'] ?>x</span>
                  </div>
                <?php endforeach; ?>
                <?php if (count($barangTerlaris) === 0): ?>
                  <div class="text-center text-gray-500">Belum ada data penjualan</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <footer class="mt-auto pt-6 pb-2">
          <p class="text-center text-sm text-gray-500">© 2024 KasirApp. All rights reserved.</p>
        </footer>
      </div>
    </main>
  </div>

  <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
      document.body.classList.toggle('mobile-menu-open');
    });
  
    // Setup chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'Penjualan (Rp)',
          data: <?= json_encode($dataPenjualan) ?>,
          backgroundColor: 'rgba(99, 102, 241, 0.7)',
          borderColor: 'rgba(79, 70, 229, 1)',
          borderWidth: 1,
          borderRadius: 6,
          hoverBackgroundColor: 'rgba(79, 70, 229, 0.9)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              callback: function(value) {
                return 'Rp ' + value.toLocaleString('id-ID');
              }
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Rp ' + context.raw.toLocaleString('id-ID');
              }
            }
          }
        }
      }
    });

    function updateChart() {
      const periode = document.getElementById('periode').value;
      window.location.href = `KasirDashboard.php?periode=${periode}`;
    }
  </script>
</body>
</html>