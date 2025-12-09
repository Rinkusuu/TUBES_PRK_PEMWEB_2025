<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_siswa.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['kelas'] = ($user['kelas'] === null || $user['kelas'] === '') ? null : intval($user['kelas']);
            if ($user['role'] === 'admin') {
                $_SESSION['kelas'] = null;
            }
            
            if ($user['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_siswa.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ujian Online</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
       
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1200px;
            min-height: 700px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
    
        .login-illustration {
            flex: 1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-illustration::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="white" opacity="0.05"><path d="M50,50 Q80,20 95,50 Q80,80 50,50 Z"/></svg>') repeat;
        }
        
        .brand-section {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #6366f1;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .brand-name {
            font-size: 32px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        
        .brand-tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            margin-bottom: 50px;
            line-height: 1.6;
        }
        
        .features-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .feature-text {
            color: white;
            font-size: 15px;
            font-weight: 500;
        }
        
        .login-form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-container {
            max-width: 420px;
            margin: 0 auto;
            width: 100%;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: #6b7280;
            font-size: 16px;
            font-weight: 400;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #dc2626;
        }
        
        .error-message i {
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #6366f1;
            font-size: 16px;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 50px 16px 20px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            transition: all 0.3s ease;
            color: #1f2937;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            pointer-events: none;
        }
        
        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            position: relative;
            min-height: 54px;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-button.loading .button-text {
            opacity: 0;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .login-button.loading .loading-spinner {
            display: block;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .login-button.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            color: #6b7280;
            font-size: 15px;
        }
        
        .register-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: color 0.3s ease;
        }
        
        .register-link:hover {
            color: #4f46e5;
            text-decoration: underline;
        }
        
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-illustration {
                padding: 40px 30px;
            }
            
            .login-form-section {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-wrapper {
                min-height: auto;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .brand-name {
                font-size: 26px;
            }
            
            .form-title {
                font-size: 26px;
            }
            
            .form-input {
                padding: 14px 45px 14px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-illustration">
            <div class="brand-section">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="brand-name">Examify<br></div>
                </div>
                
                <p class="brand-tagline">
                    Platform ujian online terintegrasi dengan sistem penilaian real-time dan analisis mendalam
                </p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="feature-text">Ujian dengan waktu terukur</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">Analisis performa detail</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="feature-text">Sertifikat digital otomatis</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="login-form-section">
            <div class="form-container">
                <div class="form-header">
                    <h1 class="form-title">Selamat Datang</h1>
                    <p class="form-subtitle">Masuk untuk mengakses dashboard Anda</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                required 
                                placeholder="Masukkan username Anda"
                            >
                            <span class="input-icon">
                                <i class="fas fa-at"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                required 
                                placeholder="Masukkan password Anda"
                            >
                            <span class="input-icon">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button" id="submitButton">
                        <span class="button-text">
                            <i class="fas fa-sign-in-alt"></i>
                            Masuk ke Akun
                        </span>
                        <div class="loading-spinner"></div>
                    </button>
                </form>
                
                <div class="form-footer">
                    <p class="footer-text">
                        Belum memiliki akun?
                        <a href="register.php" class="register-link">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('submitButton');
            const buttonText = button.querySelector('.button-text');
            const spinner = button.querySelector('.loading-spinner');
            
            button.classList.add('loading');
            
            
            button.disabled = true;
            
            setTimeout(function() {
                if (button.classList.contains('loading')) {
                    button.classList.remove('loading');
                    button.disabled = false;
                    console.log('Form submission timeout - removing loading state');
                }
            }, 10000);
        });
        
        window.addEventListener('beforeunload', function() {
            const button = document.getElementById('submitButton');
            if (button && button.classList.contains('loading')) {
                button.classList.remove('loading');
                button.disabled = false;
            }
        });
        
        window.addEventListener('pageshow', function(event) {
            const button = document.getElementById('submitButton');
            if (button && button.classList.contains('loading')) {
                button.classList.remove('loading');
                button.disabled = false;
            }
        });
    </script>
</body>
</html>