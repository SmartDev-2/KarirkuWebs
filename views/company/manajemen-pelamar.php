<?php
// manajemen-pelamar.php

// Session start harus di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';
$activePage = 'manajemen-pelamar';

require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek status persetujuan perusahaan
$company = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan, status_persetujuan, nama_perusahaan, logo_url',
    'id_pengguna' => 'eq.' . $user_id
]);



// Jika tidak ada data perusahaan, redirect ke edit_company
if (count($company['data']) === 0) {
    exit;
}



// Ambil data perusahaan untuk ditampilkan
$companyData = $company['data'][0];
$id_perusahaan = $companyData['id_perusahaan'] ?? null;
$nama_perusahaan = $companyData['nama_perusahaan'] ?? 'Perusahaan';
$logo_url = $companyData['logo_url'] ?? '';
$status_persetujuan = $companyData['status_persetujuan'] ?? null;

// Jika status menunggu, redirect ke waiting_approval
if ($status_persetujuan === 'menunggu') {
    header('Location: waiting_approval.php');
    exit;
}

// Jika tidak ada id_perusahaan, redirect ke edit_company
if (!$id_perusahaan) {
    header('Location: edit_company.php');
    exit;
}

// Ambil status dari URL parameter
$status = $_GET['status'] ?? 'diproses';

// Validasi status
$validStatuses = ['diproses', 'diterima', 'ditolak', 'selesai'];
if (!in_array($status, $validStatuses)) {
    $status = 'diproses';
}

// Cek dulu apakah ada lowongan untuk perusahaan ini
$lowonganCheck = supabaseQuery('lowongan', [
    'select' => 'id_lowongan, judul, status',
    'id_perusahaan' => 'eq.' . $id_perusahaan
]);

if ($lowonganCheck['success'] && count($lowonganCheck['data']) > 0) {

    // Hitung jumlah pelamar per status
    $counts = [];
    foreach (['diproses', 'diterima', 'ditolak'] as $s) {
        $result = getJumlahPelamarByStatus($id_perusahaan, $s);
        $counts[$s] = $result;
    }
    $counts['selesai'] = $counts['diterima'] + $counts['ditolak'];
} else {
    $counts = ['diproses' => 0, 'diterima' => 0, 'ditolak' => 0, 'selesai' => 0];
}

