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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Barang</title>
    
    <!-- Styles & Fonts -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, #6d28d9 0%, #7c3aed 100%);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 10;
        }
        .sidebar-icon {
            width: 20px;
            text-align: center;
            margin-right: 12px;
        }
        .sidebar-item {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        .main-content {
            margin-left: 250px; /* Same as sidebar width */
            transition: all 0.3s;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex">
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="flex items-center space-x-3 p-6 mb-8">
                <div class="bg-white p-2 rounded-lg">
                    <i class="fas fa-cash-register text-purple-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold">KasirApp</h2>
            </div>

            <nav class="space-y-2 px-4">
                <a href="KasirDashboard.php" class="sidebar-item hover:text-white/90">
                    <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
                </a>
                <a href="DataBarang.php" class="sidebar-item">
                    <span class="sidebar-icon"><i class="fas fa-boxes-stacked"></i></span> Data Barang
                </a>
                <a href="Transaksi.php" class="sidebar-item active">
                    <span class="sidebar-icon"><i class="fas fa-cash-register"></i></span> Transaksi
                </a>
                <a href="Riwayat.php" class="sidebar-item hover:text-white/90">
                    <span class="sidebar-icon"><i class="fas fa-history"></i></span> Riwayat
                </a>
            </nav>
        </aside>

        <!-- Toggle Button for Mobile -->
        <button id="sidebarToggle" class="md:hidden fixed top-4 left-4 z-20 bg-purple-600 text-white p-2 rounded-md shadow-md">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main class="main-content flex-1 p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-cash-register text-purple-600 mr-3"></i> Transaksi Barang
                    </h1>
                    <p class="text-gray-500 mt-1">Transaksi penjualan barang dengan mudah</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="POST" action="../process/proses_transaksi.php" onsubmit="return handleSubmit()">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Form Input -->
                        <div class="space-y-4">
                            <div class="p-5 border border-purple-200 rounded-md bg-purple-50">
                                <h3 class="text-lg font-semibold text-purple-800 mb-4">Informasi Barang</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Barang:</label>
                                    <input type="text" id="kode_barang" class="w-full p-2 border border-purple-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang:</label>
                                    <input type="text" id="nama_barang" class="w-full p-2 border border-purple-200 bg-purple-50 rounded-md" readonly>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga:</label>
                                    <input type="number" id="harga" class="w-full p-2 border border-purple-200 bg-purple-50 rounded-md" readonly>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah:</label>
                                    <input type="number" id="jumlah" min="1" class="w-full p-2 border border-purple-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <button type="button" onclick="tambahBarang()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition duration-200 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Tambah Barang
                                </button>
                            </div>
                        </div>

                        <!-- Keranjang -->
                        <div>
                            <div class="border border-purple-200 rounded-md overflow-hidden">
                                <div class="bg-purple-100 px-4 py-3 border-b border-purple-200">
                                    <h3 class="font-semibold text-purple-800">Keranjang Belanja</h3>
                                </div>
                                <div class="overflow-x-auto" style="max-height: 300px; overflow-y: auto;">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-purple-50 text-sm text-left text-purple-700">
                                                <th class="px-4 py-2 border-b border-purple-100">Kode</th>
                                                <th class="px-4 py-2 border-b border-purple-100">Nama</th>
                                                <th class="px-4 py-2 border-b border-purple-100">Harga</th>
                                                <th class="px-4 py-2 border-b border-purple-100">Jumlah</th>
                                                <th class="px-4 py-2 border-b border-purple-100">Subtotal</th>
                                                <th class="px-4 py-2 border-b border-purple-100">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="keranjang_body"></tbody>
                                    </table>
                                </div>
                                <div class="bg-white p-4">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="text-right font-semibold text-gray-700">Total:</div>
                                        <div id="total_display" class="font-semibold text-purple-700">Rp 0</div>

                                        <div class="text-right font-semibold text-gray-700">Uang Bayar:</div>
                                        <div>
                                            <input type="number" id="uang_bayar" name="uang_bayar" class="border border-purple-300 p-2 w-full rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" oninput="hitungKembalian()">
                                        </div>
                                        
                                        <div class="text-right font-semibold text-gray-700">Kembalian:</div>
                                        <div>
                                            <input type="number" id="kembalian" name="kembalian" class="border border-purple-200 bg-purple-50 p-2 w-full rounded-md" readonly>
                                    </div>

                                    <input type="hidden" name="keranjang" id="keranjang_input">
                                    <input type="hidden" name="total_bayar" id="total_bayar">
                                    <input type="hidden" name="total" id="total_input">

                                    <div class="flex justify-end mt-6">
                                        <button type="submit" name="submit_transaksi" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md">Simpan Transaksi</button>
                                    </div>
                                    
                                    <!-- <div class="mt-6">
                                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md transition duration-200 flex items-center justify-center">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Proses Transaksi
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- JavaScript for functionality -->
    <script>
        // Load keranjang dari localStorage
        let keranjang = JSON.parse(localStorage.getItem('keranjang')) || [];
        const barang = <?php echo json_encode($barang); ?>;

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Autocomplete kode barang
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
                setTimeout(function() {
                    $("#jumlah").focus();
                }, 100);
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
            if (!barangData) {
                alert('Barang tidak ditemukan!');
                return;
            }

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
                keranjang.push({ 
                    kode_barang: kode, 
                    nama_barang: nama, 
                    harga: harga, 
                    jumlah: jumlah, 
                    subtotal: subtotal, 
                    satuan: satuan 
                });
            }
            renderKeranjang();

            // Reset form fields
            $('#kode_barang, #nama_barang, #harga, #jumlah').val('');
            // $('#kode_barang').focus();
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
                        <td class="border px-2 py-1"><button type="button" onclick="hapusItem(${index})" class="text-red-500">Hapus</button></td>
                    </tr>
                `);
                // tbody.append(`
                //     <tr>
                //         <td class="px-4 py-2 border-b">${item.kode_barang}</td>
                //         <td class="px-4 py-2 border-b">${item.nama_barang}</td>
                //         <td class="px-4 py-2 border-b">Rp ${item.harga.toLocaleString('id-ID')}</td>
                //         <td class="px-4 py-2 border-b">${item.jumlah} ${item.satuan}</td>
                //         <td class="px-4 py-2 border-b">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
                //         <td class="px-4 py-2 border-b">
                //             <button type="button" onclick="hapusItem(${index})" class="text-red-500 hover:text-red-700">
                //                 <i class="fas fa-trash"></i>
                //             </button>
                //         </td>
                //     </tr>
                // `);
            });

            $('#total_display').text(`Rp ${total}`);
            $('#uang_bayar').val('');
            $('#kembalian').val('');
            localStorage.setItem('keranjang', JSON.stringify(keranjang));
            // $('#total_display').text(Rp ${total.toLocaleString('id-ID')});
            // hitungKembalian();
            // localStorage.setItem('keranjang', JSON.stringify(keranjang));
        }
        
        function hapusItem(index) {
            keranjang.splice(index, 1);
            renderKeranjang();
        }
        
        function hitungKembalian() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            const uangBayar = parseInt($('#uang_bayar').val()) || 0;
            // const kembalian = uangBayar - total;
            if (!isNaN(uangBayar)) {
            const kembalian = uangBayar - total;
            $('#kembalian').val(kembalian >= 0 ? kembalian : 0);
        }
            // $('#kembalian_display').text(Rp ${Math.max(0, kembalian).toLocaleString('id-ID')});
        }
        
        function handleSubmit() {
            const total = keranjang.reduce((sum, item) => sum + item.subtotal, 0);
            const uangBayar = parseInt($('#uang_bayar').val()) || 0;

            if (keranjang.length === 0) {
                alert('Keranjang masih kosong!');
                return false;
            }

            if (isNaN(uangBayar) || uangBayar < total) {
                alert('Uang bayar tidak cukup!');
                return false;
            }

            // Add hidden inputs for transaction data
            // const form = document.querySelector('form');
        
             // Menghitung kembalian dan menampilkan pada input kembalian
            const kembalian = uangBayar - total;
            $('#kembalian').val(kembalian >= 0 ? kembalian : 0);

            // Menyimpan uang bayar dan kembalian ke dalam hidden input untuk dikirim ke server
            $('#total_bayar').val(uangBayar); // Uang bayar yang dimasukkan
            $('#keranjang_input').val(JSON.stringify(keranjang)); // Kirim keranjang dalam format JSON

            // Menghapus data keranjang dari localStorage setelah transaksi disimpan
            localStorage.removeItem('keranjang');

            // Add total belanja
            // const totalInput = document.createElement('input');
            // totalInput.type = 'hidden';
            // totalInput.name = 'total_belanja';
            // totalInput.value = total;
            // form.appendChild(totalInput);
            
            // // Add keranjang items as JSON
            // const itemsInput = document.createElement('input');
            // itemsInput.type = 'hidden';
            // itemsInput.name = 'items';
            // itemsInput.value = JSON.stringify(keranjang);
            // form.appendChild(itemsInput);

            // // Add uang bayar
            // const uangBayarInput = document.createElement('input');
            // uangBayarInput.type = 'hidden';
            // uangBayarInput.name = 'total_bayar';
            // uangBayarInput.value = uangBayar;
            // form.appendChild(uangBayarInput);
            
            // // Menghapus data keranjang dari localStorage setelah transaksi disimpan
            // localStorage.removeItem('keranjang');
            
            return true;
        }

        // Initialize keranjang display
        renderKeranjang();

        $('#uang_bayar').on('input', function () {
            hitungKembalian();
        });
    </script>
</body>
</html>