<?php 
include '../includes/config.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$jurusan_user = $_SESSION['jurusan'];

// Department names mapping
$jurusan_names = [
    'rpl' => 'RPL',
    'akl' => 'AKL',
    'mp' => 'MP',
    'adnor' => 'ADNOR'
];

// Get department name or default to user's department code if not found
$jurusan_display = $jurusan_names[$jurusan_user] ?? strtoupper($jurusan_user);

// Initialize variables
$error = '';

// Get menu items
$result = $conn->query("SELECT * FROM menu WHERE jurusan = '$jurusan_user' ORDER BY kategori, nama");

// Handle add menu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_menu'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $harga = (int)$_POST['harga'];
    $kategori = $conn->real_escape_string($_POST['kategori']);
    
    // Handle image upload
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/menu/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
        $gambar = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $gambar;
        
        // Check if image file is valid
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["gambar"]["size"] > 500000) {
            $error = "Sorry, your file is too large (max 500KB).";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
    
    if (empty($error)) {
        $sql = "INSERT INTO menu (nama, harga, kategori, jurusan, gambar) 
                VALUES ('$nama', $harga, '$kategori', '$jurusan_user', '$gambar')";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Menu added successfully!";
            header("Location: menu.php");
            exit();
        } else {
            $error = "Error adding menu: " . $conn->error;
        }
    }
}

// Handle edit menu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_menu'])) {
    $id = (int)$_POST['id'];
    $nama = $conn->real_escape_string($_POST['nama']);
    $harga = (int)$_POST['harga'];
    $kategori = $conn->real_escape_string($_POST['kategori']);
    
    // Handle image update
    $gambar_update = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/menu/";
        $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
        $gambar = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $gambar;
        
        // Check if image file is valid
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["gambar"]["size"] > 500000) {
            $error = "Sorry, your file is too large (max 500KB).";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $error = "Sorry, there was an error uploading your file.";
        } else {
            $gambar_update = ", gambar = '$gambar'";
            
            // Delete old image if exists
            $old_img = $conn->query("SELECT gambar FROM menu WHERE id = $id")->fetch_assoc()['gambar'];
            if ($old_img && file_exists($target_dir . $old_img)) {
                unlink($target_dir . $old_img);
            }
        }
    }
    
    if (empty($error)) {
        $sql = "UPDATE menu SET 
                nama = '$nama', 
                harga = $harga, 
                kategori = '$kategori'
                $gambar_update
                WHERE id = $id AND jurusan = '$jurusan_user'";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Menu updated successfully!";
            header("Location: menu.php");
            exit();
        } else {
            $error = "Error updating menu: " . $conn->error;
        }
    }
}

