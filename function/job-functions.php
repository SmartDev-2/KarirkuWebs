<?php
require_once __DIR__ . '/supabase.php';

function getDaftarLokasi()
{
    try {
        $params = [
            'select' => 'lokasi',
            'status' => 'eq.open',
            'order' => 'lokasi.asc'
        ];

        $response = supabaseQuery('lowongan', $params);

        if (!$response['success']) {
            throw new Exception('Failed to fetch locations: ' . ($response['error'] ?? 'Unknown error'));
        }

        $data = $response['data'];

        $lokasiUnik = [];
        foreach ($data as $row) {
            if (!empty($row['lokasi']) && !in_array($row['lokasi'], $lokasiUnik)) {
                $lokasiUnik[] = $row['lokasi'];
            }
        }

        sort($lokasiUnik);

        return $lokasiUnik;
    } catch (Exception $e) {
        error_log("Error in getDaftarLokasi: " . $e->getMessage());
        return [];
    }
}

function searchLowongan($keyword = '', $lokasi = '', $page = 1, $limit = 5)
{
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $offset = ($page - 1) * $limit;

    try {
        // Query untuk mendapatkan data lowongan beserta data perusahaan
        $params = [
            'select' => '*, perusahaan:perusahaan(id_perusahaan, nama_perusahaan, logo_url, deskripsi, website)',
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'dibuat_pada.desc',
            'status' => 'eq.publish'
        ];

        if (!empty($keyword)) {
            $params['or'] = "(judul.ilike.%{$keyword}%,kategori.ilike.%{$keyword}%,deskripsi.ilike.%{$keyword}%,kualifikasi.ilike.%{$keyword}%)";
        }

        if (!empty($lokasi) && $lokasi !== 'semua') {
            $params['lokasi'] = 'ilike.%' . $lokasi . '%';
        }

        $response = supabaseQuery('lowongan', $params);

        if (!$response['success']) {
            throw new Exception('Failed to fetch data: ' . ($response['error'] ?? 'Unknown error'));
        }

        $data = $response['data'];

        // Process the data to extract perusahaan information
        $processedData = [];
        foreach ($data as $row) {
            $processedRow = $row;
            
            // Extract perusahaan data from the nested structure
            if (isset($row['perusahaan']) && is_array($row['perusahaan'])) {
                $processedRow['perusahaan'] = $row['perusahaan'][0] ?? $row['perusahaan'];
            } else {
                $processedRow['perusahaan'] = null;
            }
            
            $processedData[] = $processedRow;
        }

        $countParams = [
            'select' => 'id_lowongan',
            'status' => 'eq.publish'
        ];

        if (!empty($keyword)) {
            $countParams['or'] = "(judul.ilike.%{$keyword}%,kategori.ilike.%{$keyword}%,deskripsi.ilike.%{$keyword}%,kualifikasi.ilike.%{$keyword}%)";
        }

        if (!empty($lokasi) && $lokasi !== 'semua') {
            $countParams['lokasi'] = 'ilike.%' . $lokasi . '%';
        }

        $countResponse = supabaseQuery('lowongan', $countParams, ['count' => 'exact']);

        $totalData = $countResponse['count'] ?? count($processedData);
        $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 1;

        return [
            'success' => true,
            'data' => $processedData,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_data' => $totalData,
                'limit' => $limit,
                'offset' => $offset
            ],
            'search_params' => [
                'keyword' => $keyword,
                'lokasi' => $lokasi
            ]
        ];
    } catch (Exception $e) {
        error_log("Error in searchLowongan: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => 1,
                'total_data' => 0,
                'limit' => $limit,
                'offset' => $offset
            ]
        ];
    }
}

// Fungsi untuk mendapatkan URL logo perusahaan
function getCompanyLogoUrl($lowonganData)
{
    // Jika ada data perusahaan dan logo_url
    if (isset($lowonganData['perusahaan']['logo_url']) && !empty($lowonganData['perusahaan']['logo_url'])) {
        return $lowonganData['perusahaan']['logo_url'];
    }
    
    // Fallback ke logo default
    return '../assets/img/logo.png';
}

