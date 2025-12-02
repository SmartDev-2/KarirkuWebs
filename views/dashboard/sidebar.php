<?php
// --- LOGIKA PHP UNTUK MENENTUKAN MENU AKTIF ---

// Pastikan variabel didefinisikan untuk menghindari warning
$activePage = isset($activePage) ? $activePage : ''; 
$count_pending_perusahaan = isset($count_pending_perusahaan) ? $count_pending_perusahaan : 0;

// 1. Logika Menu LOWONGAN (Aktif jika buka Data Lowongan atau Persetujuan)
$isLowonganOpen = ($activePage == 'data_lowongan' || $activePage == 'persetujuan');

// 2. Logika Menu PERUSAHAAN (Aktif jika buka Data Perusahaan atau Verifikasi)
$isPerusahaanOpen = ($activePage == 'data_perusahaan' || $activePage == 'verifikasi');

// 3. Logika Menu LAPORAN (Aktif jika buka salah satu laporan)
$isLaporanOpen = ($activePage == 'laporan_lowongan' || $activePage == 'laporan_perusahaan' || $activePage == 'laporan_user');
?>

<style>
/* --- STYLE SIDEBAR --- */
.sidebar {
    width: 240px; height: 100vh; position: fixed; top: 0; left: 0;
    background: #FFFFFF; border-right: 1px solid #EFEFEF;
    padding-top: 80px; padding-left: 15px; padding-right: 15px;
    z-index: 1020; overflow-y: auto; transition: all 0.3s;
    display: flex; flex-direction: column; gap: 5px;
}

/* Scrollbar halus */
.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-track { background: #f1f1f1; }
.sidebar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }

/* Menu Item Utama */
.sidebar .nav-item {
    display: flex; align-items: center; padding: 12px 15px;
    text-decoration: none; color: #64748B; font-weight: 500;
    border-radius: 10px; font-size: 14px; transition: all 0.2s ease;
    border: 1px solid transparent; cursor: pointer; user-select: none;
}
.sidebar .nav-item i:not(.arrow-indicator) { margin-right: 12px; width: 20px; text-align: center; font-size: 18px; }

