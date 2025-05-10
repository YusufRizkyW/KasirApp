<?php
include '../config/koneksi.php';

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
      <input type="hidden" name="kode" value="<?= $data['KODE_BARANG'] ?>">
      <div class="mb-4">
        <label>Nama Barang</label>
        <input type="text" name="nama" value="<?= $data['NAMA_BARANG'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>
      <div class="mb-4">
        <label>Kategori</label>
        <input type="text" name="kategori" value="<?= $data['KATEGORI'] ?>" required class="w-full border px-3 py-2 rounded">
      </div>
      <div class="mb-4">
        <label>Stok</label>
        <input type="number" name="stok" value="<?= $data['STOK'] ?>" required class="w-full border px-3 py-2 rounded">
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
