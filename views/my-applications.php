<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

// Cek apakah user sudah login sebagai pencaker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pencaker') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pencaker = getPencakerByUserId($user_id);

// Ambil semua data lamaran
$lamaranResult = getLamaranByPencaker($pencaker['id_pencaker'], 100);
$lamaranData = $lamaranResult['success'] ? $lamaranResult['data'] : [];

// Hitung statistik
$stats = [
    'total' => count($lamaranData),
    'diproses' => 0,
    'diterima' => 0,
    'ditolak' => 0
];

foreach ($lamaranData as $lamaran) {
    if (isset($stats[$lamaran['status']])) {
        $stats[$lamaran['status']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamaran Saya - Karirku</title>
    <link href="../assets/img/favicon.ico" rel="icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        .applications-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .application-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #001f66;
            transition: transform 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-diproses {
            background: #e3f2fd;
            color: #001f66;
        }

        .status-diterima {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-ditolak {
            background: #ffebee;
            color: #c62828;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
                    <a href="logout.php" class="btn-login">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <div class="applications-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2" style="color: #001f66;">Lamaran Saya</h1>
            <span class="badge bg-primary"><?= count($lamaranData) ?> Lamaran</span>
        </div>

        <?php if (empty($lamaranData)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">Belum Ada Lamaran</h3>
                <p class="text-muted">Anda belum mengirim lamaran pekerjaan.</p>
                <a href="job-list.php" class="btn btn-primary">Cari Lowongan</a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($lamaranData as $lamaran): ?>
                    <div class="application-card">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <?php if (!empty($lamaran['lowongan']['perusahaan']['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($lamaran['lowongan']['perusahaan']['logo_url']) ?>"
                                        alt="Logo Perusahaan" class="company-logo">
                                <?php else: ?>
                                    <div class="company-logo bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-building text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-2"><?= htmlspecialchars($lamaran['lowongan']['judul']) ?></h5>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-building me-2"></i>
                                    <?= htmlspecialchars($lamaran['lowongan']['perusahaan']['nama_perusahaan'] ?? 'Perusahaan') ?>
                                </p>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?= htmlspecialchars($lamaran['lowongan']['lokasi']) ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-2"></i>
                                    Dilamar pada: <?= date('d M Y', strtotime($lamaran['tanggal_lamaran'])) ?>
                                </small>
                            </div>

                            <div class="col-md-4 text-end">
                                <?php
                                $statusClass = 'status-diproses';
                                $statusText = 'Diproses';
                                if ($lamaran['status'] === 'diterima') {
                                    $statusClass = 'status-diterima';
                                    $statusText = 'Diterima';
                                } elseif ($lamaran['status'] === 'ditolak') {
                                    $statusClass = 'status-ditolak';
                                    $statusText = 'Ditolak';
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?> mb-2">
                                    <?= $statusText ?>
                                </span>

                                <?php if (!empty($lamaran['catatan_perusahaan'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <strong>Catatan:</strong> <?= htmlspecialchars($lamaran['catatan_perusahaan']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <a href="job-detail.php?id=<?= $lamaran['id_lowongan'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Lihat Lowongan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'include/footer.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>