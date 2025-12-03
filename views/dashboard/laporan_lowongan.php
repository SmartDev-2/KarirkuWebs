<?php
// --- 1. SETUP & KONEKSI ---
require_once __DIR__ . '/supabase.php';

// PENTING: SET ZONA WAKTU KE INDONESIA (WIB)
// Ini akan membuat semua fungsi date() dan new DateTime() otomatis pakai waktu Jakarta
date_default_timezone_set('Asia/Jakarta');

// A. AMBIL PARAMETER FILTER
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'diterima'; 
$startDate    = isset($_GET['start']) ? $_GET['start'] : '';
$endDate      = isset($_GET['end']) ? $_GET['end'] : '';

// B. QUERY KE DATABASE
$params = [
    'select' => '*',
    'order' => 'dibuat_pada.desc'
];

if ($statusFilter == 'diterima') {
    $params['status'] = 'in.(publish,aktif)';
} elseif ($statusFilter == 'ditolak') {
    $params['status'] = 'eq.ditolak';
}

$result = supabaseQuery('lowongan', $params);
$raw_data = $result['success'] ? $result['data'] : [];

// C. FILTER TANGGAL (PHP)
$laporan_data = [];

// Konversi input filter ke Timestamp
$startTs = !empty($startDate) ? strtotime($startDate . " 00:00:00") : 0;
$endTs   = !empty($endDate) ? strtotime($endDate . " 23:59:59") : 0;

foreach ($raw_data as $row) {
    // Ambil string waktu dari DB
    $tglStr = $row['dibuat_pada'] ?? $row['created_at'];
    
    if ($tglStr) {
        // PERBAIKAN: Langsung baca sebagai waktu lokal (Jakarta)
        // Tidak perlu convert dari UTC lagi agar jam tidak loncat
        $rowTs = strtotime($tglStr); 
        
        // Cek Filter
        if ($startTs > 0 && $endTs > 0) {
            if ($rowTs >= $startTs && $rowTs <= $endTs) {
                $laporan_data[] = $row;
            }
        } else {
            $laporan_data[] = $row;
        }
    }
}

// 2. FUNGSI WAKTU PINTAR (VERSI FIX JAM)
function format_smart_time($datetime) {
    if (empty($datetime)) return '-';

    // 1. Waktu Sekarang (Jakarta)
    $now = new DateTime(); // Otomatis ikut default timezone (Jakarta)
    
    // 2. Waktu Database -> Baca langsung (Jangan di-convert dari UTC)
    // Asumsinya data di DB sudah disimpan dalam WIB atau format lokal
    $date = new DateTime($datetime); 

    // Format Tanggal Y-m-d untuk perbandingan hari
    $todayStr = $now->format('Y-m-d');
    $dateStr  = $date->format('Y-m-d');
    
    // Format Jam H:i
    $timeStr = $date->format('H:i'); 

    // Cek Hari Ini
    if ($todayStr == $dateStr) {
        return "Hari ini, " . $timeStr . " WIB"; 
    }
    
    // Cek Kemarin
    $yesterday = clone $now;
    $yesterday->modify('-1 day');
    if ($yesterday->format('Y-m-d') == $dateStr) {
        return "Kemarin, " . $timeStr . " WIB";
    }

    // Sisanya tanggal biasa
    return $date->format('d M Y • H:i') . " WIB";
}

