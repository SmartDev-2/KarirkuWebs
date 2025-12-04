<?php
// tambah-lowongan.php

// Set variabel dasar terlebih dahulu
$base_url = '../../';
$activePage = 'tambah-lowongan';

// Perbaikan path include supabase.php
$supabase_path = __DIR__ . '/../../function/supabase.php';
if (file_exists($supabase_path)) {
    include $supabase_path;
} else {
    error_log("Supabase path not found: " . $supabase_path);
    die("File supabase.php tidak ditemukan di: " . $supabase_path);
}

// START SESSION DAN CEK LOGIN
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// AMBIL DATA PERUSAHAAN DARI DATABASE
$company = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan, nama_perusahaan, logo_url',
    'id_pengguna' => 'eq.' . $user_id
]);

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_perusahaan' => $id_perusahaan, // GUNAKAN ID_PERUSAHAAN YANG SESUAI
        'judul' => $_POST['judul'] ?? '',
        'deskripsi' => $_POST['deskripsi'] ?? '',
        'kualifikasi' => $_POST['kualifikasi'] ?? '',
        'lokasi' => $_POST['lokasi'] ?? '',
        'tipe_pekerjaan' => $_POST['tipe_pekerjaan'] ?? 'full-time',
        'gaji_range' => $_POST['gaji_range'] ?? '',
        'batas_tanggal' => $_POST['batas_tanggal'] ?? null,
        'status' => 'ditinjau',
        'kategori' => $_POST['kategori'] ?? '',
        'mode_kerja' => $_POST['mode_kerja'] ?? 'On-site',
        'benefit' => $_POST['benefit'] ?? '',
        'tanggung_jawab' => $_POST['tanggung_jawab'] ?? ''
    ];

    $result = supabaseInsert('lowongan', $data);

    if ($result['success']) {
        header('Location: lowongan.php?success=1');
        exit;
    } else {
        $error = "Gagal menambah lowongan: " . ($result['data']['message'] ?? 'Unknown error');
        error_log("Error inserting job: " . print_r($result, true));
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lowongan Baru - Karirku</title>
    <link href="../../assets/img/karirkulogo.ico" rel="icon">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/company/company.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/company/tambah-lowongan.css">
    <!-- Tambahkan Bootstrap dan Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        .custom-checkbox {
            border-radius: 50% !important;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php
        $sidebar_path = __DIR__ . '/sidebar.php';
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        }
        ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <?php include 'topbar_company.php'; ?>

            <!-- Content -->
            <div class="content">

                <?php if (isset($error)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="jobForm">
                    <div class="content-wrapper">
                        <!-- Left Panel - Recommendations -->
                        <div class="left-panel">
                            <div class="recommendation-card">
                                <div class="recommendation-header">
                                    <h3>Rekomendasi</h3>
                                </div>
                                <div class="recommendation-body">
                                    <div class="recommendation-item">
                                        <div class="checkbox-wrapper">
                                            <div class="custom-checkbox" id="indicator1"></div>
                                        </div>
                                        <label for="rec1" class="recommendation-text">
                                            <div class="title">Judul lowongan</div>
                                        </label>
                                    </div>
                                    <div class="recommendation-item">
                                        <div class="checkbox-wrapper">
                                            <div class="custom-checkbox" id="indicator3"></div>
                                        </div>
                                        <label for="rec3" class="recommendation-text">
                                            <div class="title">Kategori</div>
                                        </label>
                                    </div>
                                    <div class="recommendation-item">
                                        <div class="checkbox-wrapper">
                                            <div class="custom-checkbox" id="indicator4"></div>
                                        </div>
                                        <label for="rec4" class="recommendation-text">
                                            <div class="title">Deskripsi</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel - Form -->
                        <div class="right-panel">
                            <!-- Section Tabs -->
                            <div class="section-tabs">
                                <div class="section-tab active" data-section="informasi">Informasi lowongan</div>
                                <div class="section-tab" data-section="deskripsi">Deskripsi</div>
                                <div class="section-tab" data-section="detail">Detail lowongan</div>
                            </div>

                            <!-- Section: Informasi Lowongan -->
                            <div class="section-content active" id="section-informasi">
                                <div class="form-card">
                                    <h2>Informasi Lowongan</h2>
                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Nama lowongan</label>
                                        <input type="text" name="judul" id="judulInput" class="form-input" placeholder="Contoh: Frontend Developer" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Kategori</label>
                                        <input type="text" name="kategori" id="kategoriInput" class="form-input" placeholder="Contoh: IT & Development" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Deskripsi -->
                            <div class="section-content" id="section-deskripsi">
                                <div class="form-card">
                                    <h2>Deskripsi Lowongan</h2>
                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Deskripsi lowongan</label>
                                        <textarea name="deskripsi" id="deskripsiInput" class="form-textarea" placeholder="Tuliskan deskripsi lowongan" required></textarea>
                                        <span id="errorDeskripsi" class="error-message">Deskripsi harus minimal 20 karakter</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Tanggung Jawab</label>
                                        <textarea name="tanggung_jawab" id="tanggungJawabInput" class="form-textarea" placeholder="Tuliskan tanggung jawab lowongan"></textarea>
                                        <span id="errorTanggungJawab" class="error-message">Tanggung jawab harus minimal 20 karakter</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Kualifikasi</label>
                                        <textarea name="kualifikasi" id="kualifikasiInput" class="form-textarea" placeholder="Tuliskan kualifikasi lowongan" required></textarea>
                                        <span id="errorKualifikasi" class="error-message">Kualifikasi harus minimal 20 karakter</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Detail Lowongan -->
                            <div class="section-content" id="section-detail">
                                <div class="form-card">
                                    <h2>Detail Lowongan</h2>
                                    <label class="form-label"><span class="required">*</span>Jam kerja</label>
                                    <div class="work-type-buttons">
                                        <button type="button" class="work-type-btn" data-type="full-time">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Full time
                                        </button>
                                        <button type="button" class="work-type-btn active" data-type="part-time">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Part time
                                        </button>
                                    </div>
                                    <input type="hidden" name="tipe_pekerjaan" id="workTypeInput" value="full-time">
                                    <input type="hidden" name="part_time_hours" id="partTimeHours" value="">
                                    <div class="form-group">
                                        <label class="form-label" style="margin-top: 7px;"><span class="required">*</span>Gaji</label>
                                        <input type="text" name="gaji_range" class="form-input" placeholder="Contoh: Rp 5.000.000 - Rp 8.000.000">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Lokasi</label>
                                        <textarea name="lokasi" class="form-textarea" style="min-height: 80px;" placeholder="Tulis lengkap lokasi kerja..." required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Mode Kerja</label>
                                        <select name="mode_kerja" class="form-select">
                                            <option value="On-site">On-site</option>
                                            <option value="Hybrid">Hybrid</option>
                                            <option value="Remote">Remote</option>
                                            <option value="Shift">Shift</option>
                                            <option value="Lapangan">Lapangan</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Benefit</label>
                                        <textarea name="benefit" class="form-textarea" style="min-height: 80px;" placeholder="Fasilitas dan benefit yang ditawarkan..."></textarea>
                                    </div>
                                    <!-- Tambahkan setelah field Benefit -->
                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span>Batas Tanggal</label>
                                        <input type="date" name="batas_tanggal" class="form-input" required
                                            min="<?php echo date('Y-m-d'); ?>"
                                            value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                        <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                                            Lowongan akan otomatis expired setelah tanggal ini
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="lowongan.php" class="btn-cancel" style="text-decoration: none; display: inline-block; text-align: center;">Batal</a>

                                <!-- Tombol Lanjut (muncul di section informasi dan deskripsi) -->
                                <button type="button" id="nextButton" class="btn-submit">Lanjut</button>

                                <!-- Tombol Simpan (muncul hanya di section detail) -->
                                <button type="submit" id="submitButton" class="btn-submit" style="display: none;">Simpan Lowongan</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Part Time -->
    <div id="partTimeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Pilih Jam Kerja Part Time</h3>
                <button class="close-modal" onclick="closePartTimeModal()">&times;</button>
            </div>
            <div class="time-slots">
                <div class="time-slot" data-hours="2">2-3 Jam/h</div>
                <div class="time-slot" data-hours="4">2-4 Jam/h</div>
                <div class="time-slot" data-hours="5">2-5 Jam/h</div>
                <div class="time-slot" data-hours="3">2-6 Jam/h</div>
                <div class="time-slot" data-hours="5">2-7 Jam/h</div>
                <div class="time-slot" data-hours="3">2-8 Jam/h</div>
                <div class="time-slot" data-hours="4">2-9 Jam/h</div>
                <div class="time-slot" data-hours="4">2-10 Jam/h</div>
                <div class="time-slot custom-slot">+</div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closePartTimeModal()">Batal</button>
                <button type="button" class="btn-submit" onclick="confirmPartTime()">Konfirmasi</button>
            </div>
        </div>
    </div>
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Section Tab Navigation
        document.querySelectorAll('.section-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const section = this.dataset.section;

                // Update active tab
                document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update active content
                document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));
                document.getElementById(`section-${section}`).classList.add('active');
            });
        });

        // Work Type Selection
        document.querySelectorAll('.work-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.dataset.type;

                // Update button states
                document.querySelectorAll('.work-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update hidden input
                document.getElementById('workTypeInput').value = type;

                // Open modal if part-time
                if (type === 'part-time') {
                    openPartTimeModal();
                } else {
                    document.getElementById('partTimeHours').value = '';
                }
            });
        });

        // Part Time Modal Functions
        function openPartTimeModal() {
            document.getElementById('partTimeModal').style.display = 'block';
        }

        function closePartTimeModal() {
            document.getElementById('partTimeModal').style.display = 'none';

            // Reset to full-time if no hours selected
            if (!document.getElementById('partTimeHours').value) {
                document.querySelectorAll('.work-type-btn').forEach(b => b.classList.remove('active'));
                document.querySelector('[data-type="full-time"]').classList.add('active');
                document.getElementById('workTypeInput').value = 'full-time';
            }
        }

        function confirmPartTime() {
            const selected = document.querySelector('.time-slot.selected');
            if (selected) {
                const hours = selected.dataset.hours;
                document.getElementById('partTimeHours').value = hours + ' Jam/hari';
                closePartTimeModal();
            } else {
                alert('Silakan pilih jam kerja terlebih dahulu');
            }
        }

        // Time Slot Selection
        document.querySelectorAll('.time-slot:not(.custom-slot)').forEach(slot => {
            slot.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Custom slot
        document.querySelector('.custom-slot').addEventListener('click', function() {
            const customHours = prompt('Masukkan jumlah jam kerja:');
            if (customHours && !isNaN(customHours)) {
                document.getElementById('partTimeHours').value = customHours + ' Jam/hari';
                closePartTimeModal();
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('partTimeModal');
            if (event.target === modal) {
                closePartTimeModal();
            }
        });
        // Auto-check recommendation checkboxes based on form input
        function updateRecommendationChecks() {
            // Check for judul lowongan (indicator1) - hanya butuh judul
            const judulValue = document.getElementById('judulInput').value.trim();
            const indicator1 = document.getElementById('indicator1');
            indicator1.classList.toggle('checked', judulValue !== '');

            // Check for poster (indicator2) - handled in poster upload event
            // (tetap di event poster upload)

            // Check for kategori (indicator3) - hanya butuh kategori
            const kategoriValue = document.getElementById('kategoriInput').value.trim();
            const indicator3 = document.getElementById('indicator3');
            indicator3.classList.toggle('checked', kategoriValue !== '');

            // Check for deskripsi (indicator4) - butuh deskripsi, tanggung jawab, dan kualifikasi
            const deskripsiValue = document.getElementById('deskripsiInput').value.trim();
            const tanggungJawabValue = document.getElementById('tanggungJawabInput').value.trim();
            const kualifikasiValue = document.getElementById('kualifikasiInput').value.trim();
            const indicator4 = document.getElementById('indicator4');
            indicator4.classList.toggle('checked', deskripsiValue !== '' && tanggungJawabValue !== '' && kualifikasiValue !== '');
        }

        // Add event listeners to form inputs
        document.getElementById('judulInput').addEventListener('input', updateRecommendationChecks);
        document.getElementById('kategoriInput').addEventListener('input', updateRecommendationChecks);
        document.getElementById('deskripsiInput').addEventListener('input', updateRecommendationChecks);
        document.getElementById('tanggungJawabInput').addEventListener('input', updateRecommendationChecks);
        document.getElementById('kualifikasiInput').addEventListener('input', updateRecommendationChecks);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updateRecommendationChecks);

        // Navigasi antara section
        let currentSection = 'informasi';

        // Fungsi untuk update tampilan tombol berdasarkan section
        function updateButtonVisibility() {
            const nextButton = document.getElementById('nextButton');
            const submitButton = document.getElementById('submitButton');

            if (currentSection === 'detail') {
                // Di section detail, tampilkan tombol Simpan, sembunyikan tombol Lanjut
                nextButton.style.display = 'none';
                submitButton.style.display = 'inline-block';
            } else {
                // Di section informasi dan deskripsi, tampilkan tombol Lanjut, sembunyikan tombol Simpan
                nextButton.style.display = 'inline-block';
                submitButton.style.display = 'none';
            }
        }

        // Fungsi untuk pindah ke section berikutnya
        function nextSection() {
            const sections = ['informasi', 'deskripsi', 'detail'];
            const currentIndex = sections.indexOf(currentSection);

            // Validasi section saat ini sebelum pindah
            if (!validateCurrentSection()) {
                return;
            }

            if (currentIndex < sections.length - 1) {
                // Pindah ke section berikutnya
                const nextSection = sections[currentIndex + 1];

                // Update active tab
                document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
                document.querySelector(`[data-section="${nextSection}"]`).classList.add('active');

                // Update active content
                document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));
                document.getElementById(`section-${nextSection}`).classList.add('active');

                currentSection = nextSection;
                updateButtonVisibility();
            }
        }

        // Fungsi untuk validasi section saat ini
        function validateCurrentSection() {
            switch (currentSection) {
                case 'informasi':
                    const judul = document.getElementById('judulInput').value.trim();
                    const kategori = document.getElementById('kategoriInput').value.trim();
                    if (!judul || !kategori) {
                        alert('Harap isi nama lowongan dan kategori terlebih dahulu');
                        return false;
                    }
                    break;

                case 'deskripsi':
                    const deskripsi = document.getElementById('deskripsiInput').value.trim();
                    const kualifikasi = document.getElementById('kualifikasiInput').value.trim();
                    if (!deskripsi || !kualifikasi) {
                        alert('Harap isi deskripsi dan kualifikasi terlebih dahulu');
                        return false;
                    }
                    break;
            }
            return true;
        }

        // Event listener untuk tombol Lanjut
        document.getElementById('nextButton').addEventListener('click', nextSection);

        // Event listener untuk tab navigation (tetap berfungsi)
        document.querySelectorAll('.section-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const section = this.dataset.section;

                // Validasi sebelum pindah tab
                if (!validateCurrentSection()) {
                    return;
                }

                // Update active tab
                document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update active content
                document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));
                document.getElementById(`section-${section}`).classList.add('active');

                currentSection = section;
                updateButtonVisibility();
            });
        });

        // Inisialisasi tampilan tombol saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateButtonVisibility();
        });

        function validateTextarea(textareaId, errorId) {
            const textarea = document.getElementById(textareaId);
            const errorSpan = document.getElementById(errorId);
            const value = textarea.value.trim();

            if (value.length < 20 && value.length > 0) {
                errorSpan.style.display = 'block';
                textarea.classList.add('textarea-error');
                return false;
            } else {
                errorSpan.style.display = 'none';
                textarea.classList.remove('textarea-error');
                return true;
            }
        }

        // Fungsi untuk memvalidasi semua textarea sebelum lanjut ke section berikutnya
        function validateCurrentSection() {
            let isValid = true;

            switch (currentSection) {
                case 'informasi':
                    const judul = document.getElementById('judulInput').value.trim();
                    const kategori = document.getElementById('kategoriInput').value.trim();
                    if (!judul || !kategori) {
                        alert('Harap isi nama lowongan dan kategori terlebih dahulu');
                        isValid = false;
                    }
                    break;

                case 'deskripsi':
                    const deskripsi = document.getElementById('deskripsiInput').value.trim();
                    const kualifikasi = document.getElementById('kualifikasiInput').value.trim();

                    // Validasi deskripsi
                    if (!deskripsi) {
                        alert('Harap isi deskripsi terlebih dahulu');
                        isValid = false;
                    } else if (deskripsi.length < 20) {
                        document.getElementById('errorDeskripsi').style.display = 'block';
                        document.getElementById('deskripsiInput').classList.add('textarea-error');
                        isValid = false;
                    }

                    // Validasi kualifikasi
                    if (!kualifikasi) {
                        alert('Harap isi kualifikasi terlebih dahulu');
                        isValid = false;
                    } else if (kualifikasi.length < 20) {
                        document.getElementById('errorKualifikasi').style.display = 'block';
                        document.getElementById('kualifikasiInput').classList.add('textarea-error');
                        isValid = false;
                    }

                    // Validasi tanggung jawab (tidak wajib, tapi jika diisi harus minimal 20 karakter)
                    const tanggungJawab = document.getElementById('tanggungJawabInput').value.trim();
                    if (tanggungJawab && tanggungJawab.length < 20) {
                        document.getElementById('errorTanggungJawab').style.display = 'block';
                        document.getElementById('tanggungJawabInput').classList.add('textarea-error');
                        isValid = false;
                    }
                    break;
            }
            return isValid;
        }

        // Event listeners untuk real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            // Validasi real-time untuk deskripsi
            document.getElementById('deskripsiInput').addEventListener('input', function() {
                validateTextarea('deskripsiInput', 'errorDeskripsi');
            });

            // Validasi real-time untuk tanggung jawab
            document.getElementById('tanggungJawabInput').addEventListener('input', function() {
                validateTextarea('tanggungJawabInput', 'errorTanggungJawab');
            });

            // Validasi real-time untuk kualifikasi
            document.getElementById('kualifikasiInput').addEventListener('input', function() {
                validateTextarea('kualifikasiInput', 'errorKualifikasi');
            });
        });
    </script>
</body>

</html>