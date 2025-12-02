<?php
// ==========================================
// FILE KONEKSI PUSAT (supabase.php)
// ==========================================

// 1. LOAD LIBRARY GUZZLE (Penting untuk Dashboard: Data Perusahaan & Verifikasi)
require __DIR__ . '/../../vendor/autoload.php';
use GuzzleHttp\Client;

// 2. CONFIGURASI KREDENSIAL
$supabase_url = 'https://tkjnbelcgfwpbhppsnrl.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRram5iZWxjZ2Z3cGJocHBzbnJsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTc0MDc2MiwiZXhwIjoyMDc3MzE2NzYyfQ.vZoNXxMWtoG4ktg7K6Whqv8EFzCv7qbS3OAHEfxVoR0';

// 3. INISIALISASI $CLIENT (Untuk Dashboard Baru)
try {
    $client = new Client([
        'base_uri' => $supabase_url . '/rest/v1/',
        'headers' => [
            'apikey'        => $supabase_key,
            'Authorization' => 'Bearer ' . $supabase_key,
            'Content-Type'  => 'application/json',
        ],
        'http_errors' => false
    ]);
} catch (Exception $e) {
    error_log("Guzzle Client Error: " . $e->getMessage());
}

// ============================================================================
// 4. HELPER FUNCTIONS CRUD (Query, Insert, Update, Delete)
// ============================================================================

// --- FUNGSI SELECT (READ) ---
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
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($body, true);

    $count = null;
    if (isset($options['count']) && $options['count'] === 'exact') {
        $headerString = substr($response, 0, $headerSize);
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

// --- FUNGSI INSERT (CREATE) ---
function supabaseInsert($table, $data)
{
    global $supabase_url, $supabase_key;

    foreach ($data as $key => $value) {
        if ($value === '') {
            $data[$key] = null;
        }
    }

    $url = $supabase_url . '/rest/v1/' . $table;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: application/json',
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

// --- FUNGSI UPDATE (EDIT) ---
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

// --- FUNGSI DELETE (HAPUS) ---
// (Ini yang baru ditambahkan untuk mengatasi error di user.php)
function supabaseDelete($table, $column, $value)
{
    global $supabase_url, $supabase_key;

    // URL: url/rest/v1/tabel?kolom=eq.nilai
    $url = $supabase_url . '/rest/v1/' . $table . '?' . $column . '=eq.' . urlencode($value);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => json_decode($response, true),
        'status' => $statusCode,
        'error' => $curlError . ' ' . $response
    ];
}

// ============================================================================
// 5. HELPER USER & PROFIL
// ============================================================================

function checkUsernameExists($username) {
    $result = supabaseQuery('users', ['select' => 'id', 'username' => 'eq.' . $username]);
    return $result['success'] && count($result['data']) > 0;
}

function checkEmailExists($email) {
    $result = supabaseQuery('users', ['select' => 'id', 'email' => 'eq.' . $email]);
    return $result['success'] && count($result['data']) > 0;
}

function getUserById($id) {
    $result = supabaseQuery('pengguna', ['select' => '*', 'id_pengguna' => 'eq.' . $id]);
    if ($result['success'] && count($result['data']) > 0) return $result['data'][0];
    return null;
}

// --- STORAGE HELPERS ---

function getStoragePublicUrl($bucket, $path) {
    global $supabase_url;
    return $supabase_url . '/storage/v1/object/public/' . $bucket . '/' . $path;
}
?>