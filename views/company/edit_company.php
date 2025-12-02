<?php
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
        // SESUAIKAN DENGAN STRUKTUR TABEL YANG SEBENARNYA
        $companyUpdateData = [
            'id_pengguna' => $user_id,
            'nama_perusahaan' => $nama_perusahaan,
            'deskripsi' => $deskripsi_perusahaan, // PERUBAHAN: 'deskripsi_perusahaan' -> 'deskripsi'
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
                
                // Sesuaikan dengan policy: upload ke folder dengan nama user_id
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
            // DEBUG: Log data yang akan disimpan
            error_log("Data yang akan disimpan ke perusahaan: " . print_r($companyUpdateData, true));
            
            if (!empty($companyData)) {
                // Update existing company
                $updateResult = supabaseUpdate('perusahaan', $companyUpdateData, 'id_pengguna', $user_id);
            } else {
                // Insert new company
                $updateResult = supabaseInsert('perusahaan', $companyUpdateData);
            }

            // DEBUG: Log hasil operasi
            error_log("Hasil operasi perusahaan: " . print_r($updateResult, true));

            if ($updateResult['success']) {
                $message = 'Profil perusahaan berhasil disimpan! Menunggu persetujuan admin.';
                // Redirect setelah 2 detik ke waiting approval
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "waiting_approval.php";
                    }, 2000);
                </script>';
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

<!-- HTML content tetap sama seperti sebelumnya -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Perusahaan - Karirku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* CSS styles tetap sama seperti sebelumnya */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8f9fa;
        }

        /* Custom Navbar Styles */
        .custom-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
        }

        .navbar-logo img {
            height: 40px;
            width: auto;
        }

        .profile-edit-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #2b3940;
        }

        .back-arrow:hover {
            background-color: #003399;
            color: white;
            border-color: #003399;
        }

        .page-title {
            color: #003399;
            font-weight: 700;
            margin-bottom: 10px;
            padding-top: 20px;
            font-size: 28px;
        }

        .page-subtitle {
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 16px;
        }

        .form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }

        .form-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #2b3940;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .required {
            color: #dc3545;
            margin-left: 2px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #003399;
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 153, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Logo Upload Section */
        .logo-upload-section {
            margin-bottom: 30px;
        }

        .logo-upload-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .logo-upload {
            width: 108px;
            height: 104px;
            border: 2px dashed #6B7280;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: white;
            position: relative;
            overflow: hidden;
        }

        .logo-upload:hover {
            border-color: #003399;
            background-color: #f0f4ff;
        }

        .logo-upload img.upload-icon {
            width: 38px;
            height: 32px;
            opacity: 0.6;
        }

        .logo-upload p {
            margin: 0;
            color: #002E92;
            font-size: 10px;
            text-align: center;
        }

        .logo-upload.has-image {
            border-style: solid;
            border-color: #003399;
        }

        .logo-upload img.preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .logo-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-item {
            display: flex;
            gap: 8px;
            font-size: 8px;
            color: #B1B1B1;
            align-items: flex-start;
        }

        .bullet {
            color: #B1B1B1;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #003399;
            border: none;
            padding: 12px 40px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #002266;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 51, 153, 0.3);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }

        /* Drag Overlay */
        .drag-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 51, 153, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }

        .drag-overlay.active {
            display: flex;
        }

        .drag-content {
            text-align: center;
            color: white;
        }

        .drag-content i {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        .drag-content h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .drag-content p {
            font-size: 16px;
            opacity: 0.9;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @media (max-width: 992px) {
            .form-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .logo-upload-container {
                flex-direction: column;
            }

            .logo-upload {
                width: 100%;
                max-width: 200px;
            }
        }
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

    <!-- Custom Navbar Start -->
    <nav class="custom-navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-logo">
                <img src="../../assets/img/karirkuperusahaan.png" alt="Karirku Logo">
            </a>
        </div>
    </nav>
    <!-- Custom Navbar End -->

    <div class="container">
        <div class="profile-edit-container">
            <!-- Back Arrow -->
            <a href="profile.php" class="back-arrow">
                <i class="bi bi-arrow-left"></i>
            </a>

            <h2 class="text-center page-title">Lengkapi Profil Perusahaan</h2>
            <p class="text-center page-subtitle">Tambahkan informasi profil perusahaan</p>

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
                <!-- Form Section dengan 2 Kolom -->
                <div class="form-section">
                    <!-- Kolom Kiri -->
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label"><span class="required">*</span>Nama Perusahaan</label>
                            <input type="text" name="nama_perusahaan" class="form-control"
                                placeholder="Contoh: PT. Karirku Indonesia" 
                                value="<?php echo htmlspecialchars($companyData['nama_perusahaan'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><span class="required">*</span>Deskripsi Perusahaan</label>
                            <textarea name="deskripsi_perusahaan" class="form-control"
                                placeholder="Tulis deskripsi singkat tentang perusahaan Anda..." required><?php echo htmlspecialchars($companyData['deskripsi_perusahaan'] ?? ''); ?></textarea>
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
                            <label class="form-label"><span class="required">*</span>Email</label>
                            <input type="email" name="email" class="form-control"
                                placeholder="email@perusahaan.com" 
                                value="<?php echo htmlspecialchars($companyData['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><span class="required">*</span>Lokasi</label>
                            <input type="text" name="lokasi" class="form-control"
                                placeholder="Kota, Provinsi"
                                value="<?php echo htmlspecialchars($companyData['lokasi'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><span class="required">*</span>Nomor HP</label>
                            <input type="text" name="no_hp" class="form-control"
                                placeholder="08xxxxxxxxxx"
                                value="<?php echo htmlspecialchars($companyData['no_telp'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><span class="required">*</span>NPWP</label>
                            <input type="text" name="npwp" class="form-control"
                                placeholder="00.000.000.0-000.000"
                                value="<?php echo htmlspecialchars($companyData['npwp'] ?? ''); ?>" required>
                        </div>

                        <!-- Logo Upload Section -->
                        <div class="logo-upload-section">
                            <label class="form-label"><span class="required">*</span>Logo Perusahaan</label>
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
                        <i class="bi bi-check-circle me-2"></i>Simpan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview Logo
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file
                if (file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB');
                    e.target.value = '';
                    return;
                }

                // Validasi tipe file
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

                    // Remove old preview if exists
                    const oldPreview = logoUpload.querySelector('.preview');
                    if (oldPreview) oldPreview.remove();

                    // Add new preview
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview');
                    logoUpload.appendChild(img);

                    // Hide upload icon and text
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

                // Trigger change event
                const event = new Event('change');
                logoInput.dispatchEvent(event);
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>