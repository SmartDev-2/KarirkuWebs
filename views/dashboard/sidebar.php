<?php
// --- LOGIKA PHP UNTUK MENENTUKAN MENU AKTIF ---
$activePage = isset($activePage) ? $activePage : ''; 
$count_pending_perusahaan = isset($count_pending_perusahaan) ? $count_pending_perusahaan : 0;

$isLowonganOpen = ($activePage == 'data_lowongan' || $activePage == 'persetujuan');
$isPerusahaanOpen = ($activePage == 'data_perusahaan' || $activePage == 'verifikasi');
$isLaporanOpen = ($activePage == 'laporan_lowongan' || $activePage == 'laporan_perusahaan' || $activePage == 'laporan_user');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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

/* SETUP IKON UTAMA AGAR MUNCUL & RAPI */
.sidebar .nav-item i:not(.arrow-indicator) { 
    margin-right: 12px; 
    width: 20px; 
    text-align: center; 
    font-size: 18px; 
    color: #94a3b8; /* Warna abu-abu default */
}

/* Hover & Active State */
.sidebar .nav-item:hover { background-color: #F8FAFC; color: #5967FF; }
.sidebar .nav-item:hover i { color: #5967FF; } /* Ikon jadi biru saat dihover */

.sidebar .nav-item.active { background-color: #EFF6FF; color: #5967FF; font-weight: 700; border-color: #DBEAFE; }
.sidebar .nav-item.active i { color: #5967FF; } /* Ikon jadi biru saat aktif */

/* Panah Indikator Dropdown */
.arrow-indicator { font-size: 12px; margin-left: auto; transition: transform 0.3s ease; opacity: 0.6; }
.nav-item.active .arrow-indicator, .nav-item[aria-expanded="true"] .arrow-indicator { transform: rotate(180deg); opacity: 1; }

/* --- SUBMENU STYLING --- */
.submenu {
    overflow: hidden; max-height: 0; transition: max-height 0.4s ease-in-out;
    background-color: transparent; margin-left: 12px; border-left: 2px solid #F1F5F9;
}
.submenu.show { max-height: 500px; }

/* Item Submenu */
.submenu-item {
    padding: 10px 15px !important; font-size: 13px !important;
    color: #64748B; background: transparent !important; border: none !important;
    border-radius: 0 8px 8px 0 !important; margin-left: 2px; display: flex;
    align-items: center; text-decoration: none;
}
.submenu-item:hover { color: #5967FF; transform: translateX(5px); }

/* IKON KECIL DI SUBMENU */
.sub-icon {
    font-size: 12px; margin-right: 10px; width: 15px; text-align: center; color: #cbd5e1;
}
.submenu-item:hover .sub-icon, .submenu-item.active .sub-icon { color: #5967FF; }

/* Submenu Aktif */
.submenu-item.active { color: #5967FF !important; font-weight: 700 !important; background: #F8FAFC !important; }

/* Badge Notifikasi Merah */
.badge-notif {
    background-color: #FF5252; color: white; font-size: 10px; font-weight: 700;
    padding: 2px 6px; border-radius: 6px; margin-left: auto;
}
</style>

<div class="sidebar">
    
    <a href="index.php" class="nav-item <?= ($activePage == 'dashboard') ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> <span>Halaman Utama</span>
    </a>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isLowonganOpen) ? 'active' : '' ?>" id="btnLowongan">
        <i class="fas fa-briefcase"></i> <span>Lowongan</span>
        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isLowonganOpen) ? 'show' : '' ?>" id="submenuLowongan">
        <a href="data_lowongan.php" class="submenu-item <?= ($activePage == 'data_lowongan') ? 'active' : '' ?>">
            <i class="fas fa-list sub-icon"></i> <span>Data Lowongan</span>
        </a>
        
        <a href="persetujuan.php" class="submenu-item <?= ($activePage == 'persetujuan') ? 'active' : '' ?>">
            <i class="fas fa-check-circle sub-icon"></i> <span>Persetujuan</span>
        </a>
    </div>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isPerusahaanOpen) ? 'active' : '' ?>" id="btnPerusahaan">
        <i class="fas fa-building"></i>  <span>Perusahaan</span>
        
        <?php if ($count_pending_perusahaan > 0): ?>
            <span class="badge-notif ms-2"><?= $count_pending_perusahaan ?></span>
        <?php endif; ?>

        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isPerusahaanOpen) ? 'show' : '' ?>" id="submenuPerusahaan">
        <a href="data_perusahaan.php" class="submenu-item <?= ($activePage == 'data_perusahaan') ? 'active' : '' ?>">
            <i class="far fa-building sub-icon"></i> <span>Data Perusahaan</span>
        </a>

        <a href="verifikasi.php" class="submenu-item <?= ($activePage == 'verifikasi') ? 'active' : '' ?>">
            <i class="fas fa-user-check sub-icon"></i> <span>Verifikasi</span>
            <?php if ($count_pending_perusahaan > 0): ?>
                <span class="badge-notif"><?= $count_pending_perusahaan ?></span>
            <?php endif; ?>
        </a>
    </div>

    <a href="user.php" class="nav-item <?= ($activePage == 'user') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> <span>User</span>
    </a>

    <a href="javascript:void(0)" class="nav-item has-submenu <?= ($isLaporanOpen) ? 'active' : '' ?>" id="btnLaporan">
        <i class="fas fa-chart-bar"></i> <span>Laporan</span>
        <i class="fas fa-chevron-down arrow-indicator"></i>
    </a>
    
    <div class="submenu <?= ($isLaporanOpen) ? 'show' : '' ?>" id="submenuLaporan">
        <a href="laporan_lowongan.php" class="submenu-item <?= ($activePage == 'laporan_lowongan') ? 'active' : '' ?>">
            <i class="far fa-file-alt sub-icon"></i>
            <span>Laporan Lowongan</span>
        </a>

        <a href="laporan_perusahaan.php" class="submenu-item <?= ($activePage == 'laporan_perusahaan') ? 'active' : '' ?>">
            <i class="fas fa-chart-line sub-icon"></i>
            <span>Laporan Perusahaan</span>
        </a>

        <a href="laporan_user.php" class="submenu-item <?= ($activePage == 'laporan_user') ? 'active' : '' ?>">
            <i class="far fa-id-card sub-icon"></i>
            <span>Laporan User</span>
        </a>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupSubmenu(btnId, submenuId) {
        const btn = document.getElementById(btnId);
        const submenu = document.getElementById(submenuId);

        if (btn && submenu) {
            if (submenu.classList.contains('show')) {
                submenu.style.maxHeight = submenu.scrollHeight + "px";
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const isOpen = submenu.classList.contains('show');
                closeAllSubmenus(submenuId);

                if (isOpen) {
                    submenu.style.maxHeight = '0';
                    submenu.classList.remove('show');
                    btn.classList.remove('active');
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    submenu.classList.add('show');
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                    btn.classList.add('active');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        }
    }

    function closeAllSubmenus(exceptId) {
        const allSubmenus = document.querySelectorAll('.submenu');
        const allBtns = document.querySelectorAll('.has-submenu');

        allSubmenus.forEach((menu, index) => {
            if (menu.id !== exceptId && menu.classList.contains('show')) {
                menu.style.maxHeight = '0';
                menu.classList.remove('show');
                if(allBtns[index]) allBtns[index].classList.remove('active');
            }
        });
    }

    setupSubmenu('btnLowongan', 'submenuLowongan');
    setupSubmenu('btnPerusahaan', 'submenuPerusahaan');
    setupSubmenu('btnLaporan', 'submenuLaporan');
});
</script>