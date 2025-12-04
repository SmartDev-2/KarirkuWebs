<?php
require_once __DIR__ . '/supabase.php';

// --- 1. LOGIKA ACC / TOLAK LOWONGAN (Jika diakses langsung via URL) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_lowongan = (int)$_GET['id'];
    $action = $_GET['action'];

    // Tentukan status baru
    $status = ($action == 'acc') ? 'publish' : 'ditolak';

    // Update status di tabel lowongan
    $result = supabaseUpdate('lowongan', ['status' => $status], 'id_lowongan', $id_lowongan);

    if ($result['success']) {
        header('Location: persetujuan.php');
        exit;
    } else {
        echo "<script>alert('Gagal update status: " . $result['error'] . "');</script>";
    }
}

// --- 2. QUERY DATA LOWONGAN ---
$result = supabaseQuery('lowongan', [
    'select' => '*, perusahaan(*)', 
    'order'  => 'dibuat_pada.desc'
]);

$all_jobs = $result['success'] ? $result['data'] : [];

// --- 3. FILTER DATA ---
$list_pending  = [];
$list_active   = [];
$list_rejected = [];
$list_overdue  = [];

$now = time();
$limit_seconds = 5 * 24 * 60 * 60; // 5 Hari dalam detik

foreach ($all_jobs as $job) {
    $status = isset($job['status']) ? strtolower($job['status']) : 'ditinjau';
    $created_at_str = $job['dibuat_pada'] ?? date('Y-m-d H:i:s');
    $age = $now - strtotime($created_at_str);

    // LOGIKA PEMISAHAN
    if ($status == 'publish' || $status == 'aktif') {
        $list_active[] = $job;
    } 
    elseif ($status == 'ditolak') {
        $list_rejected[] = $job;
    } 
    elseif ($status == 'ditinjau' || $status == 'menunggu') {
        if ($age > $limit_seconds) {
            $list_overdue[] = $job;
        } else {
            $list_pending[] = $job;
        }
    }
}

// --- 4. FUNGSI WAKTU ---
function time_elapsed_string($datetime, $full = false) {
    if(empty($datetime)) return '-';
    try {
        $now = new DateTime; 
        $ago = new DateTime($datetime); 
        $diff = $now->diff($ago);
        
        // Calculate weeks separately
        $weeks = floor($diff->d / 7);
        $remaining_days = $diff->d - ($weeks * 7);
        
        $string = [
            'y' => 'thn',
            'm' => 'bln',
            'w' => 'mgg',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'mnt',
            's' => 'dtk'
        ];
        
        // Create array with values
        $values = [
            'y' => $diff->y,
            'm' => $diff->m,
            'w' => $weeks,
            'd' => $remaining_days,
            'h' => $diff->h,
            'i' => $diff->i,
            's' => $diff->s
        ];
        
        // Filter out zero values
        foreach ($string as $k => &$v) {
            if ($values[$k]) {
                $v = $values[$k] . ' ' . $v;
            } else {
                unset($string[$k]);
            }
        }
        
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' lalu' : 'Baru saja';
    } catch(Exception $e) { 
        return '-'; 
    }
}