// Handle delete menu
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Delete image file if exists
    $img = $conn->query("SELECT gambar FROM menu WHERE id = $id AND jurusan = '$jurusan_user'")->fetch_assoc()['gambar'];
    if ($img && file_exists("../uploads/menu/" . $img)) {
        unlink("../uploads/menu/" . $img);
    }
    
    $sql = "DELETE FROM menu WHERE id = $id AND jurusan = '$jurusan_user'";
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Menu deleted successfully!";
        header("Location: menu.php");
        exit();
    } else {
        $error = "Error deleting menu: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - <?= htmlspecialchars($jurusan_display) ?> Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4c1d95;
            --primary-light: #7c3aed;
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6d28d9 100%);
            min-height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
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
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: var(--transition);
        }
        
        .header {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0;
        }
        
        /* Card */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        /* Badges */
        .badge-makanan {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }
        
        .badge-minuman {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        
        .badge-snack {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }
        
        /* Table */
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Image thumbnail */
        .img-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        /* Empty state */
        .empty-state {
            padding: 3rem;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle btn btn-primary d-lg-none position-fixed" style="z-index: 1050; top: 10px; left: 10px;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4>Kantin <?= htmlspecialchars($jurusan_display) ?></h4>
        </div>
        <div class="pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="menu.php">
                        <i class="fas fa-utensils"></i> Menu Makanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="transaksi.php">
                        <i class="fas fa-shopping-cart"></i> Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="laporan.php">
                        <i class="fas fa-file-alt"></i> Laporan
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2 class="page-title">Manajemen Menu</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahMenuModal">
                <i class="fas fa-plus me-2"></i> Tambah Menu
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mt-4 animate__animated animate__fadeIn">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Menu Kantin <?= htmlspecialchars($jurusan_display) ?></h5>
                <span class="badge bg-primary">
                    <?= $result->num_rows ?> Menu
                </span>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Gambar</th>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($menu = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <?php if (!empty($menu['gambar'])): ?>
                                                <img src="../uploads/menu/<?= htmlspecialchars($menu['gambar']) ?>" 
                                                     alt="<?= htmlspecialchars($menu['nama']) ?>" 
                                                     class="img-thumbnail">
                                            <?php else: ?>
                                                <div class="img-thumbnail bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-utensils text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($menu['nama']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $menu['kategori'] ?>">
                                                <i class="fas <?= 
                                                    $menu['kategori'] == 'makanan' ? 'fa-utensils' : 
                                                    ($menu['kategori'] == 'minuman' ? 'fa-glass-water' : 'fa-cookie')
                                                ?> me-1"></i>
                                                <?= ucfirst($menu['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <div class="d-flex">
                                                <button class="btn btn-sm btn-outline-primary me-2" 
                                                        data-bs-toggle="modal" data-bs-target="#editMenuModal" 
                                                        onclick="setEditForm(<?= $menu['id'] ?>, '<?= htmlspecialchars($menu['nama']) ?>', <?= $menu['harga'] ?>, '<?= $menu['kategori'] ?>', '<?= $menu['gambar'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="menu.php?hapus=<?= $menu['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h4 class="text-muted mb-3">Belum Ada Menu</h4>
                        <p class="text-muted mb-4">Anda belum menambahkan menu untuk kantin jurusan ini</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahMenuModal">
                            <i class="fas fa-plus me-2"></i> Tambah Menu
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Menu -->
    <div class="modal fade" id="tambahMenuModal" tabindex="-1" aria-labelledby="tambahMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahMenuModalLabel">Tambah Menu Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <select class="form-select" id="kategori" name="kategori" required>
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                                <option value="snack">Snack</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Gambar Menu (Opsional)</label>
                            <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                            <small class="text-muted">Maksimal 500KB (JPG, PNG, GIF)</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        <input type="hidden" name="jurusan" value="<?= $jurusan_user ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_menu" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Menu -->
   <!-- Modal Edit Menu -->
<div class="modal fade" id="editMenuModal" tabindex="-1" aria-labelledby="editMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editMenuModalLabel">
                    <i class="fas fa-edit me-2"></i> Edit Menu
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" name="jurusan" value="<?= $jurusan_user ?>">
                <div class="modal-body">
                    <!-- Form Fields with Improved Styling -->
                    <div class="mb-4">
                        <label for="edit_nama" class="form-label fw-semibold">Nama Menu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-utensils text-primary"></i>
                            </span>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_harga" class="form-label fw-semibold">Harga</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-tag text-primary"></i>
                            </span>
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="edit_harga" name="harga" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_kategori" class="form-label fw-semibold">Kategori</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-filter text-primary"></i>
                            </span>
                            <select class="form-select" id="edit_kategori" name="kategori" required>
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                                <option value="snack">Snack</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_gambar" class="form-label fw-semibold">Gambar Menu</label>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div id="currentImage" class="text-center mb-3">
                                    <!-- Current image will be displayed here -->
                                </div>
                                <div class="file-upload-wrapper">
                                    <label class="btn btn-outline-primary w-100" for="edit_gambar">
                                        <i class="fas fa-camera me-2"></i> Pilih Gambar Baru
                                        <input type="file" class="d-none" id="edit_gambar" name="gambar" accept="image/*">
                                    </label>
                                    <small class="text-muted d-block mt-2 text-center">
                                        Biarkan kosong jika tidak ingin mengubah gambar
                                    </small>
                                </div>
                                <div id="newImagePreview" class="text-center mt-3">
                                    <!-- New image preview will appear here -->
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info p-2">
                            <small>
                                <i class="fas fa-info-circle me-1"></i> Format: JPG, PNG, GIF (Maks. 500KB)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" name="edit_menu" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Image preview for add form
        document.getElementById('gambar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <p class="mb-1">Preview:</p>
                        <img src="${e.target.result}" 
                             alt="Preview Gambar" 
                             class="img-thumbnail">
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Function to set edit form values
        function setEditForm(id, nama, harga, kategori, gambar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_harga').value = harga;
            document.getElementById('edit_kategori').value = kategori;
            
            const currentImage = document.getElementById('currentImage');
            if (gambar) {
                currentImage.innerHTML = `
                    <p class="mb-1">Gambar Saat Ini:</p>
                    <img src="../uploads/menu/${gambar}" 
                         alt="Gambar Menu" 
                         class="img-thumbnail">
                `;
            } else {
                currentImage.innerHTML = '<p class="text-muted">Tidak ada gambar</p>';
            }
            
            // Reset new image preview
            document.getElementById('newImagePreview').innerHTML = '';
            document.getElementById('edit_gambar').value = '';
        }
        
        // Image preview for edit form
        document.getElementById('edit_gambar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('newImagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <p class="mb-1">Preview Gambar Baru:</p>
                        <img src="${e.target.result}" 
                             alt="Preview Gambar Baru" 
                             class="img-thumbnail">
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Auto close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html> 