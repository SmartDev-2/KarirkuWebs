<?php
require_once __DIR__ . '/supabase.php';

function getDaftarLokasi()
{
    try {
        $params = [
            'select' => 'lokasi',
            'status' => 'eq.publish', // PERBAIKAN: Gunakan status yang benar
            'order' => 'lokasi.asc'
        ];

        $response = supabaseQuery('lowongan', $params);

        if (!$response['success']) {
            error_log("Error fetching locations: " . ($response['error'] ?? 'Unknown error'));
            // Return default locations jika query gagal
            return ['Jakarta', 'Surabaya', 'Bandung', 'Malang', 'Jember'];
        }

        $data = $response['data'];

        $lokasiUnik = [];
        foreach ($data as $row) {
            if (!empty($row['lokasi']) && !in_array($row['lokasi'], $lokasiUnik)) {
                $lokasiUnik[] = $row['lokasi'];
            }
        }

        // Jika tidak ada lokasi, berikan default
        if (empty($lokasiUnik)) {
            $lokasiUnik = ['Jakarta', 'Surabaya', 'Bandung', 'Malang', 'Jember'];
        }

        sort($lokasiUnik);

        return $lokasiUnik;
    } catch (Exception $e) {
        error_log("Error in getDaftarLokasi: " . $e->getMessage());
        return ['Jakarta', 'Surabaya', 'Bandung', 'Malang', 'Jember'];
    }
}

function searchLowongan($keyword = '', $lokasi = '', $page = 1, $limit = 5)
{
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $offset = ($page - 1) * $limit;

    error_log("searchLowongan called with: keyword='$keyword', lokasi='$lokasi', page=$page, limit=$limit");

    try {
        // PERBAIKAN: Query tanpa join dulu, join manual di PHP
        $params = [
            'select' => 'id_lowongan, judul, deskripsi, kualifikasi, lokasi, tipe_pekerjaan, gaji_range, dibuat_pada, batas_tanggal, status, kategori, mode_kerja, benefit, id_perusahaan',
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'dibuat_pada.desc'
        ];

        // PERBAIKAN: Gunakan filter status yang benar
        $params['status'] = 'eq.publish'; // Sesuai dengan nilai di skema database

        if (!empty($keyword)) {
            $params['or'] = "(judul.ilike.%{$keyword}%,kategori.ilike.%{$keyword}%,deskripsi.ilike.%{$keyword}%,kualifikasi.ilike.%{$keyword}%)";
        }

        if (!empty($lokasi) && $lokasi !== 'semua') {
            $params['lokasi'] = 'ilike.%' . $lokasi . '%';
        }

        error_log("Query params untuk lowongan: " . print_r($params, true));

        $response = supabaseQuery('lowongan', $params);

        error_log("Response lowongan success: " . ($response['success'] ? 'true' : 'false'));
        error_log("Response lowongan count: " . count($response['data'] ?? []));

        if (!$response['success']) {
            throw new Exception('Failed to fetch lowongan data: ' . ($response['error'] ?? 'Unknown error'));
        }

        $data = $response['data'];

        error_log("Data lowongan raw count: " . count($data));

        // PERBAIKAN: Ambil data perusahaan secara terpisah untuk join manual
        $processedData = [];
        if (!empty($data)) {
            // Kumpulkan semua id_perusahaan yang unik
            $idPerusahaanArray = [];
            foreach ($data as $row) {
                if (!empty($row['id_perusahaan'])) {
                    $idPerusahaanArray[] = $row['id_perusahaan'];
                }
            }

            $idPerusahaanArray = array_unique($idPerusahaanArray);

            // Ambil data perusahaan
            $perusahaanData = [];
            if (!empty($idPerusahaanArray)) {
                $perusahaanParams = [
                    'select' => 'id_perusahaan, nama_perusahaan, logo_url, deskripsi, website',
                    'id_perusahaan' => 'in.(' . implode(',', $idPerusahaanArray) . ')'
                ];

                $perusahaanResponse = supabaseQuery('perusahaan', $perusahaanParams);

                if ($perusahaanResponse['success']) {
                    foreach ($perusahaanResponse['data'] as $perusahaan) {
                        $perusahaanData[$perusahaan['id_perusahaan']] = $perusahaan;
                    }
                }
            }

            // Gabungkan data
            foreach ($data as $row) {
                $processedRow = $row;

                // Tambahkan data perusahaan jika ada
                if (!empty($row['id_perusahaan']) && isset($perusahaanData[$row['id_perusahaan']])) {
                    $processedRow['perusahaan'] = $perusahaanData[$row['id_perusahaan']];
                } else {
                    $processedRow['perusahaan'] = null;
                }

                $processedData[] = $processedRow;
            }
        }

        // Hitung total data
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

        $totalData = $countResponse['count'] ?? 0;
        $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 1;

        error_log("Total data setelah query: $totalData, Processed data: " . count($processedData));

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
// Fungsi untuk mendapatkan URL logo perusahaan
function getCompanyLogoUrl($lowonganData)
{
    // Jika ada data perusahaan dan logo_url
    if (isset($lowonganData['perusahaan']) && !empty($lowonganData['perusahaan']['logo_url'])) {
        return $lowonganData['perusahaan']['logo_url'];
    }

    // Fallback ke logo default
    return '../assets/img/logo.png';
}

// Fungsi untuk mendapatkan nama perusahaan
function getCompanyName($lowonganData)
{
    if (isset($lowonganData['perusahaan']) && !empty($lowonganData['perusahaan']['nama_perusahaan'])) {
        return $lowonganData['perusahaan']['nama_perusahaan'];
    }

    // Coba ambil dari data langsung jika perusahaan tidak ada
    if (isset($lowonganData['nama_perusahaan'])) {
        return $lowonganData['nama_perusahaan'];
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
        // PERBAIKAN: Ambil data lowongan dulu
        $params = [
            'select' => '*',
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

        // Ambil data perusahaan secara terpisah
        if (!empty($data['id_perusahaan'])) {
            $perusahaanParams = [
                'select' => 'id_perusahaan, nama_perusahaan, logo_url, deskripsi, website, lokasi, no_telp',
                'id_perusahaan' => 'eq.' . $data['id_perusahaan']
            ];

            $perusahaanResponse = supabaseQuery('perusahaan', $perusahaanParams);

            if ($perusahaanResponse['success'] && !empty($perusahaanResponse['data'])) {
                $data['perusahaan'] = $perusahaanResponse['data'][0];
            } else {
                $data['perusahaan'] = null;
            }
        } else {
            $data['perusahaan'] = null;
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
