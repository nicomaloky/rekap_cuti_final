<?php
// Menghubungkan ke file database untuk melakukan query
require_once __DIR__ . '/../database.php';

// --- LOGIKA UNTUK SORTING ---
$allowed_sort_keys = ['nama', 'jabatan', 'unit_kerja', 'tmt_pensiun', 'sisa_cuti'];
$sort_key = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_keys) ? $_GET['sort'] : 'nama';

// Peta untuk mengubah kunci sorting menjadi ekspresi SQL yang benar
$sort_columns_map = [
    'nama' => 'p.nama',
    'jabatan' => 'p.jabatan',
    'unit_kerja' => "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p.unit_kerja, ' ', 2), ' ', -1) AS UNSIGNED), p.unit_kerja",
    'tmt_pensiun' => 'p.tmt_pensiun',
    'sisa_cuti' => 'sisa_cuti'
];
$sort_column = $sort_columns_map[$sort_key];
$sort_order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc']) ? strtolower($_GET['order']) : 'asc';

function generateSortLink($key, $display_text, $current_key, $current_order) {
    $order_for_link = ($current_key == $key && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($current_key == $key) {
        $icon = $current_order == 'asc' ? ' <span class="text-gray-900">&uarr;</span>' : ' <span class="text-gray-900">&darr;</span>';
    }
    $search_param = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
    return "<a href='?page=data_pegawai&sort=$key&order=$order_for_link$search_param' class='hover:text-gray-900'>$display_text$icon</a>";
}

$sql_total = "SELECT COUNT(id) as total FROM pegawai WHERE is_deleted = 0";
$result_total = $conn->query($sql_total);
$total_pegawai = $result_total->fetch_assoc()['total'];

?>
<div class="bg-white p-8 rounded-lg shadow-lg w-full" id="data-pegawai-page-container">
    <!-- Baris atas: Judul + Tombol -->
    <div class="flex justify-between items-start mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Data Pegawai</h1>
        <div class="flex space-x-2">
            <button id="btn-show-import-form" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-md flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import CSV
            </button>
            <button id="btn-show-add-form"
                class="bg-blue-800 hover:bg-blue-950 text-white font-bold py-2 px-4 rounded shadow-md">
                + Input Pegawai Baru
            </button>
        </div>
    </div>

    

    <!-- Baris bawah: Form + Total -->
    <div class="flex justify-between items-end mb-8">
        <form action="index.php" method="GET" class="w-full max-w-xl">
            <input type="hidden" name="page" value="data_pegawai">
            <label for="search_pegawai" class="block text-xs font-semibold text-gray-700 mb-1">
                Cari Pegawai
            </label>
            <div class="flex w-full rounded-md shadow-sm">
                <input type="text" id="search_pegawai" name="search"
                    class="flex-1 min-w-0 block w-full rounded-l-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="Ketik Nama atau NIP..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-md text-white bg-blue-800 hover:bg-blue-900 focus:outline-none">
                    Cari
                </button>
            </div>
        </form>

        <div class="ml-4 bg-gray-100 border border-gray-300 rounded-md px-4 py-2 text-sm font-medium text-gray-700 whitespace-nowrap shadow-sm flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-800 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5.121 17.804A8.966 8.966 0 0112 15c2.21 0 4.21.804 5.879 2.137M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Total Pegawai: <?php echo $total_pegawai; ?>
        </div>
    </div>

    <!-- Tabel Data Guru -->
    <div class="overflow-auto h-[60vh] border rounded-lg shadow-inner">
        <table id="data-pegawai-table" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLink('nama', 'Nama', $sort_key, $sort_order); ?></th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLink('jabatan', 'Jabatan', $sort_key, $sort_order); ?></th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLink('unit_kerja', 'Unit Kerja', $sort_key, $sort_order); ?></th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLink('tmt_pensiun', 'Masa Akhir Kontrak/BUP', $sort_key, $sort_order); ?></th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo generateSortLink('sisa_cuti', 'Sisa Cuti Tahunan', $sort_key, $sort_order); ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $search_query = isset($_GET['search']) ? $_GET['search'] : '';
                $search_term = "%" . $search_query . "%";
                $tahun_ini = date('Y');
                $sql_pegawai = "SELECT p.id, p.nama, p.nip, p.jabatan, p.unit_kerja, p.tmt_pensiun, (12 - IFNULL(c.total_diambil, 0)) AS sisa_cuti FROM pegawai p LEFT JOIN (SELECT pegawai_id, SUM(lama_cuti) AS total_diambil FROM cuti WHERE jenis_cuti_id = 1 AND YEAR(tgl_mulai) = ? AND pertimbangan_atasan = 'Disetujui' AND is_deleted = 0 GROUP BY pegawai_id) c ON p.id = c.pegawai_id WHERE p.is_deleted = 0";
                
                $params = [];
                $types = '';
                $params[] = $tahun_ini;
                $types .= 's';

                if (!empty($search_query)) {
                    $sql_pegawai .= " AND (p.nama LIKE ? OR p.nip LIKE ?)";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $types .= 'ss';
                }
                
                $sql_pegawai .= " ORDER BY $sort_column " . strtoupper($sort_order);
                
                $stmt_pegawai = $conn->prepare($sql_pegawai);

                if (!empty($params)) {
                    $refs = [];
                    foreach ($params as $key => $value) {
                        $refs[$key] = &$params[$key];
                    }
                    array_unshift($refs, $types);
                    call_user_func_array([$stmt_pegawai, 'bind_param'], $refs);
                }

                $stmt_pegawai->execute();
                $result_pegawai = $stmt_pegawai->get_result();
                if ($result_pegawai->num_rows > 0) {
                    while($row = $result_pegawai->fetch_assoc()) {
                        $is_pensiun = (!empty($row['tmt_pensiun']) && strtotime($row['tmt_pensiun']) < time());
                        $row_class = $is_pensiun ? 'bg-red-50 text-gray-500' : '';
                ?>
                        <tr class="<?php echo $row_class; ?> hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></div><div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['nip']); ?></div></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap"><?php echo htmlspecialchars($row['jabatan']); ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap"><?php echo htmlspecialchars($row['unit_kerja']); ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <?php if (!empty($row['tmt_pensiun'])) {
                                    echo date('d M Y', strtotime($row['tmt_pensiun']));
                                    if ($is_pensiun) echo '<span class="ml-2 text-xs font-semibold text-red-600">(Pensiun)</span>';
                                } else { echo '-'; } ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold <?php echo ($row['sisa_cuti'] <= 3 ? 'text-red-600' : 'text-green-600'); ?>"><?php echo $row['sisa_cuti']; ?> hari</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2">
                                <button type="button" data-id="<?php echo $row['id']; ?>" class="edit-pegawai-btn text-indigo-600 hover:text-indigo-900 px-2 py-1 rounded-md hover:bg-indigo-100">Edit</button>
                                <form action="hapus_pegawai.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="pegawai_id" value="<?php echo $row['id']; ?>">
                                    <button type="button" data-nama="<?php echo htmlspecialchars($row['nama'], ENT_QUOTES); ?>" data-type="pegawai" class="delete-btn text-red-600 hover:text-red-900 px-2 py-1 rounded-md hover:bg-red-100">Hapus</button>
                                </form>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center px-6 py-4'>Tidak ada data pegawai yang cocok.</td></tr>";
                }
                $stmt_pegawai->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Input via CSV -->
<div id="import-csv-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Impor Data Pegawai dari CSV</h3>
                <button id="close-import-modal-btn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-600 mb-4">
                    Upload file CSV dengan format yang sesuai, Pastikan urutan kolomnya adalah: <strong>nama, nip, jabatan, unit_kerja, tmt_pensiun</strong>.
                </p>
                <p class="text-sm text-gray-600 mb-4">
                    <strong>Catatan:</strong> Pengisian kolom <em>tmt_pensiun</em> harus dalam format <code>YYYY-MM-DD</code> Contoh: 2060-04-01.
               </p>
                <a href="templates/template.xlsx" download="template.xlsx" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
                    Unduh Template xlsx disini
                </a>
                <p class="text-sm text-red-600 mb-4">
                    <strong>Setelah mengisi file template, wajib save as CSV agar bisa di upload</strong> 
               </p>
                <form action="proses_import_pegawai.php" method="POST" enctype="multipart/form-data">
                    <div>
                        <label for="csv-file" class="block text-sm font-medium text-gray-700">Pilih File CSV</label>
                        <input type="file" name="csv_file" id="csv-file" accept=".csv" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div class="flex justify-end mt-6 space-x-4">
                        <button type="button" id="cancel-import-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Batal</button>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Impor Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Pegawai Baru -->
<div id="add-pegawai-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Formulir Input Pegawai Baru</h3>
                <button id="close-add-modal-btn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="mt-4">
                <form id="formGuruBaru" action="proses_pegawai.php" method="POST">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="new_nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" id="new_nama" name="nama" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="new_nip" class="block text-sm font-medium text-gray-700">NIP</label>
                            <input type="text" id="new_nip" name="nip" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required maxlength="18" minlength="18" pattern="\d{18}" title="NIP harus terdiri dari 18 angka." oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div>
                            <label for="new_jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <select id="new_jabatan" name="jabatan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option>Kepala Sekolah</option>
                                <option>Guru</option>
                                <option>Tata Usaha</option>
                            </select>
                        </div>
                        <div>
                            <label for="new_unit_kerja" class="block text-sm font-medium text-gray-700">Unit Kerja / Sekolah</label>
                            <select id="new_unit_kerja" name="unit_kerja" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Unit Kerja --</option>
                                <?php
                                for ($i = 1; $i <= 23; $i++) {
                                    $nama_sekolah = "SMPN " . $i . " Kota Bogor";
                                    echo "<option value='" . htmlspecialchars($nama_sekolah) . "'>" . htmlspecialchars($nama_sekolah) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label for="new_tmt_pensiun" class="block text-sm font-medium text-gray-700">Masa Akhir Kontrak/BUP</label>
                            <input type="date" id="new_tmt_pensiun" name="tmt_pensiun" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end mt-6 space-x-4">
                        <button type="button" id="cancel-add-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Batal</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Pegawai -->
<div id="edit-pegawai-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Data Pegawai</h3>
                <button id="close-pegawai-modal-btn" type="button" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="mt-4">
                <form id="edit-pegawai-form" action="proses_edit_pegawai.php" method="POST">
                    <input type="hidden" name="id" id="edit-pegawai-id">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" name="nama" id="edit-nama" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP</label>
                            <input type="text" name="nip" id="edit-nip" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required maxlength="18" minlength="18" pattern="\d{18}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jabatan</label>
                            <select name="jabatan" id="edit-jabatan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Unit Kerja</label>
                            <select name="unit_kerja" id="edit-unit-kerja" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Masa Akhir Kontrak/BUP</label>
                            <input type="date" name="tmt_pensiun" id="edit-tmt-pensiun" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end mt-6 space-x-4">
                        <button type="button" id="cancel-pegawai-edit-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Batal</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="delete-confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Logika Notifikasi Global ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('import_status') || urlParams.has('status')) {
        const status = urlParams.get('import_status') || urlParams.get('status');
        const message = urlParams.get('message');
        if (message) {
            const pageContainer = document.getElementById('data-pegawai-page-container');
            const firstChild = pageContainer.firstChild;
            const notification = document.createElement('div');
            const isSuccess = status === 'success' || status === 'sukses';
            notification.className = isSuccess 
                ? 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6'
                : 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6';
            notification.textContent = decodeURIComponent(message);
            pageContainer.insertBefore(notification, firstChild.nextSibling); // Insert after h1
            setTimeout(() => {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
    }

    // --- Logika Halaman Data Pegawai ---
    const dataPegawaiPage = document.getElementById('data-pegawai-page-container');
    if (dataPegawaiPage) {
        // Modal Tambah Pegawai
        const btnShowAddForm = document.getElementById('btn-show-add-form');
        const addPegawaiModal = document.getElementById('add-pegawai-modal');
        const closeAddModalBtn = document.getElementById('close-add-modal-btn');
        const cancelAddBtn = document.getElementById('cancel-add-btn');
        
        if(btnShowAddForm) btnShowAddForm.addEventListener('click', () => addPegawaiModal.classList.remove('hidden'));
        if(closeAddModalBtn) closeAddModalBtn.addEventListener('click', () => addPegawaiModal.classList.add('hidden'));
        if(cancelAddBtn) cancelAddBtn.addEventListener('click', () => addPegawaiModal.classList.add('hidden'));

        // Modal Impor CSV
        const importModal = document.getElementById('import-csv-modal');
        const btnShowImport = document.getElementById('btn-show-import-form');
        const btnCloseImport = document.getElementById('close-import-modal-btn');
        const btnCancelImport = document.getElementById('cancel-import-btn');

        if(btnShowImport) btnShowImport.addEventListener('click', () => importModal.classList.remove('hidden'));
        if(btnCloseImport) btnCloseImport.addEventListener('click', () => importModal.classList.add('hidden'));
        if(btnCancelImport) btnCancelImport.addEventListener('click', () => importModal.classList.add('hidden'));
    
        // Modal Edit Pegawai
        const editPegawaiModal = document.getElementById('edit-pegawai-modal');
        if (editPegawaiModal) {
            const closePegawaiModalBtn = document.getElementById('close-pegawai-modal-btn');
            const cancelPegawaiEditBtn = document.getElementById('cancel-pegawai-edit-btn');
            const closePegawaiModal = () => editPegawaiModal.classList.add('hidden');
            if(closePegawaiModalBtn) closePegawaiModalBtn.addEventListener('click', closePegawaiModal);
            if(cancelPegawaiEditBtn) cancelPegawaiEditBtn.addEventListener('click', closePegawaiModal);
            editPegawaiModal.addEventListener('click', (e) => { if (e.target === editPegawaiModal) closePegawaiModal(); });
        }

        // Modal Konfirmasi Hapus
        const deleteModal = document.getElementById('delete-confirm-modal');
        if (deleteModal) {
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const deleteConfirmText = document.getElementById('delete-confirm-text');
            let formToDelete = null;

            const openDeleteModal = () => deleteModal.classList.remove('hidden');
            const closeDeleteModal = () => {
                deleteModal.classList.add('hidden');
                formToDelete = null;
            };

            if(cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);
            if(confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => {
                if (formToDelete) {
                    formToDelete.submit();
                }
            });

            document.body.addEventListener('click', function(event) {
                if (event.target.classList.contains('delete-btn')) {
                    const button = event.target;
                    formToDelete = button.closest('form');
                    const nama = button.dataset.nama;
                    const type = button.dataset.type === 'cuti' ? 'data cuti ini' : `data pegawai "${nama}"`;
                    deleteConfirmText.textContent = `Apakah Anda yakin ingin menghapus ${type}? Aksi ini tidak dapat dibatalkan.`;
                    openDeleteModal();
                }
            });
        }

        // Event listener untuk tombol edit pegawai
        document.body.addEventListener('click', async function(event) {
            if (event.target.classList.contains('edit-pegawai-btn')) {
                const pegawaiId = event.target.dataset.id;
                try {
                    // Anda perlu membuat file api.php untuk mengambil detail pegawai
                    const response = await fetch(`api.php?action=get_pegawai_detail&id=${pegawaiId}`);
                    const data = await response.json();
                    if (data.error) { alert(data.error); return; }

                    // Isi form popup pegawai
                    document.getElementById('edit-pegawai-id').value = data.id;
                    document.getElementById('edit-nama').value = data.nama;
                    document.getElementById('edit-nip').value = data.nip;
                    document.getElementById('edit-tmt-pensiun').value = data.tmt_pensiun;
                    
                    const jabatanSelect = document.getElementById('edit-jabatan');
                    jabatanSelect.innerHTML = '';
                    const jabatanOpsi = ["Kepala Sekolah", "Guru", "Tata Usaha"];
                    jabatanOpsi.forEach(opsi => {
                        const option = document.createElement('option');
                        option.value = opsi; option.textContent = opsi;
                        if (data.jabatan === opsi) option.selected = true;
                        jabatanSelect.appendChild(option);
                    });

                    const unitKerjaSelect = document.getElementById('edit-unit-kerja');
                    unitKerjaSelect.innerHTML = '';
                    // Asumsi daftar sekolah sama, idealnya ini juga diambil dari API
                    for (let i = 1; i <= 23; i++) {
                        const namaSekolah = `SMPN ${i} Kota Bogor`;
                        const option = document.createElement('option');
                        option.value = namaSekolah; option.textContent = namaSekolah;
                        if (data.unit_kerja === namaSekolah) option.selected = true;
                        unitKerjaSelect.appendChild(option);
                    }
                    
                    editPegawaiModal.classList.remove('hidden');
                } catch (error) {
                    console.error('Gagal mengambil data pegawai:', error);
                    alert('Terjadi kesalahan saat mengambil data.');
                }
            }
        });
    }
});
</script>
