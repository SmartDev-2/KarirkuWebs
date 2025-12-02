<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

if ($_POST['action'] == 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Semua field harus diisi!";
        header('Location: ../views/register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid!";
        header('Location: ../views/register.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password harus minimal 6 karakter!";
        header('Location: ../views/register.php');
        exit;
    }

    $result = supabaseQuery('pengguna', [
        'select' => 'id_pengguna',
        'email' => 'eq.' . $email
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        $_SESSION['error'] = "Email sudah terdaftar!";
        header('Location: ../views/register.php');
        exit;
    }

    // Di bagian register manual, tambahkan:
    $newUser = supabaseInsert('pengguna', [
        'nama_lengkap' => $username,
        'email' => $email,
        'password' => $password,
        'dibuat_pada' => date('Y-m-d H:i:s'),
        'email_verified' => false  // ❌ MANUAL REGISTRATION BELUM VERIFIED
    ]);

    if ($newUser['success']) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = $username;
        $_SESSION['user_id'] = $newUser['data'][0]['id_pengguna'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'pencaker';

        // Notifikasi sukses registrasi
        $_SESSION['success'] = "Registrasi berhasil! Selamat datang di Karirku.";
        header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        header('Location: ../views/register.php');
        exit;
    }
} elseif ($_POST['action'] == 'login') {
    $email = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email dan password harus diisi!";
        header('Location: ../views/login.php');
        exit;
    }

    error_log("Mencari user dengan email: " . $email);

    $result = supabaseQuery('pengguna', [
        'select' => '*',
        'email' => 'eq.' . $email
    ]);

    error_log("Query result: " . print_r($result, true));

    if ($result['success'] && count($result['data']) > 0) {
        $user = $result['data'][0];

        error_log("Password dari database: " . $user['password']);

        if ($password === $user['password']) {
            supabaseUpdate('pengguna', [
                'diperbarui_pada' => date('Y-m-d H:i:s')
            ], 'id_pengguna', $user['id_pengguna']);

            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $user['nama_lengkap'];
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'pencaker';

            // Notifikasi sukses login
            $_SESSION['success'] = "Login berhasil! Selamat datang kembali, " . $user['nama_lengkap'] . "!";

            // Cek role dan redirect sesuai
            if (($_SESSION['role'] === 'perusahaan')) {
                $_SESSION['error'] = "Akun anda adalah akun perusahaan";
                header('Refresh: 1; url=../views/company/login.php');
                exit;
            }

            header('Location: ' . dirname($_SERVER['PHP_SELF'], 2) . '/index.php');
            exit;
        } else {
            $_SESSION['error'] = "Password salah!";
            header('Location: ../views/login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
        header('Location: ../views/login.php');
        exit;
    }
} elseif ($_POST['action'] == 'forgot-password') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['error'] = "Email harus diisi!";
        header('Location: ../views/forgot-password.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid!";
        header('Location: ../views/forgot-password.php');
        exit;
    }

    // Cek apakah email ada di database
    $result = supabaseQuery('pengguna', [
        'select' => 'id_pengguna, email, nama_lengkap',
        'email' => 'eq.' . $email
    ]);

    error_log("Forgot password - Mencari email: " . $email);
    error_log("Forgot password - Query result: " . print_r($result, true));

    if ($result['success'] && count($result['data']) > 0) {
        $user = $result['data'][0];

        // Generate token sederhana
        $token = bin2hex(random_bytes(32));

        // Simpan token di session
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_id'] = $user['id_pengguna'];
        $_SESSION['reset_user_name'] = $user['nama_lengkap'];

        // Set expiry time (1 jam)
        $_SESSION['reset_expiry'] = time() + 3600;

        error_log("Token reset dibuat: " . $token . " untuk user: " . $user['nama_lengkap']);

        // Notifikasi sukses verifikasi email
        $_SESSION['success'] = "Email berhasil diverifikasi! Silakan buat password baru.";

        // Redirect ke reset password page
        header('Location: ../views/reset-password.php?token=' . $token);
        exit;
    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
        header('Location: ../views/forgot-password.php');
        exit;
    }
} elseif ($_POST['action'] == 'reset-password') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    error_log("Reset password - Token: " . $token);
    error_log("Reset password - Session token: " . ($_SESSION['reset_token'] ?? 'not set'));

    // Validasi token
    if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
        $_SESSION['error'] = "Token reset tidak valid!";
        header('Location: ../views/forgot-password.php');
        exit;
    }

    // Cek expiry time
    if (!isset($_SESSION['reset_expiry']) || time() > $_SESSION['reset_expiry']) {
        $_SESSION['error'] = "Token reset sudah kadaluarsa! Silakan request reset password lagi.";
        header('Location: ../views/forgot-password.php');
        exit;
    }

    // Validasi password
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "Password baru dan konfirmasi harus diisi!";
        header('Location: ../views/reset-password.php?token=' . $token);
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Password baru dan konfirmasi tidak cocok!";
        header('Location: ../views/reset-password.php?token=' . $token);
        exit;
    }

    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password harus minimal 6 karakter!";
        header('Location: ../views/reset-password.php?token=' . $token);
        exit;
    }

    // Update password di database
    $update = supabaseUpdate('pengguna', [
        'password' => $new_password,
        'diperbarui_pada' => date('Y-m-d H:i:s')
    ], 'id_pengguna', $_SESSION['reset_user_id']);

    error_log("Reset password - Update result: " . print_r($update, true));

    if ($update['success']) {
        // Log informasi untuk debugging
        error_log("Password berhasil direset untuk user ID: " . $_SESSION['reset_user_id']);

        // Simpan data user untuk notifikasi
        $reset_email = $_SESSION['reset_email'];
        $reset_user_name = $_SESSION['reset_user_name'];

        // Hapus session reset
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_user_name']);
        unset($_SESSION['reset_expiry']);

        // Notifikasi sukses reset password
        $_SESSION['success'] = "Password berhasil direset! Silakan login dengan password baru Anda.";

        error_log("Redirect ke login dengan pesan sukses");
        header('Location: ../views/login.php');
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat reset password. Silakan coba lagi.";
        header('Location: ../views/reset-password.php?token=' . $token);
        exit;
    }
}

$_SESSION['error'] = "Aksi tidak valid!";
header('Location: ../views/login.php');
exit;
