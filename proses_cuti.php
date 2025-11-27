<?php
// proses_cuti.php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $jenis_cuti_id = (int)$_POST['jenis_cuti_id'];
    $alasan_cuti = $_POST['alasan_cuti'];
    $lama_cuti = (int)$_POST['lama_cuti'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $alamat_cuti = $_POST['alamat_cuti'];
    $telp = $_POST['telp'];
    $pertimbangan_atasan = $_POST['pertimbangan_atasan'];

    $sql = "INSERT INTO cuti (pegawai_id, jenis_cuti_id, alasan_cuti, lama_cuti, tgl_mulai, tgl_selesai, alamat_cuti, telp, pertimbangan_atasan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisisssss", $pegawai_id, $jenis_cuti_id, $alasan_cuti, $lama_cuti, $tgl_mulai, $tgl_selesai, $alamat_cuti, $telp, $pertimbangan_atasan);

    if ($stmt->execute()) {
        header("Location: index.php?page=laporan_cuti&status=sukses");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>
