<?php
$conn = oci_connect('C##KASIR', 'kasir123', 'localhost:1522/orcl');
if (!$conn) {
    $e = oci_error();
    error_log("Koneksi Oracle gagal: " . $e['message']);
    die("Koneksi database gagal.");
}
?>

