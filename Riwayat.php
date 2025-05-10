<?php
include 'koneksi.php';

// Ambil riwayat transaksi
$query_transaksi = oci_parse($conn, "SELECT ID_TRANSAKSI, TANGGAL, TOTAL, KASIR FROM TBL_TRANSAKSI ORDER BY TANGGAL DESC");
oci_execute($query_transaksi);

// Ambil detail transaksi per transaksi
$riwayat = [];
while ($transaksi = oci_fetch_assoc($query_transaksi)) {
    $id_transaksi = $transaksi['ID_TRANSAKSI'];

    // Ambil detail transaksi lengkap (pakai join untuk dapat nama barang)
    $query_detail = oci_parse($conn, "
        SELECT d.KODE_BARANG, b.NAMA_BARANG, d.JUMLAH, d.SUBTOTAL
        FROM TBL_DETAIL_TRANSAKSI d
        JOIN TBL_BARANG b ON d.KODE_BARANG = b.KODE_BARANG
        WHERE d.ID_TRANSAKSI = :id_transaksi
    ");
    oci_bind_by_name($query_detail, ':id_transaksi', $id_transaksi);
    oci_execute($query_detail);

    $detail_transaksi = [];
    while ($detail = oci_fetch_assoc($query_detail)) {
        $detail_transaksi[] = $detail;
    }

    $transaksi['DETAIL'] = $detail_transaksi;
    $riwayat[] = $transaksi;
}

oci_free_statement($query_transaksi);
oci_free_statement($query_detail);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-6">
        <h2 class="text-2xl font-bold mb-10 text-purple-600">KasirApp</h2>
        <nav class="space-y-4">
            <a href="KasirDashboard.php" class="block text-gray-700 hover:text-purple-600 font-medium">🏠 Dashboard</a>
            <a href="DataBarang.php" class="block text-gray-700 hover:text-purple-600 font-medium">📦 Data Barang</a>
            <a href="Transaksi.php" class="block text-gray-700 hover:text-purple-600 font-medium">🛒 Transaksi</a>
            <a href="riwayat.php" class="block text-gray-700 hover:text-purple-600 font-medium">📄 Riwayat</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-6">Riwayat Transaksi</h1>

        <!-- Tabel Riwayat Transaksi -->
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-sm text-left">
                    <th class="border px-4 py-2">ID Transaksi</th>
                    <th class="border px-4 py-2">Tanggal</th>
                    <th class="border px-4 py-2">Total</th>
                    <th class="border px-4 py-2">Kasir</th>
                    <th class="border px-4 py-2">Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $transaksi): ?>
                    <tr class="bg-white">
                        <td class="border px-4 py-2"><?= $transaksi['ID_TRANSAKSI'] ?></td>
                        <td class="border px-4 py-2"><?= date("d-m-Y H:i:s", strtotime($transaksi['TANGGAL'])) ?></td>
                        <td class="border px-4 py-2">Rp <?= number_format($transaksi['TOTAL'], 0, ',', '.') ?></td>
                        <td class="border px-4 py-2"><?= $transaksi['KASIR'] ?></td>
                        <td class="border px-4 py-2">
                            <button onclick="toggleDetail(<?= $transaksi['ID_TRANSAKSI'] ?>)" class="bg-blue-500 text-white px-4 py-2 rounded">Lihat Detail</button>
                        </td>
                    </tr>

                    <!-- Detail Transaksi -->
                    <tr id="detail_<?= $transaksi['ID_TRANSAKSI'] ?>" style="display:none;">
                        <td colspan="5" class="border px-4 py-2 bg-gray-50">
                            <?php if (count($transaksi['DETAIL']) > 0): ?>
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border px-4 py-2">Kode Barang</th>
                                            <th class="border px-4 py-2">Nama Barang</th>
                                            <th class="border px-4 py-2">Jumlah</th>
                                            <th class="border px-4 py-2">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transaksi['DETAIL'] as $detail): ?>
                                            <tr>
                                                <td class="border px-4 py-2"><?= $detail['KODE_BARANG'] ?></td>
                                                <td class="border px-4 py-2"><?= $detail['NAMA_BARANG'] ?></td>
                                                <td class="border px-4 py-2"><?= $detail['JUMLAH'] ?></td>
                                                <td class="border px-4 py-2">Rp <?= number_format($detail['SUBTOTAL'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500">Tidak ada detail untuk transaksi ini.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    function toggleDetail(id_transaksi) {
        const detailRow = document.getElementById('detail_' + id_transaksi);
        if (detailRow.style.display === "none") {
            detailRow.style.display = "table-row";
        } else {
            detailRow.style.display = "none";
        }
    }
</script>

</body>
</html>
