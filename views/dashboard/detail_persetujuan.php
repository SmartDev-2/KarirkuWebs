<?php
// --- 1. LOGIKA PHP (TIDAK DIUBAH) ---
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

// Parsing Variables
$judul = htmlspecialchars($data['judul'] ?? $data['judul_pekerjaan'] ?? 'Tanpa Judul');
$deskripsi = nl2br(htmlspecialchars($data['deskripsi'] ?? '-'));
$kualifikasi = nl2br(htmlspecialchars($data['kualifikasi'] ?? '-'));
$gaji_min = isset($data['gaji_min']) ? number_format($data['gaji_min'], 0, ',', '.') : '0';
$gaji_max = isset($data['gaji_max']) ? number_format($data['gaji_max'], 0, ',', '.') : '0';
$tipe = htmlspecialchars($data['tipe_pekerjaan'] ?? 'Full Time');
$lokasi = htmlspecialchars($data['lokasi'] ?? '-');

// Status Logic
$statusRaw = $data['status'] ?? 'ditinjau';
$status = strtolower($statusRaw);
if (empty($status)) $status = 'ditinjau';

// Data Perusahaan
$pt_nama = htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? 'Perusahaan');
$pt_logo = $data['perusahaan']['logo_url'] ?? '';
$pt_web = htmlspecialchars($data['perusahaan']['website'] ?? '#');
$tgl_buat = date('d M Y', strtotime($data['dibuat_pada'] ?? 'now'));

// Include Header
include 'header.php'; 
?>

