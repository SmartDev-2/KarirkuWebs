<?php
session_start();

// Jika tidak ada token reset, redirect ke lupa password
if (!isset($_GET['token']) && !isset($_SESSION['reset_token'])) {
    $_SESSION['error'] = "Token reset tidak valid";
    header('Location: forgot-password.php');
    exit;
}

// Ambil token dari URL atau session
$token = $_GET['token'] ?? $_SESSION['reset_token'] ?? '';

// Jika user sudah login, redirect ke index
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reset Password - Karirku</title>
    <!-- Include semua CSS yang sama seperti forgot-password.php -->
    <!-- Favicon -->
    <link href="../assets/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../assets/css/auth.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        /* Include semua style dari forgot-password.php */
        .login-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: url("../assets/img/background-login.png") center center no-repeat;
            background-size: cover;
            text-align: center;
            padding: 40px 20px;
        }

        .intro-text h1 {
            color: #002E92;
            font-size: 2rem;
            font-weight: 600;
            line-height: 1.4;
            max-width: 700px;
        }

        .login-card1 {
            background: white;
            border-radius: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            margin-bottom: 20px;
            margin-top: -15px;
        }

        .btn-login-primary {
            background-color: #001f66;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 32px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-login-primary:hover {
            background-color: #002c99;
        }

        .form-control {
            width: 100%;
            padding: 10px 5px;
            border: none;
            border-bottom: 1px solid #ccc;
            border-radius: 0;
            background: transparent;
            font-size: 14px;
            color: #333;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-bottom: 1px solid #001f66;
            box-shadow: none;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #001f66;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }

        .step.active {
            background: #001f66;
            color: white;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #ddd;
            margin: 0 5px;
            align-self: center;
        }
    </style>
</head>

<body>
    <!-- Navbar (sama seperti forgot-password.php) -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between">
            <a href="index.php" class="navbar-brand d-flex align-items-center text-center py-0">
                <img src="../assets/img/logo.png" alt="">
            </a>
        </div>
    </nav>

    <!-- Reset Password Container -->
    <div class="login-container">
        <div class="intro-text">
            <h1>Buat Password Baru</h1>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step">1</div>
            <div class="step-line"></div>
            <div class="step active">2</div>
            <div class="step-line"></div>
            <div class="step">3</div>
        </div>

        <div class="login-card1">
            <div class="login-body">
                <h4 class="login-title"><img src="../assets/img/karirkulogo.png" alt="" style="width: 40px;"> Password Baru</h4>

                <form id="resetPasswordForm" action="../function/auth-process.php" method="POST">
                    <input type="hidden" name="action" value="reset-password">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <input type="password" id="new_password" name="new_password" class="form-control" required
                            placeholder="Password Baru" minlength="6">
                    </div>

                    <div class="form-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                            placeholder="Konfirmasi Password Baru">
                    </div>

                    <button type="submit" class="btn-login-primary">Reset Password</button>
                </form>

                <!-- Pesan Error -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mt-3">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="forgot-password.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Kembali ke Verifikasi Email
        </a>
    </div>

    <!-- JavaScript untuk validasi password -->
    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password harus minimal 6 karakter!');
                return false;
            }
        });
        // Auto-hide notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');

            alerts.forEach(alert => {
                // Create progress bar
                const progressBar = document.createElement('div');
                progressBar.className = 'alert-progress';
                alert.style.position = 'relative';
                alert.appendChild(progressBar);

                // Auto hide after 5 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Manual close button
            const closeButtons = document.querySelectorAll('.alert-dismissible .btn-close');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const alert = this.closest('.alert');
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            });
        });
    </script>
</body>

</html>