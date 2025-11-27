<?php
require_once __DIR__ . 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $file_type = $_FILES['csv_file']['type'];

    // Validasi tipe file
    if ($file_type !== 'text/csv') {
        header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Format file harus CSV."));
        exit();
    }

    $handle = fopen($file, "r");
    if ($handle === FALSE) {
        header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Tidak bisa membuka file CSV."));
        exit();
    }

    // Lewati baris header
    fgetcsv($handle, 1000, ",");

    $conn->begin_transaction();
    $sql = "INSERT INTO pegawai (nama, nip, jabatan, unit_kerja, tmt_pensiun) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $berhasil = 0;
    $gagal = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Asumsi urutan kolom: nama, nip, jabatan, unit_kerja, tmt_pensiun
        $nama = $data[0] ?? null;
        $nip = $data[1] ?? null;
        $jabatan = $data[2] ?? null;
        $unit_kerja = !empty($data[3]) ? $data[3] : "-";  // 👉 opsi 1: kasih default "-"
        $tmt_pensiun = !empty($data[4]) ? date('Y-m-d', strtotime($data[4])) : null;

        // Validasi data dasar
        if (empty($nama) || empty($nip)) {
            $gagal++;
            continue; // Lewati baris ini jika nama atau NIP kosong
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
    
    // Selesaikan transaksi
    $conn->commit();

    $message = "Impor selesai. Berhasil: $berhasil baris. Gagal: $gagal baris.";
    header("Location: index.php?page=data_pegawai&import_status=success&message=" . urlencode($message));
    exit();

} else {
    header("Location: index.php?page=data_pegawai&import_status=error&message=" . urlencode("Error: Tidak ada file yang diunggah."));
    exit();
}
?>
