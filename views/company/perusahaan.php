<?php
$activePage = 'perusahaan'; // Set halaman aktif

session_start();
require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Cek data perusahaan yang sudah ada
$existingCompany = supabaseQuery('perusahaan', [
    'select' => '*',
    'id_pengguna' => 'eq.' . $user_id
]);

$companyData = [];
if ($existingCompany['success'] && count($existingCompany['data']) > 0) {
    $companyData = $existingCompany['data'][0];
    $nama_perusahaan = $companyData['nama_perusahaan'] ?? 'Perusahaan';
    $logo_url = $companyData['logo_url'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_perusahaan = trim($_POST['nama_perusahaan'] ?? '');
    $deskripsi_perusahaan = trim($_POST['deskripsi_perusahaan'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $npwp = trim($_POST['npwp'] ?? '');

    // Validasi required fields
    if (empty($nama_perusahaan) || empty($deskripsi_perusahaan) || empty($email) || empty($lokasi) || empty($no_hp) || empty($npwp)) {
        $error = 'Semua field yang wajib diisi harus dilengkapi';
    } else {
        $companyUpdateData = [
            'id_pengguna' => $user_id,
            'nama_perusahaan' => $nama_perusahaan,
            'deskripsi' => $deskripsi_perusahaan,
            'website' => $website,
            'email' => $email,
            'lokasi' => $lokasi,
            'no_telp' => $no_hp,
            'npwp' => $npwp,
            'status_persetujuan' => 'menunggu'
        ];

        // Jika ini perusahaan baru, tambahkan timestamp
        if (empty($companyData)) {
            $companyUpdateData['dibuat_pada'] = date('Y-m-d H:i:s');
        }

        // Handle upload logo
        if (isset($_FILES['logo_perusahaan']) && $_FILES['logo_perusahaan']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_perusahaan'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Ukuran file terlalu besar. Maksimal 10MB.';
            } else {
                // Hapus logo lama jika ada
                if (!empty($companyData['logo_path'])) {
                    $deleteResult = supabaseStorageDelete('profile-pictures', $companyData['logo_path']);
                    if (!$deleteResult['success']) {
                        error_log("Gagal menghapus logo lama: " . $companyData['logo_path']);
                    }
                }

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'company_logo_' . $user_id . '_' . time() . '.' . $extension;
                $filePath = $user_id . '/' . $filename;

                $uploadResult = supabaseStorageUpload('profile-pictures', $filePath, $file);

                if ($uploadResult['success']) {
                    $publicUrl = getStoragePublicUrl('profile-pictures', $filePath);
                    $companyUpdateData['logo_url'] = $publicUrl;
                    $companyUpdateData['logo_path'] = $filePath;
                } else {
                    $error = 'Gagal mengupload logo. Silakan coba lagi. Error: ' . ($uploadResult['error'] ?? 'Unknown error');
                    error_log("Upload logo error: " . print_r($uploadResult, true));
                }
            }
        }

        if (empty($error)) {
            error_log("Data yang akan disimpan ke perusahaan: " . print_r($companyUpdateData, true));
            
            if (!empty($companyData)) {
                $updateResult = supabaseUpdate('perusahaan', $companyUpdateData, 'id_pengguna', $user_id);
            } else {
                $updateResult = supabaseInsert('perusahaan', $companyUpdateData);
            }

            error_log("Hasil operasi perusahaan: " . print_r($updateResult, true));

            if ($updateResult['success']) {
                $message = 'Profil perusahaan berhasil disimpan!';
                // Refresh data perusahaan
                $existingCompany = supabaseQuery('perusahaan', [
                    'select' => '*',
                    'id_pengguna' => 'eq.' . $user_id
                ]);
                if ($existingCompany['success'] && count($existingCompany['data']) > 0) {
                    $companyData = $existingCompany['data'][0];
                    $nama_perusahaan = $companyData['nama_perusahaan'] ?? 'Perusahaan';
                    $logo_url = $companyData['logo_url'] ?? '';
                }
            } else {
                $error = 'Gagal menyimpan profil perusahaan. Silakan coba lagi.';
                if (isset($updateResult['data'][0]['message'])) {
                    $error .= ' Detail: ' . $updateResult['data'][0]['message'];
                }
                error_log("Update company error: " . print_r($updateResult, true));
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
    <title>Edit Profil Perusahaan - Karirku</title>
    <link rel="stylesheet" href="../../assets/css/company/company.css">
    <link href="../../assets/img/karirkulogo.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/company/perusahaan.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
    </style>
</head>

<body>
    <!-- Drag and Drop Overlay -->
    <div class="drag-overlay" id="dragOverlay">
        <div class="drag-content">
            <i class="bi bi-cloud-upload"></i>
            <h3>Lepaskan logo di sini</h3>
            <p>Format: JPG, PNG, GIF, WEBP. Maksimal: 10MB</p>
        </div>
    </div>

    <div class="dashboard">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php include "topbar_company.php" ?>

            <!-- Content -->
            <div class="content">
                <div class="profile-edit-container">
                    <h2 class="page-title">Edit Profil Perusahaan</h2>
                    <p class="page-subtitle">Perbarui informasi profil perusahaan Anda</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="companyForm">
                        <div class="form-section">
                            <!-- Kolom Kiri -->
                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Nama Perusahaan</label>
                                    <input type="text" name="nama_perusahaan" class="form-control"
                                        placeholder="Contoh: PT. Karirku Indonesia" 
                                        value="<?php echo htmlspecialchars($companyData['nama_perusahaan'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Deskripsi Perusahaan</label>
                                    <textarea name="deskripsi_perusahaan" class="form-control"
                                        placeholder="Tulis deskripsi singkat tentang perusahaan Anda..." required><?php echo htmlspecialchars($companyData['deskripsi'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Website</label>
                                    <input type="url" name="website" class="form-control"
                                        placeholder="https://www.perusahaan.com"
                                        value="<?php echo htmlspecialchars($companyData['website'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="form-column">
                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Email</label>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="email@perusahaan.com" 
                                        value="<?php echo htmlspecialchars($companyData['email'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Lokasi</label>
                                    <input type="text" name="lokasi" class="form-control"
                                        placeholder="Kota, Provinsi"
                                        value="<?php echo htmlspecialchars($companyData['lokasi'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> Nomor HP</label>
                                    <input type="text" name="no_hp" class="form-control"
                                        placeholder="08xxxxxxxxxx"
                                        value="<?php echo htmlspecialchars($companyData['no_telp'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><span class="required">*</span> NPWP</label>
                                    <input type="text" name="npwp" class="form-control"
                                        placeholder="00.000.000.0-000.000"
                                        value="<?php echo htmlspecialchars($companyData['npwp'] ?? ''); ?>" required>
                                </div>

                                <!-- Logo Upload Section -->
                                <div class="logo-upload-section">
                                    <label class="form-label">Logo Perusahaan</label>
                                    <div class="logo-upload-container">
                                        <div class="logo-upload <?php echo !empty($companyData['logo_url']) ? 'has-image' : ''; ?>" 
                                             id="logoUpload" 
                                             onclick="document.getElementById('logoInput').click()">
                                            
                                            <?php if (!empty($companyData['logo_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($companyData['logo_url']); ?>" 
                                                     alt="Logo Perusahaan" class="preview">
                                                <img src="../../assets/img/tambah-foto.png" alt="" class="upload-icon" id="uploadIcon" style="display: none;">
                                                <p id="uploadText" style="display: none;">Tambahkan logo</p>
                                            <?php else: ?>
                                                <img src="../../assets/img/tambah-foto.png" alt="" class="upload-icon" id="uploadIcon">
                                                <p id="uploadText">Tambahkan logo</p>
                                            <?php endif; ?>
                                            
                                            <input type="file" id="logoInput" name="logo_perusahaan" accept="image/*" style="display: none;">
                                        </div>

                                        <div class="logo-info">
                                            <div class="info-item">
                                                <span class="bullet">•</span>
                                                <span>Upload logo 1:1</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="bullet">•</span>
                                                <span>Upload maks. 10MB</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="bullet">•</span>
                                                <span>Format: JPG, PNG, GIF, WEBP</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
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
        // Preview Logo
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB');
                    e.target.value = '';
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP');
                    e.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const logoUpload = document.getElementById('logoUpload');
                    logoUpload.classList.add('has-image');

                    const oldPreview = logoUpload.querySelector('.preview');
                    if (oldPreview) oldPreview.remove();

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview');
                    logoUpload.appendChild(img);

                    document.getElementById('uploadIcon').style.display = 'none';
                    document.getElementById('uploadText').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and Drop functionality
        const dragOverlay = document.getElementById('dragOverlay');
        const logoInput = document.getElementById('logoInput');
        let dragCounter = 0;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            document.body.addEventListener(eventName, () => {
                dragCounter++;
                dragOverlay.classList.add('active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            document.body.addEventListener(eventName, () => {
                dragCounter--;
                if (dragCounter === 0) {
                    dragOverlay.classList.remove('active');
                }
            }, false);
        });

        document.body.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                const file = files[0];

                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP');
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB');
                    return;
                }

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                logoInput.files = dataTransfer.files;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const logoUpload = document.getElementById('logoUpload');
                    logoUpload.classList.add('has-image');

                    const oldPreview = logoUpload.querySelector('.preview');
                    if (oldPreview) oldPreview.remove();

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview');
                    logoUpload.appendChild(img);

                    document.getElementById('uploadIcon').style.display = 'none';
                    document.getElementById('uploadText').style.display = 'none';
                }
                reader.readAsDataURL(file);

                const event = new Event('change');
                logoInput.dispatchEvent(event);
            }
        }
    </script>
</body>
</html>