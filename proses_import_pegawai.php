<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $file_type = $_FILES['csv_file']['type'];

    // Validasi tipe file
    if (!in_array($file_type, ['text/csv', 'application/vnd.ms-excel'])) {
        header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Format file harus CSV."));
        exit();
    }

    $handle = fopen($file, "r");
    if ($handle === FALSE) {
        header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Tidak bisa membuka file CSV."));
        exit();
    }

    // Lewati baris header
    fgetcsv($handle, 1000, ";");

    $conn->begin_transaction();
    $sql = "INSERT INTO pegawai (nama, nip, jabatan, unit_kerja, tmt_pensiun) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $berhasil = 0;
    $gagal = 0;

    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Skip baris kosong
        if (count(array_filter($data)) == 0) {
            continue;
        }

        $nama = trim($data[0] ?? '');
        $nip = trim($data[1] ?? '');
        $jabatan = trim($data[2] ?? '');
        $unit_kerja = trim($data[3] ?? '');
        $tmt_pensiun = !empty($data[4]) ? date('Y-m-d', strtotime($data[4])) : null;

        // Validasi wajib
        if (empty($nama) || empty($nip)) {
            $gagal++;
            continue;
        }

        $stmt->bind_param("sssss", $nama, $nip, $jabatan, $unit_kerja, $tmt_pensiun);
        if ($stmt->execute()) {
            $berhasil++;
        } else {
            $gagal++;
        }
    }

    fclose($handle);
    $stmt->close();
    $conn->commit();

    $message = "Impor selesai. Berhasil: $berhasil baris. Gagal: $gagal baris.";
    header("Location: index.php?page=data_pegawai&import_status=success&message=" . urlencode($message));
    exit();

} else {
    header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Tidak ada file yang diunggah."));
    exit();
}
?>
