<?php
// proses_edit_guru.php

require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari form
    $id = (int)$_POST['id'];
    $nama = $conn->real_escape_string($_POST['nama']);
    $nip = $conn->real_escape_string($_POST['nip']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $unit_kerja = $conn->real_escape_string($_POST['unit_kerja']);
    $tmt_pensiun = !empty($_POST['tmt_pensiun']) ? $conn->real_escape_string($_POST['tmt_pensiun']) : NULL;

    // Validasi data
    if (empty($id) || empty($nama) || empty($nip) || empty($jabatan) || empty($unit_kerja)) {
        die("Error: Semua kolom harus diisi.");
    }

    // Query SQL untuk UPDATE
    $sql = "UPDATE pegawai SET nama = ?, nip = ?, jabatan = ?, unit_kerja = ?, tmt_pensiun = ? WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameter
    $stmt->bind_param("sssssi", $nama, $nip, $jabatan, $unit_kerja, $tmt_pensiun, $id);

    // Eksekusi
    if ($stmt->execute()) {
        header("Location: index.php?page=data_pegawai&status_edit=sukses");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();

} else {
    header("Location: index.php");
    exit();
}

$conn->close();
?>
