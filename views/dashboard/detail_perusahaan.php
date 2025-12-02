<?php 
// --- 1. SETUP KONEKSI & LOGIKA ---
require_once __DIR__ . '/supabase.php';

$id_perusahaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_perusahaan == 0) {
    echo "<script>alert('ID tidak valid'); window.location='data_perusahaan.php';</script>";
    exit;
}

// Ambil data perusahaan
$result = supabaseQuery('perusahaan', [
    'select' => '*',
    'id_perusahaan' => 'eq.' . $id_perusahaan
]);

if (!$result['success'] || empty($result['data'])) {
    echo "<div style='text-align:center; padding:50px;'>Data tidak ditemukan. <a href='data_perusahaan.php'>Kembali</a></div>";
    exit;
}

$data = $result['data'][0];

// --- DATA PREPARATION ---
$nama_pt = htmlspecialchars($data['nama_perusahaan'] ?? 'Tanpa Nama');
$deskripsi = nl2br(htmlspecialchars($data['deskripsi'] ?? '-'));
$alamat = nl2br(htmlspecialchars($data['alamat'] ?? '-'));
$website = htmlspecialchars($data['website'] ?? '#');
$logo_url = $data['logo'] ?? ''; // Asumsi kolom logo
$email = htmlspecialchars($data['email'] ?? '-');
$telepon = htmlspecialchars($data['no_telp'] ?? '-');
$npwp = htmlspecialchars($data['npwp'] ?? '-');
$visi_misi = nl2br(htmlspecialchars($data['visi_misi'] ?? '-'));

// Status Logic (Sesuaikan dengan kolom database, misal: status_persetujuan)
$statusRaw = $data['status_persetujuan'] ?? 'menunggu'; 
$status = strtolower($statusRaw);

// Waktu Bergabung
$tgl_raw = isset($data['created_at']) ? strtotime($data['created_at']) : time();
$waktu_gabung = date('d M Y', $tgl_raw);

$activePage = 'data_perusahaan';
include 'header.php'; 
// include 'topbar.php'; // Opsional jika header sudah include topbar
?>

