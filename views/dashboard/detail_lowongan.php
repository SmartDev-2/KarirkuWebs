<?php
// --- 1. LOGIKA PHP (TIDAK DIUBAH - TETAP MENGGUNAKAN LOGIC PERSETUJUAN) ---
require_once __DIR__ . '/supabase.php';

$id_lowongan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_lowongan == 0) {
    echo "<script>alert('ID tidak valid'); window.location='persetujuan.php';</script>";
    exit;
}

$result = supabaseQuery('lowongan', [
    'select' => '*, perusahaan(*)',
    'id_lowongan' => 'eq.' . $id_lowongan
]);

if (!$result['success'] || empty($result['data'])) {
    echo "<div style='text-align:center; padding:50px;'>Data tidak ditemukan. <a href='persetujuan.php'>Kembali</a></div>";
    exit;
}

$data = $result['data'][0];

// --- DATA PREPARATION ---
$judul = htmlspecialchars($data['judul'] ?? $data['judul_pekerjaan'] ?? 'Tanpa Judul');
$deskripsi = nl2br(htmlspecialchars($data['deskripsi'] ?? '-'));
$kualifikasi = nl2br(htmlspecialchars($data['kualifikasi'] ?? '-'));
$gaji_min = isset($data['gaji_min']) ? number_format($data['gaji_min'], 0, ',', '.') : '0';
$gaji_max = isset($data['gaji_max']) ? number_format($data['gaji_max'], 0, ',', '.') : '0';
$tipe = htmlspecialchars($data['tipe_pekerjaan'] ?? 'Full Time');
$lokasi = htmlspecialchars($data['lokasi'] ?? '-');

// Hitung waktu posting
$tgl_raw = isset($data['dibuat_pada']) ? strtotime($data['dibuat_pada']) : time();
$now = time();
$diff = $now - $tgl_raw;
$days_ago = floor($diff / (60 * 60 * 24));
if ($days_ago == 0) { $waktu_post = "Hari Ini"; }
elseif ($days_ago == 1) { $waktu_post = "1 Hari Lalu"; }
else { $waktu_post = $days_ago . " Hari Lalu"; }

// Status Logic
$statusRaw = $data['status'] ?? 'ditinjau';
$status = strtolower($statusRaw);

// Data Perusahaan
$pt_nama = htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? 'Perusahaan');
$pt_logo = $data['perusahaan']['logo_url'] ?? ''; 
$pt_web = htmlspecialchars($data['perusahaan']['website'] ?? '#');
$pt_alamat = htmlspecialchars($data['perusahaan']['alamat'] ?? $lokasi);

// Include Header
include 'header.php'; 
?>

