<?php
include '../config/koneksi.php';

$kode = $_GET['kode'];
$sql = "SELECT * FROM TBL_BARANG WHERE KODE_BARANG = :kode";
$stid = oci_parse($koneksi, $sql);
oci_bind_by_name($stid, ":kode", $kode);
oci_execute($stid);

$row = oci_fetch_assoc($stid);
if ($row) {
    echo json_encode([
        "nama_barang" => $row["NAMA_BARANG"],
        "harga" => $row["HARGA"]
    ]);
} else {
    echo json_encode(null);
}
?>
