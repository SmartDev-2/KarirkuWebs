<?php
session_start();
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['user_id'])) {
        $success = updateLastNotificationCheck($_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false]);
?>