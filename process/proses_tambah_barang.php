<?php
include '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode = $_POST['kode_barang'];
    $nama = $_POST['nama_barang'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga'];

    $query = "INSERT INTO TBL_BARANG (KODE_BARANG, NAMA_BARANG, KATEGORI, STOK, HARGA) 
              VALUES (:kode, :nama, :kategori, :stok, :harga)";
    
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":kode", $kode);
    oci_bind_by_name($stmt, ":nama", $nama);
    oci_bind_by_name($stmt, ":kategori", $kategori);
    oci_bind_by_name($stmt, ":stok", $stok);
    oci_bind_by_name($stmt, ":harga", $harga);

    $result = oci_execute($stmt);

    if ($result) {
        oci_commit($conn);
        header("Location: ../pages/DataBarang.php?status=added");
    } else {
        $e = oci_error($stmt);
        header("Location: ../pages/DataBarang.php?status=erroradding");
    }

    oci_free_statement($stmt);
    oci_close($conn);
    exit();


} else {
    echo "Akses tidak sah!";
}
?>
