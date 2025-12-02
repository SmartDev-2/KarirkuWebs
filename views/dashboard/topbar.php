<?php
// --- 1. PHP: AMBIL DATA ADMIN DARI SUPABASE ---
require __DIR__ . '/../../vendor/autoload.php';
use GuzzleHttp\Client;

// Konfigurasi Supabase
$supabaseUrl = 'https://tkjnbelcgfwpbhppsnrl.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRram5iZWxjZ2Z3cGJocHBzbnJsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MTc0MDc2MiwiZXhwIjoyMDc3MzE2NzYyfQ.vZoNXxMWtoG4ktg7K6Whqv8EFzCv7qbS3OAHEfxVoR0';

// Data Default (Jika belum ada di DB)
$admin_nama = "Admin";
$admin_role = "Super Administrator";
// Default avatar placeholder jika foto kosong/error
$admin_foto_url = "https://ui-avatars.com/api/?name=Admin&background=5967FF&color=fff"; 

try {
    $client = new Client([
        'base_uri' => $supabaseUrl . '/rest/v1/',
        'headers' => [
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
        ],
        'http_errors' => false
    ]);

    // Ambil data tabel 'admin' (Ambil 1 baris pertama)
    $response = $client->get('admin?select=*&limit=1');
    
    if ($response->getStatusCode() == 200) {
        $result = json_decode($response->getBody(), true);
        
        if (!empty($result)) {
            $data_admin = $result[0];
            
            // 1. AMBIL NAMA
            if(isset($data_admin['nama'])) {
                $admin_nama = $data_admin['nama'];
            }

            // 2. AMBIL FOTO & SUSUN URL SUPABASE
            // Di database isinya: "profile/IMG_1462.JPG"
            // Kita ubah jadi: "https://tkjn.../storage/v1/object/public/profile/IMG_1462.JPG"
            if(isset($data_admin['foto']) && !empty($data_admin['foto'])) {
                $path_foto = $data_admin['foto'];
                
                // Cek: Kalau di database sudah full link (http), pakai langsung
                if (strpos($path_foto, 'http') === 0) {
                    $admin_foto_url = $path_foto;
                } else {
                    // Kalau belum, kita tempelkan URL Storage Public Supabase
                    $admin_foto_url = $supabaseUrl . "/storage/v1/object/public/" . $path_foto;
                }
            }
        }
    }
} catch (Exception $e) {}
?>

<style>
  /* --- STYLE KHUSUS TOPBAR --- */
  .navbar-custom {
    height: 70px; 
    position: fixed; 
    top: 0; left: 0; right: 0; 
    z-index: 1030;
    background-color: white;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  /* Brand / Logo Kiri */
  .brand-area {
    display: flex; align-items: center; text-decoration: none; gap: 10px;
  }
  .brand-img {
    width: 35px; height: 35px; 
    border-radius: 8px; 
    object-fit: contain;
  }
  .brand-text-blue { color: #5967FF; font-weight: 800; font-size: 18px; }
  .brand-text-dark { color: #333; font-weight: 600; font-size: 18px; }

  /* Bagian Kanan (Profil Dropdown) */
  .profile-wrapper { position: relative; cursor: pointer; user-select: none; }

  .profile-trigger {
    display: flex; align-items: center; gap: 12px;
    padding: 4px 8px 4px 12px;
    border-radius: 50px;
    transition: background 0.2s;
    border: 1px solid transparent;
  }
  .profile-trigger:hover { background-color: #F7F9FC; border-color: #F1F5F9; }

  .admin-avatar {
    width: 40px; height: 40px; 
    border-radius: 50%; 
    object-fit: cover;
    border: 2px solid #EEF2FF;
  }
  
  .admin-info { display: flex; flex-direction: column; text-align: right; }
  .admin-name { font-size: 14px; font-weight: 700; color: #2D3748; }
  .admin-role { font-size: 11px; color: #A0AEC0; font-weight: 500; }
  
  .icon-chevron { font-size: 12px; color: #A0AEC0; margin-left: 5px; transition: 0.2s; }
  .profile-wrapper.active .icon-chevron { transform: rotate(180deg); }

  /* Dropdown Menu */
  .dropdown-menu-custom {
    position: absolute; top: 130%; right: 0; width: 220px;
    background: white; border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid #EDF2F7; padding: 8px;
    opacity: 0; visibility: hidden; transform: translateY(-10px);
    transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
  }
  .profile-wrapper.active .dropdown-menu-custom { opacity: 1; visibility: visible; transform: translateY(0); }

  .menu-link {
    display: flex; align-items: center; gap: 10px; padding: 10px 15px;
    color: #4A5568; text-decoration: none; font-size: 14px; font-weight: 500;
    border-radius: 8px; transition: 0.2s;
  }
  .menu-link:hover { background-color: #F8F9FA; color: #5967FF; }
  .menu-link i { width: 18px; text-align: center; }
  .menu-divider { height: 1px; background: #F1F5F9; margin: 6px 0; }
  .menu-link.logout { color: #E53E3E; }
  .menu-link.logout:hover { background-color: #FFF5F5; color: #C53030; }
  
  @media (max-width: 768px) { .admin-info { display: none; } }
</style>

<nav class="navbar-custom">
  
  <a href="index.php" class="brand-area">
    <img src="../../assets/img/karirkulogo.png" alt="Logo" class="brand-img">
    <div>
        <span class="brand-text-blue">KarirKu</span>
        <span class="brand-text-dark">Admin</span>
    </div>
  </a>

  <div class="profile-wrapper" id="profileDropdown" onclick="toggleProfileDropdown()">
      
      <div class="profile-trigger">
          <div class="admin-info"> 
              <span class="admin-name"><?php echo htmlspecialchars($admin_nama); ?></span>
              <span class="admin-role"><?php echo htmlspecialchars($admin_role); ?></span>
          </div>

          <img src="<?php echo htmlspecialchars($admin_foto_url); ?>" alt="Admin" class="admin-avatar">
          
          <i class="fas fa-chevron-down icon-chevron"></i>
      </div>

      <div class="dropdown-menu-custom">
          <div style="padding: 8px 15px; font-size: 12px; color: #A0AEC0; font-weight: 600;">AKUN SAYA</div>
          
          <a href="#" class="menu-link"><i class="far fa-user"></i> Profil</a>
          <a href="#" class="menu-link"><i class="fas fa-cog"></i> Pengaturan</a>

          <div class="menu-divider"></div>

          <a href="../../logout.php" class="menu-link logout" onclick="return confirm('Yakin ingin keluar?');">
              <i class="fas fa-sign-out-alt"></i> Logout
          </a>
      </div>

  </div>
</nav>

<script>
function toggleProfileDropdown() {
    const wrapper = document.getElementById('profileDropdown');
    wrapper.classList.toggle('active');
}
document.addEventListener('click', function(event) {
    const wrapper = document.getElementById('profileDropdown');
    if (!wrapper.contains(event.target) && wrapper.classList.contains('active')) {
        wrapper.classList.remove('active');
    }
});
</script>