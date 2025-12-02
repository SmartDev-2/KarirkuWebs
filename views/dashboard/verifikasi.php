<?php
require_once __DIR__ . '/supabase.php';

// --- ACTION: ACC atau TOLAK PERUSAHAAN ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'acc') {
        $status = "disetujui";
        $result = supabaseUpdate('perusahaan', ['status_persetujuan' => $status], 'id_perusahaan', $id);
    } elseif ($action == 'tolak') {
        $status = "ditolak";
        $result = supabaseUpdate('perusahaan', ['status_persetujuan' => $status], 'id_perusahaan', $id);
    }

    if ($result['success']) {
        header('Location: verifikasi.php');
        exit;
    }
}

// --- QUERY SEMUA DATA ---
$all_result = supabaseQuery('perusahaan', [
    'select' => '*',
    'order' => 'dibuat_pada.desc'
]);

// --- PROSES DATA ---
$all_companies = $all_result['success'] ? $all_result['data'] : [];

$list_pending = [];
$list_accepted = [];
$list_rejected = [];

foreach ($all_companies as $company) {
    $status = $company['status_persetujuan'] ?? 'menunggu';
    
    if ($status === 'menunggu') {
        $list_pending[] = $company;
    } elseif ($status === 'disetujui') {
        $list_accepted[] = $company;
    } elseif ($status === 'ditolak') {
        $list_rejected[] = $company;
    }
}

// --- HITUNG OVERDUE ---
$list_overdue = [];
$now = time();
$five_days = 5 * 24 * 60 * 60;

foreach ($list_pending as $row) {
    if (!empty($row['dibuat_pada'])) {
        $tgl_daftar = strtotime($row['dibuat_pada']);
        if ($tgl_daftar && ($now - $tgl_daftar) > $five_days) {
            $list_overdue[] = $row;
        }
    }
}

$activePage = 'verifikasi';
include 'header.php';
include 'sidebar.php';
include 'topbar.php';

