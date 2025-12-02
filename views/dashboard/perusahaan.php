<?php 
// --- 1. SETUP UTAMA & KONEKSI ---
require_once 'supabase.php'; // GUNAKAN FILE YANG SAMA

// --- 2. TENTUKAN HALAMAN MANA YANG DIBUKA ---
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'data';

// --- 3. DATA UNTUK SIDEBAR ---
$activePage = 'perusahaan'; 

// Hitung notifikasi pending untuk badge sidebar
$count_pending_perusahaan = 0;
$result = supabaseQuery('perusahaan', [
    'select' => 'id_perusahaan',
    'status_persetujuan' => 'eq.menunggu'
], ['count' => 'exact']);

if ($result['success'] && isset($result['count'])) {
    $count_pending_perusahaan = $result['count'];
}

// --- 4. LOAD LAYOUT UTAMA ---
include 'header.php';
include 'sidebar.php';
include 'topbar.php';
?>

<div class="main-content-wrapper">
    <?php 
      if ($currentTab == 'data') {
          // Jika url ?tab=data, panggil file data_perusahaan.php
          include 'data_perusahaan.php'; 
      } elseif ($currentTab == 'verifikasi') {
          // Jika url ?tab=verifikasi, panggil file verifikasi.php
          include 'verifikasi.php';
      }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>