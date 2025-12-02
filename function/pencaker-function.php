<?php
// pencaker-functions.php
require_once __DIR__ . '/supabase-core.php';
require_once __DIR__ . '/user-functions.php'; // Tambahkan ini

function getPencakerByUserId($userId) {
    $result = supabaseQuery('pencaker', [
        'select' => '*',
        'id_pengguna' => 'eq.' . $userId
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function createPencakerProfile($data) {
    error_log("Data untuk createPencakerProfile: " . print_r($data, true));
    $result = supabaseInsert('pencaker', $data);
    error_log("Hasil createPencakerProfile: " . print_r($result, true));
    return $result;
}

function updatePencakerProfile($idPencaker, $data) {
    return supabaseUpdate('pencaker', $data, 'id_pencaker', $idPencaker);
}

function hasPencakerProfile($userId) {
    $result = supabaseQuery('pencaker', [
        'select' => 'id_pencaker',
        'id_pengguna' => 'eq.' . $userId
    ]);

    return $result['success'] && count($result['data']) > 0;
}

function getUserWithPencakerProfile($userId) {
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
?>