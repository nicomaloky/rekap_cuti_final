<?php
// Menghubungkan ke file database untuk melakukan query
require_once __DIR__ . '/../database.php';

// --- LOGIKA UNTUK FILTER WAKTU ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_date_clause = '';
switch ($filter) {
    case '7d':
        $where_date_clause = "AND c.tgl_pengajuan >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '1m':
        $where_date_clause = "AND c.tgl_pengajuan >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case '3m':
        $where_date_clause = "AND c.tgl_pengajuan >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        break;
    case '1y':
        $where_date_clause = "AND c.tgl_pengajuan >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case 'all':
    default:
        $where_date_clause = '';
        break;
}

// --- LOGIKA UNTUK SORTING ---
$allowed_sort_columns = [
    'nama' => 'p.nama',
    'unit_kerja' => 'p.unit_kerja',
    'jenis_cuti' => 'jc.nama_cuti',
    'durasi' => 'c.lama_cuti',
    'status' => 'c.pertimbangan_atasan',
    'tanggal' => 'c.tgl_mulai',
    'pengajuan' => 'c.tgl_pengajuan'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sort_columns) ? $_GET['sort'] : 'tanggal';
$sort_column = $allowed_sort_columns[$sort_key];
$sort_order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';

// Fungsi untuk membuat link sorting
function generateSortLinkLaporan($key, $display_text, $current_key, $current_order, $current_filter) {
    $order_for_link = ($current_key == $key && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($current_key == $key) {
        $icon = $current_order == 'asc' ? ' <span class="text-gray-900">&uarr;</span>' : ' <span class="text-gray-900">&darr;</span>';
    }
    $search_param = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
    $filter_param = ($current_filter != 'all') ? '&filter=' . urlencode($current_filter) : '';
    return "<a href='?page=laporan_cuti&sort=$key&order=$order_for_link$search_param$filter_param' class='hover:text-gray-900'>$display_text$icon</a>";
}

// --- QUERY UTAMA ---
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = "%" . $search_query . "%";

$sql = "
    SELECT
        c.id,
        p.nama,
        p.nip,
        p.unit_kerja,
        jc.nama_cuti,
        c.tgl_mulai,
        c.tgl_selesai,
        c.lama_cuti,
        c.alasan_cuti,
        c.tgl_pengajuan,
        c.pertimbangan_atasan
    FROM cuti c
    JOIN pegawai p ON c.pegawai_id = p.id
    LEFT JOIN jenis_cuti jc ON c.jenis_cuti_id = jc.id
    WHERE c.is_deleted = 0 $where_date_clause
";
if (!empty($search_query)) {
    $sql .= " AND (p.nama LIKE ? OR p.nip LIKE ? OR jc.nama_cuti LIKE ? OR p.unit_kerja LIKE ?)";
}
$sql .= " ORDER BY $sort_column " . strtoupper($sort_order);

$stmt = $conn->prepare($sql);

if (!empty($search_query)) {
    $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
}

$stmt->execute();
$result = $stmt->get_result();
$all_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- KALKULASI DATA RINGKASAN ---
$total_records = count($all_data);
$total_hari_cuti = 0;
$unique_pegawai = [];
foreach ($all_data as $row) {
    $total_hari_cuti += $row['lama_cuti'];
    if (!in_array($row['nip'], $unique_pegawai)) {
        $unique_pegawai[] = $row['nip'];
    }
}
$total_unique_pegawai = count($unique_pegawai);

?>
<div class="bg-white p-8 rounded-lg shadow-lg w-full">
    <!-- Header Halaman -->
    <div class="no-print flex flex-wrap justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Lengkap Data Cuti</h1>
            <p class="text-gray-600">Menampilkan seluruh riwayat cuti dari sistem.</p>
        </div>
        <div class="flex space-x-2">
            <a href="export_laporan.php?<?php echo http_build_query($_GET); ?>" class="bg-blue-800 hover:bg-blue-950 text-white font-bold py-2 px-4 rounded-md flex items-center shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Export ke Excel
            </a>
            <button onclick="window.print()" class="bg-green-600 hover:bg-green-950 text-white font-bold py-2 px-4 rounded-md flex items-center shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Cetak Laporan
            </button>
        </div>
    </div>
    
    <!-- Fitur Filter & Pencarian -->
    <div class="mb-6 no-print flex flex-wrap justify-between items-end gap-4">
        <form action="index.php" method="GET" class="max-w-md flex-grow">
            <input type="hidden" name="page" value="laporan_cuti">
            <label for="search_laporan" class="block text-sm font-medium text-gray-700 mb-1">Cari Laporan</label>
            <div class="flex items-center shadow-md rounded-md">
                <input type="text" id="search_laporan" name="search" class="block w-full border-gray-300 rounded-l-md py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ketik Nama, NIP, Unit Kerja..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="bg-blue-800 text-white px-4 py-2 rounded-r-md hover:bg-blue-700">Cari</button>
            </div>
        </form>
        <form id="filter-form" action="index.php" method="GET">
            <input type="hidden" name="page" value="laporan_cuti">
            <?php if (isset($_GET['search'])): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>"><?php endif; ?>
            <label for="filter-waktu" class="block text-sm font-medium text-gray-700 align text-right mb-1">Filter Waktu</label>
            <select id="filter-waktu" name="filter" onchange="this.form.submit()" class="block w-full border-gray-300 rounded-md shadow-md py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>Semua Waktu</option>
                <option value="7d" <?php if ($filter == '7d') echo 'selected'; ?>>7 Hari Terakhir</option>
                <option value="1m" <?php if ($filter == '1m') echo 'selected'; ?>>1 Bulan Terakhir</option>
                <option value="3m" <?php if ($filter == '3m') echo 'selected'; ?>>3 Bulan Terakhir</option>
                <option value="1y" <?php if ($filter == '1y') echo 'selected'; ?>>1 Tahun Terakhir</option>
            </select>
        </form>
    </div>

    <!-- Header Cetak -->
    <div class="print-only mb-6 text-center">
        <h1 class="text-xl font-bold">Laporan Rekapitulasi Cuti Pegawai</h1>
        <h2 class="text-lg">Dinas Pendidikan Kota Bogor</h2>
        <p class="text-sm">Dicetak pada: <?php echo date('d F Y'); ?></p>
    </div>

    <!-- Kontainer Tabel Scrollable -->
    <div class="overflow-auto h-[60vh] border rounded-lg shadow-inner table-scroll-container">
        <table id="laporan-cuti-table" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('nama', 'Nama / NIP', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('unit_kerja', 'Unit Kerja', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('jenis_cuti', 'Jenis Cuti', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('pengajuan', 'Tgl Pengajuan', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('durasi', 'Durasi', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLinkLaporan('status', 'Status', $sort_key, $sort_order, $filter); ?></th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                <?php
                if ($total_records > 0) {
                    $nomor = 1;
                    foreach($all_data as $row) {
                ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-2 py-2 whitespace-nowrap"><?php echo $nomor++; ?></td>
                            <td class="px-3 py-2 whitespace-nowrap"><div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></div><div class="text-gray-500"><?php echo htmlspecialchars($row['nip']); ?></div></td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($row['unit_kerja']); ?></td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($row['nama_cuti']); ?></td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500"><?php echo $row['tgl_pengajuan'] ? date('d/m/Y', strtotime($row['tgl_pengajuan'])) : 'N/A'; ?></td>
                            <td class="px-3 py-2 whitespace-nowrap"><div class="text-gray-900"><?php echo htmlspecialchars($row['lama_cuti']); ?> hari</div><div class="text-gray-500"><?php echo date('d/m/y', strtotime($row['tgl_mulai'])) . ' - ' . date('d/m/y', strtotime($row['tgl_selesai'])); ?></div></td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <?php 
                                $status = htmlspecialchars($row['pertimbangan_atasan']);
                                $badge_color = 'bg-gray-100 text-gray-800';
                                if ($status == 'Disetujui') $badge_color = 'bg-green-100 text-green-800';
                                if ($status == 'Ditangguhkan' || $status == 'Perubahan') $badge_color = 'bg-yellow-100 text-yellow-800';
                                if ($status == 'Tidak Disetujui') $badge_color = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_color; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap font-medium flex items-center space-x-2 no-print">
                                <button type="button" data-id="<?php echo $row['id']; ?>" class="edit-cuti-btn text-indigo-600 hover:text-indigo-900 px-2 py-1 rounded-md hover:bg-indigo-100">Edit</button>
                                <form action="hapus_cuti.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="cuti_id" value="<?php echo $row['id']; ?>">
                                    <button type="button" data-nama="<?php echo htmlspecialchars($row['nama'], ENT_QUOTES); ?>" data-type="cuti" class="delete-btn text-red-600 hover:text-red-900 px-2 py-1 rounded-md hover:bg-red-100">Hapus</button>
                                </form>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center px-6 py-4'>Tidak ada data cuti yang cocok dengan kriteria Anda.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Cuti -->
<div id="edit-cuti-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden no-print">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Data Cuti</h3>
                <button id="close-modal-btn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="mt-2 px-7 py-3">
                <form id="edit-cuti-form" action="proses_edit_cuti.php" method="POST">
                    <input type="hidden" name="id" id="edit-cuti-id">
                    <div class="grid grid-cols-1 gap-6 text-left">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jenis Cuti</label>
                            <select name="jenis_cuti_id" id="edit-jenis-cuti" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alasan Cuti</label>
                            <textarea name="alasan_cuti" id="edit-alasan-cuti" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lama (Hari)</label>
                                <input type="number" name="lama_cuti" id="edit-lama-cuti" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mulai Tanggal</label>
                                <input type="date" name="tgl_mulai" id="edit-tgl-mulai" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" id="edit-tgl-selesai" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">Alamat Selama Cuti</label>
                            <input type="text" name="alamat_cuti" id="edit-alamat-cuti" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">No. Telepon</label>
                            <input type="text" name="telp" id="edit-telp" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status Persetujuan</label>
                            <select name="pertimbangan_atasan" id="edit-status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></select>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3 mt-6">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="delete-confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden no-print">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-2xl rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-200">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p id="delete-confirm-text" class="text-sm text-gray-500"></p>
            </div>
            <div class="items-center px-4 py-3 space-x-4">
                <button id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                    Batal
                </button>
                <button id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Khusus untuk Mencetak -->
<style>
    .print-only { display: none; }
    @media print {
        body > nav, .no-print { display: none !important; }
        .print-only { display: block; }
        body > main { padding: 0 !important; margin: 1cm !important; }
        .bg-white { box-shadow: none !important; border: none !important; padding: 0 !important; }
        
        .table-scroll-container {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 9pt; 
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead { 
            display: table-header-group; 
            background-color: #f2f2f2 !important; 
            -webkit-print-color-adjust: exact; 
            color-adjust: exact; 
        }
        th, td { border: 1px solid #ccc !important; padding: 4px; }
        .max-w-sm { max-width: none !important; }
        .whitespace-normal { white-space: normal !important; }
        tbody span.rounded-full { background-color: transparent !important; color: black !important; padding: 0 !important; border: none !important; }
    }
</style>
