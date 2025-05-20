<?php
session_start();
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaksi'])) {
    $keranjang = json_decode($_POST['keranjang'], true);
    $kasir = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

    if (empty($keranjang)) {
        echo "<script>alert('Keranjang kosong!'); window.location.href='../pages/Transaksi.php';</script>";
        exit;
    }

    // Ambil ID transaksi dari sequence
    $stmt_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_TRANSAKSI.NEXTVAL) AS ID FROM DUAL");
    oci_execute($stmt_id);
    $row_id = oci_fetch_assoc($stmt_id);
    $id_transaksi = $row_id['ID'];
    oci_free_statement($stmt_id);

    // Hitung total dari keranjang
    $total = array_sum(array_column($keranjang, 'subtotal'));

    // Pastikan total_bayar ada, jika tidak menggunakan total
    $total_bayar = isset($_POST['total_bayar']) ? (float)$_POST['total_bayar'] : $total;

    // Debugging: Periksa nilai total dan total_bayar
    echo "Total Keranjang: " . $total . "<br>";
    echo "Total Bayar: " . $total_bayar . "<br>";

    // Hitung kembalian
    $kembalian = $total_bayar - $total;

    $kembalian = $_POST['kembalian']; // Ambil nilai kembalian
    
    // Debugging: Periksa nilai kembalian
    echo "Kembalian: " . $kembalian . "<br>";

    // Insert ke tabel transaksi
    $insert_transaksi = oci_parse($conn, "INSERT INTO TBL_TRANSAKSI 
        (ID_TRANSAKSI, TANGGAL, TOTAL, TOTAL_BAYAR, KASIR, KEMBALIAN)
        VALUES (:id_transaksi, SYSDATE, :total, :total_bayar, :kasir, :kembalian)");
            oci_bind_by_name($insert_transaksi, ':id_transaksi', $id_transaksi);
            oci_bind_by_name($insert_transaksi, ':total', $total);
            oci_bind_by_name($insert_transaksi, ':total_bayar', $total_bayar);
            oci_bind_by_name($insert_transaksi, ':kasir', $kasir);
            oci_bind_by_name($insert_transaksi, ':kembalian', $kembalian);  // Menyimpan kembalian
            if (!oci_execute($insert_transaksi)) {
                echo "<script>alert('Gagal menyimpan transaksi!'); window.location.href='../pages/Transaksi.php';</script>";
                exit;
            }
    oci_free_statement($insert_transaksi);

    // Insert detail transaksi dan update stok
    foreach ($keranjang as $item) {
        // Ambil ID_DETAIL dari sequence
        $stmt_detail_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_DETAIL.NEXTVAL) AS ID_DETAIL FROM DUAL");
        oci_execute($stmt_detail_id);
        $row_detail_id = oci_fetch_assoc($stmt_detail_id);
        $id_detail = $row_detail_id['ID_DETAIL'];
        oci_free_statement($stmt_detail_id);

        // Insert detail transaksi
        $insert_detail = oci_parse($conn, "INSERT INTO TBL_DETAIL_TRANSAKSI 
            (ID_DETAIL, ID_TRANSAKSI, KODE_BARANG, JUMLAH, SUBTOTAL)
            VALUES (:id_detail, :id_transaksi, :kode_barang, :jumlah, :subtotal)");
        oci_bind_by_name($insert_detail, ':id_detail', $id_detail);
        oci_bind_by_name($insert_detail, ':id_transaksi', $id_transaksi);
        oci_bind_by_name($insert_detail, ':kode_barang', $item['kode_barang']);
        oci_bind_by_name($insert_detail, ':jumlah', $item['jumlah']);
        oci_bind_by_name($insert_detail, ':subtotal', $item['subtotal']);
        oci_execute($insert_detail);
        oci_free_statement($insert_detail);

        // Update stok barang
        $update_stok = oci_parse($conn, "UPDATE TBL_BARANG SET STOK = STOK - :jumlah WHERE KODE_BARANG = :kode_barang");
        oci_bind_by_name($update_stok, ':jumlah', $item['jumlah']);
        oci_bind_by_name($update_stok, ':kode_barang', $item['kode_barang']);
        oci_execute($update_stok);
        oci_free_statement($update_stok);
    }

    oci_commit($conn);
    
    echo "<script>
        alert('Transaksi berhasil!');
        window.location.href='../pages/Transaksi.php';
    </script>";
}
?>
