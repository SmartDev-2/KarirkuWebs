<?php
require __DIR__ . '/../function/job-functions.php';

session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';

$id_lowongan = isset($_GET['id']) ? trim($_GET['id']) : null;

if (!$id_lowongan) {
    header('Location: job-list.php');
    exit();
}

$result = getDetailLowongan($id_lowongan);

if (!$result['success'] || empty($result['data'])) {
    header('Location: job-list.php');
    exit();
}

$lowongan = $result['data'];

// Ambil data perusahaan berdasarkan id_perusahaan dari lowongan
require_once __DIR__ . '/../function/supabase.php';
$perusahaanResult = supabaseQuery('perusahaan', [
    'select' => 'nama_perusahaan, logo_url, deskripsi, website, lokasi, no_telp, email',
    'id_perusahaan' => 'eq.' . $lowongan['id_perusahaan']
]);

$perusahaan = [];
if ($perusahaanResult['success'] && !empty($perusahaanResult['data'])) {
    $perusahaan = $perusahaanResult['data'][0];
}

$data = formatLowongan($lowongan);

$kualifikasi_list = parseKualifikasi($data['kualifikasi']);
$benefit_list = parseBenefit($data['benefit']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>JobEntry - <?= htmlspecialchars($data['judul']) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

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
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Perbaikan alignment dropdown user */
        .auth-buttons {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            padding: 0;
            margin: 0;
        }

        .user-dropdown img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-dropdown .fa-user {
            font-size: 18px;
        }

        /* Pastikan dropdown menu juga sejajar */
        .dropdown-menu {
            left: auto !important;
            right: 0 !important;
            top: 100% !important;
            margin-top: 8px !important;
        }

        /* Perbaikan untuk ikon default */
        .rounded-circle.bg-light {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        /* Pastikan navbar items vertikal center */
        .navbar-nav {
            display: flex;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .user-dropdown:focus,
        .user-dropdown:active {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            object-position: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 2px;
            background: white;
        }

        .company-detail-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
        }

        .company-info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .company-info-item i {
            color: #001f66;
            width: 20px;
            margin-right: 10px;
            margin-top: 4px;
        }

        .company-info-content {
            flex: 1;
        }
    </style>
</head>

<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
            <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
                <a href="index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                    <img src="../assets/img/logo.png" alt="Karirku Logo">
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
                        <?php if ($isLoggedIn && isset($_SESSION['user_id'])): ?>
                            <?php
                            $pencaker = getPencakerByUserId($_SESSION['user_id']);
                            $fotoProfil = $pencaker['foto_profil_url'] ?? '';
                            ?>
                            <div class="dropdown">
                                <button class="btn user-dropdown dropdown-toggle text-white p-0 border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none !important; background-color: white !important;">
                                    <?php if (!empty($fotoProfil)): ?>
                                        <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-dark" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
                                    <li><a class="dropdown-item" href="my-applications.php"><i class="fas fa-briefcase me-2"></i>Lamaran Saya</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="views/logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="register.php" class="btn-register">Register</a>
                            <a href="login.php" class="btn-login">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Header End -->
        <div class="container-xxl py-5 bg-dark page-header mb-5">
            <div class="container my-5 pt-5 pb-4">
                <h1 class="display-3 text-white mb-3 animated slideInDown">Job Detail</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb text-uppercase">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="job-list.php">Job List</a></li>
                        <li class="breadcrumb-item text-white active" aria-current="page">Job Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Header End -->

        <!-- Job Detail Start -->
        <div class="container-xxl py-5 wow fadeInUp" data-wow-delay="0.1s">
            <div class="container">
                <div class="row gy-5 gx-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center mb-5">
                            <?php if (!empty($perusahaan['logo_url'])): ?>
                                <img class="flex-shrink-0 img-fluid border rounded company-logo" src="<?= htmlspecialchars($perusahaan['logo_url']) ?>" alt="<?= htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Company Logo') ?>">
                            <?php else: ?>
                                <img class="flex-shrink-0 img-fluid border rounded company-logo" src="../assets/img/com-logo-2.jpg" alt="Default Company Logo">
                            <?php endif; ?>
                            <div class="text-start ps-4">
                                <h3 class="mb-3"><?= htmlspecialchars($data['judul']) ?></h3>
                                <span class="text-truncate me-3"><i class="fa fa-map-marker-alt text-primary me-2"></i><?= htmlspecialchars($data['lokasi']) ?></span>
                                <span class="text-truncate me-3"><i class="far fa-clock text-primary me-2"></i><?= htmlspecialchars(formatTipePekerjaan($data['tipe_pekerjaan'])) ?></span>
                                <span class="text-truncate me-0"><i class="far fa-money-bill-alt text-primary me-2"></i><?= htmlspecialchars($data['gaji']) ?> juta</span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h4 class="mb-3">Job description</h4>
                            <p><?= htmlspecialchars($data['deskripsi']) ?></p>

                            <h4 class="mb-3">Kualifikasi</h4>
                            <?php if (!empty($kualifikasi_list)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($kualifikasi_list as $item): ?>
                                        <li><i class="fa fa-angle-right text-primary me-2"></i><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p><?= htmlspecialchars($data['kualifikasi']) ?></p>
                            <?php endif; ?>

                            <h4 class="mb-3">Benefit</h4>
                            <?php if (!empty($benefit_list)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($benefit_list as $item): ?>
                                        <li><i class="fa fa-angle-right text-primary me-2"></i><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p><?= htmlspecialchars($data['benefit']) ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Tambahkan di bagian setelah job summary, sebelum company detail -->
                        <div class="bg-light rounded p-5 mb-4 wow slideInUp" data-wow-delay="0.1s">
                            <h4 class="mb-4">Apply For This Job</h4>
                            <?php if ($isLoggedIn && $_SESSION['role'] === 'pencaker'): ?>
                                <?php
                                // Ambil data pencaker
                                $pencaker = getPencakerByUserId($_SESSION['user_id']);

                                // Cek apakah sudah apply
                                $alreadyApplied = false;
                                if ($pencaker) {
                                    $checkApply = supabaseQuery('lamaran', [
                                        'select' => 'id_lamaran',
                                        'id_lowongan' => 'eq.' . $id_lowongan,
                                        'id_pencaker' => 'eq.' . $pencaker['id_pencaker']
                                    ]);
                                    $alreadyApplied = $checkApply['success'] && count($checkApply['data']) > 0;

                                    // CEK CV DARI TABLE CV (bukan dari pencaker)
                                    $checkCV = supabaseQuery('cv', [
                                        'select' => 'id_cv, cv_url',
                                        'id_pencaker' => 'eq.' . $pencaker['id_pencaker'],
                                        'order' => 'uploaded_at.desc',
                                        'limit' => 1
                                    ]);
                                    $hasCV = $checkCV['success'] && count($checkCV['data']) > 0;
                                }
                                ?>

                                <?php if ($alreadyApplied): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Anda sudah mengirim lamaran untuk lowongan ini.
                                    </div>
                                    <a href="profile.php" class="btn btn-primary w-100">Lihat Lamaran Saya</a>
                                <?php else: ?>
                                    <?php if ($hasCV): ?>
                                        <!-- Jika memiliki CV, tampilkan tombol Apply Now -->
                                        <a href="apply-job.php?id=<?= $id_lowongan ?>" class="btn btn-primary w-100 btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Apply Now
                                        </a>
                                    <?php else: ?>
                                        <!-- Jika tidak memiliki CV, tampilkan tombol Lengkapi Identitas -->
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Anda belum memiliki CV. Silakan lengkapi identitas terlebih dahulu.
                                        </div>
                                        <a href="profile.php" class="btn btn-warning w-100 btn-lg">
                                            <i class="fas fa-user-edit me-2"></i>Lengkapi Identitas
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Silakan login sebagai pencaker untuk mengirim lamaran.
                                </div>
                                <a href="login.php" class="btn btn-outline-primary w-100">Login</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="bg-light rounded p-5 mb-4 wow slideInUp" data-wow-delay="0.1s">
                            <h4 class="mb-4">Job Summery</h4>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Published On: <?= htmlspecialchars($data['dibuat_pada']) ?></p>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Kategori: <?= htmlspecialchars($data['kategori']) ?></p>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Job Nature: <?= htmlspecialchars(formatTipePekerjaan($data['tipe_pekerjaan'])) ?></p>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Salary: <?= htmlspecialchars($data['gaji']) ?> juta</p>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Location: <?= htmlspecialchars($data['lokasi']) ?></p>
                            <p><i class="fa fa-angle-right text-primary me-2"></i>Mode Kerja: <?= htmlspecialchars($data['mode_kerja']) ?></p>
                            <p class="m-0"><i class="fa fa-angle-right text-primary me-2"></i>Date Line: <?= htmlspecialchars($data['batas_tanggal']) ?></p>
                        </div>

                        <!-- Company Detail Section -->
                        <div class="company-detail-section wow slideInUp" data-wow-delay="0.1s">
                            <h4 class="mb-4">Company Detail</h4>

                            <?php if (!empty($perusahaan)): ?>
                                <!-- Nama Perusahaan -->
                                <div class="company-info-item">
                                    <i class="fas fa-building"></i>
                                    <div class="company-info-content">
                                        <strong>Nama Perusahaan:</strong><br>
                                        <?= htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Tidak tersedia') ?>
                                    </div>
                                </div>

                                <!-- Deskripsi Perusahaan -->
                                <?php if (!empty($perusahaan['deskripsi'])): ?>
                                    <div class="company-info-item">
                                        <i class="fas fa-info-circle"></i>
                                        <div class="company-info-content">
                                            <strong>Deskripsi:</strong><br>
                                            <?= htmlspecialchars($perusahaan['deskripsi']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Lokasi Perusahaan -->
                                <?php if (!empty($perusahaan['lokasi'])): ?>
                                    <div class="company-info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div class="company-info-content">
                                            <strong>Lokasi:</strong><br>
                                            <?= htmlspecialchars($perusahaan['lokasi']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Website -->
                                <?php if (!empty($perusahaan['website'])): ?>
                                    <div class="company-info-item">
                                        <i class="fas fa-globe"></i>
                                        <div class="company-info-content">
                                            <strong>Website:</strong><br>
                                            <a href="<?= htmlspecialchars($perusahaan['website']) ?>" target="_blank" class="text-primary">
                                                <?= htmlspecialchars($perusahaan['website']) ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Kontak -->
                                <?php if (!empty($perusahaan['no_telp']) || !empty($perusahaan['email'])): ?>
                                    <div class="company-info-item">
                                        <i class="fas fa-phone"></i>
                                        <div class="company-info-content">
                                            <strong>Kontak:</strong><br>
                                            <?php if (!empty($perusahaan['no_telp'])): ?>
                                                Telp: <?= htmlspecialchars($perusahaan['no_telp']) ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($perusahaan['email'])): ?>
                                                Email: <?= htmlspecialchars($perusahaan['email']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-building fa-2x mb-3"></i>
                                    <p>Informasi perusahaan tidak tersedia</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Job Detail End -->

        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-4">Company</h5>
                        <a class="btn btn-link text-white-50" href="">About Us</a>
                        <a class="btn btn-link text-white-50" href="">Contact Us</a>
                        <a class="btn btn-link text-white-50" href="">Our Services</a>
                        <a class="btn btn-link text-white-50" href="">Privacy Policy</a>
                        <a class="btn btn-link text-white-50" href="">Terms & Condition</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-4">Quick Links</h5>
                        <a class="btn btn-link text-white-50" href="">About Us</a>
                        <a class="btn btn-link text-white-50" href="">Contact Us</a>
                        <a class="btn btn-link text-white-50" href="">Our Services</a>
                        <a class="btn btn-link text-white-50" href="">Privacy Policy</a>
                        <a class="btn btn-link text-white-50" href="">Terms & Condition</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-4">Contact</h5>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@example.com</p>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h5 class="text-white mb-4">Newsletter</h5>
                        <p>Dolor amet sit justo amet elitr clita ipsum elitr est.</p>
                        <div class="position-relative mx-auto" style="max-width: 400px;">
                            <input class="form-control bg-transparent w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                            <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="#">Your Site Name</a>, All Right Reserved.

                            <!--/*** This template is free as long as you keep the footer author's credit link/attribution link/backlink. If you'd like to use the template without the footer author's credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                            Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="">Home</a>
                                <a href="">Cookies</a>
                                <a href="">Help</a>
                                <a href="">FQAs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/lib/wow/wow.min.js"></script>
    <script src="../assets/lib/easing/easing.min.js"></script>
    <script src="../assets/lib/waypoints/waypoints.min.js"></script>
    <script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="../assets/js/main.js"></script>
</body>

</html>