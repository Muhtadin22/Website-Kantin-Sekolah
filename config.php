<?php
session_start();

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Password MySQL (default XAMPP kosong)
$db_name = 'kantin_sekolah';

try {
    // Coba koneksi ke MySQL server
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Periksa koneksi
    if ($conn->connect_error) {
        throw new Exception("MySQL Connection failed: " . $conn->connect_error);
    }
    
    // Cek dan buat database jika tidak ada
    if (!$conn->select_db($db_name)) {
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if ($conn->query($sql) === TRUE) {
            $conn->select_db($db_name);
            // Buat tabel-tabel dasar jika diperlukan
            initializeDatabase($conn);
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    }
    
    // Set charset untuk mencegah masalah encoding
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

$jurusan = [
    'RPL' => 'Rekayasa Perangkat Lunak',
    'AKL' => 'Akuntansi Keuangan Lembaga',
    'MP'  => 'Manajemen Perkantoran',
    'ADNOR' => 'Administrasi Niaga dan Otomatisasi Rumah Tangga'
];

if (!function_exists('clean_input')) {
    function clean_input($data) {
        global $conn;
        return htmlspecialchars(strip_tags($conn->real_escape_string($data)));
    }
}

// Fungsi untuk inisialisasi database
function initializeDatabase($conn) {
    // Contoh: Buat tabel users jika tidak ada
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','user') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating table: " . $conn->error);
    }
}
?>