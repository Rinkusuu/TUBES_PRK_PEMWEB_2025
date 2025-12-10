<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['ujian_id'])) {
    header("Location: dashboard_siswa.php");
    exit();
}

$ujian_id = isset($_GET['ujian_id']) ? intval($_GET['ujian_id']) : 0;
$user_id = $_SESSION['user_id'];
$user_kelas = isset($_SESSION['kelas']) ? intval($_SESSION['kelas']) : null;

$query_ujian = "SELECT u.*, mp.nama as mata_pelajaran, mp.gambar, mp.deskripsi, u.kelas as kelas_id, COALESCE(k.nama, u.kelas) as kelas_nama
                FROM ujian u 
                JOIN mata_pelajaran mp ON u.mata_pelajaran_id = mp.id 
                LEFT JOIN kelas k ON u.kelas = k.id
                WHERE u.id = $ujian_id";
$result_ujian = mysqli_query($conn, $query_ujian);
$ujian = mysqli_fetch_assoc($result_ujian);

if (!$ujian) {
    header("Location: dashboard_siswa.php");
    exit();
}


if (!is_null($user_kelas) && intval($ujian['kelas_id']) !== $user_kelas) {
    header("Location: dashboard_siswa.php");
    exit();
}

$query_soal = "SELECT * FROM soal WHERE ujian_id = $ujian_id ORDER BY id";
$result_soal = mysqli_query($conn, $query_soal);
$total_soal = mysqli_num_rows($result_soal);


$query_riwayat = "SELECT MAX(skor) as nilai_terbaik, COUNT(*) as jumlah_percobaan 
                  FROM riwayat_ujian 
                  WHERE user_id = $user_id AND ujian_id = $ujian_id";
