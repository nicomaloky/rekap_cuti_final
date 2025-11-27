<?php
// hapus_pegawai.php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pegawai_id = isset($_POST['pegawai_id']) ? (int)$_POST['pegawai_id'] : 0;

    if ($pegawai_id > 0) {
        $sql = "UPDATE pegawai SET is_deleted = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pegawai_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?page=data_pegawai&status_hapus=sukses");
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
?>