<style>
    /* --- RESET & LAYOUT --- */
    body { background-color: #F4F7FE; font-family: 'Inter', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
    .sidebar, .left-sidebar, #sidebar, .header-navbar, .topbar { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; background-color: #F4F7FE; min-height: 100vh; }

    /* --- HERO BANNER --- */
    .hero-banner {
        position: relative; width: 100%; height: 260px;
        background-image: url('../../assets/img/backgroundamin.png') !important;
        background-size: cover; background-position: center top;
        display: flex; align-items: center; padding-left: 5%;
    }
    

    .btn-back-simple {
        position: absolute; top: 30px; left: 40px; z-index: 50;
        color: white; font-size: 14px; font-weight: 600;
        background: rgba(255,255,255,0.15); padding: 8px 18px; border-radius: 50px;
        text-decoration: none; display: flex; align-items: center; gap: 8px;
        border: 1px solid rgba(255,255,255,0.25); transition: 0.3s;
    }
    .btn-back-simple:hover { background: rgba(255,255,255,0.3); color: white; }

    .glass-title-box {
        position: relative; z-index: 10; margin-top: 10px;
        display: inline-flex; align-items: center; gap: 12px;
    }
    .glass-bar { width: 5px; height: 28px; background-color: #05CD99; border-radius: 10px; }
    .glass-text { font-size: 26px; font-weight: 800; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }

    /* --- CONTAINER UTAMA --- */
    .profile-container { max-width: 1150px; margin: 0 auto; padding: 40px 25px; position: relative; z-index: 20; }

    /* --- LAYOUT HEADER KIRI (LOGO SEBELAH JUDUL) --- */
    .job-header-row {
        display: flex; gap: 20px; align-items: flex-start;
        margin-bottom: 30px;
    }

    .logo-box-standard {
        width: 80px; height: 80px; border-radius: 12px;
        background: white; border: 1px solid #E0E5F2;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; flex-shrink: 0;
    }

    .job-title { font-size: 28px; font-weight: 800; color: #1B2559; margin: 0 0 5px 0; line-height: 1.2; }
    .job-company { font-size: 16px; color: #11047A; font-weight: 600; }
    
    .tags-row { display: flex; gap: 10px; margin-top: 12px; }
    .tag-item { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .tag-blue { background: #E0E5F2; color: #1B2559; }
    .tag-green { background: #E6F9EB; color: #05CD99; }

    /* --- KONTEN TEKS --- */
    .content-section-title { 
        font-size: 16px; font-weight: 700; color: #1B2559; 
        margin: 30px 0 10px 0; text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 2px solid #E0E5F2; display: inline-block; padding-bottom: 5px;
    }
    .content-text { font-size: 15px; color: #2B3674; line-height: 1.8; text-align: justify; }

    /* --- PANEL KANAN (KOTAK AKSI) --- */
    .analysis-card {
        background: white; border-radius: 20px; padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
        position: sticky; top: 30px;
    }
    
    /* STATUS MENUNGGU JADI BIRU */
    .status-badge {
        text-align: center; padding: 10px; border-radius: 10px; 
        font-weight: 800; font-size: 14px; margin-bottom: 20px; letter-spacing: 1px;
    }
    /* Ganti warna oranye jadi Biru Tema */
    .st-wait { background: #E0E5F2; color: #11047A; border: 1px solid #11047A; } 
    .st-active { background: #E6F9EB; color: #05CD99; border: 1px solid #05CD99; }
    .st-reject { background: #FEE2E2; color: #EE5D50; border: 1px solid #EE5D50; }

    /* TOMBOL LEBIH KECIL/PROPORSIONAL */
    .btn-verify-group { display: flex; flex-direction: column; gap: 10px; }
    .btn-verify {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 10px; /* Padding dikecilkan */
        border-radius: 8px; font-size: 13px; font-weight: 700;
        text-decoration: none; border: none; cursor: pointer; transition: 0.2s;
    }
    
    .btn-accept { background: #05CD99; color: white; }
    .btn-accept:hover { background: #04b385; transform: translateY(-2px); }

    .btn-reject { background: #EE5D50; color: white; }
    .btn-reject:hover { background: #d64538; transform: translateY(-2px); }

    .btn-back { margin-top: 15px; color: #A3AED0; background: transparent; border: 1px solid #E0E5F2; }
    .btn-back:hover { border-color: #A3AED0; color: #1B2559; }

    /* Info Mini */
    .info-mini { margin-top: 25px; border-top: 1px solid #F4F7FE; padding-top: 15px; }
    .im-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .im-label { color: #A3AED0; font-weight: 600; }
    .im-val { color: #2B3674; font-weight: 700; }

</style>

<div class="main-content">
    
    <div class="hero-banner">
        <div class="hero-overlay"></div>
        
        <a href="persetujuan.php" class="btn-back-simple">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="glass-title-box" style="margin-left: 40px;">
            <div class="glass-bar"></div>
            <div class="glass-text">Detail Lowongan</div>
        </div>
    </div>

    <div class="profile-container">
        <div class="row">
            
            <div class="col-lg-8 mb-5">
                
                <div class="job-header-row">
                    <div class="logo-box-standard">
                        <?php if($pt_logo): ?>
                            <img src="<?= htmlspecialchars($pt_logo) ?>" style="width:100%; height:100%; object-fit:contain;">
                        <?php else: ?>
                            <i class="fas fa-building fa-2x" style="color:#CBD5E0;"></i>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h1 class="job-title"><?= $judul ?></h1>
                        <div class="job-company"><?= $pt_nama ?></div>
                        
                        <div class="tags-row">
                            <div class="tag-item tag-blue"><i class="far fa-clock"></i> <?= $tipe ?></div>
                            <div class="tag-item tag-green"><i class="fas fa-money-bill-wave"></i> Rp <?= $gaji_min ?> - <?= $gaji_max ?></div>
                        </div>
                    </div>
                </div>

                <div style="padding-right: 20px;">
                    <div class="content-section-title">Deskripsi Pekerjaan</div>
                    <div class="content-text"><?= $deskripsi ?></div>

                    <div class="content-section-title" style="margin-top: 35px;">Kualifikasi</div>
                    <div class="content-text"><?= $kualifikasi ?></div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="analysis-card">
                    
                    <div style="font-size:12px; font-weight:700; color:#A3AED0; margin-bottom:8px; text-transform:uppercase;">Status Saat Ini</div>
                    
                    <?php 
                        $stClass = 'st-wait'; $stLabel = 'MENUNGGU';
                        if($status == 'publish' || $status == 'aktif') { $stClass = 'st-active'; $stLabel = 'PUBLISH'; }
                        if($status == 'ditolak') { $stClass = 'st-reject'; $stLabel = 'DITOLAK'; }
                    ?>
                    <div class="status-badge <?= $stClass ?>">
                        <?= $stLabel ?>
                    </div>

                    <div class="btn-verify-group">
                        <?php if ($status == 'ditinjau' || $status == 'menunggu' || $status == 'pending'): ?>
                            
                            <a href="persetujuan.php?action=acc&id=<?= $id_lowongan ?>" class="btn-verify btn-accept" onclick="return confirm('Setujui & Publish?')">
                                <i class="fas fa-check"></i> Terima
                            </a>
                            <a href="persetujuan.php?action=tolak&id=<?= $id_lowongan ?>" class="btn-verify btn-reject" onclick="return confirm('Tolak Lowongan?')">
                                <i class="fas fa-times"></i> Tolak
                            </a>

                        <?php elseif ($status == 'publish' || $status == 'aktif'): ?>
                            
                            <a href="persetujuan.php?action=tolak&id=<?= $id_lowongan ?>" class="btn-verify btn-reject" onclick="return confirm('Batalkan Publish?')">
                                <i class="fas fa-ban"></i> Batalkan Publish
                            </a>

                        <?php elseif ($status == 'ditolak'): ?>
                            
                            <a href="persetujuan.php?action=acc&id=<?= $id_lowongan ?>" class="btn-verify btn-accept" onclick="return confirm('Pulihkan?')">
                                <i class="fas fa-redo"></i> Pulihkan Lowongan
                            </a>

                        <?php endif; ?>
                        
                        <a href="persetujuan.php" class="btn-verify btn-back">Kembali</a>
                    </div>

                    <div class="info-mini">
                        <div class="im-row">
                            <span class="im-label">Lokasi</span><span class="im-val"><?= $lokasi ?></span>
                        </div>
                        <div class="im-row">
                            <span class="im-label">Diposting</span><span class="im-val"><?= $tgl_buat ?></span>
                        </div>
                        <div class="im-row">
                            <span class="im-label">Website</span>
                            <span class="im-val">
                                <?php if($pt_web != '#' && $pt_web != '-'): ?>
                                    <a href="<?= $pt_web ?>" target="_blank" style="color:#4318FF; text-decoration:none;">Kunjungi <i class="fas fa-external-link-alt"></i></a>
                                <?php else: ?> - <?php endif; ?>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>