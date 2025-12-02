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
        // Ambil CV URL dari table cv (bukan dari pencaker)
        $cv_url = $cvData['cv_url'] ?? '';
        $use_profile_cv = $_POST['use_profile_cv'] ?? 'yes';

        if ($use_profile_cv === 'no' && isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = supabaseStorageUpload('cv', 'cv_' . $user_id . '_' . time() . '.pdf', $_FILES['cv_file']);
            if ($uploadResult['success']) {
                $cv_url = getStoragePublicUrl('cv', 'cv_' . $user_id . '_' . time() . '.pdf');
            } else {
                $error_message = 'Gagal upload CV. Silakan coba lagi.';
            }
        }

        if (empty($error_message)) {
            // Simpan lamaran dengan catatan
            $lamaranData = [
                'id_lowongan' => $id_lowongan,
                'id_pencaker' => $pencaker['id_pencaker'],
                'cv_url' => $cv_url,
                'catatan_pelamar' => $catatan_pelamar,
                'status' => 'diproses',
                'tanggal_lamaran' => date('Y-m-d H:i:s')
            ];

            $resultApply = supabaseInsert('lamaran', $lamaranData);

            if ($resultApply['success']) {
                $_SESSION['success_message'] = 'Lamaran berhasil dikirim! Perusahaan akan melihat catatan Anda.';
                header('Location: profile.php');
                exit;
            } else {
                $error_message = 'Gagal mengirim lamaran. Silakan coba lagi.';
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
        .apply-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .job-header {
            background: linear-gradient(135deg, #001f66, #003399);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .apply-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
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
            color: white !important;
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
    <!-- Navbar End -->

    <div class="apply-container">
        <!-- Job Header -->
        <div class="job-header">
            <div class="d-flex align-items-center">
                <?php if (!empty($data['perusahaan']['logo'])): ?>
                    <img src="<?= htmlspecialchars($data['perusahaan']['logo']) ?>" alt="Logo Perusahaan" class="company-logo">
                <?php endif; ?>
                <div>
                    <h1 class="h2 mb-2"><?= htmlspecialchars($data['judul']) ?></h1>
                    <p class="mb-1">
                        <i class="fas fa-building me-2"></i><?= htmlspecialchars($data['perusahaan']['nama']) ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($data['lokasi']) ?>
                    </p>
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
            <div class="apply-card">
                <h3 class="mb-4" style="color: #001f66;">Kirim Lamaran</h3>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
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
                        </div>
                    </div>

                    <!-- CV -->
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: #001f66;">Curriculum Vitae (CV)</h5>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_profile_cv" id="use_profile_cv_yes" value="yes" <?= !empty($cvData['cv_url']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="use_profile_cv_yes">
                                    Gunakan CV dari Profil
                                    <?php if (!empty($cvData['cv_url'])): ?>
                                        <span class="text-success">(CV tersedia)</span>
                                    <?php else: ?>
                                        <span class="text-danger">(Belum ada CV di profil)</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_profile_cv" id="use_profile_cv_no" value="no" <?= empty($cvData['cv_url']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="use_profile_cv_no">
                                    Upload CV Baru
                                </label>
                            </div>
                        </div>

                        <div id="cv_upload_section" style="<?= empty($cvData['cv_url']) ? 'display: block;' : 'display: none;' ?>">
                            <label class="form-label">Upload CV Baru (PDF, Max 5MB)</label>
                            <input type="file" class="form-control" name="cv_file" accept=".pdf,.doc,.docx" <?= empty($cvData['cv_url']) ? 'required' : '' ?>>
                            <small class="text-muted">Format: PDF, DOC, DOCX. Maksimal 5MB.</small>
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
                        <button type="submit" class="btn-apply btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Kirim Lamaran
                        </button>
                        <a href="job-detail.php?id=<?= $id_lowongan ?>" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle CV upload section
        document.querySelectorAll('input[name="use_profile_cv"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const uploadSection = document.getElementById('cv_upload_section');
                const fileInput = uploadSection.querySelector('input[type="file"]');

                if (this.value === 'no') {
                    uploadSection.style.display = 'block';
                    fileInput.setAttribute('required', 'required');
                } else {
                    uploadSection.style.display = 'none';
                    fileInput.removeAttribute('required');
                }
            });
        });

        // Character counter for catatan
        const catatanTextarea = document.getElementById('catatan_pelamar');
        const charCount = document.getElementById('char_count');

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

        // Validasi form
        document.querySelector('form').addEventListener('submit', function(e) {
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

            const useProfileCv = document.querySelector('input[name="use_profile_cv"]:checked').value;
            if (useProfileCv === 'no') {
                const fileInput = document.querySelector('input[type="file"]');
                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Silakan pilih file CV');
                    return;
                }

                const file = fileInput.files[0];
                const fileSize = file.size / 1024 / 1024; // MB

                if (fileSize > 5) {
                    e.preventDefault();
                    alert('Ukuran file maksimal 5MB');
                    return;
                }
            }
        });
    </script>
</body>

</html>