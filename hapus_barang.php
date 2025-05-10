<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_barang'])) {
    $kode_barang = $_POST['kode_barang'];

    $delete = oci_parse($conn, "DELETE FROM TBL_BARANG WHERE KODE_BARANG = :kode");
    oci_bind_by_name($delete, ":kode", $kode_barang);
    $success = oci_execute($delete);

    if ($success) {
        header("Location: DataBarang.php?status=deleted");
    } else {
        header("Location: DataBarang.php?status=error");
    }
    exit();
} else {
    header("Location: DataBarang.php");
    exit();
}
?>