<?php
session_start();
require_once __DIR__ . '/../function/supabase.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pastikan parameter id tersedia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: aktivitas.php');
    exit;
}

$id_favorit = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data pencaker
$pencaker = getPencakerByUserId($user_id);
if (!$pencaker) {
    header('Location: login.php');
    exit;
}

// Verifikasi bahwa favorit milik user ini
$result = supabaseQuery('favorit_lowongan', [
    'select' => 'id_favorit',
    'id_favorit' => 'eq.' . $id_favorit,
    'id_pencaker' => 'eq.' . $pencaker['id_pencaker']
]);

if (!$result['success'] || empty($result['data'])) {
    header('Location: aktivitas.php?error=not_found');
    exit;
}

// Hapus favorit
$deleteResult = supabaseDelete('favorit_lowongan', 'id_favorit', $id_favorit);

if ($deleteResult['success']) {
    header('Location: aktivitas.php?success=removed');
} else {
    header('Location: aktivitas.php?error=delete_failed');
}
exit;
?>