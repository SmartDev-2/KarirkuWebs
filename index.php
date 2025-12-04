<?php
session_start();
require_once 'function/supabase.php';
require_once 'function/job-functions.php'; // Tambahkan ini

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user'] ?? '';

// Ambil jumlah lowongan per kategori
$kategoriCounts = [];
$allJobs = searchLowongan('', '', 1, 1000); // Ambil semua lowongan untuk menghitung per kategori
if ($allJobs['success']) {
    foreach ($allJobs['data'] as $job) {
        $kategori = $job['kategori'] ?? '';
        if ($kategori) {
            if (!isset($kategoriCounts[$kategori])) {
                $kategoriCounts[$kategori] = 0;
            }
            $kategoriCounts[$kategori]++;
        }
    }
}

// Ambil 3 lowongan terbaru untuk Job Listing
$recentJobs = searchLowongan('', '', 1, 3);
$recentJobsData = $recentJobs['data'] ?? [];

// Fungsi untuk mendapatkan jumlah berdasarkan kategori
function getKategoriCount($kategoriName, $kategoriCounts)
{
    $kategoriMapping = [
        'marketing' => 'Marketing',
        'customer-service' => 'Customer Service',
        'human-resource' => 'Human Resource',
        'project-management' => 'Project Management',
        'business-development' => 'Business Development',
        'sales' => 'Sales',
        'teaching' => 'Teaching',
        'design' => 'Design'
    ];

    $dbKategori = $kategoriMapping[strtolower($kategoriName)] ?? $kategoriName;
    return $kategoriCounts[$dbKategori] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Karirku - Job Portal Website</title> <!-- Changed title -->
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="assets/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- Custom Styles for Dropdown -->
    <style>
        .dropdown-toggle::after {
            display: none;
        }

        .user-dropdown {
            background-color: #001f66 !important;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }

        .user-dropdown:hover {
            background-color: #002c99 !important;
        }

        .auth-buttons .btn-register {
            background-color: transparent;
            color: #001f66;
            border: 2px solid #001f66;
            border-radius: 8px;
            padding: 8px 20px;
            margin-right: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .auth-buttons .btn-register:hover {
            background-color: #001f66;
            color: white;
        }

        .auth-buttons .btn-login {
            background-color: #001f66;
            color: white;
            border: 2px solid #001f66;
            border-radius: 8px;
            padding: 8px 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .auth-buttons .btn-login:hover {
            background-color: #002c99;
            border-color: #002c99;
        }

        /* .dropdown {
            margin-right: 1.5rem !important;
        } */

        .dropdown-menu {
            left: auto !important;
            right: 0 !important;
        }

        .user-dropdown:focus,
        .user-dropdown:active {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        /* Styling untuk ikon notifikasi */
        #notificationDropdown {
            color: #001f66 !important;
            font-size: 1.5rem;
            transition: color 0.3s;
            padding: 8px;
            border-radius: 50%;
        }

        #notificationDropdown:hover {
            color: #002c99 !important;
            background-color: rgba(0, 31, 102, 0.1);
        }

        /* Badge notifikasi */
        #notificationDropdown .badge {
            font-size: 0.6rem;
            padding: 0.25em 0.45em;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Hover effect pada ikon bell */
        #notificationDropdown i {
            transition: transform 0.2s;
        }

        #notificationDropdown:hover i {
            transform: scale(1.1);
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
                    <img src="assets/img/logo.png" alt="Karirku Logo">
                </a>

                <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav ms-0 mt-1">
                        <a href="index.php" class="nav-item nav-link active">Beranda</a>
                        <a href="views/job-list.php" class="nav-item nav-link">Cari Pekerjaan</a>
                    </div>

                    <div class="auth-buttons d-flex align-items-center">
                        <?php if ($isLoggedIn && isset($_SESSION['user_id'])): ?>
                            <div class="dropdown me-3">
                                <!-- Icon notification tanpa tombol -->
                                <a href="#" class="position-relative text-decoration-none" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #001f66; font-size: 1.2rem; display: inline-block;">
                                    <i class="fa-regular fa-bell"></i>
                                    <?php
                                    $unseenCount = 0;
                                    if ($isLoggedIn && isset($_SESSION['user_id'])) {
                                        $unseenCount = countUnseenNotifications($_SESSION['user_id']);
                                    }
                                    ?>
                                    <?php if ($unseenCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.25em 0.45em;">
                                            <?= $unseenCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 300px;">
                                    <li class="dropdown-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Notifikasi</strong>
                                            <?php if ($unseenCount > 0): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                                                    Tandai sudah dibaca
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <?php if ($isLoggedIn && isset($_SESSION['user_id'])): ?>
                                        <?php
                                        $notifications = getNotifikasiLowonganBaru($_SESSION['user_id'], 5);
                                        ?>
                                        <?php if ($notifications['success'] && count($notifications['data']) > 0): ?>
                                            <?php foreach ($notifications['data'] as $notif): ?>
                                                <li>
                                                    <a class="dropdown-item" href="views/job-detail.php?id=<?= $notif['id_lowongan'] ?>">
                                                        <div class="d-flex align-items-start" style="padding: 20px 10px;">
                                                            <?php if (!empty($notif['perusahaan']['logo_url'])): ?>
                                                            <?php else: ?>
                                                                <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                                    <i class="fas fa-briefcase text-primary"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold"><?= htmlspecialchars($notif['judul']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($notif['perusahaan']['nama_perusahaan'] ?? 'Perusahaan') ?></small>
                                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                                    <?= date('d M Y', strtotime($notif['dibuat_pada'])) ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                            <?php endforeach; ?>
                                            <li>
                                                <a class="dropdown-item text-center text-primary" href="views/job-list.php">
                                                    Lihat Semua Lowongan
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <div class="dropdown-item text-center text-muted">
                                                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                                    <div>Tidak ada notifikasi baru</div>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php
                            require_once 'function/supabase.php';
                            $pencaker = getPencakerByUserId($_SESSION['user_id']);
                            $fotoProfil = $pencaker['foto_profil_url'] ?? '';
                            ?>
                            <div class="dropdown">
                                <button class="btn user-dropdown dropdown-toggle text-white p-0 border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none !important; background-color: white !important;" onclick="window.location.href='views/profile.php'">>
                                    <?php if (!empty($fotoProfil)): ?>
                                        <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-dark" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <a href="views/register.php" class="btn-register">Daftar</a>
                            <a href="views/login.php" class="btn-login">Masuk</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->
        <!-- Carousel Start -->
        <div class="container-fluid p-0">
            <div class="owl-carousel header-carousel position-relative">
                <div class="owl-carousel-item position-relative">
                    <img class="img-fluid" src="assets/img/background.png" alt="">
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" style="background: rgba(43, 57, 64, .5);">
                        <div class="container">
                            <div class="row justify-content-start">
                                <div class="col-10 col-lg-8">
                                    <h1 class="display-3 text-white animated slideInDown mb-4">Carilah Masa Depanmu Bersama Kami</h1>
                                    <p class="fs-5 fw-medium text-white mb-4 pb-2">Bangun karier impianmu bersama kami. Temukan peluang, jaringan, dan inspirasi untuk menapaki masa depan yang lebih gemilang</p>
                                    <?php if (!$isLoggedIn): ?>
                                        <a href="views/register.php" class="btn btn-primary py-3 px-5" style="border-radius: 20px;">Daftar Sekarang</a>
                                    <?php else: ?>
                                        <a href="views/job-list.php" class="btn btn-primary py-3 px-5">Cari Lowongan</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Carousel End -->

        <!-- Category Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <h1 class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">Cari Berdasarkan Kategori</h1>
                <div class="row g-4">
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=marketing">
                            <i class="fa fa-3x fa-mail-bulk text-primary mb-4"></i>
                            <h6 class="mb-3">Marketing</h6>
                            <p class="mb-0"><?= getKategoriCount('marketing', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.3s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=customer-service">
                            <i class="fa fa-3x fa-headset text-primary mb-4"></i>
                            <h6 class="mb-3">Customer Service</h6>
                            <p class="mb-0"><?= getKategoriCount('customer-service', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.5s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=human-resource">
                            <i class="fa fa-3x fa-user-tie text-primary mb-4"></i>
                            <h6 class="mb-3">Human Resource</h6>
                            <p class="mb-0"><?= getKategoriCount('human-resource', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.7s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=project-management">
                            <i class="fa fa-3x fa-tasks text-primary mb-4"></i>
                            <h6 class="mb-3">Project Management</h6>
                            <p class="mb-0"><?= getKategoriCount('project-management', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=business-development">
                            <i class="fa fa-3x fa-chart-line text-primary mb-4"></i>
                            <h6 class="mb-3">Business Development</h6>
                            <p class="mb-0"><?= getKategoriCount('business-development', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.3s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=sales">
                            <i class="fa fa-3x fa-hands-helping text-primary mb-4"></i>
                            <h6 class="mb-3">Sales & Communication</h6>
                            <p class="mb-0"><?= getKategoriCount('sales', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.5s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=teaching">
                            <i class="fa fa-3x fa-book-reader text-primary mb-4"></i>
                            <h6 class="mb-3">Teaching & Education</h6>
                            <p class="mb-0"><?= getKategoriCount('teaching', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.7s">
                        <a class="cat-item rounded p-4" href="views/job-list.php?category=design">
                            <i class="fa fa-3x fa-drafting-compass text-primary mb-4"></i>
                            <h6 class="mb-3">Design & Creative</h6>
                            <p class="mb-0"><?= getKategoriCount('design', $kategoriCounts) ?> permintaan</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Category End -->

        <!-- About Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <div class="row g-0 about-bg rounded overflow-hidden">
                            <div class="col-6 text-start">
                                <img class="img-fluid w-100" src="assets/img/about-1.jpg">
                            </div>
                            <div class="col-6 text-start">
                                <img class="img-fluid" src="assets/img/about-2.jpg" style="width: 85%; margin-top: 15%;">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid" src="assets/img/about-3.jpg" style="width: 85%;">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid w-100" src="assets/img/about-4.jpg">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <h1 class="mb-4">Temukan Jalan Menuju Kesempatan Terbaikmu</h1>
                        <p class="mb-4">Dunia kerja berubah cepat — dan kami ada untuk memastikan Anda selalu selangkah di depan. Entah mencari karier baru atau merekrut bintang masa depan, kami hadir sebagai jembatan antara impian dan pencapaian.</p>
                        <p><i class="fa fa-check text-primary me-3"></i>Akses langsung ke perusahaan ternama</p>
                        <p><i class="fa fa-check text-primary me-3"></i>Sistem pencocokan talenta yang cerdas</p>
                        <p><i class="fa fa-check text-primary me-3"></i>Dukungan penuh untuk pertumbuhan berkelanjutan</p>
                        <p>Daftarkan akun anda sekarang juga</p>
                        <?php if (!$isLoggedIn): ?>
                            <a class="btn btn-primary py-3 px-5 mt-3" href="register.php" style="border-radius: 18px;">Daftar</a>
                        <?php else: ?>
                            <a class="btn btn-primary py-3 px-5 mt-3" href="views/job-list.php">Cari Lowongan</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->

        <!-- Jobs Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <h1 class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">Daftar Pekerjaan</h1>
                <div class="tab-class text-center wow fadeInUp" data-wow-delay="0.3s">
                    <ul class="nav nav-pills d-inline-flex justify-content-center border-bottom mb-5">
                        <li class="nav-item">
                            <a class="d-flex align-items-center text-start mx-3 ms-0 pb-3 active" data-bs-toggle="pill" href="#tab-1">
                                <h6 class="mt-n1 mb-0">Fitur</h6>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div id="tab-1" class="tab-pane fade show p-0 active">
                            <?php if (!empty($recentJobsData)): ?>
                                <?php foreach ($recentJobsData as $row): ?>
                                    <?php
                                    $logoUrl = getCompanyLogoUrl($row);
                                    $companyName = getCompanyName($row);
                                    ?>
                                    <div class="job-item p-4 mb-4 border rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-sm-12 col-md-8 d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid border rounded" src="<?= htmlspecialchars($logoUrl) ?>" alt="" style="width: 80px; height: 80px;">
                                                <div class="text-start ps-4">
                                                    <h5 class="mb-3"><?= htmlspecialchars($row['judul'] ?? 'Judul tidak tersedia') ?></h5>
                                                    <span class="text-truncate me-3"><i class="fa fa-map-marker-alt text-primary me-2"></i><?= htmlspecialchars($row['lokasi'] ?? 'Lokasi tidak tersedia') ?></span>
                                                    <span class="text-truncate me-3"><i class="far fa-clock text-primary me-2"></i><?= htmlspecialchars(formatTipePekerjaan($row['tipe_pekerjaan'] ?? '')) ?></span>
                                                    <span class="text-truncate me-0"><i class="far fa-money-bill-alt text-primary me-2"></i><?= htmlspecialchars($row['gaji_range'] ?? 'Gaji tidak tersedia') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 col-md-4 d-flex flex-column align-items-start align-items-md-end justify-content-center">
                                                <div class="d-flex mb-3">
                                                    <?php if ($isLoggedIn): ?>
                                                        <a class="btn btn-primary" href="views/job-detail.php?id=<?= htmlspecialchars($row['id_lowongan'] ?? '') ?>" style="border-radius: 20px;">Apply Sekarang</a>
                                                    <?php else: ?>
                                                        <a class="btn btn-primary" href="views/login.php" style="border-radius: 20px;">Login untuk Apply</a>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-truncate"><i class="far fa-calendar-alt text-primary me-2"></i>Date Line: <?= !empty($row['batas_tanggal']) ? date('d M Y', strtotime($row['batas_tanggal'])) : 'Tidak ditentukan' ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="job-item p-4 mb-4">
                                    <div class="text-center py-5">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Belum ada lowongan yang tersedia saat ini</h5>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <a class="btn btn-primary py-3 px-5" href="views/job-list.php" style="background-color: #001f66; border-radius: 18px;">Lainnya</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Jobs End -->

        <?php include "views/include/footer.php" ?>

        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/lib/wow/wow.min.js"></script>
    <script src="assets/lib/easing/easing.min.js"></script>
    <script src="assets/lib/waypoints/waypoints.min.js"></script>
    <script src="assets/lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="assets/js/main.js"></script>

    <!-- Logout Confirmation Script -->
    <script>
        // Fungsi untuk menandai semua notifikasi sebagai sudah dibaca
        function markAllAsRead() {
            fetch('function/mark-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload halaman untuk update counter
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Auto-close dropdown ketika klik di luar
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#notificationDropdown')) {
                const dropdown = document.querySelector('.dropdown-menu');
                if (dropdown && dropdown.classList.contains('show')) {
                    // Optional: bisa tambahkan logika untuk update last check time
                }
            }
        });
    </script>
</body>

</html>