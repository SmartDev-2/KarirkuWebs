<?php
// lowongan.php
include 'config.php';
$activePage = 'lowongan-saya';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_job') {
            $jobId = $_POST['job_id'];
            $data = [
                'judul' => $_POST['title'],
                'gaji_range' => $_POST['salary'],
                'lokasi' => $_POST['location'],
                'tipe_pekerjaan' => $_POST['type'],
                'deskripsi' => $_POST['description'],
                'kualifikasi' => $_POST['qualifications'],
                'kategori' => $_POST['category'],
                'mode_kerja' => $_POST['work_mode'],
                'benefit' => $_POST['benefits'],
                'batas_tanggal' => $_POST['deadline'] ?: null
            ];

            $result = supabaseUpdate('lowongan', $data, 'id_lowongan', $jobId);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Lowongan berhasil diperbarui']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui lowongan']);
            }
            exit;
        } elseif ($_POST['action'] === 'delete_job') {
            $jobId = $_POST['job_id'];
            $result = supabaseDelete('lowongan', 'id_lowongan', $jobId);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Lowongan berhasil dihapus']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus lowongan: ' . ($result['error'] ?? 'Unknown error')]);
            }
            exit;
        }
    }
}

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

// Ambil data lowongan khusus perusahaan ini
$lowonganResult = supabaseQuery('lowongan', [
    'select' => '*',
    'id_perusahaan' => 'eq.' . $id_perusahaan,
    'order' => 'dibuat_pada.desc'
]);

if (!$lowonganResult['success']) {
    error_log("Error fetching jobs: " . print_r($lowonganResult, true));
    $allJobs = [];
} else {
    $allJobs = [];
    foreach ($lowonganResult['data'] as $job) {
        // Hitung jarak hari
        $createdDate = new DateTime($job['dibuat_pada']);
        $currentDate = new DateTime();
        $interval = $currentDate->diff($createdDate);
        $daysAgo = $interval->days;

        // Format tanggal posting
        $formattedDate = date('M j \a\t g:i', strtotime($job['dibuat_pada']));
        $postedText = "Diposting {$daysAgo} hari lalu ({$formattedDate})";

        // Format gaji
        $salary = $job['gaji_range'] ?: 'Gaji tidak ditampilkan';

        // Cek apakah lowongan sudah expired
        $isExpired = false;
        $expiredText = '';
        if (!empty($job['batas_tanggal'])) {
            $deadline = new DateTime($job['batas_tanggal']);
            $today = new DateTime();
            if ($deadline < $today) {
                $isExpired = true;
                $expiredText = " (Expired)";
            } else {
                // Hitung hari tersisa
                $daysLeft = $today->diff($deadline)->days;
                $expiredText = " ({$daysLeft} hari tersisa)";
            }
        }

        // Tentukan status class dan label berdasarkan status di database dan expired status
        $status = $job['status'] ?? 'ditinjau';
        $statusClass = '';
        $statusLabel = '';

        // Jika expired, override status menjadi expired
        if ($isExpired && $status === 'publish') {
            $status = 'expired';
            $statusClass = 'status-expired';
            $statusLabel = 'Expired';
        } else {
            switch ($status) {
                case 'publish':
                    $statusClass = 'status-publish';
                    $statusLabel = 'Publish';
                    break;
                case 'ditinjau':
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
                    break;
                case 'draft':
                    $statusClass = 'status-draft';
                    $statusLabel = 'Draft';
                    break;
                case 'ditolak':
                    $statusClass = 'status-rejected';
                    $statusLabel = 'Ditolak';
                    break;
                default:
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
            }
        }

        // Untuk performa, tampilkan jumlah pelamar untuk status publish, lainnya "Belum dipublikasi"
        $applicants = ($status === 'publish' || $status === 'expired') ? 'Berjumlah 0 Pelamar' : 'Belum dipublikasi';

        $jobData = [
            'id' => $job['id_lowongan'],
            'title' => $job['judul'] . $expiredText,
            'company' => $nama_perusahaan,
            'salary' => $salary,
            'posted' => $postedText,
            'applicants' => $applicants,
            'status' => $status,
            'statusClass' => $statusClass,
            'statusLabel' => $statusLabel,
            'location' => $job['lokasi'] ?? '',
            'type' => $job['tipe_pekerjaan'] ?? '',
            'description' => $job['deskripsi'] ?? '',
            'qualifications' => $job['kualifikasi'] ?? '',
            'category' => $job['kategori'] ?? '',
            'work_mode' => $job['mode_kerja'] ?? '',
            'benefits' => $job['benefit'] ?? '',
            'deadline' => $job['batas_tanggal'] ?? '',
            'isExpired' => $isExpired,
            'expiredText' => $expiredText
        ];

        $allJobs[] = $jobData;
    }
}

