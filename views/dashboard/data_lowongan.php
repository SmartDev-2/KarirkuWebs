<?php 
  // --- 1. SETUP KONEKSI KE SUPABASE ---
  require_once __DIR__ . '/supabase.php';

  // --- 2. QUERY DATA LOWONGAN ---
  // KITA PAKAI CARA STRICT (KETAT):
  // Hanya minta data ke Supabase yang statusnya 'publish'.
  // Data 'ditolak', 'ditinjau', atau 'aktif' (jika ada sisa lama) TIDAK AKAN DIAMBIL.
  
  $daftar_lowongan = [];
  $keyword = isset($_GET['q']) ? $_GET['q'] : '';

  $query_params = [
      'select' => '*, perusahaan(nama_perusahaan)', // Ambil data lowongan + nama perusahaan
      'status' => 'eq.publish',                     // FILTER WAJIB: Hanya yang PUBLISH
      'order'  => 'dibuat_pada.desc'                // Urutkan dari yang terbaru
  ];

  $result = supabaseQuery('lowongan', $query_params);
  
  if ($result['success']) {
      $daftar_lowongan = $result['data'];
  } else {
      // Jika error, kosongkan array (jangan biarkan error tampil di user)
      $daftar_lowongan = [];
  }

  // --- 3. FILTER PENCARIAN (KEYWORD) ---
  // Filter ini hanya jalan jika user mengetik di kolom pencarian.
  if (!empty($keyword)) {
      $daftar_lowongan = array_filter($daftar_lowongan, function($item) use ($keyword) {
          $keyword = strtolower($keyword);
          $judul   = strtolower($item['judul'] ?? '');
          $pt      = strtolower($item['perusahaan']['nama_perusahaan'] ?? '');
          
          // Tampilkan jika Judul ATAU Nama PT cocok dengan keyword
          return (strpos($judul, $keyword) !== false) || (strpos($pt, $keyword) !== false);
      });
  }

  $activePage = 'data_lowongan'; 
  include 'header.php';
  include 'sidebar.php';
  include 'topbar.php';
?>

