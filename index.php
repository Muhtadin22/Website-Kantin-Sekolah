<?php
include 'includes/config.php';

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: admin/dashboard.php");
    exit();
}

// Redirect ke halaman login jika belum login
header("Location: login.php");
exit();
?>