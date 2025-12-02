<?php
// config.php
$base_url = '../../';

// Mulai session di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include supabase.php dengan path yang benar
$supabase_path = __DIR__ . '/../../function/supabase.php';
if (file_exists($supabase_path)) {
    include $supabase_path;
} else {
    // Fallback ke data statis jika supabase tidak ditemukan
    $jobsData = [
        'semua' => [
            [
                'id' => 1,
                'title' => 'UI/UX Designer',
                'company' => 'PT Karirku Nusantara',
                'salary' => 'Rp 5.000.000 - Rp 8.000.000',
                'posted' => 'Diposting 10 hari lalu (Jul 14 at 9:41)',
                'applicants' => 'Berjumlah 50 Pelamar',
                'status' => 'publish',
                'statusClass' => 'status-publish',
                'statusLabel' => 'Publish'
            ]
        ],
        'live' => [],
        'perlu' => [],
        'draft' => [],
        'sedang' => []
    ];
    $jobsDataJson = json_encode($jobsData);
}

// Jika supabase berhasil diinclude, ambil data dari database
if (isset($supabase_url) && function_exists('supabaseQuery')) {
    // Ambil data lowongan dari database
    function getJobsDataFromDatabase() {
        // Query untuk mengambil data lowongan dengan join perusahaan
        $result = supabaseQuery('lowongan', [
            'select' => '*, perusahaan(nama_perusahaan)',
            'order' => 'dibuat_pada.desc'
        ]);

        if (!$result['success'] || !is_array($result['data'])) {
            error_log("Error fetching jobs: " . print_r($result, true));
            return [];
        }

        $allJobs = [];
        
        foreach ($result['data'] as $job) {
            // Pastikan status ada, jika tidak beri default
            $status = $job['status'] ?? 'ditinjau';
            
            // Hitung jarak hari
            $createdDate = new DateTime($job['dibuat_pada']);
            $currentDate = new DateTime();
            $interval = $currentDate->diff($createdDate);
            $daysAgo = $interval->days;
            
            // Format tanggal posting
            $formattedDate = date('M j \a\t g:i', strtotime($job['dibuat_pada']));
            $postedText = "Diposting {$daysAgo} hari lalu ({$formattedDate})";
            
            // Format gaji
            $salary = $job['gaji_range'] ?: 'Gaji tidak ditampilkan';
            
            // Tentukan status class dan label berdasarkan status di database
            $statusClass = '';
            $statusLabel = '';
            
            switch ($status) {
                case 'publish':
                    $statusClass = 'status-publish';
                    $statusLabel = 'Publish';
                    break;
                case 'ditinjau':
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
                    break;
                case 'draft':
                    $statusClass = 'status-draft';
                    $statusLabel = 'Draft';
                    break;
                case 'ditolak':
                    $statusClass = 'status-rejected';
                    $statusLabel = 'Ditolak';
                    break;
                default:
                    $statusClass = 'status-review';
                    $statusLabel = 'Ditinjau';
            }

            // Untuk performa, tampilkan jumlah pelamar untuk status publish, lainnya "Belum dipublikasi"
            $applicants = ($status === 'publish') ? 'Berjumlah 0 Pelamar' : 'Belum dipublikasi';

            $jobData = [
                'id' => $job['id_lowongan'],
                'title' => $job['judul'],
                'company' => $job['perusahaan']['nama_perusahaan'] ?? 'Perusahaan',
                'salary' => $salary,
                'posted' => $postedText,
                'applicants' => $applicants,
                'status' => $status,
                'statusClass' => $statusClass,
                'statusLabel' => $statusLabel,
                'location' => $job['lokasi'] ?? '',
                'type' => $job['tipe_pekerjaan'] ?? '',
                'description' => $job['deskripsi'] ?? '',
                'qualifications' => $job['kualifikasi'] ?? '',
                'category' => $job['kategori'] ?? '',
                'work_mode' => $job['mode_kerja'] ?? '',
                'benefits' => $job['benefit'] ?? '',
                'deadline' => $job['batas_tanggal'] ?? ''
            ];

            $allJobs[] = $jobData;
        }

        return $allJobs;
    }

    // Ambil semua data lowongan dari database
    $allJobs = getJobsDataFromDatabase();
    
    // Helper function untuk filter dengan pengecekan status yang aman
    function filterJobsByStatus($jobs, $targetStatus) {
        return array_values(array_filter($jobs, function($job) use ($targetStatus) {
            return isset($job['status']) && $job['status'] === $targetStatus;
        }));
    }
    
    // Siapkan data untuk JSON (semua data, filtering dilakukan di JavaScript)
    $jobsDataForJson = [
        'semua' => $allJobs,
        'live' => filterJobsByStatus($allJobs, 'publish'),
        'perlu' => filterJobsByStatus($allJobs, 'ditinjau'),
        'draft' => filterJobsByStatus($allJobs, 'draft'),
        'sedang' => filterJobsByStatus($allJobs, 'ditolak')
    ];

    $jobsDataJson = json_encode($jobsDataForJson);
    
    // Data untuk tampilan count di PHP (untuk ditampilkan di tab)
    $jobsData = [
        'semua' => $allJobs,
        'live' => array_filter($allJobs, function($job) { 
            return isset($job['status']) && $job['status'] === 'publish'; 
        }),
        'perlu' => array_filter($allJobs, function($job) { 
            return isset($job['status']) && $job['status'] === 'ditinjau'; 
        }),
        'draft' => array_filter($allJobs, function($job) { 
            return isset($job['status']) && $job['status'] === 'draft'; 
        }),
        'sedang' => array_filter($allJobs, function($job) { 
            return isset($job['status']) && $job['status'] === 'ditolak'; 
        })
    ];
} else {
    // Fallback data jika supabase tidak tersedia
    $jobsData = [
        'semua' => [],
        'live' => [],
        'perlu' => [],
        'draft' => [],
        'sedang' => []
    ];
    $jobsDataJson = json_encode($jobsData);
}
?>