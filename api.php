<?php
// api.php
header('Content-Type: application/json');
require_once 'database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

function search_pegawai($conn) {
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    if (strlen($query) < 2) { echo json_encode([]); return; }
    $searchTerm = "%" . $conn->real_escape_string($query) . "%";
    $sql = "SELECT id, nama, nip, jabatan, unit_kerja, tmt_pensiun FROM pegawai WHERE nama LIKE ? OR nip LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $pegawais = [];
    while ($row = $result->fetch_assoc()) { $pegawais[] = $row; }
    echo json_encode($pegawais);
    $stmt->close();
}

function get_sisa_cuti($conn) {
    $pegawai_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($pegawai_id == 0) { echo json_encode(['sisa_cuti' => 12]); return; }
    $tahun_ini = date('Y');
    $cuti_tahunan_maksimal = 12;
    $sql = "SELECT SUM(lama_cuti) as total_diambil FROM cuti WHERE pegawai_id = ? AND jenis_cuti_id = 1 AND YEAR(tgl_mulai) = ? AND pertimbangan_atasan = 'Disetujui' AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $pegawai_id, $tahun_ini);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_diambil = $row['total_diambil'] ? (int)$row['total_diambil'] : 0;
    $sisa_cuti = $cuti_tahunan_maksimal - $total_diambil;
    echo json_encode(['sisa_cuti' => $sisa_cuti]);
    $stmt->close();
}

function get_cuti_detail($conn) {
    $cuti_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($cuti_id == 0) {
        echo json_encode(['error' => 'ID Cuti tidak valid.']);
        return;
    }
    $sql = "SELECT id, pegawai_id, jenis_cuti_id, alasan_cuti, lama_cuti, tgl_mulai, tgl_selesai, alamat_cuti, telp, pertimbangan_atasan FROM cuti WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cuti_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cuti = $result->fetch_assoc();
    if ($cuti) {
        echo json_encode($cuti);
    } else {
        echo json_encode(['error' => 'Data cuti tidak ditemukan.']);
    }
    $stmt->close();
}

function get_pegawai_detail($conn) {
    $pegawai_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($pegawai_id == 0) {
        echo json_encode(['error' => 'ID Pegawai tidak valid.']);
        return;
    }
    $sql = "SELECT * FROM pegawai WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pegawai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pegawai = $result->fetch_assoc();
    if ($pegawai) {
        echo json_encode($pegawai);
    } else {
        echo json_encode(['error' => 'Data pegawai tidak ditemukan.']);
    }
    $stmt->close();
}

switch ($action) {
    case 'search_pegawai':
        search_pegawai($conn);
        break;
    case 'get_sisa_cuti':
        get_sisa_cuti($conn);
        break;
    case 'get_cuti_detail':
        get_cuti_detail($conn);
        break;
    case 'get_pegawai_detail':
        get_pegawai_detail($conn);
        break;
    default:
        echo json_encode(['error' => 'No action specified']);
        break;
}

$conn->close();
?>