// Fungsi untuk mendapatkan nama perusahaan
function getCompanyName($lowonganData)
{
    if (isset($lowonganData['perusahaan']['nama_perusahaan']) && !empty($lowonganData['perusahaan']['nama_perusahaan'])) {
        return $lowonganData['perusahaan']['nama_perusahaan'];
    }
    
    return 'Perusahaan';
}

function getDetailLowongan($id_lowongan)
{
    if (empty($id_lowongan)) {
        return [
            'success' => false,
            'error' => 'ID lowongan tidak valid',
            'data' => null
        ];
    }

    try {
        $params = [
            'select' => '*, perusahaan:perusahaan(id_perusahaan, nama_perusahaan, logo_url, deskripsi, website, lokasi, no_telp)',
            'id_lowongan' => 'eq.' . $id_lowongan
        ];

        $response = supabaseQuery('lowongan', $params);

        if (!$response['success'] || empty($response['data'])) {
            return [
                'success' => false,
                'error' => 'Lowongan tidak ditemukan',
                'data' => null
            ];
        }

        $data = $response['data'][0];
        
        // Process perusahaan data
        if (isset($data['perusahaan']) && is_array($data['perusahaan'])) {
            $data['perusahaan'] = $data['perusahaan'][0] ?? $data['perusahaan'];
        }

        return [
            'success' => true,
            'data' => $data
        ];
    } catch (Exception $e) {
        error_log("Error in getDetailLowongan: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'data' => null
        ];
    }
}

function formatLowongan($lowongan)
{
    $perusahaan = $lowongan['perusahaan'] ?? [];
    
    return [
        'id_lowongan' => $lowongan['id_lowongan'] ?? '',
        'judul' => $lowongan['judul'] ?? 'Judul tidak tersedia',
        'lokasi' => $lowongan['lokasi'] ?? 'Lokasi tidak tersedia',
        'tipe_pekerjaan' => $lowongan['tipe_pekerjaan'] ?? 'Tipe tidak tersedia',
        'gaji' => $lowongan['gaji_range'] ?? 'Gaji tidak tersedia',
        'deskripsi' => $lowongan['deskripsi'] ?? 'Deskripsi tidak tersedia',
        'kualifikasi' => $lowongan['kualifikasi'] ?? 'Kualifikasi tidak tersedia',
        'benefit' => $lowongan['benefit'] ?? 'Benefit tidak tersedia',
        'kategori' => $lowongan['kategori'] ?? 'Kategori tidak tersedia',
        'mode_kerja' => $lowongan['mode_kerja'] ?? 'Mode kerja tidak tersedia',
        'dibuat_pada' => !empty($lowongan['dibuat_pada']) ? date('d M Y', strtotime($lowongan['dibuat_pada'])) : 'Tidak tersedia',
        'batas_tanggal' => !empty($lowongan['batas_tanggal']) ? date('d M Y', strtotime($lowongan['batas_tanggal'])) : 'Tidak ditentukan',
        'perusahaan' => [
            'nama' => $perusahaan['nama_perusahaan'] ?? 'Perusahaan',
            'logo' => $perusahaan['logo_url'] ?? '../assets/img/logo.png',
            'deskripsi' => $perusahaan['deskripsi'] ?? '',
            'website' => $perusahaan['website'] ?? ''
        ]
    ];
}

function parseKualifikasi($kualifikasi)
{
    $kualifikasi_list = [];
    
    if (!empty($kualifikasi)) {
        $kualifikasi_array = explode(';', $kualifikasi);
        foreach ($kualifikasi_array as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $kualifikasi_list[] = $item;
            }
        }
    }
    
    return $kualifikasi_list;
}

function parseBenefit($benefit)
{
    $benefit_list = [];
    
    if (!empty($benefit)) {
        $benefit_array = explode(',', $benefit);
        foreach ($benefit_array as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $benefit_list[] = $item;
            }
        }
    }
    
    return $benefit_list;
}

function formatTipePekerjaan($tipe_pekerjaan)
{
    return ucfirst(str_replace('-', ' ', $tipe_pekerjaan));
}

function validateSearchInput($input)
{
    return [
        'keyword' => isset($input['keyword']) ? trim($input['keyword']) : '',
        'lokasi' => isset($input['lokasi']) ? trim($input['lokasi']) : '',
        'page' => isset($input['page']) ? max(1, (int)$input['page']) : 1
    ];
}