<?php
// hapus_cuti.php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cuti_id = isset($_POST['cuti_id']) ? (int)$_POST['cuti_id'] : 0;
    
    if ($cuti_id > 0) {
        $sql = "UPDATE cuti SET is_deleted = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cuti_id);
        
        if ($stmt->execute()) {
            header("Location: index.php?page=laporan_cuti&status_hapus=sukses");
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
?>
