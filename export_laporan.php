<?php
// export_laporan.php
require_once 'database.php';

$filename = "laporan_cuti_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- LOGIKA FILTER WAKTU ---
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

// --- LOGIKA SORTING ---
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

// --- QUERY DATA ---
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
$conn->close();
?>

<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 5px; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
<h3>Laporan Rekapitulasi Cuti Pegawai</h3>
<h4>Dinas Pendidikan Kota Bogor</h4>
<p>Tanggal Ekspor: <?php echo date('d F Y'); ?></p>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>NIP</th>
            <th>Unit Kerja</th>
            <th>Jenis Cuti</th>
            <th>Tgl Pengajuan</th>
            <th>Lama Cuti (Hari)</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Selesai</th>
            <th>Alasan</th>
            <th>Status Persetujuan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $nomor = 1;
        if (!empty($all_data)) {
            foreach ($all_data as $row) {
                echo "<tr>";
                echo "<td>" . $nomor++ . "</td>";
                echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                echo "<td>'" . htmlspecialchars($row['nip']) . "</td>";
                echo "<td>" . htmlspecialchars($row['unit_kerja']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_cuti']) . "</td>";
                echo "<td>" . ($row['tgl_pengajuan'] ? date('d/m/Y', strtotime($row['tgl_pengajuan'])) : 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['lama_cuti']) . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['tgl_mulai'])) . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['tgl_selesai'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['alasan_cuti']) . "</td>";
                echo "<td>" . htmlspecialchars($row['pertimbangan_atasan']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='11'>Tidak ada data cuti sesuai kriteria.</td></tr>";
        }
        ?>
    </tbody>
</table>
</body>
</html>
