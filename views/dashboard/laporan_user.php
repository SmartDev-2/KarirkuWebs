<?php
require_once __DIR__ . '/supabase.php';

// 1. SETUP FILTER
$params = [
    'select' => '*',
    'kategori' => 'eq.user',       // Filter KHUSUS USER
    'order' => 'created_at.desc'   // Urutkan terbaru
];
$result = supabaseQuery('riwayat_laporan', $params);
$laporan_data = $result['success'] ? $result['data'] : [];

// 2. FUNGSI WAKTU (Lalu)
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return '-';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'thn', 'm' => 'bln', 'w' => 'mgg', 'd' => 'hari', 'h' => 'jam', 'i' => 'mnt', 's' => 'dtk');
    foreach ($string as $k => &$v) {
        if ($diff->$k) $v = $diff->$k . ' ' . $v; else unset($string[$k]);
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' lalu' : 'Baru saja';
}

$activePage = 'laporan_user';
require_once 'header.php';
require_once 'topbar.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    /* STYLE TEMA BIRU */
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
    .main-content { 
        margin-top: 70px !important; margin-left: 240px !important; 
        padding: 10px 30px 30px 30px !important; min-height: 100vh;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

    .page-header-title {
        font-size: 20px; font-weight: 700; color: #2B3674;
        margin-top: 0 !important; margin-bottom: 25px;
    }

    /* FILTER SECTION */
    .filter-wrapper {
        display: flex; justify-content: center; align-items: center;
        gap: 15px; margin-bottom: 30px; flex-wrap: wrap;
    }
    .filter-label { font-weight: 600; color: #A3AED0; font-size: 14px; }
    .date-picker-box { position: relative; width: 250px; }
    .date-input {
        width: 100%; padding: 10px 15px; border-radius: 30px; border: 1px solid #5967FF;
        outline: none; color: #2B3674; font-weight: 600; text-align: center; background: white; cursor: pointer; font-size: 13px;
    }
    .date-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #5967FF; pointer-events: none; }

    /* CARD ITEM */
    .card-item {
        background: white; border: 1px solid #E0E5F2; border-radius: 15px;
        padding: 20px 25px; margin-bottom: 15px; display: flex;
        justify-content: space-between; align-items: center; transition: all 0.2s;
    }
    .card-item:hover { border-color: #5967FF; transform: translateX(5px); background-color: #F8F9FF; }
    .item-title { font-size: 16px; font-weight: 700; color: #2B3674; margin-bottom: 5px; }
    .item-sub { font-size: 13px; color: #A3AED0; margin-bottom: 0; }
    .item-time { font-size: 12px; color: #707EAE; font-style: italic; margin-top: 5px; display: block; }
    .btn-detail {
        background-color: #11047A; color: white; padding: 10px 25px;
        border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 600; border: none;
    }
    .btn-detail:hover { background-color: #0d035e; transform: translateY(-2px); }
    .empty-state { text-align: center; padding: 50px; color: #A3AED0; width: 100%; }
</style>

<div class="main-content">
    <h3 class="page-header-title">Laporan Aktivitas User</h3>

    <div class="filter-wrapper">
        <span class="filter-label">Rentang waktu</span>
        <div class="date-picker-box">
            <input type="text" id="dateRange" class="date-input" placeholder="Pilih Tanggal">
            <i class="fas fa-calendar-alt date-icon"></i>
        </div>
    </div>

    <div class="laporan-list">
        <?php if (empty($laporan_data)): ?>
            <div class="card-item" style="justify-content: center;">
                <div class="empty-state">
                    <i class="fas fa-clipboard-list" style="font-size: 30px; margin-bottom: 10px;"></i><br>
                    Belum ada riwayat laporan user.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($laporan_data as $row): 
                $judul = htmlspecialchars($row['judul']); 
                $desc  = htmlspecialchars($row['deskripsi']);
                $time  = time_elapsed_string($row['created_at']);
                $rawTime = date('d M Y H:i', strtotime($row['created_at']));
            ?>
            <div class="card-item">
                <div>
                    <h5 class="item-title"><?= $judul ?></h5>
                    <p class="item-sub"><?= $desc ?></p>
                    <small class="item-time"><?= $time ?> (<?= $rawTime ?>)</small>
                </div>
                <button class="btn-detail" onclick="alert('Detail Aktivitas:\n<?= $judul ?>\n\n<?= $desc ?>')">Detail</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr("#dateRange", {
        mode: "range", dateFormat: "d/m/Y",
        defaultDate: [new Date().fp_incr(-30), new Date()], 
        locale: { rangeSeparator: " - " }
    });
</script>

<?php require_once 'footer.php'; ?>