// Siapkan data untuk JSON
$jobsDataForJson = [
    'semua' => $allJobs,
    'live' => array_values(array_filter($allJobs, function ($job) {
        return $job['status'] === 'publish';
    })),
    'perlu' => array_values(array_filter($allJobs, function ($job) {
        return $job['status'] === 'ditinjau';
    })),
    'draft' => array_values(array_filter($allJobs, function ($job) {
        return $job['status'] === 'draft';
    })),
    'sedang' => array_values(array_filter($allJobs, function ($job) {
        return $job['status'] === 'ditolak';
    }))
];

$jobsDataJson = json_encode($jobsDataForJson);

// Data untuk tampilan count di PHP (untuk ditampilkan di tab)
$jobsData = [
    'semua' => $allJobs,
    'live' => array_filter($allJobs, function ($job) {
        return $job['status'] === 'publish';
    }),
    'perlu' => array_filter($allJobs, function ($job) {
        return $job['status'] === 'ditinjau';
    }),
    'draft' => array_filter($allJobs, function ($job) {
        return $job['status'] === 'draft';
    }),
    'sedang' => array_filter($allJobs, function ($job) {
        return $job['status'] === 'ditolak';
    })
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan Saya - Karirku</title>
    <link href="../../assets/img/karirkulogo.ico" rel="icon">
    <link rel="stylesheet" href="../../assets/css/company/company.css">
    <link rel="stylesheet" href="../../assets/css/company/lowongan.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        .user-dropdown.dropdown-toggle::after {
            display: none !important;
        }

        /* Tambahkan di bagian style */
        .notification {
            transition: all 0.3s ease;
        }

        .notification button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-expired {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-expired::before {
            background: #dc2626;
        }

        .days-left {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            font-weight: 500;
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
                        <span class="text-tab active" onclick="changeTab('semua')">Semua</span>
                        <span class="text-tab" onclick="changeTab('live')">Live (<?php echo isset($jobsData['live']) ? count($jobsData['live']) : 0; ?>)</span>
                        <span class="text-tab" onclick="changeTab('perlu')">Ditinjau (<?php echo isset($jobsData['perlu']) ? count($jobsData['perlu']) : 0; ?>)</span>
                        <span class="text-tab" onclick="changeTab('draft')">Draft (<?php echo isset($jobsData['draft']) ? count($jobsData['draft']) : 0; ?>)</span>
                        <span class="text-tab" onclick="changeTab('sedang')">Ditolak (<?php echo isset($jobsData['sedang']) ? count($jobsData['sedang']) : 0; ?>)</span>
                    </div>
                </div>

                <div class="tabs-container">
                    <h2>Lowongan Saya</h2>
                    <div class="filters">
                        <div class="filters-left">
                            <div class="search-container">
                                <div class="search-box">
                                    <svg class="search-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" placeholder="Cari lowongan..." oninput="filterJobs()" id="searchInput">
                                </div>
                                <div class="total-count" id="totalCount">
                                    <span id="currentCount"><?php echo count($allJobs); ?></span> lowongan
                                </div>
                            </div>
                            <select class="filter-select" onchange="filterJobs()" id="categorySelect">
                                <option value="">Semua Kategori</option>
                                <option>IT & Development</option>
                                <option>Design</option>
                                <option>Marketing</option>
                            </select>
                            <select class="filter-select" onchange="filterJobs()" id="prioritySelect">
                                <option value="">Semua Prioritas</option>
                                <option>Tinggi</option>
                                <option>Sedang</option>
                                <option>Rendah</option>
                            </select>
                        </div>
                        <div class="filters-right">
                            <button class="btn" onclick="toggleView()">Tampilan</button>
                            <a href="tambah-lowongan.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Buat Lowongan</span>
                            </a>
                        </div>
                    </div>
                    <div class="jobs-table-container">
                        <table class="jobs-table">
                            <thead>
                                <tr>
                                    <th>Lowongan</th>
                                    <th>Gaji</th>
                                    <th>Performa</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="jobsTableBody">
                                <!-- Jobs akan di-render oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div id="editJobModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Lowongan</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editJobForm">
                <input type="hidden" id="editJobId" name="job_id">
                <input type="hidden" name="action" value="update_job">

                <div class="form-group">
                    <label class="form-label" for="editJobTitle">Judul Lowongan</label>
                    <input type="text" id="editJobTitle" name="title" class="form-input" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editJobSalary">Gaji</label>
                        <input type="text" id="editJobSalary" name="salary" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editJobLocation">Lokasi</label>
                        <input type="text" id="editJobLocation" name="location" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editJobType">Tipe Pekerjaan</label>
                        <select id="editJobType" name="type" class="form-select" required>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editJobCategory">Kategori</label>
                        <select id="editJobCategory" name="category" class="form-select" required>
                            <option value="IT & Development">IT & Development</option>
                            <option value="Design">Design</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editJobWorkMode">Mode Kerja</label>
                    <select id="editJobWorkMode" name="work_mode" class="form-select" required>
                        <option value="WFO">WFO (Work From Office)</option>
                        <option value="WFH">WFH (Work From Home)</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editJobDescription">Deskripsi Pekerjaan</label>
                    <textarea id="editJobDescription" name="description" class="form-textarea" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editJobQualifications">Kualifikasi</label>
                    <textarea id="editJobQualifications" name="qualifications" class="form-textarea" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editJobBenefits">Benefit</label>
                    <textarea id="editJobBenefits" name="benefits" class="form-textarea"></textarea>
                </div>

                <!-- Dalam form edit modal, tambahkan setelah field benefits -->
                <div class="form-group">
                    <label class="form-label" for="editJobDeadline">Batas Tanggal</label>
                    <input type="date" id="editJobDeadline" name="deadline" class="form-input">
                    <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                        Lowongan akan otomatis expired setelah tanggal ini
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Konfirmasi Hapus</h3>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="confirmation-text">
                <p>Apakah Anda yakin ingin menghapus lowongan "<span id="deleteJobTitle"></span>"?</p>
                <p style="color: #ef4444; font-size: 14px; margin-top: 8px;">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button type="button" class="btn-save" style="background: #ef4444;" onclick="confirmDelete()">Hapus</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data dari PHP - sekarang berisi data yang sudah difilter
        const jobsData = <?php echo $jobsDataJson; ?>;
        let currentTab = 'semua';
        let currentView = 'table';
        let allJobsData = jobsData.semua; // Simpan semua data untuk filtering
        let jobToDelete = null;

        // Fungsi untuk change tab
        function changeTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.text-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            renderJobs();
        }

        // Fungsi untuk filter jobs
        function filterJobs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categorySelect').value;

            let filteredJobs = allJobsData;

            // Apply search filter
            if (searchTerm) {
                filteredJobs = filteredJobs.filter(job =>
                    job.title.toLowerCase().includes(searchTerm) ||
                    job.company.toLowerCase().includes(searchTerm)
                );
            }

            // Apply category filter
            if (category) {
                filteredJobs = filteredJobs.filter(job =>
                    job.category && job.category.toLowerCase().includes(category.toLowerCase())
                );
            }

            // Update jobsData untuk tab saat ini
            const tempJobsData = {
                'semua': filteredJobs,
                'live': filteredJobs.filter(job => job.status === 'publish'),
                'perlu': filteredJobs.filter(job => job.status === 'ditinjau'),
                'draft': filteredJobs.filter(job => job.status === 'draft'),
                'sedang': filteredJobs.filter(job => job.status === 'ditolak')
            };

            // Render dengan data yang sudah difilter
            renderFilteredJobs(tempJobsData[currentTab] || []);
        }

        // Render jobs dengan data yang sudah difilter
        function renderFilteredJobs(jobs) {
            const jobsTableBody = document.getElementById('jobsTableBody');
            const currentCount = document.getElementById('currentCount');

            if (!jobsTableBody) {
                console.error('Element jobsTableBody tidak ditemukan');
                return;
            }

            let html = '';

            if (jobs.length === 0) {
                html = `
                <tr>
                    <td colspan="5" class="empty-state">
                        <svg width="48" height="48" fill="none" stroke="#d1d5db" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p>Belum ada lowongan di kategori ini</p>
                    </td>
                </tr>
            `;
                currentCount.textContent = '0';
            } else {
                jobs.forEach(job => {
                    // Menampilkan jumlah pelamar atau "Belum dipublikasi"
                    const performanceText = job.status === 'publish' ? job.applicants : 'Belum dipublikasi';

                    // Tampilkan tombol edit hanya untuk status draft dan publish
                    // Untuk status "ditinjau" dan "ditolak", jangan tampilkan action apapun
                    const showEditButton = (job.status === 'draft' || job.status === 'publish');
                    const showDeleteButton = (job.status === 'draft' || job.status === 'publish' || job.status === 'ditolak');

                    html += `
                    <tr class="job-row" id="job-${job.id}">
                        <td>
                            <div class="job-info-cell">
                                <div class="job-details">
                                    <h3>${job.title.replace(job.expiredText, '')}</h3>
                                    ${job.expiredText ? `<div class="days-left">${job.expiredText.replace(/[()]/g, '')}</div>` : ''}
                                    <p class="job-company">${job.company}</p>
                                    <div class="job-meta">
                                        <span>${job.posted}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="salary-cell">${job.salary}</div>
                        </td>
                        <td>
                            <div class="performance-cell">
                                <span class="performance-text">${performanceText}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${job.statusClass}">${job.statusLabel}</span>
                        </td>
                        <td>
                            <div class="action-cell">
                              ${showEditButton ? `
    <a href="edit_lowongan.php?id=${job.id}" class="btn-action btn-edit">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Edit
    </a>
` : ''}
                                ${showDeleteButton ? `
                                    <button class="btn-action btn-delete" onclick="openDeleteModal(${job.id}, '${escapeString(job.title)}')">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                ` : ''}
                                ${(!showEditButton && !showDeleteButton) ? '<span class="no-action">-</span>' : ''}
                            </div>
                        </td>
                    </tr>
                `;
                });
                currentCount.textContent = jobs.length;
            }

            jobsTableBody.innerHTML = html;

            // Dalam fungsi renderFilteredJobs, tambahkan case untuk status expired
            switch ($status) {
                case 'publish':
                    $statusClass = 'status-publish';
                    $statusLabel = 'Publish';
                    break;
                case 'ditinjau':
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
                    break;
                case 'draft':
                    $statusClass = 'status-draft';
                    $statusLabel = 'Draft';
                    break;
                case 'ditolak':
                    $statusClass = 'status-rejected';
                    $statusLabel = 'Ditolak';
                    break;
                case 'expired':
                    $statusClass = 'status-expired';
                    $statusLabel = 'Expired';
                    break;
                default:
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
            }
        }

        // Helper function untuk escape string
        function escapeString(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'").replace(/\n/g, "\\n");
        }

        // Render jobs normal (tanpa filter)
        function renderJobs() {
            const jobs = jobsData[currentTab] || [];
            renderFilteredJobs(jobs);
        }

        // Fungsi untuk toggle view
        function toggleView() {
            alert('Fitur tampilan card akan segera hadir!');
        }

        // Modal functions - Edit
        function openEditModal(jobId, title, salary, location, type, description, qualifications, category, workMode, benefits) {
            document.getElementById('editJobId').value = jobId;
            document.getElementById('editJobTitle').value = title;
            document.getElementById('editJobSalary').value = salary;
            document.getElementById('editJobLocation').value = location || '';
            document.getElementById('editJobType').value = type || 'Full-time';
            document.getElementById('editJobDescription').value = description || '';
            document.getElementById('editJobQualifications').value = qualifications || '';
            document.getElementById('editJobCategory').value = category || 'IT & Development';
            document.getElementById('editJobWorkMode').value = workMode || 'WFO';
            document.getElementById('editJobBenefits').value = benefits || '';

            document.getElementById('editJobModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editJobModal').style.display = 'none';
        }

        // Modal functions - Delete
        function openDeleteModal(jobId, title) {
            jobToDelete = jobId;
            document.getElementById('deleteJobTitle').textContent = title;
            document.getElementById('deleteConfirmModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            jobToDelete = null;
        }

        function confirmDelete() {
            if (jobToDelete) {
                const formData = new FormData();
                formData.append('action', 'delete_job');
                formData.append('job_id', jobToDelete);

                fetch('lowongan.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hapus dari data frontend
                            allJobsData = allJobsData.filter(job => job.id != jobToDelete);

                            // Update jobsData untuk semua tab
                            updateJobsData();

                            // Hapus elemen dari DOM
                            const jobRow = document.getElementById(`job-${jobToDelete}`);
                            if (jobRow) {
                                jobRow.remove();
                            }

                            // Update count
                            updateJobCount();

                            // Tutup modal dan beri feedback
                            closeDeleteModal();
                            alert('Lowongan berhasil dihapus!');

                            // Jika tabel kosong setelah penghapusan, render ulang untuk menampilkan state kosong
                            const remainingJobs = jobsData[currentTab] || [];
                            if (remainingJobs.length === 0) {
                                renderJobs();
                            }

                            // Reload halaman setelah 1 detik untuk sinkronisasi data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);

                        } else {
                            alert('Gagal menghapus lowongan: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus lowongan.');
                    });
            }
        }

        // Update jobs data setelah perubahan
        function updateJobsData() {
            jobsData.semua = allJobsData;
            jobsData.live = allJobsData.filter(job => job.status === 'publish');
            jobsData.perlu = allJobsData.filter(job => job.status === 'ditinjau');
            jobsData.draft = allJobsData.filter(job => job.status === 'draft');
            jobsData.sedang = allJobsData.filter(job => job.status === 'ditolak');
        }

        // Update job count
        function updateJobCount() {
            const currentCount = document.getElementById('currentCount');
            const totalCount = allJobsData.length;
            currentCount.textContent = totalCount;
        }

        // Handle form submission untuk edit
        document.getElementById('editJobForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            // Dalam event listener form submission, update data deadline
            allJobsData[jobIndex].deadline = document.getElementById('editJobDeadline').value;

            // Hitung ulang status expired
            const deadline = new Date(allJobsData[jobIndex].deadline);
            const today = new Date();
            if (deadline < today && allJobsData[jobIndex].status === 'publish') {
                allJobsData[jobIndex].status = 'expired';
                allJobsData[jobIndex].statusClass = 'status-expired';
                allJobsData[jobIndex].statusLabel = 'Expired';
                allJobsData[jobIndex].isExpired = true;
            }
            fetch('lowongan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update data di frontend
                        const jobId = document.getElementById('editJobId').value;
                        const jobIndex = allJobsData.findIndex(job => job.id == jobId);

                        if (jobIndex !== -1) {
                            // Update properti yang diubah
                            allJobsData[jobIndex].title = document.getElementById('editJobTitle').value;
                            allJobsData[jobIndex].salary = document.getElementById('editJobSalary').value;
                            allJobsData[jobIndex].location = document.getElementById('editJobLocation').value;
                            allJobsData[jobIndex].type = document.getElementById('editJobType').value;
                            allJobsData[jobIndex].description = document.getElementById('editJobDescription').value;
                            allJobsData[jobIndex].qualifications = document.getElementById('editJobQualifications').value;
                            allJobsData[jobIndex].category = document.getElementById('editJobCategory').value;
                            allJobsData[jobIndex].work_mode = document.getElementById('editJobWorkMode').value;
                            allJobsData[jobIndex].benefits = document.getElementById('editJobBenefits').value;

                            // Update jobsData
                            updateJobsData();

                            // Re-render table
                            renderJobs();
                        }

                        // Tutup modal
                        closeEditModal();
                        alert('Perubahan berhasil disimpan!');
                    } else {
                        alert('Gagal menyimpan perubahan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menyimpan perubahan.');
                });
        });

        function openEditModal(jobId, title, salary, location, type, description, qualifications, category, workMode, benefits, deadline) {
            document.getElementById('editJobId').value = jobId;
            document.getElementById('editJobTitle').value = title;
            document.getElementById('editJobSalary').value = salary;
            document.getElementById('editJobLocation').value = location || '';
            document.getElementById('editJobType').value = type || 'Full-time';
            document.getElementById('editJobDescription').value = description || '';
            document.getElementById('editJobQualifications').value = qualifications || '';
            document.getElementById('editJobCategory').value = category || 'IT & Development';
            document.getElementById('editJobWorkMode').value = workMode || 'WFO';
            document.getElementById('editJobBenefits').value = benefits || '';
            document.getElementById('editJobDeadline').value = deadline || '';

            document.getElementById('editJobModal').style.display = 'block';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const editModal = document.getElementById('editJobModal');
            const deleteModal = document.getElementById('deleteConfirmModal');

            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        // Initial render
        document.addEventListener('DOMContentLoaded', function() {
            renderJobs();
        });
    </script>
</body>

</html>