$activePage = 'laporan_lowongan';
require_once 'header.php';
require_once 'topbar.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
    .main-content { 
        margin-top: 70px !important; margin-left: 240px !important; 
        padding: 10px 30px 30px 30px !important; min-height: 100vh;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }
    .page-header-title { font-size: 20px; font-weight: 700; color: #2B3674; margin-top: 0 !important; margin-bottom: 25px; }
    
    /* FILTER */
    .filter-wrapper { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
    .filter-label { font-weight: 600; color: #A3AED0; font-size: 14px; }
    
    .date-picker-box { position: relative; width: 250px; }
    .date-input { 
        width: 100%; padding: 10px 15px; 
        border-radius: 10px; border: 1px solid #5967FF; outline: none; text-align: center; 
        color: #2B3674; font-weight: 600; background: white; cursor: pointer; font-size: 13px; 
    }
    .date-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #5967FF; }
    
    .status-group { 
        display: flex; background: white; 
        border-radius: 10px; padding: 4px; border: 1px solid #E0E5F2; 
    }
    .btn-status { 
        border: none; background: transparent; padding: 8px 25px; 
        border-radius: 8px; color: #A3AED0; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.3s;
        text-decoration: none; display: inline-block;
    }
    .btn-status:hover { background-color: #F0F2FA; }
    .btn-status.active { background-color: #11047A; color: white; box-shadow: 0 4px 10px rgba(17, 4, 122, 0.2); }

    .btn-reset { color: #E53E3E; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; }
    .btn-reset:hover { background-color: #FFF5F5; }

    /* CARD ITEM */
    .card-item { background: white; border: 1px solid #E0E5F2; border-radius: 12px; padding: 20px 25px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
    .card-item:hover { border-color: #5967FF; transform: translateX(5px); background-color: #F8F9FF; }
    .item-title { font-size: 16px; font-weight: 700; color: #2B3674; margin-bottom: 5px; }
    .item-sub { font-size: 13px; color: #A3AED0; margin-bottom: 0; }
    
    /* TIME STYLE */
    .item-time { 
        font-size: 12px; color: #707EAE; font-style: normal; 
        font-weight: 600; margin-top: 5px; display: block; 
    }
    
    .btn-detail { background-color: #11047A; color: white; padding: 10px 25px; border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 600; border: none; transition: 0.2s; display: inline-block; }
    .btn-detail:hover { background-color: #0d035e; color: white; transform: translateY(-2px); }
    .empty-state { text-align: center; padding: 50px; color: #A3AED0; }

    /* BADGES */
    .badge-status { font-size: 10px; padding: 2px 8px; border-radius: 6px; margin-left: 5px; font-weight: bold; text-transform: uppercase; }
    .badge-publish { background-color: #d1fae5; color: #065f46; }
    .badge-ditolak { background-color: #fee2e2; color: #991b1b; }
</style>

<div class="main-content">
    <h3 class="page-header-title">Laporan Lowongan</h3>

    <div class="filter-wrapper">
        <span class="filter-label">Rentang waktu</span>
        <div class="date-picker-box">
            <input type="text" id="dateRange" class="date-input" placeholder="Pilih Tanggal">
            <i class="fas fa-calendar-alt date-icon"></i>
        </div>

        <div class="status-group">
            <a href="javascript:void(0)" onclick="applyStatus('diterima')" class="btn-status <?= $statusFilter == 'diterima' ? 'active' : '' ?>">
                Diterima
            </a>
            <a href="javascript:void(0)" onclick="applyStatus('ditolak')" class="btn-status <?= $statusFilter == 'ditolak' ? 'active' : '' ?>">
                Ditolak
            </a>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] != 'diterima' || (!empty($startDate) && !empty($endDate))): ?>
            <a href="laporan_lowongan.php" class="btn-reset" title="Reset Filter"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </div>

    <div class="laporan-list">
        <?php if (empty($laporan_data)): ?>
            <div class="card-item" style="justify-content: center;">
                <div class="empty-state">
                    <i class="fas fa-file-excel" style="font-size: 30px; margin-bottom: 10px;"></i><br>
                    Tidak ada data lowongan <?= $statusFilter ?>.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($laporan_data as $row): 
                $judul = htmlspecialchars($row['judul']);
                $lokasi = htmlspecialchars($row['lokasi'] ?? 'Indonesia');
                $tglStr = $row['dibuat_pada'] ?? $row['created_at'];
                
                // GUNAKAN FUNGSI BARU DISINI
                $timeDisplay = format_smart_time($tglStr);
                
                $status = strtolower($row['status']);
                $id = $row['id_lowongan'];

                // Badge Logic
                $badgeClass = ($status == 'publish' || $status == 'aktif') ? 'badge-publish' : 'badge-ditolak';
                $statusDisplay = ($status == 'publish') ? 'DITERIMA' : strtoupper($status);
            ?>
            <div class="card-item">
                <div>
                    <h5 class="item-title">
                        <?= $judul ?>
                        <span class="badge-status <?= $badgeClass ?>"><?= $statusDisplay ?></span>
                    </h5>
                    <p class="item-sub"><?= $lokasi ?></p>
                    <small class="item-time">
                        <i class="far fa-clock me-1"></i> <?= $timeDisplay ?>
                    </small>
                </div>
                <a href="detail_lowongan.php?id=<?= $id ?>" class="btn-detail">Detail</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        flatpickr("#dateRange", {
            mode: "range", 
            dateFormat: "Y-m-d",
            defaultDate: [
                "<?= !empty($startDate) ? $startDate : '' ?>", 
                "<?= !empty($endDate) ? $endDate : '' ?>"
            ],
            locale: { rangeSeparator: " to " },
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    let range = dateStr.split(' to ');
                    if(range.length === 2) {
                        urlParams.set('start', range[0]);
                        urlParams.set('end', range[1]);
                        if(!urlParams.has('status')) {
                            urlParams.set('status', 'diterima');
                        }
                        window.location.search = urlParams.toString();
                    }
                }
            }
        });
    });

    function applyStatus(status) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('status', status);
        window.location.search = urlParams.toString();
    }
</script>

<?php require_once 'footer.php'; ?>