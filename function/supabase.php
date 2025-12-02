<?php
$supabase_url = 'https://tkjnbelcgfwpbhppsnrl.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRram5iZWxjZ2Z3cGJocHBzbnJsIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjE3NDA3NjIsImV4cCI6MjA3NzMxNjc2Mn0.wOjK4X2qJV6LzOG4yXxnfeTezDX5_3Sb3wezhCuQAko';
function supabaseQuery($table, $params = [], $options = [])
{
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/rest/v1/' . $table;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $headers = [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json',
    ];

    if (isset($options['count']) && $options['count'] === 'exact') {
        $headers[] = 'Prefer: count=exact';
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $headerString = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    curl_close($ch);

    $data = json_decode($body, true);

    $count = null;
    if (isset($options['count']) && $options['count'] === 'exact') {
        if (preg_match('/Content-Range: \d+-\d+\/(\d+)/i', $headerString, $matches)) {
            $count = (int)$matches[1];
        }
    }

    $result = [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => $data,
        'status' => $statusCode
    ];

    if ($count !== null) {
        $result['count'] = $count;
    }

    return $result;
}

function supabaseInsert($table, $data) {
    global $supabase_url, $supabase_key; // ✅ FIXED: Use correct variable names
    
    $url = $supabase_url . '/rest/v1/' . $table;
    
    error_log("Supabase Insert - URL: " . $url);
    error_log("Supabase Insert - Data: " . json_encode($data));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $supabase_key,
            'apikey: ' . $supabase_key,
            'Prefer: return=representation'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // ✅ BETTER ERROR HANDLING
    $responseData = json_decode($response, true);
    
    $result = [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'data' => $responseData,
        'error' => $error,
        'raw_response' => $response
    ];
    
    error_log("Supabase Insert Full Result: " . print_r($result, true));
    
    return $result;
}

function supabaseUpdate($table, $data, $column, $value)
{
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/rest/v1/' . $table . '?' . $column . '=eq.' . $value;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: ' . 'application/json',
            'Prefer: return=representation'
        ],
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => $result,
        'status' => $statusCode
    ];
}

function checkUsernameExists($username)
{
    $result = supabaseQuery('users', [
        'select' => 'id',
        'username' => 'eq.' . $username
    ]);

    return $result['success'] && count($result['data']) > 0;
}

function checkEmailExists($email)
{
    $result = supabaseQuery('users', [
        'select' => 'id',
        'email' => 'eq.' . $email
    ]);

    return $result['success'] && count($result['data']) > 0;
}

