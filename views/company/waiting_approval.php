<?php
session_start();
require_once __DIR__ . '/../../function/supabase.php';

// Pastikan user sudah login dan merupakan perusahaan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'perusahaan') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek status persetujuan
$company = supabaseQuery('perusahaan', [
    'select' => 'status_persetujuan',
    'id_pengguna' => 'eq.' . $user_id
]);

// Jika status diterima, redirect ke index
if ($company['success'] && count($company['data']) > 0 && $company['data'][0]['status_persetujuan'] === 'disetujui') {
    header('Location: index.php');
    exit;
}

// Jika belum ada data perusahaan, redirect ke edit company
if ($company['success'] && count($company['data']) === 0) {
    header('Location: edit_company.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menunggu Persetujuan - Karirku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .custom-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        
        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
        }
        
        .navbar-logo img {
            height: 40px;
            width: auto;
        }
        
        .waiting-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .waiting-icon {
            font-size: 80px;
            color: #003399;
            margin-bottom: 20px;
        }
        
        .waiting-title {
            color: #003399;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .waiting-message {
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-outline-primary {
            border-color: #003399;
            color: #003399;
        }
        
        .btn-outline-primary:hover {
            background-color: #003399;
            color: white;
        }
        
        .auto-check {
            font-size: 14px;
            color: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Custom Navbar -->
    <nav class="custom-navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-logo">
                <img src="../../assets/img/karirkuperusahaan.png" alt="Karirku Logo">
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="waiting-container">
            <i class="bi bi-clock-history waiting-icon"></i>
            <h2 class="waiting-title">Menunggu Persetujuan</h2>
            <p class="waiting-message">
                Profil perusahaan Anda sedang dalam proses peninjauan oleh admin kami. 
                Proses ini biasanya memakan waktu 1-2 hari kerja. Anda akan mendapatkan 
                notifikasi via email setelah persetujuan diberikan.
            </p>
            <p class="text-muted mb-4">
                Status saat ini: <span class="badge bg-warning">Menunggu Persetujuan</span>
            </p>
            
            <p class="auto-check">
                <i class="bi bi-arrow-repeat"></i> Status akan diperiksa otomatis...
            </p>
            
            <a href="../../function/auth-process.php?action=logout" class="btn btn-outline-primary me-2">Logout</a>
            <a href="edit_company.php" class="btn btn-outline-secondary">Edit Profil</a>
        </div>
    </div>

    <script>
        // Auto check status setiap 10 detik
        function checkApprovalStatus() {
            fetch('../../function/auth-process.php?action=check_status')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'diterima') {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Check status setiap 10 detik
        setInterval(checkApprovalStatus, 10000);
        
        // Juga check ketika page load
        document.addEventListener('DOMContentLoaded', checkApprovalStatus);
    </script>
</body>
</html>