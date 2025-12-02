<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Berhasil - KarirKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        :root {
            --primary: #5967FF;
            --text-main: #1A202C;
            --text-muted: #718096;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            /* Background sama persis dengan Login agar konsisten */
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('assets/img/background.png');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Kartu Logout Glassmorphism */
        .logout-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            text-align: center;
            animation: popUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            transform: scale(0.8);
            opacity: 0;
        }

        /* Animasi Muncul */
        @keyframes popUp {
            to { transform: scale(1); opacity: 1; }
        }

        /* Lingkaran Ikon */
        .icon-circle {
            width: 80px; height: 80px;
            background-color: #E6F6EC; /* Hijau Muda Soft */
            color: #27AE60; /* Hijau Sukses */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px;
            margin: 0 auto 20px auto;
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.15);
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(89, 103, 255, 0.3);
        }

        .btn-login:hover {
            background: #434CE8;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(89, 103, 255, 0.4);
        }

        .auto-redirect {
            margin-top: 20px;
            font-size: 12px;
            color: #A0AEC0;
        }
    </style>
</head>
<body>

    <div class="logout-card">
        <div class="icon-circle">
            <i class="fas fa-check"></i>
        </div>

        <h1>Berhasil Keluar</h1>
        <p>Sesi Anda telah berakhir dengan aman.<br>Sampai jumpa lagi!</p>

        <a href="login.php" class="btn-login">Masuk Kembali</a>

        <div class="auto-redirect">
            Otomatis kembali dalam <span id="countdown">3</span> detik...
        </div>
    </div>

    <script>
        var seconds = 3;
        var countdownSpan = document.getElementById('countdown');
        
        var interval = setInterval(function() {
            seconds--;
            countdownSpan.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = "login.php";
            }
        }, 1000);
    </script>

</body>
</html>