<?php
// sidebar.php
if (!isset($activePage)) {
    $activePage = 'halaman-utama';
}
if (!isset($base_url)) {
    $base_url = '../../';
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?php echo $base_url; ?>assets/img/logo.png" alt="Karirku Logo">
            <span class="logo-badge">.Perusahaan</span>
        </div>
    </div>

    <div class="sidebar-nav">
        <!-- Halaman Utama -->
        <a href="index.php" class="nav-item <?php echo $activePage == 'halaman-utama' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Halaman utama</span>
        </a>

        <!-- Lowongan -->
        <div class="nav-item <?php echo in_array($activePage, ['lowongan-saya', 'tambah-lowongan']) ? 'active' : ''; ?>" onclick="toggleLowonganMenu()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span style="flex: 1;">Lowongan</span>
            <svg class="collapse-icon <?php echo in_array($activePage, ['lowongan-saya', 'tambah-lowongan']) ? 'expanded' : ''; ?>" id="collapseIcon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6 12l4-4-4-4" stroke="currentColor" stroke-width="2" fill="none" />
            </svg>
        </div>

        <div class="nav-collapse <?php echo in_array($activePage, ['lowongan-saya', 'tambah-lowongan']) ? 'expanded' : ''; ?>" id="lowonganCollapse">
            <a href="lowongan.php" class="nav-collapse-item <?php echo $activePage == 'lowongan-saya' ? 'active' : ''; ?>">
                <span class="bullet"></span>
                Lowongan saya
            </a>
            <a href="tambah-lowongan.php" class="nav-collapse-item <?php echo $activePage == 'tambah-lowongan' ? 'active' : ''; ?>">
                <span class="bullet"></span>
                Tambah lowongan baru
            </a>
        </div>

        <!-- Perusahaan -->
        <a href="perusahaan.php" class="nav-item <?php echo $activePage == 'perusahaan' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Perusahaan</span>
        </a>

        <!-- Laporan -->
        <a href="laporan.php" class="nav-item <?php echo $activePage == 'laporan' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Laporan</span>
        </a>
    </div>
</div>

<script>
// Fungsi untuk toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
    }
}

// Fungsi toggle untuk menu Lowongan dengan transisi smooth
function toggleLowonganMenu() {
    const collapse = document.getElementById('lowonganCollapse');
    const icon = document.getElementById('collapseIcon');
    const navItem = event.currentTarget;
    
    if (!collapse || !icon) return;
    
    // Tambah active class ke nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    navItem.classList.add('active');
    
    if (collapse.classList.contains('expanded')) {
        // Collapse menu
        collapse.classList.remove('expanded');
        icon.classList.remove('expanded');
        
        setTimeout(() => {
            if (!collapse.classList.contains('expanded')) {
                collapse.style.maxHeight = '0';
            }
        }, 300);
    } else {
        // Expand menu
        collapse.classList.add('expanded');
        icon.classList.add('expanded');
        
        const items = collapse.querySelectorAll('.nav-collapse-item');
        const itemHeight = 36;
        const totalHeight = items.length * itemHeight;
        collapse.style.maxHeight = totalHeight + 'px';
    }
}

// Initialize sidebar state
document.addEventListener('DOMContentLoaded', function() {
    const collapse = document.getElementById('lowonganCollapse');
    const icon = document.getElementById('collapseIcon');
    
    if (collapse && collapse.classList.contains('expanded')) {
        const items = collapse.querySelectorAll('.nav-collapse-item');
        const itemHeight = 36;
        const totalHeight = items.length * itemHeight;
        collapse.style.maxHeight = totalHeight + 'px';
    }
});
</script>