<style>
    /* --- RESET & LAYOUT UTAMA --- */
    body { background-color: #F8F9FC; font-family: 'DM Sans', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
    
    /* Sembunyikan elemen bawaan template jika mengganggu */
    .sidebar, .left-sidebar, #sidebar, .header-navbar, .topbar { display: none !important; }

    .main-content {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; max-width: 100% !important;
        min-height: 100vh;
    }

    /* --- HERO BANNER (Persis Screenshot) --- */
    .hero-banner {
        position: relative; width: 100%; height: 280px;
        /* Ganti path gambar sesuai kebutuhan */
        background-image: url('../../assets/img/backgroundamin.png'); 
        background-size: cover; background-position: center;
        display: flex; align-items: center;
    }

    .hero-content {
        margin-left: 8%;
        position: relative;
        padding-left: 25px; /* Space untuk garis biru vertikal */
    }

    /* Garis Biru Vertikal Tebal di Kiri Judul Banner */
    .hero-content::before {
        content: ''; position: absolute; left: 0; top: 5px; bottom: 5px;
        width: 8px; background-color: #003399; /* Biru tua */
        border-radius: 4px;
    }

    .hero-title {
        font-size: 36px; font-weight: 800; color: #002B7F; /* Warna Biru Gelap */
        margin: 0; line-height: 1.2;
    }
    .hero-breadcrumb {
        font-size: 14px; color: #555; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px;
    }

    /* --- CONTAINER KONTEN (TANPA KOTAK PUTIH/CARD) --- */
    .content-container {
        max-width: 1100px;
        margin: 40px auto; /* Jarak dari banner */
        padding: 0 20px;
    }

    /* --- BAGIAN HEADER PEKERJAAN (FLEXBOX) --- */
    .job-header-wrapper {
        display: flex; 
        justify-content: space-between; /* Kiri info, Kanan tombol */
        align-items: flex-start;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .job-info-group {
        display: flex; gap: 25px; align-items: flex-start;
    }

    /* Logo Kotak Besar */
    .company-logo-box {
        width: 100px; height: 100px;
        background: #E8EBF2; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; flex-shrink: 0;
    }
    .company-logo-box img { width: 100%; height: 100%; object-fit: cover; }
    .company-logo-box i { font-size: 40px; color: #999; }

    /* Teks Header */
    .job-title-text {
        font-size: 28px; font-weight: 800; color: #003399; /* Biru Judul */
        margin: 0 0 5px 0;
    }
    .job-meta-text {
        font-size: 16px; font-weight: 700; color: #333; /* Nama PT Hitam Tebal */
        margin-bottom: 8px;
    }
    .job-time-ago {
        color: #888; font-weight: 400; font-size: 14px; margin-left: 10px;
    }

    /* Tags (Fulltime, Gaji) */
    .tags-line { display: flex; gap: 20px; font-size: 14px; color: #444; margin-top: 8px; font-weight: 500; }
    .tags-line i { margin-right: 6px; }
    .tag-salary { color: #2E7D32; font-weight: 600; } /* Hijau duit */

    /* --- TOMBOL AKSI (SEBELAH KANAN) --- */
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

    .btn-reject { background-color: #B71C1C; color: white; } /* Merah Tua */
    .btn-reject:hover { background-color: #920e0e; }

    .status-badge {
        padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
        margin-bottom: 10px; text-transform: uppercase;
    }
    .st-wait { background: #FFECB3; color: #FF6F00; } /* Kuning */
    .st-active { background: #C8E6C9; color: #2E7D32; } /* Hijau */
    .st-reject { background: #FFCDD2; color: #C62828; } /* Merah */

    /* --- ALAMAT SECTION (ORANYE) --- */
    .address-section { margin-top: 10px; margin-bottom: 40px; }
    .address-header { 
        font-size: 18px; font-weight: 700; color: #333; margin-bottom: 5px;
        display: flex; align-items: center; gap: 8px;
    }
    .icon-orange { color: #FF5722; } /* Ikon Oranye */
    .address-text { font-size: 16px; color: #555; line-height: 1.6; max-width: 800px; }

    /* --- DESKRIPSI SECTION --- */
    .desc-header { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 15px; margin-top: 30px; }
    .desc-text { font-size: 16px; color: #555; line-height: 1.8; text-align: justify; }

</style>

<div class="main-content">
    
    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-title">Detail Lowongan</h1>
            <div class="hero-breadcrumb">HOME / PAGES / DETAIL PEKERJAAN</div>
        </div>
    </div>

    <div class="content-container">
        
        <div class="job-header-wrapper">
            
            <div class="job-info-group">
                <div class="company-logo-box">
                    <?php if(!empty($pt_logo)): ?>
                        <img src="<?= htmlspecialchars($pt_logo) ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-briefcase"></i>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="job-title-text">
                        <?= $judul ?> 
                        <span style="font-size:14px; color:#999; font-weight:400; margin-left:10px;"><?= $waktu_post ?></span>
                    </div>
                    <div class="job-meta-text">
                        <?= $pt_nama ?>
                    </div>
                    <div class="tags-line">
                        <span><i class="far fa-clock"></i> <?= $tipe ?></span>
                        <span class="tag-salary"><i class="fas fa-money-bill-wave"></i> Rp <?= $gaji_min ?> - Rp <?= $gaji_max ?></span>
                    </div>
                </div>
            </div>

            <div class="action-group">
                <?php 
                    $stClass = 'st-wait'; $stLabel = 'MENUNGGU';
                    if($status == 'publish' || $status == 'aktif') { $stClass = 'st-active'; $stLabel = 'PUBLISH'; }
                    if($status == 'ditolak') { $stClass = 'st-reject'; $stLabel = 'DITOLAK'; }
                ?>
                <div class="status-badge <?= $stClass ?>"><?= $stLabel ?></div>

                <?php if ($status == 'ditinjau' || $status == 'menunggu' || $status == 'pending'): ?>
                    <a href="persetujuan.php?action=acc&id=<?= $id_lowongan ?>" class="btn-action btn-acc" onclick="return confirm('Publish lowongan ini?')">
                        Terima & Publish
                    </a>
                    <a href="persetujuan.php?action=tolak&id=<?= $id_lowongan ?>" class="btn-action btn-reject" onclick="return confirm('Tolak lowongan ini?')">
                        Tolak Lowongan
                    </a>
                
                <?php elseif ($status == 'publish' || $status == 'aktif'): ?>
                    <a href="persetujuan.php?action=tolak&id=<?= $id_lowongan ?>" class="btn-action btn-reject" onclick="return confirm('Batalkan publish?')">
                        Batalkan Publish
                    </a>

                <?php elseif ($status == 'ditolak'): ?>
                    <a href="persetujuan.php?action=acc&id=<?= $id_lowongan ?>" class="btn-action btn-acc" onclick="return confirm('Pulihkan lowongan?')">
                        Pulihkan
                    </a>
                <?php endif; ?>
                
                <a href="persetujuan.php" style="font-size:12px; color:#777; margin-top:5px; text-decoration:none;">&larr; Kembali</a>
            </div>

        </div>

        <div class="address-section">
            <div class="address-header">
                <i class="fas fa-map-marker-alt icon-orange"></i> Alamat
            </div>
            <div class="address-text">
                <?= nl2br($pt_alamat) ?>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #E0E0E0; margin: 30px 0;">

        <div class="row">
            <div class="col-md-8">
                <div class="desc-header">Deskripsi Pekerjaan</div>
                <div class="desc-text">
                    <?= $deskripsi ?>
                </div>

                <div class="desc-header">Kualifikasi</div>
                <div class="desc-text">
                    <?= $kualifikasi ?>
                </div>

                <?php if($pt_web != '#' && $pt_web != ''): ?>
                <div class="desc-header">Website</div>
                <div class="desc-text">
                    <a href="<?= $pt_web ?>" target="_blank" style="color:#003399; text-decoration:underline;">
                        <?= $pt_web ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once 'footer.php'; ?>