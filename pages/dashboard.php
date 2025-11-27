<?php
// Memuat koneksi database
require_once __DIR__ . '/../database.php';

// --- Query untuk Statistik Kunci ---
// Menghitung hanya pegawai yang belum pensiun atau yang tmt_pensiun-nya kosong
$sql_total_pegawai = "SELECT COUNT(id) as total FROM pegawai WHERE is_deleted = 0 AND (tmt_pensiun > NOW() OR tmt_pensiun IS NULL)";
$total_pegawai = $conn->query($sql_total_pegawai)->fetch_assoc()['total'] ?? 0;

// BARU: Menghitung pegawai yang sudah pensiun
$sql_total_pensiun = "SELECT COUNT(id) as total FROM pegawai WHERE is_deleted = 0 AND tmt_pensiun <= NOW()";
$total_pensiun = $conn->query($sql_total_pensiun)->fetch_assoc()['total'] ?? 0;

$sql_sedang_cuti = "SELECT COUNT(id) as total FROM cuti WHERE NOW() BETWEEN tgl_mulai AND tgl_selesai AND is_deleted = 0 AND pertimbangan_atasan = 'Disetujui'";
$sedang_cuti = $conn->query($sql_sedang_cuti)->fetch_assoc()['total'] ?? 0;

$sql_cuti_bulan_ini = "SELECT COUNT(id) as total FROM cuti WHERE MONTH(tgl_pengajuan) = MONTH(NOW()) AND YEAR(tgl_pengajuan) = YEAR(NOW()) AND is_deleted = 0";
$cuti_bulan_ini = $conn->query($sql_cuti_bulan_ini)->fetch_assoc()['total'] ?? 0;

// --- Query untuk Grafik Jenis Cuti ---
$sql_chart = "SELECT jc.nama_cuti, COUNT(c.id) as jumlah FROM cuti c JOIN jenis_cuti jc ON c.jenis_cuti_id = jc.id WHERE c.is_deleted = 0 GROUP BY jc.nama_cuti ORDER BY jumlah DESC";
$result_chart = $conn->query($sql_chart);
$labels = [];
$data = [];
if ($result_chart) {
    while($row = $result_chart->fetch_assoc()) {
        $labels[] = $row['nama_cuti'];
        $data[] = $row['jumlah'];
    }
}
$chart_labels = json_encode($labels);
$chart_data = json_encode($data);

// --- Query untuk Peringatan Pensiun (6 Bulan ke Depan) ---
$sql_pensiun = "SELECT nama, jabatan, tmt_pensiun FROM pegawai WHERE is_deleted = 0 AND tmt_pensiun BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 6 MONTH) ORDER BY tmt_pensiun ASC LIMIT 15";
$result_pensiun = $conn->query($sql_pensiun);

// --- Query untuk Sisa Cuti Kritis (kurang dari 3 hari) ---
$tahun_ini = date('Y');
$sql_cuti_kritis = "
    SELECT p.nama, (12 - IFNULL(c.total_diambil, 0)) AS sisa_cuti
    FROM pegawai p 
    LEFT JOIN (
        SELECT pegawai_id, SUM(lama_cuti) AS total_diambil 
        FROM cuti 
        WHERE jenis_cuti_id = 1 AND YEAR(tgl_mulai) = ? AND pertimbangan_atasan = 'Disetujui' AND is_deleted = 0 
        GROUP BY pegawai_id
    ) c ON p.id = c.pegawai_id 
    WHERE p.is_deleted = 0 AND (p.tmt_pensiun > NOW() OR p.tmt_pensiun IS NULL)
    HAVING sisa_cuti <= 3 
    ORDER BY sisa_cuti ASC LIMIT 5";
