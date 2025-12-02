<?php
// --- 1. LOGIKA PHP (BACKEND) ---
require_once __DIR__ . '/supabase.php';

// A. LOGIKA HAPUS USER & CATAT LAPORAN
if (isset($_GET['hapus']) && isset($_GET['id'])) {
    $id_user = (int)$_GET['id'];
    
    // 1. Ambil data nama sebelum dihapus (untuk log)
    $cek_user = supabaseQuery('pengguna', [
        'select' => 'nama_lengkap, email',
        'id_pengguna' => 'eq.' . $id_user
    ]);

    $nama_hapus = 'User';
    $email_hapus = '-';
    if ($cek_user['success'] && !empty($cek_user['data'])) {
        $nama_hapus = $cek_user['data'][0]['nama_lengkap'];
        $email_hapus = $cek_user['data'][0]['email'];
    }

    // 2. Proses Hapus
    $result = supabaseDelete('pengguna', 'id_pengguna', $id_user); 

    if ($result['success']) {
        // 3. CATAT KE RIWAYAT LAPORAN (Manual Insert via PHP)
        $log_data = [
            'judul'      => 'Penghapusan User',
            'deskripsi'  => "Admin menghapus user: $nama_hapus ($email_hapus)",
            'kategori'   => 'user',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Asumsi fungsi insert Anda bernama supabaseInsert
        // Jika error, pastikan fungsi ini ada di supabase.php
        if(function_exists('supabaseInsert')) {
            supabaseInsert('riwayat_laporan', $log_data);
        }

        echo "<script>alert('User berhasil dihapus dan tercatat di laporan!'); window.location='user.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menghapus user. " . htmlspecialchars($result['error']) . "'); window.location='user.php';</script>";
    }
}

// B. AMBIL DATA DARI TABEL 'pengguna'
$list_users = [];
$result = supabaseQuery('pengguna', [
    'select' => '*',
    'role'   => 'eq.pencaker', 
    'order'  => 'id_pengguna.desc' 
]);

if ($result['success']) {
    $list_users = $result['data'];
}

// Helper Tanggal
function formatTanggal($dateString) {
    if(empty($dateString)) return "Baru Bergabung";
    $date = date_create($dateString);
    if(!$date) return "Baru Bergabung";
    return date_format($date, "d M Y"); 
}

// D. SETUP HALAMAN
$activePage = 'user';
require_once 'header.php';
require_once 'sidebar.php';
require_once 'topbar.php';
?>

<style>
    /* --- CSS BARU (SPLIT VIEW RAPAT) --- */
    body { background-color: #F4F7FE !important; font-family: 'DM Sans', sans-serif !important; margin: 0; }

    .main-content {
        margin-top: 65px !important; 
        margin-left: 260px !important; 
        padding: 10px 30px 30px 30px !important;
        background-color: #F4F7FE;
        min-height: 100vh;
    }
    @media (max-width: 1200px) { .main-content { margin-left: 0 !important; padding: 20px !important; } }

    /* Container Utama Split */
    .split-container { display: flex; gap: 30px; align-items: flex-start; max-width: 1600px; margin: 0 auto; }

    /* === KOLOM KIRI: LIST === */
    .left-list-col { flex: 1; min-width: 0; }

    .list-header-container { margin-bottom: 20px; }
    .page-title { font-size: 22px; font-weight: 700; color: #1B2559; margin: 0 0 15px 0; text-align: left; }

    .search-wrapper { display: flex; justify-content: center; width: 100%; margin-bottom: 10px; }
    .search-box { position: relative; width: 100%; }
    .search-box input {
        width: 100%; padding: 12px 20px 12px 45px; border-radius: 30px; border: none;
        background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.02); color: #2B3674; outline: none; transition: 0.3s;
    }
    .search-box input:focus { box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #A3AED0; font-size: 16px; }

    /* Kartu List User */
    .user-list-card {
        background: white; border-radius: 16px; padding: 18px 25px;
        margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;
        border: 1px solid transparent; transition: all 0.2s; position: relative; overflow: hidden;
        cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.01);
    }
    .user-list-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
    .user-list-card.active { border-color: #4318FF; background: #F8F9FF; }
    .user-list-card.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #4318FF; }

    .ulc-info { display: flex; align-items: center; gap: 15px; flex: 1; }
    .ulc-avatar {
        width: 45px; height: 45px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        background: #E0E5F2; display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #2B3674; font-size: 16px;
    }
    .ulc-text h5 { margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: #1B2559; }
    .ulc-text p { margin: 0; font-size: 13px; color: #A3AED0; }
    .ulc-meta { text-align: right; margin-right: 20px; min-width: 100px; }
    .ulc-date { font-size: 12px; color: #A3AED0; display: block; margin-bottom: 4px; }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #E6F9EB; color: #05CD99; }
    .btn-detail-mini { background: #11047A; color: white; border: none; padding: 7px 15px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-detail-mini:hover { background: #2B3674; }

    /* === KOLOM KANAN: PREVIEW === */
    .right-preview-col { width: 360px; flex-shrink: 0; position: sticky; top: 90px; }
    .preview-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); text-align: center; min-height: 480px; }
    .preview-avatar-large {
        width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 15px auto;
        object-fit: cover; background: #E0E5F2; border: 4px solid #F4F7FE;
        display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 700; color: #2B3674;
    }
    .preview-name { font-size: 18px; font-weight: 800; color: #1B2559; margin-bottom: 5px; }
    .preview-role { font-size: 13px; color: #A3AED0; font-weight: 500; margin-bottom: 25px; }
    .detail-list { list-style: none; padding: 0; margin: 0; text-align: left; }
    .detail-item { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #F4F7FE; }
    .detail-item:last-child { border-bottom: none; }
    .di-icon { width: 32px; height: 32px; border-radius: 10px; background: #F4F7FE; display: flex; align-items: center; justify-content: center; color: #4318FF; flex-shrink: 0; font-size: 14px; }
    .di-content label { display: block; font-size: 10px; color: #A3AED0; margin-bottom: 2px; font-weight: 600; text-transform: uppercase; }
    .di-content span { font-size: 13px; font-weight: 600; color: #1B2559; word-break: break-all; }
    .btn-delete-full {
        width: 100%; padding: 12px; border-radius: 15px; margin-top: 15px; background: #FFF5F5; color: #E53E3E; font-weight: 700; border: 1px solid #FED7D7;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: 0.2s; font-size: 13px;
    }
    .btn-delete-full:hover { background: #E53E3E; color: white; }
</style>

<div class="main-content">
    <div class="split-container">
        
        <div class="left-list-col">
            <div class="list-header-container">
                <h2 class="page-title">Daftar Pencari Kerja</h2>
                <div class="search-wrapper">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari nama user atau email...">
                    </div>
                </div>
            </div>

            <div id="userListContainer">
            <?php if (empty($list_users)): ?>
                <div style="text-align: center; padding: 50px; color: #A3AED0;">
                    <i class="fas fa-users-slash" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>Belum ada data Pencari Kerja.</p>
                </div>
            <?php else: ?>
                <?php foreach ($list_users as $index => $row): 
                    $id     = $row['id_pengguna'];
                    $nama   = htmlspecialchars($row['nama_lengkap'] ?? $row['username'] ?? 'User Tanpa Nama');
                    $email  = htmlspecialchars($row['email'] ?? '-');
                    $hp     = htmlspecialchars($row['no_hp'] ?? '-');
                    $foto   = $row['foto'] ?? '';
                    $role   = ucfirst($row['role'] ?? 'Pencaker');
                    $tglGabung = isset($row['created_at']) ? formatTanggal($row['created_at']) : 'Member Aktif'; 
                    $activeClass = ($index === 0) ? 'active' : '';
                ?>
                <div class="user-list-card js-user-card <?= $activeClass ?>"
                     onclick="showPreview(this)"
                     data-id="<?= $id ?>"
                     data-nama="<?= $nama ?>"
                     data-email="<?= $email ?>"
                     data-hp="<?= $hp ?>"
                     data-foto="<?= htmlspecialchars($foto) ?>"
                     data-role="<?= $role ?>"
                     data-tgl="<?= $tglGabung ?>">
                    
                    <div class="ulc-info">
                        <?php if(!empty($foto)): ?>
                            <img src="<?= htmlspecialchars($foto) ?>" class="ulc-avatar" alt="pic">
                        <?php else: ?>
                            <div class="ulc-avatar"><?= strtoupper(substr($nama, 0, 1)) ?></div>
                        <?php endif; ?>
                        
                        <div class="ulc-text">
                            <h5><?= $nama ?></h5>
                            <p><?= $email ?></p>
                        </div>
                    </div>
                    <div class="ulc-meta">
                        <span class="ulc-date"><?= $tglGabung ?></span>
                        <span class="status-badge">Aktif</span>
                    </div>
                    <button class="btn-detail-mini">Detail</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div id="noResultMsg" style="display:none; text-align:center; padding:30px; color:#A3AED0;">
                <i class="fas fa-search" style="font-size:30px; margin-bottom:10px;"></i>
                <p>User tidak ditemukan.</p>
            </div>
            </div>
        </div>

        <div class="right-preview-col">
            <div class="preview-card" id="previewPanel">
                <div id="previewAvatarContainer">
                    <div class="preview-avatar-large" id="pAvatarInitial">U</div>
                    <img src="" class="preview-avatar-large" id="pAvatarImg" style="display:none;">
                </div>
                <h3 class="preview-name" id="pNama">Nama User</h3>
                <div class="preview-role" id="pRole">Pencari Kerja</div>

                <div style="text-align:left; margin-top:30px;">
                    <ul class="detail-list">
                        <li class="detail-item">
                            <div class="di-icon"><i class="far fa-envelope"></i></div>
                            <div class="di-content"><label>Email Address</label><span id="pEmail">email@example.com</span></div>
                        </li>
                        <li class="detail-item">
                            <div class="di-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="di-content"><label>Nomor Telepon</label><span id="pHp">0812xxxx</span></div>
                        </li>
                        <li class="detail-item">
                            <div class="di-icon"><i class="far fa-calendar-alt"></i></div>
                            <div class="di-content"><label>Tanggal Bergabung</label><span id="pTgl">12 Nov 2025</span></div>
                        </li>
                         <li class="detail-item">
                            <div class="di-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="di-content"><label>Status Akun</label><span style="color:#05CD99;">Terverifikasi</span></div>
                        </li>
                    </ul>
                </div>

                <a href="#" id="pLinkHapus" class="btn-delete-full" onclick="return confirm('Yakin hapus? Data ini akan masuk ke laporan.');">
                    <i class="fas fa-trash-alt"></i> Hapus User Ini
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // FUNGSI 1: Preview Detail
    function showPreview(element) {
        document.querySelectorAll('.js-user-card').forEach(card => card.classList.remove('active'));
        element.classList.add('active');

        const d = element.dataset;
        document.getElementById('pNama').textContent = d.nama;
        document.getElementById('pEmail').textContent = d.email;
        document.getElementById('pHp').textContent = d.hp;
        document.getElementById('pRole').textContent = d.role;
        document.getElementById('pTgl').textContent = d.tgl;
        document.getElementById('pLinkHapus').href = `user.php?hapus=1&id=${d.id}`;

        const imgEl = document.getElementById('pAvatarImg');
        const initEl = document.getElementById('pAvatarInitial');
        if(d.foto && d.foto !== '') {
            imgEl.src = d.foto; imgEl.style.display = 'flex'; initEl.style.display = 'none';
        } else {
            const initial = d.nama.charAt(0).toUpperCase();
            initEl.textContent = initial; initEl.style.display = 'flex'; imgEl.style.display = 'none';
        }
    }

    // FUNGSI 2: Live Search
    const searchInput = document.getElementById('searchInput');
    const noResultMsg = document.getElementById('noResultMsg');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const cards = document.querySelectorAll('.js-user-card');
            let hasVisible = false;
            cards.forEach(card => {
                const name = card.dataset.nama.toLowerCase();
                const email = card.dataset.email.toLowerCase();
                if (name.includes(filter) || email.includes(filter)) {
                    card.style.display = ""; hasVisible = true;
                } else {
                    card.style.display = "none";
                }
            });
            noResultMsg.style.display = hasVisible ? 'none' : 'block';
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        const firstCard = document.querySelector('.js-user-card');
        if(firstCard) showPreview(firstCard);
    });
</script>

<?php require_once 'footer.php'; ?>