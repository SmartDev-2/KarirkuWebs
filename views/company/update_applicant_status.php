<?php
// update_applicant_status.php - VERSI SIMPLE & LANGSUNG
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Atur header untuk JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login sebagai perusahaan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized - Silakan login sebagai perusahaan'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
error_log("Received data: " . print_r($input, true));

$id_lamaran = $input['id_lamaran'] ?? null;
$status = $input['status'] ?? null;

if (!$id_lamaran || !$status) {
    echo json_encode([
        'success' => false, 
        'message' => 'Data tidak lengkap',
        'data_received' => $input
    ]);
    exit;
}

// Validasi status
$validStatuses = ['diproses', 'diterima', 'ditolak'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Status tidak valid'
    ]);
    exit;
}

// Verifikasi bahwa lamaran ini milik perusahaan yang login
$user_id = $_SESSION['user_id'];

// 1. Cari perusahaan berdasarkan user_id
$company = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan',
    'id_pengguna' => 'eq.' . $user_id
]);

if (!$company['success'] || count($company['data']) === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Perusahaan tidak ditemukan'
    ]);
    exit;
}

$id_perusahaan = $company['data'][0]['id_perusahaan'];

// 2. Cek lamaran dan pastikan lowongannya milik perusahaan ini
$lamaranCheck = supabaseQuery('lamaran', [
    'select' => 'id_lowongan',
    'id_lamaran' => 'eq.' . $id_lamaran
]);

if (!$lamaranCheck['success'] || count($lamaranCheck['data']) === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lamaran tidak ditemukan'
    ]);
    exit;
}

$id_lowongan = $lamaranCheck['data'][0]['id_lowongan'];

// 3. Cek apakah lowongan ini milik perusahaan
$lowonganCheck = supabaseQuery('lowongan', [
    'select' => 'id_perusahaan',
    'id_lowongan' => 'eq.' . $id_lowongan,
    'id_perusahaan' => 'eq.' . $id_perusahaan
]);

if (!$lowonganCheck['success'] || count($lowonganCheck['data']) === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Anda tidak memiliki akses untuk mengubah lamaran ini'
    ]);
    exit;
}

// 4. Update status lamaran menggunakan supabaseUpdate yang sudah diperbaiki
$updateData = ['status' => $status];
$result = supabaseUpdate('lamaran', $updateData, 'id_lamaran', $id_lamaran);

if ($result['success']) {
    // Kirim notifikasi ke pelamar
    $lamaranDetail = supabaseQuery('lamaran', [
        'select' => 'id_pencaker',
        'id_lamaran' => 'eq.' . $id_lamaran
    ]);

    if ($lamaranDetail['success'] && count($lamaranDetail['data']) > 0) {
        $id_pencaker = $lamaranDetail['data'][0]['id_pencaker'];
        
        // Cari user_id dari pencaker
        $pencaker = supabaseQuery('pencaker', [
            'select' => 'id_pengguna',
            'id_pencaker' => 'eq.' . $id_pencaker
        ]);

        if ($pencaker['success'] && count($pencaker['data']) > 0) {
            $id_pengguna = $pencaker['data'][0]['id_pengguna'];
            
            $pesan = $status === 'diterima' 
                ? 'Selamat! Lamaran Anda telah diterima oleh perusahaan.' 
                : 'Maaf, lamaran Anda telah ditolak oleh perusahaan.';

            $notifData = [
                'id_pengguna' => $id_pengguna,
                'pesan' => $pesan,
                'tipe' => 'lamaran'
            ];
            
            supabaseInsert('notifikasi', $notifData);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status berhasil diupdate menjadi: ' . $status
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengupdate status',
        'error_detail' => $result['error'] ?? 'Unknown error'
    ]);
}
?>