<?php
// --- 1. SETUP KONEKSI ---
require_once __DIR__ . '/supabase.php';

$id_pengguna = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Variabel Default
$user_found = false;
$data = [];

// --- 2. AMBIL DATA USER (DARI TABEL PENCAKER) ---
// PERBAIKAN: Gunakan tabel 'pencaker' dan kolom 'id_pencaker'
if ($id_pengguna > 0) {
    $result = supabaseQuery('pencaker', [
        'select' => '*',
        'id_pencaker' => 'eq.' . $id_pengguna
    ]);

    if ($result['success'] && !empty($result['data'])) {
        $data = $result['data'][0];
        $user_found = true;
    }
}

// --- 3. SIAPKAN VARIABEL TAMPILAN ---
if ($user_found) {
    // Data User
    $nama  = htmlspecialchars($data['nama_lengkap'] ?? 'Tanpa Nama');
    // PERBAIKAN: Gunakan 'email_pencaker'
    $email = htmlspecialchars($data['email_pencaker'] ?? '-'); 
    
    $role  = 'Pencaker'; // Hardcode karena tabel khusus pencaker
    $telp  = htmlspecialchars($data['no_hp'] ?? '-');
    $status = htmlspecialchars($data['status'] ?? 'aktif'); // Default aktif
    $foto   = $data['foto_profil_url'] ?? ''; // Sesuai kolom DB

    // Format Tanggal
    $tgl_raw = $data['created_at'] ?? 'now';
    $join_date = date('d M Y', strtotime($tgl_raw));

    // Avatar Inisial
    $initial = strtoupper(substr($nama, 0, 1));

    // Logika Warna Status
    if ($status == 'aktif' || $status == 'verifikasi') {
        $status_color = '#05CD99'; // Hijau
        $status_text  = 'Terverifikasi';
    } else {
        $status_color = '#A3AED0'; // Abu-abu
        $status_text  = ucfirst($status);
    }
} else {
    // Data Dummy jika user hilang
    $nama = "User Tidak Ditemukan";
    $status_color = "#E53E3E"; 
}

$activePage = 'user';
include 'header.php';
include 'sidebar.php'; 
include 'topbar.php';
?>

