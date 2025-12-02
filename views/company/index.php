<?php
$activePage = 'halaman-utama'; // Set halaman aktif

session_start();
require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek status persetujuan perusahaan
$company = supabaseQuery('perusahaan', [
    'select' => 'status_persetujuan, nama_perusahaan, logo_url, id_perusahaan',
    'id_pengguna' => 'eq.' . $user_id
]);

// Jika status menunggu, redirect ke waiting_approval
if ($company['success'] && count($company['data']) > 0 && $company['data'][0]['status_persetujuan'] === 'menunggu') {
    header('Location: waiting_approval.php');
    exit;
}

// Jika belum ada data perusahaan, redirect ke edit_company
if ($company['success'] && count($company['data']) === 0) {
    header('Location: edit_company.php');
    exit;
}

// data perusahaan untuk ditampilkan
$nama_perusahaan = $company['data'][0]['nama_perusahaan'] ?? 'Perusahaan';
$logo_url = $company['data'][0]['logo_url'] ?? '';
$id_perusahaan = $company['data'][0]['id_perusahaan'] ?? '';

$totalPelamar = 0;
$lowonganDisukai = 0;
$totalPengunjung = 0;

// Query untuk mendapatkan statistik
if ($id_perusahaan) {
    // Hitung total pelamar dari semua lowongan
    $aplikasi = supabaseQuery('aplikasi', [
        'select' => 'id_aplikasi',
        'id_perusahaan' => 'eq.' . $id_perusahaan
    ]);
    $totalPelamar = $aplikasi['success'] ? count($aplikasi['data']) : 0;

    // Hitung jumlah bookmark lowongan
    $lowongan = supabaseQuery('lowongan', [
        'select' => 'id_lowongan',
        'id_perusahaan' => 'eq.' . $id_perusahaan
    ]);

    if ($lowongan['success'] && count($lowongan['data']) > 0) {
        foreach ($lowongan['data'] as $job) {
            $bookmark = supabaseQuery('bookmark', [
                'select' => 'id_bookmark',
                'id_lowongan' => 'eq.' . $job['id_lowongan']
            ]);
            $lowonganDisukai += $bookmark['success'] ? count($bookmark['data']) : 0;
        }
    }

    // Total pengunjung (simulasi - bisa diambil dari tabel kunjungan jika ada)
    $totalPengunjung = rand(800, 1200); // Placeholder
}

// Hitung kesehatan perusahaan (0-100)
$maxScore = 100;
$pelamarScore = min(($totalPelamar / 50) * 40, 40); // Max 40 poin
$likeScore = min(($lowonganDisukai / 100) * 30, 30); // Max 30 poin
$visitScore = min(($totalPengunjung / 1000) * 30, 30); // Max 30 poin
$kesehatanScore = round($pelamarScore + $likeScore + $visitScore);

// Tentukan kategori kesehatan
if ($kesehatanScore >= 80) {
    $kesehatanLabel = "Sangat baik";
    $kesehatanColor = "#10b981";
} elseif ($kesehatanScore >= 60) {
    $kesehatanLabel = "Baik";
    $kesehatanColor = "#3b82f6";
} elseif ($kesehatanScore >= 40) {
    $kesehatanLabel = "Cukup";
    $kesehatanColor = "#f59e0b";
} else {
    $kesehatanLabel = "Perlu perbaikan";
    $kesehatanColor = "#ef4444";
}

