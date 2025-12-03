<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';
require_once __DIR__ . '/../function/job-functions.php';

// Definisikan $isLoggedIn untuk navbar
$isLoggedIn = isset($_SESSION['user_id']);

// Cek apakah user sudah login sebagai pencaker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pencaker') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_lowongan = $_GET['id'] ?? null;

if (!$id_lowongan) {
    header('Location: job-list.php');
    exit;
}

// Ambil data lowongan
$result = getDetailLowongan($id_lowongan);
if (!$result['success']) {
    header('Location: job-list.php');
    exit;
}

$lowongan = $result['data'];
$data = formatLowongan($lowongan);

// Ambil data perusahaan
$perusahaanResult = supabaseQuery('perusahaan', [
    'select' => 'nama_perusahaan, logo_url',
    'id_perusahaan' => 'eq.' . $lowongan['id_perusahaan'],
    'limit' => 1
]);

$perusahaan = [];
if ($perusahaanResult['success'] && count($perusahaanResult['data']) > 0) {
    $perusahaan = $perusahaanResult['data'][0];
}

// Ambil data pencaker
$pencaker = getPencakerByUserId($user_id);

// Cek apakah sudah pernah apply
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
    $cvData = $hasCV ? $checkCV['data'][0] : null;
}

// Redirect jika tidak memiliki CV
if ($pencaker && !$hasCV) {
    $_SESSION['error_message'] = 'Silakan lengkapi CV Anda terlebih dahulu sebelum mengirim lamaran.';
    header('Location: profile.php');
    exit;
}

