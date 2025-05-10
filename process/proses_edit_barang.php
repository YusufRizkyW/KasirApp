<?php
include '../config/koneksi.php';

$kode = $_POST['kode'];
$nama = $_POST['nama'];
$kategori = $_POST['kategori'];
$stok = $_POST['stok'];
$harga = $_POST['harga'];

$query = "UPDATE TBL_BARANG 
          SET NAMA_BARANG = :nama, KATEGORI = :kategori, STOK = :stok, HARGA = :harga 
          WHERE KODE_BARANG = :kode";
$statement = oci_parse($conn, $query);
oci_bind_by_name($statement, ':nama', $nama);
oci_bind_by_name($statement, ':kategori', $kategori);
oci_bind_by_name($statement, ':stok', $stok);
oci_bind_by_name($statement, ':harga', $harga);
oci_bind_by_name($statement, ':kode', $kode);

if (oci_execute($statement)) {
  header("Location: ../pages/DataBarang.php?status=edited");
} else {
  echo "Gagal update data!";
}
oci_close($conn);
