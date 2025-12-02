<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Cek apakah sudah punya profil
if (hasPencakerProfile($user_id)) {
    header('Location: profile.php');
    exit;
}

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

    $profileData = [
        'id_pengguna' => $user_id,
        'nama_lengkap' => $nama_lengkap,
        'email_pencaker' => $email_pencaker,
        'no_hp' => $no_hp,
        'alamat' => $alamat,
        'tanggal_lahir' => $tanggal_lahir,
        'gender' => $gender,
        'pengalaman_tahun' => (int)$pengalaman_tahun,
        'dibuat_pada' => date('Y-m-d H:i:s')
    ];

    $result = createPencakerProfile($profileData);

    if ($result['success']) {
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Gagal membuat profil. Silakan coba lagi.';
        if (isset($result['data']) && is_array($result['data'])) {
            $error .= ' Detail: ' . json_encode($result['data']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Profil - Karirku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .profile-create-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-icon {
            font-size: 60px;
            color: #003399;
            margin-bottom: 20px;
        }

        .welcome-title {
            color: #003399;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-text {
            color: #6c757d;
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

        .btn-primary {
            background-color: #003399;
            border: none;
            padding: 15px 50px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #002266;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="profile-create-container">
            <div class="welcome-section">
                <i class="bi bi-person-circle welcome-icon"></i>
                <h2 class="welcome-title">Selamat Datang!</h2>
                <p class="welcome-text">Lengkapi profil Anda untuk mulai mencari pekerjaan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email_pencaker"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. HP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="no_hp"
                            placeholder="08xxxxxxxxxx" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" name="tanggal_lahir">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select class="form-select" name="gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="male" <?php echo ($pencaker['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo ($pencaker['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Perempuan</option>
                            <option value="other" <?php echo ($pencaker['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pengalaman Kerja (Tahun)</label>
                        <input type="number" class="form-control" name="pengalaman_tahun"
                            min="0" max="50" value="0">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"
                            placeholder="Masukkan alamat lengkap Anda"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-check-circle me-2"></i>Buat Profil
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>