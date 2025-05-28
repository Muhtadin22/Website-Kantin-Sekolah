<?php 
include '../includes/config.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$jurusan_user = $_SESSION['jurusan'];

// Process transaction update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaksi'])) {
    $transaksi_id = $conn->real_escape_string($_POST['transaksi_id']);
    $nama_pembeli = $conn->real_escape_string($_POST['nama_pembeli']);
    $metode_bayar = $conn->real_escape_string($_POST['metode_bayar']);
    $total = (float)$_POST['total'];
    
    // Process items
    $items = json_decode($_POST['items'], true);
    if (!is_array($items)) {
        $error = "Invalid items format";
    } else {
        $items_json = $conn->real_escape_string(json_encode($items));
        
        $sql = "UPDATE transaksi SET 
                nama_pembeli='$nama_pembeli', 
                metode_bayar='$metode_bayar', 
                total='$total', 
                items='$items_json' 
                WHERE id='$transaksi_id' AND jurusan='$jurusan_user'";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Transaction updated successfully!";
            header("Location: laporan.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Process delete transaction
if (isset($_GET['hapus'])) {
    $id = $conn->real_escape_string($_GET['hapus']);
    $sql = "DELETE FROM transaksi WHERE id='$id' AND jurusan='$jurusan_user'";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Transaction deleted successfully!";
        header("Location: laporan.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get transaction data
$sql_transaksi = "SELECT * FROM transaksi WHERE jurusan='$jurusan_user' ORDER BY tanggal DESC";
$result_transaksi = $conn->query($sql_transaksi);

// Calculate totals
$sql_total = "SELECT SUM(total) as total FROM transaksi WHERE jurusan='$jurusan_user'";
$result_total = $conn->query($sql_total);
$total_pendapatan = $result_total->fetch_assoc()['total'] ?? 0;

$sql_count = "SELECT COUNT(*) as total FROM transaksi WHERE jurusan='$jurusan_user'";
$result_count = $conn->query($sql_count);
$total_transaksi = $result_count->fetch_assoc()['total'] ?? 0;

$sql_menu = "SELECT COUNT(*) as total FROM menu WHERE jurusan='$jurusan_user'";
$result_menu = $conn->query($sql_menu);
$total_menu = $result_menu->fetch_assoc()['total'] ?? 0;

// Get menu list for dropdown
$sql_menu_list = "SELECT * FROM menu WHERE jurusan='$jurusan_user' ORDER BY nama";
$result_menu_list = $conn->query($sql_menu_list);
$menu_options = [];
while ($menu = $result_menu_list->fetch_assoc()) {
    $menu_options[$menu['id']] = $menu;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAPORAN KANTIN - <?= htmlspecialchars($jurusan[$jurusan_user]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4c1d95;
            --primary-light: #7c3aed;
            --secondary-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark-color);
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
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h4 {
            color: white;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 8px;
            margin: 0.25rem 1rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
            font-weight: 500;
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
        }
        
        /* Main Content */
        main {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: all 0.3s;
        }
        
        /* Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Table */
        .table-responsive {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        /* Badges */
        .badge-payment {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }
        
        .badge-cash {
            background-color: var(--success-color);
        }
        
        .badge-transfer {
            background-color: #3b82f6;
        }
        
        .badge-qris {
            background-color: var(--secondary-color);
        }
        
        /* Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            main {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        /* Item card in modal */
        .item-card {
            border-left: 3px solid var(--primary-color);
            transition: all 0.2s;
        }
        
        .item-card:hover {
            transform: translateX(3px);
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
        <div class="sidebar-brand text-center">
            <h4 class="animate__animated animate__fadeIn"><?= $jurusan[$jurusan_user] ?> Canteen</h4>
        </div>
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item animate__animated animate__fadeInUp">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate__delay-1s">
                    <a class="nav-link" href="menu.php">
                        <i class="fas fa-utensils"></i> Menu
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate__delay-2s">
                    <a class="nav-link" href="transaksi.php">
                        <i class="fas fa-shopping-cart"></i> Transaksi
                    </a>
                </li>
                <li class="nav-item animate__animated animate__fadeInUp animate__delay-3s">
                    <a class="nav-link active" href="laporan.php">
                        <i class="fas fa-file-alt"></i> Laporan
                    </a>
                </li>
                <li class="nav-item mt-3 animate__animated animate__fadeInUp">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2 animate__animated animate__fadeIn">LAPORAN KANTIN</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-calendar me-1"></i> Hari ini
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 animate__animated animate__fadeInLeft">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Menu</h5>
                                <h2 class="card-text"><?= $total_menu ?></h2>
                            </div>
                            <i class="fas fa-utensils fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Transaksi</h5>
                                <h2 class="card-text"><?= $total_transaksi ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInRight">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Pendapatan</h5>
                                <h2 class="card-text">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h2>
                            </div>
                            <i class="fas fa-wallet fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card animate__animated animate__fadeInUp">
            <div class="card-header bg-white">
                <h5 class="mb-0">Table Transaksi</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Menu</th>
                                <th>Total</th>
                                <th>Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_transaksi->num_rows > 0): ?>
                                <?php while ($row = $result_transaksi->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pembeli'] ?? 'Customer') ?></td>
                                        <td>
                                            <?php 
                                            $items = json_decode($row['items'], true);
                                            if (is_array($items)): 
                                                foreach ($items as $item): 
                                            ?>
                                                    <div class="d-flex justify-content-between">
                                                        <span><?= htmlspecialchars($item['nama'] ?? 'Unknown Item') ?></span>
                                                        <span class="text-muted"><?= $item['qty'] ?? 0 ?>x</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-muted">No items data</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch ($row['metode_bayar']) {
                                                case 'cash': $badge_class = 'badge-cash'; break;
                                                case 'transfer': $badge_class = 'badge-transfer'; break;
                                                case 'qris': $badge_class = 'badge-qris'; break;
                                                default: $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge badge-payment <?= $badge_class ?>">
                                                <?= ucfirst($row['metode_bayar']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-action btn btn-sm btn-outline-primary me-1" 
                                                    data-bs-toggle="modal" data-bs-target="#editModal" 
                                                    data-id="<?= $row['id'] ?>" 
                                                    data-transaksi='<?= json_encode($row) ?>'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="laporan.php?hapus=<?= $row['id'] ?>" 
                                               class="btn-action btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this transaction?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                        <h5>No transactions found</h5>
                                        <p class="text-muted">Transactions will appear here</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Transaction Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editModalLabel">Ubah Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="transaksi_id" id="transaksi_id">
                        <input type="hidden" name="items" id="items_data">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nama_pembeli" class="form-label">Nama Pelanggan</label>
                                <input type="text" class="form-control" id="nama_pembeli" name="nama_pembeli" required>
                            </div>
                            <div class="col-md-6">
                                <label for="metode_bayar" class="form-label">Metode Pembayaran</label>
                                <select class="form-select" id="metode_bayar" name="metode_bayar" required>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Bank Transfer</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Menu</label>
                            <div id="items_container">
                                <!-- Items will be added here dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_item_btn">
                                <i class="fas fa-plus me-1"></i> Tambah Menu
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="total" name="total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                        <button type="submit" name="update_transaksi" class="btn btn-primary">Simpan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Menu Selection Modal -->
    <div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Select Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select mb-3" id="menu_select">
                        <?php foreach ($menu_options as $id => $menu): ?>
                            <option value="<?= $id ?>" data-harga="<?= $menu['harga'] ?>">
                                <?= htmlspecialchars($menu['nama']) ?> (Rp <?= number_format($menu['harga'], 0, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mb-3">
                        <label for="item_qty" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="item_qty" min="1" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="select_menu_btn">Select</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize variables
        let currentItems = [];
        let currentEditIndex = null;
        const menuModal = new bootstrap.Modal(document.getElementById('menuModal'));
        const editModal = document.getElementById('editModal');
        
        // Toggle sidebar on mobile
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Handle edit modal show
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const transaksiId = button.getAttribute('data-id');
                const transaksiData = JSON.parse(button.getAttribute('data-transaksi'));
                
                // Set basic fields
                document.getElementById('transaksi_id').value = transaksiId;
                document.getElementById('nama_pembeli').value = transaksiData.nama_pembeli || '';
                document.getElementById('metode_bayar').value = transaksiData.metode_bayar || 'cash';
                
                // Parse items
                try {
                    currentItems = JSON.parse(transaksiData.items) || [];
                } catch (e) {
                    currentItems = [];
                }
                updateItemsDisplay();
                calculateTotal();
            });
        }
        
        // Add item button
        document.getElementById('add_item_btn').addEventListener('click', function() {
            currentEditIndex = null;
            document.getElementById('menu_select').value = '';
            document.getElementById('item_qty').value = 1;
            menuModal.show();
        });
        
        // Select menu button
        document.getElementById('select_menu_btn').addEventListener('click', function() {
            const menuSelect = document.getElementById('menu_select');
            const selectedOption = menuSelect.options[menuSelect.selectedIndex];
            const menuId = menuSelect.value;
            const menuName = selectedOption.text.split(' (')[0];
            const menuPrice = parseFloat(selectedOption.getAttribute('data-harga'));
            const qty = parseInt(document.getElementById('item_qty').value) || 1;
            
            const newItem = {
                id: menuId,
                nama: menuName,
                harga: menuPrice,
                qty: qty
            };
            
            if (currentEditIndex !== null) {
                // Update existing item
                currentItems[currentEditIndex] = newItem;
            } else {
                // Add new item
                currentItems.push(newItem);
            }
            
            updateItemsDisplay();
            calculateTotal();
            menuModal.hide();
        });
        
        // Update items display
        function updateItemsDisplay() {
            const container = document.getElementById('items_container');
            container.innerHTML = '';
            
            if (!Array.isArray(currentItems)) {
                currentItems = [];
            }
            
            currentItems.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'card mb-2 item-card';
                itemDiv.innerHTML = `
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${item.nama || 'Unknown Item'}</h6>
                                <small class="text-muted">${item.qty || 0} x Rp ${(item.harga || 0).toLocaleString('id-ID')}</small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-item-btn" data-index="${index}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-item-btn" data-index="${index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(itemDiv);
            });
            
            // Add event listeners for edit and delete buttons
            document.querySelectorAll('.edit-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    editItem(index);
                });
            });
            
            document.querySelectorAll('.delete-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    deleteItem(index);
                });
            });
            
            // Update hidden field with JSON data
            document.getElementById('items_data').value = JSON.stringify(currentItems);
        }
        
        // Edit item
        function editItem(index) {
            currentEditIndex = index;
            const item = currentItems[index];
            
            // Set values in menu modal
            document.getElementById('menu_select').value = item.id || '';
            document.getElementById('item_qty').value = item.qty || 1;
            
            menuModal.show();
        }
        
        // Delete item
        function deleteItem(index) {
            if (confirm('Are you sure you want to delete this item?')) {
                currentItems.splice(index, 1);
                updateItemsDisplay();
                calculateTotal();
            }
        }
        
        // Calculate total
        function calculateTotal() {
            let total = 0;
            if (!Array.isArray(currentItems)) {
                currentItems = [];
            }
            
            currentItems.forEach(item => {
                total += (item.harga || 0) * (item.qty || 0);
            });
            document.getElementById('total').value = total;
        }
    </script>
</body>
</html>