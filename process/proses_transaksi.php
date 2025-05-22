<?php
session_start();
include '../config/koneksi.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get cart data from form
        $keranjang = json_decode($_POST['keranjang'], true);
        $kasir = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
        
        // Validate cart is not empty
        if (empty($keranjang)) {
            $_SESSION['error_msg'] = 'Keranjang belanja kosong! Tidak dapat memproses transaksi.';
            header('Location: ../pages/Transaksi.php');
            exit;
        }
        
        // Get transaction ID from sequence
        $stmt_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_TRANSAKSI.NEXTVAL) AS ID FROM DUAL");
        if (!$stmt_id) {
            throw new Exception(oci_error($conn)['message']);
        }
        oci_execute($stmt_id);
        $row_id = oci_fetch_assoc($stmt_id);
        $id_transaksi = $row_id['ID'];
        oci_free_statement($stmt_id);
        
        // Calculate total from cart items
        $total = array_sum(array_column($keranjang, 'subtotal'));
        
        // Get payment amount and validate
        $total_bayar = isset($_POST['total_bayar']) && is_numeric($_POST['total_bayar']) 
            ? (float)$_POST['total_bayar'] 
            : 0;
            
        if ($total_bayar < $total) {
            $_SESSION['error_msg'] = 'Jumlah pembayaran kurang dari total belanja!';
            header('Location: ../pages/Transaksi.php');
            exit;
        }
        
        // Calculate change
        $kembalian = $total_bayar - $total;
        
        // Begin transaction
        oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));
        
        // Insert transaction record
        $insert_transaksi = oci_parse($conn, "INSERT INTO TBL_TRANSAKSI 
            (ID_TRANSAKSI, TANGGAL, TOTAL, TOTAL_BAYAR, KASIR, KEMBALIAN)
            VALUES (:id_transaksi, SYSDATE, :total, :total_bayar, :kasir, :kembalian)");
            
        oci_bind_by_name($insert_transaksi, ':id_transaksi', $id_transaksi);
        oci_bind_by_name($insert_transaksi, ':total', $total);
        oci_bind_by_name($insert_transaksi, ':total_bayar', $total_bayar);
        oci_bind_by_name($insert_transaksi, ':kasir', $kasir);
        oci_bind_by_name($insert_transaksi, ':kembalian', $kembalian);
        
        if (!oci_execute($insert_transaksi)) {
            $error = oci_error($insert_transaksi);
            oci_rollback($conn);
            throw new Exception("Gagal menyimpan transaksi: " . $error['message']);
        }
        oci_free_statement($insert_transaksi);
        
        // Process each item in cart
        foreach ($keranjang as $item) {
            // Get detail ID from sequence
            $stmt_detail_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_DETAIL.NEXTVAL) AS ID_DETAIL FROM DUAL");
            oci_execute($stmt_detail_id);
            $row_detail_id = oci_fetch_assoc($stmt_detail_id);
            $id_detail = $row_detail_id['ID_DETAIL'];
            oci_free_statement($stmt_detail_id);
            
            // Check stock availability
            $check_stock = oci_parse($conn, "SELECT STOK FROM TBL_BARANG WHERE KODE_BARANG = :kode_barang");
            oci_bind_by_name($check_stock, ':kode_barang', $item['kode_barang']);
            oci_execute($check_stock);
            $current_stock = oci_fetch_assoc($check_stock);
            oci_free_statement($check_stock);
            
            if (!$current_stock || $current_stock['STOK'] < $item['jumlah']) {
                oci_rollback($conn);
                throw new Exception("Stok untuk " . $item['nama_barang'] . " tidak mencukupi!");
            }
            
            // Insert transaction detail
            $insert_detail = oci_parse($conn, "INSERT INTO TBL_DETAIL_TRANSAKSI 
                (ID_DETAIL, ID_TRANSAKSI, KODE_BARANG, JUMLAH, SUBTOTAL)
                VALUES (:id_detail, :id_transaksi, :kode_barang, :jumlah, :subtotal)");
                
            oci_bind_by_name($insert_detail, ':id_detail', $id_detail);
            oci_bind_by_name($insert_detail, ':id_transaksi', $id_transaksi);
            oci_bind_by_name($insert_detail, ':kode_barang', $item['kode_barang']);
            oci_bind_by_name($insert_detail, ':jumlah', $item['jumlah']);
            oci_bind_by_name($insert_detail, ':subtotal', $item['subtotal']);
            
            if (!oci_execute($insert_detail)) {
                $error = oci_error($insert_detail);
                oci_rollback($conn);
                throw new Exception("Gagal menyimpan detail transaksi: " . $error['message']);
            }
            oci_free_statement($insert_detail);
            
            // Update item stock
            $update_stok = oci_parse($conn, "UPDATE TBL_BARANG SET STOK = STOK - :jumlah WHERE KODE_BARANG = :kode_barang");
            oci_bind_by_name($update_stok, ':jumlah', $item['jumlah']);
            oci_bind_by_name($update_stok, ':kode_barang', $item['kode_barang']);
            
            if (!oci_execute($update_stok)) {
                $error = oci_error($update_stok);
                oci_rollback($conn);
                throw new Exception("Gagal mengupdate stok barang: " . $error['message']);
            }
            oci_free_statement($update_stok);
        }
        
        // Commit the transaction
        oci_commit($conn);
        
        // Format amount for display in message
        function format_rupiah($amount) {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
        
        // Set success message with transaction details
        $_SESSION['success_msg'] = "Transaksi #" . $id_transaksi . " berhasil disimpan. " .
                                   "Total: " . format_rupiah($total) . ", " .
                                   "Bayar: " . format_rupiah($total_bayar) . ", " .
                                   "Kembalian: " . format_rupiah($kembalian);
        
        // Redirect to transaction page
        header('Location: ../pages/Transaksi.php');
        exit;
        
    } catch (Exception $e) {
        // Handle any exceptions
        $_SESSION['error_msg'] = $e->getMessage();
        header('Location: ../pages/Transaksi.php');
        exit;
    }
} else {
    // Handle direct access to this script
    $_SESSION['error_msg'] = 'Akses tidak sah!';
    header('Location: ../pages/Transaksi.php');
    exit;
}
?>