<?php
// proses_edit_cuti.php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $jenis_cuti_id = (int)$_POST['jenis_cuti_id'];
    $alasan_cuti = $_POST['alasan_cuti'];
    $lama_cuti = (int)$_POST['lama_cuti'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $alamat_cuti = $_POST['alamat_cuti'];
    $telp = $_POST['telp'];
    $pertimbangan_atasan = $_POST['pertimbangan_atasan'];

    $sql = "UPDATE cuti SET jenis_cuti_id = ?, alasan_cuti = ?, lama_cuti = ?, tgl_mulai = ?, tgl_selesai = ?, alamat_cuti = ?, telp = ?, pertimbangan_atasan = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisssssi", $jenis_cuti_id, $alasan_cuti, $lama_cuti, $tgl_mulai, $tgl_selesai, $alamat_cuti, $telp, $pertimbangan_atasan, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?page=laporan_cuti&status_edit=sukses");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>