// Proses apply job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyApplied && $hasCV) {
    $catatan_pelamar = trim($_POST['catatan_pelamar'] ?? '');

    // Validasi catatan
    if (empty($catatan_pelamar)) {
        $error_message = 'Silakan tulis catatan untuk perusahaan.';
    } elseif (strlen($catatan_pelamar) > 1000) {
        $error_message = 'Catatan maksimal 1000 karakter.';
    } else {
        // Langsung gunakan CV dari profil
        $cv_url = $cvData['cv_url'] ?? '';

        if (empty($cv_url)) {
            $error_message = 'CV tidak ditemukan. Silakan perbarui CV di profil.';
        } else {
            // PERBAIKAN: Sesuaikan dengan struktur tabel lamaran yang benar
            $lamaranData = [
                'id_lowongan' => $id_lowongan,
                'id_pencaker' => $pencaker['id_pencaker'],
                'cv_url' => $cv_url,
                'catatan' => $catatan_pelamar, // PERBAIKAN: 'catatan' bukan 'catatan_pelamar'
                'status' => 'diproses'
                // PERBAIKAN: 'dibuat_pada' akan diisi otomatis oleh database (CURRENT_TIMESTAMP)
            ];

            $resultApply = supabaseInsert('lamaran', $lamaranData);

            if ($resultApply['success']) {
                $_SESSION['success_message'] = 'Lamaran berhasil dikirim! Perusahaan akan melihat catatan Anda.';
                header('Location: aktivitas.php');
                exit;
            } else {
                // Tampilkan error detail untuk debugging
                $error_message = 'Gagal mengirim lamaran. Error: ' . ($resultApply['error'] ?? 'Unknown error');
                error_log('Apply Job Error: ' . print_r($resultApply, true));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Job - <?= htmlspecialchars($data['judul']) ?></title>
    <link href="../assets/img/favicon.ico" rel="icon">

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
            background-color: #ffffff;
        }

        .apply-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .job-header {
            background: linear-gradient(to right, #224BA4 0%, #ffffff 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            height: 200px;
            color: white !important;
        }

        .job-header h1,
        .job-header p,
        .job-header i,
        .job-header h3,
        .job-header .fs-6 {
            color: white !important;
        }

        .apply-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px blue;
        }

        .form-label {
            font-weight: 600;
            color: #001f66;
            margin-bottom: 8px;
        }

        .btn-apply {
            background: linear-gradient(135deg, #001f66, #003399);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
        }

        .catatan-info {
            background: #e3f2fd;
            border: 1px solid #001f66;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .char-count {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }

        .char-count.warning {
            color: #dc3545;
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

        .job-header .h2 {
            color: #001f66 !important;
        }

        .salary-badge {
            background-color: #001f66;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        /* Tambahkan di bagian style dalam head */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
        }

        /* Tambahkan di bagian style dalam head */
        .badge.bg-primary {
            padding: 5px 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .text-truncate {
            white-space: nowrap;
        }

        /* Untuk responsif pada mobile */
        @media (max-width: 768px) {
            .d-flex.align-items-center {
                flex-wrap: wrap;
            }

            .text-truncate {
                margin-bottom: 5px;
            }

            .d-flex.align-items-center.mb-3 {
                margin-bottom: 10px !important;
            }
        }

        .fs-6 {
            font-size: 0.9rem !important;
        }

        /* Responsif untuk mobile */
        @media (max-width: 768px) {
            .d-flex.align-items-center.mb-3 {
                flex-wrap: wrap;
            }

            .ms-auto.text-primary.fs-6 {
                margin-left: 0 !important;
                margin-top: 8px;
                width: 100%;
            }

            .d-flex.align-items-center {
                flex-wrap: wrap;
            }

            .ms-auto {
                margin-left: 0 !important;
                margin-top: 8px;
            }
        }

        /* Styling untuk modal konfirmasi */
        .modal-content {
            border-radius: 15px;
            border: 2px solid #001f66;
        }

        .modal-header.bg-primary {
            border-radius: 13px 13px 0 0;
            background:  linear-gradient(to right, #224BA4 0%, #ffffff 100%);;
        }

        .modal-body {
            padding: 2rem;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Tombol konfirmasi */
        #btnSubmitConfirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }

        #btnSubmitConfirm:hover {
            background: linear-gradient(135deg, #218838, #1e9e8a);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        /* Tombol konfirmasi utama */
        #btnConfirmApply {
            transition: all 0.3s ease;
        }

        #btnConfirmApply:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 31, 102, 0.3);
        }

        .modal-title {
            color: white;
        }
    </style>
</head>

<body>
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
    <a href="job-detail.php?id=<?= $id_lowongan ?>" class="text-primary position-absolute" style="top: 100px; left: 30px; background: white; padding: 5px 15px; font-size: 1rem; border-radius: 5px; text-decoration: none; z-index: 10;">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <div class="apply-container">
        <!-- Job Header -->
        <div class="job-header">
            <div class="d-flex align-items-center mb-5">
                <?php if (!empty($perusahaan['logo_url'])): ?>
                    <img class="flex-shrink-0 img-fluid border rounded company-logo" src="<?= htmlspecialchars($perusahaan['logo_url']) ?>" alt="<?= htmlspecialchars($perusahaan['nama_perusahaan'] ?? 'Company Logo') ?>">
                <?php else: ?>
                    <img class="flex-shrink-0 img-fluid border rounded company-logo" src="../assets/img/com-logo-2.jpg" alt="Default Company Logo">
                <?php endif; ?>
                <div class="text-start ps-4 w-100">
                    <h3 class="mb-2"><?= htmlspecialchars($data['judul']) ?></h3>

                    <!-- Nama Perusahaan -->
                    <h5 class="text-muted mb-2"><?= htmlspecialchars($perusahaan['nama_perusahaan'] ?? '') ?></h5>

                    <!-- Baris Kategori dan Gaji (sejajar) -->
                    <div class="d-flex align-items-center mb-3">
                        <!-- Badge Kategori -->
                        <span class="badge bg-primary me-3"><?= htmlspecialchars($data['kategori']) ?></span>
                        <!-- Gaji di sebelah kanan badge -->
                        <span class="text-primary fs-6 fw-bold">
                            <?= htmlspecialchars($data['gaji']) ?></span>
                    </div>

                    <!-- Baris Lokasi dan Tanggal (sejajar) -->
                    <div class="d-flex align-items-center mb-2">
                        <!-- Lokasi -->
                        <span class="text-truncate me-4">
                            <i class="fa fa-map-marker-alt text-primary me-2"></i>
                            <?= htmlspecialchars($data['lokasi']) ?>
                        </span>
                        <!-- Tanggal Dibuat -->
                        <span class="text-muted fs-6">
                            <i class="far fa-calendar-alt me-2"></i>
                            <?= htmlspecialchars(date('d M Y', strtotime($data['dibuat_pada']))) ?>
                        </span>
                    </div>

                    <!-- Baris Tipe Pekerjaan dan Mode Kerja -->
                    <div class="d-flex align-items-center">
                        <!-- Tipe Pekerjaan -->
                        <span class="text-truncate me-4">
                            <i class="far fa-clock text-primary me-2"></i>
                            <?= htmlspecialchars(formatTipePekerjaan($data['tipe_pekerjaan'])) ?>
                        </span>
                        <!-- Mode Kerja -->
                        <span class="text-truncate">
                            <i class="fas fa-laptop-house text-primary me-2"></i>
                            <?= htmlspecialchars($data['mode_kerja']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($alreadyApplied): ?>
            <!-- Sudah Apply -->
            <div class="apply-card text-center">
                <div class="alert alert-info border-0">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3 class="text-success">Lamaran Telah Dikirim</h3>
                    <p class="mb-3">Anda sudah mengirim lamaran untuk posisi ini.</p>
                    <a href="profile.php" class="btn btn-primary">Kembali ke Profil</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Form Apply -->
            <div class="apply-card border border-primary" style="border-width: 2px !important;">
                <h3 class="mb-4" style="color: #001f66;">Kirim Lamaran</h3>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="applyForm">
                    <!-- Data Pribadi -->
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: #001f66;">Data Pribadi</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($pencaker['nama_lengkap'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($pencaker['email_pencaker'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($pencaker['no_hp'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pengalaman</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($pencaker['pengalaman_tahun'] ?? '0') ?> Tahun" readonly>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Gaji yang Diharapkan</label>
                                <input type="text" class="form-control" value="Rp. <?= htmlspecialchars($data['gaji']) ?> juta/bulan" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3" style="color: #001f66;">Curriculum Vitae (CV)</h5>
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-file-pdf fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">CV yang akan digunakan:</h6>
                                    <p class="mb-1">CV dari profil Anda akan digunakan untuk melamar pekerjaan ini.</p>
                                    <?php if (!empty($cvData['cv_url'])): ?>
                                        <a href="<?= htmlspecialchars($cvData['cv_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="fas fa-eye me-1"></i> Lihat CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-danger">CV tidak tersedia di profil</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Untuk mengubah CV, silakan perbarui CV di halaman profil Anda.
                            </small>
                        </div>
                    </div>

                    <!-- Catatan untuk Perusahaan -->
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: #001f66;">Catatan untuk Perusahaan</h5>
                        <div class="catatan-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan ini hanya akan dilihat oleh perusahaan:</strong>
                            Ceritakan mengapa Anda tertarik dengan posisi ini, kelebihan Anda,
                            dan mengapa perusahaan harus mempertimbangkan Anda.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tulis catatan untuk perusahaan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="catatan_pelamar" id="catatan_pelamar" rows="6"
                                placeholder="Contoh: 
Saya sangat tertarik dengan lowongan ini karena...
Saya memiliki pengalaman di bidang... selama... tahun
Saya yakin dapat berkontribusi dengan kemampuan...
..."
                                maxlength="1000" required><?= isset($_POST['catatan_pelamar']) ? htmlspecialchars($_POST['catatan_pelamar']) : '' ?></textarea>
                            <div class="char-count" id="char_count">0/1000 karakter</div>
                            <small class="text-muted">Wajib diisi. Maksimal 1000 karakter.</small>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="button" class="btn-apply btn-lg" id="btnConfirmApply">
                            <i class="fas fa-paper-plane me-2"></i>Kirim Lamaran
                        </button>
                    </div>

                    <!-- Modal Konfirmasi -->
                    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="confirmModalLabel">
                                        <i class="fas fa-paper-plane me-2"></i>Konfirmasi Pengiriman Lamaran
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-4">
                                        <div class="mb-3">
                                            <i class="fas fa-question-circle fa-4x text-primary"></i>
                                        </div>
                                        <h5 class="mb-3">Apakah Anda yakin ingin mengirim lamaran?</h5>
                                        <p class="text-muted">
                                            Lamaran Anda akan dikirim ke perusahaan dan tidak dapat dibatalkan.
                                            Pastikan data dan catatan Anda sudah benar.
                                        </p>

                                        <div class="alert alert-info text-start">
                                            <h6><i class="fas fa-info-circle me-2"></i>Detail Lamaran:</h6>
                                            <ul class="mb-0">
                                                <li><strong>Posisi:</strong> <?= htmlspecialchars($data['judul']) ?></li>
                                                <li><strong>Perusahaan:</strong> <?= htmlspecialchars($perusahaan['nama_perusahaan'] ?? '') ?></li>
                                                <li><strong>CV:</strong> Akan menggunakan CV dari profil Anda</li>
                                                <li><strong>Status:</strong> Akan diproses oleh perusahaan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="btnSubmitConfirm">
                                        <i class="fas fa-paper-plane me-2"></i>Ya, Kirim Lamaran
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Character counter for catatan
        const catatanTextarea = document.getElementById('catatan_pelamar');
        const charCount = document.getElementById('char_count');

        if (catatanTextarea) {
            catatanTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = `${length}/1000 karakter`;

                if (length > 900) {
                    charCount.classList.add('warning');
                } else {
                    charCount.classList.remove('warning');
                }
            });

            // Trigger initial count
            catatanTextarea.dispatchEvent(new Event('input'));
        }

        // Konfirmasi sebelum submit
        const btnConfirmApply = document.getElementById('btnConfirmApply');
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const btnSubmitConfirm = document.getElementById('btnSubmitConfirm');
        const applyForm = document.getElementById('applyForm');

        if (btnConfirmApply && confirmModal && btnSubmitConfirm && applyForm) {
            // Tampilkan modal konfirmasi saat tombol kirim diklik
            btnConfirmApply.addEventListener('click', function() {
                // Validasi catatan terlebih dahulu
                const catatan = document.getElementById('catatan_pelamar');

                if (catatan.value.trim().length === 0) {
                    alert('Silakan tulis catatan untuk perusahaan');
                    catatan.focus();
                    return;
                }

                if (catatan.value.length > 1000) {
                    alert('Catatan maksimal 1000 karakter');
                    return;
                }

                // Tampilkan modal konfirmasi
                confirmModal.show();
            });

            // Submit form saat tombol konfirmasi di modal diklik
            btnSubmitConfirm.addEventListener('click', function() {
                // Tambahkan loading state
                const originalText = btnSubmitConfirm.innerHTML;
                btnSubmitConfirm.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
                btnSubmitConfirm.disabled = true;

                // Submit form
                applyForm.submit();
            });
        }

        // Validasi form saat submit langsung (untuk keamanan tambahan)
        applyForm.addEventListener('submit', function(e) {
            const catatan = document.getElementById('catatan_pelamar');

            if (catatan.value.trim().length === 0) {
                e.preventDefault();
                alert('Silakan tulis catatan untuk perusahaan');
                catatan.focus();
                return;
            }

            if (catatan.value.length > 1000) {
                e.preventDefault();
                alert('Catatan maksimal 1000 karakter');
                return;
            }

            // Optional: Tampilkan loading di tombol utama juga
            const mainSubmitBtn = document.querySelector('button[type="submit"]');
            if (mainSubmitBtn) {
                mainSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
                mainSubmitBtn.disabled = true;
            }
        });
    </script>
</body>

</html>