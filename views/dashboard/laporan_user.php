<?php
// --- 1. SETUP & KONEKSI ---
require_once __DIR__ . '/supabase.php';

// PAKSA TIMEZONE SERVER KE JAKARTA
date_default_timezone_set('Asia/Jakarta');

// A. AMBIL PARAMETER FILTER
$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate   = isset($_GET['end']) ? $_GET['end'] : '';

// B. QUERY DATABASE
// Kita ambil semua data dulu, baru difilter di PHP agar konversi waktunya akurat
$params = [
    'select'   => '*',
    'kategori' => 'eq.user',       
    'order'    => 'created_at.desc' 
];

$result = supabaseQuery('riwayat_laporan', $params);
$raw_data = $result['success'] ? $result['data'] : [];

// C. FILTER TANGGAL (LOGIKA YANG SUDAH DIPAKSA BENAR)
$laporan_data = [];

// Siapkan Timestamp Filter (Mulai 00:00:00 sampai 23:59:59)
$filterStartTs = 0;
$filterEndTs   = 0;

if (!empty($startDate) && !empty($endDate)) {
    $filterStartTs = strtotime($startDate . " 00:00:00");
    $filterEndTs   = strtotime($endDate . " 23:59:59");
}

foreach ($raw_data as $row) {
    // 1. Ambil String Waktu dari Database (Asumsi UTC)
    $waktuDB = $row['created_at']; 

    // 2. Konversi UTC ke Jakarta menggunakan DateTime Object (Paling Aman)
    $dt = new DateTime($waktuDB, new DateTimeZone('UTC')); // Baca sebagai UTC
    $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));    // Ubah ke Jakarta
    
    // 3. Ambil Timestamp lokal (WIB)
    $rowTimestamp = $dt->getTimestamp();

    // 4. LOGIKA FILTER
    if (!empty($startDate) && !empty($endDate)) {
        // Cek apakah timestamp baris ini masuk dalam range filter
        if ($rowTimestamp >= $filterStartTs && $rowTimestamp <= $filterEndTs) {
            $laporan_data[] = $row;
        }
    } else {
        // Jika tidak ada filter, masukkan semua
        $laporan_data[] = $row;
    }
}

// 2. FUNGSI WAKTU RELATIF (YANG SUDAH DIPAKSA BENAR)
function time_elapsed_string_fixed($datetime) {
    if (empty($datetime)) return '-';

    // Waktu Sekarang (Jakarta)
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    
    // Waktu Kejadian (Dari DB UTC -> Convert ke Jakarta)
    $ago = new DateTime($datetime, new DateTimeZone('UTC'));
    $ago->setTimezone(new DateTimeZone('Asia/Jakarta'));

    // Jika waktu kejadian lebih baru dari sekarang (mencegah error minus)
    if ($ago > $now) {
        return 'Baru saja';
    }

    $diff = $now->diff($ago);

    // Format output
    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

$activePage = 'laporan_user';
require_once 'header.php';
require_once 'topbar.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
    .main-content { margin-top: 70px !important; margin-left: 240px !important; padding: 10px 30px 30px 30px !important; min-height: 100vh; }
    @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

    .page-header-title { font-size: 20px; font-weight: 700; color: #2B3674; margin-top: 0 !important; margin-bottom: 25px; }

    /* FILTER WRAPPER */
    .filter-wrapper { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
    .filter-label { font-weight: 600; color: #A3AED0; font-size: 14px; }
    .date-picker-box { position: relative; width: 250px; }
    .date-input { width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #5967FF; outline: none; color: #2B3674; font-weight: 600; text-align: center; background: white; cursor: pointer; font-size: 13px; }
    .date-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #5967FF; pointer-events: none; }
    .btn-reset { color: #E53E3E; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; }
    .btn-reset:hover { background-color: #FFF5F5; }

    /* CARD ITEM STYLE */
    .card-item {
        background: white; border: 1px solid #E0E5F2; border-radius: 16px;
        padding: 20px; margin-bottom: 15px; 
        display: flex; align-items: flex-start; gap: 20px; 
        transition: all 0.2s;
    }
    .card-item:hover { border-color: #5967FF; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

    /* ICON BOX */
    .activity-icon-box {
        width: 50px; height: 50px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 20px;
    }
    .icon-red { background-color: #FEE2E2; color: #EF4444; }   
    .icon-blue { background-color: #DBEAFE; color: #3B82F6; }  
    .icon-green { background-color: #DCFCE7; color: #10B981; } 

    /* CONTENT */
    .content-area { flex-grow: 1; }
    .item-title { font-size: 16px; font-weight: 700; color: #1B2559; margin: 0 0 5px 0; }
    .item-sub { font-size: 13px; color: #707EAE; margin: 0; line-height: 1.5; }

    /* TIME AREA */
    .time-area { text-align: right; min-width: 140px; display: flex; flex-direction: column; justify-content: center; }
    .time-ago { font-size: 12px; font-weight: 700; color: #1B2559; margin-bottom: 3px; }
    .time-full { font-size: 11px; color: #A3AED0; }

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
        <?php if(!empty($startDate) && !empty($endDate)): ?>
            <a href="laporan_user.php" class="btn-reset" title="Reset Filter"><i class="fas fa-times"></i></a>
        <?php endif; ?>
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
                
                // --- PROSES WAKTU (FIXED) ---
                // 1. Buat object DateTime dari DB (UTC)
                $dt = new DateTime($row['created_at'], new DateTimeZone('UTC'));
                // 2. Ubah ke Jakarta
                $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                
                // 3. Format untuk tampilan
                $timeAgo = time_elapsed_string_fixed($row['created_at']);
                $rawTime = $dt->format('d M Y • H:i'); // Jam WIB yang benar

                // LOGIKA ICON
                $judulLower = strtolower($judul);
                if (strpos($judulLower, 'hapus') !== false || strpos($judulLower, 'delete') !== false) {
                    $iconClass = 'fa-trash-alt'; $colorClass = 'icon-red';
                } elseif (strpos($judulLower, 'baru') !== false || strpos($judulLower, 'daftar') !== false) {
                    $iconClass = 'fa-user-plus'; $colorClass = 'icon-green';
                } else {
                    $iconClass = 'fa-info-circle'; $colorClass = 'icon-blue';
                }
            ?>
            <div class="card-item">
                <div class="activity-icon-box <?= $colorClass ?>">
                    <i class="fas <?= $iconClass ?>"></i>
                </div>

                <div class="content-area">
                    <h5 class="item-title"><?= $judul ?></h5>
                    <p class="item-sub"><?= $desc ?></p>
                </div>

                <div class="time-area">
                    <span class="time-ago"><?= $timeAgo ?></span>
                    <span class="time-full"><?= $rawTime ?> WIB</span>
                </div>
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
            mode: "range", dateFormat: "Y-m-d", 
            defaultDate: ["<?= !empty($startDate) ? $startDate : '' ?>", "<?= !empty($endDate) ? $endDate : '' ?>"], 
            locale: { rangeSeparator: " to " },
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    let range = dateStr.split(' to ');
                    if(range.length === 2) {
                        urlParams.set('start', range[0]);
                        urlParams.set('end', range[1]);
                        window.location.search = urlParams.toString();
                    }
                }
            }
        });
    });
</script>

<?php require_once 'footer.php'; ?>