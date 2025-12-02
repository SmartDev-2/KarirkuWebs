<?php
session_start();

// Jika user sudah login dan role-nya perusahaan, redirect ke login perusahaan
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'perusahaan') {
        $_SESSION['error'] = "Akun anda adalah akun perusahaan";
        header('Refresh: 1; url=../company/login.php');
        exit;
    } else {
        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register - Karirku</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="../assets/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../assets/css/auth.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <!-- Login Page Styles -->
    <style>
        .login-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: url("../assets/img/background-login.png") center center no-repeat;
            background-size: cover;
            text-align: center;
            padding: 40px 20px;
        }

        .intro-text h1 {
            color: #002E92;
            font-size: 2rem;
            font-weight: 600;
            line-height: 1.4;
            max-width: 700px;
        }

        .login-card1 {
            background: white;
            border-radius: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            margin-bottom: 20px;
            margin-top: -15px;
        }

        .login-card2 {
            background: white;
            border-radius: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .btn-text {
            margin-left: 20px;
            color: #8D92A0;
            font-weight: 600;
            line-height: 1.4;
            font-size: 16px;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 2px 6px;
            border: 1px solid #ddd;
            border-radius: 32px;
            background: white;
            font-weight: 500;
            color: #333;
            transition: all 0.3s;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-google:hover {
            background: #f5f5f5;
        }

        .btn-google img {
            width: 55px;
            height: 55px;
            margin: 0;
        }

        .login-title {
            color: #002E92;
        }

        .btn-login-primary {
            background-color: #001f66;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 32px;
            font-weight: 500;
            width: 141px;
            transition: background-color 0.3s;
        }

        .login-card2 .btn-google {
            margin-bottom: 0;
            border-radius: 32px;
        }

        .login-card2 {
            padding: 0;
        }

        .span-text h1 {
            color: #8D92A0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
            margin-top: 12px;
            font-size: 15px;
        }

        .btn-daftar-primary {
            display: inline-block;
            text-align: center;
            text-decoration: none;
            background-color: #001f66;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 32px;
            font-weight: 500;
            width: 350px;
            transition: background-color 0.3s;
            margin-top: 20px;
            cursor: pointer;
        }

        .btn-daftar-primary:hover {
            background-color: #002c99;
            color: white;
            text-decoration: none;
        }

        .form-control {
            width: 100%;
            padding: 10px 5px;
            border: none;
            border-bottom: 1px solid #ccc;
            border-radius: 0;
            background: transparent;
            font-size: 14px;
            color: #333;
            transition: border-color 0.3s;
        }

        .form-control input {
            font-size: 20px;
        }

        .form-control:focus {
            outline: none;
            border-bottom: 1px solid #001f66;
            box-shadow: none;
        }
    </style>
</head>

<body>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
            <a href="index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                <img src="../assets/img/logo.png" alt="">
            </a>

            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                <div class="navbar-nav ms-0 mt-1">
                    <a href="../index.php" class="nav-item nav-link active">Home</a>
                    <a href="job-list.php" class="nav-item nav-link">Cari Pekerjaan</a>
                </div>
                <div class="auth-buttons d-flex align-items-center">
                    <a href="register.php" class="btn-register">Register</a>
                    <a href="login.php" class="btn-login">Login</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Login Container Start -->
    <div class="login-container">
        <div class="intro-text">
            <h1>Gabung di Karirku</h1>
        </div>
        <div class="login-card1">
            <div class="login-body">
                <h4 class="login-title"><img src="../assets/img/karirkulogo.png" alt="" style="width: 40px;"> Register</h4>
                <!-- Ganti action form dan tambahkan input hidden -->
                <form action="../function/auth-process.php" method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <input type="text" id="username" name="username" class="form-control" required placeholder="Nama Pengguna">
                    </div>

                    <div class="form-group">
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Email">
                    </div>

                    <div class="form-group">
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Kata sandi">
                    </div>
                </form>
                <!-- Tambahkan pesan error jika user perusahaan mencoba register di sini -->
                <?php if (isset($_SESSION['error_role'])): ?>
                    <div class="alert alert-warning mt-3">
                        <?php
                        echo $_SESSION['error_role'];
                        unset($_SESSION['error_role']);
                        ?>
                    </div>
                <?php endif; ?>
                <!-- Tambahkan pesan error jika ada -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mt-3">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger mt-3">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- ✅ BENAR (harus seperti ini) -->
        <div class="login-card2">
            <a href="../function/google-auth.php" class="btn-google" id="google-auth-btn">
                <span class="btn-text">Pilih akun google</span>
                <span class="btn-icon">
                    <img src="../assets/img/icon-google2.png" alt="">
                </span>
            </a>
        </div>
        <!-- Tombol submit untuk form register -->
        <button type="submit" form="registerForm" class="btn-daftar-primary" style="display: inline-block; text-align: center; text-decoration: none;">
            Daftar
        </button>
        <div class="span-text">
            <h1>Sudah punya akun? <a href="login.php">Login</a></h1>
        </div>
    </div>
    <!-- Login Container End -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/lib/wow/wow.min.js"></script>
    <script src="../assets/lib/easing/easing.min.js"></script>
    <script src="../assets/lib/waypoints/waypoints.min.js"></script>

    <!-- Template Javascript -->
    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('google-auth-btn').addEventListener('click', function(e) {
            // Tambahkan loading state
            this.querySelector('.btn-text').textContent = 'Loading...';
            this.style.opacity = '0.7';
        });
    </script>
</body>

</html>