<?php
session_start();

// --- LOGIKA REDIRECT ADMIN ---
// Jika user sudah login (session aktif)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    
    // Cek Role (Opsional, jaga-jaga kalau akun perusahaan nyasar kesini)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'perusahaan') {
        $_SESSION['error'] = "Akun ini terdaftar sebagai Perusahaan.";
        header('Refresh: 1; url=../company/login.php');
        exit;
    } else {
        // --- PERUBAHAN DISINI ---
        // Karena login.php dan index.php ada di folder yang sama (views/dashboard/)
        // Kita cukup panggil index.php saja.
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Login Admin - KarirKu</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="../../assets/img/favicon.ico" rel="icon">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* --- GLOBAL STYLE --- */
        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #fcfcfc !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03) !important;
            padding: 15px 0;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-right: 40px;
        }
        
        .navbar-brand img {
            height: 35px;
        }

        .navbar-brand span {
            color: #002E92;
            font-weight: 800;
            font-size: 24px;
            font-style: italic;
        }

        /* Menu Navbar di Kiri */
        .nav-link {
            color: #002E92 !important;
            font-weight: 600 !important;
            font-size: 16px;
            padding: 0 !important;
            margin-right: 25px;
        }
        
        .nav-link:hover {
            color: #001f66 !important;
        }

        /* --- CONTAINER LOGIN --- */
        .login-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px;
        }

        /* GLOW BACKGROUND */
        .glow-background {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(160, 240, 180, 0.15) 0%, rgba(150, 190, 255, 0.15) 40%, rgba(255,255,255,0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(60px);
            pointer-events: none;
        }

        /* --- JUDUL HALAMAN (RATA KIRI & SEJAJAR KARTU) --- */
        .page-title {
            color: #002E92;
            font-weight: 800;
            font-size: 32px;
            margin-bottom: 20px;
            
            /* Logic Rata Kiri */
            text-align: left; 
            width: 100%;
            max-width: 420px; /* Samakan dengan lebar kartu */
            padding-left: 5px; /* Sedikit padding agar lurus optikal */
        }

        /* --- KARTU FORM --- */
        .login-card {
            background: white;
            border-radius: 25px;
            padding: 40px 40px 30px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            text-align: center;
        }

        .card-header-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 40px;
            color: #002E92;
            font-weight: 700;
            font-size: 20px;
        }

        /* INPUT STYLE */
        .form-group {
            margin-bottom: 35px;
            text-align: left;
            position: relative;
        }

        .input-line {
            width: 100%;
            border: none;
            border-bottom: 2px solid #E0E0E0;
            padding: 10px 0;
            font-size: 15px;
            font-weight: 600;
            color: #333;
            outline: none;
            background: transparent;
            transition: border-color 0.3s;
        }

        .input-line::placeholder {
            color: #A0A0A0;
            font-weight: 600;
        }

        .input-line:focus {
            border-bottom-color: #002E92;
        }

        /* --- TOMBOL GOOGLE --- */
        .btn-google-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 420px;
            height: 55px;
            background: white;
            border-radius: 50px;
            padding: 0 10px 0 25px;
            text-decoration: none !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #f1f1f1;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .btn-google-pill:hover {
            transform: translateY(-2px);
            background: #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .google-text {
            color: #999;
            font-weight: 600;
            font-size: 15px;
            flex-grow: 1;
            text-align: center;
            margin-left: -20px;
        }

        .google-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- TOMBOL LOGIN UTAMA --- */
        .btn-login-big {
            background-color: #002E92;
            color: white;
            width: 100%;
            max-width: 420px;
            padding: 15px;
            border-radius: 50px;
            border: none;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0, 46, 146, 0.25);
            transition: background 0.3s;
        }

        .btn-login-big:hover {
            background-color: #001f66;
        }

        .footer-text {
            color: #999;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
        }
        
        .footer-text a {
            color: #002E92;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container px-4">
            
            <a href="../../index.php" class="navbar-brand">
                <img src="../../assets/img/logo.png" alt="KarirKu Logo">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav me-auto mb-2 mb-lg-0">
                </div>
            </div>
        </div>
    </nav>
    <div class="login-wrapper">
        
        <div class="glow-background"></div>

        <h1 class="page-title">Login Admin</h1>

        <div class="login-card">
            
            <div class="card-header-logo">
                <img src="../../assets/img/karirkulogo.png" alt="Icon" style="width: 25px; height: auto;">
                <span>Login</span>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success py-2 mb-4" style="font-size: 13px;">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger py-2 mb-4" style="font-size: 13px;">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger py-2 mb-4" style="font-size: 13px;">
                    <?= htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" action="admin-auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <input type="text" name="username" class="input-line" placeholder="Nama Pengguna" required autocomplete="off">
                </div>

                <div class="form-group">
                    <input type="password" name="password" class="input-line" placeholder="Kata sandi" required>
                </div>
            </form>
        </div>

        <a href="../../function/google-auth.php" class="btn-google-pill">
            <span style="color: #002E92; font-weight: bold; font-size: 18px;"><i class="bi bi-chevron-right"></i></span>
            <span class="google-text">Pilih akun google</span>
            <div class="google-icon-wrapper">
                <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
            </div>
        </a>

        <div class="footer-text">
            Sudah punya akun? <a href="register.php">Daftar</a>
        </div>

        <button type="submit" form="loginForm" class="btn-login-big">
            Login
        </button>

    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alert
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>