/* Hover & Active State */
.sidebar .nav-item:hover { background-color: #F8FAFC; color: #5967FF; }
.sidebar .nav-item.active { background-color: #EFF6FF; color: #5967FF; font-weight: 700; border-color: #DBEAFE; }

/* Panah Indikator Dropdown */
.arrow-indicator { font-size: 12px; margin-left: auto; transition: transform 0.3s ease; opacity: 0.6; }
/* Rotasi panah saat menu aktif/terbuka */
.nav-item.active .arrow-indicator, .nav-item[aria-expanded="true"] .arrow-indicator { transform: rotate(180deg); opacity: 1; }

/* --- SUBMENU STYLING --- */
.submenu {
    overflow: hidden; max-height: 0; transition: max-height 0.4s ease-in-out;
    background-color: transparent; margin-left: 12px; border-left: 2px solid #F1F5F9;
}
/* Class 'show' akan ditambahkan lewat PHP/JS */
.submenu.show { max-height: 500px; /* Nilai cukup besar agar konten muat */ }

/* Item Submenu */
.submenu-item {
    padding: 10px 15px !important; font-size: 13px !important;
    color: #64748B; background: transparent !important; border: none !important;
    border-radius: 0 8px 8px 0 !important; margin-left: 2px; display: flex;
    align-items: center; text-decoration: none;
}
.submenu-item:hover { color: #5967FF; transform: translateX(5px); }

/* Submenu Aktif */
.submenu-item.active { color: #5967FF !important; font-weight: 700 !important; background: #F8FAFC !important; }
.submenu-item.active::before { 
    content: '•'; margin-right: 8px; font-size: 20px; line-height: 0; color: #5967FF; 
}

/* Badge Notifikasi Merah */
.badge-notif {
    background-color: #FF5252; color: white; font-size: 10px; font-weight: 700;
    padding: 2px 6px; border-radius: 6px; margin-left: auto;
}
</style>

<div class="sidebar">
    
    <a href="index.php" class="nav-item <?= ($activePage == 'dashboard') ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i>
        <span>Halaman Utama</span>
    </a>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isLowonganOpen) ? 'active' : '' ?>" id="btnLowongan">
        <i class="fas fa-briefcase"></i>
        <span>Lowongan</span>
        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isLowonganOpen) ? 'show' : '' ?>" id="submenuLowongan">
        <a href="data_lowongan.php" class="submenu-item <?= ($activePage == 'data_lowongan') ? 'active' : '' ?>">
            <span>Data Lowongan</span>
        </a>
        
        <a href="persetujuan.php" class="submenu-item <?= ($activePage == 'persetujuan') ? 'active' : '' ?>">
            <span>Persetujuan</span>
        </a>
    </div>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isPerusahaanOpen) ? 'active' : '' ?>" id="btnPerusahaan">
        <i class="fas fa-building"></i> 
        <span>Perusahaan</span>
        
        <?php if ($count_pending_perusahaan > 0): ?>
            <span class="badge-notif ms-2"><?= $count_pending_perusahaan ?></span>
        <?php endif; ?>

        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isPerusahaanOpen) ? 'show' : '' ?>" id="submenuPerusahaan">
        <a href="data_perusahaan.php" class="submenu-item <?= ($activePage == 'data_perusahaan') ? 'active' : '' ?>">
            <span>Data Perusahaan</span>
        </a>

        <a href="verifikasi.php" class="submenu-item <?= ($activePage == 'verifikasi') ? 'active' : '' ?>">
            <span>Verifikasi</span>
            <?php if ($count_pending_perusahaan > 0): ?>
                <span class="badge-notif"><?= $count_pending_perusahaan ?></span>
            <?php endif; ?>
        </a>
    </div>

    <a href="user.php" class="nav-item <?= ($activePage == 'user') ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>User</span>
    </a>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isLaporanOpen) ? 'active' : '' ?>" id="btnLaporan">
        <i class="fas fa-chart-bar"></i>
        <span>Laporan</span>
        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isLaporanOpen) ? 'show' : '' ?>" id="submenuLaporan">
        <a href="laporan_lowongan.php" class="submenu-item <?= ($activePage == 'laporan_lowongan') ? 'active' : '' ?>">
            <span>Laporan Lowongan</span>
        </a>

        <a href="laporan_perusahaan.php" class="submenu-item <?= ($activePage == 'laporan_perusahaan') ? 'active' : '' ?>">
            <span>Laporan Perusahaan</span>
        </a>

        <a href="laporan_user.php" class="submenu-item <?= ($activePage == 'laporan_user') ? 'active' : '' ?>">
            <span>Laporan User</span>
        </a>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Fungsi untuk Mengatur Logika Dropdown
    function setupSubmenu(btnId, submenuId) {
        const btn = document.getElementById(btnId);
        const submenu = document.getElementById(submenuId);

        if (btn && submenu) {
            // Cek kondisi awal (jika aktif dari PHP)
            if (submenu.classList.contains('show')) {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah pindah halaman
                
                // Cek apakah sedang terbuka
                const isOpen = submenu.classList.contains('show');

                // Tutup semua submenu lain (Opsional, agar rapi)
                closeAllSubmenus(submenuId);

                if (isOpen) {
                    // Jika terbuka, tutup
                    submenu.style.maxHeight = '0';
                    submenu.classList.remove('show');
                    btn.classList.remove('active'); // Hilangkan highlight biru induknya
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    // Jika tertutup, buka
                    submenu.classList.add('show');
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                    btn.classList.add('active'); // Beri highlight biru induknya
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        }
    }

    // Fungsi helper untuk menutup submenu lain saat satu dibuka (Accordion effect)
    function closeAllSubmenus(exceptId) {
        const allSubmenus = document.querySelectorAll('.submenu');
        const allBtns = document.querySelectorAll('.has-submenu');

        allSubmenus.forEach((menu, index) => {
            if (menu.id !== exceptId && menu.classList.contains('show')) {
                menu.style.maxHeight = '0';
                menu.classList.remove('show');
                // Hapus class active dari tombol pemicunya
                if(allBtns[index]) allBtns[index].classList.remove('active');
            }
        });
    }

    // Inisialisasi Menu
    setupSubmenu('btnLowongan', 'submenuLowongan');
    setupSubmenu('btnPerusahaan', 'submenuPerusahaan');
    setupSubmenu('btnLaporan', 'submenuLaporan');
});
</script>