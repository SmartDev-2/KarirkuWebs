<?php
session_start();
require_once __DIR__ . '/../../function/supabase.php';

if ($_POST['action'] === 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Semua field harus diisi';
        header('Location: ../company/register.php');
        exit;
    }

    // Check jika email sudah terdaftar menggunakan fungsi yang ada
    $existingUser = supabaseQuery('pengguna', [
        'select' => 'id_pengguna',
        'email' => 'eq.' . $email
    ]);

    if ($existingUser['success'] && count($existingUser['data']) > 0) {
        $_SESSION['error'] = 'Email sudah terdaftar';
        header('Location: ../company/register.php');
        exit;
    }

    // Insert data ke tabel pengguna (password tanpa hash)
    $userData = [
        'nama_lengkap' => $username,
        'email' => $email,
        'password' => $password, // Simpan sebagai plain text
        'role' => 'perusahaan',
        'dibuat_pada' => date('Y-m-d H:i:s'),
        'diperbarui_pada' => date('Y-m-d H:i:s')
    ];

    $result = supabaseInsert('pengguna', $userData);

    if ($result['success']) {
        // Set session
        $_SESSION['user_id'] = $result['data'][0]['id_pengguna'];
        $_SESSION['user_name'] = $username;
        $_SESSION['role'] = 'perusahaan';
        $_SESSION['logged_in'] = true;

        // Redirect ke edit company
        header('Location: ../company/edit_company.php');
        exit;
    } else {
        $_SESSION['error'] = 'Terjadi kesalahan saat registrasi';
        header('Location: ../company/register.php');
        exit;
    }
}

if ($_POST['action'] === 'login') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validasi input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email dan password harus diisi';
        header('Location: ../company/login.php');
        exit;
    }

    // Cari user berdasarkan email menggunakan fungsi yang ada
    $user = supabaseQuery('pengguna', [
        'select' => '*',
        'email' => 'eq.' . $email,
        'role' => 'eq.perusahaan'
    ]);

    if (!$user['success'] || count($user['data']) === 0) {
        $_SESSION['error'] = 'Email atau password salah';
        header('Location: ../company/login.php');
        exit;
    }

    $userData = $user['data'][0];

    // Verifikasi password (tanpa hash, langsung compare plain text)
    if ($password === $userData['password']) {
        // Set session
        $_SESSION['user_id'] = $userData['id_pengguna'];
        $_SESSION['user_name'] = $userData['nama_lengkap'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['logged_in'] = true;

        // Cek status persetujuan perusahaan
        $company = supabaseQuery('perusahaan', [
            'select' => 'status_persetujuan',
            'id_pengguna' => 'eq.' . $userData['id_pengguna']
        ]);

        if ($company['success'] && count($company['data']) > 0) {
            $status = $company['data'][0]['status_persetujuan'];

            if ($status === 'diterima') {
                header('Location: ../company/index.php');
                exit;
            } else {
                // Redirect ke halaman waiting
                header('Location: ../company/waiting_approval.php');
                exit;
            }
        } else {
            // Belum ada data perusahaan, redirect ke edit company
            header('Location: ../company/edit_company.php');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Email atau password salah';
        header('Location: ../company/login.php');
        exit;
    }
}

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ../company/login.php');
    exit;
}

// Check status approval
if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $company = supabaseQuery('perusahaan', [
        'select' => 'status_persetujuan',
        'id_pengguna' => 'eq.' . $user_id
    ]);

    if ($company['success'] && count($company['data']) > 0) {
        echo json_encode(['status' => $company['data'][0]['status_persetujuan']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit;
}