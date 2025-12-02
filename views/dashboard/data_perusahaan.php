<?php
// --- 1. SETUP KONEKSI ---
require __DIR__ . '/../../vendor/autoload.php';
use GuzzleHttp\Client;

$supabaseUrl = 'https://tkjnbelcgfwpbhppsnrl.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRram5iZWxjZ2Z3cGJocHBzbnJsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTc0MDc2MiwiZXhwIjoyMDc3MzE2NzYyfQ.vZoNXxMWtoG4ktg7K6Whqv8EFzCv7qbS3OAHEfxVoR0';

$list_data = [];
$keyword = isset($_GET['q']) ? $_GET['q'] : '';

try {
    $client = new Client([
        'base_uri' => $supabaseUrl . '/rest/v1/',
        'headers' => [
            'apikey' => $supabaseKey, 
            'Authorization' => 'Bearer ' . $supabaseKey,
        ],
        'http_errors' => false
    ]);

    // QUERY: Hanya ambil perusahaan dengan status 'disetujui'
    $queryUrl = 'perusahaan?select=*,lowongan(count)&status_persetujuan=eq.disetujui&order=id_perusahaan.desc';
    
    if (!empty($keyword)) {
        $queryUrl .= '&nama_perusahaan=ilike.*' . urlencode($keyword) . '*';
    }

    $res = $client->get($queryUrl);
    if ($res->getStatusCode() == 200) {
        $list_data = json_decode($res->getBody(), true);
    }
} catch (Exception $e) { }

$activePage = 'data_perusahaan'; 
include 'header.php';
include 'sidebar.php';
include 'topbar.php';
?>

