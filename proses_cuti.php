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

    // --- LOGIKA UPLOAD FILE ---
    $bukti_cuti = NULL; // Default null jika tidak ada upload

    if (isset($_FILES['bukti_cuti']) && $_FILES['bukti_cuti']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['bukti_cuti']['tmp_name'];
        $file_name = $_FILES['bukti_cuti']['name'];
        $file_size = $_FILES['bukti_cuti']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi Ekstensi
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        // Validasi Ukuran (Max 2MB = 2097152 bytes)
        $max_size = 2 * 1024 * 1024;

        if (!in_array($file_ext, $allowed_ext)) {
            die("Error: Format file tidak diizinkan. Gunakan PDF, JPG, atau PNG.");
        }

        if ($file_size > $max_size) {
            die("Error: Ukuran file terlalu besar. Maksimal 2MB.");
        }

        // Generate nama file unik agar tidak bentrok
        // Format: BUKTI_time_pegawaiID.ext
        $new_file_name = 'BUKTI_' . time() . '_' . $pegawai_id . '.' . $file_ext;
        $upload_dir = 'uploads/bukti_cuti/';

        // Pastikan folder ada
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                 die("Error: Gagal membuat direktori uploads.");
            }
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $bukti_cuti = $new_file_name;
        } else {
            die("Error: Gagal mengupload file ke folder tujuan.");
        }
    }

    // --- LOGIKA VALIDASI BISNIS (Cuti Melahirkan 1x Setahun) ---
    // ID 4 adalah Cuti Melahirkan (sesuai database.php)
    if ($jenis_cuti_id == 4) {
        $tahun_ini = date('Y');
        $sql_cek = "SELECT COUNT(*) as jumlah FROM cuti WHERE pegawai_id = ? AND jenis_cuti_id = 4 AND YEAR(tgl_mulai) = ? AND is_deleted = 0";
        $stmt_cek = $conn->prepare($sql_cek);
        $stmt_cek->bind_param("is", $pegawai_id, $tahun_ini);
        $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();
        $row_cek = $result_cek->fetch_assoc();
        
        if ($row_cek['jumlah'] > 0) {
            die("Error Validasi: Pegawai ini sudah mengajukan Cuti Melahirkan tahun ini.");
        }
        $stmt_cek->close();
    }

    // --- INSERT DATA ---
    // Menambahkan kolom bukti_cuti pada INSERT
    $sql = "INSERT INTO cuti (pegawai_id, jenis_cuti_id, alasan_cuti, bukti_cuti, lama_cuti, tgl_mulai, tgl_selesai, alamat_cuti, telp, pertimbangan_atasan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Urutan parameter (10 item): 
    // i (pegawai_id), i (jenis_cuti_id), s (alasan), s (bukti_cuti), i (lama), s (mulai), s (selesai), s (alamat), s (telp), s (status)
    $stmt->bind_param("iissssssss", $pegawai_id, $jenis_cuti_id, $alasan_cuti, $bukti_cuti, $lama_cuti, $tgl_mulai, $tgl_selesai, $alamat_cuti, $telp, $pertimbangan_atasan);

    if ($stmt->execute()) {
        header("Location: index.php?page=laporan_cuti&status=sukses");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>