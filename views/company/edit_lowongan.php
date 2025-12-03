<?php
$activePage = 'lowongan-saya';

session_start();
require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil ID lowongan dari URL dan validasi
$job_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$job_id) {
    header('Location: lowongan.php');
    exit;
}

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

// Jika tidak ada id_perusahaan, redirect ke edit_company
if (!$id_perusahaan) {
    header('Location: edit_company.php');
    exit;
}

// Ambil data lowongan dari database
$jobResult = supabaseQuery('lowongan', [
    'select' => '*',
    'id_lowongan' => 'eq.' . $job_id,
    'id_perusahaan' => 'eq.' . $id_perusahaan
]);

if (!$jobResult['success'] || empty($jobResult['data'])) {
    header('Location: lowongan.php');
    exit;
}

$job = $jobResult['data'][0];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Tampilkan data POST
    error_log("POST Data: " . print_r($_POST, true));
    
    // Bersihkan dan validasi data
    $judul = trim($_POST['judul'] ?? '');
    $gaji = trim($_POST['gaji'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tipe_pekerjaan = strtolower(trim($_POST['tipe_pekerjaan'] ?? '')); // Konversi ke lowercase
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kualifikasi = trim($_POST['kualifikasi'] ?? '');
    $benefit = trim($_POST['benefit'] ?? '');
    $batas_tanggal = !empty($_POST['batas_tanggal']) ? $_POST['batas_tanggal'] : null;
    
    // Validasi tipe_pekerjaan sesuai constraint database
    $valid_tipe_pekerjaan = ['full-time', 'part-time', 'contract', 'internship'];
    if (!in_array($tipe_pekerjaan, $valid_tipe_pekerjaan)) {
        $error = "Tipe pekerjaan tidak valid. Pilih dari: Full-time, Part-time, Contract, atau Internship";
    }
    // Validasi field wajib lainnya
    elseif (empty($judul) || empty($gaji) || empty($kategori) || empty($lokasi) || empty($deskripsi)) {
        $error = "Semua field yang wajib diisi harus terisi!";
    } else {
        $data = [
            'judul' => $judul,
            'gaji_range' => $gaji,
            'kategori' => $kategori,
            'lokasi' => $lokasi,
            'tipe_pekerjaan' => $tipe_pekerjaan, // Gunakan tipe_pekerjaan sesuai constraint
            'deskripsi' => $deskripsi,
            'kualifikasi' => $kualifikasi,
            'benefit' => $benefit,
            'batas_tanggal' => $batas_tanggal,
            'status' => "ditinjau"
        ];
        
        // Debug: Tampilkan data yang akan diupdate
        error_log("Update Data: " . print_r($data, true));
        
        $result = supabaseUpdate('lowongan', $data, 'id_lowongan', $job_id);
        
        // Debug: Tampilkan hasil update
        error_log("Update Result: " . print_r($result, true));
        
        if ($result['success']) {
            echo '<script>alert("Lowongan berhasil diperbarui!"); window.location.href = "lowongan.php";</script>';
            exit;
        } else {
            // Tampilkan error detail untuk debugging
            $error_detail = $result['error'] ?? ($result['response'] ?? 'Tidak ada detail error');
            $error = "Gagal memperbarui lowongan. Error: " . htmlspecialchars($error_detail);
            
            // Simpan data POST untuk ditampilkan kembali
            $_SESSION['form_data'] = $_POST;
        }
    }
}

// Jika ada data yang disimpan di session (setelah error), gunakan itu
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
    
    // Override data dari database dengan data form
    $job['judul'] = $form_data['judul'] ?? $job['judul'];
    $job['gaji_range'] = $form_data['gaji'] ?? $job['gaji_range'];
    $job['kategori'] = $form_data['kategori'] ?? $job['kategori'];
    $job['lokasi'] = $form_data['lokasi'] ?? $job['lokasi'];
    $job['tipe_pekerjaan'] = $form_data['tipe_pekerjaan'] ?? $job['tipe_pekerjaan'];
    $job['deskripsi'] = $form_data['deskripsi'] ?? $job['deskripsi'];
    $job['kualifikasi'] = $form_data['kualifikasi'] ?? $job['kualifikasi'];
    $job['benefit'] = $form_data['benefit'] ?? $job['benefit'];
    $job['batas_tanggal'] = $form_data['batas_tanggal'] ?? $job['batas_tanggal'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lowongan - Karirku</title>
    <link rel="stylesheet" href="../../assets/css/company/company.css">
    <link href="../../assets/img/karirkulogo.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        .content {
            background: #f8f9fa;
            min-height: 60vh;
            padding: 20px;
        }

        /* Card dengan shadow */
        .profile-edit-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
            border: 1px solid #e0f2fe;
            position: relative;
            margin-top: 20px;
        }

        /* Header center dengan warna biru */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0f2fe;
            padding-top: 10px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 12px;
            text-align: center;
        }

        .page-subtitle {
            color: #3b82f6;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
        }

        /* Form styling */
        .form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            display: block;
        }

        .form-group .form-control,
        .form-group .form-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            background: #f9fafb;
        }

        .form-group .form-control:focus,
        .form-group .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .form-group textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Kembali */
        .back-button {
            text-decoration: none;
            color: #1e40af;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 100;
            padding: 8px 0;
            font-size: 16px;
            background: none;
            border: none;
            position: absolute;
            top: 40px;
            left: 40px;
        }

        .back-button:hover {
            color: #3b82f6;
            text-decoration: none;
        }

        .back-button i {
            font-size: 18px;
        }

        /* Form actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            margin-top: 40px;
        }

        .required {
            color: #ef4444;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Alert styling */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .text-muted {
            color: #6b7280 !important;
        }

        .topbar {
            height: 81px !important;
        }
        
        /* Error message */
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
            display: block;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
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
                <div class="profile-edit-container">
                    <!-- Button Kembali -->
                    <a href="lowongan.php" class="back-button">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>

                    <!-- Header center dengan warna biru -->
                    <div class="page-header">
                        <h2 class="page-title">Edit Lowongan</h2>
                        <p class="page-subtitle">Perbarui informasi lowongan pekerjaan</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="editJobForm" onsubmit="return validateForm()">
                        <div class="form-section">
                            <!-- Kolom Kiri -->
                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Judul Lowongan</label>
                                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul lowongan"
                                        value="<?php echo htmlspecialchars($job['judul'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Gaji</label>
                                    <input type="text" name="gaji" class="form-control" placeholder="Contoh: Rp 5.000.000 - Rp 8.000.000"
                                        value="<?php echo htmlspecialchars($job['gaji_range'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Kategori</label>
                                    <select class="form-select" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="IT & Software" <?php echo ($job['kategori'] ?? '') == 'IT & Software' ? 'selected' : ''; ?>>IT & Software</option>
                                        <option value="Marketing" <?php echo ($job['kategori'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Design" <?php echo ($job['kategori'] ?? '') == 'Design' ? 'selected' : ''; ?>>Design</option>
                                        <option value="Sales" <?php echo ($job['kategori'] ?? '') == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Finance" <?php echo ($job['kategori'] ?? '') == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    </select>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span> Lokasi Pekerjaan</label>
                                        <input type="text" class="form-control" name="lokasi" placeholder="Contoh: Jakarta Pusat"
                                            value="<?php echo htmlspecialchars($job['lokasi'] ?? ''); ?>" required>
                                    </div>

                                    <!-- PERUBAHAN: Tipe Pekerjaan dipindah ke sini (menggantikan mode kerja) -->
                                    <div class="form-group">
                                        <label class="form-label"><span class="required">*</span> Tipe Pekerjaan</label>
                                        <select class="form-select" name="tipe_pekerjaan" required>
                                            <option value="">Pilih Tipe Pekerjaan</option>
                                            <option value="full-time" <?php echo (isset($job['tipe_pekerjaan']) && strtolower($job['tipe_pekerjaan']) == 'full-time') ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="part-time" <?php echo (isset($job['tipe_pekerjaan']) && strtolower($job['tipe_pekerjaan']) == 'part-time') ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="contract" <?php echo (isset($job['tipe_pekerjaan']) && strtolower($job['tipe_pekerjaan']) == 'contract') ? 'selected' : ''; ?>>Contract</option>
                                            <option value="internship" <?php echo (isset($job['tipe_pekerjaan']) && strtolower($job['tipe_pekerjaan']) == 'internship') ? 'selected' : ''; ?>>Internship</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- HAPUS: Field Mode Kerja dihapus sesuai permintaan -->
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Deskripsi Pekerjaan</label>
                                    <textarea class="form-control" name="deskripsi" rows="5" required><?php echo htmlspecialchars($job['deskripsi'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Kualifikasi</label>
                                    <textarea class="form-control" name="kualifikasi" rows="4" placeholder="Masukkan kualifikasi yang dibutuhkan..."><?php echo htmlspecialchars($job['kualifikasi'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Benefit</label>
                                    <textarea class="form-control" name="benefit" rows="4" placeholder="Masukkan benefit yang ditawarkan..."><?php echo htmlspecialchars($job['benefit'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Batas Tanggal</label>
                                    <input type="date" class="form-control" name="batas_tanggal"
                                        value="<?php echo $job['batas_tanggal'] ?? ''; ?>">
                                    <small class="text-muted" style="display: block; margin-top: 6px; font-size: 12px;">
                                        Lowongan akan otomatis expired setelah tanggal ini
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateForm() {
            let isValid = true;
            const requiredFields = document.querySelectorAll('#editJobForm [required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Harap isi semua field yang wajib diisi!');
            }
            
            return isValid;
        }
        
        // Remove invalid class when user starts typing
        document.querySelectorAll('#editJobForm input, #editJobForm textarea, #editJobForm select').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>