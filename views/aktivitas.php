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

$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$error = isset($_GET['error']) ? urldecode($_GET['error']) : '';

// Cek jika ada pesan dari parameter GET
if (isset($_GET['success_msg'])) {
    $success = htmlspecialchars($_GET['success_msg']);
}
if (isset($_GET['error_msg'])) {
    $error = htmlspecialchars($_GET['error_msg']);
}

// Cek jika ada pesan dari save action
if (isset($_GET['saved'])) {
    $success = "Lowongan berhasil disimpan!";
} elseif (isset($_GET['unsaved'])) {
    $success = "Lowongan berhasil dihapus dari favorit!";
}

// Ambil lowongan yang disimpan (favorit)
$savedJobs = [];
if ($pencaker) {
    $result = supabaseQuery('favorit_lowongan', [
        'select' => '*, lowongan!inner(*, perusahaan!inner(nama_perusahaan, logo_url))',
        'id_pencaker' => 'eq.' . $pencaker['id_pencaker'],
        'order' => 'dibuat_pada.desc'
    ]);

    if ($result['success']) {
        $savedJobs = $result['data'];
    }
}

// Ambil riwayat lamaran dengan format yang benar
$applications = [];
if ($pencaker) {
    $result = supabaseQuery('lamaran', [
        'select' => '*, lowongan!inner(judul, lokasi, perusahaan!inner(nama_perusahaan, logo_url))',
        'id_pencaker' => 'eq.' . $pencaker['id_pencaker'],
        'order' => 'dibuat_pada.desc'
    ]);

    if ($result['success']) {
        $applications = $result['data'];
    }
}

// Handle Upload CV (Edit/Replace)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    $file = $_FILES['cv_file'];

    // Validasi file
    $allowedTypes = ['application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        header('Location: aktivitas.php?error_msg=' . urlencode('Hanya file PDF yang diperbolehkan.'));
        exit;
    } elseif ($file['size'] > $maxSize) {
        header('Location: aktivitas.php?error_msg=' . urlencode('Ukuran file maksimal 5MB.'));
        exit;
    } else {
        // Hapus CV lama jika ada (dari storage dan database)
        if ($cvData) {
            // Hapus file lama dari Supabase Storage
            $parsedUrl = parse_url($cvData['cv_url']);
            $oldPath = str_replace('/storage/v1/object/public/cv/', '', $parsedUrl['path']);
            supabaseStorageDelete('cv', $oldPath);

            // Hapus record lama dari database
            supabaseDelete('cv', 'id_cv', $cvData['id_cv']);
        }

        // Upload file baru ke Supabase Storage
        $fileName = 'cv_' . $pencaker['id_pencaker'] . '.pdf'; // Nama tetap untuk menimpa
        $filePath = 'pencaker/' . $fileName;

        $uploadResult = supabaseStorageUpload('cv', $filePath, $file);

        if ($uploadResult['success']) {
            $cvUrl = getStoragePublicUrl('cv', $filePath);

            // Insert atau update CV di tabel cv
            $insertResult = supabaseInsert('cv', [
                'id_pencaker' => $pencaker['id_pencaker'],
                'id_pengguna' => $user_id,
                'nama_file' => $fileName,
                'cv_url' => $cvUrl,
                'uploaded_at' => date('Y-m-d H:i:s')
            ]);

            // Update juga di tabel pencaker untuk backward compatibility
            updatePencakerProfile($pencaker['id_pencaker'], [
                'cv_url' => $cvUrl,
                'diperbarui_pada' => date('Y-m-d H:i:s')
            ]);

            if ($insertResult['success']) {
                header('Location: aktivitas.php?success_msg=' . urlencode('CV berhasil diupdate!'));
                exit;
            } else {
                header('Location: aktivitas.php?error_msg=' . urlencode('Gagal menyimpan CV ke database.'));
                exit;
            }
        } else {
            header('Location: aktivitas.php?error_msg=' . urlencode('Gagal mengupload CV.'));
            exit;
        }
    }
}

// Handle Delete CV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cv'])) {
    if ($cvData) {
        // Hapus file dari Supabase Storage
        $parsedUrl = parse_url($cvData['cv_url']);
        $path = str_replace('/storage/v1/object/public/cv/', '', $parsedUrl['path']);

        $deleteStorageResult = supabaseStorageDelete('cv', $path);

        if ($deleteStorageResult['success']) {
            // Hapus dari tabel cv
            $deleteDbResult = supabaseDelete('cv', 'id_cv', $cvData['id_cv']);

            // Update juga di tabel pencaker (kosongkan cv_url)
            updatePencakerProfile($pencaker['id_pencaker'], [
                'cv_url' => null,
                'diperbarui_pada' => date('Y-m-d H:i:s')
            ]);

            if ($deleteDbResult['success']) {
                header('Location: aktivitas.php?success_msg=' . urlencode('CV berhasil dihapus!'));
                exit;
            } else {
                header('Location: aktivitas.php?error_msg=' . urlencode('Gagal menghapus CV dari database.'));
                exit;
            }
        } else {
            header('Location: aktivitas.php?error_msg=' . urlencode('Gagal menghapus CV dari storage.'));
            exit;
        }
    }
}

