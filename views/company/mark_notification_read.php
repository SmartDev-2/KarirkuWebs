<?php
// mark_notification_read.php
session_start();
require_once __DIR__ . '/../function/supabase.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notifId = $input['notif_id'] ?? null;

if (!$notifId) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

// Update notifikasi menjadi sudah dibaca
$result = supabaseUpdate('notifikasi', ['sudah_dibaca' => true], 'id_notifikasi', $notifId);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}
?>