<?php 
session_start();
include '../includes/config.php';

// Cek session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ensure jurusan is set in session and exists in the $jurusan array
if (!isset($_SESSION['jurusan'])) {
    $_SESSION['jurusan'] = 'admin'; // Default value if not set
}

$jurusan_user = $_SESSION['jurusan'];
$nama_jurusan = isset($jurusan[$jurusan_user]) ? $jurusan[$jurusan_user] : 'Admin';

// Get today's stats
// Get today's stats
$today = date('Y-m-d');
$sql_menu_count = "SELECT COUNT(*) as total FROM menu WHERE jurusan='$jurusan_user'";
$result_menu = $conn->query($sql_menu_count);
$menu_count = $result_menu->fetch_assoc()['total'];

$sql_transaksi_count = "SELECT COUNT(*) as total FROM transaksi WHERE jurusan='$jurusan_user' AND DATE(tanggal)='$today'";
$result_transaksi = $conn->query($sql_transaksi_count);
$transaksi_count = $result_transaksi->fetch_assoc()['total'];

$sql_pendapatan = "SELECT SUM(total) as total FROM transaksi WHERE jurusan='$jurusan_user' AND DATE(tanggal)='$today'";
$result_pendapatan = $conn->query($sql_pendapatan);
$pendapatan = $result_pendapatan->fetch_assoc()['total'] ?? 0;

