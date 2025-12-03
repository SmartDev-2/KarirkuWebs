<?php
// admin-auth.php - Login untuk admin dashboard
session_start();

// Include koneksi Supabase
require_once __DIR__ . '/../../function/supabase.php';

// Cek apakah form login dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    
    // Ambil data dari form
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username dan password harus diisi";
        header('Location: login.php');
        exit;
    }
    
    try {
        // Query ke tabel pengguna untuk mencari user
        // Mencari berdasarkan email (yang digunakan sebagai username)
        $result = supabaseQuery('pengguna', [
            'select' => '*',
            'email' => 'eq.' . $username
        ]);
        
        // Cek apakah query berhasil
        if (!$result['success']) {
            throw new Exception("Gagal terhubung ke database");
        }
        
        // Cek apakah user ditemukan
        if (empty($result['data'])) {
            $_SESSION['error'] = "Username atau password salah";
            header('Location: login.php');
            exit;
        }
        
        $user = $result['data'][0];
        
        // Verifikasi password (TANPA HASH - langsung compare plain text)
        if ($password !== $user['password']) {
            $_SESSION['error'] = "Username atau password salah";
            header('Location: login.php');
            exit;
        }
        
        // Cek role - hanya admin yang boleh login
        if ($user['role'] !== 'admin') {
            $_SESSION['error'] = "Akses ditolak. Hanya admin yang dapat login di sini.";
            header('Location: login.php');
            exit;
        }
        
        // Set session untuk admin
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id_pengguna'];
        $_SESSION['username'] = $user['email'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        // Set waktu login
        $_SESSION['login_time'] = time();
        
        // Redirect ke dashboard admin (index.php)
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
        header('Location: login.php');
        exit;
    }
} else {
    // Jika bukan POST request, redirect ke login
    header('Location: login.php');
    exit;
}