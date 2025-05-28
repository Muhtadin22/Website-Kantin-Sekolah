<?php
include '../includes/config.php';

// Cek session
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$jurusan = isset($_GET['jurusan']) ? $conn->real_escape_string($_GET['jurusan']) : '';

// Ambil data menu berdasarkan jurusan
$sql_menu = "SELECT * FROM menu WHERE jurusan='$jurusan' ORDER BY nama";
$result_menu = $conn->query($sql_menu);

if ($result_menu->num_rows > 0) {
    while ($menu = $result_menu->fetch_assoc()) {
        echo '
        <div class="col-md-4 mb-3">
            <div class="card menu-card h-100 animate__animated animate__fadeIn" 
                 onclick="addToCart('.$menu['id'].', \''.htmlspecialchars($menu['nama']).'\', '.$menu['harga'].', \''.$menu['kategori'].'\', \''.$menu['jurusan'].'\')">
                <div class="card-body">
                    <h6 class="card-title">'.htmlspecialchars($menu['nama']).'</h6>
                    <span class="badge badge-category badge-'.$menu['kategori'].' mb-2">
                        '.ucfirst(htmlspecialchars($menu['kategori'])).'
                    </span>
                    <p class="card-text fw-bold text-primary">Rp '.number_format($menu['harga'], 0, ',', '.').'</p>
                    <small class="text-muted">'.$menu['jurusan'].'</small>
                </div>
            </div>
        </div>';
    }
} else {
    echo '
    <div class="col-12">
        <div class="empty-state">
            <i class="fas fa-utensils"></i>
            <h5>Belum ada menu tersedia</h5>
        </div>
    </div>';
}
?>