<?php include '../config/koneksi.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Data Barang</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
  <script>
    function toggleModal() {
      const modal = document.getElementById("modal");
      modal.classList.toggle("hidden");
      if (!modal.classList.contains("hidden")) {
        document.getElementById("kode").focus();
      }
    };
        
    <?php if (isset($_GET['status'])): ?>
      <div id="alert-msg" class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
        <?= $_GET['status'] == 'edited' ? 'Data berhasil diperbarui!' : 'Gagal mengedit data.' ?>
      </div>
    <?php endif; ?>

  </script>
</head>
<body class="bg-gray-50">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-6">
      <h2 class="text-2xl font-bold mb-10 text-purple-600">KasirApp</h2>
      <nav class="space-y-4">
        <a href="KasirDashboard.php" class="block text-gray-700 hover:text-purple-600 font-medium">🏠 Dashboard</a>
        <a href="DataBarang.php" class="block text-purple-600 font-bold">📦 Data Barang</a>
        <a href="Transaksi.php" class="block text-gray-700 hover:text-purple-600 font-medium">🛒 Transaksi</a>
        <a href="Riwayat.php" class="block text-gray-700 hover:text-purple-600 font-medium">📄 Riwayat</a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Data Barang</h1>
        <button onclick="toggleModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">+ Tambah Barang</button>
      </div>
      
      <!-- Pesan sukses -->
      <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'deleted'): ?>
          <div id="alert-msg" class="bg-green-100 text-green-800 p-3 mb-4 rounded">Barang berhasil dihapus!</div>
        <?php elseif ($_GET['status'] === 'errordeleting'): ?>
          <div id="alert-msg" class="bg-red-100 text-red-800 p-3 mb-4 rounded">Gagal menghapus barang.</div>
        <?php elseif ($_GET['status'] === 'added'): ?>
          <div id="alert-msg" class="bg-green-100 text-green-800 p-3 mb-4 rounded">Barang berhasil ditambahkan!</div>
        <?php elseif ($_GET['status'] === 'erroradding'): ?>
          <div id="alert-msg" class="bg-red-100 text-red-800 p-3 mb-4 rounded">Gagal menambahkan barang!</div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Tabel Data Barang -->
      <div class="overflow-x-auto bg-white shadow-sm rounded-xl p-6">
        <table class="min-w-full text-sm text-left">
          <thead class="text-gray-600 border-b">
            <tr>
              <th class="py-3 px-4">Kode</th>
              <th class="py-3 px-4">Nama Barang</th>
              <th class="py-3 px-4">Kategori</th>
              <th class="py-3 px-4">Stok</th>
              <th class="py-3 px-4">Satuan</th>
              <th class="py-3 px-4">Harga</th>
              <th class="py-3 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <?php
              $query = "SELECT * FROM TBL_BARANG ORDER BY KODE_BARANG";
              $result = oci_parse($conn, $query);
              oci_execute($result);

              while ($row = oci_fetch_assoc($result)) {
                echo "<tr class='border-b hover:bg-gray-50'>";
                echo "<td class='py-3 px-4'>{$row['KODE_BARANG']}</td>";
                echo "<td class='py-3 px-4'>{$row['NAMA_BARANG']}</td>";
                echo "<td class='py-3 px-4'>{$row['KATEGORI']}</td>";
                echo "<td class='py-3 px-4'>{$row['STOK']}</td>";
                echo "<td class='py-3 px-4'>{$row['SATUAN']}</td>";
                echo "<td class='py-3 px-4'>Rp " . number_format($row['HARGA']) . "</td>";
                echo "<td class='py-3 px-4 text-center'>
                      <a href='../process/edit_barang.php?kode={$row['KODE_BARANG']}' class='text-blue-500 hover:underline mr-2'>Edit</a>
                      <form action='../process/hapus_barang.php' method='POST' onsubmit='return confirm(\"Yakin ingin menghapus barang ini?\")' style='display:inline;'>
                        <input type='hidden' name='kode_barang' value='{$row['KODE_BARANG']}'>
                        <button type='submit' class='text-red-500 hover:underline'>Hapus</button>
                      </form>
                    </td>";
                echo "</tr>";
              }
              oci_free_statement($result);
              oci_close($conn);
            ?>
          </tbody>
        </table>
      </div>

      <!-- Modal Tambah Barang -->
      <div id="modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
          <h2 class="text-xl font-semibold mb-4">Tambah Barang</h2>
          <form action="../process/proses_tambah_barang.php" method="POST">
            <div class="mb-4">
              <label for="kode" class="block font-medium">Kode Barang</label>
              <input type="text" name="kode_barang" id="kode" required class="w-full border px-3 py-2 rounded" />
            </div>

            <div class="mb-4">
              <label for="nama" class="block font-medium">Nama Barang</label>
              <input type="text" name="nama_barang" id="nama" required class="w-full border px-3 py-2 rounded" />
            </div>

            <div class="mb-4">
              <label for="kategori" class="block font-medium">Kategori</label>
                <select name="kategori" id="kategori" required class="w-full border px-3 py-2 rounded">
                  <option value="">-- Pilih Kategori --</option>
                  <option value="Alat Tulis Kantor">Alat Tulis Kantor</option>
                  <option value="Bahan Makanan">Bahan Makanan</option>
                  <option value="Makanan">Makanan</option>
                  <option value="Minuman">Minuman</option>
                  <option value="Perlengkapan Mandi">Perlengkapan Mandi</option>
                  <option value="Snack">Snack</option>
                </select>
            </div>

            <div class="mb-4">
              <label for="stok" class="block font-medium">Stok</label>
              <input type="number" name="stok" id="stok" min="0" required class="w-full border px-3 py-2 rounded" />
            </div>

            <div class="mb-4">
              <label for="satuan" class="block font-medium">Satuan</label>
              <select name="satuan" id="satuan" required class="w-full border px-3 py-2 rounded">
                <option value="">-- Pilih Satuan --</option>
                <option value="botol">botol</option>
                <option value="dus">dus</option>
                <option value="kg">kg</option>
                <option value="liter">liter</option>
                <option value="pak">pak</option>
                <option value="pcs">pcs</option>
              </select>
            </div>

            <div class="mb-4">
              <label for="harga" class="block font-medium">Harga</label>
              <input type="number" name="harga" id="harga" min="0" required class="w-full border px-3 py-2 rounded" />
            </div>

            <div class="flex justify-end">
              <button type="button" onclick="toggleModal()" class="px-4 py-2 mr-2 border rounded text-gray-700">Batal</button>
              <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
