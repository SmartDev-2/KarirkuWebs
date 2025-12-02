<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user dari tabel pengguna
$user = getUserById($user_id);
if (!$user) {
    header('Location: login.php');
    exit;
}

// Ambil data profil dari tabel pencaker
$pencaker = getPencakerByUserId($user_id);

// Hitung usia dari tanggal lahir (hanya jika profil ada)
$usia = null;
if (!empty($pencaker['tanggal_lahir'])) {
    $tanggalLahir = new DateTime($pencaker['tanggal_lahir']);
    $today = new DateTime();
    $usia = $today->diff($tanggalLahir)->y;
}

// Ambil data lamaran user
$lamaranData = [];
if ($pencaker) {
    $result = supabaseQuery('lamaran', [
        'select' => '*, lowongan(judul, perusahaan(nama_perusahaan))',
        'id_pencaker' => 'eq.' . $pencaker['id_pencaker'],
        'order' => 'tanggal_lamaran.desc',
        'limit' => 10
    ]);

    if ($result['success']) {
        $lamaranData = $result['data'];
    }
}

// Hitung statistik lamaran
$jumlah_diproses = 0;
$jumlah_diterima = 0;
$jumlah_ditolak = 0;

foreach ($lamaranData as $lamaran) {
    switch ($lamaran['status']) {
        case 'diproses':
            $jumlah_diproses++;
            break;
        case 'diterima':
            $jumlah_diterima++;
            break;
        case 'ditolak':
            $jumlah_ditolak++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Profile - Karirku</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
    <link href="../assets/css/profile.css" rel="stylesheet">

    <style>
        /* Container */
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Main Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            position: relative;
            text-align: center;
        }

        .profile-header {
            display: flex;
            gap: 80px;
            margin-bottom: 30px;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f0f0f0;
        }

        .profile-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .profile-name {
            font-size: 22px;
            font-weight: 700;
            color: #003399;
            text-align: center;
        }

        .profile-info {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
            /* Tambahkan gap horizontal yang lebih besar */
            text-align: left;
            /* Pastikan text rata kiri */
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            text-align: left;
            /* Pastikan item rata kiri */
        }

        .info-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
            text-align: left;
            /* Label rata kiri */
        }

        .info-value {
            font-size: 15px;
            color: #2b3940;
            font-weight: 600;
            text-align: left;
            /* Value rata kiri */
        }

        .info-value a {
            color: #003399;
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        /* Status Badges */
        .status-badges {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .badge-custom {
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
            min-width: 150px;
        }

        .badge-yellow {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-green {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-red {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Bottom Cards */
        .bottom-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #2b3940;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            transition: 0.2s;
        }

        .card-item:last-child {
            border-bottom: none;
        }

        .card-item:hover {
            padding-left: 10px;
        }

        .card-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-icon {
            color: #003399;
            font-size: 20px;
        }

        .card-item-text {
            font-size: 15px;
            color: #2b3940;
            font-weight: 500;
        }

        .card-arrow {
            color: #6c757d;
            font-size: 18px;
        }

        .menu-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
        }

        .menu-icon:hover {
            color: #003399;
        }

        /* Empty Profile Styles */
        .empty-profile {
            text-align: center;
            padding: 60px 40px;
        }

        .empty-profile-icon {
            font-size: 80px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .empty-profile-title {
            font-size: 24px;
            font-weight: 600;
            color: #2b3940;
            margin-bottom: 10px;
        }

        /* .empty-profile-text {
            color: #6c757d;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        } */

        @media (max-width: 992px) {
            .bottom-cards {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
            }

            .profile-info {
                grid-template-columns: 1fr;
            }

            .status-badges {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
            <a href="../index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                <img src="../assets/img/logo.png" alt="">
            </a>

            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                <div class="navbar-nav ms-0 mt-1">
                    <a href="../index.php" class="nav-item nav-link">Home</a>
                    <a href="job-list.php" class="nav-item nav-link">Cari Pekerjaan</a>
                </div>
                <div class="auth-buttons d-flex align-items-center">
                    <span class="me-3">Halo, <?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <div class="profile-container">
        <?php if (!$pencaker): ?>
            <!-- Tampilan Profil Kosong -->
            <div class="profile-card empty-profile">
                <a href="edit_profile.php"><i class="bi bi-person-plus empty-profile-icon"></i></a>
                <h2 class="empty-profile-title">Tambahkan Profil</h2>
            </div>
        <?php else: ?>
            <!-- Tampilan Profil Lengkap -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-left">
                        <img src="<?php echo !empty($pencaker['foto_profil_url']) ? htmlspecialchars($pencaker['foto_profil_url']) : '../assets/img/default-avatar.png'; ?>"
                            alt="Profile"
                            class="profile-image">
                        <h2 class="profile-name">
                            <?php echo htmlspecialchars($pencaker['nama_lengkap'] ?? 'Nama Belum Diisi'); ?>
                        </h2>
                    </div>

                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($pencaker['no_hp'] ?? '-'); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($pencaker['email_pencaker'] ?? $user['email']); ?>">
                                    <?php echo htmlspecialchars($pencaker['email_pencaker'] ?? $user['email']); ?>
                                </a>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lokasi</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($pencaker['alamat'] ?? '-'); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Usia, jenis kelamin</span>
                            <span class="info-value">
                                <?php echo $usia ?? '-'; ?>,
                                <?php
                                $gender_display = [
                                    'male' => 'Laki-laki',
                                    'female' => 'Perempuan',
                                    'other' => 'Lainnya'
                                ];
                                echo htmlspecialchars($gender_display[$pencaker['gender']] ?? '-');
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Lahir</span>
                            <span class="info-value">
                                <?php
                                if (!empty($pencaker['tanggal_lahir'])) {
                                    $date = new DateTime($pencaker['tanggal_lahir']);
                                    echo $date->format('d F Y');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pengalaman Kerja</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($pencaker['pengalaman_tahun'] ?? '0'); ?> Tahun
                            </span>
                        </div>
                    </div>
                </div>

                <div class="status-badges">
                    <div class="badge-custom badge-yellow">Diproses (<?= $jumlah_diproses ?>)</div>
                    <div class="badge-custom badge-green">Diterima (<?= $jumlah_diterima ?>)</div>
                    <div class="badge-custom badge-red">Ditolak (<?= $jumlah_ditolak ?>)</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bottom Cards (Tetap Ditampilkan untuk Kedua Kondisi) -->
        <div class="bottom-cards">
            <!-- Aktivitas Card -->
            <div class="info-card">
                <h3 class="card-title">Aktivitas</h3>
                <div class="card-item" onclick="window.location.href='aktivitas.php'">
                    <div class="card-item-left">
                        <a href="aktivitas.php">
                            <i class="bi bi-file-text card-icon"></i>
                            <span class="card-item-text">Lamaran Saya</span>
                        </a>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
                <div class="card-item" onclick="window.location.href='aktivitas.php'">
                    <div class="card-item-left">
                        <i class="bi bi-bookmark card-icon"></i>
                        <span class="card-item-text">Disimpan</span>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
                <div class="card-item" onclick="window.location.href='aktivitas.php'">
                    <div class="card-item-left">
                        <i class="bi bi-file-earmark-person card-icon"></i>
                        <span class="card-item-text">CV Saya</span>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
            </div>

            <!-- Tampilan Card -->
            <div class="info-card">
                <h3 class="card-title">Tampilan</h3>
                <div class="card-item">
                    <div class="card-item-left">
                        <i class="bi bi-palette card-icon"></i>
                        <span class="card-item-text">Tema</span>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
            </div>

            <!-- Akun Card -->
            <div class="info-card">
                <h3 class="card-title">Akun</h3>
                <div class="card-item" onclick="window.location.href='edit_profile.php'">
                    <div class="card-item-left">
                        <i class="bi bi-pencil-square card-icon"></i>
                        <span class="card-item-text">Edit Profil</span>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
                <div class="card-item" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <div class="card-item-left">
                        <i class="bi bi-box-arrow-right card-icon"></i>
                        <span class="card-item-text">Keluar</span>
                    </div>
                    <i class="bi bi-chevron-right card-arrow"></i>
                </div>
            </div>
        </div>
        <!-- Section Lamaran Saya -->
        <?php if ($pencaker && !empty($lamaranData)): ?>
            <div class="lamaran-section">
                <div class="section-header">
                    <h3>Lamaran Terbaru</h3>
                    <a href="my-applications.php" class="view-all">Lihat Semua</a>
                </div>

                <div class="lamaran-list">
                    <?php foreach (array_slice($lamaranData, 0, 3) as $lamaran): ?>
                        <div class="lamaran-item">
                            <div class="lamaran-header">
                                <h4><?= htmlspecialchars($lamaran['lowongan']['judul']) ?></h4>
                                <span class="status-badge status-<?= $lamaran['status'] ?>">
                                    <?= ucfirst($lamaran['status']) ?>
                                </span>
                            </div>
                            <p class="company-name">
                                <?= htmlspecialchars($lamaran['lowongan']['perusahaan']['nama_perusahaan']) ?>
                            </p>
                            <p class="lamaran-date">
                                Dilamar pada: <?= date('d M Y', strtotime($lamaran['tanggal_lamaran'])) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <style>
                .lamaran-section {
                    background: white;
                    border-radius: 20px;
                    padding: 30px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    margin-top: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 25px;
                }

                .section-header h3 {
                    color: #003399;
                    font-size: 22px;
                    font-weight: 700;
                    margin: 0;
                }

                .view-all {
                    color: #003399;
                    text-decoration: none;
                    font-weight: 600;
                }

                .view-all:hover {
                    text-decoration: underline;
                }

                .lamaran-list {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }

                .lamaran-item {
                    background: #f8f9fa;
                    border-radius: 12px;
                    padding: 20px;
                    border-left: 4px solid #003399;
                }

                .lamaran-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 10px;
                }

                .lamaran-header h4 {
                    color: #2b3940;
                    font-size: 18px;
                    font-weight: 600;
                    margin: 0;
                    flex: 1;
                }

                .status-badge {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }

                .status-diproses {
                    background: #e3f2fd;
                    color: #1976d2;
                }

                .status-diterima {
                    background: #e8f5e8;
                    color: #2e7d32;
                }

                .status-ditolak {
                    background: #ffebee;
                    color: #c62828;
                }

                .company-name {
                    color: #6c757d;
                    margin: 5px 0;
                    font-size: 14px;
                }

                .lamaran-date {
                    color: #6c757d;
                    font-size: 12px;
                    margin: 5px 0 0 0;
                }

                .catatan-perusahaan {
                    background: white;
                    padding: 12px;
                    border-radius: 8px;
                    margin-top: 10px;
                    border-left: 3px solid #ffc107;
                }

                .catatan-perusahaan strong {
                    color: #856404;
                }
            </style>
        <?php endif; ?>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/lib/wow/wow.min.js"></script>
    <script src="../assets/lib/easing/easing.min.js"></script>
    <script src="../assets/lib/waypoints/waypoints.min.js"></script>

    <!-- Template Javascript -->
    <script src="../assets/js/main.js"></script>
    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Konfirmasi Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-box-arrow-right text-warning" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h5>Apakah Anda yakin ingin keluar?</h5>
                    <p class="text-muted">Anda harus login kembali untuk mengakses akun Anda.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="logout.php" class="btn btn-primary">Ya, Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>