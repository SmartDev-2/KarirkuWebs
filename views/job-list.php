<?php
require __DIR__ . '/../function/job-functions.php';

session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';

// Ambil data pencaker jika user login
$pencaker = null;
$id_pencaker = null;
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../function/supabase.php';
    $pencaker = getPencakerByUserId($_SESSION['user_id']);
    if ($pencaker) {
        $id_pencaker = $pencaker['id_pencaker'];
    }
}

// Ambil daftar favorit pencaker jika login
$favorit_lowongan = [];
if ($id_pencaker) {
    $favorit_result = supabaseQuery('favorit_lowongan', [
        'select' => 'id_lowongan',
        'id_pencaker' => 'eq.' . $id_pencaker
    ]);

    if ($favorit_result['success']) {
        foreach ($favorit_result['data'] as $fav) {
            $favorit_lowongan[] = $fav['id_lowongan'];
        }
    }
}

$daftarLokasi = getDaftarLokasi();

$searchInput = validateSearchInput($_GET);
$keyword = $searchInput['keyword'];
$lokasi = $searchInput['lokasi'];
$page = $searchInput['page'];
$limit = 5;

$result = searchLowongan($keyword, $lokasi, $page, $limit);

$data = $result['data'] ?? [];
$totalPages = $result['pagination']['total_pages'] ?? 1;
$currentPage = $result['pagination']['current_page'] ?? 1;
$totalData = $result['pagination']['total_data'] ?? 0;
$searchKeyword = $result['search_params']['keyword'] ?? '';
$searchLokasi = $result['search_params']['lokasi'] ?? '';

// Handle save/unsave action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$isLoggedIn || !$id_pencaker) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    $id_lowongan = $_POST['id_lowongan'] ?? 0;
    $action = $_POST['action'];

    if ($action === 'save') {
        // Cek apakah sudah ada
        $check_result = supabaseQuery('favorit_lowongan', [
            'select' => 'id_favorit',
            'id_pencaker' => 'eq.' . $id_pencaker,
            'id_lowongan' => 'eq.' . $id_lowongan
        ]);

        if ($check_result['success'] && empty($check_result['data'])) {
            $insert_result = supabaseInsert('favorit_lowongan', [
                'id_pencaker' => $id_pencaker,
                'id_lowongan' => $id_lowongan,
                'dibuat_pada' => date('Y-m-d H:i:s')
            ]);

            if ($insert_result['success']) {
                $favorit_lowongan[] = $id_lowongan;
            }
        }
    } elseif ($action === 'unsave') {
        // Cari id_favorit
        $find_result = supabaseQuery('favorit_lowongan', [
            'select' => 'id_favorit',
            'id_pencaker' => 'eq.' . $id_pencaker,
            'id_lowongan' => 'eq.' . $id_lowongan
        ]);

        if ($find_result['success'] && !empty($find_result['data'])) {
            $id_favorit = $find_result['data'][0]['id_favorit'];
            $delete_result = supabaseDelete('favorit_lowongan', 'id_favorit', $id_favorit);

            if ($delete_result['success']) {
                $key = array_search($id_lowongan, $favorit_lowongan);
                if ($key !== false) {
                    unset($favorit_lowongan[$key]);
                }
            }
        }
    }

    // Refresh halaman
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

