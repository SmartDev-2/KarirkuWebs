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

// Ambil data lowongan expired
$lowonganResult = supabaseQuery('lowongan', [
    'select' => '*',
    'id_perusahaan' => 'eq.' . $id_perusahaan,
    'order' => 'dibuat_pada.desc'
]);

$expiredJobs = [];
$allApplicants = [];
$jobIds = [];

if ($lowonganResult['success']) {
    foreach ($lowonganResult['data'] as $job) {
        $jobIds[] = $job['id_lowongan'];

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

            // Hitung jumlah pelamar real dari database
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

// Ambil data pelamar untuk perusahaan ini
if (!empty($jobIds)) {
    // Query lamaran dengan join ke pencari_kerja
    $lamaranResult = supabaseQuery('lamaran', [
        'select' => '*',
        'id_lowongan' => 'in.(' . implode(',', $jobIds) . ')',
        'order' => 'tanggal_lamaran.desc'
    ]);

    if ($lamaranResult['success']) {
        foreach ($lamaranResult['data'] as $lamaran) {
            // Ambil data pencari kerja
            $pencariKerja = supabaseQuery('pencari_kerja', [
                'select' => '*',
                'id_pengguna' => 'eq.' . $lamaran['id_pengguna']
            ]);

            if ($pencariKerja['success'] && count($pencariKerja['data']) > 0) {
                $pelamar = $pencariKerja['data'][0];

                // Ambil data lowongan yang dilamar
                $lowongan = null;
                foreach ($lowonganResult['data'] as $job) {
                    if ($job['id_lowongan'] == $lamaran['id_lowongan']) {
                        $lowongan = $job;
                        break;
                    }
                }

                // Format tanggal lamaran
                $applyDate = date('d M Y', strtotime($lamaran['tanggal_lamaran']));

                // Tentukan status lamaran
                $statusLamaran = $lamaran['status'] ?? 'pending';
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
                    case 'diproses':
                        $statusClass = 'status-processing';
                        $statusLabel = 'Diproses';
                        break;
                    default:
                        $statusClass = 'status-pending';
                        $statusLabel = 'Menunggu';
                }

                $allApplicants[] = [
                    'id' => $lamaran['id_lamaran'],
                    'name' => $pelamar['nama_lengkap'] ?? 'Nama tidak tersedia',
                    'email' => $pelamar['email'] ?? '',
                    'phone' => $pelamar['nomor_telepon'] ?? '',
                    'job_title' => $lowongan ? $lowongan['judul'] : 'Lowongan tidak ditemukan',
                    'job_id' => $lamaran['id_lowongan'],
                    'apply_date' => $applyDate,
                    'status' => $statusLamaran,
                    'status_class' => $statusClass,
                    'status_label' => $statusLabel,
                    'cv_url' => $lamaran['cv_url'] ?? '',
                    'cover_letter' => $lamaran['cover_letter'] ?? '',
                    'pendidikan' => $pelamar['pendidikan'] ?? '',
                    'pengalaman' => $pelamar['pengalaman_kerja'] ?? '',
                    'keahlian' => $pelamar['keahlian'] ?? ''
                ];
            }
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
                        <span class="text-tab" onclick="changeTab('applicants')">Pencari lowongan kerja</span>
                    </div>
                </div>
                <!-- Filters -->
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
                            <!-- Content will be rendered by JavaScript -->
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
                data = data.filter(item =>
                    item.title.toLowerCase().includes(searchTerm) ||
                    item.company.toLowerCase().includes(searchTerm) ||
                    (item.location && item.location.toLowerCase().includes(searchTerm))
                );
            }

            // Apply date range filter
            if (dateRange) {
                const days = parseInt(dateRange);
                const cutoffDate = new Date();
                cutoffDate.setDate(cutoffDate.getDate() - days);

                data = data.filter(item => {
                    const itemDate = new Date(item.expired_date || item.posted_date);
                    return itemDate >= cutoffDate;
                });
            }

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
            if (filteredData.length === 0) {
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
                            <div class="job-title-cell">${job.title}</div>
                            <div class="job-subtitle">${job.company}</div>
                        </td>
                        <td>${job.salary}</td>
                        <td>${job.location || '-'}</td>
                        <td>
                            <span class="expired-badge">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ${job.expired_date}
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
            if (filteredData.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-report">
                        <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3>Belum ada pelamar</h3>
                        <p>Data pelamar yang mendaftar akan muncul di sini</p>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            filteredData.forEach(applicant => {
                const initial = applicant.name.charAt(0).toUpperCase();
                html += `
                    <tr>
                        <td>
                            <div class="applicant-info-cell">
                                <div class="applicant-avatar">
                                    ${initial}
                                </div>
                                <div class="applicant-details">
                                    <div class="applicant-name">${applicant.name}</div>
                                    <div class="applicant-email">${applicant.email}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="job-title-cell">${applicant.job_title}</div>
                        </td>
                        <td>${applicant.apply_date}</td>
                        <td>
                            <span class="status-badge ${applicant.status_class}">${applicant.status_label}</span>
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
                                    <a href="${applicant.cv_url}" target="_blank" class="btn-download" style="text-decoration: none;">
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
                        <div style="padding: 20px 0;">
                            <div style="margin-bottom: 20px;">
                                <div class="applicant-info-cell" style="margin-bottom: 16px;">
                                    <div class="applicant-avatar" style="width: 60px; height: 60px; font-size: 24px;">
                                        ${applicant.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="applicant-details">
                                        <div class="applicant-name" style="font-size: 18px; margin-bottom: 4px;">${applicant.name}</div>
                                        <div class="applicant-email">${applicant.email}</div>
                                        ${applicant.phone ? `<div class="applicant-email">${applicant.phone}</div>` : ''}
                                    </div>
                                </div>
                            </div>

                            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Lowongan yang Dilamar:</strong>
                                    <p style="color: #6b7280; margin: 0;">${applicant.job_title}</p>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Tanggal Melamar:</strong>
                                    <p style="color: #6b7280; margin: 0;">${applicant.apply_date}</p>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <strong style="color: #374151; display: block; margin-bottom: 8px;">Status:</strong>
                                    <span class="status-badge ${applicant.status_class}">${applicant.status_label}</span>
                                </div>

                                ${applicant.pendidikan ? `
                                    <div style="margin-bottom: 16px;">
                                        <strong style="color: #374151; display: block; margin-bottom: 8px;">Pendidikan:</strong>
                                        <p style="color: #6b7280; margin: 0; white-space: pre-wrap;">${applicant.pendidikan}</p>
                                    </div>
                                ` : ''}

                                ${applicant.pengalaman ? `
                                    <div style="margin-bottom: 16px;">
                                        <strong style="color: #374151; display: block; margin-bottom: 8px;">Pengalaman Kerja:</strong>
                                        <p style="color: #6b7280; margin: 0; white-space: pre-wrap;">${applicant.pengalaman}</p>
                                    </div>
                                ` : ''}

                                ${applicant.keahlian ? `
                                    <div style="margin-bottom: 16px;">
                                        <strong style="color: #374151; display: block; margin-bottom: 8px;">Keahlian:</strong>
                                        <p style="color: #6b7280; margin: 0; white-space: pre-wrap;">${applicant.keahlian}</p>
                                    </div>
                                ` : ''}

                                ${applicant.cover_letter ? `
                                    <div style="margin-bottom: 16px;">
                                        <strong style="color: #374151; display: block; margin-bottom: 8px;">Cover Letter:</strong>
                                        <p style="color: #6b7280; margin: 0; white-space: pre-wrap;">${applicant.cover_letter}</p>
                                    </div>
                                ` : ''}

                                ${applicant.cv_url ? `
                                    <div style="margin-bottom: 16px;">
                                        <a href="${applicant.cv_url}" target="_blank" class="btn-download" style="text-decoration: none; display: inline-flex;">
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