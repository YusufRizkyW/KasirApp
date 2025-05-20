<?php
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $kode_lama = $_POST['kode_lama'];        // kode lama untuk WHERE
    $kode_baru = $_POST['kode_barang'];      // kode baru (hasil edit)
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $satuan = $_POST['satuan'];
    $harga = $_POST['harga'];

    // Query update
    $query = "UPDATE TBL_BARANG SET 
                KODE_BARANG = :kode_baru,
                NAMA_BARANG = :nama,
                KATEGORI = :kategori,
                STOK = :stok,
                SATUAN = :satuan,
                HARGA = :harga
              WHERE KODE_BARANG = :kode_lama";

    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ':kode_baru', $kode_baru);
    oci_bind_by_name($stmt, ':nama', $nama);
    oci_bind_by_name($stmt, ':kategori', $kategori);
    oci_bind_by_name($stmt, ':stok', $stok);
    oci_bind_by_name($stmt, ':satuan', $satuan);
    oci_bind_by_name($stmt, ':harga', $harga);
    oci_bind_by_name($stmt, ':kode_lama', $kode_lama);

    $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

    if ($result) {
        header('Location: ../pages/DataBarang.php?status=edited');
    } else {
        header('Location: ../pages/DataBarang.php?status=erroredit');
    }

    oci_free_statement($stmt);
    oci_close($conn);
} else {
    header('Location: ../pages/DataBarang.php');
    exit;
}
?>
