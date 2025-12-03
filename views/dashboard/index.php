<?php
// --- 1. SETUP KONEKSI ---
if (file_exists('supabase.php')) {
    include 'supabase.php';
} elseif (file_exists('../supabase.php')) {
    include '../supabase.php';
} else {
    function supabaseQuery($t, $p, $o = []) { return ['count' => 0, 'data' => []]; }
}

// --- FUNGSI UNTUK MENGAMBIL DATA PERTUMBUHAN USER PER BULAN ---
function getMonthlyUserGrowth() {
    $chart_data = [];
    
    // Ambil data untuk 7 bulan terakhir
    for ($i = 6; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        
        // Query untuk menghitung user yang dibuat dalam rentang bulan tersebut
        $result = supabaseQuery('pencaker', [
            'select' => 'id_pencaker',
            'dibuat_pada' => 'gte.' . $month_start,
            'dibuat_pada' => 'lte.' . $month_end
        ], ['count' => 'exact']);
        
        $chart_data[] = $result['count'] ?? 0;
    }
    
    return $chart_data;
}

// --- FUNGSI UNTUK MENGAMBIL DATA PELAMAR PER MINGGU ---
function getWeeklyApplyData() {
    $chart_data = [];
    
    // Ambil data untuk 7 hari terakhir
    for ($i = 6; $i >= 0; $i--) {
        $day_start = date('Y-m-d', strtotime("-$i days")) . ' 00:00:00';
        $day_end = date('Y-m-d', strtotime("-$i days")) . ' 23:59:59';
        
        // Query untuk menghitung lamaran yang dibuat dalam rentang hari tersebut
        // Gunakan filter yang tepat untuk kolom dibuat_pada
        $result = supabaseQuery('lamaran', [
            'select' => 'id_lamaran',
            'dibuat_pada' => 'gte.' . $day_start,
            'dibuat_pada' => 'lte.' . $day_end
        ], ['count' => 'exact']);
        
        $chart_data[] = $result['count'] ?? 0;
    }
    
    return $chart_data;
}

// --- 2. DATA FETCHING ---
// Hitung Total Data
$total_pengguna   = supabaseQuery('pencaker', ['select' => 'id_pencaker'], ['count' => 'exact'])['count'] ?? 0;

// Menghitung jumlah perusahaan yang sudah disetujui
$result_perusahaan = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan',
    'status_persetujuan' => 'eq.disetujui'
], ['count' => 'exact']);

$total_perusahaan = $result_perusahaan['count'] ?? 0;

// MODIFIKASI DI SINI: Hanya hitung lowongan dengan status 'publish'
$total_lowongan_result = supabaseQuery('lowongan', [
    'select' => 'id_lowongan',
    'status' => 'eq.publish'
], ['count' => 'exact']);

$total_lowongan = $total_lowongan_result['count'] ?? 0;
$total_pelamar    = supabaseQuery('lamaran', ['select' => 'id_lamaran'], ['count' => 'exact'])['count'] ?? 0;

// Ambil Data Lowongan Terbaru
$res_lowongan = supabaseQuery('lowongan', [
    'select' => '*, perusahaan(nama_perusahaan)',
    'order' => 'created_at.desc',
    'limit' => 3
]);
$list_lowongan = $res_lowongan['data'] ?? [];

// --- 3. DATA UNTUK GRAFIK ---
// Ambil data real untuk chart pertumbuhan user
$chart_user_data = getMonthlyUserGrowth();

// Ambil data real untuk chart lamaran per minggu
$chart_apply_data = getWeeklyApplyData();

// Debug: Tampilkan data untuk melihat hasil query
// echo "Debug - Data lamaran per hari:<br>";
// print_r($chart_apply_data);
// echo "<br>";

// Jika data real kosong atau error, gunakan data dummy
if (empty(array_filter($chart_user_data))) {
    $chart_user_data = [50, 75, 90, 130, 160, 210, 270];
}

if (empty(array_filter($chart_apply_data))) {
    $chart_apply_data = [28, 45, 38, 60, 75, 40, 35];
}