// Get weekly data for chart
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sql = "SELECT COALESCE(SUM(total), 0) as total FROM transaksi WHERE jurusan='$jurusan_user' AND DATE(tanggal)='$date'";
    $result = $conn->query($sql);
    $weekly_data[] = $result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kantin <?= isset($jurusan[$jurusan_user]) ? $jurusan[$jurusan_user] : 'Admin' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4c1d95;
            --primary-light: #7c3aed;
            --secondary-color: #f59e0b;
            --secondary-light: #fbbf24;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #e2e8f0;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 70px;
        }
        
        body {
            background-color: #f1f5f9;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark-color);
            overflow-x: hidden;
        }
        
        /* Sidebar Modern */
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6d28d9 100%);
            min-height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h4 {
            color: white;
            font-weight: 600;
            margin: 0;
            font-size: 1.25rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 8px;
            margin: 0.25rem 1rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: white;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active::before {
            transform: scaleY(1);
        }
        
        .sidebar .nav-link i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link.logout {
            color: #fecaca;
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        .sidebar .nav-link.logout:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }
        
        /* Main Content */
        main {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s;
            min-height: calc(100vh - var(--topbar-height));
            padding-top: calc(var(--topbar-height) + 1rem);
        }
        
        /* Topbar Glassmorphism */
        .topbar {
            height: var(--topbar-height);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 999;
            transition: all 0.3s;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
        }
        
        /* Modern Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow: hidden;
            margin-bottom: 1.5rem;
            background-color: white;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Stat Cards with Icons */
        .stat-card {
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary-color);
        }
        
        .stat-card.success {
            border-left-color: var(--success-color);
        }
        
        .stat-card.info {
            border-left-color: var(--info-color);
        }
        
        .stat-card .icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2.5rem;
            opacity: 0.15;
            color: inherit;
        }
        
        .stat-card .card-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            opacity: 0.8;
            font-weight: 500;
        }
        
        .stat-card .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .card-change {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .stat-card .card-change.up {
            color: var(--success-color);
        }
        
        .stat-card .card-change.down {
            color: var(--danger-color);
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Quick Access Buttons */
        .quick-access-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-radius: 10px;
            color: var(--dark-color);
            text-decoration: none;
            background-color: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .quick-access-btn:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
        }
        
        .quick-access-btn:hover i {
            color: white;
        }
        
        .quick-access-btn i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-light);
            transition: all 0.3s ease;
        }
        
        .quick-access-btn .title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        /* Kantin Access Buttons */
        .kantin-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .kantin-access-btn {
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 2px solid var(--primary-light);
            background-color: rgba(124, 58, 237, 0.1);
            text-align: center;
            text-decoration: none;
            color: var(--dark-color);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .kantin-access-btn:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-3px);
        }
        
        .kantin-access-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .kantin-access-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: var(--gray-color);
            background-color: rgba(226, 232, 240, 0.5);
        }
        
        .kantin-access-btn.disabled:hover {
            background-color: rgba(226, 232, 240, 0.5);
            color: var(--dark-color);
            transform: none;
        }
        
        /* Access Alert */
        .access-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
            animation: slideInRight 0.3s, fadeOut 0.5s 2.5s forwards;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1050;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            main, .topbar {
                margin-left: 0;
                left: 0;
            }
            
            .sidebar-collapse-btn {
                display: block !important;
            }
            
            .kantin-access-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .kantin-access-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animation Classes */
        .animate-delay-1 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-2 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-3 {
            animation-delay: 0.3s;
        }
        
        /* Button Styles */
        .btn-outline-primary {
            border-color: var(--primary-light);
            color: var(--primary-light);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }
        
        /* Toggle Button for Mobile */
        .sidebar-collapse-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            z-index: 1051;
            position: fixed;
            top: 15px;
            left: 15px;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Fix for dropdown menus */
        .dropdown-menu {
            z-index: 1060;
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-collapse-btn d-lg-none">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4 class="animate__animated animate__fadeIn">Kantin <?= isset($jurusan[$jurusan_user]) ? $jurusan[$jurusan_user] : 'Admin' ?></h4>
        </div>
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item animate__animated animate__fadeInUp">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate-delay-1">
                    <a class="nav-link" href="menu.php">
                        <i class="bi bi-book"></i> <span>Menu Makanan</span>
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate-delay-2">
                    <a class="nav-link" href="transaksi.php">
                        <i class="bi bi-cart"></i> <span>Transaksi</span>
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate-delay-3">
                    <a class="nav-link" href="laporan.php">
                        <i class="bi bi-file-earmark-text"></i> <span>Laporan</span>
                    </a>
                </li>
                <li class="nav-item mt-3 animate__animated animate__fadeInUp">
                    <a class="nav-link logout" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Topbar -->
    <nav class="topbar d-flex justify-content-between align-items-center">
        <div></div> <!-- Empty div for spacing -->
        <div class="d-flex align-items-center">
            <span class="me-3 d-none d-md-inline">Halo, <?= $_SESSION['username'] ?></span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="periodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Hari Ini
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="periodDropdown">
                    <li><button class="dropdown-item active" type="button">Hari Ini</button></li>
                    <li><button class="dropdown-item" type="button">Minggu Ini</button></li>
                    <li><button class="dropdown-item" type="button">Bulan Ini</button></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header animate__animated animate__fadeIn">
                <h1 class="h2 mb-0">Dashboard Kantin <?= isset($jurusan[$jurusan_user]) ? $jurusan[$jurusan_user] : 'Admin' ?></h1>
                <p class="text-muted mb-0">Ringkasan aktivitas dan statistik terkini</p>
            </div>
            
            <!-- Stat Cards -->
            <div class="row mb-4 g-4">
                <div class="col-md-4 animate__animated animate__fadeInLeft">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="card-title">Total Menu</div>
                                    <div class="card-value"><?= $menu_count ?></div>
                                    <div class="card-change up">
                                        <i class="bi bi-arrow-up"></i> 2 dari kemarin
                                    </div>
                                </div>
                                <i class="bi bi-book icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInUp">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="card-title">Transaksi Hari Ini</div>
                                    <div class="card-value"><?= $transaksi_count ?></div>
                                    <div class="card-change up">
                                        <i class="bi bi-arrow-up"></i> 5 dari kemarin
                                    </div>
                                </div>
                                <i class="bi bi-cart icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate__animated animate__fadeInRight">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="card-title">Pendapatan Hari Ini</div>
                                    <div class="card-value">Rp <?= number_format($pendapatan, 0, ',', '.') ?></div>
                                    <div class="card-change up">
                                        <i class="bi bi-arrow-up"></i> 10% dari kemarin
                                    </div>
                                </div>
                                <i class="bi bi-currency-dollar icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row g-4">
                <!-- Chart -->
                <div class="col-lg-8">
                    <div class="card animate__animated animate__fadeInLeft h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Pendapatan 7 Hari Terakhir</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chartDropdown">
                                    <li><a class="dropdown-item active" href="#">Minggu Ini</a></li>
                                    <li><a class="dropdown-item" href="#">Bulan Ini</a></li>
                                    <li><a class="dropdown-item" href="#">Tahun Ini</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Access -->
                <div class="col-lg-4">
                    <div class="card animate__animated animate__fadeInRight h-100">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Akses Cepat</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="menu.php?action=tambah" class="quick-access-btn animate__animated animate__fadeIn">
                                        <i class="bi bi-plus-circle"></i>
                                        <div class="title">Tambah Menu</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="transaksi.php" class="quick-access-btn animate__animated animate__fadeIn animate-delay-1">
                                        <i class="bi bi-cart-plus"></i>
                                        <div class="title">Transaksi Baru</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="laporan.php" class="quick-access-btn animate__animated animate__fadeIn animate-delay-2">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <div class="title">Lihat Laporan</div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="menu.php" class="quick-access-btn animate__animated animate__fadeIn animate-delay-3">
                                        <i class="bi bi-pencil-square"></i>
                                        <div class="title">Kelola Menu</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kantin Access Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card animate__animated animate__fadeInUp">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Akses Kantin</h6>
                        </div>
                        <div class="card-body">
                            <div class="kantin-access-grid">
                                <?php if(isset($jurusan) && is_array($jurusan)): ?>
                                    <?php foreach ($jurusan as $key => $value): ?>
                                        <a href="transaksi.php?jurusan=<?= $key ?>" 
   class="kantin-access-btn <?= $jurusan_user !== $key ? 'disabled' : '' ?>">
   <i class="bi bi-shop"></i>
   <span>Kantin <?= $value ?></span>
   <?php if ($jurusan_user !== $key): ?>
       <small class="text-muted">Akses dibatasi</small>
   <?php endif; ?>
</a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Tidak ada data kantin yang tersedia</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Access Alert (hidden by default) -->
    <div class="access-alert alert alert-warning alert-dismissible fade show d-none" role="alert">
        <strong>Akses Dibatasi!</strong> Anda hanya dapat mengakses kantin <?= $nama_jurusan ?>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle Sidebar on Mobile
        document.querySelector('.sidebar-collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    '<?= date('D', strtotime('-6 days')) ?>', 
                    '<?= date('D', strtotime('-5 days')) ?>', 
                    '<?= date('D', strtotime('-4 days')) ?>', 
                    '<?= date('D', strtotime('-3 days')) ?>', 
                    '<?= date('D', strtotime('-2 days')) ?>', 
                    '<?= date('D', strtotime('-1 day')) ?>', 
                    'Hari Ini'
                ],
                datasets: [{
                    label: 'Pendapatan',
                    data: [<?= implode(',', $weekly_data) ?>],
                    backgroundColor: [
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(124, 58, 237, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderColor: [
                        'rgba(124, 58, 237, 1)',
                        'rgba(124, 58, 237, 1)',
                        'rgba(124, 58, 237, 1)',
                        'rgba(124, 58, 237, 1)',
                        'rgba(124, 58, 237, 1)',
                        'rgba(124, 58, 237, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Handle kantin access buttons
        document.querySelectorAll('.kantin-access-btn.disabled').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const alert = document.querySelector('.access-alert');
                alert.classList.remove('d-none');
                
                setTimeout(() => {
                    alert.classList.add('d-none');
                }, 3000);
            });
        });
    </script>
</body>
</html>