<?php
// laporan.php
include 'config.php';
$activePage = 'laporan';

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

// Ambil data perusahaan untuk ditampilkan
$id_perusahaan = $company['data'][0]['id_perusahaan'] ?? null;
$nama_perusahaan = $company['data'][0]['nama_perusahaan'] ?? 'Perusahaan';
$logo_url = $company['data'][0]['logo_url'] ?? '';

// Jika tidak ada id_perusahaan, redirect ke edit_company
if (!$id_perusahaan) {
    header('Location: edit_company.php');
    exit;
}

// Inisialisasi array untuk data
$expiredJobs = [];
$allApplicants = [];

// Ambil data lowongan perusahaan
$lowonganResult = supabaseQuery('lowongan', [
    'select' => '*',
    'id_perusahaan' => 'eq.' . $id_perusahaan,
    'order' => 'dibuat_pada.desc'
]);

// Proses data lowongan expired
if ($lowonganResult['success']) {
    foreach ($lowonganResult['data'] as $job) {
        // Cek apakah lowongan expired
        $isExpired = false;
        if (!empty($job['batas_tanggal'])) {
            $deadline = new DateTime($job['batas_tanggal']);
            $today = new DateTime();
            if ($deadline < $today && $job['status'] === 'publish') {
                $isExpired = true;
            }
        }

        if ($isExpired) {
            // Format tanggal expired
            $expiredDate = date('d M Y', strtotime($job['batas_tanggal']));

            // Hitung jumlah pelamar dari database
            $applicantResult = supabaseQuery('lamaran', [
                'select' => 'id_lamaran',
                'id_lowongan' => 'eq.' . $job['id_lowongan']
            ]);
            $applicantCount = $applicantResult['success'] ? count($applicantResult['data']) : 0;

            $expiredJobs[] = [
                'id' => $job['id_lowongan'],
                'title' => $job['judul'],
                'company' => $nama_perusahaan,
                'salary' => $job['gaji_range'] ?: 'Gaji tidak ditampilkan',
                'location' => $job['lokasi'] ?? '',
                'type' => $job['tipe_pekerjaan'] ?? '',
                'expired_date' => $expiredDate,
                'applicants' => $applicantCount,
                'posted_date' => date('d M Y', strtotime($job['dibuat_pada']))
            ];
        }
    }
}

// AMBIL DATA PELAMAR SELESAI (DITERIMA/DITOLAK) - MENGGUNAKAN FUNGSI getPelamarSelesai
if ($id_perusahaan) {
    $result = getPelamarSelesai($id_perusahaan, 1000);
    
    if ($result['success']) {
        foreach ($result['data'] as $lamaran) {
            // Pastikan nama pelamar ada
            $namaPelamar = $lamaran['nama_pelamar'] ?? 'Nama tidak tersedia';
            
            // Jika nama masih "Nama tidak tersedia", coba ambil dari pencaker langsung
            if ($namaPelamar === 'Nama tidak tersedia' && isset($lamaran['pencaker'])) {
                $pencaker = $lamaran['pencaker'];
                if (is_array($pencaker)) {
                    $namaPelamar = $pencaker['nama_lengkap'] ?? 'Nama tidak tersedia';
                }
            }
            
            // Format tanggal lamaran
            $applyDate = date('d M Y', strtotime($lamaran['tanggal_lamaran'] ?? $lamaran['dibuat_pada'] ?? 'now'));
            
            // Tentukan status dan styling
            $statusLamaran = $lamaran['status'] ?? '';
            $statusClass = '';
            $statusLabel = '';
            
            switch ($statusLamaran) {
                case 'diterima':
                    $statusClass = 'status-accepted';
                    $statusLabel = 'Diterima';
                    break;
                case 'ditolak':
                    $statusClass = 'status-rejected';
                    $statusLabel = 'Ditolak';
                    break;
                default:
                    $statusClass = 'status-pending';
                    $statusLabel = 'Tidak Diketahui';
            }
            
            $allApplicants[] = [
                'id' => $lamaran['id_lamaran'],
                'name' => $namaPelamar,
                'email' => $lamaran['email_pelamar'] ?? '',
                'phone' => $lamaran['no_hp_pelamar'] ?? '',
                'job_title' => $lamaran['judul_lowongan'] ?? 'Lowongan tidak ditemukan',
                'job_id' => $lamaran['id_lowongan'],
                'apply_date' => $applyDate,
                'status' => $statusLamaran,
                'status_class' => $statusClass,
                'status_label' => $statusLabel,
                'cv_url' => $lamaran['cv_url'] ?? '',
                'cover_letter' => $lamaran['catatan_pelamar'] ?? ''
            ];
        }
    }
}

