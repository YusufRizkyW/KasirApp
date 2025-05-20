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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body { 
      font-family: 'Inter', sans-serif; 
      overflow-y: hidden;
      height: 100vh;
    }
    .sidebar-icon {
      width: 20px;
      text-align: center;
      margin-right: 10px;
    }
    .card-icon {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background-color: rgba(139, 92, 246, 0.1);
    }
    .content-wrapper {
      height: calc(100vh - 2rem);
    }
    .chart-container {
      height: calc(100% - 2rem);
    }
    .list-container {
      max-height: calc(100vh - 150px);
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-6">
      <h2 class="text-2xl font-bold mb-8 text-purple-600">KasirApp</h2>
      <nav class="space-y-3">
        <a href="KasirDashboard.php" class="flex items-center p-2 rounded-lg bg-purple-100 text-purple-700 font-medium">
          <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
        </a>
        <a href="DataBarang.php" class="flex items-center p-2 rounded-lg hover:bg-purple-50 text-gray-700 hover:text-purple-600 font-medium">
          <span class="sidebar-icon"><i class="fas fa-boxes-stacked"></i></span> Data Barang
        </a>
        <a href="Transaksi.php" class="flex items-center p-2 rounded-lg hover:bg-purple-50 text-gray-700 hover:text-purple-600 font-medium">
          <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Transaksi
        </a>
        <a href="Riwayat.php" class="flex items-center p-2 rounded-lg hover:bg-purple-50 text-gray-700 hover:text-purple-600 font-medium">
          <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 overflow-hidden">
      <div class="content-wrapper flex flex-col">
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">Selamat datang, Kasir 👋</h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white p-4 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="card-icon">
              <i class="fas fa-boxes-stacked text-purple-600 text-xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Barang</p>
              <h2 class="text-xl font-bold"><?= $totalBarang ?></h2>
            </div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="card-icon">
              <i class="fas fa-warehouse text-purple-600 text-xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Stok</p>
              <h2 class="text-xl font-bold"><?= $totalStok ?></h2>
            </div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="card-icon">
              <i class="fas fa-receipt text-purple-600 text-xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Transaksi</p>
              <h2 class="text-xl font-bold"><?= $totalTransaksi ?></h2>
            </div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow-sm flex items-center space-x-4">
            <div class="card-icon">
              <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Pendapatan</p>
              <h2 class="text-xl font-bold">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2>
            </div>
          </div>
        </div>

        <!-- Chart and Recent Transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 overflow-hidden">
          <!-- Chart -->
          <div class="bg-white p-4 rounded-xl shadow-sm lg:col-span-2 flex flex-col">
            <div class="flex justify-between items-center mb-2">
              <h3 class="text-lg font-semibold">Aktivitas Penjualan <?= ucfirst($periode) ?></h3>
              <select id="periode" onchange="updateChart()" class="p-1 border rounded text-sm">
                <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
              </select>
            </div>
            <div class="chart-container flex-1">
              <canvas id="salesChart" class="w-full h-full"></canvas>
            </div>
          </div>

          <!-- Recent Transactions + Barang Terlaris -->
          <div class="flex flex-col gap-4 list-container">
            <!-- Transaksi Terakhir -->
            <div class="bg-white p-4 rounded-xl shadow-sm flex-1">
              <h3 class="text-lg font-semibold mb-3 flex items-center">
                <i class="fas fa-history text-purple-600 mr-2"></i> Transaksi Terakhir
              </h3>
              <ul class="text-sm space-y-3">
                <?php foreach ($transaksiTerakhir as $trx): ?>
                  <li class="flex justify-between border-b pb-2">
                    <span>#<?= $trx['ID_TRANSAKSI'] ?></span>
                    <span class="font-medium">Rp <?= number_format($trx['TOTAL'], 0, ',', '.') ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <!-- Penjualan Terlaris -->
            <div class="bg-white p-4 rounded-xl shadow-sm flex-1">
              <h3 class="text-lg font-semibold mb-3 flex items-center">
                <i class="fas fa-fire text-purple-600 mr-2"></i> Penjualan Terlaris
              </h3>
              <ul class="text-sm space-y-3">
                <?php foreach ($barangTerlaris as $item): ?>
                  <li class="flex justify-between border-b pb-2">
                    <span class="truncate pr-2"><?= htmlspecialchars($item['NAMA_BARANG']) ?></span>
                    <span class="font-medium"><?= $item['TOTAL_JUMLAH'] ?>x</span>
                  </li>
                <?php endforeach; ?>
              </ul>
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
          backgroundColor: 'rgba(139, 92, 246, 0.7)', // purple-500
          borderRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
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