<?php
include '../config/koneksi.php';

// Default periode to 'mingguan' if not set in the query string
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mingguan';  // Default to 'mingguan'

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
$queryPendapatan = oci_parse($conn, "
  SELECT SUM(dt.SUBTOTAL) AS TOTAL_PENDAPATAN FROM TBL_DETAIL_TRANSAKSI dt
");
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
    $penjualan = array_fill_keys($labels, 0); // Key: nama hari
} else if ($periode == 'bulanan') {
    $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $penjualan = array_fill(0, 12, 0); // Index 0–11 untuk bulan
}

// --- QUERY DAN PENGOLAHAN DATA ---

if ($periode == 'mingguan') {
    // Ambil penjualan 7 hari terakhir, urut berdasarkan nama hari (Senin - Minggu)
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
        $namaHari = ucfirst(strtolower(trim($row['NAMA_HARI']))); // Contoh: 'SENIN    ' => 'Senin'
        if (array_key_exists($namaHari, $penjualan)) {
            $penjualan[$namaHari] = (float)$row['PENJUALAN'];
        }
    }

} else if ($periode == 'bulanan') {
    // Ambil penjualan dari awal tahun sampai bulan ini
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

// --- SIAPKAN DATA UNTUK CHART.JS ---

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
      overflow-y: hidden;
      height: 100vh;
      background-color: #f8f9fc;
    }
    .sidebar {
      background: linear-gradient(180deg, #6d28d9 0%, #7c3aed 100%);
      color: white;
      height: 100vh;
      position: fixed;
      width: 250px;
      transition: all 0.3s;
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
    .sidebar-item:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
    .sidebar-item.active {
      background-color: rgba(255, 255, 255, 0.2);
      font-weight: 600;
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
  </style>
</head>
<body>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside class="sidebar w-64 text-white p-6 hidden md:block">
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
      
      <!-- <div class="absolute bottom-0 left-0 w-64 p-6">
        <div class="bg-white/10 p-4 rounded-xl">
          <div class="flex items-center space-x-3 mb-3">
            <div class="bg-purple-200 text-purple-700 p-2 rounded-lg">
              <i class="fas fa-user"></i>
            </div>
            <div>
              <h4 class="font-medium text-white">Admin</h4>
              <p class="text-xs text-white/70">admin@kasirapp.com</p>
            </div>
          </div>
          <a href="../logout.php" class="flex items-center justify-center p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm font-medium transition-all duration-200">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
        </div>
      </div> -->
    </aside>

    <!-- Main Content -->
    <main class="content-wrapper">
      <div class="flex flex-col h-full">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-2xl font-semibold text-gray-800">Selamat datang, Kasir 👋</h1>
          <div class="flex items-center space-x-4">
            <div class="bg-white p-2 rounded-full shadow-sm">
              <i class="fas fa-bell text-gray-600"></i>
            </div>
            <div class="bg-indigo-600 text-white p-2 rounded-full shadow-sm">
              <i class="fas fa-user"></i>
            </div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="card p-6 flex items-center space-x-4">
            <div class="card-icon bg-indigo-100 text-indigo-600">
              <i class="fas fa-boxes-stacked text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Total Barang</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalBarang ?></h2>
            </div>
          </div>
          <div class="card p-6 flex items-center space-x-4">
            <div class="card-icon bg-green-100 text-green-600">
              <i class="fas fa-warehouse text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Total Stok</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalStok ?></h2>
            </div>
          </div>
          <div class="card p-6 flex items-center space-x-4">
            <div class="card-icon bg-blue-100 text-blue-600">
              <i class="fas fa-receipt text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Total Transaksi</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalTransaksi ?></h2>
            </div>
          </div>
          <div class="card p-6 flex items-center space-x-4">
            <div class="card-icon bg-purple-100 text-purple-600">
              <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Total Pendapatan</p>
              <h2 class="text-2xl font-bold text-gray-800">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2>
            </div>
          </div>
        </div>

        <!-- Chart and Recent Transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 mb-6">
          <!-- Chart -->
          <div class="card p-6 lg:col-span-2 flex flex-col">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Aktivitas Penjualan <?= ucfirst($periode) ?></h3>
              <select id="periode" onchange="updateChart()" class="dropdown">
                <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
              </select>
            </div>
            <div class="chart-container flex-1">
              <canvas id="salesChart"></canvas>
            </div>
          </div>

          <!-- Recent Transactions + Barang Terlaris -->
          <div class="flex flex-col gap-6">
            <!-- Transaksi Terakhir -->
            <div class="card p-6 flex-1">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history text-indigo-600 mr-2"></i> Transaksi Terakhir
              </h3>
              <div class="space-y-3">
                <?php foreach ($transaksiTerakhir as $trx): ?>
                  <div class="list-item flex justify-between items-center">
                    <div>
                      <span class="font-medium text-gray-800">#<?= $trx['ID_TRANSAKSI'] ?></span>
                      <div class="text-xs text-gray-500"><?= date("d M Y", strtotime($trx['TANGGAL'])) ?></div>
                    </div>
                    <span class="font-medium text-indigo-600">Rp <?= number_format($trx['TOTAL'], 0, ',', '.') ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Penjualan Terlaris -->
            <div class="card p-6 flex-1">
              <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-fire text-orange-500 mr-2"></i> Penjualan Terlaris
              </h3>
              <div class="space-y-3">
                <?php foreach ($barangTerlaris as $item): ?>
                  <div class="list-item flex justify-between items-center">
                    <span class="truncate text-gray-800"><?= htmlspecialchars($item['NAMA_BARANG']) ?></span>
                    <span class="font-medium text-orange-500"><?= $item['TOTAL_JUMLAH'] ?>x</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    const ctx = document.getElementById('salesChart').getContext('2d');

    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'Penjualan',
          data: <?= json_encode($dataPenjualan) ?>,
          backgroundColor: 'rgba(99, 102, 241, 0.7)', // indigo-500
          borderColor: 'rgba(79, 70, 229, 1)', // indigo-600
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