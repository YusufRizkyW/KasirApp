<?php
$conn = oci_connect('C##KASIR', 'kasir123', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)
                        (PORT=1522))(CONNECT_DATA=(SERVICE_NAME=orcl)))');
if (!$conn) {
    $e = oci_error();
    error_log("Koneksi Oracle gagal: " . $e['message']);
    die("Koneksi database gagal.");
}
?>