function getUserByUsername($username)
{
    $result = supabaseQuery('users', [
        'select' => '*',
        'username' => 'eq.' . $username
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function getUserByEmail($email)
{
    $result = supabaseQuery('users', [
        'select' => '*',
        'email' => 'eq.' . $email
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function getUserById($id)
{
    $result = supabaseQuery('pengguna', [
        'select' => '*',
        'id_pengguna' => 'eq.' . $id
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function getPencakerByUserId($userId)
{
    $result = supabaseQuery('pencaker', [
        'select' => '*',
        'id_pengguna' => 'eq.' . $userId
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

// Create profil pencaker baru
function createPencakerProfile($data)
{
    // Debug: log data yang akan dikirim
    error_log("Data untuk createPencakerProfile: " . print_r($data, true));

    $result = supabaseInsert('pencaker', $data);

    // Debug: log hasil
    error_log("Hasil createPencakerProfile: " . print_r($result, true));

    return $result;
}

// Update profil pencaker
function updatePencakerProfile($idPencaker, $data)
{
    return supabaseUpdate('pencaker', $data, 'id_pencaker', $idPencaker);
}

// Cek apakah user sudah punya profil pencaker
function hasPencakerProfile($userId)
{
    $result = supabaseQuery('pencaker', [
        'select' => 'id_pencaker',
        'id_pengguna' => 'eq.' . $userId
    ]);

    return $result['success'] && count($result['data']) > 0;
}

// Get user dengan profil pencaker (JOIN manual)
function getUserWithPencakerProfile($userId)
{
    $user = getUserById($userId);
    if (!$user) {
        return null;
    }

    $pencaker = getPencakerByUserId($userId);

    return [
        'user' => $user,
        'pencaker' => $pencaker
    ];
}

// Fungsi untuk upload file ke Supabase Storage dengan Service Role Key
function supabaseStorageUpload($bucket, $path, $file) {
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/storage/v1/object/' . $bucket . '/' . $path;

    // Baca file sebagai string biner
    $fileContent = file_get_contents($file['tmp_name']);
    
    if ($fileContent === false) {
        return [
            'success' => false,
            'error' => 'Failed to read file content'
        ];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: ' . $file['type'],
            'Content-Length: ' . strlen($fileContent)
        ],
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("Storage Upload Response: " . $response);
    error_log("Storage Upload Status: " . $statusCode);

    $result = json_decode($response, true);

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => $result,
        'status' => $statusCode,
        'error' => $error,
        'response' => $response
    ];
}

// Fungsi untuk menghapus file dari Supabase Storage
function supabaseStorageDelete($bucket, $path) {
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/storage/v1/object/' . $bucket . '/' . $path;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $supabase_key,
        ],
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("Storage Delete Response: " . $response);
    error_log("Storage Delete Status: " . $statusCode);

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'error' => $error,
        'response' => $response
    ];
}

// Fungsi untuk mendapatkan URL publik dari file di Supabase Storage
function getStoragePublicUrl($bucket, $path)
{
    global $supabase_url;
    return $supabase_url . '/storage/v1/object/public/' . $bucket . '/' . $path;
}

// Fungsi untuk ambil lowongan dengan detail perusahaan
function getLowonganWithPerusahaan() {
    $lowongan = supabaseQuery('lowongan', ['select' => '*']);
    $perusahaan = supabaseQuery('perusahaan', ['select' => '*']);
    
    // Lakukan join manual di PHP
    foreach ($lowongan['data'] as &$low) {
        foreach ($perusahaan['data'] as $per) {
            if ($low['id_perusahaan'] == $per['id_perusahaan']) {
                $low['perusahaan'] = $per;
                break;
            }
        }
    }
    
    return $lowongan;
}

// Fungsi untuk pencarian lowongan
// function searchLowongan($keyword) {
//     return supabaseQuery('lowongan', [
//         'select' => '*',
//         'judul' => 'ilike.%' . $keyword . '%'
//     ]);
// }

// Fungsi untuk menghapus data dari tabel Supabase
function supabaseDelete($table, $column, $value) {
    global $supabase_url, $supabase_key;

    $url = $supabase_url . '/rest/v1/' . $table . '?' . $column . '=eq.' . $value;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ],
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => $result,
        'status' => $statusCode,
        'error' => $error
    ];
}

// Fungsi untuk mendapatkan notifikasi lowongan baru
function getNotifikasiLowonganBaru($userId, $limit = 10) {
    // Ambil waktu terakhir user melihat notifikasi (simpan di session atau database)
    $lastChecked = $_SESSION['last_notification_check'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
    
    $result = supabaseQuery('lowongan', [
        'select' => '*, perusahaan(nama_perusahaan, logo_url)',
        'dibuat_pada' => 'gt.' . $lastChecked,
        'status' => 'eq.publish',
        'order' => 'dibuat_pada.desc',
        'limit' => $limit
    ]);

    return $result;
}

// Fungsi untuk menandai notifikasi telah dilihat
function updateLastNotificationCheck($userId) {
    $_SESSION['last_notification_check'] = date('Y-m-d H:i:s');
    return true;
}

// Fungsi untuk menghitung notifikasi belum dilihat
function countUnseenNotifications($userId) {
    $lastChecked = $_SESSION['last_notification_check'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
    
    $result = supabaseQuery('lowongan', [
        'select' => 'id_lowongan',
        'dibuat_papan' => 'gt.' . $lastChecked,
        'status' => 'eq.publish',
        'count' => 'exact'
    ]);

    return $result['count'] ?? 0;
}
// Tambahkan di file function/supabase.php jika belum ada

function supabaseAuth($email, $password) {
    // Implementasi autentikasi Supabase
}

function supabaseGetUser($accessToken) {
    // Implementasi get user data
}

// Fungsi untuk mendapatkan jumlah pelamar berdasarkan status - VERSI DIPERBAIKI
function getJumlahPelamarByStatus($id_perusahaan, $status = null) {
    // Ambil semua lowongan perusahaan
    $lowongan = supabaseQuery('lowongan', [
        'select' => 'id_lowongan',
        'id_perusahaan' => 'eq.' . $id_perusahaan
    ]);
    
    if (!$lowongan['success'] || count($lowongan['data']) === 0) {
        return 0;
    }
    
    $id_lowongan_array = array_column($lowongan['data'], 'id_lowongan');
    $params = [
        'select' => 'id_lamaran',
        'id_lowongan' => 'in.(' . implode(',', $id_lowongan_array) . ')'
    ];
    
    if ($status) {
        $params['status'] = 'eq.' . $status;
    }
    
    $result = supabaseQuery('lamaran', $params);
    return $result['success'] ? count($result['data']) : 0;
}

// Fungsi untuk mendapatkan detail pelamar - VERSI DIPERBAIKI
function getPelamarByStatus($id_perusahaan, $status = null, $limit = 50) {
    // Ambil semua lowongan perusahaan
    $lowongan = supabaseQuery('lowongan', [
        'select' => 'id_lowongan, judul',
        'id_perusahaan' => 'eq.' . $id_perusahaan
    ]);
    
    if (!$lowongan['success']) {
        return ['success' => false, 'data' => []];
    }
    
    $id_lowongan_array = array_column($lowongan['data'], 'id_lowongan');
    $lowongan_dict = [];
    foreach ($lowongan['data'] as $low) {
        $lowongan_dict[$low['id_lowongan']] = $low['judul'];
    }
    
    if (empty($id_lowongan_array)) {
        return ['success' => true, 'data' => []];
    }
    
    // Query yang diperbaiki - sesuai dengan schema database
    $params = [
        'select' => '*, pencaker(nama_lengkap, email_pencaker, no_hp), lowongan(judul)',
        'id_lowongan' => 'in.(' . implode(',', $id_lowongan_array) . ')',
        'order' => 'dibuat_pada.desc',
        'limit' => $limit
    ];
    
    if ($status) {
        $params['status'] = 'eq.' . $status;
    }
    
    $result = supabaseQuery('lamaran', $params);
    
    // Format data untuk konsistensi - DIPERBAIKI
    if ($result['success'] && isset($result['data'])) {
        foreach ($result['data'] as &$lamaran) {
            $lamaran['judul_lowongan'] = $lamaran['lowongan']['judul'] ?? '';
            $lamaran['nama_pelamar'] = $lamaran['pencaker']['nama_lengkap'] ?? '';
            $lamaran['email_pelamar'] = $lamaran['pencaker']['email_pencaker'] ?? '';
            $lamaran['no_hp_pelamar'] = $lamaran['pencaker']['no_hp'] ?? '';
            $lamaran['tanggal_lamaran'] = $lamaran['dibuat_pada'] ?? ''; // Sesuai schema
            $lamaran['catatan_pelamar'] = $lamaran['catatan'] ?? ''; // Sesuai schema
            $lamaran['cv_url'] = $lamaran['cv_url'] ?? '';
            
            // Field pendidikan, pengalaman, keahlian tidak ada di tabel lamaran
            // Jadi kita kosongkan saja atau bisa diambil dari tabel pencaker jika perlu
            $lamaran['pendidikan'] = '';
            $lamaran['pengalaman'] = '';
            $lamaran['keahlian'] = '';
        }
    }
    
    return $result;
}

// Fungsi untuk mendapatkan lamaran oleh pencaker
function getLamaranByPencaker($id_pencaker, $limit = 50) {
    return supabaseQuery('lamaran', [
        'select' => '*, lowongan(judul, lokasi, perusahaan(nama_perusahaan, logo_url))',
        'id_pencaker' => 'eq.' . $id_pencaker,
        'order' => 'tanggal_lamaran.desc',
        'limit' => $limit
    ]);
}

// Fungsi untuk update status lamaran - PERBAIKAN
function updateStatusLamaran($id_lamaran, $status, $catatan_perusahaan = '') {
    $updateData = [
        'status' => $status,
        'catatan_perusahaan' => $catatan_perusahaan,
        'diperbarui_pada' => date('Y-m-d H:i:s')
    ];
    
    // Debug log
    error_log("Updating lamaran ID: " . $id_lamaran . " with status: " . $status);
    
    $result = supabaseUpdate('lamaran', $updateData, 'id_lamaran', $id_lamaran);
    
    // Debug log hasil
    error_log("Update result: " . print_r($result, true));
    
    return $result;
}

// Fungsi untuk mendapatkan detail lowongan dengan perusahaan
function getLowonganWithPerusahaanDetail($id_lowongan) {
    $result = supabaseQuery('lowongan', [
        'select' => '*, perusahaan(nama_perusahaan, logo_url, lokasi)',
        'id_lowongan' => 'eq.' . $id_lowongan
    ]);
    
    return $result['success'] && !empty($result['data']) ? $result['data'][0] : null;
}

// Fungsi untuk mendapatkan semua favorit pencaker
function getFavoritByPencaker($id_pencaker) {
    return supabaseQuery('favorit_lowongan', [
        'select' => '*, lowongan(*, perusahaan(nama_perusahaan, logo_url))',
        'id_pencaker' => 'eq.' . $id_pencaker,
        'order' => 'dibuat_pada.desc'
    ]);
}

// Fungsi untuk menambahkan ke favorit
function addToFavorites($id_pencaker, $id_lowongan) {
    $data = [
        'id_pencaker' => $id_pencaker,
        'id_lowongan' => $id_lowongan,
        'dibuat_pada' => date('Y-m-d H:i:s')
    ];
    
    return supabaseInsert('favorit_lowongan', $data);
}

// Fungsi untuk mengecek apakah lowongan sudah di-favorit
function isJobFavorited($id_pencaker, $id_lowongan) {
    $result = supabaseQuery('favorit_lowongan', [
        'select' => 'id_favorit',
        'id_pencaker' => 'eq.' . $id_pencaker,
        'id_lowongan' => 'eq.' . $id_lowongan
    ]);
    
    return $result['success'] && !empty($result['data']);
}