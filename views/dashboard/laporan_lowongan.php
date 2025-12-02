<?php
// 1. Set active page
$activePage = 'laporan_lowongan';

// 2. Panggil Header, Topbar, Sidebar
require_once 'header.php';
require_once 'topbar.php';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    /* --- FONTS & LAYOUT (Sesuai Referensi Data Perusahaan) --- */
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }

    .main-content { 
        margin-top: 55px !important; 
        margin-left: 240px !important; 
        padding: 0px 35px 30px 35px !important; 
        transition: all 0.3s;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

    /* JUDUL (Mepet Atas - Sesuai Request) */
    .page-header-title {
        font-size: 20px; font-weight: 700; color: #2B3674;
        margin-top: 0px !important; padding-top: 0px !important;
        line-height: 1 !important; transform: translateY(-15px); 
        margin-bottom: 25px;
    }

    /* --- FILTER SECTION (CENTER) --- */
    .filter-wrapper {
        display: flex;
        justify-content: center; /* PENTING: Membuat elemen ke tengah */
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .filter-label {
        font-weight: 600;
        color: #A3AED0; /* Abu-abu sesuai tema */
        font-size: 14px;
    }

    /* Input Tanggal Custom */
    .date-picker-box {
        position: relative;
        width: 250px;
    }
    .date-input {
        width: 100%;
        padding: 10px 15px;
        border-radius: 30px;
        border: 1px solid #5967FF; /* Biru Border */
        outline: none;
        color: #2B3674;
        font-weight: 600;
        text-align: center;
        background: white;
        cursor: pointer;
        font-size: 13px;
    }
    .date-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #5967FF;
        pointer-events: none;
    }

    /* Tombol Status (Diterima/Ditolak) */
    .status-group {
        display: flex;
        background: white;
        border-radius: 30px;
        padding: 4px;
        border: 1px solid #E0E5F2;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .btn-status {
        border: none;
        background: transparent;
        padding: 8px 25px;
        border-radius: 25px;
        color: #A3AED0;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s;
    }
    /* Warna Biru Aktif (Sesuai Referensi Detail Button) */
    .btn-status.active {
        background-color: #11047A; 
        color: white;
        box-shadow: 0 4px 10px rgba(17, 4, 122, 0.2);
    }

    /* --- CARD LIST ITEM --- */
    .card-item {
        background: white;
        border: 1px solid #E0E5F2; /* Border halus */
        border-radius: 15px;
        padding: 20px 25px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }
    /* Hover Effect Biru */
    .card-item:hover {
        border-color: #5967FF;
        transform: translateX(5px);
        background-color: #F8F9FF;
    }

    .item-title {
        font-size: 16px; font-weight: 700; color: #2B3674; margin-bottom: 5px;
    }
    .item-sub {
        font-size: 13px; color: #A3AED0; margin-bottom: 0;
    }
    .item-time {
        font-size: 12px; color: #707EAE; font-style: italic; margin-top: 5px; display: block;
    }

    /* Tombol Detail (Biru Gelap Sesuai Referensi) */
    .btn-detail {
        background-color: #11047A; /* Warna tombol detail di referensi */
        color: white;
        padding: 10px 25px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        border: none;
        transition: 0.2s;
    }
    .btn-detail:hover {
        background-color: #0d035e;
        color: white;
        transform: translateY(-2px);
    }

</style>

<div class="main-content">
    
    <h3 class="page-header-title">Laporan Lowongan</h3>

    <div class="filter-wrapper">
        
        <span class="filter-label">Rentang waktu</span>

        <div class="date-picker-box">
            <input type="text" id="dateRange" class="date-input" placeholder="Pilih Tanggal">
            <i class="fas fa-calendar-alt date-icon"></i>
        </div>

        <div class="status-group">
            <button class="btn-status active" onclick="setActive(this)">diterima</button>
            <button class="btn-status" onclick="setActive(this)">ditolak</button>
        </div>

    </div>

    <div class="laporan-list">
        
        <div class="card-item">
            <div>
                <h5 class="item-title">UI/UX Designer</h5>
                <p class="item-sub">Jakarta</p>
                <small class="item-time">Baru saja</small>
            </div>
            <button class="btn-detail">Detail</button>
        </div>

        <div class="card-item">
            <div>
                <h5 class="item-title">Frontend Developer</h5>
                <p class="item-sub">Bandung</p>
                <small class="item-time">1 jam yang lalu</small>
            </div>
            <button class="btn-detail">Detail</button>
        </div>

        <div class="card-item">
            <div>
                <h5 class="item-title">Backend Engineer</h5>
                <p class="item-sub">Surabaya</p>
                <small class="item-time">12 jam yang lalu</small>
            </div>
            <button class="btn-detail">Detail</button>
        </div>

        <div class="card-item">
            <div>
                <h5 class="item-title">Fullstack Developer</h5>
                <p class="item-sub">Jakarta</p>
                <small class="item-time">1 hari yang lalu</small>
            </div>
            <button class="btn-detail">Detail</button>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // 1. Setup Date Picker
    flatpickr("#dateRange", {
        mode: "range",              
        dateFormat: "d/m/Y",        
        defaultDate: ["25/03/2025", "12/07/2025"], 
        locale: { rangeSeparator: " - " }
    });

    // 2. Setup Tombol Aktif (Diterima/Ditolak)
    function setActive(btn) {
        // Hapus class active dari semua tombol dalam grup
        const buttons = document.querySelectorAll('.btn-status');
        buttons.forEach(b => b.classList.remove('active'));
        
        // Tambahkan class active ke tombol yang diklik
        btn.classList.add('active');
    }

    // 3. Setup Tombol Detail
    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            alert("Membuka detail lowongan...");
        });
    });
</script>

<?php require_once 'footer.php'; ?>