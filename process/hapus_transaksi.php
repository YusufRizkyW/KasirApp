<?php
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_transaksi'])) {
    $id_transaksi = $_POST['id_transaksi'];

    // Hapus dari detail transaksi dulu (karena foreign key)
    $query_detail = oci_parse($conn, "DELETE FROM TBL_DETAIL_TRANSAKSI WHERE ID_TRANSAKSI = :id_transaksi");
    oci_bind_by_name($query_detail, ':id_transaksi', $id_transaksi);
    $detail_deleted = oci_execute($query_detail);

    // Hapus transaksi utama
    $query_transaksi = oci_parse($conn, "DELETE FROM TBL_TRANSAKSI WHERE ID_TRANSAKSI = :id_transaksi");
    oci_bind_by_name($query_transaksi, ':id_transaksi', $id_transaksi);
    $transaksi_deleted = oci_execute($query_transaksi);

    oci_free_statement($query_detail);
    oci_free_statement($query_transaksi);
    oci_close($conn);

    if ($detail_deleted && $transaksi_deleted) {
        header("Location: ../pages/Riwayat.php");
        exit;
    } else {
        echo "Gagal menghapus transaksi.";
    }
} else {
    echo "Permintaan tidak valid.";
}
?>