if (isset($_GET['debug'])) {
    echo "<!-- Debug: Success=" . ($result['success'] ? 'true' : 'false') .
        ", Data Count=" . count($data) .
        ", Lokasi Count=" . count($daftarLokasi) .
        ", Error=" . ($result['error'] ?? 'None') . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>JobEntry - Job Portal Website Template</title>
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
        body {
            background-color: white !important;
        }

        .search-container {
            background-color: #ffffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-input i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .search-input input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .location-select {
            min-width: 180px;
        }

        .location-select select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }

        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }

        .search-button {
            background-color: #001f66;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #002c77;
        }

        .search-results-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #001f66;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input,
            .location-select {
                min-width: 100%;
            }
        }

        .dropdown-menu {
            left: auto !important;
            right: 0 !important;
        }

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

        .company-logo-list {
            width: 80px;
            height: 80px;
            object-fit: cover;
            object-position: center;
            border-radius: 8px;
        }

        /* Save Button Styles */
        .save-button-container {
            position: absolute;
            top: 33px;
            right: 175px;
            z-index: 10;
        }

        .save-form {
            margin: 0;
            padding: 0;
        }

        .btn-save {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 18px;
            text-decoration: none;
        }

        .btn-save:hover {
            background: #003399;
            border-color: #003399;
            color: white;
            transform: scale(1.1);
        }

        .btn-save.saved {
            background: #003399;
            border-color: #003399;
            color: white;
        }

        .btn-save.saved:hover {
            background: #dc3545;
            border-color: #dc3545;
        }

        /* Job item positioning */
        .job-item {
            position: relative;
            padding-top: 15px !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .save-button-container {
                top: 15px;
                right: 15px;
            }

            .btn-save {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

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
                                <li><a class="dropdown-item text-danger" href="logout.php">
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
            <h1 class="display-3 text-white mb-3 animated slideInDown">Job List</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb text-uppercase">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Job List</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Header End -->
    <!-- Jobs Start -->
    <div class="container py-5">
        <!-- Search Section -->
        <h2 class="text-center mb-4 fw-bold">Daftar Lowongan</h2>

        <!-- Form Pencarian -->
        <div class="search-container">
            <form class="search-form" method="GET" action="">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="keyword" placeholder="Cari judul pekerjaan, perusahaan, kata kunci"
                        value="<?= htmlspecialchars($searchKeyword) ?>">
                </div>

                <div class="location-select">
                    <select name="lokasi">
                        <option value="" <?= empty($searchLokasi) ? 'selected' : '' ?>>Semua Lokasi</option>
                        <?php if (!empty($daftarLokasi)): ?>
                            <?php foreach ($daftarLokasi as $lok): ?>
                                <option value="<?= htmlspecialchars(strtolower($lok)) ?>"
                                    <?= $searchLokasi == strtolower($lok) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lok) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="jakarta" <?= $searchLokasi == 'jakarta' ? 'selected' : '' ?>>Jakarta</option>
                            <option value="surabaya" <?= $searchLokasi == 'surabaya' ? 'selected' : '' ?>>Surabaya</option>
                            <option value="bandung" <?= $searchLokasi == 'bandung' ? 'selected' : '' ?>>Bandung</option>
                            <option value="malang" <?= $searchLokasi == 'malang' ? 'selected' : '' ?>>Malang</option>
                            <option value="jember" <?= $searchLokasi == 'jember' ? 'selected' : '' ?>>Jember</option>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="search-button">
                    <i class="fas fa-search me-2"></i>Cari
                </button>
            </form>
        </div>

        <?php if (!empty($searchKeyword) || (!empty($searchLokasi) && $searchLokasi !== 'semua')): ?>
            <div class="search-results-info">
                <h6 class="mb-2">Hasil Pencarian:</h6>
                <?php if (!empty($searchKeyword)): ?>
                    <span class="badge bg-primary me-2">Kata kunci: "<?= htmlspecialchars($searchKeyword) ?>"</span>
                <?php endif; ?>
                <?php if (!empty($searchLokasi) && $searchLokasi !== 'semua'): ?>
                    <span class="badge bg-secondary">Lokasi: <?= htmlspecialchars(ucfirst($searchLokasi)) ?></span>
                <?php endif; ?>
                <span class="badge bg-info ms-2">Total: <?= $totalData ?> lowongan</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($data)) : ?>
            <?php foreach ($data as $row): ?>
                <?php
                $logoUrl = getCompanyLogoUrl($row);
                $companyName = getCompanyName($row);
                $isFavorited = in_array($row['id_lowongan'], $favorit_lowongan);
                ?>
                <div class="job-item p-4 mb-4 border rounded shadow-sm position-relative">
                    <!-- Save Button -->
                    <div class="save-button-container">
                        <?php if ($isLoggedIn): ?>
                            <form method="POST" class="save-form">
                                <input type="hidden" name="id_lowongan" value="<?= htmlspecialchars($row['id_lowongan']) ?>">
                                <input type="hidden" name="action" value="<?= $isFavorited ? 'unsave' : 'save' ?>">
                                <button type="submit" class="btn-save <?= $isFavorited ? 'saved' : '' ?>"
                                    title="<?= $isFavorited ? 'Hapus dari favorit' : 'Simpan ke favorit' ?>">
                                    <i class="<?= $isFavorited ? 'fas' : 'far' ?> fa-bookmark"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                class="btn-save" title="Login untuk menyimpan">
                                <i class="far fa-bookmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <img src="<?= htmlspecialchars($logoUrl) ?>"
                                alt="Logo <?= htmlspecialchars($companyName) ?>"
                                class="company-logo-list rounded">
                        </div>
                        <div class="col-md-7">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($row['judul'] ?? 'Judul tidak tersedia') ?></h5>
                            <p class="mb-1 text-muted">
                                <i class="fa fa-building me-2"></i><?= htmlspecialchars($companyName) ?> |
                                <i class="fa fa-map-marker-alt me-2"></i><?= htmlspecialchars($row['lokasi'] ?? 'Lokasi tidak tersedia') ?> |
                                <i class="fa fa-tags me-2"></i><?= htmlspecialchars($row['kategori'] ?? 'Kategori tidak tersedia') ?>
                            </p>
                            <p class="mb-1 text-muted">
                                <i class="fa fa-briefcase me-2"></i><?= htmlspecialchars(formatTipePekerjaan($row['tipe_pekerjaan'] ?? '')) ?> |
                                <i class="fa fa-coins me-2"></i><?= htmlspecialchars($row['gaji_range'] ?? 'Gaji tidak tersedia') ?> |
                                <i class="fa fa-building me-2"></i><?= htmlspecialchars($row['mode_kerja'] ?? 'Mode kerja tidak tersedia') ?>
                            </p>
                            <p class="mb-0"><?= htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 150)) ?>...</p>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="job-detail.php?id=<?= htmlspecialchars($row['id_lowongan']) ?>" class="btn btn-primary rounded-pill px-4">Apply Now</a>
                            <div class="text-muted mt-2">
                                <i class="fa fa-hourglass-half me-2"></i>
                                Batas: <?= !empty($row['batas_tanggal']) ? date('d M Y', strtotime($row['batas_tanggal'])) : 'Tidak ditentukan' ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Tidak ada lowongan yang ditemukan</h5>
                <?php if ($totalData === 0 && empty($searchKeyword) && empty($searchLokasi)): ?>
                    <p class="text-muted">Belum ada lowongan yang tersedia saat ini</p>
                    <?php if (isset($_GET['debug'])): ?>
                        <div class="alert alert-warning mt-3">
                            <small>
                                Debug Info:<br>
                                Success: <?= $result['success'] ? 'true' : 'false' ?><br>
                                Error: <?= $result['error'] ?? 'None' ?><br>
                                Data Count: <?= count($data) ?><br>
                                Total Data: <?= $totalData ?>
                            </small>
                        </div>
                    <?php endif; ?>
                <?php elseif (!empty($searchKeyword) || (!empty($searchLokasi) && $searchLokasi !== 'semua')): ?>
                    <p class="text-muted">Coba ubah kata kunci atau filter lokasi Anda</p>
                    <a href="?keyword=&lokasi=" class="btn btn-primary mt-2">Tampilkan Semua Lowongan</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav>
                    <ul class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $currentPage - 1 ?>&keyword=<?= urlencode($searchKeyword) ?>&lokasi=<?= urlencode($searchLokasi) ?>">
                                    « Prev
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?page=<?= $i ?>&keyword=<?= urlencode($searchKeyword) ?>&lokasi=<?= urlencode($searchLokasi) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $currentPage + 1 ?>&keyword=<?= urlencode($searchKeyword) ?>&lokasi=<?= urlencode($searchLokasi) ?>">
                                    Next »
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    <!-- Jobs End -->

    <?php include "include/footer.php" ?>

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
    <script>
        // Fungsi untuk handle save button dengan AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const saveForms = document.querySelectorAll('.save-form');

            saveForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const saveBtn = this.querySelector('.btn-save');
                    const icon = saveBtn.querySelector('i');

                    // Disable button selama proses
                    saveBtn.disabled = true;

                    fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (response.ok) {
                                // Toggle kelas saved
                                saveBtn.classList.toggle('saved');

                                // Toggle icon
                                if (saveBtn.classList.contains('saved')) {
                                    icon.classList.remove('far');
                                    icon.classList.add('fas');
                                    saveBtn.title = 'Hapus dari favorit';
                                } else {
                                    icon.classList.remove('fas');
                                    icon.classList.add('far');
                                    saveBtn.title = 'Simpan ke favorit';
                                }

                                // Toggle action value
                                const actionInput = this.querySelector('input[name="action"]');
                                actionInput.value = saveBtn.classList.contains('saved') ? 'unsave' : 'save';
                            }
                            return response;
                        })
                        .finally(() => {
                            saveBtn.disabled = false;
                        });
                });
            });
        });

        // Show toast notification for save actions
        function showSaveNotification(message, type = 'success') {
            // Remove existing notifications
            const existingNotif = document.querySelector('.save-notification');
            if (existingNotif) existingNotif.remove();

            // Create notification
            const notification = document.createElement('div');
            notification.className = `save-notification alert alert-${type} alert-dismissible fade show`;
            notification.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            // Add styles
            notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }

        // Add CSS for notification
        const style = document.createElement('style');
        style.textContent = `
    .save-notification {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
        document.head.appendChild(style);
    </script>
</body>

</html>