$activePage = 'persetujuan'; 
require_once 'header.php';
require_once 'sidebar.php';
require_once 'topbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Lowongan</title>
    <style>
        body { background-color: #F7F8FC !important; font-family: 'Inter', sans-serif; margin: 0; }
        .main-content {
            background-color: #F7F8FC; min-height: 100vh;
            margin-top: 70px !important; margin-left: 240px !important;
            padding: 10px 30px 30px 30px !important;
            box-sizing: border-box; display: flex; flex-direction: column;
        }
        @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

        .page-title-custom {
            margin-top: 0 !important; padding-top: 0 !important; margin-bottom: 20px; 
            color: #2B3674; font-weight: 700; font-size: 20px; position: relative;
        }

        .split-grid {
            display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px;
            align-items: stretch; height: calc(100vh - 120px); min-height: 600px;
        }
        @media (max-width: 1200px) { .split-grid { grid-template-columns: 1fr; height: auto; display: block; } }

        .section-header {
            font-size: 14px; font-weight: 700; color: #2B3674; text-align: center;
            margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;
        }

        .main-card-container {
            background: white; border: 1px solid #E0E5F2; border-radius: 12px;
            padding: 20px 25px; height: 100%; display: flex; flex-direction: column;
        }
        .right-stack { display: flex; flex-direction: column; gap: 25px; height: 100%; }
        .right-section-box {
            background: white; border: 1px solid #E0E5F2; border-radius: 12px;
            padding: 20px; flex: 1; display: flex; flex-direction: column; min-height: 0;
        }
        .scroll-area-lg { flex: 1; overflow-y: auto; padding-right: 5px; }
        .scroll-area-sm { flex: 1; overflow-y: auto; padding-right: 5px; }

        .item-card {
            background: #fff; border: 1px solid #E0E5F2; border-radius: 12px;
            padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between;
            align-items: center; transition: all 0.2s;
        }
        .item-card:hover { border-color: #5967FF; background-color: #F8F9FF; }
        
        .item-info h5 { margin: 0 0 5px 0; font-size: 16px; font-weight: 700; color: #2B3674; }
        .item-info p { margin: 0 0 5px 0; font-size: 13px; color: #11047A; font-weight: 600; }
        .item-info span { font-size: 12px; color: #A3AED0; }

        .btn-detail { 
            background-color: #11047A; color: white; padding: 8px 20px; border-radius: 8px; 
            font-size: 12px; font-weight: 600; text-decoration: none; border: none; 
            cursor: pointer; transition: 0.2s;
        }
        .btn-detail:hover { background-color: #0d035e; transform: translateY(-2px); }

        .mini-card { 
            background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; 
            padding: 12px 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; 
        }
        .mini-card.success { border: 1px solid #86efac; } 
        .mini-card.danger { border: 1px solid #fca5a5; } 
        .mini-card.warning { border: 1px solid #fcd34d; }
        
        .mini-info h6 { margin: 0; font-size: 14px; font-weight: 600; color: #2B3674; } 
        .mini-info small { font-size: 11px; color: #A3AED0; }
        
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .pill-success { background: #DCFCE7; color: #166534; } 
        .pill-danger { background: #FEE2E2; color: #991B1B; }
        .empty-state { text-align: center; color: #cbd5e1; padding: 20px 0; font-size: 13px; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>

<body>
    <div class="main-content">
        <h4 class="page-title-custom">Persetujuan Lowongan</h4>

        <div class="split-grid">

            <div class="main-card-container">
                <div class="section-header">
                    Menunggu Persetujuan <span style="color:#ef4444">•</span>
                </div>

                <div class="scroll-area-lg">
                    <?php if (empty($list_pending)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                            Tidak ada lowongan baru.
                        </div>
                    <?php else: ?>
                        <?php foreach ($list_pending as $row): 
                            $judul = $row['judul'] ?? 'Tanpa Judul';
                            $pt = $row['perusahaan']['nama_perusahaan'] ?? 'Perusahaan';
                            $tgl = $row['dibuat_pada'] ?? '';
                            $time_ago = time_elapsed_string($tgl);
                        ?>
                        <div class="item-card">
                            <div class="item-info">
                                <h5><?= htmlspecialchars($judul) ?></h5>
                                <p><?= htmlspecialchars($pt) ?></p>
                                <span><?= $time_ago ?></span>
                            </div>
                            <div>
                                <a href="detail_persetujuan.php?id=<?= $row['id_lowongan'] ?>" class="btn-detail">Detail</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-stack">

                <div class="right-section-box">
                    <div class="section-header">Lowongan Aktif (Publish)</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_active)): ?>
                            <div class="empty-state">Belum ada</div>
                        <?php else: ?>
                            <?php foreach ($list_active as $row): 
                                $judul = $row['judul'] ?? 'Tanpa Judul';
                            ?>
                            <div class="mini-card success">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($judul) ?></h6>
                                    <small>Publish</small>
                                </div>
                                <span class="status-pill pill-success">Aktif</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="right-section-box">
                    <div class="section-header">Lowongan Ditolak</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_rejected)): ?>
                            <div class="empty-state">Belum ada</div>
                        <?php else: ?>
                            <?php foreach ($list_rejected as $row): 
                                $judul = $row['judul'] ?? 'Tanpa Judul';
                            ?>
                            <div class="mini-card danger">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($judul) ?></h6>
                                    <small>Ditolak</small>
                                </div>
                                <span class="status-pill pill-danger">Ditolak</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="right-section-box">
                    <div class="section-header" style="color: #C05621;">Lewat 5 Hari (Belum Ditinjau)</div>
                    <div class="scroll-area-sm">
                        <?php if (empty($list_overdue)): ?>
                            <div class="empty-state">Aman</div>
                        <?php else: ?>
                            <?php foreach ($list_overdue as $row): 
                                $judul = $row['judul'] ?? 'Tanpa Judul';
                                $time_ago = time_elapsed_string($row['dibuat_pada'] ?? '');
                            ?>
                            <div class="mini-card warning">
                                <div class="mini-info">
                                    <h6><?= htmlspecialchars($judul) ?></h6>
                                    <small><?= $time_ago ?></small>
                                </div>
                                <a href="detail_persetujuan.php?id=<?= $row['id_lowongan'] ?>" class="btn-detail" style="padding: 4px 12px; font-size:10px;">Cek</a>
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