<style>
  /* --- LAYOUT UTAMA --- */
  body { 
      /* PERUBAHAN DI SINI: BACKGROUND IMAGE */
      background-image: url('backgroundamin.png');
      background-size: cover;       /* Agar gambar memenuhi layar */
      background-position: center;  /* Posisi gambar di tengah */
      background-repeat: no-repeat; /* Jangan diulang-ulang */
      background-attachment: fixed; /* Background tetap saat discroll */
      
      font-family: 'Inter', sans-serif; 
  }
  
  .main-content { 
      margin-top: 55px !important; 
      margin-left: 240px !important; 
      padding: 0px 35px 30px 35px !important; 
      transition: all 0.3s;
  }
  @media (max-width: 992px) { .main-content { margin-left: 0 !important; padding: 15px !important; } }

  /* JUDUL (Mepet Atas) */
  .page-header-title {
      font-size: 20px; font-weight: 700; color: #2B3674;
      margin-top: 0px !important; padding-top: 0px !important;
      line-height: 1 !important; transform: translateY(-15px); 
      margin-bottom: 20px;
      /* Tambahan shadow text biar terbaca jika background gelap */
      text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
  }

  /* --- TOOLBAR --- */
  .top-action-wrapper {
      display: flex; gap: 15px; margin-bottom: 25px; margin-top: -10px; 
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

  .btn-custom {
      background: white; border: 1px solid #E0E5F2; border-radius: 12px;
      padding: 0 20px; display: flex; align-items: center; gap: 8px;
      color: #666; font-weight: 600; font-size: 14px; cursor: pointer; 
      height: 46px; text-decoration: none; transition: 0.2s;
  }
  .btn-custom:hover { transform: translateY(-2px); }

  /* --- LIST CARD --- */
  .list-card {
      background: white; border: 1px solid #5967FF; border-radius: 12px;
      padding: 20px 40px 20px 25px; 
      margin-bottom: 15px;
      display: flex; align-items: center; justify-content: flex-start; 
      gap: 0; 
      transition: all 0.2s; cursor: pointer; position: relative;
  }
  .list-card:hover { background-color: #F8F9FF; transform: translateX(5px); }
  .list-card.active { background-color: #EFF2FF; border-width: 2px; }

  /* KOLOM 1: NAMA */
  .lc-name { 
      flex: 1; min-width: 180px; 
      font-weight: 700; color: #2B3674; font-size: 15px; line-height: 1.4; 
      padding-right: 10px;
  }

  /* KOLOM 2: LOWONGAN */
  .lc-stats { 
      width: 150px; flex-shrink: 0; display: flex; align-items: center; gap: 8px;
      font-weight: 600; color: #2B3674; font-size: 14px;
  }
  .num-box { font-weight: 800; font-size: 16px; color: #5967FF; }

  /* KOLOM 3: TANGGAL */
  .lc-date { 
      width: 140px; flex-shrink: 0; font-size: 13px; color: #A3AED0; 
  }
  
  /* KOLOM 5: DETAIL */
  .lc-action { 
      width: 100px; flex-shrink: 0; text-align: center; display: flex; justify-content: center;
  }
  .btn-detail-row {
      background-color: #11047A; color: white; text-decoration: none;
      padding: 10px 25px; border-radius: 8px; font-size: 12px; font-weight: 600; 
      display: inline-block; width: 100%; text-align: center;
  }

  /* --- PREVIEW PANEL --- */
  .preview-wrapper { position: sticky; top: 85px; }
  .preview-card {
      background: white; border-radius: 20px; padding: 25px 20px; 
      text-align: center; min-height: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
      display: flex; flex-direction: column;
  }
  .pc-logo {
      width: 70px; height: 70px; background: #F4F7FE; border-radius: 50%; margin: 0 auto 15px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: 800; color: #5967FF; overflow: hidden;
  }
  .pc-list { list-style: none; padding: 0; text-align: left; margin-bottom: 30px; flex-grow: 1; }
  .pc-list li { display: flex; gap: 10px; margin-bottom: 12px; font-size: 12px; color: #707EAE; line-height: 1.4; }
  .pc-list i { color: #05CD99; font-size: 13px; width: 20px; text-align: center; }
  .empty-state { color: #A3AED0; margin-top: 100px; font-size: 13px; }
  
  .btn-sidebar-full {
      width: 100%; padding: 12px; background-color: #5967FF; color: white;
      border-radius: 12px; font-weight: 600; text-decoration: none; display: block;
      text-align: center; margin-top: 10px; transition: 0.2s;
  }
  .btn-sidebar-full:hover { background-color: #434CE8; color: white; }
</style>

<div class="main-content">
    
    <h3 class="page-header-title">Data Perusahaan</h3>

    <div class="top-action-wrapper">
        <form action="" method="GET" style="flex-grow:1; display:flex;">
            <div class="search-bar-large">
                <input type="text" name="q" placeholder="Cari perusahaan..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
                <button type="submit" class="search-btn-transparent"><i class="fas fa-search"></i></button>
            </div>
        </form>
        
        <div class="btn-custom"><i class="fas fa-sliders-h"></i> Filter</div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <?php if (empty($list_data)): ?>
                <div class="text-center py-5 text-muted" style="background:white; border-radius:12px;">
                    <p>Tidak ada data perusahaan.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($list_data as $row): 
                    $id = $row['id_perusahaan'];
                    $nama = $row['nama_perusahaan'];
                    $jml_loker = $row['lowongan'][0]['count'] ?? 0;
                    
                    if (!empty($row['dibuat_pada'])) {
                        $tgl_gabung = date('d M Y', strtotime($row['dibuat_pada']));
                    } else {
                        $tgl_gabung = '-'; 
                    }
                    
                    $jsonArray = [
                        'id' => $id,
                        'nama' => $nama,
                        'logo' => $row['logo'] ?? '',
                        'loker' => $jml_loker,
                        'tgl' => $tgl_gabung,
                        'alamat' => $row['alamat'] ?? 'Belum ada alamat',
                    ];
                    $jsonString = htmlspecialchars(json_encode($jsonArray), ENT_QUOTES, 'UTF-8');
                ?>

                <div class="list-card" onclick="updatePreview(this)" data-json="<?= $jsonString ?>">
                    <div class="lc-name"><?= htmlspecialchars($nama) ?></div>
                    
                    <div class="lc-stats">
                        <span class="num-box"><?= $jml_loker ?></span> 
                        <span>Lowongan</span>
                    </div>
                    
                    <div class="lc-date">Bergabung <br><?= $tgl_gabung ?></div>
                    
                    <div class="lc-action">
                        <a href="detail_perusahaan.php?id=<?= $id ?>" class="btn-detail-row">Detail</a>
                    </div>
                </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-3">
            <div class="preview-wrapper">
                <div class="preview-card">
                    <div id="emptyState" class="empty-state">
                        <i class="fas fa-mouse-pointer fa-2x mb-3"></i>
                        <p>Pilih perusahaan di kiri<br>untuk melihat ringkasan.</p>
                    </div>

                    <div id="previewContent" style="display:none; height: 100%; flex-direction: column;">
                        <div class="pc-logo" id="pLogo">PT</div>
                        <h4 style="font-weight:700; color:#2B3674; margin-bottom:20px;" id="pName">Nama PT</h4>

                        <ul class="pc-list">
                            <li><i class="fas fa-check-circle"></i><span>Informasi perusahaan lengkap</span></li>
                            <li><i class="fas fa-check-circle"></i><span>Terpercaya</span></li>
                            <li><i class="fas fa-star" style="color:#FFB547"></i><span>Rating perusahaan<br><strong class="text-dark">9.4</strong></span></li>
                            <li><i class="far fa-calendar-alt" style="color:#5967FF"></i><span>Tanggal bergabung<br><strong class="text-dark" id="pDate">-</strong></span></li>
                            <li><i class="fas fa-map-marker-alt" style="color:#FF5B5B"></i><span>Alamat perusahaan<br><span id="pAddr" class="text-dark">-</span></span></li>
                            
                            <li><i class="fas fa-briefcase text-primary"></i><span>Jumlah Lowongan<br><strong class="text-dark" id="pLoker">0</strong></span></li>
                        </ul>

                        <a href="#" id="btnSidebarDetail" class="btn-sidebar-full">
                            Lihat Detail Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updatePreview(el) {
    document.querySelectorAll('.list-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');

    const rawData = el.getAttribute('data-json');
    const data = JSON.parse(rawData);

    document.getElementById('emptyState').style.display = 'none';
    const pContent = document.getElementById('previewContent');
    pContent.style.display = 'flex';

    document.getElementById('pName').innerText = data.nama;
    document.getElementById('pDate').innerText = data.tgl;
    document.getElementById('pAddr').innerText = data.alamat;
    document.getElementById('pLoker').innerText = data.loker;
    document.getElementById('btnSidebarDetail').href = 'detail_perusahaan.php?id=' + data.id;

    const logoBox = document.getElementById('pLogo');
    if (data.logo && data.logo !== '') {
        logoBox.innerHTML = `<img src="${data.logo}" style="width:100%; height:100%; object-fit:contain;">`;
    } else {
        logoBox.innerHTML = data.nama.substring(0,2).toUpperCase();
    }
}
</script>

<?php include 'footer.php'; ?>