$stmt_cuti_kritis = $conn->prepare($sql_cuti_kritis);
$stmt_cuti_kritis->bind_param("i", $tahun_ini);
$stmt_cuti_kritis->execute();
$result_cuti_kritis = $stmt_cuti_kritis->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<body class="antialiased text-slate-800">
    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Header -->
        <header class="flex justify-between items-center p-4 bg-white border-b sticky top-0 z-10">
            <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
        </header>

        <!-- Content Area -->
        <main class="flex-1 bg-slate-100 p-4 md:p-6">
            <!-- Statistik Kunci -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-5 rounded-xl shadow-md flex items-center justify-between"><div><div class="text-sm text-slate-500">Total Pegawai Aktif</div><div class="text-2xl font-bold"><?php echo $total_pegawai; ?></div></div><div class="bg-blue-100 text-blue-600 p-3 rounded-full"><svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 015.968 0M12 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg></div></div>
                <!-- KOTAK BARU UNTUK PEGAWAI PENSIUN -->
                <div class="bg-white p-5 rounded-xl shadow-md flex items-center justify-between"><div><div class="text-sm text-slate-500">Pegawai Pensiun</div><div class="text-2xl font-bold text-slate-600"><?php echo $total_pensiun; ?></div></div><div class="bg-slate-100 text-slate-600 p-3 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zm12-2h-6" /></svg></div></div>
                <div class="bg-white p-5 rounded-xl shadow-md flex items-center justify-between"><div><div class="text-sm text-slate-500">Pegawai Sedang Cuti</div><div class="text-2xl font-bold text-yellow-600"><?php echo $sedang_cuti; ?></div></div><div class="bg-yellow-100 text-yellow-600 p-3 rounded-full"><svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg></div></div>
                <div class="bg-white p-5 rounded-xl shadow-md flex items-center justify-between"><div><div class="text-sm text-slate-500">Total Cuti Bulan Ini</div><div class="text-2xl font-bold text-green-600"><?php echo $cuti_bulan_ini; ?></div></div><div class="bg-green-100 text-green-600 p-3 rounded-full"><svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg></div></div>
            </div>

            <!-- Grafik dan Peringatan -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Grafik Jenis Cuti -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Distribusi Jenis Cuti</h2>
                    <div class="h-96"><canvas id="jenisCutiChart" data-labels='<?php echo $chart_labels; ?>' data-values='<?php echo $chart_data; ?>'></canvas></div>
                </div>

                <!-- Kolom Peringatan -->
                <div class="space-y-6">
                    <!-- Peringatan Pensiun -->
                    <div class="bg-white p-6 rounded-2xl shadow-lg">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Peringatan Pensiun</h2>
                        <div class="max-h-64 overflow-y-auto custom-scrollbar pr-2 space-y-4">
                            <?php if ($result_pensiun && $result_pensiun->num_rows > 0): ?>
                                <?php while($row = $result_pensiun->fetch_assoc()): ?>
                                    <?php
                                        $nama = htmlspecialchars($row['nama']);
                                        $words = explode(" ", $nama);
                                        $initials = "";
                                        if (isset($words[0])) $initials .= strtoupper(substr($words[0], 0, 1));
                                        if (isset($words[1])) $initials .= strtoupper(substr($words[1], 0, 1));
                                        elseif (strlen($words[0]) > 1) $initials .= strtoupper(substr($words[0], 1, 1));
                                    ?>
                                    <div class="flex items-start">
                                        <img src="https://placehold.co/40x40/dbeafe/3b82f6?text=<?php echo urlencode($initials); ?>" class="w-10 h-10 rounded-full mr-3" alt="Foto <?php echo $nama; ?>">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-slate-900 text-sm"><?php echo $nama; ?></h3>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($row['jabatan'] ?? 'Jabatan tidak tersedia'); ?></p>
                                            <p class="text-xs text-red-600 font-medium mt-1">Pensiun: <?php echo date('d M Y', strtotime($row['tmt_pensiun'])); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-500">Tidak ada pegawai yang akan pensiun dalam 6 bulan ke depan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sisa Cuti Kritis -->
                    <div class="bg-white p-6 rounded-2xl shadow-lg">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Peringatan Sisa Cuti Tahunan</h2>
                        <div class="max-h-64 overflow-y-auto custom-scrollbar pr-2 space-y-3">
                            <?php if ($result_cuti_kritis && $result_cuti_kritis->num_rows > 0): ?>
                                <?php while($row = $result_cuti_kritis->fetch_assoc()): ?>
                                    <div class="text-sm flex justify-between items-center">
                                        <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($row['nama']); ?></p>
                                        <p class="text-red-600 font-bold bg-red-100 px-2 py-1 rounded"><?php echo $row['sisa_cuti']; ?> hari</p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-500">Tidak ada pegawai dengan sisa cuti kritis.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Inisialisasi Grafik
        const ctx = document.getElementById('jenisCutiChart').getContext('2d');
        const chartLabels = JSON.parse(document.getElementById('jenisCutiChart').dataset.labels);
        const chartData = JSON.parse(document.getElementById('jenisCutiChart').dataset.values);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Jumlah Cuti',
                    data: chartData,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(238, 255, 0, 0.9)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            boxWidth: 12,
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