$result_riwayat = mysqli_query($conn, $query_riwayat);
$riwayat = mysqli_fetch_assoc($result_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Ujian - <?php echo $ujian['judul']; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --border: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.08), 0 8px 32px rgba(0,0,0,0.06);
            --accent: #6366f1;
            --accent-light: #eef2ff;
            --gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --gradient-2: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            --gradient-3: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --shadow: 0 1px 3px rgba(0,0,0,0.3), 0 4px 12px rgba(0,0,0,0.2);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.4), 0 8px 32px rgba(0,0,0,0.3);
            --accent-light: rgba(99, 102, 241, 0.15);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
     
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            transform: translateX(-4px);
        }
        
        .theme-toggle-btn {
            width: 44px;
            height: 44px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.25rem;
        }
        
        .theme-toggle-btn:hover {
            background: var(--accent);
            color: #fff;
        }
        
       
        .exam-content {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }
        
  
        .hero-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .hero-image {
            position: relative;
            height: 280px;
            background: var(--gradient);
            overflow: hidden;
        }
        
        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.9;
        }
        
        .hero-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(12px);
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
        }
        
        .hero-body {
            padding: 2rem;
        }
        
        .hero-title {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            text-align: center;
            padding: 1rem;
            background: var(--bg-hover);
            border-radius: 12px;
        }
        
        .stat-box .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-box .value {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        
        .stat-box .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
       
        .instructions-card {
            background: var(--accent-light);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .instructions-card h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--accent);
        }
        
        .instructions-card ul {
            list-style: none;
        }
        
        .instructions-card li {
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        
        .instructions-card li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-weight: 700;
        }
        
        .warning-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            margin-top: 1rem;
        }
        
        .warning-box .icon {
            font-size: 1.25rem;
            color: var(--danger);
        }
        
        .warning-box .text {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
      
        .start-exam-btn {
            width: 100%;
            padding: 1.25rem;
            background: var(--gradient);
            border: none;
            border-radius: 14px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
        }
        
        .start-exam-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        }
        
        .start-exam-btn:active {
            transform: translateY(0);
        }
        

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        
        .timer-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .timer-circle {
            width: 160px;
            height: 160px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }
        
        .timer-circle::before {
            content: '';
            position: absolute;
            inset: 8px;
            background: var(--bg-card);
            border-radius: 50%;
        }
        
        .timer-content {
            position: relative;
            text-align: center;
        }
        
        .timer-content .time {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .timer-content .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .timer-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .timer-card p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .info-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item .label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .info-item .value {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        
        .history-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .history-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .history-empty {
            text-align: center;
            padding: 2rem 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .history-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .history-stat {
            text-align: center;
            padding: 1rem;
            background: var(--bg-hover);
            border-radius: 12px;
        }
        
        .history-stat .value {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        
        .history-stat .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        @media (max-width: 968px) {
            .exam-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
            }
            
            .history-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard_siswa.php" class="back-btn">
                <span>←</span>
                <span>Kembali ke Dashboard</span>
            </a>
            <div class="theme-toggle-btn" onclick="toggleTheme()">🌙</div>
        </div>
        
        <div class="exam-content">
            <div class="main-content">
                <div class="hero-card">
                    <div class="hero-image">
                        <img src="images/<?php echo $ujian['gambar']; ?>" alt="<?php echo $ujian['mata_pelajaran']; ?>">
                        <span class="hero-badge"><?php echo $ujian['mata_pelajaran']; ?></span>
                    </div>
                    <div class="hero-body">
                        <h1 class="hero-title"><?php echo $ujian['judul']; ?></h1>
                        <p class="hero-subtitle"><?php echo $ujian['deskripsi'] ?? 'Persiapkan diri Anda sebaik mungkin sebelum memulai ujian'; ?></p>
                        
                        <div class="hero-stats">
                            <div class="stat-box">
                                <div class="icon">📝</div>
                                <div class="value"><?php echo $total_soal; ?></div>
                                <div class="label">Soal</div>
                            </div>
                            <div class="stat-box">
                                <div class="icon">⏱️</div>
                                <div class="value"><?php echo $ujian['waktu_pengerjaan']; ?></div>
                                <div class="label">Menit</div>
                            </div>
                            <div class="stat-box">
                                <div class="icon">🎯</div>
                                <div class="value"><?php echo $riwayat['jumlah_percobaan'] ?? 0; ?>x</div>
                                <div class="label">Percobaan</div>
                            </div>
                        </div>
                        
                        <div class="instructions-card">
                            <h3><span>📋</span> Petunjuk Pengerjaan</h3>
                            <ul>
                                <li>Ujian terdiri dari <strong><?php echo $total_soal; ?> soal pilihan ganda</strong></li>
                                <li>Waktu pengerjaan: <strong><?php echo $ujian['waktu_pengerjaan']; ?> menit</strong></li>
                                <li>Pilih satu jawaban yang paling tepat untuk setiap soal</li>
                                <li>Jawaban tidak dapat diubah setelah mengklik "Soal Selanjutnya"</li>
                                <li>Timer akan berjalan otomatis setelah ujian dimulai</li>
                                <li>Ujian akan berakhir otomatis saat waktu habis</li>
                            </ul>
                            <div class="warning-box">
                                <div class="icon">⚠️</div>
                                <div class="text">Pastikan koneksi internet Anda stabil selama mengerjakan ujian</div>
                            </div>
                        </div>
                        
                        <button class="start-exam-btn" onclick="startExam()">
                            <span>▶</span>
                            <span>Mulai Ujian Sekarang</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="sidebar">
                <div class="timer-card">
                    <div class="timer-circle">
                        <div class="timer-content">
                            <div class="time" id="previewTimer"><?php echo $ujian['waktu_pengerjaan']; ?></div>
                            <div class="label">Menit</div>
                        </div>
                    </div>
                    <h3>Durasi Ujian</h3>
                    <p>Timer akan dimulai setelah Anda klik tombol "Mulai Ujian"</p>
                </div>
                
                <div class="info-card">
                    <h3>Informasi Ujian</h3>
                    <div class="info-item">
                        <span class="label">Mata Pelajaran</span>
                        <span class="value"><?php echo $ujian['mata_pelajaran']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Total Soal</span>
                        <span class="value"><?php echo $total_soal; ?> Soal</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Durasi</span>
                        <span class="value"><?php echo $ujian['waktu_pengerjaan']; ?> Menit</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Nama Peserta</span>
                        <span class="value"><?php echo $_SESSION['nama_lengkap']; ?></span>
                    </div>
                    <div class="info-item">
    <span class="label">Kelas</span>
    <span class="value"><?php echo htmlspecialchars($ujian['kelas_nama'] ?? $ujian['kelas_id']); ?></span>
</div>
                </div>
                
                <?php if ($riwayat['jumlah_percobaan'] > 0): ?>
                <div class="history-card">
                    <h3>Riwayat Anda</h3>
                    <div class="history-stats">
                        <div class="history-stat">
                            <div class="value"><?php echo number_format($riwayat['nilai_terbaik'], 0); ?></div>
                            <div class="label">Nilai Terbaik</div>
                        </div>
                        <div class="history-stat">
                            <div class="value"><?php echo $riwayat['jumlah_percobaan']; ?>x</div>
                            <div class="label">Percobaan</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="history-card">
                    <h3>Riwayat Anda</h3>
                    <div class="history-empty">
                        <p>🎯</p>
                        <p>Ini adalah percobaan pertama Anda.<br>Semangat!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    
        function toggleTheme() {
            const body = document.body;
            
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        
     
        if (localStorage.getItem('theme') === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
        }
        
     
        let minutes = <?php echo $ujian['waktu_pengerjaan']; ?>;
        const timerElement = document.getElementById('previewTimer');
        
        setInterval(() => {
            if (minutes > 0) {
                minutes--;
                timerElement.textContent = minutes;
            } else {
                minutes = <?php echo $ujian['waktu_pengerjaan']; ?>;
            }
        }, 3000);
        
     
        function startExam() {
            if (confirm('Apakah Anda siap untuk memulai ujian?\n\nTimer akan dimulai setelah Anda klik OK.')) {
                window.location.href = 'kerjakan_ujian.php?ujian_id=<?php echo $ujian_id; ?>';
            }
        }
        
    
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>