// Data untuk JSON
$laporanDataJson = json_encode([
    'expired' => $expiredJobs,
    'applicants' => $allApplicants
]);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Karirku</title>
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
        
        /* Kembalikan ke desain awal - Filter */
        .report-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }
        
        .report-search {
            flex-grow: 1;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #6b7280;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .date-range-select {
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            min-width: 150px;
            height: 40px;
        }
        
        /* Tabel Styles - Desain Awal */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .report-table th {
            background-color: #f8f9fa;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .report-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .report-table tr:hover {
            background-color: #f9fafb;
        }
        
        .job-title-cell {
            font-weight: 600;
            color: #111827;
        }
        
        .job-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .expired-badge {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }
        
        .applicants-count {
            color: #10b981;
            font-weight: 600;
        }
        
        .applicant-info-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .applicant-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #002E92;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .applicant-details {
            display: flex;
            flex-direction: column;
        }
        
        .applicant-name {
            font-weight: 600;
            color: #111827;
        }
        
        .applicant-email {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-accepted {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .report-action-cell {
            display: flex;
            gap: 8px;
        }
        
        .btn-view {
            background-color: #002E92;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-view:hover {
            background-color: #001a66;
        }
        
        .btn-download {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        
        .btn-download:hover {
            background-color: #0da271;
        }
        
        .empty-state-report {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state-report svg {
            width: 64px;
            height: 64px;
            color: #d1d5db;
            margin-bottom: 16px;
        }
        
        .empty-state-report h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .empty-state-report p {
            font-size: 14px;
            color: #6b7280;
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
        
        .form-actions {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-cancel:hover {
            background: #374151;
        }
        
        /* Content Styles */
        .content-header {
            margin-bottom: 24px;
        }
        
        .text-tabs {
            display: flex;
            gap: 32px;
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
        
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
            min-height: 400px;
        }
        
        @media (max-width: 768px) {
            .report-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-range-select {
                width: 100%;
            }
            
            .report-table {
                font-size: 12px;
            }
            
            .report-table th,
            .report-table td {
                padding: 8px 12px;
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
                <div class="content-header">
                    <div class="text-tabs">
                        <span class="text-tab active" onclick="changeTab('expired')">Lowongan expired</span>
                        <span class="text-tab" onclick="changeTab('applicants')">Pelamar</span>
                    </div>
                </div>
                <!-- Filters - Desain Awal -->
                <div class="report-filters">
                    <div class="report-search">
                        <div class="search-box">
                            <svg class="search-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" placeholder="Cari" id="searchInput" oninput="filterReports()">
                        </div>
                    </div>
                    <select class="date-range-select" id="dateRangeSelect" onchange="filterReports()">
                        <option value="">Rentang waktu</option>
                        <option value="7">7 hari terakhir</option>
                        <option value="30">30 hari terakhir</option>
                        <option value="90">90 hari terakhir</option>
                    </select>
                </div>
                <div class="tabs-container">
                    <!-- Report Card -->
                    <div class="report-card">
                        <div id="reportContent">
                            <!-- Content akan di-render oleh JavaScript -->
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
        // Data dari PHP
        const reportData = <?php echo $laporanDataJson; ?>;
        let currentTab = 'expired';
        let filteredData = reportData.expired;

        console.log('Data Expired:', reportData.expired);
        console.log('Data Applicants:', reportData.applicants);

        // Fungsi untuk change tab
        function changeTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.text-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            filterReports();
        }

        // Fungsi untuk filter reports
        function filterReports() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const dateRange = document.getElementById('dateRangeSelect').value;

            let data = currentTab === 'expired' ? reportData.expired : reportData.applicants;

            // Apply search filter
            if (searchTerm) {
                if (currentTab === 'expired') {
                    data = data.filter(item =>
                        (item.title && item.title.toLowerCase().includes(searchTerm)) ||
                        (item.company && item.company.toLowerCase().includes(searchTerm)) ||
                        (item.location && item.location.toLowerCase().includes(searchTerm))
                    );
                } else {
                    // Untuk tab pelamar, cari berdasarkan nama, email, atau lowongan
                    data = data.filter(item =>
                        (item.name && item.name.toLowerCase().includes(searchTerm)) ||
                        (item.email && item.email.toLowerCase().includes(searchTerm)) ||
                        (item.job_title && item.job_title.toLowerCase().includes(searchTerm))
                    );
                }
            }

            // Apply date range filter HANYA untuk lowongan expired
            if (dateRange && currentTab === 'expired') {
                const days = parseInt(dateRange);
                const cutoffDate = new Date();
                cutoffDate.setDate(cutoffDate.getDate() - days);

                data = data.filter(item => {
                    if (item.expired_date) {
                        // Convert date from "d M Y" format to Date object
                        const dateParts = item.expired_date.split(' ');
                        const months = {
                            'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                            'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
                        };
                        const itemDate = new Date(dateParts[2], months[dateParts[1]], dateParts[0]);
                        return itemDate >= cutoffDate;
                    }
                    return false;
                });
            }
            // Tab pelamar TIDAK DIBERI FILTER RENTANG WAKTU

            filteredData = data;
            renderReport();
        }

        // Fungsi untuk render report
        function renderReport() {
            const reportContent = document.getElementById('reportContent');

            if (currentTab === 'expired') {
                renderExpiredJobs(reportContent);
            } else {
                renderApplicants(reportContent);
            }
        }

        // Render expired jobs
        function renderExpiredJobs(container) {
            if (!filteredData || filteredData.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-report">
                        <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3>Belum ada lowongan expired</h3>
                        <p>Lowongan yang telah melewati batas tanggal akan muncul di sini</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Lowongan</th>
                            <th>Gaji</th>
                            <th>Lokasi</th>
                            <th>Tanggal Expired</th>
                            <th>Jumlah Pelamar</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            filteredData.forEach(job => {
                html += `
                    <tr>
                        <td>
                            <div class="job-title-cell">${escapeHtml(job.title)}</div>
                            <div class="job-subtitle">${escapeHtml(job.company)}</div>
                        </td>
                        <td>${escapeHtml(job.salary)}</td>
                        <td>${escapeHtml(job.location || '-')}</td>
                        <td>
                            <span class="expired-badge">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ${escapeHtml(job.expired_date)}
                            </span>
                        </td>
                        <td>
                            <span class="applicants-count">${job.applicants} pelamar</span>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // Render applicants
        function renderApplicants(container) {
            if (!filteredData || filteredData.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-report">
                        <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3>Belum ada pelamar</h3>
                        <p>Data pelamar yang sudah selesai (diterima/ditolak) akan muncul di sini</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Nama Pelamar</th>
                            <th>Lowongan</th>
                            <th>Tanggal Melamar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            filteredData.forEach(applicant => {
                const initial = applicant.name ? applicant.name.charAt(0).toUpperCase() : '?';
                html += `
                    <tr>
                        <td>
                            <div class="applicant-info-cell">
                                <div class="applicant-avatar">
                                    ${initial}
                                </div>
                                <div class="applicant-details">
                                    <div class="applicant-name">${escapeHtml(applicant.name)}</div>
                                    ${applicant.email ? `<div class="applicant-email">${escapeHtml(applicant.email)}</div>` : ''}
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="job-title-cell">${escapeHtml(applicant.job_title)}</div>
                        </td>
                        <td>${escapeHtml(applicant.apply_date)}</td>
                        <td>
                            <span class="status-badge ${applicant.status_class}">${escapeHtml(applicant.status_label)}</span>
                        </td>
                        <td>
                            <div class="report-action-cell">
                                <button class="btn-view" onclick="viewApplicantDetail(${applicant.id})">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </button>
                                ${applicant.cv_url ? `
                                    <a href="${escapeHtml(applicant.cv_url)}" target="_blank" class="btn-download">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        CV
                                    </a>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // Fungsi untuk escape HTML
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Fungsi untuk melihat detail pelamar
        function viewApplicantDetail(applicantId) {
            const applicant = reportData.applicants.find(a => a.id === applicantId);
            if (!applicant) {
                alert('Data pelamar tidak ditemukan');
                return;
            }

            // Buat modal detail
            const modalHtml = `
                <div id="applicantDetailModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">Detail Pelamar</h3>
                            <button class="close-modal" onclick="closeApplicantModal()">&times;</button>
                        </div>
                        <div style="padding: 20px;">
                            <div style="margin-bottom: 20px;">
                                <div class="applicant-info-cell" style="margin-bottom: 16px;">
                                    <div class="applicant-avatar" style="width: 60px; height: 60px; font-size: 24px;">
                                        ${applicant.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="applicant-details">
                                        <div class="applicant-name" style="font-size: 18px; margin-bottom: 4px;">${escapeHtml(applicant.name)}</div>
                                        ${applicant.email ? `<div class="applicant-email">${escapeHtml(applicant.email)}</div>` : ''}
                                        ${applicant.phone ? `<div class="applicant-email">${escapeHtml(applicant.phone)}</div>` : ''}
                                    </div>
                                </div>
                            </div>

                            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Lowongan yang Dilamar:</strong>
                                    <p style="color: #6b7280; margin: 0;">${escapeHtml(applicant.job_title)}</p>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Tanggal Melamar:</strong>
                                    <p style="color: #6b7280; margin: 0;">${escapeHtml(applicant.apply_date)}</p>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Status:</strong>
                                    <span class="status-badge ${applicant.status_class}">${escapeHtml(applicant.status_label)}</span>
                                </div>

                                ${applicant.cover_letter ? `
                                    <div style="margin-bottom: 16px;">
                                        <strong style="color: #374151; display: block; margin-bottom: 8px;">Catatan/Cover Letter:</strong>
                                        <p style="color: #6b7280; margin: 0; white-space: pre-wrap;">${escapeHtml(applicant.cover_letter)}</p>
                                    </div>
                                ` : ''}

                                ${applicant.cv_url ? `
                                    <div style="margin-bottom: 16px;">
                                        <a href="${escapeHtml(applicant.cv_url)}" target="_blank" class="btn-download" style="text-decoration: none; display: inline-flex;">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Download CV
                                        </a>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeApplicantModal()">Tutup</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // Fungsi untuk close modal
        function closeApplicantModal() {
            const modal = document.getElementById('applicantDetailModal');
            if (modal) {
                modal.remove();
            }
        }

        // Close modal saat klik di luar
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('applicantDetailModal');
            if (event.target === modal) {
                closeApplicantModal();
            }
        });

        // Initial render
        document.addEventListener('DOMContentLoaded', function() {
            renderReport();
        });
    </script>
</body>

</html>