<style>
    /* --- RESET & LAYOUT UTAMA (Copy dari detail_lowongan) --- */
    body { background-color: #F8F9FC; font-family: 'DM Sans', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
    
    /* Sembunyikan elemen bawaan template jika mengganggu */
    .sidebar, .left-sidebar, #sidebar, .header-navbar, .topbar { display: none !important; }

    .main-content {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; max-width: 100% !important;
        min-height: 100vh;
    }

    /* --- HERO BANNER --- */
    .hero-banner {
        position: relative; width: 100%; height: 280px;
        background-image: url('../../assets/img/backgroundamin.png'); 
        background-size: cover; background-position: center;
        display: flex; align-items: center;
    }

    .hero-content {
        margin-left: 8%;
        position: relative;
        padding-left: 25px;
    }

    .hero-content::before {
        content: ''; position: absolute; left: 0; top: 5px; bottom: 5px;
        width: 8px; background-color: #003399;
        border-radius: 4px;
    }

    .hero-title {
        font-size: 36px; font-weight: 800; color: #002B7F;
        margin: 0; line-height: 1.2;
    }
    .hero-breadcrumb {
        font-size: 14px; color: #555; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;
    }

    /* --- CONTAINER KONTEN --- */
    .content-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* --- HEADER PROFIL (FLEXBOX) --- */
    .job-header-wrapper {
        display: flex; 
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .job-info-group { display: flex; gap: 25px; align-items: flex-start; }

    /* Logo Kotak Besar */
    .company-logo-box {
        width: 100px; height: 100px;
        background: #E8EBF2; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; flex-shrink: 0; border: 1px solid #ddd;
    }
    .company-logo-box img { width: 100%; height: 100%; object-fit: contain; }
    .company-logo-box i { font-size: 40px; color: #999; }

    /* Teks Header */
    .job-title-text {
        font-size: 28px; font-weight: 800; color: #003399;
        margin: 0 0 5px 0;
    }
    .job-meta-text {
        font-size: 16px; font-weight: 600; color: #555;
        margin-bottom: 8px; display: flex; align-items: center; gap: 15px;
    }
    .job-meta-text i { color: #A3AED0; margin-right: 5px; }

    /* Tags Info */
    .tags-line { display: flex; gap: 10px; font-size: 13px; color: #444; margin-top: 8px; font-weight: 500; }
    .tag-pill {
        background: #E6F9EB; color: #05CD99; padding: 5px 12px; border-radius: 6px;
        display: flex; align-items: center; gap: 5px;
    }
    .tag-pill.blue { background: #E0E5F2; color: #4318FF; }

    /* --- TOMBOL AKSI (KANAN) --- */
    .action-group {
        display: flex; flex-direction: column; gap: 10px; align-items: flex-end;
        min-width: 200px;
    }
    .btn-action {
        padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px;
        text-decoration: none; border: none; cursor: pointer; text-align: center;
        width: 100%; transition: 0.2s; display: inline-block;
    }
    
    .btn-acc { background-color: #05CD99; color: white; }
    .btn-acc:hover { background-color: #04b385; }

    .btn-reject { background-color: #B71C1C; color: white; }
    .btn-reject:hover { background-color: #920e0e; }

    .status-badge {
        padding: 8px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
        margin-bottom: 10px; text-transform: uppercase; text-align: center; width: 100%;
    }
    .st-wait { background: #FFECB3; color: #FF6F00; } 
    .st-active { background: #C8E6C9; color: #2E7D32; } 
    .st-reject { background: #FFCDD2; color: #C62828; }

    /* --- SECTION KONTEN --- */
    .address-section { margin-top: 10px; margin-bottom: 30px; }
    .address-header { 
        font-size: 18px; font-weight: 700; color: #333; margin-bottom: 8px;
        display: flex; align-items: center; gap: 8px;
    }
    .icon-orange { color: #FF5722; }
    .address-text { font-size: 16px; color: #555; line-height: 1.6; }

    .desc-header { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 10px; margin-top: 30px; }
    .desc-text { font-size: 16px; color: #555; line-height: 1.8; text-align: justify; }

    hr { border: 0; border-top: 1px solid #E0E0E0; margin: 30px 0; }

</style>

<div class="main-content">
    
    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-title">Detail Perusahaan</h1>
            <div class="hero-breadcrumb">DASHBOARD / PERUSAHAAN / DETAIL</div>
        </div>
    </div>

    <div class="content-container">
        
        <div class="job-header-wrapper">
            
            <div class="job-info-group">
                <div class="company-logo-box">
                    <?php if(!empty($logo_url)): ?>
                        <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="job-title-text">
                        <?= $nama_pt ?> 
                    </div>
                    
                    <div class="job-meta-text">
                        <span><i class="far fa-envelope"></i> <?= $email ?></span>
                        <span><i class="fas fa-phone-alt"></i> <?= $telepon ?></span>
                    </div>

                    <div class="tags-line">
                        <div class="tag-pill blue">
                            <i class="far fa-calendar-alt"></i> Gabung: <?= $waktu_gabung ?>
                        </div>
                        <?php if($npwp != '-'): ?>
                        <div class="tag-pill">
                            <i class="fas fa-file-invoice"></i> NPWP: <?= $npwp ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="action-group">
                <?php 
                    $stClass = 'st-wait'; $stLabel = 'MENUNGGU VERIFIKASI';
                    if($status == 'disetujui' || $status == 'aktif') { $stClass = 'st-active'; $stLabel = 'DISETUJUI / AKTIF'; }
                    if($status == 'ditolak') { $stClass = 'st-reject'; $stLabel = 'DITOLAK'; }
                ?>
                <div class="status-badge <?= $stClass ?>"><?= $stLabel ?></div>

                <?php if ($status == 'menunggu' || $status == 'pending'): ?>
                    <a href="verifikasi.php?action=acc&id=<?= $id_perusahaan ?>" class="btn-action btn-acc" onclick="return confirm('Terima & Verifikasi Perusahaan ini?')">
                        <i class="fas fa-check"></i> Terima
                    </a>
                    <a href="verifikasi.php?action=tolak&id=<?= $id_perusahaan ?>" class="btn-action btn-reject" onclick="return confirm('Tolak Perusahaan ini?')">
                        <i class="fas fa-times"></i> Tolak
                    </a>
                
                <?php elseif ($status == 'disetujui' || $status == 'aktif'): ?>
                    <a href="verifikasi.php?action=tolak&id=<?= $id_perusahaan ?>" class="btn-action btn-reject" onclick="return confirm('Batalkan Verifikasi / Blokir Perusahaan?')">
                        <i class="fas fa-ban"></i> Blokir / Batalkan
                    </a>

                <?php elseif ($status == 'ditolak'): ?>
                    <a href="verifikasi.php?action=acc&id=<?= $id_perusahaan ?>" class="btn-action btn-acc" onclick="return confirm('Pulihkan & Verifikasi?')">
                        <i class="fas fa-redo"></i> Pulihkan
                    </a>
                <?php endif; ?>
                
                <a href="data_perusahaan.php" style="font-size:12px; color:#777; margin-top:5px; text-decoration:none;">&larr; Kembali ke Daftar</a>
            </div>

        </div>

        <div class="address-section">
            <div class="address-header">
                <i class="fas fa-map-marker-alt icon-orange"></i> Alamat Lengkap
            </div>
            <div class="address-text">
                <?= $alamat ?>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-8">
                <div class="desc-header">Tentang Perusahaan</div>
                <div class="desc-text">
                    <?= $deskripsi ?>
                </div>

                <?php if($visi_misi != '-'): ?>
                <div class="desc-header">Visi & Misi</div>
                <div class="desc-text">
                    <?= $visi_misi ?>
                </div>
                <?php endif; ?>

                <?php if($website != '#' && $website != ''): ?>
                <div class="desc-header">Website</div>
                <div class="desc-text">
                    <a href="<?= $website ?>" target="_blank" style="color:#003399; text-decoration:underline;">
                        <?= $website ?> <i class="fas fa-external-link-alt" style="font-size:12px;"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once 'footer.php'; ?>