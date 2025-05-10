<?php
include '../config/koneksi.php';

// Total Barang
$queryBarang = oci_parse($conn, "SELECT COUNT(*) AS TOTAL FROM TBL_BARANG");
oci_execute($queryBarang);
$rowBarang = oci_fetch_assoc($queryBarang);
$totalBarang = $rowBarang['TOTAL'];

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

// Query untuk mendapatkan penjualan per hari dalam seminggu
$queryPenjualanMingguan = oci_parse($conn, "
  SELECT TO_CHAR(t.TANGGAL, 'Day') AS HARI, SUM(dt.SUBTOTAL) AS PENJUALAN
  FROM TBL_TRANSAKSI t
  JOIN TBL_DETAIL_TRANSAKSI dt ON t.ID_TRANSAKSI = dt.ID_TRANSAKSI
  WHERE t.TANGGAL >= SYSDATE - 7
  GROUP BY TO_CHAR(t.TANGGAL, 'Day')
  ORDER BY TO_CHAR(t.TANGGAL, 'Day')
");
oci_execute($queryPenjualanMingguan);
$penjualanMingguan = [];
while ($row = oci_fetch_assoc($queryPenjualanMingguan)) {
    $hariTrimmed = trim($row['HARI']);
    $penjualanMingguan[$hariTrimmed] = $row['PENJUALAN'];
}

// Format data penjualan mingguan untuk Chart.js
$hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$dataPenjualan = [];
foreach ($hari as $h) {
    $dataPenjualan[] = isset($penjualanMingguan[$h]) ? $penjualanMingguan[$h] : 0;
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
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-6">
      <h2 class="text-2xl font-bold mb-10 text-purple-600">KasirApp</h2>
      <nav class="space-y-4">
        <a href="KasirDashboard.php" class="block text-gray-700 hover:text-purple-600 font-medium">🏠 Dashboard</a>
        <a href="DataBarang.php" class="block text-gray-700 hover:text-purple-600 font-medium">📦 Data Barang</a>
        <a href="Transaksi.php" class="block text-gray-700 hover:text-purple-600 font-medium">🛒 Transaksi</a>
        <a href="riwayat.php" class="block text-gray-700 hover:text-purple-600 font-medium">📄 Riwayat</a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <h1 class="text-3xl font-semibold text-gray-800 mb-6">Selamat datang, Kasir 👋</h1>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm">
          <p class="text-gray-600">Total Barang</p>
          <h2 class="text-2xl font-bold"><?= $totalBarang ?></h2>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm">
          <p class="text-gray-600">Total Transaksi</p>
          <h2 class="text-2xl font-bold"><?= $totalTransaksi ?></h2>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm">
          <p class="text-gray-600">Total Pendapatan</p>
          <h2 class="text-2xl font-bold">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2>
        </div>
      </div>

      <!-- Chart and Recent Transactions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart -->
        <div class="bg-white p-6 rounded-xl shadow-sm lg:col-span-2">
          <h3 class="text-lg font-semibold mb-4">Aktivitas Penjualan Mingguan</h3>
          <canvas id="salesChart" class="w-full h-64"></canvas>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white p-6 rounded-xl shadow-sm">
          <h3 class="text-lg font-semibold mb-4">Transaksi Terakhir</h3>
          <ul class="text-sm space-y-3">
            <?php foreach ($transaksiTerakhir as $trx): ?>
              <li class="flex justify-between">
                <span>#<?= $trx['ID_TRANSAKSI'] ?></span>
                <span>Rp <?= number_format($trx['TOTAL'], 0, ',', '.') ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </main>
  </div>

  <!-- Chart.js Script -->
  <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
        datasets: [{
          label: 'Penjualan (Rp)',
          data: <?php echo json_encode($dataPenjualan); ?>,  // Menyisipkan data PHP ke dalam JS
          backgroundColor: 'rgba(139, 92, 246, 0.6)',
          borderRadius: 8,
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
