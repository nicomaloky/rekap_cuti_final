<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'dashboard';
$user_role = $_SESSION['role'];

// Jika role view mencoba masuk ke form_cuti, arahkan ke dashboard
if ($user_role == 'view' && $page == 'form_cuti') {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Rekapitulasi Cuti - Disdik Kota Bogor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="assets/image/logo.jpg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <!-- Navbar -->
    <nav class="shadow-md sticky top-0 z-50 animated-navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Judul -->
                <div class="flex items-center">
                    <img class="rounded-md h-12 w-auto" src="assets/image/logo.jpg" alt="Logo">
                    <span class="text-white font-bold text-xl ml-2">Manajemen Rekapitulasi Data Cuti Pegawai</span>
                </div>

                <!-- Menu Desktop -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="?page=dashboard" 
                           class="<?php echo $page == 'dashboard' ? 'bg-blue-800 text-white' : 'text-blue-50 hover:bg-blue-900 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                           Dashboard
                        </a>
                        <?php if ($user_role == 'admin'): ?>
                            <a href="?page=form_cuti" 
                               class="<?php echo $page == 'form_cuti' ? 'bg-blue-800 text-white' : 'text-blue-50 hover:bg-blue-900 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                               Input Cuti
                            </a>
                        <?php endif; ?>
                        <a href="?page=laporan_cuti" 
                           class="<?php echo $page == 'laporan_cuti' ? 'bg-blue-800 text-white' : 'text-blue-50 hover:bg-blue-900 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                           Laporan Cuti
                        </a>
                        <a href="?page=data_pegawai" 
                           class="<?php echo $page == 'data_pegawai' ? 'bg-blue-800 text-white' : 'text-blue-50 hover:bg-blue-900 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                           Data Pegawai
                        </a>
                        <a href="logout.php" 
                           class="bg-red-600 text-white hover:bg-red-700 px-3 py-2 rounded-md text-sm font-medium shadow-md transition duration-150 ease-in-out">
                           Logout
                        </a>
                    </div>
                </div>

                <!-- Tombol Mobile -->
                <div class="-mr-2 flex md:hidden">
                    <button type="button" id="mobile-menu-button" 
                            class="bg-blue-900 p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-blue-800 focus:ring-white">
                        <span class="sr-only">Buka menu utama</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="?page=dashboard" class="<?php echo $page == 'dashboard' ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium transition">Dashboard</a>
                <?php if ($user_role == 'admin'): ?>
                    <a href="?page=form_cuti" class="<?php echo $page == 'form_cuti' ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium transition">Input Cuti</a>
                <?php endif; ?>
                <a href="?page=laporan_cuti" class="<?php echo $page == 'laporan_cuti' ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium transition">Laporan Cuti</a>
                <a href="?page=data_pegawai" class="<?php echo $page == 'data_pegawai' ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium transition">Data Pegawai</a>
                <a href="logout.php" class="bg-red-600 text-white hover:bg-red-700 block px-3 py-2 rounded-md text-base font-medium transition">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php
        switch ($page) {
            case 'laporan_cuti':
                include 'pages/laporan_cuti.php';
                break;
            case 'data_pegawai':
                if ($user_role == 'admin') {
                    include 'pages/data_pegawai.php';
                } else {
                    include 'pages/data_pegawai_view.php';
                }
                break;
            case 'form_cuti':
                if ($user_role == 'admin') {
                    include 'pages/form_cuti.php';
                } else {
                    include 'pages/dashboard.php';
                }
                break;
            case 'dashboard':
            default:
                include 'pages/dashboard.php';
                break;
        }
        ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>
