<?php
$conn = oci_connect('C##KASIR', 'kasir123', 'localhost:1521/orcl');
if (!$conn) {
    $e = oci_error();
    echo "Koneksi gagal: " . $e['message'];
} else {
    // echo "Koneksi berhasil!";
}
?>
