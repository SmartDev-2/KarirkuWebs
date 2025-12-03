<?php
// --- 1. LOGIKA PHP (BACKEND) ---
require_once __DIR__ . '/supabase.php';

// A. AMBIL PARAMETER FILTER DARI URL
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$startDate    = isset($_GET['start']) ? $_GET['start'] : '';
$endDate      = isset($_GET['end']) ? $_GET['end'] : '';

// B. QUERY KE TABEL RIWAYAT_LAPORAN
$params = [
    'select' => '*',
    'kategori' => 'eq.perusahaan', // Filter KHUSUS PERUSAHAAN
    'order' => 'created_at.desc'   // Urutkan dari yang terbaru
];

// C. LOGIKA FILTER STATUS (HANYA DITERIMA & DITOLAK)
if ($statusFilter == 'diterima') {
    $params['status'] = 'eq.disetujui';
} elseif ($statusFilter == 'ditolak') {
    // Status 'ditolak' di database mencakup penolakan awal maupun pemblokiran
    $params['status'] = 'eq.ditolak';
}

$result = supabaseQuery('riwayat_laporan', $params);
$raw_data = $result['success'] ? $result['data'] : [];

// D. FILTER TANGGAL (DILAKUKAN DI PHP)
$laporan_data = [];

if (!empty($startDate) && !empty($endDate)) {
    $startTs = strtotime($startDate . " 00:00:00");
    $endTs   = strtotime($endDate . " 23:59:59");

    foreach ($raw_data as $row) {
        $rowTs = strtotime($row['created_at']);
        if ($rowTs >= $startTs && $rowTs <= $endTs) {
            $laporan_data[] = $row;
        }
    }
} else {
    $laporan_data = $raw_data;
}

// E. FUNGSI WAKTU
function time_elapsed_string($datetime, $full = false) {
    if(empty($datetime)) return '-';
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

// F. SETUP HALAMAN
$activePage = 'laporan_perusahaan';
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

    /* INPUT TANGGAL (Radius disamakan dengan button detail) */
    .date-picker-box { position: relative; width: 250px; }
    .date-input {
        width: 100%; padding: 10px 15px; 
        border-radius: 10px; /* UBAH JADI 10px AGAR KOTAK */
        border: 1px solid #5967FF; outline: none; text-align: center;
        color: #2B3674; font-weight: 600; background: white; cursor: pointer; font-size: 13px;
    }
    .date-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #5967FF; }

    /* GRUP TOMBOL FILTER (Radius disamakan) */
    .status-group {
        display: flex; background: white; 
        border-radius: 10px; /* UBAH JADI 10px AGAR KOTAK */
        padding: 4px; border: 1px solid #E0E5F2;
    }
    .btn-status {
        border: none; background: transparent; padding: 8px 20px;
        border-radius: 8px; /* AGAK KOTAK DI DALAM GROUP */
        color: #A3AED0; font-weight: 600;
        font-size: 13px; cursor: pointer; transition: all 0.3s;
        text-decoration: none; display: inline-block;
    }
    .btn-status:hover { background-color: #F0F2FA; }
    .btn-status.active { 
        background-color: #11047A; color: white; 
        box-shadow: 0 4px 10px rgba(17, 4, 122, 0.2); 
    }

    .btn-reset {
        color: #E53E3E; padding: 8px 12px; border-radius: 8px; /* UBAH JADI 8px */
        cursor: pointer; font-size: 14px; display: flex; align-items: center;
    }
    .btn-reset:hover { background-color: #FFF5F5; }

    /* CARD LIST */
    .card-item {
        background: white; border: 1px solid #E0E5F2; border-radius: 12px;
        padding: 20px 25px; margin-bottom: 15px; display: flex;
        justify-content: space-between; align-items: center; transition: all 0.2s;
    }
    .card-item:hover { border-color: #5967FF; transform: translateX(5px); background-color: #F8F9FF; }

    .item-title { font-size: 16px; font-weight: 700; color: #2B3674; margin-bottom: 5px; }
    .item-sub { font-size: 13px; color: #A3AED0; margin-bottom: 0; }
    .item-time { font-size: 12px; color: #707EAE; font-style: italic; margin-top: 5px; display: block; }

    .btn-detail {
        background-color: #11047A; color: white; padding: 10px 25px;
        border-radius: 10px; /* ACUAN UTAMA */
        text-decoration: none; font-size: 12px;
        font-weight: 600; border: none; transition: 0.2s; display: inline-block;
    }
    .btn-detail:hover { background-color: #0d035e; color: white; transform: translateY(-2px); }
    
    .empty-state { text-align: center; padding: 50px; color: #A3AED0; }

    /* BADGES */
    .badge-status { font-size: 10px; padding: 2px 8px; border-radius: 6px; margin-left: 5px; font-weight: bold; text-transform: uppercase; }
    .badge-disetujui { background-color: #d1fae5; color: #065f46; }
    .badge-ditolak { background-color: #fee2e2; color: #991b1b; }
    .badge-diblokir { background-color: #450a0a; color: #fecaca; } 
    .badge-menunggu { background-color: #fef3c7; color: #92400e; }
</style>

<div class="main-content">
    
    <h3 class="page-header-title">Laporan Perusahaan</h3>

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

        <?php if(!empty($statusFilter) || (!empty($startDate) && !empty($endDate))): ?>
            <a href="laporan_perusahaan.php" class="btn-reset" title="Reset Filter">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="laporan-list">
        
        <?php if (empty($laporan_data)): ?>
            <div class="card-item" style="justify-content: center;">
                <div class="empty-state">
                    <i class="fas fa-file-excel" style="font-size: 30px; margin-bottom: 10px;"></i><br>
                    Tidak ada data ditemukan.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($laporan_data as $row): 
                $judul = htmlspecialchars($row['judul']); 
                $desc  = htmlspecialchars($row['deskripsi']); 
                $time  = time_elapsed_string($row['created_at']);
                $statusRaw = $row['status'] ?? '';
                $idPerusahaan = $row['referensi_id']; 

                // Tentukan Badge & Status
                $badgeClass = 'badge-menunggu';
                $statusDisplay = strtoupper($statusRaw); 

                // Deteksi Blokir
                $isBlokir = (stripos($judul, 'Blokir') !== false);

                if($isBlokir) {
                    $badgeClass = 'badge-diblokir';
                    $statusDisplay = 'DIBLOKIR';
                } elseif($statusRaw == 'disetujui') {
                    $badgeClass = 'badge-disetujui';
                    $statusDisplay = 'DISETUJUI';
                } elseif($statusRaw == 'ditolak') {
                    $badgeClass = 'badge-ditolak';
                    $statusDisplay = 'DITOLAK';
                }
            ?>
            <div class="card-item">
                <div>
                    <h5 class="item-title">
                        <?= $judul ?>
                        <span class="badge-status <?= $badgeClass ?>"><?= $statusDisplay ?></span>
                    </h5>
                    <p class="item-sub"><?= $desc ?></p>
                    <small class="item-time"><?= $time ?></small>
                </div>
                
                <a href="detail_perusahaan.php?id=<?= $idPerusahaan ?>" class="btn-detail">Detail</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);

    flatpickr("#dateRange", {
        mode: "range", dateFormat: "Y-m-d",
        defaultDate: ["<?= $startDate ?>", "<?= $endDate ?>"], 
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

    function applyStatus(status) {
        if(urlParams.get('status') === status) {
            urlParams.delete('status'); 
        } else {
            urlParams.set('status', status);
        }
        window.location.search = urlParams.toString();
    }
</script>

<?php require_once 'footer.php'; ?>