// Handle Remove Favorite
if (isset($_GET['remove_favorite']) && is_numeric($_GET['remove_favorite'])) {
    $id_favorit = $_GET['remove_favorite'];

    if ($pencaker) {
        // Verifikasi bahwa favorit milik user ini
        $checkResult = supabaseQuery('favorit_lowongan', [
            'select' => 'id_favorit',
            'id_favorit' => 'eq.' . $id_favorit,
            'id_pencaker' => 'eq.' . $pencaker['id_pencaker']
        ]);

        if ($checkResult['success'] && !empty($checkResult['data'])) {
            $deleteResult = supabaseDelete('favorit_lowongan', 'id_favorit', $id_favorit);

            if ($deleteResult['success']) {
                header('Location: aktivitas.php?success_msg=' . urlencode('Lowongan berhasil dihapus dari favorit!'));
                exit();
            } else {
                header('Location: aktivitas.php?error_msg=' . urlencode('Gagal menghapus dari favorit.'));
                exit();
            }
        } else {
            header('Location: aktivitas.php?error_msg=' . urlencode('Lowongan tidak ditemukan dalam favorit.'));
            exit();
        }
    }
}

// Ambil data CV dari tabel cv (hanya 1 CV per pencaker)
$cvData = null;
if ($pencaker) {
    $result = supabaseQuery('cv', [
        'select' => '*',
        'id_pencaker' => 'eq.' . $pencaker['id_pencaker'],
        'order' => 'uploaded_at.desc',
        'limit' => 1
    ]);

    if ($result['success'] && !empty($result['data'])) {
        $cvData = $result['data'][0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Aktivitas - Karirku</title>
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

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/aktivitas.css">
</head>

<body>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
            <a href="../index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                <img src="../assets/img/logo.png" alt="Karirku">
            </a>

            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                <div class="navbar-nav ms-0 mt-1">
                    <a href="../index.php" class="nav-item nav-link">Home</a>
                    <a href="job-list.php" class="nav-item nav-link">Cari Pekerjaan</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <div class="activity-container">
        <!-- Back Button -->
        <a href="profile.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali ke Profil</span>
        </a>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Vertical Cards Layout -->
        <div class="cards-vertical">
            <!-- Card 1: Lamaran Saya -->
            <div class="activity-card">
                <div class="card-header">
                    <h6><i class="bi bi-file-text me-2"></i>Lamaran Saya</h6>
                    <span class="badge bg-primary"><?php echo count($applications); ?> lamaran</span>
                </div>

                <div class="applications-table">
                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox-fill"></i>
                            <p>Belum ada lamaran</p>
                            <a href="job-list.php" class="btn btn-outline-primary mt-2">Cari Lowongan</a>
                        </div>
                    <?php else: ?>
                        <div id="applicationList">
                            <?php foreach ($applications as $index => $app):
                                $job = $app['lowongan'] ?? [];
                                $company = $job['perusahaan'] ?? [];
                                $isHidden = $index >= 5 ? 'style="display:none;"' : '';
                            ?>
                                <div class="application-row" <?php echo $isHidden; ?>>
                                    <img src="<?php echo htmlspecialchars($company['logo_url'] ?? '../assets/img/default-company.png'); ?>"
                                        alt="<?php echo htmlspecialchars($company['nama_perusahaan'] ?? 'Perusahaan'); ?>"
                                        class="job-logo">
                                    <div class="job-info">
                                        <div class="job-title"><?php echo htmlspecialchars($job['judul'] ?? 'Judul tidak tersedia'); ?></div>
                                        <div class="job-company"><?php echo htmlspecialchars($company['nama_perusahaan'] ?? 'Perusahaan tidak tersedia'); ?></div>
                                        <div class="job-location">
                                            <i class="bi bi-calendar"></i>
                                            <?php echo date('d M Y', strtotime($app['dibuat_pada'] ?? date('Y-m-d'))); ?>
                                            •
                                            <i class="bi bi-geo-alt ms-2"></i>
                                            <?php echo htmlspecialchars($job['lokasi'] ?? 'Lokasi tidak tersedia'); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $app['status'] ?? 'diproses'; ?>">
                                        <?php
                                        $statusText = [
                                            'diproses' => 'Diproses',
                                            'diterima' => 'Diterima',
                                            'ditolak' => 'Ditolak',
                                            'lanjutan' => 'Lanjutan'
                                        ];
                                        echo $statusText[$app['status'] ?? 'diproses'] ?? 'Diproses';
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($applications) > 5): ?>
                            <div class="show-toggle">
                                <button class="btn-toggle" id="toggleApplicationBtn" onclick="toggleApplications()">
                                    Tampilkan Lebih Banyak (<span id="appCount"><?php echo count($applications) - 5; ?></span>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card 2: Lowongan Disimpan -->
            <div class="activity-card">
                <div class="card-header">
                    <h6><i class="bi bi-bookmark-fill me-2"></i>Lowongan Disimpan</h6>
                    <span class="badge bg-warning text-dark"><?php echo count($savedJobs); ?> disimpan</span>
                </div>

                <div class="saved-jobs-table">
                    <?php if (empty($savedJobs)): ?>
                        <div class="empty-state">
                            <i class="bi bi-bookmark"></i>
                            <p>Belum ada lowongan yang disimpan</p>
                            <p class="text-muted small mt-2">Klik ikon <i class="fas fa-bookmark text-warning"></i> di halaman lowongan untuk menyimpan</p>
                            <a href="job-list.php" class="btn btn-outline-warning mt-2">
                                <i class="bi bi-search me-2"></i>Jelajahi Lowongan
                            </a>
                        </div>
                    <?php else: ?>
                        <div id="jobList">
                            <?php foreach ($savedJobs as $index => $saved):
                                $job = $saved['lowongan'] ?? [];
                                $company = $job['perusahaan'] ?? [];
                                $isHidden = $index >= 5 ? 'style="display:none;"' : '';
                            ?>
                                <div class="job-row" <?php echo $isHidden; ?>>
                                    <img src="<?php echo htmlspecialchars($company['logo_url'] ?? '../assets/img/default-company.png'); ?>"
                                        alt="<?php echo htmlspecialchars($company['nama_perusahaan'] ?? 'Perusahaan'); ?>"
                                        class="job-logo">
                                    <div class="job-info">
                                        <div class="job-title"><?php echo htmlspecialchars($job['judul'] ?? 'Judul tidak tersedia'); ?></div>
                                        <div class="job-company"><?php echo htmlspecialchars($company['nama_perusahaan'] ?? 'Perusahaan tidak tersedia'); ?></div>
                                        <div class="job-meta">
                                            <span class="job-location">
                                                <i class="bi bi-geo-alt"></i>
                                                <?php echo htmlspecialchars($job['lokasi'] ?? 'Lokasi tidak tersedia'); ?>
                                            </span>
                                            <span class="job-type">
                                                <i class="bi bi-briefcase"></i>
                                                <?php echo htmlspecialchars($job['tipe_pekerjaan'] ?? 'Full-time'); ?>
                                            </span>
                                            <span class="job-saved-date">
                                                <i class="bi bi-calendar"></i>
                                                <?php echo date('d M Y', strtotime($saved['dibuat_pada'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="job-actions">
                                        <button class="btn-view" onclick="window.location.href='job-detail.php?id=<?php echo $job['id_lowongan']; ?>'">
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                        <button class="btn-remove" onclick="removeFavorite(<?php echo $saved['id_favorit']; ?>, '<?php echo htmlspecialchars(addslashes($job['judul'])); ?>')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($savedJobs) > 5): ?>
                            <div class="show-toggle">
                                <button class="btn-toggle" id="toggleJobBtn" onclick="toggleJobs()">
                                    Tampilkan Lebih Banyak (<span id="jobCount"><?php echo count($savedJobs) - 5; ?></span>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card 3: CV Saya -->
            <div class="activity-card">
                <div class="card-header">
                    <h6><i class="bi bi-file-earmark-person-fill me-2"></i>CV Saya</h6>
                    <?php if ($cvData): ?>
                        <span class="badge bg-success">Tersedia</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Belum Upload</span>
                    <?php endif; ?>
                </div>

                <?php if (!$cvData): ?>
                    <!-- Upload CV (Pertama Kali) -->
                    <div class="cv-upload-section">
                        <i class="bi bi-cloud-upload cv-icon"></i>
                        <h3 class="cv-title">Upload CV Anda</h3>
                        <p class="cv-text">Format PDF • Maksimal ukuran file 5MB</p>
                        <p class="text-muted small mb-3">1 akun hanya dapat memiliki 1 CV</p>

                        <form method="POST" enctype="multipart/form-data" id="cvForm">
                            <div class="file-input-wrapper">
                                <input type="file" name="cv_file" id="cvFile" accept=".pdf" required>
                                <label for="cvFile" class="btn-upload">
                                    <i class="bi bi-upload"></i>
                                    Pilih File CV
                                </label>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload me-2"></i>Upload CV
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Display CV dalam format table seperti lowongan disimpan -->
                    <div id="cvList">
                        <div class="job-row">
                            <div class="job-info">
                                <div class="job-title"><?php echo htmlspecialchars($cvData['nama_file']); ?></div>
                                <div class="job-company">CV Anda</div>
                                <div class="job-meta">
                                    <span class="job-location">
                                        <i class="bi bi-calendar"></i>
                                        Diupdate: <?php echo date('d M Y H:i', strtotime($cvData['uploaded_at'])); ?>
                                    </span>
                                    <span class="job-type">
                                        <i class="bi bi-info-circle"></i>
                                        Upload CV baru akan menimpa CV yang lama
                                    </span>
                                    <span class="job-saved-date">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                        Format: PDF
                                    </span>
                                </div>
                            </div>
                            <div class="job-actions">
                                <a href="<?php echo htmlspecialchars($cvData['cv_url']); ?>" target="_blank" class="btn-view">
                                    <i class="bi bi-download"></i> Download
                                </a>

                                <form method="POST" enctype="multipart/form-data" class="d-inline edit" style="margin: 0;">
                                    <input type="file" name="cv_file" id="replaceCvFile" accept=".pdf" style="display: none;" onchange="this.form.submit()">
                                    <button type="button" class="btn-edit" onclick="document.getElementById('replaceCvFile').click()">
                                        <i class="bi bi-pencil"></i> Edit/Ganti
                                    </button>
                                </form>

                                <form method="POST" class="d-inline" style="margin: 0;">
                                    <button type="submit" name="delete_cv" class="btn-remove" onclick="return confirmDeleteCV()">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let showingAllJobs = false;
        let showingAllApplications = false;

        function toggleJobs() {
            const jobRows = document.querySelectorAll('.job-row');
            const toggleBtn = document.getElementById('toggleJobBtn');
            const jobCount = document.getElementById('jobCount');

            showingAllJobs = !showingAllJobs;

            jobRows.forEach((row, index) => {
                if (index >= 5) {
                    row.style.display = showingAllJobs ? 'flex' : 'none';
                }
            });

            if (showingAllJobs) {
                toggleBtn.innerHTML = 'Tampilkan Lebih Sedikit';
                jobCount.style.display = 'none';
            } else {
                toggleBtn.innerHTML = 'Tampilkan Lebih Banyak (<span id="jobCount">' + (jobRows.length - 5) + '</span>)';
            }
        }

        function toggleApplications() {
            const appRows = document.querySelectorAll('.application-row');
            const toggleBtn = document.getElementById('toggleApplicationBtn');
            const appCount = document.getElementById('appCount');

            showingAllApplications = !showingAllApplications;

            appRows.forEach((row, index) => {
                if (index >= 5) {
                    row.style.display = showingAllApplications ? 'flex' : 'none';
                }
            });

            if (showingAllApplications) {
                toggleBtn.innerHTML = 'Tampilkan Lebih Sedikit';
                appCount.style.display = 'none';
            } else {
                toggleBtn.innerHTML = 'Tampilkan Lebih Banyak (<span id="appCount">' + (appRows.length - 5) + '</span>)';
            }
        }

        function removeFavorite(id, jobTitle) {
            if (confirm(`Apakah Anda yakin ingin menghapus "${jobTitle}" dari favorit?`)) {
                window.location.href = `aktivitas.php?remove_favorite=${id}`;
            }
        }

        function confirmDeleteCV() {
            return confirm('Apakah Anda yakin ingin menghapus CV? CV yang dihapus tidak dapat dikembalikan.');
        }

        // Validasi file sebelum upload
        document.getElementById('cvFile')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const submitBtn = document.querySelector('#cvForm button[type="submit"]');

            if (file) {
                // Validasi ukuran (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file maksimal 5MB');
                    e.target.value = '';
                    submitBtn.disabled = true;
                    return;
                }

                // Validasi tipe file
                if (file.type !== 'application/pdf') {
                    alert('Hanya file PDF yang diperbolehkan');
                    e.target.value = '';
                    submitBtn.disabled = true;
                    return;
                }

                submitBtn.disabled = false;
            }
        });

        // Auto-hide alerts setelah 5 detik
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>