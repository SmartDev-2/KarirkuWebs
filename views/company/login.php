<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login - Karirku</title>
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
    <link href="../../assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="../../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../../assets/css/auth.css" rel="stylesheet">
    <link href="../../assets/css/company/register.css" rel="stylesheet">
</head>

<body>
    <!-- Custom Navbar Start -->
    <nav class="custom-navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-logo">
                <img src="../../assets/img/karirkuperusahaan.png" alt="Karirku Logo">
            </a>
        </div>
    </nav>
    <!-- Custom Navbar End -->

    <!-- Login Container Start -->
    <div class="login-container">
        <div class="login-card1">
            <div class="card-header">
                <h1 class="card-title">Masuk ke Akun Perusahaan</h1>
                <img src="../../assets/img/karirkulogo.png" alt="Karirku Logo" class="card-logo">
            </div>

            <div class="login-body">
                <form action="auth-process.php" method="POST">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Email">
                    </div>

                    <div class="form-group">
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Kata sandi">
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn-daftar-primary">Login</button>
                </form>

                <!-- Register Link -->
                <p class="login-link">Belum punya akun? <a href="register.php">Daftar disini</a></p>

                <!-- Error Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="footer-links">
            <a href="job-seeker.php">Saya adalah Pencari kerja</a>
            <a href="https://wa.me/081187771001" target="_blank">Hubungi kami via WhatsApp</a>
        </div>

        <!-- Divider -->
        <hr class="footer-divider">

        <!-- Service Text -->
        <div class="footer-service">
            Layanan pengaduan konsumen
        </div>

        <!-- Additional Footer Information -->
        <div class="footer-additional">
            <div class="footer-left">
                <p>Kecamatan Sumbersari, Jember utara</p>
                <p>Alamat email: hai@karirku.com</p>
                <p>© 2025 Karirku | NTGL : 198771872883638003</p>
            </div>
            <div class="footer-right">
                <p>Direktorat Jenderal Perlindungan</p>
                <p>Konsumen dan Tertib Niaga</p>
                <p>Nomor Kontak WhatsApp: 0811-8877-1010</p>
            </div>
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
</body>

</html>