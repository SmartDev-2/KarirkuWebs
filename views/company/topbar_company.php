<?php
// topbar_company.php
$base_url = '../../';

// Inisialisasi variabel dengan nilai default
$nama_perusahaan = 'Perusahaan';
$logo_url = '';
$id_perusahaan = null;
$unread_count = 0;
$notification_list = [];

// Jika tidak ada data perusahaan dari session, ambil dari database
if (!isset($_SESSION['nama_perusahaan']) || !isset($_SESSION['logo_url'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../function/supabase.php';
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $company = supabaseQuery('perusahaan', [
            'select' => 'id_perusahaan, nama_perusahaan, logo_url',
            'id_pengguna' => 'eq.' . $user_id
        ]);
        
        if ($company['success'] && count($company['data']) > 0) {
            $nama_perusahaan = $company['data'][0]['nama_perusahaan'] ?? 'Perusahaan';
            $logo_url = $company['data'][0]['logo_url'] ?? '';
            $id_perusahaan = $company['data'][0]['id_perusahaan'] ?? null;
            
            // Simpan di session untuk penggunaan berikutnya
            $_SESSION['nama_perusahaan'] = $nama_perusahaan;
            $_SESSION['logo_url'] = $logo_url;
            $_SESSION['id_perusahaan'] = $id_perusahaan;
            
            // Ambil notifikasi yang belum dibaca
            $notifications = supabaseQuery('notifikasi', [
                'select' => 'id_notifikasi, pesan, dibuat_pada, tipe',
                'id_pengguna' => 'eq.' . $user_id,
                'sudah_dibaca' => 'eq.false',
                'order' => 'dibuat_pada.desc',
                'limit' => 10
            ]);
            
            if ($notifications['success'] && is_array($notifications['data'])) {
                $unread_count = count($notifications['data']);
                $notification_list = $notifications['data'];
            }
        }
    }
} else {
    // Gunakan data dari session
    $nama_perusahaan = $_SESSION['nama_perusahaan'];
    $logo_url = $_SESSION['logo_url'];
    $id_perusahaan = $_SESSION['id_perusahaan'] ?? null;
    
    // Ambil notifikasi dari session atau query baru
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $notifications = supabaseQuery('notifikasi', [
            'select' => 'id_notifikasi, pesan, dibuat_pada, tipe',
            'id_pengguna' => 'eq.' . $user_id,
            'sudah_dibaca' => 'eq.false',
            'order' => 'dibuat_pada.desc',
            'limit' => 10
        ]);
        
        if ($notifications['success'] && is_array($notifications['data'])) {
            $unread_count = count($notifications['data']);
            $notification_list = $notifications['data'];
        }
    }
}
?>

<!-- Topbar Company -->
<div class="topbar">
    <div class="topbar-left">
        <!-- Bisa diisi dengan breadcrumb atau judul halaman jika diperlukan -->
    </div>

    <div class="topbar-right">
        <div class="dropdown notification-dropdown">
            <button class="notification-btn" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown">
                <li class="dropdown-header">
                    <h6>Notifikasi</h6>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-primary"><?php echo $unread_count; ?> baru</span>
                    <?php endif; ?>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if (empty($notification_list)): ?>
                    <li class="text-center py-3 text-muted">Tidak ada notifikasi baru</li>
                <?php else: ?>
                    <?php foreach ($notification_list as $notif): ?>
                        <li>
                            <a class="dropdown-item notification-item" href="#" data-notif-id="<?php echo $notif['id_notifikasi']; ?>">
                                <div class="notification-content">
                                    <p class="notification-message"><?php echo htmlspecialchars($notif['pesan']); ?></p>
                                    <small class="notification-time"><?php echo date('d M Y H:i', strtotime($notif['dibuat_pada'])); ?></small>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center text-primary" href="notifications.php">Lihat Semua Notifikasi</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <button class="btn user-dropdown dropdown-toggle text-dark p-0 border-0 bg-transparent d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none !important;">
                <div class="user-profile">
                    <div class="user-avatar <?php echo !empty($logo_url) ? 'has-logo' : ''; ?>">
                        <?php if (!empty($logo_url)): ?>
                            <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo <?php echo htmlspecialchars($nama_perusahaan); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="avatar-placeholder" style="display: none;">
                                <?php echo strtoupper(substr($nama_perusahaan, 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($nama_perusahaan, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($nama_perusahaan); ?></span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="edit_company.php">
                        <i class="fas fa-edit me-2"></i>Edit Profil
                    </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
            </ul>
        </div>
    </div>
</div>

<style>
    .topbar {
        height: 81px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        border-bottom: 1px solid #e5e7eb;
        background: white;
    }

    .topbar-left,
    .topbar-right {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .notification-dropdown {
        position: relative;
        margin-right: 10px;
    }

    .notification-btn {
        background: none;
        border: none;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-btn:hover {
        background-color: #f3f4f6;
    }

    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-menu {
        width: 320px;
        max-height: 400px;
        overflow-y: auto;
    }

    .dropdown-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
    }

    .notification-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f1f1;
        cursor: pointer;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-content {
        display: flex;
        flex-direction: column;
    }

    .notification-message {
        margin: 0;
        font-size: 0.875rem;
        color: #333;
    }

    .notification-time {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    .user-dropdown {
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        height: auto !important;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 8px;
        transition: background-color 0.2s;
        margin: 0 !important;
    }

    .user-profile:hover {
        background-color: #f3f4f6 !important;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #003399;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
        border: 2px solid #e5e7eb;
        flex-shrink: 0;
    }

    .user-avatar.has-logo {
        background-color: transparent;
        border: none;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-avatar .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: #003399;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .dropdown-menu {
        left: auto !important;
        right: 0 !important;
        transform: none !important;
        position: absolute !important;
        top: 100% !important;
    }

    .user-dropdown:focus,
    .user-dropdown:active {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .user-dropdown.dropdown-toggle::after {
        display: none !important;
    }

    .dropdown {
        position: relative;
    }
</style>

<script>
// JavaScript untuk menandai notifikasi sebagai dibaca ketika diklik
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const notifId = this.getAttribute('data-notif-id');
            
            // Tandai notifikasi sebagai dibaca via AJAX
            if (notifId) {
                markNotificationAsRead(notifId);
            }
            
            // Hapus badge notifikasi
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.remove();
            }
            
            // Tutup dropdown
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        });
    });
});

function markNotificationAsRead(notifId) {
    // Implementasi AJAX untuk update status notifikasi
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notif_id: notifId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Notifikasi ditandai sebagai dibaca');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>