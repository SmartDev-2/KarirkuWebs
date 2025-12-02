<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pencaker = getPencakerByUserId($user_id);

if (!$pencaker) {
    echo json_encode(['success' => false, 'message' => 'Profil pencaker tidak ditemukan']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id_lowongan = $_POST['id_lowongan'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$id_lowongan || !in_array($action, ['save', 'unsave'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id_pencaker = $pencaker['id_pencaker'];

if ($action === 'save') {
    // Cek apakah sudah ada
    $check_result = supabaseQuery('favorit_lowongan', [
        'select' => 'id_favorit',
        'id_pencaker' => 'eq.' . $id_pencaker,
        'id_lowongan' => 'eq.' . $id_lowongan
    ]);
    
    if ($check_result['success'] && empty($check_result['data'])) {
        $insert_result = supabaseInsert('favorit_lowongan', [
            'id_pencaker' => $id_pencaker,
            'id_lowongan' => $id_lowongan,
            'dibuat_pada' => date('Y-m-d H:i:s')
        ]);
        
        if ($insert_result['success']) {
            echo json_encode(['success' => true, 'message' => 'Lowongan berhasil disimpan', 'saved' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan lowongan']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Lowongan sudah disimpan', 'saved' => true]);
    }
} elseif ($action === 'unsave') {
    // Cari id_favorit
    $find_result = supabaseQuery('favorit_lowongan', [
        'select' => 'id_favorit',
        'id_pencaker' => 'eq.' . $id_pencaker,
        'id_lowongan' => 'eq.' . $id_lowongan
    ]);
    
    if ($find_result['success'] && !empty($find_result['data'])) {
        $id_favorit = $find_result['data'][0]['id_favorit'];
        $delete_result = supabaseDelete('favorit_lowongan', 'id_favorit', $id_favorit);
        
        if ($delete_result['success']) {
            echo json_encode(['success' => true, 'message' => 'Lowongan berhasil dihapus dari favorit', 'saved' => false]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus lowongan']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Lowongan tidak ada di favorit', 'saved' => false]);
    }
}