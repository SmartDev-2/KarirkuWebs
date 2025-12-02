<?php
// user-functions.php
require_once __DIR__ . '/supabase-core.php';

function checkUsernameExists($username) {
    $result = supabaseQuery('pengguna', [
        'select' => 'id_pengguna',
        'nama_lengkap' => 'eq.' . $username
    ]);

    return $result['success'] && count($result['data']) > 0;
}

function checkEmailExists($email) {
    $result = supabaseQuery('pengguna', [
        'select' => 'id_pengguna',
        'email' => 'eq.' . $email
    ]);

    return $result['success'] && count($result['data']) > 0;
}

function getUserByUsername($username) {
    $result = supabaseQuery('pengguna', [
        'select' => '*',
        'nama_lengkap' => 'eq.' . $username
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function getUserByEmail($email) {
    $result = supabaseQuery('pengguna', [
        'select' => '*',
        'email' => 'eq.' . $email
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}

function getUserById($id) {
    $result = supabaseQuery('pengguna', [
        'select' => '*',
        'id_pengguna' => 'eq.' . $id
    ]);

    if ($result['success'] && count($result['data']) > 0) {
        return $result['data'][0];
    }

    return null;
}
?>