// Ambil data pelamar berdasarkan status
if ($status === 'selesai') {
    // Hitung tanggal 7 hari yang lalu
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

    // Query untuk mendapatkan pelamar dengan status diterima/ditolak dalam 7 hari terakhir
    $pelamarResult = getPelamarByStatus($id_perusahaan, null, 100);

    // Filter manual untuk diterima, ditolak, dan dalam 7 hari terakhir
    if ($pelamarResult['success'] && isset($pelamarResult['data'])) {
        $allApplicants = array_filter($pelamarResult['data'], function ($applicant) use ($sevenDaysAgo) {
            $isCompleted = in_array($applicant['status'] ?? '', ['diterima', 'ditolak']);
            $isWithin7Days = false;

            // Cek jika tanggal lamaran dalam 7 hari terakhir
            if (isset($applicant['tanggal_lamaran']) && $applicant['tanggal_lamaran']) {
                $applyDate = date('Y-m-d', strtotime($applicant['tanggal_lamaran']));
                $isWithin7Days = ($applyDate >= $sevenDaysAgo);
            }

            return $isCompleted && $isWithin7Days;
        });

        // Reset array keys
        $allApplicants = array_values($allApplicants);
    } else {
        $allApplicants = [];
    }
} else {
    $pelamarResult = getPelamarByStatus($id_perusahaan, $status, 100);

    if ($pelamarResult['success'] && isset($pelamarResult['data'])) {
        $allApplicants = $pelamarResult['data'];
    } else {
        $allApplicants = [];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelamar - Karirku</title>
    <link href="../../assets/img/karirkulogo.ico" rel="icon">
    <link rel="stylesheet" href="../../assets/css/company/company.css">
    <link rel="stylesheet" href="../../assets/css/company/lowongan.css">
    <link rel="stylesheet" href="../../assets/css/company/laporan.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        .user-dropdown.dropdown-toggle::after {
            display: none !important;
        }

        .text-tab .active {
            color: #002E92 !important;
        }

        .search-box input {
            border-radius: 12px !important;
        }

        .back-button {
            background: none;
            border: none;
            color: #002E92;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            margin-bottom: 16px;
        }

        .back-button:hover {
            color: #001a66;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #002E92;
            margin-bottom: 24px;
        }

        .text-tabs {
            display: flex;
            gap: 32px;
            margin-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-tab {
            padding: 12px 0;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            position: relative;
            transition: color 0.2s;
        }

        .text-tab.active {
            color: #002E92;
        }

        .text-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #002E92;
        }

        .text-tab-count {
            background: #e5e7eb;
            color: #374151;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 8px;
        }

        .text-tab.active .text-tab-count {
            background: #002E92;
            color: white;
        }

        /* Table Styles */
        .applicants-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .applicants-table {
            width: 100%;
            border-collapse: collapse;
        }

        .applicants-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .applicants-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #6b7280;
            font-size: 14px;
        }

        .applicants-table tr:hover {
            background: #f9fafb;
        }

        .applicants-table tr:last-child td {
            border-bottom: none;
        }

        .applicant-name-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .applicant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #002E92;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }

        .applicant-name {
            font-weight: 600;
            color: #111827;
        }

        .applicant-job {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-diproses {
            background: #eff6ff;
            color: #3b82f6;
        }

        .status-diterima {
            background: #ecfdf5;
            color: #10b981;
        }

        .status-ditolak {
            background: #fef2f2;
            color: #ef4444;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-detail {
            background: #002E92;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }

        .btn-detail:hover {
            background: #001a66;
        }

        .btn-cv {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-cv:hover {
            background: #0da271;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .empty-state svg {
            margin-bottom: 16px;
            color: #d1d5db;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            margin: 0;
            color: #002E92;
            font-size: 20px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }

        .close-modal:hover {
            color: #374151;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-success:hover {
            background: #0da271;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background: #374151;
        }

        @media (max-width: 768px) {
            .applicants-table-container {
                overflow-x: auto;
            }

            .applicants-table {
                min-width: 800px;
            }

            .action-buttons {
                flex-direction: column;
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
                <a href="index.php"><button class="back-button">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </button></a>

                <h1 class="page-title">Pelamar Saya</h1>

                <!-- Tabs -->
                <div class="text-tabs">
                    <span class="text-tab <?php echo $status === 'diproses' ? 'active' : ''; ?>" onclick="changeStatus('diproses')">
                        Diproses <span class="text-tab-count"><?php echo $counts['diproses']; ?></span>
                    </span>
                    <span class="text-tab <?php echo $status === 'diterima' ? 'active' : ''; ?>" onclick="changeStatus('diterima')">
                        Diterima <span class="text-tab-count"><?php echo $counts['diterima']; ?></span>
                    </span>
                    <span class="text-tab <?php echo $status === 'ditolak' ? 'active' : ''; ?>" onclick="changeStatus('ditolak')">
                        Ditolak <span class="text-tab-count"><?php echo $counts['ditolak']; ?></span>
                    </span>
                    <span class="text-tab <?php echo $status === 'selesai' ? 'active' : ''; ?>" onclick="changeStatus('selesai')">
                        Selesai <span class="text-tab-count"><?php echo $counts['selesai']; ?></span>
                    </span>
                </div>

                <!-- Filters -->
                <div class="report-filters">
                    <div class="report-search">
                        <div class="search-box">
                            <svg class="search-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" placeholder="Cari nama pelamar atau lowongan" id="searchInput" oninput="filterApplicants()">
                        </div>
                    </div>
                    <select class="date-range-select" id="dateRangeSelect" onchange="filterApplicants()">
                        <option value="">Semua waktu</option>
                        <option value="7">7 hari terakhir</option>
                        <option value="30">30 hari terakhir</option>
                        <option value="90">90 hari terakhir</option>
                    </select>
                </div>

                <!-- Applicants Table -->
                <div class="applicants-table-container">
                    <table class="applicants-table">
                        <thead>
                            <tr>
                                <th>Nama Pelamar</th>
                                <th>Lowongan</th>
                                <th>Email</th>
                                <th>No HP</th>
                                <th>Tanggal Lamar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="applicantsContent">
                            <!-- Content will be rendered by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Applicant Detail Modal -->
    <div id="applicantDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Detail Pelamar</h3>
                <button class="close-modal" onclick="closeApplicantModal()">&times;</button>
            </div>
            <div id="applicantDetailContent">
                <!-- Detail content will be populated by JavaScript -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <!-- Footer buttons will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data dari PHP - PASTIKAN menggunakan json_encode dengan JSON_HEX_TAG
        const applicantData = <?php echo json_encode($allApplicants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || [];
        let currentStatus = '<?php echo $status; ?>';
        let filteredData = applicantData;
        let currentApplicantId = null;

        console.log("Applicant Data:", applicantData); // Debug di console browser
        console.log("Current Status:", currentStatus);
        console.log("Data length:", applicantData.length);

        // Fungsi untuk change status
        function changeStatus(status) {
            window.location.href = `manajemen-pelamar.php?status=${status}`;
        }

        // Fungsi untuk filter applicants
        function filterApplicants() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const dateRange = document.getElementById('dateRangeSelect').value;

            let data = applicantData;

            // Apply search filter
            if (searchTerm) {
                data = data.filter(item => {
                    const nama = item.nama_pelamar || '';
                    const judul = item.judul_lowongan || '';
                    return nama.toLowerCase().includes(searchTerm) ||
                        judul.toLowerCase().includes(searchTerm);
                });
            }

            // Apply date range filter
            if (dateRange) {
                const days = parseInt(dateRange);
                const cutoffDate = new Date();
                cutoffDate.setDate(cutoffDate.getDate() - days);

                data = data.filter(item => {
                    if (!item.tanggal_lamaran) return true;
                    const itemDate = new Date(item.tanggal_lamaran);
                    return itemDate >= cutoffDate;
                });
            }

            filteredData = data;
            renderApplicants();
        }

        // Fungsi untuk render applicants dalam tabel
        function renderApplicants() {
            const applicantsContent = document.getElementById('applicantsContent');

            console.log("Rendering applicants...");
            console.log("Filtered data:", filteredData);

            if (!applicantsContent) {
                console.error('Element dengan id "applicantsContent" tidak ditemukan');
                return;
            }

            if (!filteredData || filteredData.length === 0) {
                applicantsContent.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="empty-state">
                            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3>Tidak ada pelamar</h3>
                            <p>Tidak ada pelamar dengan status ${currentStatus} untuk saat ini</p>
                        </div>
                    </td>
                </tr>
            `;
                return;
            }

            let html = '';

            filteredData.forEach(applicant => {
                // Format tanggal
                let applyDate = 'Tanggal tidak tersedia';
                try {
                    if (applicant.tanggal_lamaran) {
                        const date = new Date(applicant.tanggal_lamaran);
                        applyDate = date.toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                    }
                } catch (e) {
                    console.error("Error formatting date:", e);
                }

                // Status badge
                const statusClass = `status-${applicant.status}`;
                const statusLabel = applicant.status === 'diproses' ? 'Diproses' :
                    applicant.status === 'diterima' ? 'Diterima' : 'Ditolak';

                // Initial untuk avatar
                const initial = applicant.nama_pelamar ? applicant.nama_pelamar.charAt(0).toUpperCase() : 'P';

                // Escape HTML untuk mencegah XSS
                const escapeHtml = (unsafe) => {
                    if (!unsafe) return '';
                    return unsafe
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                };

                html += `
                <tr>
                    <td>
                        <div class="applicant-name-cell">
                            <div class="applicant-avatar">
                                ${initial}
                            </div>
                            <div>
                                <div class="applicant-name">${escapeHtml(applicant.nama_pelamar || 'Nama tidak tersedia')}</div>
                                <div class="applicant-job">${escapeHtml(applicant.judul_lowongan || 'Lowongan tidak tersedia')}</div>
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(applicant.judul_lowongan || 'Lowongan tidak tersedia')}</td>
                    <td>${escapeHtml(applicant.email_pelamar || 'Email tidak tersedia')}</td>
                    <td>${escapeHtml(applicant.no_hp_pelamar || '-')}</td>
                    <td>${applyDate}</td>
                    <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                    <td>
                        <div class="action-buttons">
                            ${applicant.cv_url ? `
                                <a href="${escapeHtml(applicant.cv_url)}" target="_blank" class="btn-cv">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    CV
                                </a>
                            ` : ''}
                            <button class="btn-detail" onclick="viewApplicantDetail(${applicant.id_lamaran})">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Detail
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            });

            applicantsContent.innerHTML = html;
            console.log("Table rendered with", filteredData.length, "rows");
        }

        // Pindahkan fungsi escapeHtml ke global scope
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initial render
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM Content Loaded");
            renderApplicants();
        });
    </script>
</body>

</html>