// Buat label hari untuk chart lamaran
$chart_apply_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $chart_apply_labels[] = date('D', strtotime("-$i days"));
}
// Konversi ke bahasa Indonesia
$hari_indonesia = [
    'Sun' => 'Min',
    'Mon' => 'Sen',
    'Tue' => 'Sel',
    'Wed' => 'Rab',
    'Thu' => 'Kam',
    'Fri' => 'Jum',
    'Sat' => 'Sab'
];
$chart_apply_labels = array_map(function($day) use ($hari_indonesia) {
    return $hari_indonesia[$day] ?? $day;
}, $chart_apply_labels);

// Untuk debugging, kita bisa cek data lamaran di database
$debug_result = supabaseQuery('lamaran', [
    'select' => 'id_lamaran, dibuat_pada',
    'order' => 'dibuat_pada.desc',
    'limit' => 10
]);

// echo "Debug - 10 data lamaran terbaru:<br>";
// foreach ($debug_result['data'] as $item) {
//     echo "ID: " . ($item['id_lamaran'] ?? 'N/A') . " - Tanggal: " . ($item['dibuat_pada'] ?? 'N/A') . "<br>";
// }

include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>

<style>
    /* --- GLOBAL LAYOUT --- */
    body { background-color: #F4F7FE; font-family: 'DM Sans', 'Inter', sans-serif; margin: 0; }
    
    .main-content {
        margin-top: 70px; margin-left: 240px; padding: 30px;
        min-height: 100vh; background-color: #F4F7FE;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }

    /* --- STAT CARD --- */
    .stat-card {
        background: white; border-radius: 16px; padding: 25px;
        text-align: center; height: 100%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.01); border: 1px solid white;
        transition: 0.3s; display: flex; flex-direction: column; justify-content: center;
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .stat-card.active { border: 2px solid #4318FF; background: #fff; }

    .stat-label { font-size: 14px; color: #A3AED0; font-weight: 500; margin-bottom: 5px; }
    .stat-value { font-size: 32px; font-weight: 800; color: #1B2559; margin: 0; line-height: 1.2; }
    .stat-sub { font-size: 13px; color: #707EAE; margin-top: 5px; }
    .text-blue-primary { color: #4318FF; }

    /* --- CHART --- */
    .chart-box {
        background: white; border-radius: 20px; padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02); height: 100%;
    }
    .chart-title { 
        font-size: 16px; font-weight: 700; color: #1B2559; 
        text-align: center; margin-bottom: 20px; 
    }

    /* --- LIST BAWAH --- */
    .list-section-title {
        font-size: 18px; font-weight: 800; color: #1B2559; 
        margin-top: 30px; margin-bottom: 20px; text-align: center;
    }

    .job-row-card {
        background: white; border: 1px solid #E0E5F2;
        border-radius: 16px; padding: 20px 30px; margin-bottom: 15px;
        display: flex; justify-content: space-between; align-items: center;
        transition: 0.2s;
    }
    .job-row-card:hover { border-color: #4318FF; box-shadow: 0 4px 15px rgba(67, 24, 255, 0.1); }

    .jr-title { font-size: 15px; font-weight: 800; color: #1B2559; min-width: 200px; }
    .jr-company { font-size: 14px; font-weight: 600; color: #2B3674; min-width: 150px; }
    .jr-loc { font-size: 14px; color: #1B2559; min-width: 100px; font-weight: 600; }
    .jr-date { font-size: 13px; color: #A3AED0; min-width: 100px; }
    
    .badge-pill { padding: 6px 20px; border-radius: 20px; font-size: 12px; font-weight: 700; min-width: 80px; text-align: center; }
    .bg-green { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
    .bg-purple { background: #E9D5FF; color: #6B21A8; border: 1px solid #D8B4FE; }
    
    @media (max-width: 768px) {
        .job-row-card { flex-direction: column; align-items: flex-start; gap: 10px; }
        .jr-title, .jr-company, .jr-loc, .jr-date { min-width: auto; }
        .badge-pill { align-self: flex-start; }
    }
</style>

<div class="main-content">
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card active">
                <div class="stat-label">Total pengguna</div>
                <div class="stat-value text-blue-primary"><?= number_format($total_pengguna) ?></div>
                <div class="stat-sub">terdaftar</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-label">Total perusahaan</div>
                <div class="stat-value text-blue-primary"><?= number_format($total_perusahaan) ?></div>
                <div class="stat-sub">aktif</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-label">Total lowongan aktif</div>
                <div class="stat-value text-blue-primary"><?= number_format($total_lowongan) ?></div>
                <div class="stat-sub">terdaftar</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-label">Total pelamar</div>
                <div class="stat-value text-blue-primary"><?= number_format($total_pelamar) ?></div>
                <div class="stat-sub">terdaftar</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="chart-box">
                <div class="chart-title">Pertumbuhan pengguna per bulan</div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-box">
                <div class="chart-title">Jumlah lamaran per minggu</div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="applyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="list-section-title">Lowongan Terbaru</div>
    <div class="card p-4 border-0 shadow-sm" style="border-radius: 20px;">
        <?php if (!empty($list_lowongan)): ?>
            <?php foreach($list_lowongan as $job): 
                $judul = htmlspecialchars($job['judul'] ?? 'Tanpa Judul');
                $pt = htmlspecialchars($job['perusahaan']['nama_perusahaan'] ?? 'Perusahaan');
                $lokasi = htmlspecialchars($job['lokasi'] ?? 'Lokasi');
                $tgl = isset($job['created_at']) ? date('d M Y', strtotime($job['created_at'])) : '-';
                $status = $job['status'] ?? 'aktif';
            ?>
            <div class="job-row-card">
                <div class="jr-title"><?= $judul ?></div>
                <div class="jr-company"><?= $pt ?></div>
                <div class="jr-loc"><?= $lokasi ?></div>
                <div class="jr-date"><?= $tgl ?></div>
                <?php if($status == 'aktif' || $status == 'publish'): ?>
                    <span class="badge-pill bg-green">Aktif</span>
                <?php else: ?>
                    <span class="badge-pill bg-purple"><?= ucfirst($status) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">Belum ada lowongan terbaru.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxUser = document.getElementById('userChart');
if (ctxUser) {
    new Chart(ctxUser, {
        type: 'line',
        data: {
            labels: [
                '<?php echo date("M", strtotime("-6 months")); ?>',
                '<?php echo date("M", strtotime("-5 months")); ?>',
                '<?php echo date("M", strtotime("-4 months")); ?>',
                '<?php echo date("M", strtotime("-3 months")); ?>',
                '<?php echo date("M", strtotime("-2 months")); ?>',
                '<?php echo date("M", strtotime("-1 months")); ?>',
                '<?php echo date("M"); ?>'
            ],
            datasets: [{
                label: 'Pengguna',
                data: <?= json_encode($chart_user_data) ?>,
                borderColor: '#4318FF',
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
                    gradient.addColorStop(0, 'rgba(67, 24, 255, 0.4)');
                    gradient.addColorStop(1, 'rgba(67, 24, 255, 0.0)');
                    return gradient;
                },
                borderWidth: 3, pointRadius: 0, fill: true, tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#A3AED0', font: { size: 10 } } },
                y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#E0E5F2' }, ticks: { color: '#A3AED0', font: { size: 10 } } }
            }
        }
    });
}

const ctxApply = document.getElementById('applyChart');
if (ctxApply) {
    new Chart(ctxApply, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_apply_labels) ?>,
            datasets: [{
                label: 'Lamaran',
                data: <?= json_encode($chart_apply_data) ?>,
                backgroundColor: '#4318FF', borderRadius: 5, barPercentage: 0.6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#A3AED0', font: { size: 10 } } },
                y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#E0E5F2' }, ticks: { color: '#A3AED0', font: { size: 10 } } }
            }
        }
    });
}
</script>
<?php include 'footer.php'; ?>