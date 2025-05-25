<?php
session_start();
include '../config/koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $required_fields = ['keranjang', 'total_bayar', 'uang_bayar', 'kembalian', 'total'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }

    $keranjang_json = $_POST['keranjang'];
    $total_bayar = floatval($_POST['total_bayar']);
    $uang_bayar = floatval($_POST['uang_bayar']);
    $kembalian = floatval($_POST['kembalian']);
    $total = floatval($_POST['total']);

    $keranjang = json_decode($keranjang_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid keranjang data format');
    }

    if (empty($keranjang)) {
        throw new Exception('Keranjang tidak boleh kosong');
    }

    if ($uang_bayar < $total_bayar) {
        throw new Exception('Uang bayar tidak mencukupi');
    }

    // Generate ID transaksi
    $id_transaksi = 'TRX' . date('YmdHis') . rand(100, 999);
    $kasir = $_SESSION['username'] ?? 'admin';

    // Insert transaksi utama
    $sql_transaksi = "INSERT INTO TBL_TRANSAKSI (ID_TRANSAKSI, TANGGAL, TOTAL, KASIR, TOTAL_BAYAR, KEMBALIAN)
                      VALUES (:id_transaksi, SYSDATE, :total, :kasir, :total_bayar, :kembalian)";

    $stmt_transaksi = oci_parse($conn, $sql_transaksi);
    oci_bind_by_name($stmt_transaksi, ':id_transaksi', $id_transaksi);
    oci_bind_by_name($stmt_transaksi, ':total', $total);
    oci_bind_by_name($stmt_transaksi, ':kasir', $kasir);
    oci_bind_by_name($stmt_transaksi, ':total_bayar', $total_bayar);
    oci_bind_by_name($stmt_transaksi, ':kembalian', $kembalian);

    if (!oci_execute($stmt_transaksi, OCI_NO_AUTO_COMMIT)) {
        $error = oci_error($stmt_transaksi);
        throw new Exception('Error inserting transaction: ' . $error['message']);
    }

    // Detail transaksi
    foreach ($keranjang as $item) {
        // Cek stok
        $sql_check_stok = "SELECT STOK FROM TBL_BARANG WHERE KODE_BARANG = :kode_barang";
        $stmt_check = oci_parse($conn, $sql_check_stok);
        oci_bind_by_name($stmt_check, ':kode_barang', $item['kode']);
        oci_execute($stmt_check, OCI_NO_AUTO_COMMIT);
        
        $row_stok = oci_fetch_assoc($stmt_check);
        if (!$row_stok) {
            throw new Exception("Barang dengan kode {$item['kode']} tidak ditemukan");
        }

        if ($row_stok['STOK'] < $item['jumlah']) {
            throw new Exception("Stok barang {$item['nama']} tidak mencukupi");
        }

        // Insert detail
        $id_detail = 'DTL' . date('YmdHis') . rand(100, 999);
        $sql_detail = "INSERT INTO TBL_DETAIL_TRANSAKSI (ID_DETAIL, ID_TRANSAKSI, KODE_BARANG, JUMLAH, SUBTOTAL)
                       VALUES (:id_detail, :id_transaksi, :kode_barang, :jumlah, :subtotal)";
        
        $stmt_detail = oci_parse($conn, $sql_detail);
        oci_bind_by_name($stmt_detail, ':id_detail', $id_detail);
        oci_bind_by_name($stmt_detail, ':id_transaksi', $id_transaksi);
        oci_bind_by_name($stmt_detail, ':kode_barang', $item['kode']);
        oci_bind_by_name($stmt_detail, ':jumlah', $item['jumlah']);
        oci_bind_by_name($stmt_detail, ':subtotal', $item['subtotal']);

        if (!oci_execute($stmt_detail, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt_detail);
            throw new Exception('Error inserting transaction detail: ' . $error['message']);
        }

        // Update stok
        $sql_update_stok = "UPDATE TBL_BARANG SET STOK = STOK - :jumlah WHERE KODE_BARANG = :kode_barang";
        $stmt_update = oci_parse($conn, $sql_update_stok);
        oci_bind_by_name($stmt_update, ':jumlah', $item['jumlah']);
        oci_bind_by_name($stmt_update, ':kode_barang', $item['kode']);

        if (!oci_execute($stmt_update, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt_update);
            throw new Exception('Error updating stock: ' . $error['message']);
        }

        // Free statement
        oci_free_statement($stmt_check);
        oci_free_statement($stmt_detail);
        oci_free_statement($stmt_update);
    }

    // Commit transaksi
    oci_commit($conn);
    oci_free_statement($stmt_transaksi);

    $_SESSION['success_msg'] = "Transaksi berhasil dengan ID: {$id_transaksi}";
    $_SESSION['last_transaction'] = [
        'id_transaksi' => $id_transaksi,
        'items' => $keranjang,
        'total' => $total,
        'uang_bayar' => $uang_bayar,
        'kembalian' => $kembalian,
        'tanggal' => date('Y-m-d H:i:s')
    ];

    header('Location: ../pages/Transaksi.php');
    exit();

} catch (Exception $e) {
    if (isset($conn)) {
        oci_rollback($conn);
    }

    error_log("Transaction Error: " . $e->getMessage());
    $_SESSION['error_msg'] = $e->getMessage();
    header('Location: ../pages/Transaksi.php');
    exit();
}
?>
