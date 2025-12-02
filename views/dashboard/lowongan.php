<?php 
require_once __DIR__ . '/supabase.php';

// --- HITUNG JUMLAH DATA UNTUK BADGE NOTIFIKASI ---
$active_count = 0;
$pending_count = 0;

// 1. Hitung Lowongan Publish (Aktif)
$res_active = supabaseQuery('lowongan', [
    'select' => 'id_lowongan',
    'status' => 'eq.publish',
    'count'  => 'exact' // Meminta jumlah data
]);
if (isset($res_active['count'])) {
    $active_count = $res_active['count'];
}

// 2. Hitung Lowongan Menunggu Persetujuan (Ditinjau)
// Kita ambil status 'ditinjau' (sesuaikan jika ada status 'menunggu')
$res_pending = supabaseQuery('lowongan', [
    'select' => 'id_lowongan',
    'status' => 'eq.ditinjau',
    'count'  => 'exact'
]);
if (isset($res_pending['count'])) {
    $pending_count = $res_pending['count'];
}

$activePage = 'lowongan'; 
include 'header.php';
include 'sidebar.php';
include 'topbar.php';
?>

<style>
    /* --- LAYOUT UTAMA --- */
    body { background-color: #F4F7FE; font-family: 'Inter', sans-serif; }
    
    .main-content { 
        margin-top: 70px !important; 
        margin-left: 240px !important; 
        padding: 30px !important; 
        min-height: 100vh;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 20px !important; } }

    .page-title {
        font-size: 24px; font-weight: 700; color: #1B2559; margin-bottom: 30px;
    }

    /* --- GRID MENU --- */
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    /* --- KARTU MENU --- */
    .menu-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        display: flex;
        align-items: center;
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
        border: 1px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border-color: #5967FF;
    }

    /* Ikon Bulat Besar */
    .icon-box {
        width: 70px; height: 70px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
        margin-right: 20px;
        flex-shrink: 0;
    }

    /* Warna Tema Kartu 1 (Data Lowongan) */
    .theme-blue .icon-box { background: #E0E5F2; color: #11047A; }
    .theme-blue:hover .icon-box { background: #11047A; color: white; }

    /* Warna Tema Kartu 2 (Persetujuan) */
    .theme-orange .icon-box { background: #FFF4DE; color: #FF9F43; }
    .theme-orange:hover .icon-box { background: #FF9F43; color: white; }

    /* Teks */
    .menu-info h3 { margin: 0 0 5px 0; font-size: 18px; font-weight: 700; color: #1B2559; }
    .menu-info p { margin: 0; font-size: 14px; color: #A3AED0; }

    /* Counter Badge (Angka Besar) */
    .count-badge {
        position: absolute;
        right: 30px; top: 50%; transform: translateY(-50%);
        font-size: 32px; font-weight: 800;
        opacity: 0.1; /* Transparan */
        color: #1B2559;
    }
    .menu-card:hover .count-badge { opacity: 0.2; transform: translateY(-50%) scale(1.1); }

    /* Notifikasi Merah (Jika ada pending) */
    .notify-dot {
        position: absolute; top: 20px; right: 20px;
        background: #EE5D50; color: white;
        font-size: 10px; font-weight: 700;
        padding: 2px 8px; border-radius: 10px;
        box-shadow: 0 2px 5px rgba(238, 93, 80, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

</style>

<div class="main-content">
    
    <h1 class="page-title">Manajemen Lowongan</h1>

    <div class="menu-grid">

        <a href="data_lowongan.php" class="menu-card theme-blue">
            <div class="icon-box">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="menu-info">
                <h3>Data Lowongan</h3>
                <p>Lihat daftar lowongan yang sudah publish/aktif.</p>
            </div>
            <div class="count-badge"><?= $active_count ?></div>
        </a>

        <a href="persetujuan.php" class="menu-card theme-orange">
            <div class="icon-box">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="menu-info">
                <h3>Persetujuan</h3>
                <p>Validasi permintaan lowongan baru.</p>
            </div>
            <div class="count-badge"><?= $pending_count ?></div>

            <?php if($pending_count > 0): ?>
                <div class="notify-dot"><?= $pending_count ?> BARU</div>
            <?php endif; ?>
        </a>

    </div>

</div>

<?php include 'footer.php'; ?>