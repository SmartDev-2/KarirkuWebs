<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// Pastikan user sudah login
if (!isset($user_id)) {
    header('Location: login.php');
    exit;
}

$user = getUserById($user_id);
$pencaker = getPencakerByUserId($user_id);

// Tentukan apakah mode edit atau create
$isEdit = $pencaker ? true : false;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email_pencaker = trim($_POST['email_pencaker'] ?? $user['email']);
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_lahir = !empty(trim($_POST['tanggal_lahir'] ?? '')) ? $_POST['tanggal_lahir'] : null;
    $gender = $_POST['gender'] ?? '';
    $pengalaman_tahun = $_POST['pengalaman_tahun'] ?? 0;

    // Data yang akan diupdate atau diinsert
    $profileData = [
        'nama_lengkap' => $nama_lengkap,
        'email_pencaker' => $email_pencaker,
        'no_hp' => $no_hp,
        'alamat' => $alamat,
        'tanggal_lahir' => $tanggal_lahir,
        'gender' => $gender,
        'pengalaman_tahun' => (int)$pengalaman_tahun
    ];

    // Handle upload foto profil
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_profil'];

        // Validasi file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $filePath = $user_id . '/' . $filename;

            // Hapus foto lama jika ada (hanya di mode edit)
            if ($isEdit && !empty($pencaker['foto_profil_path'])) {
                supabaseStorageDelete('profile-pictures', $pencaker['foto_profil_path']);
            }

            // Upload foto baru
            $uploadResult = supabaseStorageUpload('profile-pictures', $filePath, $file);

            if ($uploadResult['success']) {
                $publicUrl = getStoragePublicUrl('profile-pictures', $filePath);
                $profileData['foto_profil_url'] = $publicUrl;
                $profileData['foto_profil_path'] = $filePath;
            } else {
                $error = 'Gagal mengupload foto profil. Silakan coba lagi.';
            }
        }
    }

    // Update data ke database jika tidak ada error
    if (empty($error)) {
        if ($isEdit) {
            // Update data yang sudah ada
            $result = updatePencakerProfile($pencaker['id_pencaker'], $profileData);
        } else {
            // Buat data baru
            $profileData['id_pengguna'] = $user_id;
            $profileData['dibuat_pada'] = date('Y-m-d H:i:s');
            $result = createPencakerProfile($profileData);
        }

        if ($result['success']) {
            // Update session
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            
            // Redirect ke halaman profil setelah 2 detik untuk memberi feedback
            header('Refresh: 2; URL=profile.php');
            $message = $isEdit ? 'Profil berhasil diperbarui! Mengarahkan ke halaman profil...' : 'Profil berhasil dibuat! Mengarahkan ke halaman profil...';
        } else {
            $error = $isEdit ? 'Gagal memperbarui profil. Silakan coba lagi.' : 'Gagal membuat profil. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Karirku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-edit-container {
            max-width: 900px;
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

        .profile-image-wrapper {
            position: relative;
            width: 150px;
            margin: 0 auto 20px;
        }

        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            border: 4px solid #f0f0f0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .add-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 35px;
            height: 35px;
            background-color: #003399;
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 51, 153, 0.3);
        }

        .add-photo-btn:hover {
            background-color: #002266;
            transform: scale(1.1);
        }

        .add-photo-btn i {
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        .add-photo-btn input[type=file] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

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

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .form-label {
            font-weight: 600;
            color: #2b3940;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #003399;
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 153, 0.1);
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

        .page-title {
            color: #003399;
            font-weight: 700;
            margin-bottom: 10px;
            padding-top: 20px;
        }

        .page-subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <!-- Drag and Drop Overlay -->
    <div class="drag-overlay" id="dragOverlay">
        <div class="drag-content">
            <i class="bi bi-cloud-upload"></i>
            <h3>Lepaskan foto di sini</h3>
            <p>Format: JPG, PNG, GIF, WEBP. Maksimal: 5MB</p>
        </div>
    </div>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
            <a href="index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                <img src="../assets/img/logo.png" alt="Karirku Logo">
            </a>

            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                <div class="navbar-nav ms-0 mt-1">
                    <a href="../index.php" class="nav-item nav-link active">Home</a>
                    <a href="job-list.php" class="nav-item nav-link">Cari Pekerjaan</a>
                </div>

                <div class="auth-buttons d-flex align-items-center">
                    <?php if ($isLoggedIn && isset($_SESSION['user_id'])): ?>
                        <?php
                        $pencaker = getPencakerByUserId($_SESSION['user_id']);
                        $fotoProfil = $pencaker['foto_profil_url'] ?? '';
                        ?>
                        <div class="dropdown">
                            <button class="btn user-dropdown dropdown-toggle text-white p-0 border-0 bg-transparent" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none !important; background-color: white !important;">
                                <?php if (!empty($fotoProfil)): ?>
                                    <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-dark" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
                                <li><a class="dropdown-item" href="my-applications.php"><i class="fas fa-briefcase me-2"></i>Lamaran Saya</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="views/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="register.php" class="btn-register">Register</a>
                        <a href="login.php" class="btn-login">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <div class="container">
        <div class="profile-edit-container">
            <!-- Back Arrow -->
            <a href="profile.php" class="back-arrow">
                <i class="bi bi-arrow-left"></i>
            </a>

            <h2 class="text-center page-title"><?php echo $isEdit ? 'Edit Profil' : 'Lengkapi Profil Anda'; ?></h2>
            <p class="text-center page-subtitle"><?php echo $isEdit ? 'Perbarui informasi profil Anda' : 'Lengkapi informasi profil Anda untuk mulai mencari pekerjaan'; ?></p>

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

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <!-- Foto Profil -->
                <div class="text-center mb-4">
                    <div class="profile-image-wrapper">
                        <img src="<?php echo !empty($pencaker['foto_profil_url']) ? htmlspecialchars($pencaker['foto_profil_url']) : '../assets/img/default-avatar.png'; ?>" 
                             alt="Profile" 
                             class="profile-image-preview" 
                             id="imagePreview">
                        <label class="add-photo-btn" title="Pilih foto profil">
                            <i class="bi bi-plus-lg"></i>
                            <input type="file" name="foto_profil" accept="image/*" id="fotoInput" onchange="previewImage(event)">
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2">Format: JPG, PNG, GIF, WEBP. Maksimal: 5MB</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" 
                               value="<?php echo htmlspecialchars($pencaker['nama_lengkap'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email_pencaker" 
                               value="<?php echo htmlspecialchars($pencaker['email_pencaker'] ?? $user['email']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. HP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="no_hp" 
                               value="<?php echo htmlspecialchars($pencaker['no_hp'] ?? ''); ?>" 
                               placeholder="08xxxxxxxxxx" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" name="tanggal_lahir" 
                               value="<?php echo htmlspecialchars($pencaker['tanggal_lahir'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="gender">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="male" <?php echo ($pencaker['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo ($pencaker['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Perempuan</option>
                            <option value="other" <?php echo ($pencaker['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pengalaman Kerja (Tahun)</label>
                        <input type="number" class="form-control" name="pengalaman_tahun" 
                               min="0" max="50" 
                               value="<?php echo htmlspecialchars($pencaker['pengalaman_tahun'] ?? '0'); ?>">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3" 
                                  placeholder="Masukkan alamat lengkap Anda"><?php echo htmlspecialchars($pencaker['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Validasi ukuran file
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB');
                    event.target.value = '';
                    return;
                }

                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP');
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        // Drag and Drop functionality
        const dragOverlay = document.getElementById('dragOverlay');
        const fotoInput = document.getElementById('fotoInput');
        let dragCounter = 0;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Show overlay when dragging
        ['dragenter', 'dragover'].forEach(eventName => {
            document.body.addEventListener(eventName, () => {
                dragCounter++;
                dragOverlay.classList.add('active');
            }, false);
        });

        // Hide overlay when not dragging
        ['dragleave', 'drop'].forEach(eventName => {
            document.body.addEventListener(eventName, () => {
                dragCounter--;
                if (dragCounter === 0) {
                    dragOverlay.classList.remove('active');
                }
            }, false);
        });

        // Handle dropped files
        document.body.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                const file = files[0];

                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WEBP');
                    return;
                }

                // Validasi ukuran file
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 5MB');
                    return;
                }

                // Set file to input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fotoInput.files = dataTransfer.files;

                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>