<style>
  /* --- GLOBAL STYLE --- */
  body { background-color: #F4F7FE; font-family: 'Inter', sans-serif; }
  
  .main-content { 
      margin-top: 65px !important; 
      margin-left: 240px !important; 
      padding: 10px 35px 30px 35px !important; 
      transition: all 0.3s;
  }
  @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

  /* --- TOOLBAR (SEARCH) --- */
  .top-action-wrapper {
      display: flex; gap: 15px; 
      margin-bottom: 20px; 
      margin-top: 15px; 
  }
  .search-bar-large {
      flex-grow: 1; background: white; border-radius: 30px; 
      padding: 8px 20px; display: flex; align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.02); border: 1px solid #E0E5F2;
  }
  .search-bar-large input {
      border: none; outline: none; width: 100%; font-size: 14px; color: #444; background: transparent;
  }
  .search-btn-transparent {
      background: transparent; border: none; color: #5967FF; font-size: 18px;
      cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px;
  }

  /* --- CARD CONTAINER --- */
  .content-card-wrapper {
      background: white; border-radius: 20px; padding: 25px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.02);
      min-height: 80vh; display: flex; flex-direction: column;
  }

  .section-title {
      font-size: 18px; font-weight: 700; color: #11047A; margin-bottom: 25px;
  }

  /* --- HEADER TABLE --- */
  .list-header {
      display: flex; padding: 0 25px 10px 25px;
      border-bottom: 1px solid #E0E5F2; margin-bottom: 15px;
  }
  .col-header {
      color: #A3AED0; font-size: 14px; font-weight: 500;
  }

  /* --- ROW ITEM --- */
  .list-row {
      background: white; border: 1px solid #E0E5F2; border-radius: 16px;
      padding: 20px 25px; margin-bottom: 15px;
      display: flex; align-items: center; 
      transition: all 0.2s;
      box-shadow: 0 2px 6px rgba(0,0,0,0.02);
  }
  .list-row:hover { 
      background-color: #F8F9FF; transform: translateX(5px); 
      border-color: #5967FF;
  }

  /* --- KOLOM --- */
  .col-name { flex: 2; min-width: 250px; padding-right: 15px; }
  .job-title-text { display: block; font-weight: 700; font-size: 16px; color: #11047A; margin-bottom: 4px; }
  .job-company-text { font-size: 13px; color: #A3AED0; font-weight: 500; }

  .col-loc { flex: 1; font-weight: 600; font-size: 14px; color: #2B3674; }
  .col-date { flex: 1; font-size: 13px; color: #A3AED0; line-height: 1.4; }

  .col-status { width: 120px; text-align: center; }
  .badge-active {
      background-color: #E6F9EB; color: #05CD99; padding: 6px 15px; 
      border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block;
      text-transform: capitalize;
  }

  .col-action { width: 100px; text-align: right; }
  .btn-detail-blue {
      background-color: #11047A; color: white; text-decoration: none;
      padding: 8px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; 
      display: inline-block; transition: 0.2s;
  }
  .btn-detail-blue:hover { background-color: #201396; transform: translateY(-2px); }

  /* SCROLLBAR */
  .scroll-container {
      overflow-y: auto; max-height: 65vh; padding-right: 10px;
  }
  .scroll-container::-webkit-scrollbar { width: 6px; }
  .scroll-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
  .scroll-container::-webkit-scrollbar-thumb { background: #11047A; border-radius: 10px; }

</style>

<div class="main-content">
    
    <div class="top-action-wrapper">
        <form action="" method="GET" style="flex-grow:1; display:flex;">
            <div class="search-bar-large">
                <input type="text" name="q" placeholder="Cari judul lowongan atau perusahaan..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
                <button type="submit" class="search-btn-transparent"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="content-card-wrapper">
        <h4 class="section-title">Data Lowongan (Terpublikasi)</h4>

        <div class="list-header">
            <div class="col-header col-name">Nama Lowongan</div>
            <div class="col-header col-loc">Lokasi</div>
            <div class="col-header col-date">Berlaku Hingga</div>
            <div class="col-header col-status">Status</div>
            <div class="col-header col-action"></div>
        </div>

        <div class="scroll-container">
            
            <?php if (empty($daftar_lowongan)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-briefcase" style="font-size: 30px; margin-bottom: 10px; color: #cbd5e1;"></i>
                    <p>Tidak ada lowongan yang terpublikasi saat ini.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($daftar_lowongan as $row): 
                    $id = $row['id_lowongan'];
                    $judul = $row['judul'] ?? 'Tanpa Judul';
                    
                    // Nama Perusahaan (Relasi)
                    $pt = isset($row['perusahaan']['nama_perusahaan']) ? $row['perusahaan']['nama_perusahaan'] : 'Perusahaan';
                    
                    // Lokasi
                    $lokasi = !empty($row['lokasi']) ? $row['lokasi'] : 'Indonesia';
                    
                    // Tanggal Berakhir
                    $tgl_akhir = !empty($row['tanggal_berakhir']) ? date('d M Y', strtotime($row['tanggal_berakhir'])) : 'Secepatnya';
                    
                    // Status Badge (Karena query sudah filter 'publish', kita bisa hardcode labelnya agar rapi)
                    $statusDisplay = 'Publish';
                ?>

                <div class="list-row">
                    
                    <div class="col-name">
                        <span class="job-title-text"><?= htmlspecialchars($judul) ?></span>
                        <span class="job-company-text"><?= htmlspecialchars($pt) ?></span>
                    </div>

                    <div class="col-loc">
                        <i class="fas fa-map-marker-alt me-1 text-muted" style="font-size:12px;"></i>
                        <?= htmlspecialchars($lokasi) ?>
                    </div>

                    <div class="col-date">
                        <?= $tgl_akhir ?>
                    </div>

                    <div class="col-status">
                        <span class="badge-active"><?= $statusDisplay ?></span>
                    </div>

                    <div class="col-action">
                        <a href="detail_lowongan.php?id=<?= $id ?>" class="btn-detail-blue">Detail</a>
                    </div>

                </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php include 'footer.php'; ?>