<?php
include '../config/koneksi.php';

if (!isset($_GET['kode'])) {
    echo "Kode barang tidak ditemukan.";
    exit;
}
$kode = $_GET['kode'];
$query = "SELECT * FROM TBL_BARANG WHERE KODE_BARANG = :kode";
$statement = oci_parse($conn, $query);
oci_bind_by_name($statement, ':kode', $kode);
oci_execute($statement);
$data = oci_fetch_assoc($statement);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Barang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
  <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-semibold mb-4">Edit Barang</h2>
    <form action="../process/proses_edit_barang.php" method="POST">

      <input type="hidden" name="kode_lama" value="<?= $data['KODE_BARANG'] ?>"> 

      <div class="mb-4">
        <label for="kode_barang">Kode Barang</label>
        <input type="text" name="kode_barang" id="kode_barang" value="<?= $data['KODE_BARANG'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>
      
      <div class="mb-4">
        <label>Nama Barang</label>
        <input type="text" name="nama" value="<?= $data['NAMA_BARANG'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>

      <div class="mb-4">
        <label for="kategori" class="block font-medium">Kategori</label>
        <select name="kategori" id="kategori" required class="w-full border px-3 py-2 rounded">
          <option value="">-- Pilih Kategori --</option>
          <option value="Alat Tulis Kantor" <?= $data['KATEGORI'] == 'Alat Tulis Kantor' ? 'selected' : '' ?>>Alat Tulis Kantor</option>
          <option value="Bahan Makanan" <?= $data['KATEGORI'] == 'Bahan Makanan' ? 'selected' : '' ?>>Bahan Makanan</option>
          <option value="Makanan" <?= $data['KATEGORI'] == 'Makanan' ? 'selected' : '' ?>>Makanan</option>
          <option value="Minuman" <?= $data['KATEGORI'] == 'Minuman' ? 'selected' : '' ?>>Minuman</option>
          <option value="Perlengkapan Mandi" <?= $data['KATEGORI'] == 'Perlengkapan Mandi' ? 'selected' : '' ?>>Perlengkapan Mandi</option>
          <option value="Snack" <?= $data['KATEGORI'] == 'Snack' ? 'selected' : '' ?>>Snack</option>
        </select>
      </div>

      <div class="mb-4">
        <label>Stok</label>
        <input type="number" name="stok" value="<?= $data['STOK'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>

      <div class="mb-4">
        <label for="satuan" class="block font-medium">Satuan</label>
        <select name="satuan" id="satuan" required class="w-full border px-3 py-2 rounded">
          <option value="">-- Pilih Satuan --</option>
          <option value="botol" <?= $data['SATUAN'] == 'botol' ? 'selected' : '' ?>>botol</option>
          <option value="dus" <?= $data['SATUAN'] == 'dus' ? 'selected' : '' ?>>dus</option>
          <option value="kg" <?= $data['SATUAN'] == 'kg' ? 'selected' : '' ?>>kg</option>
          <option value="liter" <?= $data['SATUAN'] == 'liter' ? 'selected' : '' ?>>liter</option>
          <option value="pak" <?= $data['SATUAN'] == 'pak' ? 'selected' : '' ?>>pak</option>
          <option value="pcs" <?= $data['SATUAN'] == 'pcs' ? 'selected' : '' ?>>pcs</option>
        </select>
      </div>
      
      <div class="mb-4">
        <label>Harga</label>
        <input type="number" name="harga" value="<?= $data['HARGA'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>

      <div class="flex justify-end">
        <a href="../pages/DataBarang.php" class="px-4 py-2 border mr-2">Batal</a>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
      </div>
    </form>
  </div>
</body>
</html>
