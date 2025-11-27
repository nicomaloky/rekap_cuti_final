<?php
// proses_pegawai.php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $jabatan = $_POST['jabatan'];
    $unit_kerja = $_POST['unit_kerja'];
    $tmt_pensiun = !empty($_POST['tmt_pensiun']) ? $_POST['tmt_pensiun'] : NULL;

    // Langkah 1: Cek apakah NIP sudah ada di database
    $sql_check = "SELECT id FROM pegawai WHERE nip = ? AND is_deleted = 0";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $nip);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Jika NIP sudah ada, kembali ke halaman data pegawai dengan pesan error
        $stmt_check->close();
        header("Location: index.php?page=data_pegawai&error=duplicate");
        exit();
    }
    $stmt_check->close();


    // Langkah 2: Jika NIP belum ada, lanjutkan proses insert
    $sql = "INSERT INTO pegawai (nama, nip, jabatan, unit_kerja, tmt_pensiun) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nama, $nip, $jabatan, $unit_kerja, $tmt_pensiun);

    if ($stmt->execute()) {
        // Jika berhasil, kembali dengan pesan sukses
        header("Location: index.php?page=data_pegawai&status=sukses");
    } else {
        // Jika ada error lain saat menyimpan
        header("Location: index.php?page=data_pegawai&error=generic");
    }
    $stmt->close();
    $conn->close();
}
?>
