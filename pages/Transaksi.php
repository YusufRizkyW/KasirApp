<?php
session_start();
include '../config/koneksi.php';

// Ambil data barang dari database
$barang = [];
$stmt = oci_parse($conn, "SELECT KODE_BARANG, NAMA_BARANG, HARGA, SATUAN, STOK FROM TBL_BARANG");
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $barang[] = $row;
}

oci_free_statement($stmt);
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
            <a href="Transaksi.php" class="block text-purple-600 font-bold">🛒 Transaksi</a>
            <a href="riwayat.php" class="block text-gray-700 hover:text-purple-600 font-medium">📄 Riwayat</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-6">Transaksi Barang</h1>

        <form method="POST" action="../process/proses_transaksi.php" onsubmit="return handleSubmit()">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block mb-1 font-semibold">Kode Barang:</label>
                    <input type="text" id="kode_barang" class="border w-full p-2 mb-2">

                    <label class="block mb-1 font-semibold">Nama Barang:</label>
                    <input type="text" id="nama_barang" class="border w-full p-2 mb-2" readonly>

                    <label class="block mb-1 font-semibold">Harga:</label>
                    <input type="number" id="harga" class="border w-full p-2 mb-2" readonly>

                    <label class="block mb-1 font-semibold">Jumlah:</label>
                    <input type="number" id="jumlah" class="border w-full p-2 mb-2" min="1">

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
                                <th class="border px-2 py-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="keranjang_body"></tbody>
                        <tfoot>
                            <tr class="bg-gray-100">
                                <td colspan="4" class="text-right font-semibold">Total:</td>
                                <td id="total_display" class="text-right font-semibold" colspan="2">Rp 0</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right font-semibold">Uang Bayar:</td>
                                <td colspan="2">
                                    <input type="number" id="uang_bayar" class="border p-2 w-full" oninput="hitungKembalian()">
                                </td>
                            </tr>
                            <tr class="bg-gray-100">
                                <td colspan="4" class="text-right font-semibold">Kembalian:</td>
                                <td id="kembalian_display" class="text-right font-semibold" colspan="2">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <input type="hidden" name="keranjang" id="keranjang_input">

            <div class="flex justify-end mt-6">
                <button type="submit" name="submit_transaksi" class="bg-green-600 text-white px-6 py-3 rounded-md">Simpan Transaksi</button>
            </div>
        </form>
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

        if (!kode || !nama || isNaN(harga) || isNaN(jumlah) || jumlah <= 0) {
            alert('Lengkapi data dan pastikan jumlah > 0');
            return;
        }

        const barangData = barang.find(b => b.KODE_BARANG === kode);
        const satuan = barangData ? barangData.SATUAN : '';
        const stok = barangData ? parseInt(barangData.STOK) : 0;
        const sudahDiKeranjang = keranjang.find(item => item.kode_barang === kode);
        const totalJumlah = sudahDiKeranjang ? sudahDiKeranjang.jumlah + jumlah : jumlah;
        if (stok > 0 && totalJumlah > stok) {
            alert('Stok tidak mencukupi!');
            return;
        }

        const subtotal = harga * jumlah;
        if (sudahDiKeranjang) {
            sudahDiKeranjang.jumlah += jumlah;
            sudahDiKeranjang.subtotal += subtotal;
        } else {
            keranjang.push({ kode_barang: kode, nama_barang: nama, harga: harga, jumlah: jumlah, subtotal: subtotal, satuan: satuan });
        }
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
                        <button type="button" onclick="hapusBarang(${index})" class="bg-red-500 text-white px-2 py-1 rounded">Hapus</button>
                    </td>
                </tr>
            `);
        });

        $('#total_display').text('Rp ' + total);
        $('#keranjang_input').val(JSON.stringify(keranjang));
        localStorage.setItem('keranjang', JSON.stringify(keranjang));
    }

    function hapusBarang(index) {
        keranjang.splice(index, 1);
        renderKeranjang();
    }

    function hitungKembalian() {
        const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
        const uangBayar = parseInt($('#uang_bayar').val());
        if (!isNaN(uangBayar)) {
            $('#kembalian_display').text('Rp ' + (uangBayar - total));
        }
    }

    function handleSubmit() {
        const uangBayar = parseInt($('#uang_bayar').val());
        const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
        if (keranjang.length === 0) {
            alert('Keranjang masih kosong!');
            return false;
        }
        if (isNaN(uangBayar) || uangBayar < total) {
            alert('Uang bayar tidak mencukupi!');
            return false;
        }
        document.getElementById('keranjang_input').value = JSON.stringify(keranjang);
        // Bersihkan keranjang di localStorage setelah submit
        localStorage.removeItem('keranjang');
        return true;
    }

    renderKeranjang();
</script>

</body>
</html>