<?php
include '../config/koneksi.php';

// Ambil data barang untuk autocomplete
$query_barang = oci_parse($conn, "SELECT KODE_BARANG, NAMA_BARANG, HARGA, SATUAN FROM TBL_BARANG");
oci_execute($query_barang);

$barang = [];
while ($row = oci_fetch_assoc($query_barang)) {
    $barang[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaksi'])) {
    if (isset($_POST['keranjang'])) {
        $keranjang = json_decode($_POST['keranjang'], true); // Decode data JSON dari input hidden
        $kasir = 'Admin'; // Bisa diganti sesuai session login

        if (empty($keranjang)) {
            echo "<script>alert('Keranjang kosong!');</script>";
        } else {
            // Lanjutkan dengan proses transaksi
            include '../config/koneksi.php';
            $stmt_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_TRANS.NEXTVAL) AS ID FROM DUAL");
            oci_execute($stmt_id);
            $row_id = oci_fetch_assoc($stmt_id);
            $id_transaksi = $row_id['ID'];

            // Hitung total
            $total = array_sum(array_column($keranjang, 'subtotal'));

            // Mulai transaksi
            oci_parse($conn, "BEGIN");

            // Simpan ke tabel transaksi
            $insert_transaksi = oci_parse($conn, "INSERT INTO TBL_TRANSAKSI (ID_TRANSAKSI, TANGGAL, TOTAL, KASIR)
                                                  VALUES (:id_transaksi, SYSDATE, :total, :kasir)");
            oci_bind_by_name($insert_transaksi, ':id_transaksi', $id_transaksi);
            oci_bind_by_name($insert_transaksi, ':total', $total);
            oci_bind_by_name($insert_transaksi, ':kasir', $kasir);
            oci_execute($insert_transaksi);

            // Simpan detail transaksi dalam satu loop
            foreach ($keranjang as $item) {
                $insert_detail = oci_parse($conn, "INSERT INTO TBL_DETAIL_TRANSAKSI 
                    (ID_TRANSAKSI, KODE_BARANG, JUMLAH, SUBTOTAL)
                    VALUES (:id_transaksi, :kode_barang, :jumlah, :subtotal)");
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

            // Commit transaksi
            oci_parse($conn, "COMMIT");

            echo "<script>
                    alert('Transaksi berhasil!'); 
                    localStorage.removeItem('keranjang');
                    window.location.href='Transaksi.php';
                </script>";
        }

        oci_free_statement($insert_transaksi);
        oci_free_statement($stmt_id);
    } else {
        echo "<script>alert('Keranjang tidak ditemukan!');</script>";
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Barang</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
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
        <h1 class="text-3xl font-bold mb-6">Transaksi Barang</h1>

        <form method="POST" action="Transaksi.php" onsubmit="syncKeranjang()">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block mb-1 font-semibold">Kode Barang:</label>
                    <input type="text" id="kode_barang" class="border w-full p-2 mb-2">

                    <label class="block mb-1 font-semibold">Nama Barang:</label>
                    <input type="text" id="nama_barang" class="border w-full p-2 mb-2" readonly>

                    <label class="block mb-1 font-semibold">Harga:</label>
                    <input type="number" id="harga" class="border w-full p-2 mb-2" readonly>

                    <label class="block mb-1 font-semibold">Jumlah:</label>
                    <input type="number" id="jumlah" class="border w-full p-2 mb-2">

                    <button type="button" onclick="tambahBarang()" class="bg-blue-500 text-white px-4 py-2 rounded">Tambah Barang</button>
                </div>

                <div>
                    <table class="w-full border">
                        <thead>
                            <tr class="bg-gray-200 text-sm text-left">
                                <th class="border px-2 py-1">Kode</th>
                                <th class="border px-2 py-1">Nama</th>
                                <th class="border px-2 py-1">Harga</th>
                                <th class="border px-2 py-1">Jumlah</th>
                                <th class="border px-2 py-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="keranjang_body"></tbody>
                        <tfoot>
                            <tr class="bg-gray-100">
                                <td colspan="4" class="text-right font-semibold">Total:</td>
                                <td id="total_display" class="text-right font-semibold">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right font-semibold">Uang Bayar:</td>
                                <td>
                                    <input type="number" id="uang_bayar" class="border p-2 w-full" oninput="hitungKembalian()">
                                </td>
                            </tr>
                            <tr class="bg-gray-100">
                                <td colspan="4" class="text-right font-semibold">Kembalian:</td>
                                <td id="kembalian_display" class="text-right font-semibold">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <input type="hidden" name="keranjang" id="keranjang_input">

            <!-- Tombol Simpan Transaksi di bawah kanan -->
            <div class="flex justify-end mt-6">
                <button type="submit" name="submit_transaksi" class="bg-green-600 text-white px-6 py-3 rounded-md">Simpan Transaksi</button>
            </div>
        </form>

        <!-- Proses simpan transaksi di sini -->
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaksi'])) {
            $keranjang = json_decode($_POST['keranjang'], true);
            $kasir = 'Admin'; // Bisa diganti sesuai session login

            if (empty($keranjang)) {
                echo "<script>alert('Keranjang kosong!');</script>";
            } else {
                // Ambil ID transaksi dari sequence
                $conn = oci_connect('username', 'password', 'localhost/XE');
                $stmt_id = oci_parse($conn, "SELECT TO_CHAR(SEQ_TRANS.NEXTVAL) AS ID FROM DUAL");
                oci_execute($stmt_id);
                $row_id = oci_fetch_assoc($stmt_id);
                $id_transaksi = $row_id['ID'];

                // Hitung total
                $total = array_sum(array_column($keranjang, 'subtotal'));

                // Mulai transaksi
                oci_parse($conn, "BEGIN");

                // Simpan ke tabel transaksi
                $insert_transaksi = oci_parse($conn, "INSERT INTO TBL_TRANSAKSI (ID_TRANSAKSI, TANGGAL, TOTAL, KASIR)
                                                      VALUES (:id_transaksi, SYSDATE, :total, :kasir)");
                oci_bind_by_name($insert_transaksi, ':id_transaksi', $id_transaksi);
                oci_bind_by_name($insert_transaksi, ':total', $total);
                oci_bind_by_name($insert_transaksi, ':kasir', $kasir);
                oci_execute($insert_transaksi);

                // Simpan detail transaksi dalam satu loop
                foreach ($keranjang as $item) {
                    $insert_detail = oci_parse($conn, "INSERT INTO TBL_DETAIL_TRANSAKSI 
                        (ID_TRANSAKSI, KODE_BARANG, JUMLAH, SUBTOTAL)
                        VALUES (:id_transaksi, :kode_barang, :jumlah, :subtotal)");
                    oci_bind_by_name($insert_detail, ':id_transaksi', $id_transaksi);
                    oci_bind_by_name($insert_detail, ':kode_barang', $item['kode_barang']);
                    oci_bind_by_name($insert_detail, ':jumlah', $item['jumlah']);
                    oci_bind_by_name($insert_detail, ':subtotal', $item['subtotal']);
                    oci_execute($insert_detail);
                    oci_free_statement($insert_detail);
                }

                // Commit transaksi
                // oci_parse($conn, "COMMIT");
                oci_commit($conn);

                echo "<script>alert('Transaksi berhasil!'); window.location.href='Transaksi.php';</script>";
            }

            oci_free_statement($insert_transaksi);
            oci_free_statement($stmt_id);
        }
        ?>
    </main>
</div>

<script>
    let keranjang = JSON.parse(localStorage.getItem('keranjang')) || [];
    const barang = <?php echo json_encode($barang); ?>;

    $("#kode_barang").autocomplete({
        source: function(request, response) {
            const results = barang.filter(b =>
                b.KODE_BARANG.includes(request.term.toUpperCase()) ||
                b.NAMA_BARANG.toLowerCase().includes(request.term.toLowerCase())
            );
            response(results.map(b => ({
                label: `${b.KODE_BARANG} - ${b.NAMA_BARANG} - Rp ${b.HARGA}`,
                value: b.KODE_BARANG,
                harga: b.HARGA,
                nama: b.NAMA_BARANG
            })));
        },
        select: function(event, ui) {
            $('#kode_barang').val(ui.item.value);
            $('#nama_barang').val(ui.item.nama);
            $('#harga').val(ui.item.harga);
        }
    });

    function tambahBarang() {
        const kode = $('#kode_barang').val();
        const nama = $('#nama_barang').val();
        const harga = parseInt($('#harga').val());
        const jumlah = parseInt($('#jumlah').val());

        if (!kode || !nama || isNaN(harga) || isNaN(jumlah)) {
            alert('Lengkapi semua data barang!');
            return;
        }

        // Ambil satuan dari array barang
        const barangData = barang.find(b => b.KODE_BARANG === kode);
        const satuan = barangData ? barangData.SATUAN : '';

        const subtotal = harga * jumlah;
        keranjang.push({ kode_barang: kode, nama_barang: nama, harga: harga, jumlah: jumlah, subtotal: subtotal, satuan: satuan });
        renderKeranjang();

        $('#kode_barang, #nama_barang, #harga, #jumlah').val('');
    }

    function renderKeranjang() {
        const tbody = $('#keranjang_body');
        tbody.empty();
        let total = 0;

        keranjang.forEach((item, index) => {
            total += item.subtotal;
            tbody.append(`
                <tr>
                    <td class="border px-2 py-1">${item.kode_barang}</td>
                    <td class="border px-2 py-1">${item.nama_barang}</td>
                    <td class="border px-2 py-1">Rp ${item.harga}</td>
                    <td class="border px-2 py-1">${item.jumlah} ${item.satuan}</td>
                    <td class="border px-2 py-1">Rp ${item.subtotal}</td>
                    <td class="border px-2 py-1">
                        <button onclick="hapusBarang(${index})" class="bg-red-500 text-white px-2 py-1 rounded">Hapus</button>
                    </td>
                </tr>
            `);
        });

        $('#total_display').text('Rp ' + total);
        $('#keranjang_input').val(JSON.stringify(keranjang));

        // Save keranjang to localStorage
        localStorage.setItem('keranjang', JSON.stringify(keranjang));
    }

    function hapusBarang(index) {
        // Hapus item dari keranjang
        keranjang.splice(index, 1);
        renderKeranjang(); // Render ulang keranjang setelah penghapusan
    }


    function hitungKembalian() {
        const total = parseInt($('#total_display').text().replace('Rp ', '').replace(',', ''));
        const uangBayar = parseInt($('#uang_bayar').val());
        if (!isNaN(uangBayar)) {
            $('#kembalian_display').text('Rp ' + (uangBayar - total));
        }
    }

    function syncKeranjang() {
        document.getElementById('keranjang_input').value = JSON.stringify(keranjang);
    }
</script>

</body>
</html>