function time_elapsed_string($datetime, $full = false) {
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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Perusahaan</title>
    <style>
        /* --- LAYOUT UTAMA --- */
        body { 
            background-color: #F7F8FC !important; 
            font-family: 'Inter', sans-serif !important; 
            margin: 0; 
            padding: 0; 
        }

        .main-content {
            background-color: #F7F8FC;
            min-height: 100vh;
            /* Margin top 70px karena tinggi topbar biasanya 70px */
            margin-top: 70px !important; 
            margin-left: 240px !important;
            /* Padding atas 10px supaya judul langsung terlihat tapi tidak nempel border browser */
            padding: 10px 30px 30px 30px !important;
            box-sizing: border-box;
            height: 100vh; 
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 992px) {
            .main-content { 
                margin-left: 0 !important; 
                padding: 15px !important; 
                height: auto; 
            }
        }

        /* JUDUL HALAMAN (DIPERBAIKI: Margin 0 agar mepet ke atas) */
        .page-title-custom {
            margin-top: 0 !important; 
            padding-top: 0 !important;
            margin-bottom: 20px; 
            /* Warna Biru sesuai request */
            color: #2B3674; 
            font-weight: 700;
            font-size: 20px;
            /* Hapus transform negative yang bikin hilang */
            position: relative;
        }

        /* GRID SYSTEM */
        .split-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 30px;
            align-items: stretch; 
            height: calc(100vh - 120px);
            min-height: 600px;
        }

        @media (max-width: 1200px) {
            .split-grid { 
                grid-template-columns: 1fr; 
                height: auto; 
                display: block; 
            }
        }

        .section-header {
            font-size: 14px; 
            font-weight: 700; 
            color: #2B3674; 
            text-align: center;
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid #f1f5f9; 
            text-transform: capitalize;
        }

        /* SCROLLBAR CUSTOM */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* --- LEFT COLUMN --- */
        .main-card-container {
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 12px;
            padding: 20px 25px;
            height: 100%; 
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .scroll-area-lg {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* --- RIGHT COLUMN --- */
        .right-stack {
            display: flex;
            flex-direction: column;
            gap: 25px;
            height: 100%;
        }

        .right-section-box {
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .scroll-area-sm {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* CARD ITEMS */
        .item-card {
            background: #fff; 
            border: 1px solid #E0E5F2; 
            border-radius: 12px;
            padding: 20px; 
            margin-bottom: 15px; 
            display: flex; 
            justify-content: space-between;
            align-items: center; 
            transition: all 0.2s; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .item-card:hover { 
            border-color: #5967FF; 
            background-color: #F8F9FF;
            box-shadow: 0 4px 6px rgba(89, 103, 255, 0.1); 
        }
        
        .item-info h5 { 
            margin: 0 0 5px 0; 
            font-size: 16px; 
            font-weight: 700; 
            color: #2B3674; 
        }
        
        .item-info span { 
            font-size: 12px; 
            color: #A3AED0; 
        }

        /* BUTTONS & BADGES */
        .btn-group-custom { 
            display: flex; 
            gap: 8px; 
            align-items: center; 
        }
        
        .btn-custom { 
            padding: 8px 20px; 
            border-radius: 8px; 
            font-size: 12px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            transition: background 0.2s; 
            border: 1px solid transparent; 
            text-align: center; 
            cursor: pointer;
        }
        
        /* TOMBOL DETAIL WARNA BIRU GELAP */
        .btn-detail { 
            background-color: #11047A; 
            color: white; 
        } 
        
        .btn-detail:hover { 
            background-color: #0d035e; 
            transform: translateY(-2px);
        }
        
        .mini-card { 
            background: #fff; 
            border: 1px solid #E2E8F0; 
            border-radius: 8px; 
            padding: 12px 15px; 
            margin-bottom: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .mini-card.success { border: 1px solid #86efac; } 
        .mini-card.danger { border: 1px solid #fca5a5; } 
        .mini-card.warning { border: 1px solid #fcd34d; }
        
        .mini-info h6 { 
            margin: 0; 
            font-size: 14px; 
            font-weight: 600; 
            color: #2B3674; 
        } 
        
        .mini-info small { 
            font-size: 11px; 
            color: #A3AED0; 
        }
        
        .status-pill { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 10px; 
            font-weight: 600; 
            text-transform: uppercase; 
        }
        
        .pill-success { background: #DCFCE7; color: #166534; } 
        .pill-danger { background: #FEE2E2; color: #991B1B; }
        
        .empty-state { 
            text-align: center; 
            color: #cbd5e1; 
            padding: 20px 0; 
            font-size: 14px; 
            font-weight: 500; 
            margin: auto; 
        }
    </style>
</head>

<body>
    <div class="main-content">
        <h4 class="page-title-custom">Verifikasi Perusahaan</h4>

        <div class="split-grid">

            <div class="main-card-container">
                <div class="section-header">
                    Permintaan Bergabung <span style="color:#ef4444">•</span>
                </div>

                <div class="scroll-area-lg">
                    <?php if (empty($list_pending)): ?>
                        <div class="empty-state">Tidak ada permintaan baru</div>
                    <?php else: ?>
                        <?php foreach ($list_pending as $row): 
                            $time_ago = !empty($row['dibuat_pada']) ? time_elapsed_string($row['dibuat_pada']) : '-';
                        ?>
                        <div class="item-card">
                            <div class="item-info">
                                <h5><?= htmlspecialchars($row['nama_perusahaan']) ?></h5>
                                <span><?= $time_ago ?></span>
                            </div>
                            <div class="btn-group-custom">
                                <a href="detail_perusahaan.php?id=<?= $row['id_perusahaan'] ?>" class="btn-custom btn-detail">
                                    Detail
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-stack">

                <div class="right-section-box">
                    <div class="section-header">Permintaan Diterima</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_accepted)): ?>
                            <div class="empty-state">Belum ada data</div>
                        <?php else: ?>
                            <?php foreach ($list_accepted as $row): ?>
                            <div class="mini-card success">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($row['nama_perusahaan']) ?></h6>
                                    <small>Disetujui</small>
                                </div>
                                <span class="status-pill pill-success">Aktif</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="right-section-box">
                    <div class="section-header">Permintaan Ditolak</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_rejected)): ?>
                            <div class="empty-state">Belum ada data</div>
                        <?php else: ?>
                            <?php foreach ($list_rejected as $row): ?>
                            <div class="mini-card danger">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($row['nama_perusahaan']) ?></h6>
                                    <small>Ditolak</small>
                                </div>
                                <span class="status-pill pill-danger">Ditolak</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="right-section-box">
                    <div class="section-header" style="color: #C05621;">Lewat 5 Hari</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_overdue)): ?>
                            <div class="empty-state">Aman</div>
                        <?php else: ?>
                            <?php foreach ($list_overdue as $row): 
                                $time_ago = !empty($row['dibuat_pada']) ? time_elapsed_string($row['dibuat_pada']) : '5 hari lalu';
                            ?>
                            <div class="mini-card warning">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($row['nama_perusahaan']) ?></h6>
                                    <small><?= $time_ago ?></small>
                                </div>
                                <a href="detail_perusahaan.php?id=<?= $row['id_perusahaan'] ?>" class="btn-custom btn-detail" style="padding: 4px 12px; font-size: 10px;">Detail</a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>