// Hitung masalah yang perlu diselesaikan (lowongan ditolak dalam 2 minggu terakhir)
$masalahCount = 0;
if ($id_perusahaan) {
    // Hitung tanggal 2 minggu yang lalu
    $duaMingguLalu = date('Y-m-d H:i:s', strtotime('-2 weeks'));
    
    $lowonganBermasalah = supabaseQuery('lowongan', [
        'select' => 'id_lowongan, status, dibuat_pada, judul',
        'id_perusahaan' => 'eq.' . $id_perusahaan,
        'status' => 'eq.ditolak',
        'dibuat_pada' => 'gt.' . $duaMingguLalu 
    ]);
    
    $masalahCount = $lowonganBermasalah['success'] ? count($lowonganBermasalah['data']) : 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karirku</title>
    <link rel="stylesheet" href="../../assets/css/company/company.css">
    <link href="../../assets/img/karirkulogo.ico" rel="icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        .content {
            background-color: #f6f6f6;
            font-family: 'inter', sans-serif;
            font-style: normal;
        }

        .action-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(221px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 4px rgba(0, 46, 146, 1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
            width: 221px;
            height: 135px;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #003399;
        }

        .action-card-number {
            font-size: 22px;
            font-weight: 600;
            color: #224BA4;
            margin-bottom: 8px;
            margin-top: 20px;
            text-align: center;
        }

        .action-card-label {
            font-size: 16px;
            color: #111827;
            font-weight: 400;
            text-align: center;
        }

        .performa-card {
            background: white;
            padding: 24px;
            border-radius: 19px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .performa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        h3 {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            color: #224BA4;
        }

        .performa-period {
            font-size: 13px;
            color: #6b7280;
        }

        .performa-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .performa-stat-item {
            text-align: center;
        }

        .performa-stat-label {
            font-size: 16px;
            color: #111827;
            margin-bottom: 8px;
        }

        .performa-stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #224BA4;
        }

        .performa-stat-value.health {
            color: #10b981;
        }

        .bottom-cards-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .masalah-card {
            background: white;
            padding: 24px;
            border-radius: 19px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .masalah-header {
            font-weight: 600;
            margin-bottom: 12px;
        }

        .masalah-subtext {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .masalah-count-container {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .masalah-count-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .masalah-count {
            font-size: 32px;
            font-weight: 700;
            color: #224BA4;
        }

        .masalah-info {
            margin-top: 16px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
        }

        .peringatan-card {
            background: white;
            padding: 24px;
            border-radius: 19px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .peringatan-header {
            font-weight: 600;
            margin-bottom: 16px;
        }

        .chat-bubble {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 12px;
            padding: 16px;
            position: relative;
            margin-bottom: 12px;
        }

        .chat-bubble-icon {
            position: absolute;
            top: -8px;
            left: 16px;
            background: #3b82f6;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .chat-bubble-content {
            margin-top: 16px;
            font-size: 14px;
            color: #1f2937;
            line-height: 1.6;
        }

        .chat-bubble-list {
            list-style: none;
            padding: 0;
            margin: 8px 0 0 0;
        }

        .chat-bubble-list li {
            padding: 4px 0;
            color: #374151;
        }

        .chat-bubble-list li:before {
            content: "•";
            color: #3b82f6;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
            padding-right: 8px;
        }

        @media (max-width: 768px) {
            .bottom-cards-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php include "topbar_company.php" ?>

            <!-- Content -->
            <div class="content">

                <!-- Yang Perlu dilakukan Section -->
                <div style="background: white; padding: 24px; border-radius: 19px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
                    <h3 style="margin-bottom: 16px;">Yang Perlu dilakukan</h3>

                    <div class="action-cards-container">
                        <?php
                        // Hitung jumlah pelamar berdasarkan status
                        $pelamarDiproses = 0;
                        $pelamarDiterima = 0;
                        $pelamarDitolak = 0;

                        if ($id_perusahaan) {
                            // Ambil semua lowongan perusahaan
                            $lowongan = supabaseQuery('lowongan', [
                                'select' => 'id_lowongan',
                                'id_perusahaan' => 'eq.' . $id_perusahaan
                            ]);

                            if ($lowongan['success'] && count($lowongan['data']) > 0) {
                                $id_lowongan_array = array_column($lowongan['data'], 'id_lowongan');

                                // Hitung pelamar dengan status 'diproses'
                                $resultDiproses = supabaseQuery('lamaran', [
                                    'select' => 'id_lamaran',
                                    'id_lowongan' => 'in.(' . implode(',', $id_lowongan_array) . ')',
                                    'status' => 'eq.diproses'
                                ]);
                                $pelamarDiproses = $resultDiproses['success'] ? count($resultDiproses['data']) : 0;

                                // Hitung pelamar dengan status 'diterima'
                                $resultDiterima = supabaseQuery('lamaran', [
                                    'select' => 'id_lamaran',
                                    'id_lowongan' => 'in.(' . implode(',', $id_lowongan_array) . ')',
                                    'status' => 'eq.diterima'
                                ]);
                                $pelamarDiterima = $resultDiterima['success'] ? count($resultDiterima['data']) : 0;

                                // Hitung pelamar dengan status 'ditolak'
                                $resultDitolak = supabaseQuery('lamaran', [
                                    'select' => 'id_lamaran',
                                    'id_lowongan' => 'in.(' . implode(',', $id_lowongan_array) . ')',
                                    'status' => 'eq.ditolak'
                                ]);
                                $pelamarDitolak = $resultDitolak['success'] ? count($resultDitolak['data']) : 0;
                            }
                        }
                        ?>

                        <a href="manajemen-pelamar.php?status=diproses" class="action-card">
                            <div class="action-card-number"><?php echo $pelamarDiproses; ?></div>
                            <div class="action-card-label">Pelamar Perlu Diproses</div>
                        </a>

                        <a href="manajemen-pelamar.php?status=diterima" class="action-card">
                            <div class="action-card-number"><?php echo $pelamarDiterima; ?></div>
                            <div class="action-card-label">Pelamar Telah Diterima</div>
                        </a>

                        <a href="manajemen-pelamar.php?status=ditolak" class="action-card">
                            <div class="action-card-number"><?php echo $pelamarDitolak; ?></div>
                            <div class="action-card-label">Pelamar Telah Ditolak</div>
                        </a>

                        <a href="manajemen-pelamar.php" class="action-card">
                            <div class="action-card-number">0</div>
                            <div class="action-card-label">Pelamar Lanjutan</div>
                        </a>
                    </div>
                </div>

                <!-- Performa Perusahaan Section -->
                <div class="performa-card">
                    <div class="performa-header">
                        <h3>Performa Perusahaan</h3>
                        <span class="performa-period">~ Per hari ini</span>
                    </div>

                    <div class="performa-stats">
                        <div class="performa-stat-item">
                            <div class="performa-stat-label">Total Pelamar</div>
                            <div class="performa-stat-value"><?php echo $totalPelamar; ?></div>
                        </div>

                        <div class="performa-stat-item">
                            <div class="performa-stat-label">Jumlah Lowongan disukai</div>
                            <div class="performa-stat-value"><?php echo $lowonganDisukai; ?></div>
                        </div>

                        <div class="performa-stat-item">
                            <div class="performa-stat-label">Total Pengunjung Perusahaan</div>
                            <div class="performa-stat-value"><?php echo $totalPengunjung; ?></div>
                        </div>

                        <div class="performa-stat-item">
                            <div class="performa-stat-label">Kesehatan Perusahaan</div>
                            <div class="performa-stat-value health" style="color: <?php echo $kesehatanColor; ?>">
                                <?php echo $kesehatanLabel; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Cards: Masalah & Peringatan -->
                <div class="bottom-cards-container">
                    <!-- Masalah Perlu diselesaikan -->
                    <div class="masalah-card">
                        <h3 class="masalah-header">Masalah Perlu diselesaikan</h3>
                            <p class="masalah-subtext">Lowongan yang ditolak dalam 2 minggu terakhir</p>

                        <div class="masalah-count-container">
                            <div class="masalah-count-label">Lowongan bermasalah</div>
                            <div class="masalah-count"><?php echo $masalahCount; ?></div>
                        </div>

                        <div class="masalah-info">
                            Melanggar kebijakan lowongan dapat menghasilkan poin penalti. Dalam 6 kali Lowongan ditolak KarirKu dapat menyebabkan akun anda diblokir sementara.
                        </div>
                    </div>

                    <!-- Peringatan Akun -->
                    <div class="peringatan-card">
                        <h3 class="peringatan-header">Peringatan Akun</h3>

                        <div class="chat-bubble">
                            <div class="chat-bubble-icon">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="chat-bubble-content">
                                <strong>Isi lowongan anda bermasalah</strong>
                                <ul class="chat-bubble-list">
                                    <li>Lowongan tidak boleh mengandung kata kata diskriminatif</li>
                                    <li>Lowongan tidak boleh mengandung konten sensitif</li>
                                    <li>Pastikan lowongan sesuai dengan kebijakan KarirKu</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }

        // Pastikan nav collapse dalam state yang benar saat load
        document.addEventListener('DOMContentLoaded', function() {
            const collapse = document.getElementById('lowonganCollapse');
            const icon = document.getElementById('collapseIcon');

            // Jika halaman aktif adalah lowongan, expand menu
            if (collapse && collapse.classList.contains('expanded')) {
                const items = collapse.querySelectorAll('.nav-collapse-item');
                const itemHeight = 36;
                const totalHeight = items.length * itemHeight;
                collapse.style.maxHeight = totalHeight + 'px';
            }
        });
    </script>
</body>

</html>