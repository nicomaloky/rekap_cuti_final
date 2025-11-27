<?php
// Mulai session
session_start();

// Jika pengguna sudah login, alihkan ke halaman utama
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/image/logo.jpg" >
    <title>Login - Manajemen dan Rekapitulasi Cuti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- CSS untuk background gambar -->
    <style>
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1; /* Menempatkan pseudo-element di belakang konten */

            /* Path ke gambar background Anda */
            background-image: url('assets/image/background.png');
            
            /* Memastikan gambar menutupi seluruh layar */
            background-size: cover;
            
            /* Memposisikan gambar di tengah */
            background-position: center;
            
            /* Mencegah gambar berulang */
            background-repeat: no-repeat;

            /* Membuat gambar tetap diam saat di-scroll (opsional) */
            background-attachment: fixed;

            /* PERUBAHAN DI SINI: Menambahkan efek blur pada background */
            filter: blur(5px);
            -webkit-filter: blur(5px); /* Untuk kompatibilitas browser */
        }
    </style>
</head>
<body class="flex items-center justify-center h-screen">
    <!-- Kotak login dibuat solid agar kontras dengan background blur -->
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8">
        <!-- Bagian Logo -->
        <div class="flex justify-center mb-6">
            <!-- Mengubah src ke folder assets lokal -->
            <img src="assets/image/logo.jpg" 
                 alt="Logo Dinas Pendidikan Kota Bogor" 
                 class="h-32 w-auto rounded-full shadow-lg"
                 onerror="this.onerror=null;this.src='https://placehold.co/128x128/003366/FFFFFF?text=Logo';">
        </div>
        
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Manajemen dan Rekapitulasi Cuti</h2>
        <h3 class="text-xl font-semibold text-center text-gray-700 mb-2">Dinas Pendidikan Kota Bogor</h3>
        <h3 class="text-l font-semibold text-center text-gray-700 mb-8">Bidang SMP</h3>
        <?php 
        // Tampilkan pesan error jika ada
        if(!empty($_GET['error'])){
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo 'Username atau password salah.';
            echo '</div>';
        }
        ?>

        <form action="proses_login.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" id="username" name="username" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" id="password" name="password" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-800 hover:bg-blue-950 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Login
                </button>
            </div>
        </form>
    </div>
</body>
</html>