<style>
    /* --- STYLE SAMA SEPERTI SEBELUMNYA --- */
    body { background-color: #F4F7FE !important; font-family: 'DM Sans', sans-serif !important; margin: 0; }

    .main-content {
        margin-left: 260px !important; 
        margin-top: 70px !important;   
        width: calc(100% - 260px);    
        height: calc(100vh - 70px);    
        display: flex; justify-content: center; align-items: center;
        padding: 20px; box-sizing: border-box;
    }

    @media (max-width: 1200px) {
        .main-content { margin-left: 0 !important; width: 100%; }
    }

    .profile-card {
        background: #FFFFFF; width: 100%; max-width: 400px;
        border-radius: 24px; padding: 40px 35px; position: relative; text-align: center;
        box-shadow: 0 20px 50px rgba(112, 144, 176, 0.12); border: 1px solid white;
    }

    .btn-back { position: absolute; top: 25px; left: 25px; color: #A3AED0; font-size: 20px; text-decoration: none; transition: 0.2s; }
    .btn-back:hover { color: #2B3674; transform: translateX(-3px); }

    .avatar-wrapper { margin-bottom: 15px; display: flex; justify-content: center; }
    .avatar-circle {
        width: 90px; height: 90px; border-radius: 50%; background-color: #E9EDF5;
        color: #1B2559; font-size: 36px; font-weight: 700; display: flex; align-items: center; justify-content: center;
        border: 4px solid #FFFFFF; box-shadow: 0 4px 15px rgba(0,0,0,0.05); object-fit: cover;
    }

    .user-name { font-size: 24px; font-weight: 700; color: #1B2559; margin-bottom: 5px; letter-spacing: -0.5px; }
    .user-role { font-size: 14px; font-weight: 500; color: #A3AED0; margin-bottom: 35px; }

    .info-list { display: flex; flex-direction: column; gap: 20px; text-align: left; margin-bottom: 35px; }
    .info-item { display: flex; align-items: center; gap: 15px; padding-bottom: 15px; border-bottom: 1px solid #F4F7FE; }
    .info-item:last-child { border-bottom: none; }

    .icon-box { width: 42px; height: 42px; background-color: #F4F7FE; color: #4318FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .label { font-size: 10px; font-weight: 700; color: #A3AED0; text-transform: uppercase; margin-bottom: 3px; }
    .value { font-size: 14px; font-weight: 700; color: #1B2559; }

    .btn-delete {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px;
        background-color: #FFF5F5; color: #E53E3E; font-weight: 700; font-size: 14px;
        border-radius: 16px; border: 1px solid #FED7D7; text-decoration: none; transition: 0.2s;
    }
    .btn-delete:hover { background-color: #FEE2E2; }

    .alert-box {
        background-color: #FFF5F5; border: 1px solid #FED7D7; color: #C53030;
        border-radius: 12px; padding: 15px; margin-bottom: 30px; margin-top: 15px; font-size: 13px;
    }
    .avatar-lost {
        width: 100px; height: 100px; border-radius: 50%; background-color: #FEE2E2; color: #C53030;
        font-size: 40px; font-weight: 700; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 15px auto; border: 5px solid #FFFFFF; box-shadow: 0 0 0 4px #FFF5F5;
    }
    .lost-title { font-size: 20px; font-weight: 700; color: #8F9BBA; margin-bottom: 5px; }
    .lost-sub { font-size: 14px; color: #A3AED0; }
</style>

<div class="main-content">
    
    <div class="profile-card">
        <a href="user.php" class="btn-back" title="Kembali"><i class="fas fa-arrow-left"></i></a>

        <?php if ($user_found): ?>
            <div class="avatar-wrapper">
                <?php if(!empty($foto)): ?>
                    <img src="<?= htmlspecialchars($foto) ?>" class="avatar-circle" alt="Foto">
                <?php else: ?>
                    <div class="avatar-circle"><?= $initial ?></div>
                <?php endif; ?>
            </div>

            <div class="user-name"><?= $nama ?></div>
            <div class="user-role"><?= $role ?></div>

            <div class="info-list">
                <div class="info-item">
                    <div class="icon-box"><i class="far fa-envelope"></i></div>
                    <div><div class="label">Email Address</div><div class="value"><?= $email ?></div></div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-phone-alt"></i></div>
                    <div><div class="label">Nomor Telepon</div><div class="value"><?= $telp ?></div></div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="far fa-calendar-alt"></i></div>
                    <div><div class="label">Tanggal Bergabung</div><div class="value"><?= $join_date ?></div></div>
                </div>
                <div class="info-item">
                    <div class="icon-box"><i class="fas fa-check-circle" style="color: <?= $status_color ?>;"></i></div>
                    <div><div class="label">Status Akun</div><div class="value" style="color: <?= $status_color ?>;"><?= $status_text ?></div></div>
                </div>
            </div>

            <a href="user.php?hapus=1&id=<?= $id_pengguna ?>" class="btn-delete" onclick="return confirm('PERINGATAN: User ini akan dihapus permanen. Lanjutkan?');">
                <i class="fas fa-trash-alt"></i> Hapus User Ini
            </a>

        <?php else: ?>
            <div class="alert-box">
                <strong><i class="fas fa-exclamation-triangle me-1"></i> Data Tidak Ditemukan</strong>
                User ini mungkin sudah dihapus dari database.
            </div>
            <div class="avatar-lost">!</div>
            <div class="lost-title">User Hilang</div>
            <div class="lost-sub">ID: <?= $id_pengguna ?></div>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'footer.php'; ?>