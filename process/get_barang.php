<?php
include '../config/koneksi.php';

$kode = isset($_GET['kode']) ? $_GET['kode'] : '';

$query = "SELECT KODE_BARANG, NAMA_BARANG, HARGA FROM TBL_BARANG 
          WHERE LOWER(KODE_BARANG) LIKE LOWER(:kode) OR LOWER(NAMA_BARANG) LIKE LOWER(:kode)";
$stmt = oci_parse($conn, $query);
$searchTerm = "%" . $kode . "%";
oci_bind_by_name($stmt, ":kode", $searchTerm);
oci_execute($stmt);

$data = [];
while ($row = oci_fetch_assoc($stmt)) {
    $data[] = [
        'label' => $row['KODE_BARANG'] . ' - ' . $row['NAMA_BARANG'],
        'value' => $row['KODE_BARANG'],
        'kode_barang' => $row['KODE_BARANG'],
        'nama_barang' => $row['NAMA_BARANG'],
        'harga' => $row['HARGA']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
