<?php
// update_applicant_status.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../function/supabase.php';

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan user sudah login sebagai perusahaan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Debug log input
error_log("Received update request: " . print_r($input, true));

$id_lamaran = $input['id_lamaran'] ?? null;
$status = $input['status'] ?? null;

// Validasi input
if (!$id_lamaran || !$status) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Data tidak lengkap',
        'received_data' => $input
    ]);
    exit;
}

// Validasi status
$validStatuses = ['diproses', 'diterima', 'ditolak'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Status tidak valid. Harus: diproses, diterima, atau ditolak',
        'received_status' => $status
    ]);
    exit;
}

// Verifikasi bahwa lamaran ini milik perusahaan yang login
$user_id = $_SESSION['user_id'];
$company = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan',
    'id_pengguna' => 'eq.' . $user_id
]);

if (!$company['success'] || count($company['data']) === 0) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Perusahaan tidak ditemukan']);
    exit;
}

$id_perusahaan = $company['data'][0]['id_perusahaan'];

// Cek apakah lamaran ini benar-benar milik lowongan perusahaan ini
$lamaranCheck = supabaseQuery('lamaran', [
    'select' => 'id_lamaran, lowongan(id_perusahaan)',
    'id_lamaran' => 'eq.' . $id_lamaran
]);

if (!$lamaranCheck['success'] || count($lamaranCheck['data']) === 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lamaran tidak ditemukan']);
    exit;
}

// Cek kepemilikan
$lamaranData = $lamaranCheck['data'][0];
if (!isset($lamaranData['lowongan']) || $lamaranData['lowongan']['id_perusahaan'] != $id_perusahaan) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke lamaran ini']);
    exit;
}

// Update status lamaran
$result = updateStatusLamaran($id_lamaran, $status, 'Status diubah oleh perusahaan');

if ($result['success']) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Status pelamar berhasil diupdate menjadi ' . $status,
        'new_status' => $status
    ]);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal mengupdate status di database',
        'error' => $result['error'] ?? 'Unknown error',
        'http_code' => $result['status'] ?? 'Unknown'
    ]);
}
?>