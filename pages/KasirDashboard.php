<?php
include '../config/koneksi.php';

// Default periode to 'mingguan' if not set in the query string
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mingguan';  // Default to 'mingguan'

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
        <a href="Riwayat.php" class="block text-gray-700 hover:text-purple-600 font-medium">📄 Riwayat</a>
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
          <h3 class="text-lg font-semibold mb-4">Aktivitas Penjualan <?= ucfirst($periode) ?></h3>
          <!-- Dropdown untuk memilih periode -->
          <select id="periode" onchange="updateChart()">
            <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
            <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
          </select>
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
      labels: <?php echo json_encode($labels); ?>,
      datasets: [{
        label: 'Penjualan (Rp)',
        data: <?php echo json_encode($dataPenjualan); ?>,
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

  function updateChart() {
    const periode = document.getElementById('periode').value;
    window.location.href = `KasirDashboard.php?periode=${periode}`;
  }
</script>
</body>
</html>
