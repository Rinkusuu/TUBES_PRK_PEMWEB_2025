<?php
session_start();
include 'config.php';


$kelas_list = mysqli_query($conn, "SELECT id, nama FROM kelas ORDER BY nama");

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard_siswa.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $role = 'siswa';
    $kelas = isset($_POST['kelas']) && $_POST['kelas'] !== '' ? intval($_POST['kelas']) : null;
    

    if ($password_raw !== $confirm_password) {
        $error = "Password dan Konfirmasi Password tidak sama!";
    } else {
    

        if (!is_null($kelas)) {
            $k_check = mysqli_query($conn, "SELECT id FROM kelas WHERE id = $kelas");
            if (mysqli_num_rows($k_check) == 0) {
                $error = "Kelas tidak valid.";
            }
        }
    }

    $check_query = "SELECT id FROM users WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (empty($error) && mysqli_num_rows($check_result) > 0) {
        $error = "Username sudah digunakan!";
    } elseif (empty($error)) {
        $kelas_sql = is_null($kelas) ? "NULL" : $kelas;
        $query = "INSERT INTO users (username, password, nama_lengkap, role, kelas) 
                  VALUES ('$username', '$password', '$nama_lengkap', '$role', $kelas_sql)";
        
        if (mysqli_query($conn, $query)) {
            $success = "Pendaftaran berhasil! Silakan login.";
        } else {
            $error = "Terjadi kesalahan: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Ujian Online</title>
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
        
        
        .register-wrapper {
            display: flex;
            width: 100%;
            max-width: 1200px;
            min-height: 700px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
       
        .register-form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        
        .form-container {
            max-width: 480px;
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        
        .success-message {
            background: #d1fae5;
            color: #059669;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #10b981;
        }
        
        
        .form-group {
            margin-bottom: 24px;
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
        
        .form-input, .form-select {
            width: 100%;
            padding: 16px 50px 16px 20px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            transition: all 0.3s ease;
            color: #1f2937;
            appearance: none;
        }
        
        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }
        
        .form-input:focus, .form-select:focus {
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
        
       
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .strength-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            text-align: right;
        }
        
        
        .register-button {
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
        
        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        .register-button:active {
            transform: translateY(0);
        }
        
        
        .register-button.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .register-button.loading .button-text {
            opacity: 0;
        }
        
        .register-button .loading-spinner {
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
        
        .register-button.loading .loading-spinner {
            display: block;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
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
        
        .login-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: color 0.3s ease;
        }
        
        .login-link:hover {
            color: #4f46e5;
            text-decoration: underline;
        }
        
        
        .register-illustration {
            flex: 1;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-illustration::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="white" opacity="0.05"><circle cx="50" cy="50" r="3"/></svg>') repeat;
        }
        
        .illustration-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }
        
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #6366f1;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .logo-text {
            font-size: 36px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        
        .illustration-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        
        .illustration-description {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        
        .benefits-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .benefit-item i {
            color: #a7f3d0;
            font-size: 18px;
        }
        
        .benefit-text {
            color: white;
            font-size: 15px;
            font-weight: 500;
        }
        
       
        @media (max-width: 1024px) {
            .register-wrapper {
                flex-direction: column;
                max-width: 600px;
            }
            
            .register-illustration {
                padding: 40px 30px;
                order: -1; 
            }
            
            .register-form-section {
                padding: 40px 30px;
            }
            
            .logo-container {
                margin-bottom: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .form-title {
                font-size: 26px;
            }
            
            .illustration-title {
                font-size: 24px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }
            
            .logo-text {
                font-size: 28px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .register-wrapper {
                min-height: auto;
            }
            
            .form-input, .form-select {
                padding: 14px 45px 14px 15px;
            }
            
            .register-illustration, .register-form-section {
                padding: 30px 20px;
            }
        }
        
       
        .password-match {
            font-size: 12px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .match-valid {
            color: #10b981;
        }
        
        .match-invalid {
            color: #ef4444;
        }
        
        .password-match.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        
        <div class="register-form-section">
            <div class="form-container">
                <div class="form-header">
                    <h1 class="form-title">Bergabung Bersama Kami</h1>
                    <p class="form-subtitle">Buat akun untuk mulai mengikuti ujian online</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                        <br><small>Anda akan dialihkan ke halaman login dalam 5 detik...</small>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 5000);
                    </script>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                   
                    <div class="form-group">
                        <label for="nama_lengkap" class="form-label">
                            <i class="fas fa-user"></i>
                            Nama Lengkap
                        </label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="nama_lengkap" 
                                name="nama_lengkap" 
                                class="form-input" 
                                required 
                                placeholder="Masukkan nama lengkap Anda"
                                value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>"
                            >
                            <span class="input-icon">
                                <i class="fas fa-user-edit"></i>
                            </span>
                        </div>
                    </div>
                    
                   
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-at"></i>
                            Username
                        </label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                required 
                                placeholder="Buat username unik"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            >
                            <span class="input-icon">
                                <i class="fas fa-user-circle"></i>
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
                                placeholder="Buat password yang kuat"
                            >
                            <span class="input-icon">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Konfirmasi Password
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                required 
                                placeholder="Ketik ulang password Anda"
                            >
                            <span class="input-icon">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                        </div>
                        <div class="password-match" id="passwordMatch">
                            <i class="fas fa-check-circle"></i>
                            <span>Password cocok</span>
                        </div>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="kelas" class="form-label">
                            <i class="fas fa-school"></i>
                            Kelas (Opsional)
                        </label>
                        <div class="input-group">
                            <select id="kelas" name="kelas" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                <?php 
                                if ($kelas_list) {
                                
                                    mysqli_data_seek($kelas_list, 0);
                                    while ($k = mysqli_fetch_assoc($kelas_list)): 
                                ?>
                                    <option value="<?php echo intval($k['id']); ?>" 
                                        <?php echo (isset($_POST['kelas']) && $_POST['kelas'] == $k['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($k['nama']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                            <span class="input-icon">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                    </div>
                    
                    
                    <button type="submit" class="register-button" id="submitButton">
                        <span class="button-text">
                            <i class="fas fa-user-plus"></i>
                            Daftar Sekarang
                        </span>
                        <div class="loading-spinner"></div>
                    </button>
                </form>
                
                <div class="form-footer">
                    <p class="footer-text">
                        Sudah memiliki akun?
                        <a href="login.php" class="login-link">
                            Login di sini
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
       
        <div class="register-illustration">
            <div class="illustration-content">
                
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="logo-text">Examify<br></div>
                </div>
                
                <h2 class="illustration-title">Mulai Perjalanan Belajarmu</h2>
                
                <p class="illustration-description">
                    Bergabung dengan ribuan siswa yang telah meningkatkan prestasi mereka melalui platform ujian online kami.
                </p>
                
                <div class="benefits-list">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span class="benefit-text">Akses ke semua mata pelajaran</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span class="benefit-text">Riwayat ujian terperinci</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span class="benefit-text">Sertifikat digital otomatis</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
        
            strengthBar.className = 'strength-bar';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
            } else if (strength < 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Lemah';
                strengthText.style.color = '#ef4444';
            } else if (strength < 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Cukup';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kuat';
                strengthText.style.color = '#10b981';
            }
        });
        
    
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        
        function checkPasswordMatch() {
            if (password.value && confirmPassword.value) {
                if (password.value === confirmPassword.value) {
                    passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i><span>Password cocok</span>';
                    passwordMatch.className = 'password-match show match-valid';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i><span>Password tidak cocok</span>';
                    passwordMatch.className = 'password-match show match-invalid';
                }
            } else {
                passwordMatch.className = 'password-match';
            }
        }
        
        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
        

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const button = document.getElementById('submitButton');
            
        
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan Konfirmasi Password tidak sama!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
            
           
            button.classList.add('loading');
            button.disabled = true;
            
           
            setTimeout(function() {
                if (button.classList.contains('loading')) {
                    button.classList.remove('loading');
                    button.disabled = false;
                }
            }, 10000);
            
            return true;
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
