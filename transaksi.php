<?php 
include '../includes/config.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
// Pastikan jurusan ada di session
if (!isset($_SESSION['jurusan'])) {
    $_SESSION['jurusan'] = 'admin';
}
$jurusan_user = $_SESSION['jurusan'];
// Validasi akses berdasarkan jurusan
if (isset($_GET['jurusan'])) {
    $target_jurusan = $_GET['jurusan'];

    if ($jurusan_user !== $target_jurusan) {
        echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const alertBox = document.querySelector(".access-alert");
                if (alertBox) {
                    alertBox.classList.remove("d-none");

                    setTimeout(() => {
                        alertBox.classList.add("d-none");
                        window.history.back();
                    }, 3000);
                }
            });
        </script>';
        exit();
    }
}
// Define departments and their names
$jurusans = [
    'RPL' => 'Rekayasa Perangkat Lunak',
    'AKL' => 'Akuntansi dan Keuangan Lembaga',
    'MP' => 'Manajemen Perkantoran',
    'Adnor' => 'Administrasi Perkantoran',
];
$jurusan_user = $_SESSION['jurusan'] ?? 'RPL'; // Default to RPL if not set
$nama_jurusan = $jurusans[$jurusan_user] ?? 'Admin';
function getDefaultImage($kategori) {
    $defaultImages = [
        'makanan' => '../assets/img/default-food.jpg',
        'minuman' => '../assets/img/default-drink.jpg',
        'snack' => '../assets/img/default-snack.jpg',
        'lainnya' => '../assets/img/default-other.jpg'
    ];
    return $defaultImages[$kategori] ?? '../assets/img/default-other.jpg';
}

