<?php
include '../config/koneksi.php';

// Membaca input JSON
$data = json_decode(file_get_contents("php://input"), true);

// Mengecek apakah data valid
if (!$data) {
    echo "Data tidak valid!";
    exit;
}

// Looping untuk setiap item transaksi
foreach ($data as $item) {
    // Query untuk memasukkan data transaksi
    $sql = "INSERT INTO TBL_TRANSAKSI (ID_TRANSAKSI, TANGGAL, TOTAL, KASIR) 
            VALUES (SEQ_TRANSAKSI.NEXTVAL, :kode, :jumlah, :subtotal)";
    
    // Menyiapkan statement
    $stmt = oci_parse($koneksi, $sql);
    
    // Menghitung subtotal (harga * jumlah)
    $subtotal = $item['harga'] * $item['jumlah'];
    
    // Mengikat parameter ke statement
    oci_bind_by_name($stmt, ":kode", $item['kode']);
    oci_bind_by_name($stmt, ":jumlah", $item['jumlah']);
    oci_bind_by_name($stmt, ":subtotal", $subtotal);
    
    // Eksekusi statement
    if (oci_execute($stmt)) {
        echo "Transaksi berhasil disimpan untuk kode: " . $item['kode'] . "<br>";
    } else {
        $error = oci_error($stmt);
        echo "Gagal menyimpan transaksi untuk kode " . $item['kode'] . ": " . $error['message'] . "<br>";
    }
}

echo "Semua transaksi telah diproses.";
?>