// Proses transaksi baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_transaksi'])) {
    $items = [];
    $total = 0;
    $jurusan_transaksi = $conn->real_escape_string($_POST['jurusan_transaksi']);
    $nama_pembeli = $conn->real_escape_string($_POST['nama_pembeli']);
    
    foreach ($_POST['menu_id'] as $key => $menu_id) {
        $qty = $_POST['qty'][$key];
        if ($qty > 0) {
            $menu = $conn->query("SELECT * FROM menu WHERE id='$menu_id'")->fetch_assoc();
            $subtotal = $menu['harga'] * $qty;
            $total += $subtotal;
            
            $items[] = [
                'id' => $menu_id,
                'nama' => $menu['nama'],
                'harga' => $menu['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
    
    if (!empty($items)) {
        $metode_bayar = $conn->real_escape_string($_POST['metode_bayar']);
        $items_json = $conn->real_escape_string(json_encode($items));
        $tanggal = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO transaksi (jurusan, user_id, items, total, metode_bayar, tanggal, nama_pembeli) 
                VALUES ('$jurusan_transaksi', '{$_SESSION['user_id']}', '$items_json', '$total', '$metode_bayar', '$tanggal', '$nama_pembeli')";
        
        if ($conn->query($sql)) {
            $transaction_id = $conn->insert_id;
            
            // Simpan data struk di session untuk ditampilkan di halaman receipt
            $_SESSION['receipt_data'] = [
                'transaction_id' => $transaction_id,
                'jurusan' => $jurusan_transaksi,
                'nama_pembeli' => $nama_pembeli,
                'items' => $items,
                'total' => $total,
                'metode_bayar' => $metode_bayar,
                'tanggal' => $tanggal
            ];
            
            // Redirect ke halaman struk
            header("Location: receipt.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    } else {
        $error = "Tidak ada item yang dipilih!";
    }
}

// Get menu data only for the user's department
$stmt = $conn->prepare("SELECT * FROM menu WHERE jurusan=? ORDER BY nama");
$stmt->bind_param("s", $jurusan_user);
$stmt->execute();
$result_menu = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Kantin <?= htmlspecialchars($nama_jurusan) ?></title>
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
        
        /* Updated Menu Card Styles */
        .menu-card {
            cursor: pointer;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .card-img-top {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .menu-card:hover .card-img-top {
            transform: scale(1.1);
        }
        
        .card-hover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(76, 29, 149, 0.8) 0%, rgba(76, 29, 149, 0.4) 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 1rem;
        }
        
        .menu-card:hover .card-hover-overlay {
            opacity: 1;
        }
        
        .hover-overlay-text {
            color: white;
            text-align: center;
            width: 100%;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
        }
        
        .card-body {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            flex-grow: 1;
        }
        
        .card-footer {
            background: white;
            border-top: none;
            padding: 0.75rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price-tag {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        /* Category Badge */
        .badge-category {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .badge-makanan {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-minuman {
            background-color: var(--info-color);
            color: white;
        }
        
        .badge-snack {
            background-color: var(--warning-color);
            color: white;
        }
        
        .badge-lainnya {
            background-color: var(--danger-color);
            color: white;
        }
        
        .qty-input {
            width: 40px;
            text-align: center;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            
            .card-img-container {
                height: 180px;
            }
        }
        
        @media (max-width: 768px) {
            .card-img-container {
                height: 160px;
            }
            
            .card-title {
                font-size: 1rem;
            }
            
            .price-tag {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .card-img-container {
                height: 140px;
            }
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
        
        /* Fix for dropdown menus */
        .dropdown-menu {
            z-index: 1060;
        }
        
        /* Empty cart state */
        .empty-cart {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
        }
        
        .empty-cart i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
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
            <h4 class="animate__animated animate__fadeIn">Kantin <?= htmlspecialchars($nama_jurusan) ?></h4>
        </div>
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item animate__animated animate__fadeInUp">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate-delay-1">
                    <a class="nav-link" href="menu.php">
                        <i class="bi bi-book"></i> <span>Menu Makanan</span>
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate-delay-2">
                    <a class="nav-link active" href="transaksi.php">
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
            <span class="me-3 d-none d-md-inline">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="periodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= htmlspecialchars($nama_jurusan) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="periodDropdown">
                    <?php foreach ($jurusans as $key => $value): ?>
                        <li>
                            <button class="dropdown-item <?= $key === $jurusan_user ? 'active' : '' ?>" 
                                   type="button" onclick="showAccessAlert('<?= $key ?>')">
                                <?= htmlspecialchars($value) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header animate__animated animate__fadeIn">
                <h1 class="h2 mb-0">Transaksi Kantin <?= htmlspecialchars($nama_jurusan) ?></h1>
                <p class="text-muted mb-0">Kelola transaksi pembelian di kantin Anda</p>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Menu List -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Daftar Menu</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="jurusanDropdown" data-bs-toggle="dropdown">
                                        Jurusan: <?= htmlspecialchars($jurusan_user) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($jurusans as $key => $value): ?>
                                            <li>
                                                <a class="dropdown-item <?= $key === $jurusan_user ? 'active' : '' ?>" 
                                                   href="#" onclick="showAccessAlert('<?= $key ?>')">
                                                    <?= htmlspecialchars($value) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row" id="menuContainer">
                                <?php if ($result_menu->num_rows > 0): ?>
                                    <?php while ($menu = $result_menu->fetch_assoc()): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card menu-card h-100" onclick="addToCart(<?= $menu['id'] ?>, '<?= htmlspecialchars($menu['nama']) ?>', <?= $menu['harga'] ?>, '<?= $menu['kategori'] ?>')">
                                                <div class="card-img-container">
                                                    <span class="badge badge-category badge-<?= $menu['kategori'] ?>">
                                                        <?= ucfirst($menu['kategori']) ?>
                                                    </span>
                                                    <img src="<?= !empty($menu['gambar']) ? "../uploads/menu/".htmlspecialchars($menu['gambar']) : getDefaultImage($menu['kategori']) ?>" 
                                                         class="card-img-top" 
                                                         alt="<?= htmlspecialchars($menu['nama']) ?>">
                                                    <div class="card-hover-overlay">
                                                        <div class="hover-overlay-text">
                                                            <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($menu['nama']) ?></h5>
                                                </div>
                                                <div class="card-footer">
                                                    <span class="price-tag">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); addToCart(<?= $menu['id'] ?>, '<?= htmlspecialchars($menu['nama']) ?>', <?= $menu['harga'] ?>, '<?= $menu['kategori'] ?>')">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-4">
                                        <i class="bi bi-utensils fa-3x text-muted mb-3"></i>
                                        <p>Tidak ada menu yang tersedia untuk jurusan Anda</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shopping Cart -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Keranjang Belanja</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="transactionForm">
                                <input type="hidden" name="jurusan_transaksi" value="<?= htmlspecialchars($jurusan_user) ?>">
                                
                                <div class="mb-3">
                                    <label for="nama_pembeli" class="form-label">Nama Pembeli</label>
                                    <input type="text" class="form-control" id="nama_pembeli" name="nama_pembeli" required>
                                </div>
                                
                                <div id="cartItems">
                                    <div class="empty-cart">
                                        <i class="bi bi-cart"></i>
                                        <p>Keranjang Anda kosong</p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Total:</h5>
                                    <h5 id="totalAmount">Rp 0</h5>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="metode_bayar" class="form-label">Metode Pembayaran</label>
                                    <select class="form-select" id="metode_bayar" name="metode_bayar" required>
                                        <option value="cash">Tunai</option>
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="qris">QRIS</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="proses_transaksi" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle me-2"></i> Proses Transaksi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Access Restricted Modal -->
    <div class="modal fade" id="accessRestrictedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Akses Dibatasi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="bi bi-ban fa-4x text-danger mb-3"></i>
                        <h5>Akses ke jurusan ini dibatasi</h5>
                        <p>Anda hanya dapat mengakses jurusan <?= htmlspecialchars($nama_jurusan) ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let total = 0;
        const accessModal = new bootstrap.Modal(document.getElementById('accessRestrictedModal'));
        
        // Toggle Sidebar on Mobile
        document.querySelector('.sidebar-collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Show access restricted alert when trying to select other departments
        function showAccessAlert(jurusan) {
            if (jurusan !== '<?= $jurusan_user ?>') {
                accessModal.show();
            }
        }
        
        // Add item to cart
        function addToCart(id, nama, harga, kategori) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.qty += 1;
                existingItem.subtotal = existingItem.qty * harga;
            } else {
                cart.push({
                    id: id,
                    nama: nama,
                    harga: harga,
                    kategori: kategori,
                    qty: 1,
                    subtotal: harga
                });
            }
            
            updateCartDisplay();
        }
        
        // Update quantity
        function updateQty(index, change) {
            const item = cart[index];
            const newQty = item.qty + change;
            
            if (newQty < 1) {
                removeFromCart(index);
                return;
            }
            
            item.qty = newQty;
            item.subtotal = item.harga * newQty;
            updateCartDisplay();
        }
        
        // Remove item from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
        
        // Update cart display
        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cartItems');
            const totalAmountElement = document.getElementById('totalAmount');
            const transactionForm = document.getElementById('transactionForm');
            
            // Calculate total
            total = cart.reduce((sum, item) => sum + item.subtotal, 0);
            
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart"></i>
                        <p>Keranjang Anda kosong</p>
                    </div>
                `;
                totalAmountElement.textContent = 'Rp 0';
                return;
            }
            
            // Build cart items
            let html = '';
            cart.forEach((item, index) => {
                html += `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">${item.nama}</h6>
                                <span class="badge badge-category badge-${item.kategori}">
                                    ${item.kategori.charAt(0).toUpperCase() + item.kategori.slice(1)}
                                </span>
                                <div class="mt-1">
                                    <small class="text-muted">Rp ${item.harga.toLocaleString('id-ID')} × ${item.qty}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-2 text-primary">Rp ${item.subtotal.toLocaleString('id-ID')}</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-action" onclick="event.stopPropagation(); updateQty(${index}, -1)">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" name="qty[]" value="${item.qty}" min="1" class="form-control form-control-sm qty-input" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-action" onclick="event.stopPropagation(); updateQty(${index}, 1)">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-action" onclick="event.stopPropagation(); removeFromCart(${index})">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="menu_id[]" value="${item.id}">
                    </div>
                `;
            });
            
            cartItemsContainer.innerHTML = html;
            totalAmountElement.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.sidebar-collapse-btn');
            
            if (window.innerWidth < 992 && !sidebar.contains(event.target)) {
                if (event.target !== toggleBtn && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Form validation
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang belanja kosong. Silakan tambahkan item terlebih dahulu.');
            }
        });
    </script>
</body>
</html>